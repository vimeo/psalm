<?php
namespace Psalm\Checker\Statements\Expression;

use PhpParser;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\FunctionLikeChecker;
use Psalm\Checker\MethodChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Codebase\CallMap;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\FunctionLikeParameter;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidPassByReference;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedTypeCoercion;
use Psalm\Issue\NullArgument;
use Psalm\Issue\PossiblyFalseArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\Issue\PossiblyNullArgument;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\TypeCoercion;
use Psalm\Issue\UndefinedFunction;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TNamedObject;

class CallChecker
{
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
        $codebase = $project_checker->codebase;

        if ($context->collect_mutations &&
            $context->self &&
            (
                $context->self === $fq_class_name ||
                $codebase->classExtends(
                    $context->self,
                    $fq_class_name
                )
            )
        ) {
            $method_id = $fq_class_name . '::' . strtolower($method_name);

            if ($method_id !== $source->getMethodId()) {
                if ($context->collect_initializations) {
                    if (isset($context->initialized_methods[$method_id])) {
                        return;
                    }

                    if ($context->initialized_methods === null) {
                        $context->initialized_methods = [];
                    }

                    $context->initialized_methods[$method_id] = true;
                }

                $project_checker->getMethodMutations($method_id, $context);
            }
        } elseif ($context->collect_initializations &&
            $context->self &&
            (
                $context->self === $fq_class_name ||
                $codebase->classlikes->classExtends(
                    $context->self,
                    $fq_class_name
                )
            ) &&
            $source->getMethodName() !== $method_name
        ) {
            $method_id = $fq_class_name . '::' . strtolower($method_name);

            $declaring_method_id = (string) $codebase->methods->getDeclaringMethodId($method_id);

            if (isset($context->initialized_methods[$declaring_method_id])) {
                return;
            }

            if ($context->initialized_methods === null) {
                $context->initialized_methods = [];
            }

            $context->initialized_methods[$declaring_method_id] = true;

            $method_storage = $codebase->methods->getStorage($declaring_method_id);

            $class_checker = $source->getSource();

            if ($class_checker instanceof ClassLikeChecker &&
                ($method_storage->visibility === ClassLikeChecker::VISIBILITY_PRIVATE || $method_storage->final)
            ) {
                $local_vars_in_scope = [];
                $local_vars_possibly_in_scope = [];

                foreach ($context->vars_in_scope as $var => $_) {
                    if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                        $local_vars_in_scope[$var] = $context->vars_in_scope[$var];
                    }
                }

                foreach ($context->vars_possibly_in_scope as $var => $_) {
                    if (strpos($var, '$this->') !== 0 && $var !== '$this') {
                        $local_vars_possibly_in_scope[$var] = $context->vars_possibly_in_scope[$var];
                    }
                }

                $class_checker->getMethodMutations(strtolower($method_name), $context);

                foreach ($local_vars_in_scope as $var => $type) {
                    $context->vars_in_scope[$var] = $type;
                }

                foreach ($local_vars_possibly_in_scope as $var => $_) {
                    $context->vars_possibly_in_scope[$var] = true;
                }
            }
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

        if (self::checkFunctionLikeArgumentsMatch(
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
                        new Type\Union(
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

                if ($by_ref
                    && $by_ref_type
                    && !(
                        $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                        || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                        || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                    )
                ) {
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
                    if (ExpressionChecker::analyze($statements_checker, $arg->value, $context) === false) {
                        return false;
                    }
                }
            } else {
                if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch
                    && $arg->value->name instanceof PhpParser\Node\Identifier
                ) {
                    $var_id = '$' . $arg->value->name->name;
                } else {
                    $var_id = ExpressionChecker::getVarId(
                        $arg->value,
                        $statements_checker->getFQCLN(),
                        $statements_checker
                    );
                }

                if ($var_id) {
                    if (!$context->hasVariable($var_id, $statements_checker)
                        || $context->vars_in_scope[$var_id]->isNull()
                    ) {
                        // we don't know if it exists, assume it's passed by reference
                        $context->vars_in_scope[$var_id] = Type::getMixed();
                        $context->vars_possibly_in_scope[$var_id] = true;

                        if (strpos($var_id, '-') === false
                            && strpos($var_id, '[') === false
                            && !$statements_checker->hasVariable($var_id)
                        ) {
                            $location = new CodeLocation($statements_checker, $arg->value);
                            $statements_checker->registerVariable(
                                $var_id,
                                $location,
                                null
                            );

                            $statements_checker->registerVariableUse($location);
                        }
                    } else {
                        $context->removeVarFromConflictingClauses(
                            $var_id,
                            $context->vars_in_scope[$var_id],
                            $statements_checker
                        );

                        foreach ($context->vars_in_scope[$var_id]->getTypes() as $key => &$type) {
                            if ($type instanceof TArray && $type->type_params[1]->isEmpty()) {
                                $context->vars_in_scope[$var_id]->removeType('array');
                                $context->vars_in_scope[$var_id]->addType(
                                    new TArray(
                                        [Type::getMixed(), Type::getMixed()]
                                    )
                                );
                            }
                        }
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
    protected static function checkFunctionLikeArgumentsMatch(
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
        $in_call_map = $method_id ? CallMap::inCallMap($method_id) : false;

        $cased_method_id = $method_id;

        $is_variadic = false;

        $fq_class_name = null;

        $project_checker = $statements_checker->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        if ($method_id) {
            if ($in_call_map || !strpos($method_id, '::')) {
                $is_variadic = $codebase->functions->isVariadic(
                    $project_checker,
                    strtolower($method_id),
                    $statements_checker->getFilePath()
                );
            } else {
                $fq_class_name = explode('::', $method_id)[0];
                $is_variadic = $codebase->methods->isVariadic($method_id);
            }
        }

        if ($method_id && strpos($method_id, '::') && !$in_call_map) {
            $cased_method_id = $codebase->methods->getCasedMethodId($method_id);
        } elseif ($function_storage) {
            $cased_method_id = $function_storage->cased_name;
        }

        if ($method_id && strpos($method_id, '::')) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id && $declaring_method_id !== $method_id) {
                list($fq_class_name) = explode('::', $declaring_method_id);
                $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);
            }
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
                && $method_id !== 'extract'
            ) {
                if ($arg->value instanceof PhpParser\Node\Scalar
                    || $arg->value instanceof PhpParser\Node\Expr\Array_
                    || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                    || (
                        (
                        $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                            || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                            || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                        ) && (
                            !isset($arg->value->inferredType)
                            || !$arg->value->inferredType->by_ref
                        )
                    )
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
                        $method_id && (strpos($method_id, '::') !== false || !CallMap::inCallMap($method_id))
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

                            if ($arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                                && $arg->value->class instanceof PhpParser\Node\Name
                                && $arg->value->name instanceof PhpParser\Node\Identifier
                                && strtolower($arg->value->name->name) === 'class'
                            ) {
                                $offset_value_type = Type::parseString(
                                    ClassLikeChecker::getFQCLNFromNameObject(
                                        $arg->value->class,
                                        $statements_checker->getAliases()
                                    )
                                );

                                $offset_value_type = ExpressionChecker::fleshOutType(
                                    $project_checker,
                                    $offset_value_type,
                                    $context->self,
                                    $context->self
                                );
                            } elseif ($arg->value instanceof PhpParser\Node\Scalar\String_ && $arg->value->value) {
                                $offset_value_type = Type::parseString($arg->value->value);
                            } elseif ($arg->value instanceof PhpParser\Node\Scalar\MagicConst\Class_
                                && $context->self
                            ) {
                                $offset_value_type = Type::parseString($context->self);
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
                        $fq_class_name
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
            } elseif ($function_param) {
                $codebase->analyzer->incrementMixedCount($statements_checker->getCheckedFilePath());

                if ($function_param->type && !$function_param->type->isMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                . ' cannot be mixed, expecting ' . $function_param->type,
                            new CodeLocation($statements_checker->getSource(), $arg->value)
                        ),
                        $statements_checker->getSuppressedIssues()
                    )) {
                        // fall through
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
                    'Too many arguments for method ' . ($cased_method_id ?: $method_id)
                        . ' - expecting ' . count($function_params) . ' but saw ' . count($args),
                    $code_location
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if (!$has_packed_var && count($args) < count($function_params)) {
            for ($i = count($args), $j = count($function_params); $i < $j; ++$i) {
                $param = $function_params[$i];

                if (!$param->is_optional && !$param->is_variadic) {
                    if (IssueBuffer::accepts(
                        new TooFewArguments(
                            'Too few arguments for method ' . $cased_method_id
                                . ' - expecting ' . count($function_params) . ' but saw ' . count($args),
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
     * @param   StatementsChecker              $statements_checker
     * @param   array<int, PhpParser\Node\Arg> $args
     * @param   string                         $method_id
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

        /** @var null|PhpParser\Node\Arg */
        $closure_arg = isset($args[$closure_index]) ? $args[$closure_index] : null;

        /** @var Type\Union|null */
        $closure_arg_type = $closure_arg && isset($closure_arg->value->inferredType)
                ? $closure_arg->value->inferredType
                : null;

        if ($closure_arg && $closure_arg_type) {
            $min_closure_param_count = $max_closure_param_count = count($array_arg_types);

            if ($method_id === 'array_filter') {
                $max_closure_param_count = count($args) > 2 ? 2 : 1;
            }

            foreach ($closure_arg_type->getTypes() as $closure_type) {
                if (self::checkArrayFunctionClosureType(
                    $statements_checker,
                    $method_id,
                    $closure_type,
                    $closure_arg,
                    $min_closure_param_count,
                    $max_closure_param_count,
                    $array_arg_types
                ) === false) {
                    return false;
                }
            }
        }
    }

    /**
     * @param  string   $method_id
     * @param  int      $min_closure_param_count
     * @param  int      $max_closure_param_count [description]
     * @param  (TArray|null)[] $array_arg_types
     *
     * @return false|null
     */
    private static function checkArrayFunctionClosureType(
        StatementsChecker $statements_checker,
        $method_id,
        Type\Atomic $closure_type,
        PhpParser\Node\Arg $closure_arg,
        $min_closure_param_count,
        $max_closure_param_count,
        array $array_arg_types
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $codebase = $project_checker->codebase;

        if (!$closure_type instanceof Type\Atomic\Fn) {
            if (!$closure_arg->value instanceof PhpParser\Node\Scalar\String_
                && !$closure_arg->value instanceof PhpParser\Node\Expr\Array_
            ) {
                return;
            }

            $function_ids = self::getFunctionIdsFromCallableArg(
                $statements_checker,
                $closure_arg->value
            );

            $closure_types = [];

            foreach ($function_ids as $function_id) {
                $function_id = strtolower($function_id);

                if (strpos($function_id, '::') !== false) {
                    $function_id_parts = explode('&', $function_id);

                    foreach ($function_id_parts as $function_id_part) {
                        list($callable_fq_class_name, $method_name) = explode('::', $function_id_part);

                        switch ($callable_fq_class_name) {
                            case 'self':
                            case 'static':
                            case 'parent':
                                $container_class = $statements_checker->getFQCLN();

                                if ($callable_fq_class_name === 'parent') {
                                    $container_class = $statements_checker->getParentFQCLN();
                                }

                                if (!$container_class) {
                                    continue 2;
                                }

                                $callable_fq_class_name = $container_class;
                        }

                        if (!$codebase->classOrInterfaceExists($callable_fq_class_name)) {
                            return;
                        }

                        $function_id_part = $callable_fq_class_name . '::' . $method_name;

                        try {
                            $method_storage = $codebase->methods->getStorage($function_id_part);
                        } catch (\UnexpectedValueException $e) {
                            // the method may not exist, but we're suppressing that issue
                            continue;
                        }

                        $closure_types[] = new Type\Atomic\Fn(
                            'Closure',
                            $method_storage->params,
                            $method_storage->return_type ?: Type::getMixed()
                        );
                    }
                } else {
                    $function_storage = $codebase->functions->getStorage(
                        $statements_checker,
                        $function_id
                    );

                    if (CallMap::inCallMap($function_id)) {
                        $callmap_params_options = CallMap::getParamsFromCallMap($function_id);

                        if ($callmap_params_options === null) {
                            throw new \UnexpectedValueException('This should not happen');
                        }

                        $passing_callmap_params_options = [];

                        foreach ($callmap_params_options as $callmap_params_option) {
                            $required_param_count = 0;

                            foreach ($callmap_params_option as $i => $param) {
                                if (!$param->is_optional && !$param->is_variadic) {
                                    $required_param_count = $i + 1;
                                }
                            }

                            if ($required_param_count <= $max_closure_param_count) {
                                $passing_callmap_params_options[] = $callmap_params_option;
                            }
                        }

                        if ($passing_callmap_params_options) {
                            foreach ($passing_callmap_params_options as $passing_callmap_params_option) {
                                $closure_types[] = new Type\Atomic\Fn(
                                    'Closure',
                                    $passing_callmap_params_option,
                                    $function_storage->return_type ?: Type::getMixed()
                                );
                            }
                        } else {
                            $closure_types[] = new Type\Atomic\Fn(
                                'Closure',
                                $callmap_params_options[0],
                                $function_storage->return_type ?: Type::getMixed()
                            );
                        }
                    } else {
                        $closure_types[] = new Type\Atomic\Fn(
                            'Closure',
                            $function_storage->params,
                            $function_storage->return_type ?: Type::getMixed()
                        );
                    }
                }
            }
        } else {
            $closure_types = [$closure_type];
        }

        foreach ($closure_types as $closure_type) {
            if ($closure_type->params === null) {
                continue;
            }

            if (self::checkArrayFunctionClosureTypeArgs(
                $statements_checker,
                $method_id,
                $closure_type,
                $closure_arg,
                $min_closure_param_count,
                $max_closure_param_count,
                $array_arg_types
            ) === false) {
                return false;
            }
        }
    }

    /**
     * @param  string   $method_id
     * @param  int      $min_closure_param_count
     * @param  int      $max_closure_param_count [description]
     * @param  (TArray|null)[] $array_arg_types
     *
     * @return false|null
     */
    private static function checkArrayFunctionClosureTypeArgs(
        StatementsChecker $statements_checker,
        $method_id,
        Type\Atomic\Fn $closure_type,
        PhpParser\Node\Arg $closure_arg,
        $min_closure_param_count,
        $max_closure_param_count,
        array $array_arg_types
    ) {
        $project_checker = $statements_checker->getFileChecker()->project_checker;

        $closure_params = $closure_type->params;

        if ($closure_params === null) {
            throw new \UnexpectedValueException('Closure params should not be null here');
        }

        $required_param_count = 0;

        foreach ($closure_params as $i => $param) {
            if (!$param->is_optional && !$param->is_variadic) {
                $required_param_count = $i + 1;
            }
        }

        if (count($closure_params) < $min_closure_param_count) {
            $argument_text = $min_closure_param_count === 1 ? 'one argument' : $min_closure_param_count . ' arguments';

            if (IssueBuffer::accepts(
                new TooManyArguments(
                    'The callable passed to ' . $method_id . ' will be called with ' . $argument_text . ', expecting '
                        . $required_param_count,
                    new CodeLocation($statements_checker->getSource(), $closure_arg)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($required_param_count > $max_closure_param_count) {
            $argument_text = $max_closure_param_count === 1 ? 'one argument' : $max_closure_param_count . ' arguments';

            if (IssueBuffer::accepts(
                new TooFewArguments(
                    'The callable passed to ' . $method_id . ' will be called with ' . $argument_text . ', expecting '
                        . $required_param_count,
                    new CodeLocation($statements_checker->getSource(), $closure_arg)
                ),
                $statements_checker->getSuppressedIssues()
            )) {
                return false;
            }
        }

        // abandon attempt to validate closure params if we have an extra arg for ARRAY_FILTER
        if ($method_id === 'array_filter' && $max_closure_param_count > 1) {
            return;
        }

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
                $project_checker->codebase,
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
                    $project_checker->codebase,
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
        $codebase = $project_checker->codebase;

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
            $codebase->analyzer->incrementMixedCount($statements_checker->getCheckedFilePath());

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

        $codebase->analyzer->incrementNonMixedCount($statements_checker->getCheckedFilePath());

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

        if ($input_type->isFalsable() && !$param_type->hasBool() && !$input_type->ignore_falsable_issues) {
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
            $project_checker->codebase,
            $param_type
        );

        $type_match_found = TypeChecker::isContainedBy(
            $codebase,
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
                $codebase,
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
                if ($param_type_part instanceof TClassString
                    && $input_expr instanceof PhpParser\Node\Scalar\String_
                ) {
                    if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                        $statements_checker,
                        $input_expr->value,
                        $code_location,
                        $statements_checker->getSuppressedIssues()
                    ) === false
                    ) {
                        return false;
                    }
                } elseif ($param_type_part instanceof TArray
                    && isset($param_type_part->type_params[1]->getTypes()['class-string'])
                    && $input_expr instanceof PhpParser\Node\Expr\Array_
                ) {
                    foreach ($input_expr->items as $item) {
                        if ($item && $item->value instanceof PhpParser\Node\Scalar\String_) {
                            if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                                $statements_checker,
                                $item->value->value,
                                $code_location,
                                $statements_checker->getSuppressedIssues()
                            ) === false
                            ) {
                                return false;
                            }
                        }
                    }
                } elseif ($param_type_part instanceof TCallable) {
                    $function_ids = self::getFunctionIdsFromCallableArg(
                        $statements_checker,
                        $input_expr
                    );

                    foreach ($function_ids as $function_id) {
                        if (strpos($function_id, '::') !== false) {
                            $function_id_parts = explode('&', $function_id);

                            $non_existent_method_ids = [];
                            $has_valid_method = false;

                            foreach ($function_id_parts as $function_id_part) {
                                list($callable_fq_class_name, $method_name) = explode('::', $function_id_part);

                                switch ($callable_fq_class_name) {
                                    case 'self':
                                    case 'static':
                                    case 'parent':
                                        $container_class = $statements_checker->getFQCLN();

                                        if ($callable_fq_class_name === 'parent') {
                                            $container_class = $statements_checker->getParentFQCLN();
                                        }

                                        if (!$container_class) {
                                            continue 2;
                                        }

                                        $callable_fq_class_name = $container_class;
                                }

                                $function_id_part = $callable_fq_class_name . '::' . $method_name;

                                if (ClassLikeChecker::checkFullyQualifiedClassLikeName(
                                    $statements_checker,
                                    $callable_fq_class_name,
                                    $code_location,
                                    $statements_checker->getSuppressedIssues()
                                ) === false
                                ) {
                                    return false;
                                }

                                if (!$codebase->classOrInterfaceExists($callable_fq_class_name)) {
                                    return;
                                }

                                if (!$codebase->methodExists($function_id_part)
                                    && !$codebase->methodExists($callable_fq_class_name . '::__call')
                                ) {
                                    $non_existent_method_ids[] = $function_id_part;
                                } else {
                                    $has_valid_method = true;
                                }
                            }

                            if (!$has_valid_method && !$param_type->hasString() && !$param_type->hasArray()) {
                                if (MethodChecker::checkMethodExists(
                                    $project_checker,
                                    $non_existent_method_ids[0],
                                    $code_location,
                                    $statements_checker->getSuppressedIssues()
                                ) === false
                                ) {
                                    return false;
                                }
                            }
                        } else {
                            if (!$param_type->hasString() && !$param_type->hasArray() && self::checkFunctionExists(
                                $statements_checker,
                                $function_id,
                                $code_location,
                                false
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

                    foreach ($input_type->getTypes() as $atomic_type) {
                        $atomic_type->from_docblock = false;
                    }
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
        \Psalm\FileSource $file_source,
        $callable_arg
    ) {
        if ($callable_arg instanceof PhpParser\Node\Scalar\String_) {
            return [preg_replace('/^\\\/', '', $callable_arg->value)];
        }

        if (count($callable_arg->items) !== 2) {
            return [];
        }

        if (!isset($callable_arg->items[0]) || !isset($callable_arg->items[1])) {
            throw new \UnexpectedValueException('These should never be unset');
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
            && $class_arg->name instanceof PhpParser\Node\Identifier
            && strtolower($class_arg->name->name) === 'class'
            && $class_arg->class instanceof PhpParser\Node\Name
        ) {
            $fq_class_name = ClassLikeChecker::getFQCLNFromNameObject(
                $class_arg->class,
                $file_source->getAliases()
            );

            return [$fq_class_name . '::' . $method_name_arg->value];
        }

        if (!isset($class_arg->inferredType) || !$class_arg->inferredType->hasObjectType()) {
            return [];
        }

        $method_ids = [];

        foreach ($class_arg->inferredType->getTypes() as $type_part) {
            if ($type_part instanceof TNamedObject) {
                $method_id = $type_part->value . '::' . $method_name_arg->value;

                if ($type_part->extra_types) {
                    foreach ($type_part->extra_types as $extra_type) {
                        $method_id .= '&' . $extra_type->value . '::' . $method_name_arg->value;
                    }
                }

                $method_ids[] = $method_id;
            }
        }

        return $method_ids;
    }

    /**
     * @param  StatementsChecker    $statements_checker
     * @param  string               $function_id
     * @param  CodeLocation         $code_location
     * @param  bool                 $can_be_in_root_scope if true, the function can be shortened to the root version
     *
     * @return bool
     */
    protected static function checkFunctionExists(
        StatementsChecker $statements_checker,
        &$function_id,
        CodeLocation $code_location,
        $can_be_in_root_scope
    ) {
        $cased_function_id = $function_id;
        $function_id = strtolower($function_id);

        $codebase = $statements_checker->getFileChecker()->project_checker->codebase;

        if (!$codebase->functions->functionExists($statements_checker, $function_id)) {
            $root_function_id = preg_replace('/.*\\\/', '', $function_id);

            if ($can_be_in_root_scope
                && $function_id !== $root_function_id
                && $codebase->functions->functionExists($statements_checker, $root_function_id)
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

    /**
     * @param  StatementsChecker    $statements_checker
     * @param  string               $function_id
     * @param  bool                 $can_be_in_root_scope if true, the function can be shortened to the root version
     *
     * @return string
     */
    protected static function getExistingFunctionId(
        StatementsChecker $statements_checker,
        $function_id,
        $can_be_in_root_scope
    ) {
        $function_id = strtolower($function_id);

        $codebase = $statements_checker->getFileChecker()->project_checker->codebase;

        if ($codebase->functions->functionExists($statements_checker, $function_id)) {
            return $function_id;
        }

        if (!$can_be_in_root_scope) {
            return $function_id;
        }

        $root_function_id = preg_replace('/.*\\\/', '', $function_id);

        if ($function_id !== $root_function_id
            && $codebase->functions->functionExists($statements_checker, $root_function_id)
        ) {
            return $root_function_id;
        }

        return $function_id;
    }

    /**
     * @param  \Psalm\Storage\Assertion[] $assertions
     * @param  array<int, PhpParser\Node\Arg> $args
     * @param  Context           $context
     * @param  StatementsChecker $statements_checker
     *
     * @return void
     */
    protected static function applyAssertionsToContext(
        array $assertions,
        array $args,
        Context $context,
        StatementsChecker $statements_checker
    ) {
        $type_assertions = [];

        foreach ($assertions as $assertion) {
            if (is_int($assertion->var_id)) {
                if (!isset($args[$assertion->var_id])) {
                    continue;
                }

                $arg_value = $args[$assertion->var_id]->value;

                $arg_var_id = ExpressionChecker::getArrayVarId($arg_value, null, $statements_checker);

                if ($arg_var_id) {
                    $type_assertions[$arg_var_id] = $assertion->rule;
                }
            } else {
                $type_assertions[$assertion->var_id] = $assertion->rule;
            }
        }

        $changed_vars = [];

        // while in an and, we allow scope to boil over to support
        // statements of the form if ($x && $x->foo())
        $op_vars_in_scope = \Psalm\Type\Reconciler::reconcileKeyedTypes(
            $type_assertions,
            $context->vars_in_scope,
            $changed_vars,
            [],
            $statements_checker,
            null
        );

        foreach ($changed_vars as $changed_var) {
            if (isset($op_vars_in_scope[$changed_var])) {
                $op_vars_in_scope[$changed_var]->from_docblock = true;
            }
        }

        $context->vars_in_scope = $op_vars_in_scope;
    }
}
