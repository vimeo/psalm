<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Internal\Type\TypeCombination;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidPassByReference;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedTypeCoercion;
use Psalm\Issue\NoValue;
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
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Storage\FunctionLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TEmpty;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;

/**
 * @internal
 */
class CallAnalyzer
{
    /**
     * @param   FunctionLikeAnalyzer $source
     * @param   string              $method_name
     * @param   Context             $context
     *
     * @return  void
     */
    public static function collectSpecialInformation(
        FunctionLikeAnalyzer $source,
        $method_name,
        Context $context
    ) {
        $fq_class_name = (string)$source->getFQCLN();

        $project_analyzer = $source->getFileAnalyzer()->project_analyzer;
        $codebase = $source->getCodebase();

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

                $project_analyzer->getMethodMutations($method_id, $context);
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

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if (!$declaring_method_id) {
                if (isset($context->vars_in_scope['$this'])) {
                    foreach ($context->vars_in_scope['$this']->getTypes() as $atomic_type) {
                        if ($atomic_type instanceof TNamedObject) {
                            $fq_class_name = $atomic_type->value;
                            $method_id = $fq_class_name . '::' . strtolower($method_name);

                            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                            if ($declaring_method_id) {
                                break;
                            }

                            if (!$atomic_type->extra_types) {
                                continue;
                            }

                            foreach ($atomic_type->extra_types as $intersection_type) {
                                if ($intersection_type instanceof TNamedObject) {
                                    $fq_class_name = $intersection_type->value;
                                    $method_id = $fq_class_name . '::' . strtolower($method_name);

                                    $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                                    if ($declaring_method_id) {
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }

                if (!$declaring_method_id) {
                    throw new \UnexpectedValueException('Could not find declaring method for ' . $method_id);
                }
            }

            if (isset($context->initialized_methods[$declaring_method_id])) {
                return;
            }

            if ($context->initialized_methods === null) {
                $context->initialized_methods = [];
            }

            $context->initialized_methods[$declaring_method_id] = true;

            $method_storage = $codebase->methods->getStorage($declaring_method_id);

            $class_analyzer = $source->getSource();

            if ($class_analyzer instanceof ClassLikeAnalyzer &&
                ($method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE || $method_storage->final)
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

                $class_analyzer->getMethodMutations(strtolower($method_name), $context);

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
     * @param  array<string, array{Type\Union, ?string, ?int}>|null   &$generic_params
     * @param  Context                          $context
     * @param  CodeLocation                     $code_location
     * @param  StatementsAnalyzer                $statements_analyzer
     *
     * @return false|null
     */
    protected static function checkMethodArgs(
        $method_id,
        array $args,
        &$generic_params,
        Context $context,
        CodeLocation $code_location,
        StatementsAnalyzer $statements_analyzer
    ) {
        $codebase = $statements_analyzer->getCodebase();

        $method_params = $method_id
            ? FunctionLikeAnalyzer::getMethodParamsById($codebase, $method_id, $args)
            : null;

        if (self::checkFunctionArguments(
            $statements_analyzer,
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

        $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $method_storage = null;

        if (isset($class_storage->declaring_method_ids[strtolower($method_name)])) {
            $declaring_method_id = $class_storage->declaring_method_ids[strtolower($method_name)];

            list($declaring_fq_class_name, $declaring_method_name) = explode('::', $declaring_method_id);

            if ($declaring_fq_class_name !== $fq_class_name) {
                $declaring_class_storage = $codebase->classlike_storage_provider->get($declaring_fq_class_name);
            } else {
                $declaring_class_storage = $class_storage;
            }

            if (!isset($declaring_class_storage->methods[strtolower($declaring_method_name)])) {
                throw new \UnexpectedValueException('Storage should not be empty here');
            }

            $method_storage = $declaring_class_storage->methods[strtolower($declaring_method_name)];

            if ($context->collect_exceptions) {
                $context->possibly_thrown_exceptions += $method_storage->throws;
            }
        }

        if (!$class_storage->user_defined) {
            // check again after we've processed args
            $method_params = FunctionLikeAnalyzer::getMethodParamsById(
                $codebase,
                $method_id,
                $args
            );
        }

        if (self::checkFunctionLikeArgumentsMatch(
            $statements_analyzer,
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
     * @param   StatementsAnalyzer                       $statements_analyzer
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   array<int, FunctionLikeParameter>|null  $function_params
     * @param   string|null                             $method_id
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    protected static function checkFunctionArguments(
        StatementsAnalyzer $statements_analyzer,
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
            if (self::handleArrayAddition($statements_analyzer, $args, $context) === false) {
                return false;
            }

            return;
        }

        if ($method_id && $method_id === 'array_splice' && $function_params && count($args) > 1) {
            if (self::handleArraySplice($statements_analyzer, $args, $context) === false) {
                return false;
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
                    && !($arg->value instanceof PhpParser\Node\Expr\Closure
                        || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                        || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                        || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                        || $arg->value instanceof PhpParser\Node\Expr\Assign
                    )
                ) {
                    if (self::handleByRefFunctionArg(
                        $statements_analyzer,
                        $method_id,
                        $argument_offset,
                        $arg,
                        $context
                    ) === false) {
                        return false;
                    }

                    continue;
                }

                $toggled_class_exists = false;

                if ($method_id === 'class_exists'
                    && $argument_offset === 0
                    && !$context->inside_class_exists
                ) {
                    $context->inside_class_exists = true;
                    $toggled_class_exists = true;
                }

                if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                    return false;
                }

                if ($context->collect_references
                    && ($arg->value instanceof PhpParser\Node\Expr\AssignOp
                        || $arg->value instanceof PhpParser\Node\Expr\PreInc
                        || $arg->value instanceof PhpParser\Node\Expr\PreDec)
                ) {
                    $var_id = ExpressionAnalyzer::getVarId(
                        $arg->value->var,
                        $statements_analyzer->getFQCLN(),
                        $statements_analyzer
                    );

                    if ($var_id) {
                        $context->hasVariable($var_id, $statements_analyzer);
                    }
                }

                if ($toggled_class_exists) {
                    $context->inside_class_exists = false;
                }

                continue;
            }

            if (self::evaluateAribitraryParam(
                $statements_analyzer,
                $arg,
                $context
            ) === false) {
                return false;
            }
        }
    }

    /**
     * @return false|null
     */
    private static function evaluateAribitraryParam(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Arg $arg,
        Context $context
    ) {
        // there are a bunch of things we want to evaluate even when we don't
        // know what function/method is being called
        if ($arg->value instanceof PhpParser\Node\Expr\Closure
            || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\FuncCall
            || $arg->value instanceof PhpParser\Node\Expr\MethodCall
            || $arg->value instanceof PhpParser\Node\Expr\Assign
        ) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                return false;
            }
        }

        if ($arg->value instanceof PhpParser\Node\Expr\PropertyFetch
            && $arg->value->name instanceof PhpParser\Node\Identifier
        ) {
            $var_id = '$' . $arg->value->name->name;
        } else {
            $var_id = ExpressionAnalyzer::getVarId(
                $arg->value,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );
        }

        if ($var_id) {
            if (!$context->hasVariable($var_id, $statements_analyzer)
                || $context->vars_in_scope[$var_id]->isNull()
            ) {
                // we don't know if it exists, assume it's passed by reference
                $context->vars_in_scope[$var_id] = Type::getMixed();
                $context->vars_possibly_in_scope[$var_id] = true;

                if (strpos($var_id, '-') === false
                    && strpos($var_id, '[') === false
                    && !$statements_analyzer->hasVariable($var_id)
                ) {
                    $location = new CodeLocation($statements_analyzer, $arg->value);
                    $statements_analyzer->registerVariable(
                        $var_id,
                        $location,
                        null
                    );

                    $statements_analyzer->registerVariableUses([$location->getHash() => $location]);
                }
            } else {
                $context->removeVarFromConflictingClauses(
                    $var_id,
                    $context->vars_in_scope[$var_id],
                    $statements_analyzer
                );

                foreach ($context->vars_in_scope[$var_id]->getTypes() as $type) {
                    if ($type instanceof TArray && $type->type_params[1]->isEmpty()) {
                        $context->vars_in_scope[$var_id]->removeType('array');
                        $context->vars_in_scope[$var_id]->addType(
                            new TArray(
                                [Type::getArrayKey(), Type::getMixed()]
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * @param string|null $method_id
     * @return false|null
     */
    private static function handleByRefFunctionArg(
        StatementsAnalyzer $statements_analyzer,
        $method_id,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context
    ) {
        $var_id = ExpressionAnalyzer::getVarId(
            $arg->value,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $builtin_array_functions = [
            'shuffle', 'sort', 'rsort', 'usort', 'ksort', 'asort',
            'krsort', 'arsort', 'natcasesort', 'natsort', 'reset',
            'end', 'next', 'prev', 'array_pop', 'array_shift',
        ];

        if (($var_id && isset($context->vars_in_scope[$var_id]))
            || ($method_id
                && in_array(
                    $method_id,
                    $builtin_array_functions,
                    true
                ))
        ) {
            // if the variable is in scope, get or we're in a special array function,
            // figure out its type before proceeding
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context
            ) === false) {
                return false;
            }
        }

        // special handling for array sort
        if ($argument_offset === 0
            && $method_id
            && in_array(
                $method_id,
                $builtin_array_functions,
                true
            )
        ) {
            if (in_array($method_id, ['array_pop', 'array_shift'], true)) {
                self::handleByRefArrayAdjustment($statements_analyzer, $arg, $context);

                return;
            }

            // noops
            if (in_array($method_id, ['reset', 'end', 'next', 'prev', 'ksort'], true)) {
                return;
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

                ExpressionAnalyzer::assignByRefParam(
                    $statements_analyzer,
                    $arg->value,
                    $by_ref_type,
                    $context,
                    false
                );

                return;
            }
        }

        if ($method_id === 'socket_select') {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context
            ) === false) {
                return false;
            }
        }
    }

    /**
     * @param   StatementsAnalyzer                      $statements_analyzer
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    private static function handleArrayAddition(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        Context $context
    ) {
        $array_arg = $args[0]->value;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
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

                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $arg->value,
                    $context
                ) === false) {
                    return false;
                }

                if (!isset($arg->value->inferredType) || $arg->value->inferredType->hasMixed()) {
                    $by_ref_type = Type::combineUnionTypes(
                        $by_ref_type,
                        new Type\Union([new TArray([Type::getInt(), Type::getMixed()])])
                    );
                } elseif ($arg->unpack) {
                    if ($arg->value->inferredType->hasArray()) {
                        /** @var Type\Atomic\TArray|Type\Atomic\ObjectLike */
                        $array_atomic_type = $arg->value->inferredType->getTypes()['array'];

                        if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                            $array_atomic_type = $array_atomic_type->getGenericArrayType();
                        }

                        $by_ref_type = Type::combineUnionTypes(
                            $by_ref_type,
                            new Type\Union(
                                [
                                    new TArray(
                                        [
                                            Type::getInt(),
                                            clone $array_atomic_type->type_params[1]
                                        ]
                                    ),
                                ]
                            )
                        );
                    }
                } else {
                    $by_ref_type = Type::combineUnionTypes(
                        $by_ref_type,
                        new Type\Union(
                            [
                                new TArray(
                                    [
                                        Type::getInt(),
                                        clone $arg->value->inferredType
                                    ]
                                ),
                            ]
                        )
                    );
                }
            }

            ExpressionAnalyzer::assignByRefParam(
                $statements_analyzer,
                $array_arg,
                $by_ref_type,
                $context,
                false
            );
        }

        return;
    }

    /**
     * @param   StatementsAnalyzer                      $statements_analyzer
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    private static function handleArraySplice(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        Context $context
    ) {
        $array_arg = $args[0]->value;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $array_arg,
            $context
        ) === false) {
            return false;
        }

        $offset_arg = $args[1]->value;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $offset_arg,
            $context
        ) === false) {
            return false;
        }

        if (!isset($args[2])) {
            return;
        }

        $length_arg = $args[2]->value;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $length_arg,
            $context
        ) === false) {
            return false;
        }

        if (!isset($args[3])) {
            return;
        }

        $replacement_arg = $args[3]->value;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $replacement_arg,
            $context
        ) === false) {
            return false;
        }

        if (isset($replacement_arg->inferredType)
            && !$replacement_arg->inferredType->hasArray()
            && $replacement_arg->inferredType->hasString()
            && $replacement_arg->inferredType->isSingle()
        ) {
            $replacement_arg->inferredType = new Type\Union([
                new Type\Atomic\TArray([Type::getInt(), $replacement_arg->inferredType])
            ]);
        }

        if (isset($array_arg->inferredType)
            && $array_arg->inferredType->hasArray()
            && isset($replacement_arg->inferredType)
            && $replacement_arg->inferredType->hasArray()
        ) {
            /** @var TArray|ObjectLike */
            $array_type = $array_arg->inferredType->getTypes()['array'];

            if ($array_type instanceof ObjectLike) {
                $array_type = $array_type->getGenericArrayType();
            }

            /** @var TArray|ObjectLike */
            $replacement_array_type = $replacement_arg->inferredType->getTypes()['array'];

            if ($replacement_array_type instanceof ObjectLike) {
                $replacement_array_type = $replacement_array_type->getGenericArrayType();
            }

            $by_ref_type = TypeCombination::combineTypes([$array_type, $replacement_array_type]);

            ExpressionAnalyzer::assignByRefParam(
                $statements_analyzer,
                $array_arg,
                $by_ref_type,
                $context,
                false
            );

            return;
        }

        ExpressionAnalyzer::assignByRefParam(
            $statements_analyzer,
            $array_arg,
            Type::getArray(),
            $context,
            false
        );
    }

    /**
     * @return void
     */
    private static function handleByRefArrayAdjustment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Arg $arg,
        Context $context
    ) {
        $var_id = ExpressionAnalyzer::getVarId(
            $arg->value,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id) {
            $context->removeVarFromConflictingClauses($var_id, null, $statements_analyzer);

            if (isset($context->vars_in_scope[$var_id])) {
                $array_type = clone $context->vars_in_scope[$var_id];

                $array_atomic_types = $array_type->getTypes();

                foreach ($array_atomic_types as $array_atomic_type) {
                    if ($array_atomic_type instanceof ObjectLike) {
                        $generic_array_type = $array_atomic_type->getGenericArrayType();

                        if ($generic_array_type instanceof TNonEmptyArray) {
                            if (!$context->inside_loop && $generic_array_type->count !== null) {
                                if ($generic_array_type->count === 0) {
                                    $generic_array_type = new TArray(
                                        [
                                            new Type\Union([new TEmpty]),
                                            new Type\Union([new TEmpty]),
                                        ]
                                    );
                                } else {
                                    $generic_array_type->count--;
                                }
                            } else {
                                $generic_array_type = new TArray($generic_array_type->type_params);
                            }
                        }

                        $array_type->addType($generic_array_type);
                    } elseif ($array_atomic_type instanceof TNonEmptyArray) {
                        if (!$context->inside_loop && $array_atomic_type->count !== null) {
                            if ($array_atomic_type->count === 0) {
                                $array_atomic_type = new TArray(
                                    [
                                        new Type\Union([new TEmpty]),
                                        new Type\Union([new TEmpty]),
                                    ]
                                );
                            } else {
                                $array_atomic_type->count--;
                            }
                        } else {
                            $array_atomic_type = new TArray($array_atomic_type->type_params);
                        }

                        $array_type->addType($array_atomic_type);
                    }
                }

                $context->vars_in_scope[$var_id] = $array_type;
            }
        }
    }

    /**
     * @param   StatementsAnalyzer                       $statements_analyzer
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   string|null                             $method_id
     * @param   array<int,FunctionLikeParameter>        $function_params
     * @param   FunctionLikeStorage|null                $function_storage
     * @param   ClassLikeStorage|null                   $class_storage
     * @param   array<string, array{Type\Union, ?string, ?int}>|null  $generic_params
     * @param   CodeLocation                            $code_location
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    protected static function checkFunctionLikeArgumentsMatch(
        StatementsAnalyzer $statements_analyzer,
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

        $codebase = $statements_analyzer->getCodebase();

        if ($method_id) {
            if ($in_call_map || !strpos($method_id, '::')) {
                $is_variadic = $codebase->functions->isVariadic(
                    $codebase,
                    strtolower($method_id),
                    $statements_analyzer->getRootFilePath()
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

        $calling_class_storage = $class_storage;

        if ($method_id && strpos($method_id, '::')) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id && $declaring_method_id !== $method_id) {
                list($fq_class_name) = explode('::', $declaring_method_id);
                $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);
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
            $template_types = self::getTemplateTypesForFunction(
                $function_storage,
                $class_storage,
                $calling_class_storage
            );

            if ($template_types) {
                foreach ($args as $argument_offset => $arg) {
                    $function_param = count($function_params) > $argument_offset
                        ? $function_params[$argument_offset]
                        : ($last_param && $last_param->is_variadic ? $last_param : null);

                    if (!$function_param
                        || !$function_param->type
                        || !isset($arg->value->inferredType)
                    ) {
                        continue;
                    }

                    $empty_generic_params = [];

                    $function_param->type->replaceTemplateTypesWithStandins(
                        $template_types,
                        $empty_generic_params,
                        $codebase,
                        $arg->value->inferredType,
                        false
                    );
                }
            }
        }

        $existing_generic_params = $generic_params ?: [];

        foreach ($existing_generic_params as $key => $type) {
            $existing_generic_params[$key][0] = clone $type[0];
        }

        $function_param_count = count($function_params);

        foreach ($args as $argument_offset => $arg) {
            $function_param = $function_param_count > $argument_offset
                ? $function_params[$argument_offset]
                : ($last_param && $last_param->is_variadic ? $last_param : null);

            if ($function_param
                && $function_param->by_ref
                && $method_id !== 'extract'
            ) {
                if (self::handlePossiblyMatchingByRefParam(
                    $statements_analyzer,
                    $codebase,
                    $method_id,
                    $cased_method_id,
                    $last_param,
                    $function_params,
                    $function_storage,
                    $argument_offset,
                    $arg,
                    $context,
                    $generic_params,
                    $template_types
                ) === false) {
                    return false;
                }
            }

            if (self::checkFunctionLikeArgumentMatches(
                $statements_analyzer,
                $cased_method_id,
                $fq_class_name,
                $function_param,
                $argument_offset,
                $arg,
                $context,
                $existing_generic_params,
                $generic_params,
                $template_types
            ) === false) {
                return false;
            }
        }

        if ($method_id === 'array_map' || $method_id === 'array_filter') {
            if ($method_id === 'array_map' && count($args) < 2) {
                if (IssueBuffer::accepts(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location,
                        $method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            } elseif ($method_id === 'array_filter' && count($args) < 1) {
                if (IssueBuffer::accepts(
                    new TooFewArguments(
                        'Too few arguments for ' . $method_id,
                        $code_location,
                        $method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            if (self::checkArrayFunctionArgumentsMatch(
                $statements_analyzer,
                $args,
                $method_id,
                $context->check_functions
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
                    $code_location,
                    $method_id ?: ''
                ),
                $statements_analyzer->getSuppressedIssues()
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
                            $code_location,
                            $method_id ?: ''
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    break;
                }
            }
        }
    }

    /**
     * @param  string|null $cased_method_id
     * @param  string|null $fq_class_name
     * @param  FunctionLikeParameter|null $function_param
     * @param  FunctionLikeParameter|null $last_param
     * @param  array<string, array{Type\Union, ?string}> $existing_generic_params
     * @param  array<string, array{Type\Union, ?string, ?int}> $generic_params
     * @param  array<string, array{Type\Union, ?string}> $template_types
     * @return false|null
     */
    private static function checkFunctionLikeArgumentMatches(
        StatementsAnalyzer $statements_analyzer,
        $cased_method_id,
        $fq_class_name,
        $function_param,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        array $existing_generic_params,
        array &$generic_params = null,
        array $template_types = null
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if (!isset($arg->value->inferredType)) {
            if ($function_param) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());

                $param_type = $function_param->type;

                if ($function_param->is_variadic
                    && $param_type
                    && $param_type->hasArray()
                ) {
                    /** @var TArray */
                    $array_type = $param_type->getTypes()['array'];

                    $param_type = $array_type->type_params[1];
                }

                if ($param_type && !$param_type->hasMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                . ' cannot be mixed, expecting ' . $param_type,
                            new CodeLocation($statements_analyzer->getSource(), $arg->value)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            return;
        }

        if (self::checkFunctionLikeTypeMatches(
            $statements_analyzer,
            $codebase,
            $cased_method_id,
            $fq_class_name,
            $function_param,
            $arg->value->inferredType,
            $argument_offset,
            $arg,
            $context,
            $existing_generic_params,
            $generic_params,
            $template_types
        ) === false) {
            return false;
        }
    }

    /**
     * @param  string|null $method_id
     * @param  string|null $cased_method_id
     * @param  FunctionLikeParameter|null $function_param
     * @param  FunctionLikeParameter|null $last_param
     * @param  array<int, FunctionLikeParameter> $function_params
     * @param  array<string, array{Type\Union, ?string, ?int}> $generic_params
     * @param  array<string, array{Type\Union, ?string}> $template_types
     * @param  FunctionLikeStorage|null $function_storage
     * @return false|null
     */
    private static function handlePossiblyMatchingByRefParam(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        $method_id,
        $cased_method_id,
        $last_param,
        $function_params,
        $function_storage,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        array &$generic_params = null,
        array $template_types = null
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
                    'Parameter ' . ($argument_offset + 1) . ' of ' . $cased_method_id . ' expects a variable',
                    new CodeLocation($statements_analyzer->getSource(), $arg->value)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return;
        }

        if (!in_array(
            $method_id,
            [
                'shuffle', 'sort', 'rsort', 'usort', 'ksort', 'asort',
                'krsort', 'arsort', 'natcasesort', 'natsort', 'reset',
                'end', 'next', 'prev', 'array_pop', 'array_shift',
                'array_push', 'array_unshift', 'socket_select', 'array_splice',
            ],
            true
        )) {
            $by_ref_type = null;

            if ($last_param) {
                if ($argument_offset < count($function_params)) {
                    $by_ref_type = $function_params[$argument_offset]->type;
                    if (isset($function_storage->param_out_types[$argument_offset])) {
                        $by_ref_type = $function_storage->param_out_types[$argument_offset];
                    }
                } else {
                    $by_ref_type = $last_param->type;
                }

                if ($template_types && $by_ref_type) {
                    if ($generic_params === null) {
                        $generic_params = [];
                    }

                    $original_by_ref_type = $by_ref_type;

                    $by_ref_type = clone $by_ref_type;

                    $by_ref_type->replaceTemplateTypesWithStandins(
                        $template_types,
                        $generic_params,
                        $codebase,
                        isset($arg->value->inferredType)
                            ? $arg->value->inferredType
                            : null
                    );

                    if ($generic_params) {
                        $original_by_ref_type->replaceTemplateTypesWithArgTypes(
                            $generic_params
                        );

                        $by_ref_type = $original_by_ref_type;
                    }
                }
            }

            $by_ref_type = $by_ref_type ?: Type::getMixed();

            ExpressionAnalyzer::assignByRefParam(
                $statements_analyzer,
                $arg->value,
                $by_ref_type,
                $context,
                $method_id && (strpos($method_id, '::') !== false || !CallMap::inCallMap($method_id))
            );
        }
    }

    /**
     * @param  string|null $cased_method_id
     * @param  string|null $fq_class_name
     * @param  array<string, array{Type\Union, ?string}> $existing_generic_params
     * @param  array<string, array{Type\Union, ?string, ?int}> $generic_params
     * @param  array<string, array{Type\Union, ?string}> $template_types
     * @param  FunctionLikeParameter|null $function_param
     * @return false|null
     */
    private static function checkFunctionLikeTypeMatches(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        $cased_method_id,
        $fq_class_name,
        $function_param,
        Type\Union $arg_type,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        array $existing_generic_params = null,
        array &$generic_params = null,
        array $template_types = null
    ) {
        if ($function_param && $function_param->type) {
            $param_type = clone $function_param->type;

            if ($function_param->is_variadic) {
                if (!$param_type->hasArray()) {
                    return;
                }

                $array_atomic_type = $param_type->getTypes()['array'];

                if (!$array_atomic_type instanceof TArray) {
                    return;
                }

                $param_type = clone $array_atomic_type->type_params[1];
            }

            if ($existing_generic_params) {
                $empty_generic_params = [];

                $param_type->replaceTemplateTypesWithStandins(
                    $existing_generic_params,
                    $empty_generic_params,
                    $codebase,
                    $arg->value->inferredType
                );
            }

            if ($template_types) {
                if ($generic_params === null) {
                    $generic_params = [];
                }

                $arg_type_param = $arg_type;

                if ($arg->unpack) {
                    if ($arg_type->hasArray()) {
                        /** @var Type\Atomic\TArray|Type\Atomic\ObjectLike */
                        $array_atomic_type = $arg_type->getTypes()['array'];

                        if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                            $array_atomic_type = $array_atomic_type->getGenericArrayType();
                        }

                        $arg_type_param = $array_atomic_type->type_params[1];
                    } else {
                        $arg_type_param = Type::getMixed();
                    }
                }

                $param_type->replaceTemplateTypesWithStandins(
                    $template_types,
                    $generic_params,
                    $codebase,
                    $arg_type_param
                );
            }

            if (!$context->check_variables) {
                return;
            }

            $fleshed_out_type = ExpressionAnalyzer::fleshOutType(
                $codebase,
                $param_type,
                $fq_class_name ?: $context->self,
                $fq_class_name ?: $context->self
            );

            if ($arg->unpack) {
                if ($arg_type->hasArray()) {
                    /** @var Type\Atomic\TArray|Type\Atomic\ObjectLike */
                    $array_atomic_type = $arg_type->getTypes()['array'];

                    if ($array_atomic_type instanceof Type\Atomic\ObjectLike) {
                        $array_atomic_type = $array_atomic_type->getGenericArrayType();
                    }

                    if (self::checkFunctionArgumentType(
                        $statements_analyzer,
                        $array_atomic_type->type_params[1],
                        $fleshed_out_type,
                        $cased_method_id,
                        $argument_offset,
                        new CodeLocation($statements_analyzer->getSource(), $arg->value),
                        $arg->value,
                        $context,
                        $function_param->by_ref,
                        true
                    ) === false) {
                        return false;
                    }

                    return;
                }

                if ($arg_type->hasMixed()) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());

                    if (IssueBuffer::accepts(
                        new MixedArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                . ' cannot be mixed, expecting array',
                            new CodeLocation($statements_analyzer->getSource(), $arg->value)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    return;
                }

                foreach ($arg_type->getTypes() as $atomic_type) {
                    if (!$atomic_type->isIterable($codebase)) {
                        if (IssueBuffer::accepts(
                            new InvalidArgument(
                                'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                    . ' expects array, ' . $atomic_type->getId() . ' provided',
                                new CodeLocation($statements_analyzer->getSource(), $arg->value)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    }
                }

                return;
            }

            if (self::checkFunctionArgumentType(
                $statements_analyzer,
                $arg_type,
                $fleshed_out_type,
                $cased_method_id,
                $argument_offset,
                new CodeLocation($statements_analyzer->getSource(), $arg->value),
                $arg->value,
                $context,
                $function_param->by_ref,
                $function_param->is_variadic
            ) === false) {
                return false;
            }
        }
    }

    /**
     * @return array<string, array{Type\Union, ?string}>
     */
    private static function getTemplateTypesForFunction(
        FunctionLikeStorage $function_storage,
        ClassLikeStorage $class_storage = null,
        ClassLikeStorage $calling_class_storage = null
    ) : array {
        $template_types = [];

        if ($function_storage->template_types) {
            $template_types = $function_storage->template_types;
        }

        if ($class_storage) {
            if ($calling_class_storage
                && $class_storage !== $calling_class_storage
                && $calling_class_storage->template_type_extends
            ) {
                foreach ($calling_class_storage->template_type_extends as $class_name_lc => $type_map) {
                    foreach ($type_map as $template_name => $type) {
                        if (is_string($template_name)
                            && $class_name_lc === strtolower($class_storage->name)
                        ) {
                            if ($type instanceof Type\Atomic\TGenericParam
                                && $type->defining_class
                                && isset(
                                    $calling_class_storage
                                        ->template_type_extends
                                        [strtolower($type->defining_class)]
                                        [$type->param_name]
                                )
                            ) {
                                $type = $calling_class_storage
                                    ->template_type_extends
                                    [strtolower($type->defining_class)]
                                    [$type->param_name];
                            }

                            $template_types[$template_name] = [new Type\Union([$type]), $class_storage->name];
                        }
                    }
                }
            } elseif ($class_storage->template_types) {
                foreach ($class_storage->template_types as $template_name => $type) {
                    $template_types[$template_name] = $type;
                }
            }
        }

        foreach ($template_types as $key => $type) {
            $template_types[$key][0] = clone $type[0];
        }

        return $template_types;
    }

    /**
     * @param   StatementsAnalyzer              $statements_analyzer
     * @param   array<int, PhpParser\Node\Arg> $args
     * @param   string                         $method_id
     *
     * @return  false|null
     */
    protected static function checkArrayFunctionArgumentsMatch(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        $method_id,
        bool $check_functions
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
                    $statements_analyzer,
                    $method_id,
                    $closure_type,
                    $closure_arg,
                    $min_closure_param_count,
                    $max_closure_param_count,
                    $array_arg_types,
                    $check_functions
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
        StatementsAnalyzer $statements_analyzer,
        $method_id,
        Type\Atomic $closure_type,
        PhpParser\Node\Arg $closure_arg,
        $min_closure_param_count,
        $max_closure_param_count,
        array $array_arg_types,
        bool $check_functions
    ) {
        $project_analyzer = $statements_analyzer->getFileAnalyzer()->project_analyzer;

        $codebase = $statements_analyzer->getCodebase();

        if (!$closure_type instanceof Type\Atomic\Fn) {
            if (!$closure_arg->value instanceof PhpParser\Node\Scalar\String_
                && !$closure_arg->value instanceof PhpParser\Node\Expr\Array_
                && !$closure_arg->value instanceof PhpParser\Node\Expr\BinaryOp\Concat
            ) {
                return;
            }

            $function_ids = self::getFunctionIdsFromCallableArg(
                $statements_analyzer,
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
                                $container_class = $statements_analyzer->getFQCLN();

                                if ($callable_fq_class_name === 'parent') {
                                    $container_class = $statements_analyzer->getParentFQCLN();
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
                    if (!$check_functions) {
                        continue;
                    }

                    if (!$codebase->functions->functionExists($statements_analyzer, $function_id)) {
                        continue;
                    }

                    $function_storage = $codebase->functions->getStorage(
                        $statements_analyzer,
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
                $statements_analyzer,
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
        StatementsAnalyzer $statements_analyzer,
        $method_id,
        Type\Atomic\Fn $closure_type,
        PhpParser\Node\Arg $closure_arg,
        $min_closure_param_count,
        $max_closure_param_count,
        array $array_arg_types
    ) {
        $codebase = $statements_analyzer->getCodebase();

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
                    new CodeLocation($statements_analyzer->getSource(), $closure_arg),
                    $method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }
        } elseif ($required_param_count > $max_closure_param_count) {
            $argument_text = $max_closure_param_count === 1 ? 'one argument' : $max_closure_param_count . ' arguments';

            if (IssueBuffer::accepts(
                new TooFewArguments(
                    'The callable passed to ' . $method_id . ' will be called with ' . $argument_text . ', expecting '
                        . $required_param_count,
                    new CodeLocation($statements_analyzer->getSource(), $closure_arg),
                    $method_id
                ),
                $statements_analyzer->getSuppressedIssues()
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

            if ($input_type->hasMixed()) {
                ++$i;
                continue;
            }

            $closure_param_type = $closure_param->type;

            if (!$closure_param_type) {
                ++$i;
                continue;
            }

            $type_match_found = TypeAnalyzer::isContainedBy(
                $codebase,
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
                                $closure_param_type->getId() . ', parent type ' . $input_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new TypeCoercion(
                            'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                $closure_param_type->getId() . ', parent type ' . $input_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                }
            }

            if (!$type_coerced && !$type_match_found) {
                $types_can_be_identical = TypeAnalyzer::canExpressionTypesBeIdentical(
                    $codebase,
                    $input_type,
                    $closure_param_type
                );

                if ($scalar_type_match_found) {
                    if (IssueBuffer::accepts(
                        new InvalidScalarArgument(
                            'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                $closure_param_type->getId() . ', ' . $input_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif ($types_can_be_identical) {
                    if (IssueBuffer::accepts(
                        new PossiblyInvalidArgument(
                            'First parameter of closure passed to function ' . $method_id . ' expects '
                                . $closure_param_type->getId() . ', possibly different type '
                                . $input_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif (IssueBuffer::accepts(
                    new InvalidArgument(
                        'First parameter of closure passed to function ' . $method_id . ' expects ' .
                            $closure_param_type->getId() . ', ' . $input_type->getId() . ' provided',
                        new CodeLocation($statements_analyzer->getSource(), $closure_arg)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    return false;
                }
            }

            ++$i;
        }
    }

    /**
     * @param   StatementsAnalyzer   $statements_analyzer
     * @param   Type\Union          $input_type
     * @param   Type\Union          $param_type
     * @param   string|null         $cased_method_id
     * @param   int                 $argument_offset
     * @param   CodeLocation        $code_location
     * @param   bool                $by_ref
     * @param   bool                $variadic
     *
     * @return  null|false
     */
    public static function checkFunctionArgumentType(
        StatementsAnalyzer $statements_analyzer,
        Type\Union $input_type,
        Type\Union $param_type,
        $cased_method_id,
        $argument_offset,
        CodeLocation $code_location,
        PhpParser\Node\Expr $input_expr,
        Context $context,
        $by_ref = false,
        $variadic = false
    ) {
        if ($param_type->hasMixed()) {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();

        $method_identifier = $cased_method_id ? ' of ' . $cased_method_id : '';

        if ($context->infer_types && isset($input_expr->inferredType)) {
            $source_analyzer = $statements_analyzer->getSource();

            if ($source_analyzer instanceof FunctionLikeAnalyzer
                && $input_expr instanceof PhpParser\Node\Expr\Variable
                && is_string($input_expr->name)
                && isset($input_expr->inferredType)
            ) {
                $context->inferType(
                    $input_expr->name,
                    $source_analyzer->getFunctionLikeStorage($statements_analyzer),
                    $input_expr->inferredType,
                    $param_type,
                    $codebase
                );
            }
        }

        if ($input_type->hasMixed()) {
            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            if (IssueBuffer::accepts(
                new MixedArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be mixed, expecting ' .
                        $param_type,
                    $code_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if ($input_type->isNever()) {
            if (IssueBuffer::accepts(
                new NoValue(
                    'This function or method call never returns output',
                    $code_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                return false;
            }

            return null;
        }

        $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());

        $param_type = TypeAnalyzer::simplifyUnionType(
            $codebase,
            $param_type
        );

        $type_match_found = TypeAnalyzer::isContainedBy(
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

        if ($context->strict_types && !$param_type->from_docblock && $cased_method_id !== 'echo') {
            $scalar_type_match_found = false;

            if ($to_string_cast) {
                $to_string_cast = false;
                $type_match_found = false;
            }
        }

        if ($type_coerced) {
            if ($type_coerced_from_mixed) {
                if (IssueBuffer::accepts(
                    new MixedTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', parent type ' . $input_type->getId() . ' provided',
                        $code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            } else {
                if (IssueBuffer::accepts(
                    new TypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', parent type ' . $input_type->getId() . ' provided',
                        $code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            }
        }

        if ($to_string_cast && $cased_method_id !== 'echo') {
            if (IssueBuffer::accepts(
                new ImplicitToStringCast(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                        $param_type->getId() . ', ' . $input_type->getId() . ' provided with a __toString method',
                    $code_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (!$type_match_found && !$type_coerced) {
            $types_can_be_identical = TypeAnalyzer::canBeContainedBy(
                $codebase,
                $input_type,
                $param_type,
                true,
                true
            );

            if ($scalar_type_match_found) {
                if ($cased_method_id !== 'echo') {
                    if (IssueBuffer::accepts(
                        new InvalidScalarArgument(
                            'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                                $param_type->getId() . ', ' . $input_type->getId() . ' provided',
                            $code_location
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            } elseif ($types_can_be_identical) {
                if (IssueBuffer::accepts(
                    new PossiblyInvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', possibly different type ' . $input_type->getId() . ' provided',
                        $code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif (IssueBuffer::accepts(
                new InvalidArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                        ', ' . $input_type->getId() . ' provided',
                    $code_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return;
        }

        if ($input_expr instanceof PhpParser\Node\Scalar\String_
            || $input_expr instanceof PhpParser\Node\Expr\Array_
            || $input_expr instanceof PhpParser\Node\Expr\BinaryOp\Concat
        ) {
            foreach ($param_type->getTypes() as $param_type_part) {
                if ($param_type_part instanceof TClassString
                    && $input_expr instanceof PhpParser\Node\Scalar\String_
                ) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $input_expr->value,
                        $code_location,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false
                    ) {
                        return false;
                    }
                } elseif ($param_type_part instanceof TArray
                    && $input_expr instanceof PhpParser\Node\Expr\Array_
                ) {
                    foreach ($param_type_part->type_params[1]->getTypes() as $param_array_type_part) {
                        if ($param_array_type_part instanceof TClassString) {
                            foreach ($input_expr->items as $item) {
                                if ($item && $item->value instanceof PhpParser\Node\Scalar\String_) {
                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                        $statements_analyzer,
                                        $item->value->value,
                                        $code_location,
                                        $statements_analyzer->getSuppressedIssues()
                                    ) === false
                                    ) {
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                } elseif ($param_type_part instanceof TCallable) {
                    $function_ids = self::getFunctionIdsFromCallableArg(
                        $statements_analyzer,
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
                                        $container_class = $statements_analyzer->getFQCLN();

                                        if ($callable_fq_class_name === 'parent') {
                                            $container_class = $statements_analyzer->getParentFQCLN();
                                        }

                                        if (!$container_class) {
                                            continue 2;
                                        }

                                        $callable_fq_class_name = $container_class;
                                }

                                $function_id_part = $callable_fq_class_name . '::' . $method_name;

                                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                    $statements_analyzer,
                                    $callable_fq_class_name,
                                    $code_location,
                                    $statements_analyzer->getSuppressedIssues()
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
                                if (MethodAnalyzer::checkMethodExists(
                                    $codebase,
                                    $non_existent_method_ids[0],
                                    $code_location,
                                    $statements_analyzer->getSuppressedIssues()
                                ) === false
                                ) {
                                    return false;
                                }
                            }
                        } else {
                            if (!$param_type->hasString() && !$param_type->hasArray() && self::checkFunctionExists(
                                $statements_analyzer,
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

        if (!$param_type->isNullable() && $cased_method_id !== 'echo') {
            if ($input_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, ' .
                            'null value provided to parameter with type ' . $param_type->getId(),
                        $code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
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
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }

        if ($input_type->isFalsable()
            && !$param_type->hasBool()
            && !$param_type->hasScalar()
            && !$input_type->ignore_falsable_issues
        ) {
            if (IssueBuffer::accepts(
                new PossiblyFalseArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be false, possibly ' .
                        'false value provided',
                    $code_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($type_match_found
            && !$param_type->hasMixed()
            && !$param_type->from_docblock
            && !$variadic
            && !$by_ref
            && $cased_method_id !== 'echo'
        ) {
            $var_id = ExpressionAnalyzer::getVarId(
                $input_expr,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
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

                $context->removeVarFromConflictingClauses($var_id, null, $statements_analyzer);

                $context->vars_in_scope[$var_id] = $input_type;
            }
        }

        return null;
    }

    /**
     * @param  PhpParser\Node\Scalar\String_|PhpParser\Node\Expr\Array_|PhpParser\Node\Expr\BinaryOp\Concat
     *         $callable_arg
     *
     * @return string[]
     */
    public static function getFunctionIdsFromCallableArg(
        \Psalm\FileSource $file_source,
        $callable_arg
    ) {
        if ($callable_arg instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            if ($callable_arg->left instanceof PhpParser\Node\Expr\ClassConstFetch
                && $callable_arg->left->class instanceof PhpParser\Node\Name
                && $callable_arg->left->name instanceof PhpParser\Node\Identifier
                && strtolower($callable_arg->left->name->name) === 'class'
                && !in_array(strtolower($callable_arg->left->class->parts[0]), ['self', 'static', 'parent'])
                && $callable_arg->right instanceof PhpParser\Node\Scalar\String_
                && preg_match('/^::[A-Za-z0-9]+$/', $callable_arg->right->value)
            ) {
                return [
                    (string) $callable_arg->left->class->getAttribute('resolvedName') . $callable_arg->right->value
                ];
            }

            return [];
        }

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
            $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
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
                        if ($extra_type instanceof Type\Atomic\TGenericParam) {
                            throw new \UnexpectedValueException('Shouldn’t get a generic param here');
                        }
                        $method_id .= '&' . $extra_type->value . '::' . $method_name_arg->value;
                    }
                }

                $method_ids[] = $method_id;
            }
        }

        return $method_ids;
    }

    /**
     * @param  StatementsAnalyzer    $statements_analyzer
     * @param  string               $function_id
     * @param  CodeLocation         $code_location
     * @param  bool                 $can_be_in_root_scope if true, the function can be shortened to the root version
     *
     * @return bool
     */
    protected static function checkFunctionExists(
        StatementsAnalyzer $statements_analyzer,
        &$function_id,
        CodeLocation $code_location,
        $can_be_in_root_scope
    ) {
        $cased_function_id = $function_id;
        $function_id = strtolower($function_id);

        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->functions->functionExists($statements_analyzer, $function_id)) {
            $root_function_id = preg_replace('/.*\\\/', '', $function_id);

            if ($can_be_in_root_scope
                && $function_id !== $root_function_id
                && $codebase->functions->functionExists($statements_analyzer, $root_function_id)
            ) {
                $function_id = $root_function_id;
            } else {
                if (IssueBuffer::accepts(
                    new UndefinedFunction(
                        'Function ' . $cased_function_id . ' does not exist',
                        $code_location,
                        $function_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return false;
            }
        }

        return true;
    }

    /**
     * @param PhpParser\Node\Identifier|PhpParser\Node\Name $expr
     * @param  \Psalm\Storage\Assertion[] $assertions
     * @param  array<int, PhpParser\Node\Arg> $args
     * @param  Context           $context
     * @param  array<string, array{Type\Union, ?string}> $template_type_map,
     * @param  StatementsAnalyzer $statements_analyzer
     *
     * @return void
     */
    protected static function applyAssertionsToContext(
        $expr,
        array $assertions,
        array $args,
        array $template_type_map,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ) {
        $type_assertions = [];

        $asserted_keys = [];

        foreach ($assertions as $assertion) {
            $assertion_var_id = null;

            $arg_value = null;

            if (is_int($assertion->var_id)) {
                if (!isset($args[$assertion->var_id])) {
                    continue;
                }

                $arg_value = $args[$assertion->var_id]->value;

                $arg_var_id = ExpressionAnalyzer::getArrayVarId($arg_value, null, $statements_analyzer);

                if ($arg_var_id) {
                    $assertion_var_id = $arg_var_id;
                }
            } elseif (isset($context->vars_in_scope[$assertion->var_id])) {
                $assertion_var_id = $assertion->var_id;
            }

            if ($assertion_var_id) {
                $rule = $assertion->rule[0][0];

                $prefix = '';
                if ($rule[0] === '!') {
                    $prefix .= '!';
                    $rule = substr($rule, 1);
                }
                if ($rule[0] === '=') {
                    $prefix .= '=';
                    $rule = substr($rule, 1);
                }
                if ($rule[0] === '~') {
                    $prefix .= '~';
                    $rule = substr($rule, 1);
                }

                if (isset($template_type_map[$rule])) {
                    if ($template_type_map[$rule][0]->hasMixed()) {
                        continue;
                    }

                    $replacement_atomic_types = $template_type_map[$rule][0]->getTypes();

                    if (count($replacement_atomic_types) > 1) {
                        continue;
                    }

                    $ored_type_assertions = [];

                    foreach ($replacement_atomic_types as $replacement_atomic_type) {
                        if ($replacement_atomic_type instanceof Type\Atomic\TMixed) {
                            continue 2;
                        }

                        if ($replacement_atomic_type instanceof Type\Atomic\TArray
                            || $replacement_atomic_type instanceof Type\Atomic\ObjectLike
                        ) {
                            $ored_type_assertions[] = $prefix . 'array';
                        } elseif ($replacement_atomic_type instanceof Type\Atomic\TNamedObject) {
                            $ored_type_assertions[] = $prefix . $replacement_atomic_type->value;
                        } elseif ($replacement_atomic_type instanceof Type\Atomic\Scalar) {
                            $ored_type_assertions[] = $prefix . $replacement_atomic_type->getId();
                        } elseif ($replacement_atomic_type instanceof Type\Atomic\TNull) {
                            $ored_type_assertions[] = $prefix . 'null';
                        }
                    }

                    if ($ored_type_assertions) {
                        $type_assertions[$assertion_var_id] = [$ored_type_assertions];
                    }
                } else {
                    $type_assertions[$assertion_var_id] = $assertion->rule;
                }
            } elseif ($arg_value && $assertion->rule === [['!falsy']]) {
                $assert_clauses = \Psalm\Type\Algebra::getFormula(
                    $arg_value,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer,
                    $statements_analyzer->getCodebase()
                );

                $simplified_clauses = \Psalm\Type\Algebra::simplifyCNF(
                    array_merge($context->clauses, $assert_clauses)
                );

                $assert_type_assertions = \Psalm\Type\Algebra::getTruthsFromFormula(
                    $simplified_clauses
                );

                $type_assertions = array_merge($type_assertions, $assert_type_assertions);
            }
        }

        $changed_vars = [];

        foreach ($type_assertions as $var_id => $_) {
            $asserted_keys[$var_id] = true;
        }

        if ($type_assertions) {
            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = \Psalm\Type\Reconciler::reconcileKeyedTypes(
                $type_assertions,
                $context->vars_in_scope,
                $changed_vars,
                $asserted_keys,
                $statements_analyzer,
                $template_type_map,
                $context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $expr)
            );

            foreach ($changed_vars as $changed_var) {
                if (isset($op_vars_in_scope[$changed_var])) {
                    $op_vars_in_scope[$changed_var]->from_docblock = true;
                }
            }

            $context->vars_in_scope = $op_vars_in_scope;
        }
    }
}
