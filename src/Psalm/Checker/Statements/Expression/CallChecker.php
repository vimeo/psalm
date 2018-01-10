<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FunctionChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\AbstractInstantiation;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\ForbiddenCode;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidFunctionCall;
use Psalm\Issue\InvalidMethodCall;
use Psalm\Issue\InvalidPassByReference;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\InvalidScope;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\MixedTypeCoercion;
use Psalm\Issue\NullArgument;
use Psalm\Issue\NullFunctionCall;
use Psalm\Issue\NullReference;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\PossiblyFalseArgument;
use Psalm\Issue\PossiblyFalseReference;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\Issue\PossiblyInvalidFunctionCall;
use Psalm\Issue\PossiblyInvalidMethodCall;
use Psalm\Issue\PossiblyNullArgument;
use Psalm\Issue\PossiblyNullFunctionCall;
use Psalm\Issue\PossiblyNullReference;
use Psalm\Issue\PossiblyUndefinedMethod;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\TypeCoercion;
use Psalm\Issue\UndefinedFunction;
use Psalm\Issue\UndefinedMethod;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Reconciler;

class CallChecker
{
    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\FuncCall    $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyzeFunctionCall(
        ProjectChecker $project_checker,
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
                if ($stmt->name->inferredType->isNull()) {
                    if (IssueBuffer::accepts(
                        new NullFunctionCall(
                            'Cannot call function on null value',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                if ($stmt->name->inferredType->isNullable()) {
                    if (IssueBuffer::accepts(
                        new PossiblyNullFunctionCall(
                            'Cannot call function on possibly null value',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                $invalid_function_call_types = [];
                $has_valid_function_call_type = false;

                foreach ($stmt->name->inferredType->getTypes() as $var_type_part) {
                    if ($var_type_part instanceof Type\Atomic\Fn) {
                        $function_params = $var_type_part->params;

                        if (isset($stmt->inferredType)) {
                            $stmt->inferredType = Type::combineUnionTypes(
                                $stmt->inferredType,
                                $var_type_part->return_type
                            );
                        } else {
                            $stmt->inferredType = $var_type_part->return_type;
                        }

                        $function_exists = true;
                        $has_valid_function_call_type = true;
                    } elseif ($var_type_part instanceof TMixed) {
                        $has_valid_function_call_type = true;
                        // @todo maybe emit issue here
                    } elseif (($var_type_part instanceof TNamedObject && $var_type_part->value === 'Closure') ||
                        $var_type_part instanceof TCallable
                    ) {
                        $has_valid_function_call_type = true;
                        // this is fine
                    } elseif ($var_type_part instanceof TNull) {
                        // handled above
                    } elseif (!$var_type_part instanceof TNamedObject ||
                        !ClassLikeChecker::classOrInterfaceExists(
                            $project_checker,
                            $var_type_part->value
                        ) ||
                        !MethodChecker::methodExists(
                            $project_checker,
                            $var_type_part->value . '::__invoke'
                        )
                    ) {
                        $invalid_function_call_types[] = (string)$var_type_part;
                    }
                }

                if ($invalid_function_call_types) {
                    $var_type_part = reset($invalid_function_call_types);

                    if ($has_valid_function_call_type) {
                        if (IssueBuffer::accepts(
                            new PossiblyInvalidFunctionCall(
                                'Cannot treat type ' . $var_type_part . ' as callable',
                                new CodeLocation($statements_checker->getSource(), $stmt)
                            ),
                            $statements_checker->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } else {
                        if (IssueBuffer::accepts(
                            new InvalidFunctionCall(
                                'Cannot treat type ' . $var_type_part . ' as callable',
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
                    $statements_checker,
                    strtolower($method_id)
                );
            } else {
                $function_exists = true;
            }

            if ($function_exists) {
                if (!$in_call_map || $is_stubbed) {
                    $function_storage = FunctionChecker::getStorage(
                        $statements_checker,
                        strtolower($method_id)
                    );

                    $function_params = $function_storage->params;

                    if (!$is_predefined) {
                        $defined_constants = $function_storage->defined_constants;
                    }
                }

                if ($in_call_map && !$is_stubbed) {
                    $function_params = FunctionLikeChecker::getFunctionParamsFromCallMapById(
                        $statements_checker->getFileChecker()->project_checker,
                        $method_id,
                        $stmt->args
                    );
                }
            }
        }

        if (self::checkFunctionArguments(
            $statements_checker,
            $stmt->args,
            $function_params,
            $method_id,
            $context
        ) === false) {
            // fall through
        }

        $config = Config::getInstance();

        if ($function_exists) {
            $generic_params = null;

            if ($stmt->name instanceof PhpParser\Node\Name && $method_id) {
                if (!$is_stubbed && $in_call_map) {
                    $function_params = FunctionLikeChecker::getFunctionParamsFromCallMapById(
                        $statements_checker->getFileChecker()->project_checker,
                        $method_id,
                        $stmt->args
                    );
                }
            }

            // do this here to allow closure param checks
            if (self::checkFunctionArgumentsMatch(
                $statements_checker,
                $stmt->args,
                $method_id,
                $function_params ?: [],
                $function_storage,
                null,
                $generic_params,
                $code_location,
                $context
            ) === false) {
                // fall through
            }

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
                            $return_type = clone $function_storage->return_type;

                            if ($generic_params && $function_storage->template_types) {
                                $return_type->replaceTemplateTypesWithArgTypes(
                                    $generic_params
                                );
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
                        $statements_checker,
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
                $op_vars_in_scope = Reconciler::reconcileKeyedTypes(
                    $assert_type_assertions,
                    $context->vars_in_scope,
                    $changed_vars,
                    [],
                    $statements_checker,
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

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        if ($stmt->name instanceof PhpParser\Node\Name &&
            ($stmt->name->parts === ['get_class'] || $stmt->name->parts === ['gettype']) &&
            $stmt->args
        ) {
            $var = $stmt->args[0]->value;

            if ($var instanceof PhpParser\Node\Expr\Variable && is_string($var->name)) {
                $atomic_type = $stmt->name->parts === ['get_class']
                    ? new Type\Atomic\GetClassT('$' . $var->name)
                    : new Type\Atomic\GetTypeT('$' . $var->name);

                $stmt->inferredType = new Type\Union([$atomic_type]);
            }
        }

        return null;
    }

    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Expr\New_    $stmt
     * @param   Context                     $context
     *
     * @return  false|null
     */
    public static function analyzeNew(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\New_ $stmt,
        Context $context
    ) {
        $fq_class_name = null;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $late_static = false;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            if (!in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)) {
                $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_checker->getAliases()
                );

                if ($context->check_classes) {
                    if ($context->isPhantomClass($fq_class_name)) {
                        return null;
                    }

                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $fq_class_name,
                        new CodeLocation($statements_checker->getSource(), $stmt->class),
                        $statements_checker->getSuppressedIssues(),
                        false
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
            $fq_class_name = ClassChecker::getAnonymousClassName($stmt->class, $statements_checker->getFilePath());
        } else {
            ExpressionChecker::analyze($statements_checker, $stmt->class, $context);
        }

        if ($fq_class_name) {
            $stmt->inferredType = new Type\Union([new TNamedObject($fq_class_name)]);

            if (strtolower($fq_class_name) !== 'stdclass' &&
                $context->check_classes &&
                ClassChecker::classExists($project_checker, $fq_class_name)
            ) {
                $storage = $project_checker->classlike_storage_provider->get($fq_class_name);

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

                if ($storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $fq_class_name . ' is marked deprecated',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                if (MethodChecker::methodExists(
                    $project_checker,
                    $fq_class_name . '::__construct',
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

                    if (MethodChecker::checkMethodVisibility(
                        $method_id,
                        $context->self,
                        $statements_checker->getSource(),
                        new CodeLocation($statements_checker->getSource(), $stmt),
                        $statements_checker->getSuppressedIssues()
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

                            foreach ($first_arg_type->getTypes() as $type) {
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
                                        $value_type,
                                    ]
                                ),
                            ]);
                        }
                    } elseif ($generic_params) {
                        $stmt->inferredType = new Type\Union([
                            new Type\Atomic\TGenericObject(
                                $fq_class_name,
                                $generic_params
                            ),
                        ]);
                    }
                } elseif ($stmt->args) {
                    if (IssueBuffer::accepts(
                        new TooManyArguments(
                            'Class ' . $fq_class_name . ' has no __construct, but arguments were passed',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }
        }

        $config = Config::getInstance();

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        return null;
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\MethodCall  $stmt
     * @param   Context                         $context
     *
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

        $class_type = $var_id && $context->hasVariable($var_id) ? $context->vars_in_scope[$var_id] : null;

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

        if ($class_type &&
            is_string($stmt->name) &&
            $class_type->isNullable() &&
            !$class_type->ignore_nullable_issues
        ) {
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

        if ($class_type &&
            is_string($stmt->name) &&
            $class_type->isFalsable()
        ) {
            if (IssueBuffer::accepts(
                new PossiblyFalseReference(
                    'Cannot call method ' . $stmt->name . ' on possibly false variable ' . $var_id,
                    new CodeLocation($statements_checker->getSource(), $stmt->var)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $config = Config::getInstance();
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $non_existent_method_ids = [];
        $existent_method_ids = [];

        $invalid_method_call_types = [];
        $has_valid_method_call_type = false;

        $code_location = new CodeLocation($source, $stmt);

        if ($class_type && is_string($stmt->name)) {
            $return_type = null;
            $method_name_lc = strtolower($stmt->name);

            foreach ($class_type->getTypes() as $class_type_part) {
                if (!$class_type_part instanceof TNamedObject) {
                    switch (get_class($class_type_part)) {
                        case 'Psalm\\Type\\Atomic\\TNull':
                        case 'Psalm\\Type\\Atomic\\TFalse':
                            // handled above
                            break;

                        case 'Psalm\\Type\\Atomic\\TInt':
                        case 'Psalm\\Type\\Atomic\\TBool':
                        case 'Psalm\\Type\\Atomic\\TTrue':
                        case 'Psalm\\Type\\Atomic\\TArray':
                        case 'Psalm\\Type\\Atomic\\TString':
                        case 'Psalm\\Type\\Atomic\\TNumericString':
                            $invalid_method_call_types[] = (string)$class_type_part;
                            break;

                        case 'Psalm\\Type\\Atomic\\TMixed':
                        case 'Psalm\\Type\\Atomic\\TObject':
                            if (IssueBuffer::accepts(
                                new MixedMethodCall(
                                    'Cannot call method ' . $stmt->name . ' on a mixed variable ' . $var_id,
                                    $code_location
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // fall through
                            }
                            break;
                    }

                    continue;
                }

                $has_valid_method_call_type = true;

                $fq_class_name = $class_type_part->value;

                $intersection_types = $class_type_part->getIntersectionTypes();

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
                        $statements_checker,
                        $fq_class_name,
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    );
                }

                if (!$does_class_exist) {
                    return $does_class_exist;
                }

                if ($fq_class_name === 'iterable') {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            $fq_class_name . ' has no defined methods',
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                $method_id = $fq_class_name . '::' . $method_name_lc;

                if (MethodChecker::methodExists(
                    $project_checker,
                    $fq_class_name . '::__call'
                )
                ) {
                    if (!MethodChecker::methodExists($project_checker, $method_id)
                        || !MethodChecker::isMethodVisible(
                            $method_id,
                            $context->self,
                            $statements_checker->getSource()
                        )
                    ) {
                        $return_type = Type::getMixed();
                        continue;
                    }
                }

                if ($var_id === '$this' &&
                    $context->self &&
                    $fq_class_name !== $context->self &&
                    MethodChecker::methodExists($project_checker, $context->self . '::' . $method_name_lc)
                ) {
                    $method_id = $context->self . '::' . $method_name_lc;
                    $fq_class_name = $context->self;
                }

                if ($intersection_types && !MethodChecker::methodExists($project_checker, $method_id)) {
                    foreach ($intersection_types as $intersection_type) {
                        $method_id = $intersection_type->value . '::' . $method_name_lc;
                        $fq_class_name = $intersection_type->value;

                        if (MethodChecker::methodExists($project_checker, $method_id)) {
                            break;
                        }
                    }
                }

                $cased_method_id = $fq_class_name . '::' . $stmt->name;

                if (!MethodChecker::methodExists($project_checker, $method_id, $code_location)) {
                    $non_existent_method_ids[] = $method_id;
                    continue;
                }

                $existent_method_ids[] = $method_id;

                $class_template_params = null;

                if ($stmt->var instanceof PhpParser\Node\Expr\Variable &&
                    ($context->collect_initializations || $context->collect_mutations) &&
                    $stmt->var->name === 'this' &&
                    is_string($stmt->name) &&
                    $source instanceof FunctionLikeChecker
                ) {
                    self::collectSpecialInformation($source, $stmt->name, $context);
                }

                $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

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
                    $code_location,
                    $statements_checker
                ) === false) {
                    return false;
                }

                $return_type_location = null;
                $project_checker = $source->getFileChecker()->project_checker;

                switch (strtolower($stmt->name)) {
                    case '__tostring':
                        $return_type = Type::getString();
                        continue;
                }

                if ($method_name_lc === '__tostring') {
                    $return_type_candidate = Type::getString();
                } elseif (FunctionChecker::inCallMap($cased_method_id)) {
                    $return_type_candidate = FunctionChecker::getReturnTypeFromCallMap($method_id);

                    $return_type_candidate = ExpressionChecker::fleshOutType(
                        $project_checker,
                        $return_type_candidate,
                        $fq_class_name,
                        $method_id
                    );
                } else {
                    if (MethodChecker::checkMethodVisibility(
                        $method_id,
                        $context->self,
                        $statements_checker->getSource(),
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    if (MethodChecker::checkMethodNotDeprecated(
                        $project_checker,
                        $method_id,
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        return false;
                    }

                    $return_type_candidate = MethodChecker::getMethodReturnType($project_checker, $method_id);

                    if ($return_type_candidate) {
                        $return_type_candidate = clone $return_type_candidate;

                        if ($class_template_params) {
                            $return_type_candidate->replaceTemplateTypesWithArgTypes(
                                $class_template_params
                            );
                        }

                        $return_type_candidate = ExpressionChecker::fleshOutType(
                            $project_checker,
                            $return_type_candidate,
                            $fq_class_name,
                            $method_id
                        );

                        $return_type_location = MethodChecker::getMethodReturnTypeLocation(
                            $project_checker,
                            $method_id,
                            $secondary_return_type_location
                        );

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

            if ($invalid_method_call_types) {
                $invalid_class_type = $invalid_method_call_types[0];

                if ($has_valid_method_call_type) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidMethodCall(
                            'Cannot call method on possible ' . $invalid_class_type . ' variable ' . $var_id,
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new InvalidMethodCall(
                            'Cannot call method on ' . $invalid_class_type . ' variable ' . $var_id,
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }

            if ($non_existent_method_ids) {
                if ($existent_method_ids) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedMethod(
                            'Method ' . $non_existent_method_ids[0] . ' does not exist',
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new UndefinedMethod(
                            'Method ' . $non_existent_method_ids[0] . ' does not exist',
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }

                return null;
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

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }

        // if we called a method on this nullable variable, remove the nullable status here
        // because any further calls must have worked
        if ($var_id
            && $class_type
            && $has_valid_method_call_type
            && !$invalid_method_call_types
            && $existent_method_ids
            && ($class_type->from_docblock || $class_type->isNullable())
        ) {
            $keys_to_remove = [];

            foreach ($class_type->getTypes() as $key => $type) {
                if (!$type instanceof TNamedObject) {
                    $keys_to_remove[] = $key;
                } else {
                    $type->from_docblock = false;
                }
            }

            foreach ($keys_to_remove as $key) {
                $class_type->removeType($key);
            }

            $class_type->from_docblock = false;

            $context->removeVarFromConflictingClauses($var_id, null, $statements_checker);

            $context->vars_in_scope[$var_id] = $class_type;
        }
    }

    /**
     * @param   FunctionLikeChecker $source
     * @param   string              $method_name
     * @param   Context             $context
     *
     * @return  void
     */
    public static function collectSpecialInformation(
        FunctionLikeChecker $source,
        $method_name,
        Context $context
    ) {
        $fq_class_name = (string)$source->getFQCLN();

        $project_checker = $source->getFileChecker()->project_checker;

        if ($context->collect_mutations &&
            $context->self &&
            (
                $context->self === $fq_class_name ||
                ClassChecker::classExtends(
                    $project_checker,
                    $context->self,
                    $fq_class_name
                )
            )
        ) {
            $method_id = $fq_class_name . '::' . strtolower($method_name);

            if ($method_id !== $source->getMethodId()) {
                $project_checker->getMethodMutations($method_id, $context);
            }
        } elseif ($context->collect_initializations &&
            $context->self &&
            (
                $context->self === $fq_class_name ||
                ClassChecker::classExtends(
                    $project_checker,
                    $context->self,
                    $fq_class_name
                )
            ) &&
            $source->getMethodName() !== $method_name
        ) {
            $method_id = $fq_class_name . '::' . strtolower($method_name);

            $declaring_method_id = MethodChecker::getDeclaringMethodId($project_checker, $method_id);

            $method_storage = MethodChecker::getStorage($project_checker, (string)$declaring_method_id);

            $class_checker = $source->getSource();

            if ($class_checker instanceof ClassLikeChecker &&
                ($method_storage->visibility === ClassLikeChecker::VISIBILITY_PRIVATE || $method_storage->final)
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
                    $context->vars_possibly_in_scope[$var] = true;
                }
            }
        }
    }

    /**
     * @param   StatementsChecker               $statements_checker
     * @param   PhpParser\Node\Expr\StaticCall  $stmt
     * @param   Context                         $context
     *
     * @return  false|null
     */
    public static function analyzeStaticCall(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context
    ) {
        $method_id = null;
        $fq_class_name = null;

        $lhs_type = null;

        $file_checker = $statements_checker->getFileChecker();
        $project_checker = $file_checker->project_checker;
        $source = $statements_checker->getSource();

        $stmt->inferredType = null;

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $fq_class_name = null;

            if (count($stmt->class->parts) === 1
                && in_array(strtolower($stmt->class->parts[0]), ['self', 'static', 'parent'], true)
            ) {
                if ($stmt->class->parts[0] === 'parent') {
                    $child_fq_class_name = $context->self;

                    $class_storage = $child_fq_class_name
                        ? $project_checker->classlike_storage_provider->get($child_fq_class_name)
                        : null;

                    if (!$class_storage || !$class_storage->parent_classes) {
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

                    $fq_class_name = $class_storage->parent_classes[0];

                    $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

                    $fq_class_name = $class_storage->name;

                    if (is_string($stmt->name) && $class_storage->user_defined) {
                        $method_id = $fq_class_name . '::' . strtolower($stmt->name);

                        $old_context_include_location = $context->include_location;
                        $old_self = $context->self;
                        $context->include_location = new CodeLocation($statements_checker->getSource(), $stmt);
                        $context->self = $fq_class_name;

                        if ($context->collect_mutations) {
                            $file_checker->getMethodMutations($method_id, $context);
                        } elseif ($context->collect_initializations) {
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

                            $file_checker->getMethodMutations($method_id, $context);

                            foreach ($local_vars_in_scope as $var => $type) {
                                $context->vars_in_scope[$var] = $type;
                            }

                            foreach ($local_vars_possibly_in_scope as $var => $type) {
                                $context->vars_possibly_in_scope[$var] = $type;
                            }
                        }

                        $context->include_location = $old_context_include_location;
                        $context->self = $old_self;
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
                    $statements_checker->getAliases()
                );

                if ($context->isPhantomClass($fq_class_name)) {
                    return null;
                }

                $does_class_exist = false;

                if ($context->self) {
                    $self_storage = $project_checker->classlike_storage_provider->get($context->self);

                    if (isset($self_storage->used_traits[strtolower($fq_class_name)])) {
                        $fq_class_name = $context->self;
                        $does_class_exist = true;
                    }
                }

                if (!$does_class_exist) {
                    $does_class_exist = ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $fq_class_name,
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

            if (!isset($lhs_type) || $lhs_type->hasString()) {
                return null;
            }
        }

        if (!$context->check_methods || !$lhs_type) {
            return null;
        }

        $has_mock = false;

        $config = Config::getInstance();

        foreach ($lhs_type->getTypes() as $lhs_type_part) {
            if (!$lhs_type_part instanceof TNamedObject) {
                // @todo deal with it
                continue;
            }

            $fq_class_name = $lhs_type_part->value;

            $is_mock = ExpressionChecker::isMock($fq_class_name);

            $has_mock = $has_mock || $is_mock;

            $method_id = null;

            if (is_string($stmt->name) &&
                !MethodChecker::methodExists($project_checker, $fq_class_name . '::__callStatic') &&
                !$is_mock
            ) {
                $method_id = $fq_class_name . '::' . strtolower($stmt->name);
                $cased_method_id = $fq_class_name . '::' . $stmt->name;

                $does_method_exist = MethodChecker::checkMethodExists(
                    $project_checker,
                    $cased_method_id,
                    new CodeLocation($source, $stmt),
                    $statements_checker->getSuppressedIssues()
                );

                if (!$does_method_exist) {
                    return $does_method_exist;
                }

                $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

                if ($class_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $fq_class_name . ' is marked deprecated',
                            new CodeLocation($statements_checker->getSource(), $stmt)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
                    }
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
                    && (
                        !$context->self
                        || $statements_checker->isStatic()
                        || !ClassChecker::classExtends($project_checker, $context->self, $fq_class_name)
                    )
                ) {
                    if (MethodChecker::checkStatic(
                        $method_id,
                        $stmt->class instanceof PhpParser\Node\Name && strtolower($stmt->class->parts[0]) === 'self',
                        $project_checker,
                        new CodeLocation($source, $stmt),
                        $statements_checker->getSuppressedIssues()
                    ) === false) {
                        // fall through
                    }
                }

                if (MethodChecker::checkMethodNotDeprecated(
                    $project_checker,
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

                $return_type_candidate = MethodChecker::getMethodReturnType($project_checker, $method_id);

                if ($return_type_candidate) {
                    $return_type_candidate = clone $return_type_candidate;

                    if ($found_generic_params) {
                        $return_type_candidate->replaceTemplateTypesWithArgTypes(
                            $found_generic_params
                        );
                    }

                    $return_type_candidate = ExpressionChecker::fleshOutType(
                        $project_checker,
                        $return_type_candidate,
                        $fq_class_name,
                        $method_id
                    );

                    $return_type_location = MethodChecker::getMethodReturnTypeLocation(
                        $project_checker,
                        $method_id,
                        $secondary_return_type_location
                    );

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

        if (!$config->remember_property_assignments_after_call && !$context->collect_initializations) {
            $context->removeAllObjectVars();
        }
    }

    /**
     * @param  string|null                      $method_id
     * @param  array<int, PhpParser\Node\Arg>   $args
     * @param  array<string, Type\Union>|null   &$generic_params
     * @param  Context                          $context
     * @param  CodeLocation                     $code_location
     * @param  StatementsChecker                $statements_checker
     *
     * @return false|null
     */
    protected static function checkMethodArgs(
        $method_id,
        array $args,
        &$generic_params,
        Context $context,
        CodeLocation $code_location,
        StatementsChecker $statements_checker
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $method_params = $method_id
            ? FunctionLikeChecker::getMethodParamsById($project_checker, $method_id, $args)
            : null;

        if (self::checkFunctionArguments(
            $statements_checker,
            $args,
            $method_params,
            $method_id,
            $context
        ) === false) {
            return false;
        }

        if (!$method_id || $method_params === null) {
            return;
        }

        list($fq_class_name, $method_name) = explode('::', $method_id);

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        $method_storage = null;

        if (isset($class_storage->declaring_method_ids[strtolower($method_name)])) {
            $declaring_method_id = $class_storage->declaring_method_ids[strtolower($method_name)];

            list($declaring_fq_class_name, $declaring_method_name) = explode('::', $declaring_method_id);

            if ($declaring_fq_class_name !== $fq_class_name) {
                $declaring_class_storage = $project_checker->classlike_storage_provider->get($declaring_fq_class_name);
            } else {
                $declaring_class_storage = $class_storage;
            }

            if (!isset($declaring_class_storage->methods[strtolower($declaring_method_name)])) {
                throw new \UnexpectedValueException('Storage should not be empty here');
            }

            $method_storage = $declaring_class_storage->methods[strtolower($declaring_method_name)];
        }

        if (!$class_storage->user_defined) {
            // check again after we've processed args
            $method_params = FunctionLikeChecker::getMethodParamsById(
                $project_checker,
                $method_id,
                $args
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
            $context
        ) === false) {
            return false;
        }

        return null;
    }

    /**
     * @param   StatementsChecker                       $statements_checker
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   array<int, FunctionLikeParameter>|null  $function_params
     * @param   string|null                             $method_id
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    protected static function checkFunctionArguments(
        StatementsChecker $statements_checker,
        array $args,
        $function_params,
        $method_id,
        Context $context
    ) {
        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        // if this modifies the array type based on further args
        if ($method_id && in_array($method_id, ['array_push', 'array_unshift'], true) && $function_params) {
            $array_arg = $args[0]->value;

            if (ExpressionChecker::analyze(
                $statements_checker,
                $array_arg,
                $context
            ) === false) {
                return false;
            }

            if (isset($array_arg->inferredType) && $array_arg->inferredType->hasArray()) {
                /** @var TArray|ObjectLike */
                $array_type = $array_arg->inferredType->getTypes()['array'];

                if ($array_type instanceof ObjectLike) {
                    $array_type = $array_type->getGenericArrayType();
                }

                $by_ref_type = new Type\Union([clone $array_type]);

                foreach ($args as $argument_offset => $arg) {
                    if ($argument_offset === 0) {
                        continue;
                    }

                    if (ExpressionChecker::analyze(
                        $statements_checker,
                        $arg->value,
                        $context
                    ) === false) {
                        return false;
                    }

                    $by_ref_type = Type::combineUnionTypes(
                        $by_ref_type,
                        $by_ref_type = new Type\Union(
                            [
                                new TArray(
                                    [
                                        Type::getInt(),
                                        isset($arg->value->inferredType)
                                            ? clone $arg->value->inferredType
                                            : Type::getMixed(),
                                        ]
                                ),
                            ]
                        )
                    );
                }

                ExpressionChecker::assignByRefParam(
                    $statements_checker,
                    $array_arg,
                    $by_ref_type,
                    $context,
                    false
                );
            }

            return;
        }

        foreach ($args as $argument_offset => $arg) {
            if ($function_params !== null) {
                $by_ref = $argument_offset < count($function_params)
                    ? $function_params[$argument_offset]->by_ref
                    : $last_param && $last_param->is_variadic && $last_param->by_ref;

                $by_ref_type = null;

                if ($by_ref && $last_param) {
                    if ($argument_offset < count($function_params)) {
                        $by_ref_type = $function_params[$argument_offset]->type;
                    } else {
                        $by_ref_type = $last_param->type;
                    }

                    $by_ref_type = $by_ref_type ? clone $by_ref_type : Type::getMixed();
                }

                if ($by_ref && $by_ref_type) {
                    // special handling for array sort
                    if ($argument_offset === 0
                        && $method_id
                        && in_array(
                            $method_id,
                            [
                                'shuffle', 'sort', 'rsort', 'usort', 'ksort', 'asort',
                                'krsort', 'arsort', 'natcasesort', 'natsort', 'reset',
                                'end', 'next', 'prev', 'array_pop', 'array_shift',
                            ],
                            true
                        )
                    ) {
                        if (ExpressionChecker::analyze(
                            $statements_checker,
                            $arg->value,
                            $context
                        ) === false) {
                            return false;
                        }

                        if (in_array($method_id, ['array_pop', 'array_shift'], true)) {
                            $var_id = ExpressionChecker::getVarId(
                                $arg->value,
                                $statements_checker->getFQCLN(),
                                $statements_checker
                            );

                            if ($var_id) {
                                $context->removeVarFromConflictingClauses($var_id, null, $statements_checker);
                            }

                            continue;
                        }

                        // noops
                        if (in_array($method_id, ['reset', 'end', 'next', 'prev'], true)) {
                            continue;
                        }

                        if (isset($arg->value->inferredType)
                            && $arg->value->inferredType->hasArray()
                        ) {
                            /** @var TArray|ObjectLike */
                            $array_type = $arg->value->inferredType->getTypes()['array'];

                            if ($array_type instanceof ObjectLike) {
                                $array_type = $array_type->getGenericArrayType();
                            }

                            if (in_array($method_id, ['shuffle', 'sort', 'rsort', 'usort'], true)) {
                                $tvalue = $array_type->type_params[1];
                                $by_ref_type = new Type\Union([new TArray([Type::getInt(), clone $tvalue])]);
                            } else {
                                $by_ref_type = new Type\Union([clone $array_type]);
                            }

                            ExpressionChecker::assignByRefParam(
                                $statements_checker,
                                $arg->value,
                                $by_ref_type,
                                $context,
                                false
                            );

                            continue;
                        }
                    }

                    if ($method_id === 'socket_select') {
                        if (ExpressionChecker::analyze(
                            $statements_checker,
                            $arg->value,
                            $context
                        ) === false) {
                            return false;
                        }
                    }
                } else {
                    if ($arg->value instanceof PhpParser\Node\Expr\Variable) {
                        if (ExpressionChecker::analyzeVariable(
                            $statements_checker,
                            $arg->value,
                            $context
                        ) === false) {
                            return false;
                        }
                    } elseif ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch) {
                        if (FetchChecker::analyzePropertyFetch(
                            $statements_checker,
                            $arg->value,
                            $context
                        ) === false
                        ) {
                            return false;
                        }
                    } else {
                        if (ExpressionChecker::analyze($statements_checker, $arg->value, $context) === false) {
                            return false;
                        }
                    }
                }
            } else {
                if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch && is_string($arg->value->name)) {
                    $var_id = '$' . $arg->value->name;
                } else {
                    $var_id = ExpressionChecker::getVarId(
                        $arg->value,
                        $statements_checker->getFQCLN(),
                        $statements_checker
                    );
                }

                if ($var_id &&
                    (!$context->hasVariable($var_id) || $context->vars_in_scope[$var_id]->isNull())
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
        }

        return null;
    }

    /**
     * @param   StatementsChecker                       $statements_checker
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   string|null                             $method_id
     * @param   array<int,FunctionLikeParameter>        $function_params
     * @param   FunctionLikeStorage|null                $function_storage
     * @param   ClassLikeStorage|null                   $class_storage
     * @param   array<string, Type\Union>|null          $generic_params
     * @param   CodeLocation                            $code_location
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    protected static function checkFunctionArgumentsMatch(
        StatementsChecker $statements_checker,
        array $args,
        $method_id,
        array $function_params,
        $function_storage,
        $class_storage,
        &$generic_params,
        CodeLocation $code_location,
        Context $context
    ) {
        $in_call_map = $method_id ? FunctionChecker::inCallMap($method_id) : false;

        $cased_method_id = $method_id;

        $is_variadic = false;

        $fq_class_name = null;

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        if ($method_id) {
            if ($in_call_map || !strpos($method_id, '::')) {
                $is_variadic = FunctionChecker::isVariadic(
                    $project_checker,
                    strtolower($method_id),
                    $statements_checker->getFilePath()
                );
            } else {
                $fq_class_name = explode('::', $method_id)[0];
                $is_variadic = MethodChecker::isVariadic($project_checker, $method_id);
            }
        }

        if ($method_id && strpos($method_id, '::') && !$in_call_map) {
            $cased_method_id = MethodChecker::getCasedMethodId($project_checker, $method_id);
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
            $function_param = count($function_params) > $argument_offset
                ? $function_params[$argument_offset]
                : ($last_param && $last_param->is_variadic ? $last_param : null);

            if ($function_param
                && $function_param->by_ref
            ) {
                if ($arg->value instanceof PhpParser\Node\Scalar
                    || $arg->value instanceof PhpParser\Node\Expr\Array_
                    || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                    || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidPassByReference(
                            'Parameter ' . ($argument_offset + 1) . ' of ' . $method_id . ' expects a variable',
                            $code_location
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                if (!in_array(
                    $method_id,
                    [
                        'shuffle', 'sort', 'rsort', 'usort', 'ksort', 'asort',
                        'krsort', 'arsort', 'natcasesort', 'natsort', 'reset',
                        'end', 'next', 'prev', 'array_pop', 'array_shift',
                        'array_push', 'array_unshift', 'socket_select',
                    ],
                    true
                )) {
                    $by_ref_type = null;

                    if ($last_param) {
                        if ($argument_offset < count($function_params)) {
                            $by_ref_type = $function_params[$argument_offset]->type;
                        } else {
                            $by_ref_type = $last_param->type;
                        }

                        if ($template_types && $by_ref_type) {
                            if ($generic_params === null) {
                                $generic_params = [];
                            }

                            $by_ref_type = clone $by_ref_type;

                            $by_ref_type->replaceTemplateTypesWithStandins($template_types, $generic_params);
                        }
                    }

                    $by_ref_type = $by_ref_type ?: Type::getMixed();

                    ExpressionChecker::assignByRefParam(
                        $statements_checker,
                        $arg->value,
                        $by_ref_type,
                        $context,
                        $method_id && (strpos($method_id, '::') !== false || !FunctionChecker::inCallMap($method_id))
                    );
                }
            }

            if (isset($arg->value->inferredType)) {
                if ($function_param && $function_param->type) {
                    $param_type = clone $function_param->type;

                    if ($function_param->is_variadic) {
                        if (!$param_type->hasArray()) {
                            continue;
                        }

                        $array_atomic_type = $param_type->getTypes()['array'];

                        if (!$array_atomic_type instanceof TArray) {
                            continue;
                        }

                        $param_type = clone $array_atomic_type->type_params[1];
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
                                        $statements_checker->getAliases()
                                    )
                                );
                            } elseif ($arg->value instanceof PhpParser\Node\Scalar\String_ && $arg->value->value) {
                                $offset_value_type = Type::parseString($arg->value->value);
                            }

                            if ($offset_value_type) {
                                foreach ($offset_value_type->getTypes() as $offset_value_type_part) {
                                    // register class if the class exists
                                    if ($offset_value_type_part instanceof TNamedObject) {
                                        ClassLikeChecker::checkFullyQualifiedClassLikeName(
                                            $statements_checker,
                                            $offset_value_type_part->value,
                                            new CodeLocation($statements_checker->getSource(), $arg->value),
                                            $statements_checker->getSuppressedIssues()
                                        );
                                    }
                                }

                                $offset_value_type->setFromDocblock();
                            }

                            if ($generic_params === null) {
                                $generic_params = [];
                            }

                            $generic_params[$template_type] = $offset_value_type ?: Type::getMixed();
                        } elseif ($template_types) {
                            if ($generic_params === null) {
                                $generic_params = [];
                            }

                            $param_type->replaceTemplateTypesWithStandins(
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

                    $fleshed_out_type = ExpressionChecker::fleshOutType(
                        $project_checker,
                        $param_type,
                        $fq_class_name,
                        $method_id
                    );

                    if ($context->check_variables) {
                        if (self::checkFunctionArgumentType(
                            $statements_checker,
                            $arg->value->inferredType,
                            $fleshed_out_type,
                            $cased_method_id,
                            $argument_offset,
                            new CodeLocation($statements_checker->getSource(), $arg->value),
                            $arg->value,
                            $context,
                            $function_param->by_ref
                        ) === false) {
                            return false;
                        }
                    }
                }
            }
        }

        if ($method_id === 'array_map' || $method_id === 'array_filter') {
            if ($method_id === 'array_map' && count($args) < 2) {
                if (IssueBuffer::accepts(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            } elseif ($method_id === 'array_filter' && count($args) < 1) {
                if (IssueBuffer::accepts(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
                }
            }

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
            for ($i = count($args); $i < count($function_params); ++$i) {
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
     *
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
                    && isset($array_arg->inferredType->getTypes()['array'])
                ? $array_arg->inferredType->getTypes()['array']
                : null;

            if ($array_arg_type instanceof ObjectLike) {
                $array_arg_type = $array_arg_type->getGenericArrayType();
            }

            $array_arg_types[] = $array_arg_type;
        }

        /** @var ?PhpParser\Node\Arg */
        $closure_arg = isset($args[$closure_index]) ? $args[$closure_index] : null;

        /** @var Type\Union|null */
        $closure_arg_type = $closure_arg && isset($closure_arg->value->inferredType)
                ? $closure_arg->value->inferredType
                : null;

        $file_checker = $statements_checker->getFileChecker();

        $project_checker = $file_checker->project_checker;

        if ($closure_arg && $closure_arg_type) {
            $min_closure_param_count = $max_closure_param_count = count($array_arg_types);

            if ($method_id === 'array_filter') {
                $max_closure_param_count = count($args) > 2 ? 2 : 1;
            }

            foreach ($closure_arg_type->getTypes() as $closure_type) {
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
                        ++$i;
                        continue;
                    }

                    /** @var Type\Atomic\TArray */
                    $array_arg_type = $array_arg_types[$i];

                    $input_type = $array_arg_type->type_params[1];

                    if ($input_type->isMixed()) {
                        ++$i;
                        continue;
                    }

                    $closure_param_type = $closure_param->type;

                    if (!$closure_param_type) {
                        ++$i;
                        continue;
                    }

                    $type_match_found = TypeChecker::isContainedBy(
                        $project_checker,
                        $input_type,
                        $closure_param_type,
                        false,
                        false,
                        $scalar_type_match_found,
                        $type_coerced,
                        $type_coerced_from_mixed
                    );

                    if ($type_coerced) {
                        if ($type_coerced_from_mixed) {
                            if (IssueBuffer::accepts(
                                new MixedTypeCoercion(
                                    'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                        $closure_param_type . ', parent type ' . $input_type . ' provided',
                                    new CodeLocation($statements_checker->getSource(), $closure_arg)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        } else {
                            if (IssueBuffer::accepts(
                                new TypeCoercion(
                                    'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                        $closure_param_type . ', parent type ' . $input_type . ' provided',
                                    new CodeLocation($statements_checker->getSource(), $closure_arg)
                                ),
                                $statements_checker->getSuppressedIssues()
                            )) {
                                // keep soldiering on
                            }
                        }
                    }

                    if (!$type_coerced && !$type_match_found) {
                        $types_can_be_identical = TypeChecker::canBeIdenticalTo(
                            $project_checker,
                            $input_type,
                            $closure_param_type
                        );

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
                        } elseif ($types_can_be_identical) {
                            if (IssueBuffer::accepts(
                                new PossiblyInvalidArgument(
                                    'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                        $closure_param_type . ', possibly different type ' . $input_type . ' provided',
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

                    ++$i;
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
     * @param   bool                $by_ref
     *
     * @return  null|false
     */
    public static function checkFunctionArgumentType(
        StatementsChecker $statements_checker,
        Type\Union $input_type,
        Type\Union $param_type,
        $cased_method_id,
        $argument_offset,
        CodeLocation $code_location,
        PhpParser\Node\Expr $input_expr,
        Context $context,
        $by_ref = false
    ) {
        if ($param_type->isMixed()) {
            return null;
        }

        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $method_identifier = $cased_method_id ? ' of ' . $cased_method_id : '';

        if ($project_checker->infer_types_from_usage && $input_expr->inferredType) {
            $source_checker = $statements_checker->getSource();

            if ($source_checker instanceof FunctionLikeChecker) {
                $context->inferType(
                    $input_expr,
                    $source_checker->getFunctionLikeStorage($statements_checker),
                    $param_type
                );
            }
        }

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

            if ($input_type->isNullable() && !$input_type->ignore_nullable_issues) {
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

        if ($input_type->isFalsable() && !$param_type->hasBool()) {
            if (IssueBuffer::accepts(
                new PossiblyFalseArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be false, possibly ' .
                        'false value provided',
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        $param_type = TypeChecker::simplifyUnionType(
            $project_checker,
            $param_type
        );

        $type_match_found = TypeChecker::isContainedBy(
            $project_checker,
            $input_type,
            $param_type,
            true,
            true,
            $scalar_type_match_found,
            $type_coerced,
            $type_coerced_from_mixed,
            $to_string_cast
        );

        if ($type_coerced) {
            if ($type_coerced_from_mixed) {
                if (IssueBuffer::accepts(
                    new MixedTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type .
                            ', parent type ' . $input_type . ' provided',
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            } else {
                if (IssueBuffer::accepts(
                    new TypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type .
                            ', parent type ' . $input_type . ' provided',
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
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

        if (!$type_match_found && !$type_coerced) {
            $types_can_be_identical = TypeChecker::canBeIdenticalTo(
                $project_checker,
                $param_type,
                $input_type
            );

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
            } elseif ($types_can_be_identical) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type .
                            ', possibly different type ' . $input_type . ' provided',
                        $code_location
                    ),
                    $statements_checker->getSuppressedIssues()
                )) {
                    return false;
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
        } elseif ($input_expr instanceof PhpParser\Node\Scalar\String_
            || $input_expr instanceof PhpParser\Node\Expr\Array_
        ) {
            foreach ($param_type->getTypes() as $param_type_part) {
                if ($param_type_part instanceof TCallable) {
                    $function_ids = self::getFunctionIdsFromCallableArg(
                        $statements_checker,
                        $input_expr
                    );

                    foreach ($function_ids as $function_id) {
                        if (strpos($function_id, '::') !== false) {
                            list($callable_fq_class_name) = explode('::', $function_id);

                            if (!in_array(strtolower($callable_fq_class_name), ['self', 'static', 'parent'], true)) {
                                if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                                    $statements_checker,
                                    $callable_fq_class_name,
                                    $code_location,
                                    $statements_checker->getSuppressedIssues()
                                ) === false
                                ) {
                                    return false;
                                }

                                if (MethodChecker::checkMethodExists(
                                    $project_checker,
                                    $function_id,
                                    $code_location,
                                    $statements_checker->getSuppressedIssues()
                                ) === false
                                ) {
                                    return false;
                                }
                            }
                        } else {
                            if (self::checkFunctionExists(
                                $statements_checker,
                                $function_id,
                                $code_location
                            ) === false
                            ) {
                                return false;
                            }
                        }
                    }
                }
            }
        }

        if ($type_match_found
            && !$param_type->isMixed()
            && !$param_type->from_docblock
            && !$by_ref
        ) {
            $var_id = ExpressionChecker::getVarId(
                $input_expr,
                $statements_checker->getFQCLN(),
                $statements_checker
            );

            if ($var_id) {
                if ($input_type->isNullable() && !$param_type->isNullable()) {
                    $input_type->removeType('null');
                }

                if ($input_type->getId() === $param_type->getId()) {
                    $input_type->from_docblock = false;
                }

                $context->removeVarFromConflictingClauses($var_id, null, $statements_checker);

                $context->vars_in_scope[$var_id] = $input_type;
            }
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Scalar\String_|PhpParser\Node\Expr\Array_ $callable_arg
     *
     * @return string[]
     */
    public static function getFunctionIdsFromCallableArg(
        StatementsChecker $statements_checker,
        $callable_arg
    ) {
        if ($callable_arg instanceof PhpParser\Node\Scalar\String_) {
            return [preg_replace('/^\\\/', '', $callable_arg->value)];
        }

        if (count($callable_arg->items) !== 2) {
            return [];
        }

        $class_arg = $callable_arg->items[0]->value;
        $method_name_arg = $callable_arg->items[1]->value;

        if (!$method_name_arg instanceof PhpParser\Node\Scalar\String_) {
            return [];
        }

        if ($class_arg instanceof PhpParser\Node\Scalar\String_) {
            return [preg_replace('/^\\\/', '', $class_arg->value) . '::' . $method_name_arg->value];
        }

        if ($class_arg instanceof PhpParser\Node\Expr\ClassConstFetch
            && is_string($class_arg->name)
            && strtolower($class_arg->name) === 'class'
            && $class_arg->class instanceof PhpParser\Node\Name
        ) {
            $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                $class_arg->class,
                $statements_checker->getAliases()
            );

            return [$fq_class_name . '::' . $method_name_arg->value];
        }

        if (!isset($class_arg->inferredType) || !$class_arg->inferredType->hasObjectType()) {
            return [];
        }

        $method_ids = [];

        foreach ($class_arg->inferredType->getTypes() as $type_part) {
            if ($type_part instanceof TNamedObject) {
                $method_ids[] = $type_part . '::' . $method_name_arg->value;
            }
        }

        return $method_ids;
    }

    /**
     * @param  StatementsChecker    $statements_checker
     * @param  string               $function_id
     * @param  CodeLocation         $code_location
     *
     * @return bool
     */
    protected static function checkFunctionExists(
        StatementsChecker $statements_checker,
        &$function_id,
        CodeLocation $code_location
    ) {
        $cased_function_id = $function_id;
        $function_id = strtolower($function_id);

        if (!FunctionChecker::functionExists($statements_checker, $function_id)) {
            $root_function_id = preg_replace('/.*\\\/', '', $function_id);

            if ($function_id !== $root_function_id &&
                FunctionChecker::functionExists($statements_checker, $root_function_id)
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
