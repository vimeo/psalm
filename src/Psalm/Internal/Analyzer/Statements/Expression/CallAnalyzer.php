<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\Statements\Block\ForeachAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TypeAnalyzer;
use Psalm\Internal\Codebase\CallMap;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Taint\Sink;
use Psalm\Internal\Taint\Source;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\ImplicitToStringCast;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidPassByReference;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\MixedArgument;
use Psalm\Issue\MixedArgumentTypeCoercion;
use Psalm\Issue\NoValue;
use Psalm\Issue\NullArgument;
use Psalm\Issue\PossiblyFalseArgument;
use Psalm\Issue\PossiblyInvalidArgument;
use Psalm\Issue\PossiblyNullArgument;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\TooFewArguments;
use Psalm\Issue\TooManyArguments;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\Issue\UndefinedFunction;
use Psalm\Issue\UndefinedVariable;
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
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use function strtolower;
use function strpos;
use function explode;
use function count;
use function in_array;
use function array_reverse;
use function array_filter;
use function is_null;
use function is_string;
use function assert;
use function preg_match;
use function preg_replace;
use function str_replace;
use function is_int;
use function substr;
use function array_merge;
use Psalm\Issue\TaintedInput;

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
            $method_id = new \Psalm\Internal\MethodIdentifier(
                $fq_class_name,
                strtolower($method_name)
            );

            if ((string) $method_id !== $source->getId()) {
                if ($context->collect_initializations) {
                    if (isset($context->initialized_methods[(string) $method_id])) {
                        return;
                    }

                    if ($context->initialized_methods === null) {
                        $context->initialized_methods = [];
                    }

                    $context->initialized_methods[(string) $method_id] = true;
                }

                $project_analyzer->getMethodMutations(
                    $method_id,
                    $context,
                    $source->getRootFilePath(),
                    $source->getRootFileName()
                );
            }
        } elseif ($context->collect_initializations &&
            $context->self &&
            (
                $context->self === $fq_class_name
                || $codebase->classlikes->classExtends(
                    $context->self,
                    $fq_class_name
                )
            ) &&
            $source->getMethodName() !== $method_name
        ) {
            $method_id = new \Psalm\Internal\MethodIdentifier($fq_class_name, strtolower($method_name));

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if (isset($context->vars_in_scope['$this'])) {
                foreach ($context->vars_in_scope['$this']->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNamedObject) {
                        if ($fq_class_name === $atomic_type->value) {
                            $alt_declaring_method_id = $declaring_method_id;
                        } else {
                            $fq_class_name = $atomic_type->value;

                            $method_id = new \Psalm\Internal\MethodIdentifier(
                                $fq_class_name,
                                strtolower($method_name)
                            );

                            $alt_declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);
                        }

                        if ($alt_declaring_method_id) {
                            $declaring_method_id = $alt_declaring_method_id;
                            break;
                        }

                        if (!$atomic_type->extra_types) {
                            continue;
                        }

                        foreach ($atomic_type->extra_types as $intersection_type) {
                            if ($intersection_type instanceof TNamedObject) {
                                $fq_class_name = $intersection_type->value;
                                $method_id = new \Psalm\Internal\MethodIdentifier(
                                    $fq_class_name,
                                    strtolower($method_name)
                                );

                                $alt_declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                                if ($alt_declaring_method_id) {
                                    $declaring_method_id = $alt_declaring_method_id;
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }

            if (!$declaring_method_id) {
                // can happen for __call
                return;
            }

            if (isset($context->initialized_methods[(string) $declaring_method_id])) {
                return;
            }

            if ($context->initialized_methods === null) {
                $context->initialized_methods = [];
            }

            $context->initialized_methods[(string) $declaring_method_id] = true;

            $method_storage = $codebase->methods->getStorage($declaring_method_id);

            $class_analyzer = $source->getSource();

            if ($class_analyzer instanceof ClassLikeAnalyzer
                && !$method_storage->is_static
                && ($context->collect_nonprivate_initializations
                    || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
                    || $method_storage->final)
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

                $old_calling_method_id = $context->calling_method_id;

                if ($fq_class_name === $source->getFQCLN()) {
                    $class_analyzer->getMethodMutations(strtolower($method_name), $context);
                } else {
                    $declaring_fq_class_name = $declaring_method_id->fq_class_name;

                    $old_self = $context->self;
                    $context->self = $declaring_fq_class_name;
                    $project_analyzer->getMethodMutations(
                        $declaring_method_id,
                        $context,
                        $source->getRootFilePath(),
                        $source->getRootFileName()
                    );
                    $context->self = $old_self;
                }

                $context->calling_method_id = $old_calling_method_id;

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
     * @param  array<int, PhpParser\Node\Arg>   $args
     * @param  Context                          $context
     * @param  CodeLocation                     $code_location
     * @param  StatementsAnalyzer               $statements_analyzer
     *
     * @return false|null
     */
    protected static function checkMethodArgs(
        ?\Psalm\Internal\MethodIdentifier $method_id,
        array $args,
        ?TemplateResult $class_template_result,
        Context $context,
        CodeLocation $code_location,
        StatementsAnalyzer $statements_analyzer
    ) {
        $codebase = $statements_analyzer->getCodebase();

        $method_params = $method_id
            ? $codebase->methods->getMethodParams($method_id, $statements_analyzer, $args, $context)
            : null;

        if (self::checkFunctionArguments(
            $statements_analyzer,
            $args,
            $method_params,
            (string) $method_id,
            $context,
            $class_template_result
        ) === false) {
            return false;
        }

        if (!$method_id || $method_params === null) {
            return;
        }

        $fq_class_name = $method_id->fq_class_name;
        $method_name = $method_id->method_name;

        $fq_class_name = strtolower($codebase->classlikes->getUnAliasedName($fq_class_name));

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $method_storage = null;

        if (isset($class_storage->declaring_method_ids[$method_name])) {
            $declaring_method_id = $class_storage->declaring_method_ids[$method_name];

            $declaring_fq_class_name = $declaring_method_id->fq_class_name;
            $declaring_method_name = $declaring_method_id->method_name;

            if ($declaring_fq_class_name !== $fq_class_name) {
                $declaring_class_storage = $codebase->classlike_storage_provider->get($declaring_fq_class_name);
            } else {
                $declaring_class_storage = $class_storage;
            }

            if (!isset($declaring_class_storage->methods[$declaring_method_name])) {
                throw new \UnexpectedValueException('Storage should not be empty here');
            }

            $method_storage = $declaring_class_storage->methods[$declaring_method_name];

            if ($declaring_class_storage->user_defined
                && !$method_storage->has_docblock_param_types
                && isset($declaring_class_storage->documenting_method_ids[$method_name])
            ) {
                $documenting_method_id = $declaring_class_storage->documenting_method_ids[$method_name];

                $documenting_method_storage = $codebase->methods->getStorage($documenting_method_id);

                if ($documenting_method_storage->template_types) {
                    $method_storage = $documenting_method_storage;
                }
            }

            if (!$context->isSuppressingExceptions($statements_analyzer)) {
                $context->mergeFunctionExceptions($method_storage, $code_location);
            }
        }

        if (self::checkFunctionLikeArgumentsMatch(
            $statements_analyzer,
            $args,
            $method_id,
            $method_params,
            $method_storage,
            $class_storage,
            $class_template_result,
            $code_location,
            $context
        ) === false) {
            return false;
        }

        if ($class_template_result) {
            self::checkTemplateResult(
                $statements_analyzer,
                $class_template_result,
                $code_location,
                strtolower((string) $method_id)
            );
        }

        return null;
    }

    /**
     * @param   StatementsAnalyzer                       $statements_analyzer
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   array<int, FunctionLikeParameter>|null  $function_params
     * @param   array<string, array<string, array{Type\Union, 1?:int}>>|null   $generic_params
     * @param   string|null                             $method_id
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    public static function checkFunctionArguments(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        ?array $function_params,
        ?string $method_id,
        Context $context,
        ?TemplateResult $template_result = null
    ) {
        $last_param = $function_params
            ? $function_params[count($function_params) - 1]
            : null;

        // if this modifies the array type based on further args
        if ($method_id
            && in_array($method_id, ['array_push', 'array_unshift'], true)
            && $function_params
            && isset($args[0])
            && isset($args[1])
        ) {
            if (self::handleArrayAddition(
                $statements_analyzer,
                $args,
                $context,
                $method_id === 'array_push'
            ) === false
            ) {
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

        if ($method_id === 'array_map') {
            $args = array_reverse($args, true);
        }

        foreach ($args as $argument_offset => $arg) {
            if ($function_params !== null) {
                $param = $argument_offset < count($function_params)
                    ? $function_params[$argument_offset]
                    : ($last_param && $last_param->is_variadic ? $last_param : null);

                $by_ref = $param && $param->by_ref;

                $by_ref_type = null;

                if ($by_ref && $param) {
                    $by_ref_type = $param->type ? clone $param->type : Type::getMixed();
                }

                if ($by_ref
                    && $by_ref_type
                    && !($arg->value instanceof PhpParser\Node\Expr\Closure
                        || $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                        || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
                        || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                        || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                        || $arg->value instanceof PhpParser\Node\Expr\StaticCall
                        || $arg->value instanceof PhpParser\Node\Expr\New_
                        || $arg->value instanceof PhpParser\Node\Expr\Assign
                        || $arg->value instanceof PhpParser\Node\Expr\Array_
                        || $arg->value instanceof PhpParser\Node\Expr\Ternary
                        || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
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

                $codebase = $statements_analyzer->getCodebase();

                if (($arg->value instanceof PhpParser\Node\Expr\Closure
                        || $arg->value instanceof PhpParser\Node\Expr\ArrowFunction)
                    && $template_result
                    && $template_result->upper_bounds
                    && $param
                    && $param->type
                    && !$arg->value->getDocComment()
                ) {
                    if (($argument_offset === 1 && $method_id === 'array_filter' && count($args) === 2)
                        || ($argument_offset === 0 && $method_id === 'array_map' && count($args) >= 2)
                    ) {
                        $function_like_params = [];

                        foreach ($template_result->upper_bounds as $template_name => $_) {
                            $function_like_params[] = new \Psalm\Storage\FunctionLikeParameter(
                                'function',
                                false,
                                new Type\Union([
                                    new Type\Atomic\TTemplateParam(
                                        $template_name,
                                        Type::getMixed(),
                                        $method_id
                                    )
                                ])
                            );
                        }

                        $replaced_type = new Type\Union([
                            new Type\Atomic\TCallable(
                                'callable',
                                array_reverse($function_like_params)
                            )
                        ]);
                    } else {
                        $replaced_type = clone $param->type;
                    }

                    $replace_template_result = new \Psalm\Internal\Type\TemplateResult(
                        $template_result->upper_bounds,
                        []
                    );

                    $replaced_type = \Psalm\Internal\Type\UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        $replaced_type,
                        $replace_template_result,
                        $codebase,
                        $statements_analyzer,
                        null,
                        null,
                        null,
                        'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
                    );

                    $replaced_type->replaceTemplateTypesWithArgTypes(
                        $replace_template_result,
                        $codebase
                    );

                    $closure_id = strtolower($statements_analyzer->getFilePath())
                        . ':' . $arg->value->getLine()
                        . ':' . (int)$arg->value->getAttribute('startFilePos')
                        . ':-:closure';

                    try {
                        $closure_storage = $codebase->getClosureStorage(
                            $statements_analyzer->getFilePath(),
                            $closure_id
                        );
                    } catch (\UnexpectedValueException $e) {
                        continue;
                    }

                    foreach ($replaced_type->getAtomicTypes() as $replaced_type_part) {
                        if ($replaced_type_part instanceof Type\Atomic\TCallable
                            || $replaced_type_part instanceof Type\Atomic\TFn
                        ) {
                            foreach ($closure_storage->params as $closure_param_offset => $param_storage) {
                                if (isset($replaced_type_part->params[$closure_param_offset]->type)
                                    && !$replaced_type_part->params[$closure_param_offset]->type->hasTemplate()
                                ) {
                                    if ($param_storage->type) {
                                        if ($param_storage->type !== $param_storage->signature_type) {
                                            continue;
                                        }

                                        $type_match_found = TypeAnalyzer::isContainedBy(
                                            $codebase,
                                            $replaced_type_part->params[$closure_param_offset]->type,
                                            $param_storage->type
                                        );

                                        if (!$type_match_found) {
                                            continue;
                                        }
                                    }

                                    $param_storage->type = $replaced_type_part->params[$closure_param_offset]->type;
                                }
                            }
                        }
                    }
                }

                $was_inside_call = $context->inside_call;

                $context->inside_call = true;

                if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                    return false;
                }

                if (!$was_inside_call) {
                    $context->inside_call = false;
                }

                if (($argument_offset === 0 && $method_id === 'array_filter' && count($args) === 2)
                    || ($argument_offset > 0 && $method_id === 'array_map' && count($args) >= 2)
                ) {
                    $generic_param_type = new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getArrayKey(),
                            new Type\Union([
                                new Type\Atomic\TTemplateParam(
                                    'ArrayValue' . $argument_offset,
                                    Type::getMixed(),
                                    $method_id
                                )
                            ])
                        ])
                    ]);

                    $template_types = ['ArrayValue' . $argument_offset => [$method_id => [Type::getMixed()]]];

                    $replace_template_result = new \Psalm\Internal\Type\TemplateResult(
                        $template_types,
                        []
                    );

                    \Psalm\Internal\Type\UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        $generic_param_type,
                        $replace_template_result,
                        $codebase,
                        $statements_analyzer,
                        $statements_analyzer->node_data->getType($arg->value),
                        $argument_offset,
                        'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
                    );

                    if ($replace_template_result->upper_bounds) {
                        if (!$template_result) {
                            $template_result = new TemplateResult([], []);
                        }

                        $template_result->upper_bounds += $replace_template_result->upper_bounds;
                    }
                }

                if ($codebase->find_unused_variables
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
            || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\FuncCall
            || $arg->value instanceof PhpParser\Node\Expr\MethodCall
            || $arg->value instanceof PhpParser\Node\Expr\StaticCall
            || $arg->value instanceof PhpParser\Node\Expr\New_
            || $arg->value instanceof PhpParser\Node\Expr\Assign
            || $arg->value instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $arg->value instanceof PhpParser\Node\Expr\PropertyFetch
            || $arg->value instanceof PhpParser\Node\Expr\Array_
            || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
            || $arg->value instanceof PhpParser\Node\Expr\Ternary
            || $arg->value instanceof PhpParser\Node\Scalar\Encapsed
            || $arg->value instanceof PhpParser\Node\Expr\PostInc
            || $arg->value instanceof PhpParser\Node\Expr\PostDec
            || $arg->value instanceof PhpParser\Node\Expr\PreInc
            || $arg->value instanceof PhpParser\Node\Expr\PreDec
        ) {
            $was_inside_call = $context->inside_call;
            $context->inside_call = true;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
                return false;
            }

            if (!$was_inside_call) {
                $context->inside_call = false;
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
                if (!isset($context->vars_in_scope[$var_id])
                    && $arg->value instanceof PhpParser\Node\Expr\Variable
                ) {
                    if (IssueBuffer::accepts(
                        new PossiblyUndefinedVariable(
                            'Variable ' . $var_id
                                . ' must be defined prior to use within an unknown function or method',
                            new CodeLocation($statements_analyzer->getSource(), $arg->value)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

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

                foreach ($context->vars_in_scope[$var_id]->getAtomicTypes() as $type) {
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
            'ksort', 'asort', 'krsort', 'arsort', 'natcasesort', 'natsort',
            'reset', 'end', 'next', 'prev', 'array_pop', 'array_shift',
        ];

        if (($var_id && isset($context->vars_in_scope[$var_id]))
            || ($method_id
                && in_array(
                    $method_id,
                    $builtin_array_functions,
                    true
                ))
        ) {
            $was_inside_assignment = $context->inside_assignment;
            $context->inside_assignment = true;

            // if the variable is in scope, get or we're in a special array function,
            // figure out its type before proceeding
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $arg->value,
                $context
            ) === false) {
                return false;
            }

            $context->inside_assignment = $was_inside_assignment;
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

            if (($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                && $arg_value_type->hasArray()
            ) {
                /**
                 * @psalm-suppress PossiblyUndefinedStringArrayOffset
                 * @var TArray|TList|ObjectLike
                 */
                $array_type = $arg_value_type->getAtomicTypes()['array'];

                if ($array_type instanceof ObjectLike) {
                    $array_type = $array_type->getGenericArrayType();
                }

                if ($array_type instanceof TList) {
                    $array_type = new TArray([Type::getInt(), $array_type->type_param]);
                }

                $by_ref_type = new Type\Union([clone $array_type]);

                ExpressionAnalyzer::assignByRefParam(
                    $statements_analyzer,
                    $arg->value,
                    $by_ref_type,
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

        if (!$arg->value instanceof PhpParser\Node\Expr\Variable) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $arg->value, $context) === false) {
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
        Context $context,
        bool $is_push
    ) {
        $array_arg = $args[0]->value;

        $context->inside_call = true;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $array_arg,
            $context
        ) === false) {
            return false;
        }

        for ($i = 1; $i < count($args); $i++) {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $args[$i]->value,
                $context
            ) === false) {
                return false;
            }
        }

        if (($array_arg_type = $statements_analyzer->node_data->getType($array_arg))
            && $array_arg_type->hasArray()
        ) {
            /**
             * @psalm-suppress PossiblyUndefinedStringArrayOffset
             * @var TArray|ObjectLike|TList
             */
            $array_type = $array_arg_type->getAtomicTypes()['array'];

            $objectlike_list = null;

            if ($array_type instanceof ObjectLike) {
                if ($array_type->is_list) {
                    $objectlike_list = clone $array_type;
                }

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

                if (!($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                    || $arg_value_type->hasMixed()
                ) {
                    $by_ref_type = Type::combineUnionTypes(
                        $by_ref_type,
                        new Type\Union([new TArray([Type::getInt(), Type::getMixed()])])
                    );
                } elseif ($arg->unpack) {
                    $by_ref_type = Type::combineUnionTypes(
                        $by_ref_type,
                        clone $arg_value_type
                    );
                } else {
                    if ($objectlike_list) {
                        if ($is_push) {
                            \array_push($objectlike_list->properties, $arg_value_type);
                        } else {
                            \array_unshift($objectlike_list->properties, $arg_value_type);
                        }

                        $by_ref_type = new Type\Union([$objectlike_list]);
                    } elseif ($array_type instanceof TList) {
                        $by_ref_type = Type::combineUnionTypes(
                            $by_ref_type,
                            new Type\Union(
                                [
                                    new TNonEmptyList(clone $arg_value_type),
                                ]
                            )
                        );
                    } else {
                        $by_ref_type = Type::combineUnionTypes(
                            $by_ref_type,
                            new Type\Union(
                                [
                                    new TNonEmptyArray(
                                        [
                                            Type::getInt(),
                                            clone $arg_value_type
                                        ]
                                    ),
                                ]
                            )
                        );
                    }
                }
            }

            ExpressionAnalyzer::assignByRefParam(
                $statements_analyzer,
                $array_arg,
                $by_ref_type,
                $by_ref_type,
                $context,
                false
            );
        }

        $context->inside_call = false;

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
        $context->inside_call = true;
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

        $context->inside_call = false;

        $replacement_arg_type = $statements_analyzer->node_data->getType($replacement_arg);

        if ($replacement_arg_type
            && !$replacement_arg_type->hasArray()
            && $replacement_arg_type->hasString()
            && $replacement_arg_type->isSingle()
        ) {
            $replacement_arg_type = new Type\Union([
                new Type\Atomic\TArray([Type::getInt(), $replacement_arg_type])
            ]);

            $statements_analyzer->node_data->setType($replacement_arg, $replacement_arg_type);
        }

        if (($array_arg_type = $statements_analyzer->node_data->getType($array_arg))
            && $array_arg_type->hasArray()
            && $replacement_arg_type
            && $replacement_arg_type->hasArray()
        ) {
            /**
             * @psalm-suppress PossiblyUndefinedStringArrayOffset
             * @var TArray|ObjectLike|TList
             */
            $array_type = $array_arg_type->getAtomicTypes()['array'];

            if ($array_type instanceof ObjectLike) {
                if ($array_type->is_list) {
                    $array_type = new TNonEmptyList($array_type->getGenericValueType());
                } else {
                    $array_type = $array_type->getGenericArrayType();
                }
            }

            if ($array_type instanceof TArray
                && $array_type->type_params[0]->hasInt()
                && !$array_type->type_params[0]->hasString()
            ) {
                if ($array_type instanceof TNonEmptyArray) {
                    $array_type = new TNonEmptyList($array_type->type_params[1]);
                } else {
                    $array_type = new TList($array_type->type_params[1]);
                }
            }

            /**
             * @psalm-suppress PossiblyUndefinedStringArrayOffset
             * @var TArray|ObjectLike|TList
             */
            $replacement_array_type = $replacement_arg_type->getAtomicTypes()['array'];

            $by_ref_type = TypeCombination::combineTypes([$array_type, $replacement_array_type]);

            ExpressionAnalyzer::assignByRefParam(
                $statements_analyzer,
                $array_arg,
                $by_ref_type,
                $by_ref_type,
                $context,
                false
            );

            return;
        }

        $array_type = Type::getArray();

        ExpressionAnalyzer::assignByRefParam(
            $statements_analyzer,
            $array_arg,
            $array_type,
            $array_type,
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

                $array_atomic_types = $array_type->getAtomicTypes();

                foreach ($array_atomic_types as $array_atomic_type) {
                    if ($array_atomic_type instanceof ObjectLike) {
                        $array_atomic_type = $array_atomic_type->getGenericArrayType();
                    }

                    if ($array_atomic_type instanceof TNonEmptyArray) {
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
                        $context->removeDescendents($var_id, $array_type);
                    } elseif ($array_atomic_type instanceof TNonEmptyList) {
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
                            $array_atomic_type = new TList($array_atomic_type->type_param);
                        }

                        $array_type->addType($array_atomic_type);
                        $context->removeDescendents($var_id, $array_type);
                    }
                }

                $context->vars_in_scope[$var_id] = $array_type;
            }
        }
    }

    /**
     * @param   StatementsAnalyzer                       $statements_analyzer
     * @param   array<int, PhpParser\Node\Arg>          $args
     * @param   string|MethodIdentifier|null  $method_id
     * @param   array<int,FunctionLikeParameter>        $function_params
     * @param   FunctionLikeStorage|null                $function_storage
     * @param   ClassLikeStorage|null                   $class_storage
     * @param   CodeLocation                            $code_location
     * @param   Context                                 $context
     *
     * @return  false|null
     */
    public static function checkFunctionLikeArgumentsMatch(
        StatementsAnalyzer $statements_analyzer,
        array $args,
        $method_id,
        array $function_params,
        $function_storage,
        $class_storage,
        ?TemplateResult $class_template_result,
        CodeLocation $code_location,
        Context $context
    ) {
        $in_call_map = $method_id ? CallMap::inCallMap((string) $method_id) : false;

        $cased_method_id = (string) $method_id;

        $is_variadic = false;

        $fq_class_name = null;

        $codebase = $statements_analyzer->getCodebase();

        if ($method_id) {
            if (!$in_call_map && $method_id instanceof \Psalm\Internal\MethodIdentifier) {
                $fq_class_name = $method_id->fq_class_name;
            }

            if ($function_storage) {
                $is_variadic = $function_storage->variadic;
            } elseif (is_string($method_id)) {
                $is_variadic = $codebase->functions->isVariadic(
                    $codebase,
                    strtolower($method_id),
                    $statements_analyzer->getRootFilePath()
                );
            } else {
                $is_variadic = $codebase->methods->isVariadic($method_id);
            }
        }

        if ($method_id instanceof \Psalm\Internal\MethodIdentifier) {
            $cased_method_id = $codebase->methods->getCasedMethodId($method_id);
        } elseif ($function_storage) {
            $cased_method_id = $function_storage->cased_name;
        }

        $calling_class_storage = $class_storage;

        $static_fq_class_name = $fq_class_name;
        $self_fq_class_name = $fq_class_name;

        if ($method_id instanceof \Psalm\Internal\MethodIdentifier) {
            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id && (string)$declaring_method_id !== (string)$method_id) {
                $self_fq_class_name = $declaring_method_id->fq_class_name;
                $class_storage = $codebase->classlike_storage_provider->get($self_fq_class_name);
            }

            $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

            if ($appearing_method_id && $declaring_method_id !== $appearing_method_id) {
                $self_fq_class_name = $appearing_method_id->fq_class_name;
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

        $template_result = null;

        $class_generic_params = $class_template_result
            ? $class_template_result->upper_bounds
            : [];

        if ($function_storage) {
            $template_types = self::getTemplateTypesForCall(
                $class_storage,
                $calling_class_storage,
                $function_storage->template_types ?: []
            );

            if ($template_types) {
                $template_result = $class_template_result;

                if (!$template_result) {
                    $template_result = new TemplateResult($template_types, []);
                } elseif (!$template_result->template_types) {
                    $template_result->template_types = $template_types;
                }

                foreach ($args as $argument_offset => $arg) {
                    $function_param = count($function_params) > $argument_offset
                        ? $function_params[$argument_offset]
                        : ($last_param && $last_param->is_variadic ? $last_param : null);

                    if (!$function_param
                        || !$function_param->type
                    ) {
                        continue;
                    }

                    $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

                    if (!$arg_value_type) {
                        continue;
                    }

                    UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        $function_param->type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $arg_value_type,
                        $argument_offset,
                        $context->self,
                        $context->calling_method_id ?: $context->calling_function_id,
                        false
                    );

                    if (!$class_template_result) {
                        $template_result->upper_bounds = [];
                    }
                }
            }
        }

        foreach ($class_generic_params as $template_name => $type_map) {
            foreach ($type_map as $class => $type) {
                $class_generic_params[$template_name][$class][0] = clone $type[0];
            }
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
                    (string) $method_id,
                    $cased_method_id,
                    $last_param,
                    $function_params,
                    $function_storage,
                    $argument_offset,
                    $arg,
                    $context,
                    $template_result
                ) === false) {
                    return;
                }
            }

            if ($method_id === 'compact'
                && ($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                && $arg_value_type->isSingleStringLiteral()
            ) {
                $literal = $arg_value_type->getSingleStringLiteral();

                if (!$context->hasVariable('$' . $literal->value, $statements_analyzer)) {
                    if (IssueBuffer::accepts(
                        new UndefinedVariable(
                            'Cannot find referenced variable $' . $literal->value,
                            new CodeLocation($statements_analyzer->getSource(), $arg->value)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            if (self::checkFunctionLikeArgumentMatches(
                $statements_analyzer,
                $cased_method_id,
                $self_fq_class_name,
                $static_fq_class_name,
                $code_location,
                $function_param,
                $argument_offset,
                $arg,
                $context,
                $class_generic_params,
                $template_result,
                $function_storage ? $function_storage->pure : false,
                $in_call_map
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
                    // fall through
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
                    // fall through
                }
            }

            if (self::checkArrayFunctionArgumentsMatch(
                $statements_analyzer,
                $context,
                $args,
                $method_id,
                $context->check_functions
            ) === false
            ) {
                return false;
            }

            return null;
        }

        if (!$is_variadic
            && count($args) > count($function_params)
            && (!count($function_params) || $function_params[count($function_params) - 1]->name !== '...=')
            && ($in_call_map
                || !$function_storage instanceof \Psalm\Storage\MethodStorage
                || $function_storage->is_static
                || ($method_id instanceof MethodIdentifier
                    && $method_id->method_name === '__construct'))
        ) {
            if (IssueBuffer::accepts(
                new TooManyArguments(
                    'Too many arguments for ' . ($cased_method_id ?: $method_id)
                        . ' - expecting ' . count($function_params) . ' but saw ' . count($args),
                    $code_location,
                    (string) $method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if (!$has_packed_var && count($args) < count($function_params)) {
            if ($function_storage) {
                $expected_param_count = $function_storage->required_param_count;
            } else {
                for ($i = 0, $j = count($function_params); $i < $j; ++$i) {
                    $param = $function_params[$i];

                    if ($param->is_optional || $param->is_variadic) {
                        break;
                    }
                }

                $expected_param_count = $i;
            }

            for ($i = count($args), $j = count($function_params); $i < $j; ++$i) {
                $param = $function_params[$i];

                if (!$param->is_optional
                    && !$param->is_variadic
                    && ($in_call_map
                        || !$function_storage instanceof \Psalm\Storage\MethodStorage
                        || $function_storage->is_static
                        || ($method_id instanceof MethodIdentifier
                            && $method_id->method_name === '__construct'))
                ) {
                    if (IssueBuffer::accepts(
                        new TooFewArguments(
                            'Too few arguments for ' . $cased_method_id
                                . ' - expecting ' . $expected_param_count . ' but saw ' . count($args),
                            $code_location,
                            (string) $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }

                    break;
                }

                if ($param->is_optional
                    && $param->type
                    && $param->default_type
                    && !$param->is_variadic
                    && $template_result
                ) {
                    UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        $param->type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        clone $param->default_type,
                        $i,
                        $context->self,
                        $context->calling_method_id ?: $context->calling_function_id,
                        true
                    );
                }
            }
        }
    }

    /**
     * @param  ?string $self_fq_class_name
     * @param  ?string $static_fq_class_name
     * @param  array<string, array<string, array{Type\Union, 1?:int}>> $class_generic_params
     * @return false|null
     */
    private static function checkFunctionLikeArgumentMatches(
        StatementsAnalyzer $statements_analyzer,
        ?string $cased_method_id,
        ?string $self_fq_class_name,
        ?string $static_fq_class_name,
        CodeLocation $function_location,
        ?FunctionLikeParameter $function_param,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        array $class_generic_params,
        ?TemplateResult $template_result,
        bool $function_is_pure,
        bool $in_call_map
    ) {
        $codebase = $statements_analyzer->getCodebase();

        $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

        if (!$arg_value_type) {
            if ($function_param && !$function_param->by_ref) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                $param_type = $function_param->type;

                if ($function_param->is_variadic
                    && $param_type
                    && $param_type->hasArray()
                ) {
                    /**
                     * @psalm-suppress PossiblyUndefinedStringArrayOffset
                     * @var TList|TArray
                     */
                    $array_type = $param_type->getAtomicTypes()['array'];

                    if ($array_type instanceof TList) {
                        $param_type = $array_type->type_param;
                    } else {
                        $param_type = $array_type->type_params[1];
                    }
                }

                if ($param_type && !$param_type->hasMixed()) {
                    if (IssueBuffer::accepts(
                        new MixedArgument(
                            'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                . ' cannot be mixed, expecting ' . $param_type,
                            new CodeLocation($statements_analyzer->getSource(), $arg->value),
                            $cased_method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }
            }

            return;
        }

        if (!$function_param) {
            return;
        }

        if (self::checkFunctionLikeTypeMatches(
            $statements_analyzer,
            $codebase,
            $cased_method_id,
            $self_fq_class_name,
            $static_fq_class_name,
            $function_location,
            $function_param,
            $arg_value_type,
            $argument_offset,
            $arg,
            $context,
            $class_generic_params,
            $template_result,
            $function_is_pure,
            $in_call_map
        ) === false) {
            return false;
        }
    }

    /**
     * @param  string|null $method_id
     * @param  string|null $cased_method_id
     * @param  FunctionLikeParameter|null $last_param
     * @param  array<int, FunctionLikeParameter> $function_params
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
        ?TemplateResult $template_result
    ) {
        if ($arg->value instanceof PhpParser\Node\Scalar
            || $arg->value instanceof PhpParser\Node\Expr\Cast
            || $arg->value instanceof PhpParser\Node\Expr\Array_
            || $arg->value instanceof PhpParser\Node\Expr\ClassConstFetch
            || $arg->value instanceof PhpParser\Node\Expr\BinaryOp
            || $arg->value instanceof PhpParser\Node\Expr\Ternary
            || (
                (
                $arg->value instanceof PhpParser\Node\Expr\ConstFetch
                    || $arg->value instanceof PhpParser\Node\Expr\FuncCall
                    || $arg->value instanceof PhpParser\Node\Expr\MethodCall
                    || $arg->value instanceof PhpParser\Node\Expr\StaticCall
                ) && (
                    !($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                    || !$arg_value_type->by_ref
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
                // fall through
            }

            return false;
        }

        if (!in_array(
            $method_id,
            [
                'ksort', 'asort', 'krsort', 'arsort', 'natcasesort', 'natsort',
                'reset', 'end', 'next', 'prev', 'array_pop', 'array_shift',
                'array_push', 'array_unshift', 'socket_select', 'array_splice',
            ],
            true
        )) {
            $by_ref_type = null;
            $by_ref_out_type = null;

            $check_null_ref = true;

            if ($last_param) {
                if ($argument_offset < count($function_params)) {
                    $function_param = $function_params[$argument_offset];
                } else {
                    $function_param = $last_param;
                }

                $by_ref_type = $function_param->type;

                if (isset($function_storage->param_out_types[$argument_offset])) {
                    $by_ref_out_type = $function_storage->param_out_types[$argument_offset];
                } elseif ($argument_offset >= count($function_params)
                    && isset($function_storage->param_out_types[count($function_params) - 1])
                ) {
                    $by_ref_out_type = $function_storage->param_out_types[count($function_params) - 1];
                }

                if ($by_ref_type && $by_ref_type->isNullable()) {
                    $check_null_ref = false;
                }

                if ($template_result && $by_ref_type) {
                    $original_by_ref_type = clone $by_ref_type;

                    $by_ref_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        clone $by_ref_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $statements_analyzer->node_data->getType($arg->value),
                        $argument_offset,
                        'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
                    );

                    if ($template_result->upper_bounds) {
                        $original_by_ref_type->replaceTemplateTypesWithArgTypes(
                            $template_result,
                            $codebase
                        );

                        $by_ref_type = $original_by_ref_type;
                    }
                }

                if ($template_result && $by_ref_out_type) {
                    $original_by_ref_out_type = clone $by_ref_out_type;

                    $by_ref_out_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                        clone $by_ref_out_type,
                        $template_result,
                        $codebase,
                        $statements_analyzer,
                        $statements_analyzer->node_data->getType($arg->value),
                        $argument_offset,
                        'fn-' . ($context->calling_method_id ?: $context->calling_function_id)
                    );

                    if ($template_result->upper_bounds) {
                        $original_by_ref_out_type->replaceTemplateTypesWithArgTypes(
                            $template_result,
                            $codebase
                        );

                        $by_ref_out_type = $original_by_ref_out_type;
                    }
                }

                if ($by_ref_type && $function_param->is_variadic && $arg->unpack) {
                    $by_ref_type = new Type\Union([
                        new Type\Atomic\TArray([
                            Type::getInt(),
                            $by_ref_type,
                        ]),
                    ]);
                }
            }

            $by_ref_type = $by_ref_type ?: Type::getMixed();

            ExpressionAnalyzer::assignByRefParam(
                $statements_analyzer,
                $arg->value,
                $by_ref_type,
                $by_ref_out_type ?: $by_ref_type,
                $context,
                $method_id && (strpos($method_id, '::') !== false || !CallMap::inCallMap($method_id)),
                $check_null_ref
            );
        }
    }

    /**
     * @param  ?string $self_fq_class_name
     * @param  ?string $static_fq_class_name
     * @param  array<string, array<string, array{Type\Union, 1?:int}>> $class_generic_params
     * @param  array<string, array<string, array{Type\Union, 1?:int}>> $generic_params
     * @param  array<string, array<string, array{Type\Union}>> $template_types
     * @return false|null
     */
    private static function checkFunctionLikeTypeMatches(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        ?string $cased_method_id,
        ?string $self_fq_class_name,
        ?string $static_fq_class_name,
        CodeLocation $function_location,
        FunctionLikeParameter $function_param,
        Type\Union $arg_type,
        int $argument_offset,
        PhpParser\Node\Arg $arg,
        Context $context,
        ?array $class_generic_params,
        ?TemplateResult $template_result,
        bool $function_is_pure,
        bool $in_call_map
    ) {
        if (!$function_param->type) {
            if (!$codebase->infer_types_from_usage) {
                return;
            }

            $param_type = Type::getMixed();
        } else {
            $param_type = clone $function_param->type;
        }

        $bindable_template_params = [];

        if ($template_result) {
            $bindable_template_params = $param_type->getTemplateTypes();
        }

        if ($class_generic_params) {
            $empty_generic_params = [];

            $empty_template_result = new TemplateResult($class_generic_params, $empty_generic_params);

            $arg_value_type = $statements_analyzer->node_data->getType($arg->value);

            $param_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $param_type,
                $empty_template_result,
                $codebase,
                $statements_analyzer,
                $arg_value_type,
                $argument_offset,
                $context->self ?: 'fn-' . $context->calling_function_id
            );

            $arg_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $arg_type,
                $empty_template_result,
                $codebase,
                $statements_analyzer,
                $arg_value_type,
                $argument_offset,
                $context->self ?: 'fn-' . $context->calling_function_id
            );
        }

        if ($template_result && $template_result->template_types) {
            $arg_type_param = $arg_type;

            if ($arg->unpack) {
                $arg_type_param = null;

                foreach ($arg_type->getAtomicTypes() as $arg_atomic_type) {
                    if ($arg_atomic_type instanceof Type\Atomic\TArray
                        || $arg_atomic_type instanceof Type\Atomic\TList
                        || $arg_atomic_type instanceof Type\Atomic\ObjectLike
                    ) {
                        if ($arg_atomic_type instanceof Type\Atomic\ObjectLike) {
                            $arg_type_param = $arg_atomic_type->getGenericValueType();
                        } elseif ($arg_atomic_type instanceof Type\Atomic\TList) {
                            $arg_type_param = $arg_atomic_type->type_param;
                        } else {
                            $arg_type_param = $arg_atomic_type->type_params[1];
                        }
                    } elseif ($arg_atomic_type instanceof Type\Atomic\TIterable) {
                        $arg_type_param = $arg_atomic_type->type_params[1];
                    } elseif ($arg_atomic_type instanceof Type\Atomic\TNamedObject) {
                        ForeachAnalyzer::getKeyValueParamsForTraversableObject(
                            $arg_atomic_type,
                            $codebase,
                            $key_type,
                            $arg_type_param
                        );
                    }
                }

                if (!$arg_type_param) {
                    $arg_type_param = Type::getMixed();
                }
            }

            $param_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $param_type,
                $template_result,
                $codebase,
                $statements_analyzer,
                $arg_type_param,
                $argument_offset,
                $context->self,
                $context->calling_method_id ?: $context->calling_function_id
            );

            foreach ($bindable_template_params as $template_type) {
                if (!isset(
                    $template_result->upper_bounds
                        [$template_type->param_name]
                        [$template_type->defining_class]
                )
                    && !isset(
                        $template_result->lower_bounds
                        [$template_type->param_name]
                        [$template_type->defining_class]
                    )
                ) {
                    $template_result->upper_bounds[$template_type->param_name][$template_type->defining_class] = [
                        clone $template_type->as,
                        0
                    ];
                }
            }
        }

        if (!$context->check_variables) {
            return;
        }

        $parent_class = null;

        $classlike_storage = null;
        $static_classlike_storage = null;

        if ($self_fq_class_name) {
            $classlike_storage = $codebase->classlike_storage_provider->get($self_fq_class_name);
            $parent_class = $classlike_storage->parent_class;
            $static_classlike_storage = $classlike_storage;

            if ($static_fq_class_name && $static_fq_class_name !== $self_fq_class_name) {
                $static_classlike_storage = $codebase->classlike_storage_provider->get($static_fq_class_name);
            }
        }

        $fleshed_out_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
            $codebase,
            $param_type,
            $classlike_storage ? $classlike_storage->name : null,
            $static_classlike_storage ? $static_classlike_storage->name : null,
            $parent_class,
            true,
            false,
            $static_classlike_storage ? $static_classlike_storage->final : false
        );

        $fleshed_out_signature_type = $function_param->signature_type
            ? \Psalm\Internal\Type\TypeExpander::expandUnion(
                $codebase,
                $function_param->signature_type,
                $classlike_storage ? $classlike_storage->name : null,
                $static_classlike_storage ? $static_classlike_storage->name : null,
                $parent_class
            )
            : null;

        $unpacked_atomic_array = null;

        if ($arg->unpack) {
            if ($arg_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                if (IssueBuffer::accepts(
                    new MixedArgument(
                        'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                            . ' cannot be ' . $arg_type->getId() . ', expecting array',
                        new CodeLocation($statements_analyzer->getSource(), $arg->value),
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if ($arg_type->hasArray()) {
                /**
                 * @psalm-suppress PossiblyUndefinedStringArrayOffset
                 * @var Type\Atomic\TArray|Type\Atomic\TList|Type\Atomic\ObjectLike
                 */
                $unpacked_atomic_array = $arg_type->getAtomicTypes()['array'];

                if ($unpacked_atomic_array instanceof Type\Atomic\ObjectLike) {
                    if ($unpacked_atomic_array->is_list
                        && isset($unpacked_atomic_array->properties[$argument_offset])
                    ) {
                        $arg_type = clone $unpacked_atomic_array->properties[$argument_offset];
                    } else {
                        $arg_type = $unpacked_atomic_array->getGenericValueType();
                    }
                } elseif ($unpacked_atomic_array instanceof Type\Atomic\TList) {
                    $arg_type = $unpacked_atomic_array->type_param;
                } else {
                    $arg_type = $unpacked_atomic_array->type_params[1];
                }
            } else {
                foreach ($arg_type->getAtomicTypes() as $atomic_type) {
                    if (!$atomic_type->isIterable($codebase)) {
                        if (IssueBuffer::accepts(
                            new InvalidArgument(
                                'Argument ' . ($argument_offset + 1) . ' of ' . $cased_method_id
                                    . ' expects array, ' . $atomic_type->getId() . ' provided',
                                new CodeLocation($statements_analyzer->getSource(), $arg->value),
                                $cased_method_id
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        continue;
                    }
                }

                return;
            }
        }

        if (self::checkFunctionArgumentType(
            $statements_analyzer,
            $arg_type,
            $fleshed_out_type,
            $fleshed_out_signature_type,
            $cased_method_id,
            $argument_offset,
            new CodeLocation($statements_analyzer->getSource(), $arg->value),
            $arg->value,
            $context,
            $function_param,
            $arg->unpack,
            $unpacked_atomic_array,
            $function_is_pure,
            $in_call_map,
            $function_location
        ) === false) {
            return false;
        }
    }

    /**
     * @return array<string, array<string, array{Type\Union}>>
     * @param array<string, non-empty-array<string, array{Type\Union}>> $existing_template_types
     */
    public static function getTemplateTypesForCall(
        ClassLikeStorage $declaring_class_storage = null,
        ClassLikeStorage $calling_class_storage = null,
        array $existing_template_types = []
    ) : array {
        $template_types = $existing_template_types;

        if ($declaring_class_storage) {
            if ($calling_class_storage
                && $declaring_class_storage !== $calling_class_storage
                && $calling_class_storage->template_type_extends
            ) {
                foreach ($calling_class_storage->template_type_extends as $class_name => $type_map) {
                    foreach ($type_map as $template_name => $type) {
                        if (is_string($template_name) && $class_name === $declaring_class_storage->name) {
                            $output_type = null;

                            foreach ($type->getAtomicTypes() as $atomic_type) {
                                if ($atomic_type instanceof Type\Atomic\TTemplateParam
                                    && isset(
                                        $calling_class_storage
                                            ->template_type_extends
                                                [$atomic_type->defining_class]
                                                [$atomic_type->param_name]
                                    )
                                ) {
                                    $output_type_candidate = $calling_class_storage
                                        ->template_type_extends
                                            [$atomic_type->defining_class]
                                            [$atomic_type->param_name];
                                } elseif ($atomic_type instanceof Type\Atomic\TTemplateParam) {
                                    $output_type_candidate = $atomic_type->as;
                                } else {
                                    $output_type_candidate = new Type\Union([$atomic_type]);
                                }

                                if (!$output_type) {
                                    $output_type = $output_type_candidate;
                                } else {
                                    $output_type = Type::combineUnionTypes(
                                        $output_type_candidate,
                                        $output_type
                                    );
                                }
                            }

                            $template_types[$template_name][$declaring_class_storage->name] = [$output_type];
                        }
                    }
                }
            } elseif ($declaring_class_storage->template_types) {
                foreach ($declaring_class_storage->template_types as $template_name => $type_map) {
                    foreach ($type_map as $key => list($type)) {
                        $template_types[$template_name][$key] = [$type];
                    }
                }
            }
        }

        foreach ($template_types as $key => $type_map) {
            foreach ($type_map as $class => $type) {
                $template_types[$key][$class][0] = clone $type[0];
            }
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
        Context $context,
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

            /**
             * @psalm-suppress PossiblyUndefinedStringArrayOffset
             * @var ObjectLike|TArray|TList|null
             */
            $array_arg_type = ($arg_value_type = $statements_analyzer->node_data->getType($arg->value))
                    && ($types = $arg_value_type->getAtomicTypes())
                    && isset($types['array'])
                ? $types['array']
                : null;

            if ($array_arg_type instanceof ObjectLike) {
                $array_arg_type = $array_arg_type->getGenericArrayType();
            }

            if ($array_arg_type instanceof TList) {
                $array_arg_type = new TArray([Type::getInt(), $array_arg_type->type_param]);
            }

            $array_arg_types[] = $array_arg_type;
        }

        $closure_arg = isset($args[$closure_index]) ? $args[$closure_index] : null;

        $closure_arg_type = null;

        if ($closure_arg) {
            $closure_arg_type = $statements_analyzer->node_data->getType($closure_arg->value);
        }

        if ($closure_arg && $closure_arg_type) {
            $min_closure_param_count = $max_closure_param_count = count($array_arg_types);

            if ($method_id === 'array_filter') {
                $max_closure_param_count = count($args) > 2 ? 2 : 1;
            }

            foreach ($closure_arg_type->getAtomicTypes() as $closure_type) {
                self::checkArrayFunctionClosureType(
                    $statements_analyzer,
                    $context,
                    $method_id,
                    $closure_type,
                    $closure_arg,
                    $min_closure_param_count,
                    $max_closure_param_count,
                    $array_arg_types,
                    $check_functions
                );
            }
        }
    }

    /**
     * @param  string   $method_id
     * @param  int      $min_closure_param_count
     * @param  int      $max_closure_param_count [description]
     * @param  (TArray|null)[] $array_arg_types
     *
     * @return void
     */
    private static function checkArrayFunctionClosureType(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        $method_id,
        Type\Atomic $closure_type,
        PhpParser\Node\Arg $closure_arg,
        $min_closure_param_count,
        $max_closure_param_count,
        array $array_arg_types,
        bool $check_functions
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if (!$closure_type instanceof Type\Atomic\TFn) {
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

                        $function_id_part = new \Psalm\Internal\MethodIdentifier(
                            $callable_fq_class_name,
                            $method_name
                        );

                        try {
                            $method_storage = $codebase->methods->getStorage($function_id_part);
                        } catch (\UnexpectedValueException $e) {
                            // the method may not exist, but we're suppressing that issue
                            continue;
                        }

                        $closure_types[] = new Type\Atomic\TFn(
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
                        $callmap_callables = CallMap::getCallablesFromCallMap($function_id);

                        if ($callmap_callables === null) {
                            throw new \UnexpectedValueException('This should not happen');
                        }

                        $passing_callmap_callables = [];

                        foreach ($callmap_callables as $callmap_callable) {
                            $required_param_count = 0;

                            assert($callmap_callable->params !== null);

                            foreach ($callmap_callable->params as $i => $param) {
                                if (!$param->is_optional && !$param->is_variadic) {
                                    $required_param_count = $i + 1;
                                }
                            }

                            if ($required_param_count <= $max_closure_param_count) {
                                $passing_callmap_callables[] = $callmap_callable;
                            }
                        }

                        if ($passing_callmap_callables) {
                            foreach ($passing_callmap_callables as $passing_callmap_callable) {
                                $closure_types[] = $passing_callmap_callable;
                            }
                        } else {
                            $closure_types[] = $callmap_callables[0];
                        }
                    } else {
                        $closure_types[] = new Type\Atomic\TFn(
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

            self::checkArrayFunctionClosureTypeArgs(
                $statements_analyzer,
                $context,
                $method_id,
                $closure_type,
                $closure_arg,
                $min_closure_param_count,
                $max_closure_param_count,
                $array_arg_types
            );
        }
    }

    /**
     * @param  Type\Atomic\TFn|Type\Atomic\TCallable $closure_type
     * @param  string   $method_id
     * @param  int      $min_closure_param_count
     * @param  int      $max_closure_param_count
     * @param  (TArray|null)[] $array_arg_types
     *
     * @return void
     */
    private static function checkArrayFunctionClosureTypeArgs(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        $method_id,
        Type\Atomic $closure_type,
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
                // fall through
            }

            return;
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
                // fall through
            }

            return;
        }

        // abandon attempt to validate closure params if we have an extra arg for ARRAY_FILTER
        if ($method_id === 'array_filter' && $max_closure_param_count > 1) {
            return;
        }

        foreach ($closure_params as $i => $closure_param) {
            if (!isset($array_arg_types[$i])) {
                continue;
            }

            $array_arg_type = $array_arg_types[$i];

            $input_type = $array_arg_type->type_params[1];

            if ($input_type->hasMixed()) {
                continue;
            }

            $closure_param_type = $closure_param->type;

            if (!$closure_param_type) {
                continue;
            }

            if ($method_id === 'array_map'
                && $i === 0
                && $closure_type->return_type
                && $closure_param_type->hasTemplate()
            ) {
                $closure_param_type = clone $closure_param_type;
                $closure_type->return_type = clone $closure_type->return_type;

                $template_result = new \Psalm\Internal\Type\TemplateResult(
                    [],
                    []
                );

                foreach ($closure_param_type->getTemplateTypes() as $template_type) {
                    $template_result->template_types[$template_type->param_name] = [
                        ($template_type->defining_class) => [$template_type->as]
                    ];
                }

                $closure_param_type = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                    $closure_param_type,
                    $template_result,
                    $codebase,
                    $statements_analyzer,
                    $input_type,
                    $i,
                    $context->self,
                    $context->calling_method_id ?: $context->calling_function_id
                );

                $closure_type->return_type->replaceTemplateTypesWithArgTypes(
                    $template_result,
                    $codebase
                );
            }

            $union_comparison_results = new \Psalm\Internal\Analyzer\TypeComparisonResult();

            $type_match_found = TypeAnalyzer::isContainedBy(
                $codebase,
                $input_type,
                $closure_param_type,
                $input_type->ignore_nullable_issues,
                $input_type->ignore_falsable_issues,
                $union_comparison_results
            );

            if ($union_comparison_results->type_coerced) {
                if ($union_comparison_results->type_coerced_from_mixed) {
                    if (IssueBuffer::accepts(
                        new MixedArgumentTypeCoercion(
                            'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                $closure_param_type->getId() . ', parent type ' . $input_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg),
                            $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                } else {
                    if (IssueBuffer::accepts(
                        new ArgumentTypeCoercion(
                            'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                $closure_param_type->getId() . ', parent type ' . $input_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg),
                            $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // keep soldiering on
                    }
                }
            }

            if (!$union_comparison_results->type_coerced && !$type_match_found) {
                $types_can_be_identical = TypeAnalyzer::canExpressionTypesBeIdentical(
                    $codebase,
                    $input_type,
                    $closure_param_type
                );

                if ($union_comparison_results->scalar_type_match_found) {
                    if (IssueBuffer::accepts(
                        new InvalidScalarArgument(
                            'First parameter of closure passed to function ' . $method_id . ' expects ' .
                                $closure_param_type->getId() . ', ' . $input_type->getId() . ' provided',
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg),
                            $method_id
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
                            new CodeLocation($statements_analyzer->getSource(), $closure_arg),
                            $method_id
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif (IssueBuffer::accepts(
                    new InvalidArgument(
                        'First parameter of closure passed to function ' . $method_id . ' expects ' .
                            $closure_param_type->getId() . ', ' . $input_type->getId() . ' provided',
                        new CodeLocation($statements_analyzer->getSource(), $closure_arg),
                        $method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param Type\Atomic\ObjectLike|Type\Atomic\TArray|Type\Atomic\TList $unpacked_atomic_array
     * @return  null|false
     */
    public static function checkFunctionArgumentType(
        StatementsAnalyzer $statements_analyzer,
        Type\Union $input_type,
        Type\Union $param_type,
        ?Type\Union $signature_param_type,
        ?string $cased_method_id,
        int $argument_offset,
        CodeLocation $code_location,
        PhpParser\Node\Expr $input_expr,
        Context $context,
        FunctionLikeParameter $function_param,
        bool $unpack,
        ?Type\Atomic $unpacked_atomic_array,
        bool $function_is_pure,
        bool $in_call_map,
        CodeLocation $function_location
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if ($param_type->hasMixed()) {
            if ($codebase->infer_types_from_usage
                && !$input_type->hasMixed()
                && !$param_type->from_docblock
                && !$param_type->had_template
                && $cased_method_id
                && strpos($cased_method_id, '::')
                && !strpos($cased_method_id, '__')
            ) {
                $method_parts = explode('::', $cased_method_id);

                $method_id = new \Psalm\Internal\MethodIdentifier($method_parts[0], strtolower($method_parts[1]));
                $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if ($declaring_method_id) {
                    $id_lc = strtolower((string) $declaring_method_id);
                    if (!isset($codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset])) {
                        $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset]
                            = clone $input_type;
                    } else {
                        $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset]
                            = Type::combineUnionTypes(
                                $codebase->analyzer->possible_method_param_types[$id_lc][$argument_offset],
                                clone $input_type,
                                $codebase
                            );
                    }
                }
            }

            if ($cased_method_id) {
                self::processTaintedness(
                    $statements_analyzer,
                    $cased_method_id,
                    $argument_offset,
                    $code_location,
                    $function_location,
                    $function_param,
                    $input_type,
                    $function_is_pure
                );
            }

            return null;
        }

        $method_identifier = $cased_method_id ? ' of ' . $cased_method_id : '';

        if ($input_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            if (IssueBuffer::accepts(
                new MixedArgument(
                    'Argument ' . ($argument_offset + 1) . $method_identifier
                        . ' cannot be ' . $input_type->getId() . ', expecting ' .
                        $param_type,
                    $code_location,
                    $cased_method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            if ($input_type->isMixed()) {
                if (!$function_param->by_ref
                    && !($function_param->is_variadic xor $unpack)
                    && $cased_method_id !== 'echo'
                    && $cased_method_id !== 'print'
                    && (!$in_call_map || $context->strict_types)
                ) {
                    self::coerceValueAfterGatekeeperArgument(
                        $statements_analyzer,
                        $input_type,
                        false,
                        $input_expr,
                        $param_type,
                        $signature_param_type,
                        $context,
                        $unpack,
                        $unpacked_atomic_array
                    );
                }
            }

            if ($cased_method_id) {
                self::processTaintedness(
                    $statements_analyzer,
                    $cased_method_id,
                    $argument_offset,
                    $code_location,
                    $function_location,
                    $function_param,
                    $input_type,
                    $function_is_pure
                );
            }

            if ($input_type->isMixed()) {
                return null;
            }
        }

        if ($input_type->isNever()) {
            if (IssueBuffer::accepts(
                new NoValue(
                    'This function or method call never returns output',
                    $code_location
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return null;
        }

        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
        }

        if ($function_param->by_ref) {
            $param_type->possibly_undefined = true;
        }

        $param_type = TypeAnalyzer::simplifyUnionType(
            $codebase,
            $param_type
        );

        $union_comparison_results = new \Psalm\Internal\Analyzer\TypeComparisonResult();

        $type_match_found = TypeAnalyzer::isContainedBy(
            $codebase,
            $input_type,
            $param_type,
            true,
            true,
            $union_comparison_results
        );

        $replace_input_type = false;

        if ($union_comparison_results->replacement_union_type) {
            $replace_input_type = true;
            $input_type = $union_comparison_results->replacement_union_type;
        }

        if ($cased_method_id) {
            $old_input_type = $input_type;

            self::processTaintedness(
                $statements_analyzer,
                $cased_method_id,
                $argument_offset,
                $code_location,
                $function_location,
                $function_param,
                $input_type,
                $function_is_pure
            );

            if ($old_input_type !== $input_type) {
                $replace_input_type = true;
            }
        }

        if ($type_match_found
            && $param_type->hasCallableType()
        ) {
            $potential_method_ids = [];

            foreach ($input_type->getAtomicTypes() as $input_type_part) {
                if ($input_type_part instanceof Type\Atomic\ObjectLike) {
                    $potential_method_id = TypeAnalyzer::getCallableMethodIdFromObjectLike(
                        $input_type_part,
                        $codebase,
                        $context->calling_method_id,
                        $statements_analyzer->getFilePath()
                    );

                    if ($potential_method_id && $potential_method_id !== 'not-callable') {
                        $potential_method_ids[] = $potential_method_id;
                    }
                } elseif ($input_type_part instanceof Type\Atomic\TLiteralString
                    && strpos($input_type_part->value, '::')
                ) {
                    $parts = explode('::', $input_type_part->value);
                    $potential_method_ids[] = new \Psalm\Internal\MethodIdentifier(
                        $parts[0],
                        strtolower($parts[1])
                    );
                }
            }

            foreach ($potential_method_ids as $potential_method_id) {
                $codebase->methods->methodExists(
                    $potential_method_id,
                    $context->calling_method_id,
                    null,
                    $statements_analyzer,
                    $statements_analyzer->getFilePath()
                );
            }
        }

        if ($context->strict_types
            && !$input_type->hasArray()
            && !$param_type->from_docblock
            && $cased_method_id !== 'echo'
            && $cased_method_id !== 'print'
            && $cased_method_id !== 'sprintf'
        ) {
            $union_comparison_results->scalar_type_match_found = false;

            if ($union_comparison_results->to_string_cast) {
                $union_comparison_results->to_string_cast = false;
                $type_match_found = false;
            }
        }

        if ($union_comparison_results->type_coerced && !$input_type->hasMixed()) {
            if ($union_comparison_results->type_coerced_from_mixed) {
                if (IssueBuffer::accepts(
                    new MixedArgumentTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', parent type ' . $input_type->getId() . ' provided',
                        $code_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            } else {
                if (IssueBuffer::accepts(
                    new ArgumentTypeCoercion(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', parent type ' . $input_type->getId() . ' provided',
                        $code_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // keep soldiering on
                }
            }
        }

        if ($union_comparison_results->to_string_cast && $cased_method_id !== 'echo' && $cased_method_id !== 'print') {
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

        if (!$type_match_found && !$union_comparison_results->type_coerced) {
            $types_can_be_identical = TypeAnalyzer::canBeContainedBy(
                $codebase,
                $input_type,
                $param_type,
                true,
                true
            );

            if ($union_comparison_results->scalar_type_match_found) {
                if ($cased_method_id !== 'echo' && $cased_method_id !== 'print') {
                    if (IssueBuffer::accepts(
                        new InvalidScalarArgument(
                            'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' .
                                $param_type->getId() . ', ' . $input_type->getId() . ' provided',
                            $code_location,
                            $cased_method_id
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
                        $code_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } else {
                if (IssueBuffer::accepts(
                    new InvalidArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' expects ' . $param_type->getId() .
                            ', ' . $input_type->getId() . ' provided',
                        $code_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            return;
        }

        if ($input_expr instanceof PhpParser\Node\Scalar\String_
            || $input_expr instanceof PhpParser\Node\Expr\Array_
            || $input_expr instanceof PhpParser\Node\Expr\BinaryOp\Concat
        ) {
            foreach ($param_type->getAtomicTypes() as $param_type_part) {
                if ($param_type_part instanceof TClassString
                    && $input_expr instanceof PhpParser\Node\Scalar\String_
                ) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $input_expr->value,
                        $code_location,
                        $context->self,
                        $context->calling_method_id,
                        $statements_analyzer->getSuppressedIssues()
                    ) === false
                    ) {
                        return;
                    }
                } elseif ($param_type_part instanceof TArray
                    && $input_expr instanceof PhpParser\Node\Expr\Array_
                ) {
                    foreach ($param_type_part->type_params[1]->getAtomicTypes() as $param_array_type_part) {
                        if ($param_array_type_part instanceof TClassString) {
                            foreach ($input_expr->items as $item) {
                                if ($item && $item->value instanceof PhpParser\Node\Scalar\String_) {
                                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                        $statements_analyzer,
                                        $item->value->value,
                                        $code_location,
                                        $context->self,
                                        $context->calling_method_id,
                                        $statements_analyzer->getSuppressedIssues()
                                    ) === false
                                    ) {
                                        return;
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

                                if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                                    $statements_analyzer,
                                    $callable_fq_class_name,
                                    $code_location,
                                    $context->self,
                                    $context->calling_method_id,
                                    $statements_analyzer->getSuppressedIssues()
                                ) === false
                                ) {
                                    return;
                                }

                                $function_id_part = new \Psalm\Internal\MethodIdentifier(
                                    $callable_fq_class_name,
                                    strtolower($method_name)
                                );

                                $call_method_id = new \Psalm\Internal\MethodIdentifier(
                                    $callable_fq_class_name,
                                    '__call'
                                );

                                if (!$codebase->classOrInterfaceExists($callable_fq_class_name)) {
                                    return;
                                }

                                if (!$codebase->methods->methodExists($function_id_part)
                                    && !$codebase->methods->methodExists($call_method_id)
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
                                    return;
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
                                return;
                            }
                        }
                    }
                }
            }
        }

        if (!$param_type->isNullable() && $cased_method_id !== 'echo' && $cased_method_id !== 'print') {
            if ($input_type->isNull()) {
                if (IssueBuffer::accepts(
                    new NullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, ' .
                            'null value provided to parameter with type ' . $param_type->getId(),
                        $code_location,
                        $cased_method_id
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return null;
            }

            if ($input_type->isNullable() && !$input_type->ignore_nullable_issues) {
                if (IssueBuffer::accepts(
                    new PossiblyNullArgument(
                        'Argument ' . ($argument_offset + 1) . $method_identifier . ' cannot be null, possibly ' .
                            'null value provided',
                        $code_location,
                        $cased_method_id
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
                    $code_location,
                    $cased_method_id
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (($type_match_found || $input_type->hasMixed())
            && !$function_param->by_ref
            && !($function_param->is_variadic xor $unpack)
            && $cased_method_id !== 'echo'
            && $cased_method_id !== 'print'
            && (!$in_call_map || $context->strict_types)
        ) {
            self::coerceValueAfterGatekeeperArgument(
                $statements_analyzer,
                $input_type,
                $replace_input_type,
                $input_expr,
                $param_type,
                $signature_param_type,
                $context,
                $unpack,
                $unpacked_atomic_array
            );
        }

        return null;
    }

    private static function processTaintedness(
        StatementsAnalyzer $statements_analyzer,
        string $cased_method_id,
        int $argument_offset,
        CodeLocation $code_location,
        CodeLocation $function_location,
        FunctionLikeParameter $function_param,
        Type\Union &$input_type,
        bool $function_is_pure
    ) : void {
        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->taint) {
            return;
        }

        $method_sink = Sink::getForMethodArgument(
            $cased_method_id,
            $cased_method_id,
            $argument_offset,
            $code_location
        );

        $child_sink = null;

        if (($function_param->sink || ($child_sink = $codebase->taint->hasPreviousSink($method_sink, $suffixes)))
            && !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            && $input_type->sources
        ) {
            $all_possible_sinks = [];

            foreach ($input_type->sources as $source) {
                if ($codebase->taint->hasExistingSink($source)) {
                    continue;
                }

                $base_sink = new Sink(
                    $source->id,
                    $source->label,
                    $source->code_location
                );

                $base_sink->children = [$child_sink ?: $method_sink];

                $all_possible_sinks[] = $base_sink;

                if (strpos($source->id, '::') && strpos($source->id, '#')) {
                    list($fq_classlike_name, $method_name) = explode('::', $source->id);
                    list(, $cased_method_name) = explode('::', $source->label);

                    $method_name_parts = explode('#', $method_name);
                    list($cased_method_name) = explode('#', $cased_method_name);

                    $method_name = strtolower($method_name_parts[0]);

                    $class_storage = $codebase->classlike_storage_provider->get($fq_classlike_name);

                    foreach ($class_storage->dependent_classlikes as $dependent_classlike_lc => $_) {
                        $dependent_classlike_storage = $codebase->classlike_storage_provider->get(
                            $dependent_classlike_lc
                        );
                        $new_sink = Sink::getForMethodArgument(
                            $dependent_classlike_lc . '::' . $method_name,
                            $dependent_classlike_storage->name . '::' . $cased_method_name,
                            (int) $method_name_parts[1] - 1,
                            $code_location,
                            null
                        );

                        $new_sink->children = [$child_sink ?: $method_sink];

                        $new_sink->taint = $child_sink ? $child_sink->taint : $function_param->sink;

                        $all_possible_sinks[] = $new_sink;
                    }

                    if (isset($class_storage->overridden_method_ids[$method_name])) {
                        foreach ($class_storage->overridden_method_ids[$method_name] as $parent_method_id) {
                            $new_sink = Sink::getForMethodArgument(
                                (string) $parent_method_id,
                                $codebase->methods->getCasedMethodId($parent_method_id),
                                (int) $method_name_parts[1] - 1,
                                $code_location,
                                null
                            );

                            $new_sink->taint = $child_sink ? $child_sink->taint : $function_param->sink;
                            $new_sink->children = [$child_sink ?: $method_sink];

                            $all_possible_sinks[] = $new_sink;
                        }
                    }
                }
            }

            $codebase->taint->addSinks(
                $all_possible_sinks
            );
        }

        if ($function_param->sink
            && $input_type->tainted
            && ($function_param->sink & $input_type->tainted)
            && $input_type->sources
        ) {
            $method_sink = Sink::getForMethodArgument(
                $cased_method_id,
                $cased_method_id,
                $argument_offset,
                $code_location
            );

            $existing_sink = $codebase->taint->hasExistingSink($method_sink);

            foreach ($input_type->sources as $input_source) {
                $existing_source = $codebase->taint->hasExistingSource($input_source);

                if (IssueBuffer::accepts(
                    new TaintedInput(
                        'in path ' . $codebase->taint->getPredecessorPath($existing_source ?: $input_source)
                            . ' out path ' . $codebase->taint->getSuccessorPath($existing_sink ?: $method_sink),
                        $code_location
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }
        } elseif ($input_type->sources) {
            if ($function_is_pure) {
                $codebase->taint->addSpecialization(
                    strtolower($cased_method_id . '#' . ($argument_offset + 1)),
                    $function_location->file_name . ':' . $function_location->raw_file_start
                );
            }

            foreach ($input_type->sources as $type_source) {
                if (($previous_source = $codebase->taint->hasPreviousSource($type_source)) || $input_type->tainted) {
                    if ($function_is_pure) {
                        $method_source = Source::getForMethodArgument(
                            $cased_method_id,
                            $cased_method_id,
                            $argument_offset,
                            $code_location,
                            $function_location
                        );
                    } else {
                        $method_source = Source::getForMethodArgument(
                            $cased_method_id,
                            $cased_method_id,
                            $argument_offset,
                            $code_location
                        );
                    }

                    $method_source->taint = $input_type->tainted ?: 0;

                    $method_source->parents = [$previous_source ?: $type_source];

                    $codebase->taint->addSources(
                        [$method_source]
                    );
                }
            }
        } elseif ($input_type->tainted) {
            throw new \UnexpectedValueException(
                'sources should exist for tainted var in '
                    . $code_location->getShortSummary()
            );
        }

        if ($function_param->assert_untainted) {
            $input_type = clone $input_type;
            $input_type->tainted = null;
            $input_type->sources = [];
        }
    }

    /**
     * @param Type\Atomic\ObjectLike|Type\Atomic\TArray|Type\Atomic\TList $unpacked_atomic_array
     */
    private static function coerceValueAfterGatekeeperArgument(
        StatementsAnalyzer $statements_analyzer,
        Type\Union $input_type,
        bool $input_type_changed,
        PhpParser\Node\Expr $input_expr,
        Type\Union $param_type,
        ?Type\Union $signature_param_type,
        Context $context,
        bool $unpack,
        ?Type\Atomic $unpacked_atomic_array
    ) : void {
        if ($param_type->hasMixed()) {
            return;
        }

        if (!$input_type_changed && $param_type->from_docblock && !$input_type->hasMixed()) {
            $input_type = clone $input_type;

            foreach ($param_type->getAtomicTypes() as $param_atomic_type) {
                if ($param_atomic_type instanceof Type\Atomic\TGenericObject) {
                    foreach ($input_type->getAtomicTypes() as $input_atomic_type) {
                        if ($input_atomic_type instanceof Type\Atomic\TGenericObject
                            && $input_atomic_type->value === $param_atomic_type->value
                        ) {
                            foreach ($input_atomic_type->type_params as $i => $type_param) {
                                if ($type_param->isEmpty() && isset($param_atomic_type->type_params[$i])) {
                                    $input_type_changed = true;

                                    /** @psalm-suppress PropertyTypeCoercion */
                                    $input_atomic_type->type_params[$i] = clone $param_atomic_type->type_params[$i];
                                }
                            }
                        }
                    }
                }
            }

            if (!$input_type_changed) {
                return;
            }
        }

        $var_id = ExpressionAnalyzer::getVarId(
            $input_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        if ($var_id) {
            $was_cloned = false;

            if ($input_type->isNullable() && !$param_type->isNullable()) {
                $input_type = clone $input_type;
                $was_cloned = true;
                $input_type->removeType('null');
            }

            if ($input_type->getId() === $param_type->getId()) {
                if (!$was_cloned) {
                    $was_cloned = true;
                    $input_type = clone $input_type;
                }

                $input_type->from_docblock = false;

                foreach ($input_type->getAtomicTypes() as $atomic_type) {
                    $atomic_type->from_docblock = false;
                }
            } elseif ($input_type->hasMixed() && $signature_param_type) {
                $was_cloned = true;
                $input_type = clone $signature_param_type;

                if ($input_type->isNullable()) {
                    $input_type->ignore_nullable_issues = true;
                }
            }

            if ($context->inside_conditional) {
                $context->assigned_var_ids[$var_id] = true;
            }

            if ($was_cloned) {
                $context->removeVarFromConflictingClauses($var_id, null, $statements_analyzer);
            }

            if ($unpack) {
                if ($unpacked_atomic_array instanceof Type\Atomic\TList) {
                    $unpacked_atomic_array = clone $unpacked_atomic_array;
                    $unpacked_atomic_array->type_param = $input_type;

                    $context->vars_in_scope[$var_id] = new Type\Union([$unpacked_atomic_array]);
                } elseif ($unpacked_atomic_array instanceof Type\Atomic\TArray) {
                    $unpacked_atomic_array = clone $unpacked_atomic_array;
                    /** @psalm-suppress PropertyTypeCoercion */
                    $unpacked_atomic_array->type_params[1] = $input_type;

                    $context->vars_in_scope[$var_id] = new Type\Union([$unpacked_atomic_array]);
                } elseif ($unpacked_atomic_array instanceof Type\Atomic\ObjectLike
                    && $unpacked_atomic_array->is_list
                ) {
                    $unpacked_atomic_array = $unpacked_atomic_array->getList();
                    $unpacked_atomic_array->type_param = $input_type;

                    $context->vars_in_scope[$var_id] = new Type\Union([$unpacked_atomic_array]);
                } else {
                    $context->vars_in_scope[$var_id] = new Type\Union([
                        new TArray([
                            Type::getInt(),
                            $input_type
                        ]),
                    ]);
                }
            } else {
                $context->vars_in_scope[$var_id] = $input_type;
            }
        }
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
            $potential_id = preg_replace('/^\\\/', '', $callable_arg->value);

            if (preg_match('/^[A-Za-z0-9_]+(\\\[A-Za-z0-9_]+)*(::[A-Za-z0-9_]+)?$/', $potential_id)) {
                return [$potential_id];
            }

            return [];
        }

        if (count($callable_arg->items) !== 2) {
            return [];
        }

        /** @psalm-suppress PossiblyNullPropertyFetch */
        if ($callable_arg->items[0]->key || $callable_arg->items[1]->key) {
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

        $class_arg_type = null;

        if (!$file_source instanceof StatementsAnalyzer
            || !($class_arg_type = $file_source->node_data->getType($class_arg))
        ) {
            return [];
        }

        $method_ids = [];

        foreach ($class_arg_type->getAtomicTypes() as $type_part) {
            if ($type_part instanceof TNamedObject) {
                $method_id = $type_part->value . '::' . $method_name_arg->value;

                if ($type_part->extra_types) {
                    foreach ($type_part->extra_types as $extra_type) {
                        if ($extra_type instanceof Type\Atomic\TTemplateParam
                            || $extra_type instanceof Type\Atomic\TObjectWithProperties
                        ) {
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
     * @param  string $thisName
     * @param  array<int, PhpParser\Node\Arg> $args
     * @param  Context           $context
     * @param  array<string, array<string, array{Type\Union}>> $template_type_map,
     * @param  StatementsAnalyzer $statements_analyzer
     *
     * @return void
     */
    protected static function applyAssertionsToContext(
        $expr,
        ?string $thisName,
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
            } elseif ($assertion->var_id === '$this' && !is_null($thisName)) {
                $assertion_var_id = $thisName;
            } elseif (strpos($assertion->var_id, '$this->') === 0 && !is_null($thisName)) {
                $assertion_var_id = $thisName . str_replace('$this->', '->', $assertion->var_id);
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
                    foreach ($template_type_map[$rule] as $template_map) {
                        if ($template_map[0]->hasMixed()) {
                            continue 2;
                        }

                        $replacement_atomic_types = $template_map[0]->getAtomicTypes();

                        if (count($replacement_atomic_types) > 1) {
                            continue 2;
                        }

                        $ored_type_assertions = [];

                        foreach ($replacement_atomic_types as $replacement_atomic_type) {
                            if ($replacement_atomic_type instanceof Type\Atomic\TMixed) {
                                continue 3;
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
                            } elseif ($replacement_atomic_type instanceof Type\Atomic\TTemplateParam) {
                                $ored_type_assertions[] = $prefix . $replacement_atomic_type->param_name;
                            }
                        }

                        if ($ored_type_assertions) {
                            $type_assertions[$assertion_var_id] = [$ored_type_assertions];
                        }
                    }
                } else {
                    if (isset($type_assertions[$assertion_var_id])) {
                        $type_assertions[$assertion_var_id] = array_merge(
                            $type_assertions[$assertion_var_id],
                            $assertion->rule
                        );
                    } else {
                        $type_assertions[$assertion_var_id] = $assertion->rule;
                    }
                }
            } elseif ($arg_value && ($assertion->rule === [['!falsy']] || $assertion->rule === [['true']])) {
                if ($assertion->rule === [['true']]) {
                    $conditional = new PhpParser\Node\Expr\BinaryOp\Identical(
                        $arg_value,
                        new PhpParser\Node\Expr\ConstFetch(new PhpParser\Node\Name('true'))
                    );

                    $assert_clauses = \Psalm\Type\Algebra::getFormula(
                        \spl_object_id($conditional),
                        $conditional,
                        $context->self,
                        $statements_analyzer,
                        $statements_analyzer->getCodebase()
                    );
                } else {
                    $assert_clauses = \Psalm\Type\Algebra::getFormula(
                        \spl_object_id($arg_value),
                        $arg_value,
                        $context->self,
                        $statements_analyzer,
                        $statements_analyzer->getCodebase()
                    );
                }

                $simplified_clauses = \Psalm\Type\Algebra::simplifyCNF(
                    array_merge($context->clauses, $assert_clauses)
                );

                $assert_type_assertions = \Psalm\Type\Algebra::getTruthsFromFormula(
                    $simplified_clauses
                );

                $type_assertions = array_merge($type_assertions, $assert_type_assertions);
            }
        }

        $changed_var_ids = [];

        foreach ($type_assertions as $var_id => $_) {
            $asserted_keys[$var_id] = true;
        }

        if ($type_assertions) {
            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            $op_vars_in_scope = \Psalm\Type\Reconciler::reconcileKeyedTypes(
                $type_assertions,
                $type_assertions,
                $context->vars_in_scope,
                $changed_var_ids,
                $asserted_keys,
                $statements_analyzer,
                ($statements_analyzer->getTemplateTypeMap() ?: []) + $template_type_map,
                $context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $expr)
            );

            foreach ($changed_var_ids as $var_id => $_) {
                if (isset($op_vars_in_scope[$var_id])) {
                    $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

                    $codebase = $statements_analyzer->getCodebase();

                    if ($first_appearance
                        && isset($context->vars_in_scope[$var_id])
                        && $context->vars_in_scope[$var_id]->hasMixed()
                    ) {
                        if (!$context->collect_initializations
                            && !$context->collect_mutations
                            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                            && (!(($parent_source = $statements_analyzer->getSource())
                                        instanceof \Psalm\Internal\Analyzer\FunctionLikeAnalyzer)
                                    || !$parent_source->getSource() instanceof \Psalm\Internal\Analyzer\TraitAnalyzer)
                        ) {
                            $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                        }

                        IssueBuffer::remove(
                            $statements_analyzer->getFilePath(),
                            'MixedAssignment',
                            $first_appearance->raw_file_start
                        );
                    }

                    $op_vars_in_scope[$var_id]->from_docblock = true;

                    foreach ($op_vars_in_scope[$var_id]->getAtomicTypes() as $changed_atomic_type) {
                        $changed_atomic_type->from_docblock = true;

                        if ($changed_atomic_type instanceof Type\Atomic\TNamedObject
                            && $changed_atomic_type->extra_types
                        ) {
                            foreach ($changed_atomic_type->extra_types as $extra_type) {
                                $extra_type->from_docblock = true;
                            }
                        }
                    }
                }
            }

            $context->vars_in_scope = $op_vars_in_scope;
        }
    }

    public static function checkTemplateResult(
        StatementsAnalyzer $statements_analyzer,
        TemplateResult $template_result,
        CodeLocation $code_location,
        ?string $function_id
    ) : void {
        if ($template_result->upper_bounds && $template_result->lower_bounds) {
            foreach ($template_result->lower_bounds as $template_name => $defining_map) {
                foreach ($defining_map as $defining_id => list($lower_bound_type)) {
                    if (isset($template_result->upper_bounds[$template_name][$defining_id])) {
                        $upper_bound_type = $template_result->upper_bounds[$template_name][$defining_id][0];

                        $union_comparison_result = new \Psalm\Internal\Analyzer\TypeComparisonResult();

                        if (count($template_result->lower_bounds_unintersectable_types) > 1) {
                            $upper_bound_type = $template_result->lower_bounds_unintersectable_types[0];
                            $lower_bound_type = $template_result->lower_bounds_unintersectable_types[1];
                        }

                        if (!TypeAnalyzer::isContainedBy(
                            $statements_analyzer->getCodebase(),
                            $upper_bound_type,
                            $lower_bound_type,
                            false,
                            false,
                            $union_comparison_result
                        )) {
                            if ($union_comparison_result->type_coerced) {
                                if ($union_comparison_result->type_coerced_from_mixed) {
                                    if (IssueBuffer::accepts(
                                        new MixedArgumentTypeCoercion(
                                            'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                            $code_location,
                                            $function_id
                                        ),
                                        $statements_analyzer->getSuppressedIssues()
                                    )) {
                                        // continue
                                    }
                                } else {
                                    if (IssueBuffer::accepts(
                                        new ArgumentTypeCoercion(
                                            'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                            $code_location,
                                            $function_id
                                        ),
                                        $statements_analyzer->getSuppressedIssues()
                                    )) {
                                        // continue
                                    }
                                }
                            } elseif ($union_comparison_result->scalar_type_match_found) {
                                if (IssueBuffer::accepts(
                                    new InvalidScalarArgument(
                                        'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                        $code_location,
                                        $function_id
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // continue
                                }
                            } else {
                                if (IssueBuffer::accepts(
                                    new InvalidArgument(
                                        'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                        $code_location,
                                        $function_id
                                    ),
                                    $statements_analyzer->getSuppressedIssues()
                                )) {
                                    // continue
                                }
                            }
                        }
                    } else {
                        $template_result->upper_bounds[$template_name][$defining_id][0] = clone $lower_bound_type;
                    }
                }
            }
        }
    }
}
