<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FunctionChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\TraitChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\AbstractInstantiation;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidFunctionCall;
use Psalm\Issue\InvalidMethodCall;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\NullArgument;
use Psalm\Issue\NullReference;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyNullArgument;
use Psalm\Issue\PossiblyNullReference;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\TypeCoercion;
use Psalm\Issue\UndefinedFunction;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\Generic;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\Scalar;
use Psalm\Type\Atomic\TNumeric;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TVoid;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TResource;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNumericString;

class CallChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @param   Context                         $context
     * @return  false|null
     */
    public static function analyzeFunctionCall(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\FuncCall $stmt,
        Context $context
    ) {
        $method = $stmt->name;

        if ($method instanceof PhpParser\Node\Name) {
            $first_arg = isset($stmt->args[0]) ? $stmt->args[0] : null;

            if ($method->parts === ['method_exists']) {
                $context->check_methods = false;
            } elseif ($method->parts === ['class_exists']) {
                if ($first_arg && $first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $context->addPhantomClass($first_arg->value->value);
                } else {
                    $context->check_classes = false;
                }
            } elseif ($method->parts === ['function_exists']) {
                $context->check_functions = false;
            } elseif ($method->parts === ['is_callable']) {
                $context->check_methods = false;
                $context->check_functions = false;
            } elseif ($method->parts === ['defined']) {
                $context->check_consts = false;
            } elseif ($method->parts === ['extract']) {
                $context->check_variables = false;
            } elseif ($method->parts === ['var_dump'] || $method->parts === ['shell_exec']) {
                if (IssueBuffer::accepts(
                    new ForbiddenCode(
                        'Unsafe ' . implode('', $method->parts),
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } elseif ($method->parts === ['define']) {
                if ($first_arg && $first_arg->value instanceof PhpParser\Node\Scalar\String_) {
                    $second_arg = $stmt->args[1];
                    ExpressionChecker::analyze($statements_checker, $second_arg->value, $context);
                    $const_name = $first_arg->value->value;

                    $statements_checker->setConstType(
                        $const_name,
                        isset($second_arg->value->inferredType) ? $second_arg->value->inferredType : Type::getMixed(),
                        $context
                    );
                } else {
                    $context->check_consts = false;
                }
            }
        }

        $method_id = null;
        $function_params = null;
        $in_call_map = false;

        $is_stubbed = false;

        $function_storage = null;

        $code_location = new CodeLocation($statements_checker->getSource(), $stmt);
        $defined_constants = [];

        $function_exists = false;

        if ($stmt->name instanceof PhpParser\Node\Expr) {
            if (ExpressionChecker::analyze($statements_checker, $stmt->name, $context) === false) {
                return false;
            }

            if (isset($stmt->name->inferredType)) {
                foreach ($stmt->name->inferredType->types as $var_type_part) {
                    if ($var_type_part instanceof Type\Atomic\Fn) {
                        $function_params = $var_type_part->params;

                        if ($var_type_part->return_type) {
                            if (isset($stmt->inferredType)) {
                                $stmt->inferredType = Type::combineUnionTypes(
                                    $stmt->inferredType,
                                    $var_type_part->return_type
                                );
                            } else {
                                $stmt->inferredType = $var_type_part->return_type;
                            }
                        }

                        $function_exists = true;
                    } elseif (!$var_type_part instanceof TMixed &&
                        (!$var_type_part instanceof TNamedObject || $var_type_part->value !== 'Closure') &&
                        !$var_type_part instanceof TCallable &&
                        (!$var_type_part instanceof TNamedObject ||
                            !MethodChecker::methodExists(
                                $var_type_part->value . '::__invoke',
                                $statements_checker->getFileChecker()
                            )
                        )
                    ) {
                        $var_id = ExpressionChecker::getVarId(
                            $stmt->name,
                            $statements_checker->getFQCLN(),
                            $statements_checker
                        );

                        if (IssueBuffer::accepts(
                            new InvalidFunctionCall(
                                'Cannot treat ' . $var_id . ' of type ' . $var_type_part . ' as function',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                }
            }

            if (!isset($stmt->inferredType)) {
                $stmt->inferredType = Type::getMixed();
            }
        } else {
            $method_id = implode('\\', $stmt->name->parts);

            $in_call_map = FunctionChecker::inCallMap($method_id);
            $is_stubbed = isset(FunctionChecker::$stubbed_functions[strtolower($method_id)]);

            $is_predefined = true;

            if (!$in_call_map) {
                $predefined_functions = Config::getInstance()->getPredefinedFunctions();
                $is_predefined = isset($predefined_functions[$method_id]);
            }

            if (!$in_call_map && !$stmt->name instanceof PhpParser\Node\Name\FullyQualified) {
                $method_id = FunctionChecker::getFQFunctionNameFromString($method_id, $statements_checker);
            }

            if (!$in_call_map && !$is_stubbed) {
                if ($context->check_functions) {
                    if (self::checkFunctionExists(
                        $statements_checker,
                        $method_id,
                        $code_location
                    ) === false
                    ) {
                        return false;
                    }
                }

                $function_exists = FunctionChecker::functionExists(
                    strtolower($method_id),
                    $statements_checker->getFilePath()
                );
            } else {
                $function_exists = true;
            }

            if ($function_exists) {
                if (!$in_call_map || $is_stubbed) {
                    $function_storage = FunctionChecker::getStorage(
                        strtolower($method_id),
                        $statements_checker->getFilePath()
                    );

                    $function_params = $function_storage->params;

                    if (!$is_predefined) {
                        $defined_constants = $function_storage->defined_constants;
                    }
                }

                if ($in_call_map && !$is_stubbed) {
                    $function_params = FunctionLikeChecker::getFunctionParamsFromCallMapById(
                        $method_id,
                        $stmt->args,
                        $statements_checker->getFileChecker()
                    );
                }
            }
        }

        if (self::checkFunctionArguments(
            $statements_checker,
            $stmt->args,
            $function_params,
            $context
        ) === false) {
            // fall through
        }

        if ($function_exists) {
            $generic_params = null;

            if ($stmt->name instanceof PhpParser\Node\Name && $method_id) {
                if (!$is_stubbed && $in_call_map) {
                    $function_params = FunctionLikeChecker::getFunctionParamsFromCallMapById(
                        $method_id,
                        $stmt->args,
                        $statements_checker->getFileChecker()
                    );
                }
            }

            if ($function_params) {
                // do this here to allow closure param checks
                if (self::checkFunctionArgumentsMatch(
                    $statements_checker,
                    $stmt->args,
                    $method_id,
                    $function_params,
                    $function_storage,
                    null,
                    $generic_params,
                    $code_location,
                    $context->check_variables
                ) === false) {
                    // fall through
                }
            }

            $config = Config::getInstance();

            if ($stmt->name instanceof PhpParser\Node\Name && $method_id) {
                if (!$in_call_map || $is_stubbed) {
                    if ($function_storage && $function_storage->template_types) {
                        foreach ($function_storage->template_types as $template_name => $_) {
                            if (!isset($generic_params[$template_name])) {
                                $generic_params[$template_name] = Type::getMixed();
                            }
                        }
                    }

                    try {
                        if ($function_storage && $function_storage->return_type) {
                            if ($generic_params) {
                                $return_type = FunctionChecker::replaceTemplateTypes($function_storage->return_type, $generic_params);
                            } else {
                                $return_type = clone $function_storage->return_type;
                            }

                            $return_type_location = $function_storage->return_type_location;

                            $stmt->inferredType = $return_type;

                            // only check the type locally if it's defined externally
                            if ($return_type_location &&
                                !$is_stubbed && // makes lookups or array_* functions quicker
                                !$config->isInProjectDirs($return_type_location->file_path)
                            ) {
                                $return_type->check(
                                    $statements_checker,
                                    new CodeLocation($statements_checker->getSource(), $stmt),
                                    $statements_checker->getSuppressedIssues(),
                                    $context->getPhantomClasses()
                                );
                            }
                        }
                    } catch (\InvalidArgumentException $e) {
                        // this can happen when the function was defined in the Config startup script
                        $stmt->inferredType = Type::getMixed();
                    }
                } else {
                    $stmt->inferredType = FunctionChecker::getReturnTypeFromCallMapWithArgs(
                        $method_id,
                        $stmt->args,
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    );
                }
            }

            foreach ($defined_constants as $const_name => $const_type) {
                $context->constants[$const_name] = clone $const_type;
                $context->vars_in_scope[$const_name] = clone $const_type;
            }

            if (Config::getInstance()->use_assert_for_type &&
                $method instanceof PhpParser\Node\Name &&
                $method->parts === ['assert'] &&
                isset($stmt->args[0])
            ) {
                $assert_clauses = AlgebraChecker::getFormula(
                    $stmt->args[0]->value,
                    $statements_checker->getFQCLN(),
                    $statements_checker
                );

                $simplified_clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $assert_clauses));

                $assert_type_assertions = AlgebraChecker::getTruthsFromFormula($simplified_clauses);

                $changed_vars = [];

                // while in an and, we allow scope to boil over to support
                // statements of the form if ($x && $x->foo())
                $op_vars_in_scope = TypeChecker::reconcileKeyedTypes(
                    $assert_type_assertions,
                    $context->vars_in_scope,
                    $changed_vars,
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                if ($op_vars_in_scope === false) {
                    return false;
                }

                foreach ($changed_vars as $changed_var) {
                    if (isset($op_vars_in_scope[$changed_var])) {
                        $op_vars_in_scope[$changed_var]->from_docblock = true;
                    }
                }

                $context->vars_in_scope = $op_vars_in_scope;
            }
        }

        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['get_class'] || $stmt->name->parts === ['gettype']) &&
            $stmt->args
        ) {
            $var = $stmt->args[0]->value;

            if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                $stmt->inferredType = new Type\Union([new Type\Atomic\T('$' . $var->name)]);
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\New_    $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    public static function analyzeNew(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context
    ) {
        $fq_class_name = null;

        $file_checker = $statements_checker->getFileChecker();

        $late_static = false;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker
                );

                if ($context->check_classes) {
                    if ($context->isPhantomClass($fq_class_name)) {
                        return null;
                    }

                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $fq_class_name,
                        $file_checker,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }
                }
            } else {
                switch ($stmt->class->parts[0]) {
                    case 'self':
                        $fq_class_name = $context->self;
                        break;

                    case 'parent':
                        $fq_class_name = $context->parent;
                        break;

                    case 'static':
                        // @todo maybe we can do better here
                        $fq_class_name = $context->self;
                        $late_static = true;
                        break;
                }
            }
        } elseif ($stmt->class instanceof PhpParser\Node\Stmt\Class_) {
            $statements_checker->analyze([$stmt->class], $context);
            $fq_class_name = $stmt->class->name;
        } else {
            ExpressionChecker::analyze($statements_checker, $stmt->class, $context);
        }

        if ($fq_class_name) {
            $stmt->inferredType = new Type\Union([new TNamedObject($fq_class_name)]);

            if (strtolower($fq_class_name) !== 'stdclass' &&
                $context->check_classes &&
                ClassChecker::classExists($fq_class_name, $file_checker)
            ) {
                $storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];

                // if we're not calling this constructor via new static()
                if ($storage->abstract && !$late_static) {
                    if (IssueBuffer::accepts(
                        new AbstractInstantiation(
                            'Unable to instantiate a abstract class ' . $fq_class_name,
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                if (MethodChecker::methodExists(
                    $fq_class_name . '::__construct',
                    $file_checker,
                    $context->collect_references ? new CodeLocation($statements_checker->getSource(), $stmt) : null
                )) {
                    $method_id = $fq_class_name . '::__construct';

                    if (self::checkMethodArgs(
                        $method_id,
                        $stmt->args,
                        $found_generic_params,
                        $context,
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $statements_checker
                    ) === false) {
                        return false;
                    }

                    $generic_params = null;

                    if ($storage->template_types) {
                        foreach ($storage->template_types as $template_name => $_) {
                            if (isset($found_generic_params[$template_name])) {
                                $generic_params[] = $found_generic_params[$template_name];
                            } else {
                                $generic_params[] = Type::getMixed();
                            }
                        }
                    }

                    if ($fq_class_name === 'ArrayIterator' && isset($stmt->args[0]->value->inferredType)) {
                        /** @var Type\Union */
                        $first_arg_type = $stmt->args[0]->value->inferredType;

                        if ($first_arg_type->hasGeneric()) {
                            $key_type = null;
                            $value_type = null;

                            foreach ($first_arg_type->types as $type) {
                                if ($type instanceof Type\Atomic\TArray) {
                                    $first_type_param = count($type->type_params) ? $type->type_params[0] : null;
                                    $last_type_param = $type->type_params[count($type->type_params) - 1];

                                    if ($value_type === null) {
                                        $value_type = clone $last_type_param;
                                    } else {
                                        $value_type = Type::combineUnionTypes($value_type, $last_type_param);
                                    }

                                    if (!$key_type || !$first_type_param) {
                                        $key_type = $first_type_param ? clone $first_type_param : Type::getMixed();
                                    } else {
                                        $key_type = Type::combineUnionTypes($key_type, $first_type_param);
                                    }
                                }
                            }

                            if ($key_type === null) {
                                throw new \UnexpectedValueException('$key_type cannot be null');
                            }

                            if ($value_type === null) {
                                throw new \UnexpectedValueException('$value_type cannot be null');
                            }

                            $stmt->inferredType = new Type\Union([
                                new Type\Atomic\TGenericObject(
                                    $fq_class_name,
                                    [
                                        $key_type,
                                        $value_type
                                    ]
                                )
                            ]);
                        }
                    } elseif ($generic_params) {
                        $stmt->inferredType = new Type\Union([
                            new Type\Atomic\TGenericObject(
                                $fq_class_name,
                                $generic_params
                            )
                        ]);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\MethodCall  $stmt
     * @param   Context                         $context
     * @return  false|null
     */
    public static function analyzeMethodCall(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\MethodCall $stmt,
        Context $context
    ) {
        if (ExpressionChecker::analyze($statements_checker, $stmt->var, $context) === false) {
            return false;
        }

        $class_type = null;
        $method_id = null;

        if ($stmt->var instanceof PhpParser\Node\Expr\Variable) {
            if (is_string($stmt->var->name) && $stmt->var->name === 'this' && !$statements_checker->getClassName()) {
                if (IssueBuffer::accepts(
                    new InvalidScope(
                        'Use of $this in non-class context',
                        new CodeLocation($statements_checker->getSource(), $stmt)
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        $var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $class_type = $context->hasVariable($var_id) ? $context->vars_in_scope[$var_id] : null;

        if (isset($stmt->var->inferredType)) {
            /** @var Type\Union */
            $class_type = $stmt->var->inferredType;
        } elseif (!$class_type) {
            $stmt->inferredType = Type::getMixed();
        }

        $source = $statements_checker->getSource();

        if (!$context->check_methods || !$context->check_classes) {
            return null;
        }

        $has_mock = false;

        if ($class_type && is_string($stmt->name) && $class_type->isNull()) {
            if (IssueBuffer::accepts(
                new NullReference(
                    'Cannot call method ' . $stmt->name . ' on null variable ' . $var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt->var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if ($class_type && is_string($stmt->name) && $class_type->isNullable()) {
            if (IssueBuffer::accepts(
                new PossiblyNullReference(
                    'Cannot call method ' . $stmt->name . ' on possibly null variable ' . $var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt->var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $config = Config::getInstance();

        if ($class_type && is_string($stmt->name)) {
            $return_type = null;

            foreach ($class_type->types as $class_type_part) {
                if (!$class_type_part instanceof TNamedObject) {
                    switch (get_class($class_type_part)) {
                        case 'Psalm\\Type\\Atomic\\TNull':
                            // handled above
                            break;

                        case 'Psalm\\Type\\Atomic\\TInt':
                        case 'Psalm\\Type\\Atomic\\TBool':
                        case 'Psalm\\Type\\Atomic\\TFalse':
                        case 'Psalm\\Type\\Atomic\\TArray':
                        case 'Psalm\\Type\\Atomic\\TString':
                        case 'Psalm\\Type\\Atomic\\TNumericString':
                            if (IssueBuffer::accepts(
                                new InvalidMethodCall(
                                    'Cannot call method ' . $stmt->name . ' on ' . $class_type . ' variable ' . $var_id,
                                    new CodeLocation($statements_checker->getSource(), $stmt)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }
                            break;

                        case 'Psalm\\Type\\Atomic\\TMixed':
                        case 'Psalm\\Type\\Atomic\\TObject':
                            if (IssueBuffer::accepts(
                                new MixedMethodCall(
                                    'Cannot call method ' . $stmt->name . ' on a mixed variable ' . $var_id,
                                    new CodeLocation($statements_checker->getSource(), $stmt)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }
                            break;
                    }

                    continue;
                }

                $fq_class_name = $class_type_part->value;

                $is_mock = ExpressionChecker::isMock($fq_class_name);

                $has_mock = $has_mock || $is_mock;

                if ($fq_class_name === 'static') {
                    $fq_class_name = (string) $context->self;
                }

                if ($is_mock ||
                    $context->isPhantomClass($fq_class_name)
                ) {
                    $return_type = Type::getMixed();
                    continue;
                }

                if ($var_id === '$this') {
                    $does_class_exist = true;
                } else {
                    $does_class_exist = ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $fq_class_name,
                        $statements_checker->getFileChecker(),
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $statements_checker->getSuppressedIssues()
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist;
                }

                if (MethodChecker::methodExists($fq_class_name . '::__call', $statements_checker->getFileChecker())) {
                    $return_type = Type::getMixed();
                    continue;
                }

                $method_id = $fq_class_name . '::' . strtolower($stmt->name);
                $cased_method_id = $fq_class_name . '::' . $stmt->name;

                $does_method_exist = MethodChecker::checkMethodExists(
                    $cased_method_id,
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                if (!$does_method_exist) {
                    return $does_method_exist;
                }

                $class_template_params = null;

                if ($stmt->var instanceof PhpParser\Node\Expr\Variable &&
                    ($context->collect_initializations || $context->collect_mutations) &&
                    $stmt->var->name === 'this' &&
                    is_string($stmt->name) &&
                    $source instanceof FunctionLikeChecker
                ) {
                    self::collectSpecialInformation($source, $stmt->name, $context);
                }

                $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];

                if ($class_storage->template_types) {
                    $class_template_params = [];

                    if ($class_type_part instanceof TGenericObject) {
                        $reversed_class_template_types = array_reverse(array_keys($class_storage->template_types));

                        $provided_type_param_count = count($class_type_part->type_params);

                        foreach ($reversed_class_template_types as $i => $type_name) {
                            if (isset($class_type_part->type_params[$provided_type_param_count - 1 - $i])) {
                                $class_template_params[$type_name] =
                                    $class_type_part->type_params[$provided_type_param_count - 1 - $i];
                            } else {
                                $class_template_params[$type_name] = Type::getMixed();
                            }
                        }
                    } else {
                        foreach ($class_storage->template_types as $type_name => $_) {
                            $class_template_params[$type_name] = Type::getMixed();
                        }
                    }
                }

                if (self::checkMethodArgs(
                    $method_id,
                    $stmt->args,
                    $class_template_params,
                    $context,
                    new CodeLocation($source, $stmt),
                    $statements_checker
                ) === false) {
                    return false;
                }

                $return_type_location = null;
                $file_checker = $source->getFileChecker();

                if (FunctionChecker::inCallMap($cased_method_id)) {
                    $return_type_candidate = FunctionChecker::getReturnTypeFromCallMap($method_id);
                } else {
                    if (MethodChecker::checkMethodVisibility(
                        $method_id,
                        $context->self,
                        $statements_checker->getSource(),
                        new CodeLocation($source, $stmt),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    if (MethodChecker::checkMethodNotDeprecated(
                        $method_id,
                        new CodeLocation($source, $stmt),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    $return_type_candidate = MethodChecker::getMethodReturnType($method_id);

                    if ($return_type_candidate) {
                        if ($class_template_params) {
                            $return_type_candidate = FunctionChecker::replaceTemplateTypes($return_type_candidate, $class_template_params);
                        } else {
                            $return_type_candidate = clone $return_type_candidate;
                        }

                        $return_type_candidate = ExpressionChecker::fleshOutTypes(
                            $return_type_candidate,
                            $fq_class_name,
                            $method_id
                        );

                        $return_type_location = MethodChecker::getMethodReturnTypeLocation($method_id, $secondary_return_type_location);

                        if ($secondary_return_type_location) {
                            $return_type_location = $secondary_return_type_location;
                        }

                        // only check the type locally if it's defined externally
                        if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                            $return_type_candidate->check(
                                $statements_checker,
                                new CodeLocation($source, $stmt),
                                $statements_checker->getSuppressedIssues(),
                                $context->getPhantomClasses()
                            );
                        }
                    }
                }

                if ($return_type_candidate) {
                    if (!$return_type) {
                        $return_type = $return_type_candidate;
                    } else {
                        $return_type = Type::combineUnionTypes($return_type_candidate, $return_type);
                    }
                } else {
                    $return_type = Type::getMixed();
                }
            }

            $stmt->inferredType = $return_type;
        }

        if ($method_id === null) {
            return self::checkMethodArgs(
                $method_id,
                $stmt->args,
                $found_generic_params,
                $context,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker
            );
        }
    }

    /**
     * @param   FunctionLikeChecker $source
     * @param   string              $method_name
     * @param   Context             $context
     * @return  void
     */
    public static function collectSpecialInformation(
        FunctionLikeChecker $source,
        $method_name,
        Context $context
    ) {
        $fq_class_name = (string)$source->getFQCLN();

        if ($context->collect_mutations &&
            $context->self &&
            (
                $context->self === $fq_class_name ||
                ClassChecker::classExtends(
                    $context->self,
                    $fq_class_name
                )
            )
        ) {
            $file_checker = $source->getFileChecker();

            $method_id = $fq_class_name . '::' . strtolower($method_name);

            $file_checker->project_checker->getMethodMutations($method_id, $context);
        } elseif ($context->collect_initializations &&
            $context->self &&
            (
                $context->self === $fq_class_name ||
                ClassChecker::classExtends(
                    $context->self,
                    $fq_class_name
                )
            ) &&
            $source->getMethodName() !== $method_name
        ) {
            $method_id = $fq_class_name . '::' . strtolower($method_name);

            $declaring_method_id = MethodChecker::getDeclaringMethodId($method_id);

            $method_storage = MethodChecker::getStorage((string)$declaring_method_id);

            $class_checker = $source->getSource();

            if ($class_checker instanceof ClassLikeChecker &&
                $method_storage->visibility === ClassLikeChecker::VISIBILITY_PRIVATE
            ) {
                $local_vars_in_scope = [];
                $local_vars_possibly_in_scope = [];

                foreach ($context->vars_in_scope as $var => $type) {
                    if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                        $local_vars_in_scope[$var] = $context->vars_in_scope[$var];
                    }
                }

                foreach ($context->vars_possibly_in_scope as $var => $type) {
                    if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                        $local_vars_possibly_in_scope[$var] = $context->vars_possibly_in_scope[$var];
                    }
                }

                $class_checker->getMethodMutations(strtolower($method_name), $context);

                foreach ($local_vars_in_scope as $var => $type) {
                    $context->vars_in_scope[$var] = $type;
                }

                foreach ($local_vars_possibly_in_scope as $var => $type) {
                    $context->vars_possibly_in_scope[$var] = $type;
                }
            }
        }
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\StaticCall  $stmt
     * @param   Context                         $context
     * @return  false|null
     */
    public static function analyzeStaticCall(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context
    ) {
        if ($stmt->class instanceof PhpParser\Node\Expr\Variable ||
            $stmt->class instanceof PhpParser\Node\Expr\ArrayDimFetch
        ) {
            // this is when calling $some_class::staticMethod() - which is a shitty way of doing things
            // because it can't be statically type-checked
            return null;
        }

        $method_id = null;
        $fq_class_name = null;

        $lhs_type = null;

        $file_checker = $statements_checker->getFileChecker();
        $source = $statements_checker->getSource();

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $fq_class_name = null;

            if (count($stmt->class->parts) === 1 && in_array($stmt->class->parts[0], ['self', 'static', 'parent'])) {
                if ($stmt->class->parts[0] === 'parent') {
                    $fq_class_name = $statements_checker->getParentFQCLN();

                    if ($fq_class_name === null) {
                        if (IssueBuffer::accepts(
                            new ParentNotFound(
                                'Cannot call method on parent as this class does not extend another',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }

                        return;
                    }

                    if (is_string($stmt->name)) {
                        if ($context->collect_mutations) {
                            $method_id = $fq_class_name . '::' . strtolower($stmt->name);

                            $file_checker->project_checker->getMethodMutations($method_id, $context);
                        }
                    }
                } else {
                    $namespace = $statements_checker->getNamespace()
                        ? $statements_checker->getNamespace() . '\\'
                        : '';

                    $fq_class_name = $context->self ?: $namespace . $statements_checker->getClassName();
                }

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }
            } elseif ($context->check_classes) {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker
                );

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                $does_class_exist = false;

                if ($context->self &&
                    isset(ClassLikeChecker::$storage[strtolower($context->self)]->used_traits[$fq_class_name])
                ) {
                    $fq_class_name = $context->self;
                    $does_class_exist = true;
                }

                if (!$does_class_exist) {
                    $does_class_exist = ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $fq_class_name,
                        $file_checker,
                        new CodeLocation($source, $stmt->class),
                        $statements_checker->getSuppressedIssues(),
                        false
                    );
                }


                if (!$does_class_exist) {
                    return $does_class_exist;
                }
            }

            if ($fq_class_name) {
                $lhs_type = new Type\Union([new TNamedObject($fq_class_name)]);
            }
        } else {
            ExpressionChecker::analyze($statements_checker, $stmt->class, $context);

            /** @var Type\Union */
            $lhs_type = $stmt->class->inferredType;
        }

        if (!$context->check_methods || !$lhs_type) {
            return null;
        }

        $has_mock = false;

        $config = Config::getInstance();

        foreach ($lhs_type->types as $lhs_type_part) {
            if (!$lhs_type_part instanceof TNamedObject) {
                // @todo deal with it
                continue;
            }

            $fq_class_name = $lhs_type_part->value;

            $is_mock = ExpressionChecker::isMock($fq_class_name);

            $has_mock = $has_mock || $is_mock;

            $method_id = null;

            if (is_string($stmt->name) &&
                !MethodChecker::methodExists($fq_class_name . '::__callStatic', $file_checker) &&
                !$is_mock
            ) {
                $method_id = $fq_class_name . '::' . strtolower($stmt->name);
                $cased_method_id = $fq_class_name . '::' . $stmt->name;

                $does_method_exist = MethodChecker::checkMethodExists(
                    $cased_method_id,
                    $file_checker,
                    new CodeLocation($source, $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                if (!$does_method_exist) {
                    return $does_method_exist;
                }

                if (MethodChecker::checkMethodVisibility(
                    $method_id,
                    $context->self,
                    $statements_checker->getSource(),
                    new CodeLocation($source, $stmt),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                if ($stmt->class instanceof PhpParser\Node\Name
                    && ($stmt->class->parts[0] !== 'parent' || $statements_checker->isStatic())
                    && (!$context->self
                        || $statements_checker->isStatic()
                        || !ClassChecker::classExtends($context->self, $fq_class_name)
                    )
                ) {
                    if (MethodChecker::checkStatic(
                        $method_id,
                        $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts[0] === 'self',
                        new CodeLocation($source, $stmt),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        // fall through
                    }
                }

                if (MethodChecker::checkMethodNotDeprecated(
                    $method_id,
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker->getSuppressedIssues()
                ) === false) {
                    // fall through
                }

                if (self::checkMethodArgs(
                    $method_id,
                    $stmt->args,
                    $found_generic_params,
                    $context,
                    new CodeLocation($statements_checker->getSource(), $stmt),
                    $statements_checker
                ) === false) {
                    return false;
                }

                $fq_class_name = $stmt->class instanceof PhpParser\Node\Name && $stmt->class->parts === ['parent']
                    ? $statements_checker->getFQCLN()
                    : $fq_class_name;

                $class_template_params = [];

                $return_type_candidate = MethodChecker::getMethodReturnType($method_id);

                if ($return_type_candidate) {
                    if ($found_generic_params) {
                        $return_type_candidate = FunctionChecker::replaceTemplateTypes($return_type_candidate, $found_generic_params);
                    } else {
                        $return_type_candidate = clone $return_type_candidate;
                    }

                    $return_type_candidate = ExpressionChecker::fleshOutTypes(
                        $return_type_candidate,
                        $fq_class_name,
                        $method_id
                    );

                    $return_type_location = MethodChecker::getMethodReturnTypeLocation($method_id, $secondary_return_type_location);

                    if ($secondary_return_type_location) {
                        $return_type_location = $secondary_return_type_location;
                    }

                    // only check the type locally if it's defined externally
                    if ($return_type_location && !$config->isInProjectDirs($return_type_location->file_path)) {
                        $return_type_candidate->check(
                            $statements_checker,
                            new CodeLocation($source, $stmt),
                            $statements_checker->getSuppressedIssues(),
                            $context->getPhantomClasses()
                        );
                    }
                }

                if ($return_type_candidate) {
                    if (isset($stmt->inferredType)) {
                        $stmt->inferredType = Type::combineUnionTypes($stmt->inferredType, $return_type_candidate);
                    } else {
                        $stmt->inferredType = $return_type_candidate;
                    }
                }
            }
        }

        if ($method_id === null) {
            return self::checkMethodArgs(
                $method_id,
                $stmt->args,
                $found_generic_params,
                $context,
                new CodeLocation($statements_checker->getSource(), $stmt),
                $statements_checker
            );
        }
    }

    /**
     * @param  string|null                      $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     * @param  array<string, Type\Union>|null   &$generic_params
     * @param  Context                          $context
     * @param  CodeLocation                     $code_location
     * @param  StatementsChecker                $statements_checker
     * @return false|null
     */
    protected static function checkMethodArgs(
        $method_id,
        array $args,
        array &$generic_params = null,
        Context $context,
        CodeLocation $code_location,
        StatementsChecker $statements_checker
    ) {
        $file_checker = $statements_checker->getFileChecker();

        $method_params = $method_id
            ? FunctionLikeChecker::getMethodParamsById($method_id, $args, $file_checker)
            : null;

        if (self::checkFunctionArguments(
            $statements_checker,
            $args,
            $method_params,
            $context
        ) === false) {
            return false;
        }

        if (!$method_id || $method_params === null) {
            return;
        }

        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];
        $method_storage = isset($class_storage->methods[strtolower($method_name)])
            ? $class_storage->methods[strtolower($method_name)]
            : null;

        if (!$class_storage->user_defined) {
            // check again after we've processed args
            $method_params = FunctionLikeChecker::getMethodParamsById(
                $method_id,
                $args,
                $file_checker
            );
        }

        if (self::checkFunctionArgumentsMatch(
            $statements_checker,
            $args,
            $method_id,
            $method_params,
            $method_storage,
            $class_storage,
            $generic_params,
            $code_location,
            $context->check_variables
        ) === false) {
            return false;
        }

        return null;
    }

    /**
     * @param   StatementsChecker                       $statements_checker
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   array<int, FunctionLikeParameter>|null  $function_params
     * @param   Context                                 $context
     * @return  false|null
     */
    protected static function checkFunctionArguments(
        StatementsChecker $statements_checker,
        array $args,
        array $function_params = null,
        Context $context
    ) {
        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        foreach ($args as $argument_offset => $arg) {
            if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch) {
                if ($function_params !== null) {
                    $by_ref = $argument_offset < count($function_params)
                        ? $function_params[$argument_offset]->by_ref
                        : $last_param && $last_param->is_variadic && $last_param->by_ref;

                    $by_ref_type = null;

                    if ($by_ref && $last_param) {
                        $by_ref_type = $argument_offset < count($function_params)
                            ? clone $function_params[$argument_offset]->type
                            : clone $last_param->type;
                    }

                    if ($by_ref && $by_ref_type) {
                        ExpressionChecker::assignByRefParam($statements_checker, $arg->value, $by_ref_type, $context);
                    } else {
                        if (FetchChecker::analyzePropertyFetch($statements_checker, $arg->value, $context) === false) {
                            return false;
                        }
                    }
                } else {
                    $var_id = ExpressionChecker::getVarId(
                        $arg->value,
                        $statements_checker->getFQCLN(),
                        $statements_checker
                    );

                    if ($var_id &&
                        (!$context->hasVariable($var_id) || $context->vars_in_scope[$var_id]->isNull())
                    ) {
                        // we don't know if it exists, assume it's passed by reference
                        $context->vars_in_scope[$var_id] = Type::getMixed();
                        $context->vars_possibly_in_scope[$var_id] = true;
                        if (!$statements_checker->hasVariable($var_id)) {
                            $statements_checker->registerVariable($var_id, new CodeLocation($statements_checker, $arg->value));
                        }
                    }
                }
            } elseif ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                if ($function_params !== null) {
                    $by_ref = $argument_offset < count($function_params)
                        ? $function_params[$argument_offset]->by_ref
                        : $last_param && $last_param->is_variadic && $last_param->by_ref;

                    $by_ref_type = null;

                    if ($by_ref && $last_param) {
                        $by_ref_type = $argument_offset < count($function_params)
                            ? clone $function_params[$argument_offset]->type
                            : clone $last_param->type;
                    }

                    if (ExpressionChecker::analyzeVariable(
                        $statements_checker,
                        $arg->value,
                        $context,
                        $by_ref,
                        $by_ref_type
                    ) === false) {
                        return false;
                    }
                } elseif (is_string($arg->value->name)) {
                    $var_id = '$' . $arg->value->name;

                    if (!$context->hasVariable($var_id) ||
                        $context->vars_in_scope[$var_id]->isNull()
                    ) {
                        // we don't know if it exists, assume it's passed by reference
                        $context->vars_in_scope[$var_id] = Type::getMixed();
                        $context->vars_possibly_in_scope[$var_id] = true;
                        if (!$statements_checker->hasVariable($var_id)) {
                            $statements_checker->registerVariable(
                                $var_id,
                                new CodeLocation($statements_checker, $arg->value)
                            );
                        }
                    }
                }
            } else {
                if (ExpressionChecker::analyze($statements_checker, $arg->value, $context) === false) {
                    return false;
                }
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker                       $statements_checker
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   string|null                             $method_id
     * @param   array<int,FunctionLikeParameter>        $function_params
     * @param   FunctionLikeStorage                     $function_storage
     * @param   ClassLikeStorage                        $class_storage
     * @param   array<string, Type\Union>|null          $generic_params
     * @param   CodeLocation                            $code_location
     * @param   bool                                    $check_variables
     * @return  false|null
     */
    protected static function checkFunctionArgumentsMatch(
        StatementsChecker $statements_checker,
        array $args,
        $method_id,
        array $function_params,
        FunctionLikeStorage $function_storage = null,
        ClassLikeStorage $class_storage = null,
        array &$generic_params = null,
        CodeLocation $code_location,
        $check_variables
    ) {
        $in_call_map = $method_id ? FunctionChecker::inCallMap($method_id) : false;

        $cased_method_id = $method_id;

        $is_variadic = false;

        $fq_class_name = null;

        if ($method_id) {
            if ($in_call_map || !strpos($method_id, '::')) {
                $is_variadic = FunctionChecker::isVariadic(strtolower($method_id), $statements_checker->getFilePath());
            } else {
                $fq_class_name = explode('::', $method_id)[0];
                $is_variadic = MethodChecker::isVariadic($method_id);
            }
        }

        if ($method_id && strpos($method_id, '::') && !$in_call_map) {
            $cased_method_id = MethodChecker::getCasedMethodId($method_id);
        }

        if ($function_params) {
            foreach ($function_params as $function_param) {
                $is_variadic = $is_variadic || $function_param->is_variadic;
            }
        }

        $has_packed_var = false;

        foreach ($args as $arg) {
            $has_packed_var = $has_packed_var || $arg->unpack;
        }

        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        $template_types = null;

        if ($function_storage) {
            $template_types = [];

            if ($function_storage->template_types) {
                $template_types = $function_storage->template_types;
            }
            if ($class_storage && $class_storage->template_types) {
                $template_types = array_merge($template_types, $class_storage->template_types);
            }
        }

        foreach ($args as $argument_offset => $arg) {
            if (isset($arg->value->inferredType)) {
                $function_param = count($function_params) > $argument_offset
                    ? $function_params[$argument_offset]
                    : ($last_param && $last_param->is_variadic ? $last_param : null);

                if ($function_param) {
                    $param_type = clone $function_param->type;

                    if ($function_param->is_variadic) {
                        if (!$param_type->hasArray() || !$param_type->types['array'] instanceof TArray) {
                            break;
                        }

                        $param_type = clone $param_type->types['array']->type_params[1];
                    }

                    if ($function_storage) {
                        if (isset($function_storage->template_typeof_params[$argument_offset])) {
                            $template_type = $function_storage->template_typeof_params[$argument_offset];

                            $offset_value_type = null;

                            if ($arg->value instanceof PhpParser\Node\Expr\ClassConstFetch &&
                                $arg->value->class instanceof PhpParser\Node\Name &&
                                is_string($arg->value->name) &&
                                strtolower($arg->value->name) === 'class'
                            ) {
                                $offset_value_type = Type::parseString(
                                    ClassLikeChecker::getFQCLNFromNameObject(
                                        $arg->value->class,
                                        $statements_checker
                                    )
                                );
                            } elseif ($arg->value instanceof PhpParser\Node\Scalar\String_ && $arg->value->value) {
                                $offset_value_type = Type::parseString($arg->value->value);
                            }

                            if ($offset_value_type) {
                                foreach ($offset_value_type->types as $offset_value_type_part) {
                                    // register class if the class exists
                                    if ($offset_value_type_part instanceof TNamedObject) {
                                        ClassLikeChecker::checkFullyQualifiedClassLikeName(
                                            $offset_value_type_part->value,
                                            $statements_checker->getFileChecker(),
                                            new CodeLocation($statements_checker->getSource(), $arg->value),
                                            $statements_checker->getSuppressedIssues()
                                        );
                                    }
                                }
                            }

                            if ($generic_params === null) {
                                $generic_params = [];
                            }

                            $generic_params[$template_type] = $offset_value_type ?: Type::getMixed();
                        } elseif ($template_types) {
                            if ($generic_params === null) {
                                $generic_params = [];
                            }

                            $param_type->replaceTemplateTypes(
                                $template_types,
                                $generic_params,
                                $arg->value->inferredType
                            );
                        }
                    }

                    // for now stop when we encounter a packed argument
                    if ($arg->unpack) {
                        break;
                    }

                    $fleshed_out_type = ExpressionChecker::fleshOutTypes(
                        $param_type,
                        $fq_class_name,
                        $method_id
                    );

                    if ($check_variables) {
                        if (self::checkFunctionArgumentType(
                            $statements_checker,
                            $arg->value->inferredType,
                            $fleshed_out_type,
                            $cased_method_id,
                            $argument_offset,
                            new CodeLocation($statements_checker->getSource(), $arg->value)
                        ) === false) {
                            return false;
                        }
                    }
                }
            }
        }

        if ($method_id === 'array_map' || $method_id === 'array_filter') {
            if (self::checkArrayFunctionArgumentsMatch(
                $statements_checker,
                $args,
                $method_id
            ) === false
            ) {
                return false;
            }
        }

        if (!$is_variadic
            && count($args) > count($function_params)
            && (!count($function_params) || $function_params[count($function_params) - 1]->name !== '...=')
        ) {
            if (IssueBuffer::accepts(
                new TooManyArguments(
                    'Too many arguments for method ' . ($cased_method_id ?: $method_id),
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if (!$has_packed_var && count($args) < count($function_params)) {
            for ($i = count($args); $i < count($function_params); $i++) {
                $param = $function_params[$i];

                if (!$param->is_optional && !$param->is_variadic) {
                    if (IssueBuffer::accepts(
                        new TooFewArguments(
                            'Too few arguments for method ' . $cased_method_id,
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    break;
                }
            }
        }
    }

    /**
     * @param   StatementsChecker                       $statements_checker
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   string|null                             $method_id
     * @return  false|null
     */
    protected static function checkArrayFunctionArgumentsMatch(
        StatementsChecker $statements_checker,
        array $args,
        $method_id
    ) {
        $closure_index = $method_id === 'array_map' ? 0 : 1;

        $array_arg_types = [];

        foreach ($args as $i => $arg) {
            if ($i === 0 && $method_id === 'array_map') {
                continue;
            }

            if ($i === 1 && $method_id === 'array_filter') {
                break;
            }

            $array_arg = isset($arg->value) ? $arg->value : null;

            /** @var ObjectLike|TArray|null */
            $array_arg_type = $array_arg
                    && isset($array_arg->inferredType)
                    && isset($array_arg->inferredType->types['array'])
                ? $array_arg->inferredType->types['array']
                : null;

            if ($array_arg_type instanceof ObjectLike) {
                $array_arg_type = new TArray([Type::getString(), $array_arg_type->getGenericTypeParam()]);
            }

            $array_arg_types[] = $array_arg_type;
        }

        /** @var PhpParser\Node\Arg */
        $closure_arg = isset($args[$closure_index]) ? $args[$closure_index] : null;

        /** @var Type\Union|null */
        $closure_arg_type = $closure_arg && isset($closure_arg->value->inferredType)
                ? $closure_arg->value->inferredType
                : null;

        if ($closure_arg_type) {
            $min_closure_param_count = $max_closure_param_count = count($array_arg_types);

            if ($method_id === 'array_filter') {
                $max_closure_param_count = count($args) > 2 ? 2 : 1;
            }

            foreach ($closure_arg_type->types as $closure_type) {
                if (!$closure_type instanceof Type\Atomic\Fn) {
                    continue;
                }

                if (count($closure_type->params) > $max_closure_param_count) {
                    if (IssueBuffer::accepts(
                        new TooManyArguments(
                            'Too many arguments in closure for ' . $method_id,
                            new CodeLocation($statements_checker->getSource(), $closure_arg)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } elseif (count($closure_type->params) < $min_closure_param_count) {
                    if (IssueBuffer::accepts(
                        new TooFewArguments(
                            'You must supply a param in the closure for ' . $method_id,
                            new CodeLocation($statements_checker->getSource(), $closure_arg)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                // abandon attempt to validate closure params if we have an extra arg for ARRAY_FILTER
                if ($method_id === 'array_filter' && count($args) > 2) {
                    continue;
                }

                $closure_params = $closure_type->params;

                $i = 0;

                foreach ($closure_params as $closure_param) {
                    if (!isset($array_arg_types[$i])) {
                        $i++;
                        continue;
                    }

                    /** @var Type\Atomic\TArray */
                    $array_arg_type = $array_arg_types[$i];

                    $input_type = $array_arg_type->type_params[1];

                    if ($input_type->isMixed()) {
                        $i++;
                        continue;
                    }

                    $closure_param_type = $closure_param->type;

                    $type_match_found = TypeChecker::isContainedBy(
                        $input_type,
                        $closure_param_type,
                        $statements_checker->getFileChecker(),
                        false,
                        $scalar_type_match_found,
                        $coerced_type
                    );

                    if ($coerced_type) {
                        if (IssueBuffer::accepts(
                            new TypeCoercion(
                                'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                    $closure_param_type . ', parent type ' . $input_type . ' provided',
                                new CodeLocation($statements_checker->getSource(), $closure_arg)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    if (!$type_match_found) {
                        if ($scalar_type_match_found) {
                            if (IssueBuffer::accepts(
                                new InvalidScalarArgument(
                                    'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                        $closure_param_type . ', ' . $input_type . ' provided',
                                    new CodeLocation($statements_checker->getSource(), $closure_arg)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                return false;
                            }
                        } elseif (IssueBuffer::accepts(
                            new InvalidArgument(
                                'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                    $closure_param_type . ', ' . $input_type . ' provided',
                                new CodeLocation($statements_checker->getSource(), $closure_arg)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }

                    $i++;
                }
            }
        }
    }

    /**
     * @param   StatementsChecker   $statements_checker
     * @param   Type\Union          $input_type
     * @param   Type\Union          $param_type
     * @param   string|null         $cased_method_id
     * @param   int                 $argument_offset
     * @param   CodeLocation        $code_location
     * @return  null|false
     */
    public static function checkFunctionArgumentType(
        StatementsChecker $statements_checker,
        Type\Union $input_type,
        Type\Union $param_type,
        $cased_method_id,
        $argument_offset,
        CodeLocation $code_location
    ) {
        if ($param_type->isMixed()) {
            return null;
        }

        $method_identifier = $cased_method_id ? ' of ' . $cased_method_id : '';

        if ($input_type->isMixed()) {
            if (IssueBuffer::accepts(
                new MixedArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be mixed, expecting ' .
                        $param_type,
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        if (!$param_type->isNullable() && $cased_method_id !== 'echo') {
            if ($input_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, ' .
                            'null value provided',
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }

                return null;
            }

            if ($input_type->isNullable()) {
                if (IssueBuffer::accepts(
                    new PossiblyNullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, possibly ' .
                            'null value provided',
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }
        }

        $type_match_found = TypeChecker::isContainedBy(
            $input_type,
            $param_type,
            $statements_checker->getFileChecker(),
            true,
            $scalar_type_match_found,
            $coerced_type,
            $to_string_cast
        );

        if ($coerced_type) {
            if (IssueBuffer::accepts(
                new TypeCoercion(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type .
                        ', parent type ' . $input_type . ' provided',
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        if ($to_string_cast && $cased_method_id !== 'echo') {
            if (IssueBuffer::accepts(
                new ImplicitToStringCast(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                        $param_type . ', ' . $input_type . ' provided with a __toString method',
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (!$type_match_found && !$coerced_type) {
            if ($scalar_type_match_found) {
                if ($cased_method_id !== 'echo') {
                    if (IssueBuffer::accepts(
                        new InvalidScalarArgument(
                            'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                                $param_type . ', ' . $input_type . ' provided',
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            } elseif (IssueBuffer::accepts(
                new InvalidArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type .
                        ', ' . $input_type . ' provided',
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        return null;
    }

    /**
     * @param  StatementsChecker    $statements_checker
     * @param  string               $function_id
     * @param  CodeLocation         $code_location
     * @return bool
     */
    protected static function checkFunctionExists(
        StatementsChecker $statements_checker,
        &$function_id,
        CodeLocation $code_location
    ) {
        $cased_function_id = $function_id;
        $function_id = strtolower($function_id);

        if (!FunctionChecker::functionExists($function_id, $statements_checker->getFilePath())) {
            $root_function_id = preg_replace('/.*\\\/', '', $function_id);

            if ($function_id !== $root_function_id &&
                FunctionChecker::functionExists($root_function_id, $statements_checker->getFilePath())
            ) {
                $function_id = $root_function_id;
            } else {
                if (IssueBuffer::accepts(
                    new UndefinedFunction(
                        'Function ' . $cased_function_id . ' does not exist',
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // fall through
                }

                return false;
            }
        }

        return true;
    }
}
