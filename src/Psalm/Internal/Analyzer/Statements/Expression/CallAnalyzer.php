<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\FileSource;
use Psalm\Internal\Algebra;
use Psalm\Internal\Algebra\FormulaGenerator;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateBound;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\Issue\InvalidArgument;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InvalidScalarArgument;
use Psalm\Issue\MixedArgumentTypeCoercion;
use Psalm\Issue\TypeDoesNotContainType;
use Psalm\Issue\UndefinedFunction;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\BinaryOp\VirtualIdentical;
use Psalm\Node\Expr\VirtualConstFetch;
use Psalm\Node\VirtualName;
use Psalm\Storage\Assertion\Falsy;
use Psalm\Storage\Assertion\IsIdentical;
use Psalm\Storage\Assertion\IsType;
use Psalm\Storage\Assertion\Truthy;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\Possibilities;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Reconciler;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function assert;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_int;
use function is_numeric;
use function mt_rand;
use function preg_match;
use function preg_replace;
use function spl_object_id;
use function str_replace;
use function strpos;
use function strtolower;

/**
 * @internal
 */
class CallAnalyzer
{
    public static function collectSpecialInformation(
        FunctionLikeAnalyzer $source,
        string $method_name,
        Context $context
    ): void {
        $method_name_lc = strtolower($method_name);
        $fq_class_name = (string)$source->getFQCLN();

        $project_analyzer = $source->getFileAnalyzer()->project_analyzer;
        $codebase = $source->getCodebase();

        if ($context->collect_mutations &&
            $context->self &&
            (
                $context->self === $fq_class_name ||
                $codebase->classExtends(
                    $context->self,
                    $fq_class_name,
                )
            )
        ) {
            $method_id = new MethodIdentifier(
                $fq_class_name,
                $method_name_lc,
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
                    $source->getRootFileName(),
                );
            }
        } elseif ($context->collect_initializations &&
            $context->self &&
            (
                $context->self === $fq_class_name
                || $codebase->classlikes->classExtends(
                    $context->self,
                    $fq_class_name,
                )
            ) &&
            $source->getMethodName() !== $method_name
        ) {
            $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($method_id);

            if (isset($context->vars_in_scope['$this'])) {
                foreach ($context->vars_in_scope['$this']->getAtomicTypes() as $atomic_type) {
                    if ($atomic_type instanceof TNamedObject) {
                        if ($fq_class_name === $atomic_type->value) {
                            $alt_declaring_method_id = $declaring_method_id;
                        } else {
                            $fq_class_name = $atomic_type->value;

                            $method_id = new MethodIdentifier(
                                $fq_class_name,
                                $method_name_lc,
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
                                $method_id = new MethodIdentifier(
                                    $fq_class_name,
                                    $method_name_lc,
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

            $is_final = $method_storage->final;

            if ($method_name !== $declaring_method_id->method_name) {
                $appearing_method_id = $codebase->methods->getAppearingMethodId($method_id);

                if ($appearing_method_id) {
                    $appearing_class_storage = $codebase->classlike_storage_provider->get(
                        $appearing_method_id->fq_class_name,
                    );

                    if (isset($appearing_class_storage->trait_final_map[$method_name_lc])) {
                        $is_final = true;
                    }
                }
            }

            if ($class_analyzer instanceof ClassLikeAnalyzer
                && !$method_storage->is_static
                && ($context->collect_nonprivate_initializations
                    || $method_storage->visibility === ClassLikeAnalyzer::VISIBILITY_PRIVATE
                    || $is_final)
            ) {
                $local_vars_in_scope = [];

                foreach ($context->vars_in_scope as $var_id => $type) {
                    if (strpos($var_id, '$this->') === 0) {
                        if ($type->initialized) {
                            $local_vars_in_scope[$var_id] = $context->vars_in_scope[$var_id];

                            $context->remove($var_id, false);
                        }
                    } elseif ($var_id !== '$this') {
                        $local_vars_in_scope[$var_id] = $context->vars_in_scope[$var_id];
                    }
                }

                $local_vars_possibly_in_scope = $context->vars_possibly_in_scope;

                $old_calling_method_id = $context->calling_method_id;

                if ($fq_class_name === $source->getFQCLN()) {
                    $class_analyzer->getMethodMutations($declaring_method_id->method_name, $context);
                } else {
                    $declaring_fq_class_name = $declaring_method_id->fq_class_name;

                    $old_self = $context->self;
                    $context->self = $declaring_fq_class_name;
                    $project_analyzer->getMethodMutations(
                        $declaring_method_id,
                        $context,
                        $source->getRootFilePath(),
                        $source->getRootFileName(),
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
     * @param  list<PhpParser\Node\Arg>   $args
     */
    public static function checkMethodArgs(
        ?MethodIdentifier $method_id,
        array $args,
        TemplateResult $template_result,
        Context $context,
        CodeLocation $code_location,
        StatementsAnalyzer $statements_analyzer
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        if (!$method_id) {
            return ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $args,
                null,
                null,
                true,
                $context,
                $template_result,
            ) !== false;
        }

        $method_params = $codebase->methods->getMethodParams($method_id, $statements_analyzer, $args, $context);

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
                throw new UnexpectedValueException('Storage should not be empty here');
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

        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $args,
            $method_params,
            (string) $method_id,
            $method_storage->allow_named_arg_calls ?? true,
            $context,
            $template_result,
        ) === false) {
            return false;
        }

        if (ArgumentsAnalyzer::checkArgumentsMatch(
            $statements_analyzer,
            $args,
            $method_id,
            $method_params,
            $method_storage,
            $class_storage,
            $template_result,
            $code_location,
            $context,
        ) === false) {
            return false;
        }

        if ($template_result->template_types) {
            self::checkTemplateResult(
                $statements_analyzer,
                $template_result,
                $code_location,
                strtolower((string) $method_id),
            );
        }

        return true;
    }

    /**
     * This gets all the template params (and their types) that we think
     * we'll need to know about
     *
     * @return array<string, array<string, Union>>
     * @param array<string, non-empty-array<string, Union>> $existing_template_types
     * @param array<string, array<string, Union>> $class_template_params
     */
    public static function getTemplateTypesForCall(
        Codebase $codebase,
        ?ClassLikeStorage $declaring_class_storage,
        ?string $appearing_class_name,
        ?ClassLikeStorage $calling_class_storage,
        array $existing_template_types = [],
        array $class_template_params = []
    ): array {
        $template_types = $existing_template_types;

        if ($declaring_class_storage) {
            if ($calling_class_storage
                && $declaring_class_storage !== $calling_class_storage
                && $calling_class_storage->template_extended_params
            ) {
                foreach ($calling_class_storage->template_extended_params as $class_name => $type_map) {
                    foreach ($type_map as $template_name => $type) {
                        if ($class_name === $declaring_class_storage->name) {
                            $output_type = null;

                            foreach ($type->getAtomicTypes() as $atomic_type) {
                                if ($atomic_type instanceof TTemplateParam) {
                                    $output_type_candidate = self::getGenericParamForOffset(
                                        $atomic_type->defining_class,
                                        $atomic_type->param_name,
                                        $calling_class_storage->template_extended_params,
                                        $class_template_params + $template_types,
                                    );
                                } else {
                                    $output_type_candidate = new Union([$atomic_type]);
                                }

                                $output_type = Type::combineUnionTypes(
                                    $output_type_candidate,
                                    $output_type,
                                );
                            }

                            $template_types[$template_name][$declaring_class_storage->name] = $output_type;
                        }
                    }
                }
            } elseif ($declaring_class_storage->template_types) {
                foreach ($declaring_class_storage->template_types as $template_name => $type_map) {
                    foreach ($type_map as $key => $type) {
                        $template_types[$template_name][$key]
                            = $class_template_params[$template_name][$key] ?? $type;
                    }
                }
            }
        }

        foreach ($template_types as $key => $type_map) {
            foreach ($type_map as $class => $type) {
                $template_types[$key][$class] = TypeExpander::expandUnion(
                    $codebase,
                    $type,
                    $appearing_class_name,
                    $calling_class_storage->name ?? null,
                    null,
                    true,
                    false,
                    $calling_class_storage->final ?? false,
                );
            }
        }

        return $template_types;
    }

    /**
     * @param  array<string, array<string, Union>>  $template_extended_params
     * @param  array<string, array<string, Union>>  $found_generic_params
     */
    public static function getGenericParamForOffset(
        string $fq_class_name,
        string $template_name,
        array $template_extended_params,
        array $found_generic_params
    ): Union {
        if (isset($found_generic_params[$template_name][$fq_class_name])) {
            return $found_generic_params[$template_name][$fq_class_name];
        }

        foreach ($template_extended_params as $extended_class_name => $type_map) {
            foreach ($type_map as $extended_template_name => $extended_type) {
                foreach ($extended_type->getAtomicTypes() as $extended_atomic_type) {
                    if ($extended_atomic_type instanceof TTemplateParam
                        && $extended_atomic_type->param_name === $template_name
                        && $extended_atomic_type->defining_class === $fq_class_name
                    ) {
                        return self::getGenericParamForOffset(
                            $extended_class_name,
                            $extended_template_name,
                            $template_extended_params,
                            $found_generic_params,
                        );
                    }
                }
            }
        }

        return Type::getMixed();
    }

    /**
     * @param PhpParser\Node\Scalar\String_|PhpParser\Node\Expr\Array_|PhpParser\Node\Expr\BinaryOp\Concat $callable_arg
     * @return list<non-empty-string>
     */
    public static function getFunctionIdsFromCallableArg(
        FileSource $file_source,
        PhpParser\Node\Expr $callable_arg
    ): array {
        if ($callable_arg instanceof PhpParser\Node\Expr\BinaryOp\Concat) {
            if ($callable_arg->left instanceof PhpParser\Node\Expr\ClassConstFetch
                && $callable_arg->left->class instanceof Name
                && $callable_arg->left->name instanceof Identifier
                && strtolower($callable_arg->left->name->name) === 'class'
                && !in_array(strtolower($callable_arg->left->class->parts[0]), ['self', 'static', 'parent'])
                && $callable_arg->right instanceof PhpParser\Node\Scalar\String_
                && preg_match('/^::[A-Za-z0-9]+$/', $callable_arg->right->value)
            ) {
                $r = (string) $callable_arg->left->class->getAttribute('resolvedName') . $callable_arg->right->value;
                assert($r !== '');
                return [$r];
            }

            return [];
        }

        if ($callable_arg instanceof PhpParser\Node\Scalar\String_) {
            $potential_id = preg_replace('/^\\\/', '', $callable_arg->value, 1);

            if (preg_match('/^[A-Za-z0-9_]+(\\\[A-Za-z0-9_]+)*(::[A-Za-z0-9_]+)?$/', $potential_id)) {
                assert($potential_id !== '');
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
            throw new UnexpectedValueException('These should never be unset');
        }

        $class_arg = $callable_arg->items[0]->value;
        $method_name_arg = $callable_arg->items[1]->value;

        if (!$method_name_arg instanceof PhpParser\Node\Scalar\String_) {
            return [];
        }

        if ($class_arg instanceof PhpParser\Node\Scalar\String_) {
            return [preg_replace('/^\\\/', '', $class_arg->value, 1) . '::' . $method_name_arg->value];
        }

        if ($class_arg instanceof PhpParser\Node\Expr\ClassConstFetch
            && $class_arg->name instanceof Identifier
            && strtolower($class_arg->name->name) === 'class'
            && $class_arg->class instanceof Name
        ) {
            $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $class_arg->class,
                $file_source->getAliases(),
            );

            return [$fq_class_name . '::' . $method_name_arg->value];
        }

        if (!$file_source instanceof StatementsAnalyzer
            || !($class_arg_type = $file_source->node_data->getType($class_arg))
        ) {
            return [];
        }

        $method_ids = [];

        foreach ($class_arg_type->getAtomicTypes() as $type_part) {
            if ($type_part instanceof TNamedObject) {
                $method_id = $type_part->value . '::' . $method_name_arg->value;

                foreach ($type_part->extra_types as $extra_type) {
                    if ($extra_type instanceof TTemplateParam
                        || $extra_type instanceof TObjectWithProperties
                    ) {
                        throw new UnexpectedValueException('Shouldn’t get a generic param here');
                    }

                    $method_id .= '&' . $extra_type->value . '::' . $method_name_arg->value;
                }

                $method_ids[] = '$' . $method_id;
            }
        }

        return $method_ids;
    }

    /**
     * @param  non-empty-string     $function_id
     * @param  bool                 $can_be_in_root_scope if true, the function can be shortened to the root version
     */
    public static function checkFunctionExists(
        StatementsAnalyzer $statements_analyzer,
        string &$function_id,
        CodeLocation $code_location,
        bool $can_be_in_root_scope
    ): bool {
        $cased_function_id = $function_id;
        $function_id = strtolower($function_id);

        $codebase = $statements_analyzer->getCodebase();

        if (!$codebase->functions->functionExists($statements_analyzer, $function_id)) {
            /** @var non-empty-lowercase-string */
            $root_function_id = preg_replace('/.*\\\/', '', $function_id);

            if ($can_be_in_root_scope
                && $function_id !== $root_function_id
                && $codebase->functions->functionExists($statements_analyzer, $root_function_id)
            ) {
                $function_id = $root_function_id;
            } else {
                IssueBuffer::maybeAdd(
                    new UndefinedFunction(
                        'Function ' . $cased_function_id . ' does not exist',
                        $code_location,
                        $function_id,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return false;
            }
        }

        return true;
    }

    /**
     * @param Identifier|Name $expr
     * @param  Possibilities[] $var_assertions
     * @param  list<PhpParser\Node\Arg> $args
     */
    public static function applyAssertionsToContext(
        PhpParser\NodeAbstract $expr,
        ?string $thisName,
        array $var_assertions,
        array $args,
        TemplateResult $template_result,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ): void {
        $type_assertions = [];

        $asserted_keys = [];

        foreach ($var_assertions as $var_possibilities) {
            $assertion_var_id = null;

            $arg_value = null;

            if (is_int($var_possibilities->var_id)) {
                if (!isset($args[$var_possibilities->var_id])) {
                    continue;
                }

                $arg_value = $args[$var_possibilities->var_id]->value;

                $arg_var_id = ExpressionIdentifier::getExtendedVarId($arg_value, null, $statements_analyzer);

                if ($arg_var_id) {
                    $assertion_var_id = $arg_var_id;
                }
            } elseif ($var_possibilities->var_id === '$this' && $thisName !== null) {
                $assertion_var_id = $thisName;
            } elseif (strpos($var_possibilities->var_id, '$this->') === 0 && $thisName !== null) {
                $assertion_var_id = $thisName . str_replace('$this->', '->', $var_possibilities->var_id);
            } elseif (strpos($var_possibilities->var_id, 'self::') === 0 && $context->self) {
                $assertion_var_id = $context->self . str_replace('self::', '::', $var_possibilities->var_id);
            } elseif (strpos($var_possibilities->var_id, '::$') !== false) {
                // allow assertions to bring external static props into scope
                $assertion_var_id = $var_possibilities->var_id;
            } elseif (isset($context->vars_in_scope[$var_possibilities->var_id])) {
                $assertion_var_id = $var_possibilities->var_id;
            } elseif (strpos($var_possibilities->var_id, '->') !== false) {
                $exploded = explode('->', $var_possibilities->var_id);

                if (count($exploded) < 2) {
                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            'Assert notation is malformed',
                            new CodeLocation($statements_analyzer, $expr),
                        ),
                    );
                    continue;
                }

                [$var_id, $property] = $exploded;

                $var_id = is_numeric($var_id) ? (int) $var_id : $var_id;

                if (!is_int($var_id) || !isset($args[$var_id])) {
                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            'Variable ' . $var_id . ' is not an argument so cannot be asserted',
                            new CodeLocation($statements_analyzer, $expr),
                        ),
                    );
                    continue;
                }

                /** @var PhpParser\Node\Expr\Variable $arg_value */
                $arg_value = $args[$var_id]->value;

                $arg_var_id = ExpressionIdentifier::getExtendedVarId($arg_value, null, $statements_analyzer);

                if (!$arg_var_id) {
                    IssueBuffer::maybeAdd(
                        new InvalidDocblock(
                            'Variable being asserted as argument ' . ($var_id+1) .  ' cannot be found in local scope',
                            new CodeLocation($statements_analyzer, $expr),
                        ),
                    );
                    continue;
                }

                if (count($exploded) === 2) {
                    $failedMessage = AssertionFinder::isPropertyImmutableOnArgument(
                        $property,
                        $statements_analyzer->getNodeTypeProvider(),
                        $statements_analyzer->getCodebase()->classlike_storage_provider,
                        $arg_value,
                    );

                    if (null !== $failedMessage) {
                        IssueBuffer::maybeAdd(
                            new InvalidDocblock($failedMessage, new CodeLocation($statements_analyzer, $expr)),
                        );
                        continue;
                    }
                }

                $assertion_var_id = str_replace((string) $var_id, $arg_var_id, $var_possibilities->var_id);
            }

            $codebase = $statements_analyzer->getCodebase();

            if ($assertion_var_id) {
                $orred_rules = [];

                foreach ($var_possibilities->rule as $assertion_rule) {
                    $assertion_type_atomic = $assertion_rule->getAtomicType();

                    if ($assertion_type_atomic) {
                        $assertion_type = TemplateInferredTypeReplacer::replace(
                            new Union([$assertion_type_atomic]),
                            $template_result,
                            $codebase,
                        );

                        if (count($assertion_type->getAtomicTypes()) === 1) {
                            foreach ($assertion_type->getAtomicTypes() as $atomic_type) {
                                if ($assertion_type_atomic instanceof TTemplateParam
                                    && $assertion_type_atomic->as->getId() === $atomic_type->getId()
                                ) {
                                    continue;
                                }

                                $assertion_rule = $assertion_rule->setAtomicType($atomic_type);
                                $orred_rules[] = $assertion_rule;
                            }
                        } elseif (isset($context->vars_in_scope[$assertion_var_id])) {
                            $asserted_type = $context->vars_in_scope[$assertion_var_id];
                            if ($assertion_rule instanceof IsIdentical) {
                                $intersection = Type::intersectUnionTypes($assertion_type, $asserted_type, $codebase);

                                if ($intersection === null) {
                                    IssueBuffer::maybeAdd(
                                        new TypeDoesNotContainType(
                                            $asserted_type->getId() . ' is not contained by '
                                            . $assertion_type->getId(),
                                            new CodeLocation($statements_analyzer->getSource(), $expr),
                                            $asserted_type->getId() . ' ' . $assertion_type->getId(),
                                        ),
                                        $statements_analyzer->getSuppressedIssues(),
                                    );
                                    $intersection = Type::getNever();
                                } elseif ($intersection->getId(true) === $asserted_type->getId(true)) {
                                    continue;
                                }
                                foreach ($intersection->getAtomicTypes() as $atomic_type) {
                                    $orred_rules[] = new IsIdentical($atomic_type);
                                }
                            } elseif ($assertion_rule instanceof IsType) {
                                if (!UnionTypeComparator::canExpressionTypesBeIdentical(
                                    $codebase,
                                    $assertion_type,
                                    $asserted_type,
                                )) {
                                    IssueBuffer::maybeAdd(
                                        new TypeDoesNotContainType(
                                            $asserted_type->getId() . ' is not contained by '
                                            . $assertion_type->getId(),
                                            new CodeLocation($statements_analyzer->getSource(), $expr),
                                            $asserted_type->getId() . ' ' . $assertion_type->getId(),
                                        ),
                                        $statements_analyzer->getSuppressedIssues(),
                                    );
                                }
                            } else {
                                // Ignore negations and loose assertions with union types
                            }
                        }
                    } else {
                        $orred_rules[] = $assertion_rule;
                    }
                }

                if ($orred_rules) {
                    if (isset($type_assertions[$assertion_var_id])) {
                        $type_assertions[$assertion_var_id] = array_merge(
                            $type_assertions[$assertion_var_id],
                            [$orred_rules],
                        );
                    } else {
                        $type_assertions[$assertion_var_id] = [$orred_rules];
                    }
                }
            } elseif ($arg_value
                && count($var_possibilities->rule) === 1
            ) {
                $assert_clauses = [];

                $single_rule = $var_possibilities->rule[0];

                if ($single_rule instanceof Truthy) {
                    $assert_clauses = FormulaGenerator::getFormula(
                        spl_object_id($arg_value),
                        spl_object_id($arg_value),
                        $arg_value,
                        $context->self,
                        $statements_analyzer,
                        $statements_analyzer->getCodebase(),
                    );
                } elseif ($single_rule instanceof Falsy) {
                    $assert_clauses = Algebra::negateFormula(
                        FormulaGenerator::getFormula(
                            spl_object_id($arg_value),
                            spl_object_id($arg_value),
                            $arg_value,
                            $context->self,
                            $statements_analyzer,
                            $codebase,
                        ),
                    );
                } elseif ($single_rule instanceof IsType
                    && $single_rule->type instanceof TTrue
                ) {
                    $conditional = new VirtualIdentical(
                        $arg_value,
                        new VirtualConstFetch(new VirtualName('true')),
                    );

                    $assert_clauses = FormulaGenerator::getFormula(
                        mt_rand(0, 1_000_000),
                        mt_rand(0, 1_000_000),
                        $conditional,
                        $context->self,
                        $statements_analyzer,
                        $codebase,
                    );
                }

                $simplified_clauses = Algebra::simplifyCNF(
                    [...$context->clauses, ...$assert_clauses],
                );

                $assert_type_assertions = Algebra::getTruthsFromFormula(
                    $simplified_clauses,
                );

                $type_assertions = array_merge($type_assertions, $assert_type_assertions);
            }
        }

        $changed_var_ids = [];

        foreach ($type_assertions as $var_id => $_) {
            $asserted_keys[$var_id] = true;
        }

        $codebase = $statements_analyzer->getCodebase();

        if ($type_assertions) {
            $template_type_map = [];

            // while in an and, we allow scope to boil over to support
            // statements of the form if ($x && $x->foo())
            [$op_vars_in_scope, $op_references_in_scope] = Reconciler::reconcileKeyedTypes(
                $type_assertions,
                $type_assertions,
                $context->vars_in_scope,
                $context->references_in_scope,
                $changed_var_ids,
                $asserted_keys,
                $statements_analyzer,
                $template_type_map,
                $context->inside_loop,
                new CodeLocation($statements_analyzer->getSource(), $expr),
            );

            foreach ($changed_var_ids as $var_id => $_) {
                if (isset($op_vars_in_scope[$var_id])) {
                    $first_appearance = $statements_analyzer->getFirstAppearance($var_id);

                    if ($first_appearance
                        && isset($context->vars_in_scope[$var_id])
                        && $context->vars_in_scope[$var_id]->hasMixed()
                    ) {
                        if (!$context->collect_initializations
                            && !$context->collect_mutations
                            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                            && (!(($parent_source = $statements_analyzer->getSource())
                                        instanceof FunctionLikeAnalyzer)
                                    || !$parent_source->getSource() instanceof TraitAnalyzer)
                        ) {
                            $codebase->analyzer->decrementMixedCount($statements_analyzer->getFilePath());
                        }

                        IssueBuffer::remove(
                            $statements_analyzer->getFilePath(),
                            'MixedAssignment',
                            $first_appearance->raw_file_start,
                        );
                    }

                    $op_vars_in_scope[$var_id] = $op_vars_in_scope[$var_id]->setFromDocblock(true);
                }
            }

            $context->vars_in_scope = $op_vars_in_scope;
            $context->references_in_scope = $op_references_in_scope;
        }
    }

    /**
     * This method looks for problems with a generated TemplateResult.
     *
     * The TemplateResult object contains upper bounds and lower bounds for each template param.
     *
     * Those upper bounds represent a series of constraints like
     *
     * Lower bound:
     * T >: X (the type param T matches X, or is a supertype of X)
     * Upper bound:
     * T <: Y (the type param T matches Y, or is a subtype of Y)
     * Equality (currently represented as an upper bound with a special flag)
     * T = Z  (the template T must match Z)
     *
     * This method attempts to reconcile those constraints.
     *
     * Valid constraints:
     *
     * T <: int|float, T >: int --- implies T is an int
     * T = int --- implies T is an int
     *
     * Invalid constraints:
     *
     * T <: int|string, T >: string|float --- implies T <: int and T >: float, which is impossible
     * T = int, T = string --- implies T is a string _and_ and int, which is impossible
     */
    public static function checkTemplateResult(
        StatementsAnalyzer $statements_analyzer,
        TemplateResult $template_result,
        CodeLocation $code_location,
        ?string $function_id
    ): void {
        if ($template_result->lower_bounds && $template_result->upper_bounds) {
            foreach ($template_result->upper_bounds as $template_name => $defining_map) {
                foreach ($defining_map as $defining_id => $upper_bound) {
                    if (isset($template_result->lower_bounds[$template_name][$defining_id])) {
                        $lower_bound_type = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                            $template_result->lower_bounds[$template_name][$defining_id],
                            $statements_analyzer->getCodebase(),
                        );

                        $upper_bound_type = $upper_bound->type;

                        $union_comparison_result = new TypeComparisonResult();

                        if (count($template_result->upper_bounds_unintersectable_types) > 1) {
                            [$lower_bound_type, $upper_bound_type]
                                = $template_result->upper_bounds_unintersectable_types;
                        }

                        if (!UnionTypeComparator::isContainedBy(
                            $statements_analyzer->getCodebase(),
                            $lower_bound_type,
                            $upper_bound_type,
                            false,
                            false,
                            $union_comparison_result,
                        )) {
                            if ($union_comparison_result->type_coerced) {
                                if ($union_comparison_result->type_coerced_from_mixed) {
                                    IssueBuffer::maybeAdd(
                                        new MixedArgumentTypeCoercion(
                                            'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                            $code_location,
                                            $function_id,
                                        ),
                                        $statements_analyzer->getSuppressedIssues(),
                                    );
                                } else {
                                    IssueBuffer::maybeAdd(
                                        new ArgumentTypeCoercion(
                                            'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                            $code_location,
                                            $function_id,
                                        ),
                                        $statements_analyzer->getSuppressedIssues(),
                                    );
                                }
                            } elseif ($union_comparison_result->scalar_type_match_found) {
                                IssueBuffer::maybeAdd(
                                    new InvalidScalarArgument(
                                        'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                        $code_location,
                                        $function_id,
                                    ),
                                    $statements_analyzer->getSuppressedIssues(),
                                );
                            } else {
                                IssueBuffer::maybeAdd(
                                    new InvalidArgument(
                                        'Type ' . $lower_bound_type->getId() . ' should be a subtype of '
                                                . $upper_bound_type->getId(),
                                        $code_location,
                                        $function_id,
                                    ),
                                    $statements_analyzer->getSuppressedIssues(),
                                );
                            }
                        }
                    } else {
                        $template_result->lower_bounds[$template_name][$defining_id] = [
                            new TemplateBound(
                                $upper_bound->type,
                            ),
                        ];
                    }
                }
            }
        }

        // Attempt to identify invalid lower bounds
        foreach ($template_result->lower_bounds as $template_name => $lower_bounds) {
            foreach ($lower_bounds as $lower_bounds) {
                if (count($lower_bounds) > 1) {
                    $bounds_with_equality = array_filter(
                        $lower_bounds,
                        static fn($lower_bound): bool => (bool)$lower_bound->equality_bound_classlike,
                    );

                    if (!$bounds_with_equality) {
                        continue;
                    }

                    $equality_types = array_unique(
                        array_map(
                            static fn($bound_with_equality) => $bound_with_equality->type->getId(),
                            $bounds_with_equality,
                        ),
                    );

                    if (count($equality_types) > 1) {
                        IssueBuffer::maybeAdd(
                            new InvalidArgument(
                                'Incompatible types found for ' . $template_name . ' (must have only one of ' .
                                implode(', ', $equality_types) . ')',
                                $code_location,
                                $function_id,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    } else {
                        foreach ($lower_bounds as $lower_bound) {
                            if ($lower_bound->equality_bound_classlike === null) {
                                foreach ($bounds_with_equality as $bound_with_equality) {
                                    if (UnionTypeComparator::isContainedBy(
                                        $statements_analyzer->getCodebase(),
                                        $lower_bound->type,
                                        $bound_with_equality->type,
                                    )) {
                                        continue 2;
                                    }
                                }

                                IssueBuffer::maybeAdd(
                                    new InvalidArgument(
                                        'Incompatible types found for ' . $template_name . ' (' .
                                        $lower_bound->type->getId() . ' is not in ' .
                                        implode(', ', $equality_types) . ')',
                                        $code_location,
                                        $function_id,
                                    ),
                                    $statements_analyzer->getSuppressedIssues(),
                                );
                            }
                        }
                    }
                }
            }
        }
    }
}
