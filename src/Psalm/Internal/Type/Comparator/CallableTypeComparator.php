<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\Comparator;

use Exception;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Variable;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\NodeDataProvider;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TemplateStandinTypeReplacer;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TCallableInterface;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_slice;
use function count;
use function end;
use function strtolower;
use function substr;

/**
 * @internal
 */
final class CallableTypeComparator
{
    /**
     * @param  TCallable|TClosure   $container_type_part
     */
    public static function isContainedBy(
        Codebase $codebase,
        TClosure|TCallableInterface $input_type_part,
        Atomic $container_type_part,
        ?TypeComparisonResult $atomic_comparison_result,
    ): bool {
        if ($container_type_part instanceof TClosure) {
            if ($input_type_part instanceof TCallableInterface
                && !$input_type_part instanceof TCallable // it has stricter checks below
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                }
                return false;
            }
        }
        if ($input_type_part instanceof TCallableInterface
            && !$input_type_part instanceof TCallable // it has stricter checks below
        ) {
            return true;
        }

        if ($container_type_part->is_pure && !$input_type_part->is_pure) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = $input_type_part->is_pure === null;
            }

            return false;
        }

        if ($container_type_part->params !== null && $input_type_part->params === null) {
            if ($atomic_comparison_result) {
                $atomic_comparison_result->type_coerced = true;
                $atomic_comparison_result->type_coerced_from_mixed = true;
            }

            return false;
        }

        $input_variadic_param_idx = null;

        if ($input_type_part->params !== null && $container_type_part->params !== null) {
            foreach ($input_type_part->params as $i => $input_param) {
                $container_param = null;

                if (isset($container_type_part->params[$i])) {
                    $container_param = $container_type_part->params[$i];
                } elseif ($container_type_part->params) {
                    $last_param = end($container_type_part->params);

                    if ($last_param->is_variadic) {
                        $container_param = $last_param;
                    }
                }

                if ($input_param->is_variadic) {
                    $input_variadic_param_idx = $i;
                }

                if (!$container_param) {
                    if ($input_param->is_variadic) {
                        break;
                    }

                    if ($input_param->is_optional) {
                        break;
                    }

                    return false;
                }

                if ($container_param->type
                    && !$container_param->type->hasMixed()
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $container_param->type,
                        $input_param->type ?: Type::getMixed(),
                        false,
                        false,
                        $atomic_comparison_result,
                    )
                ) {
                    return false;
                }
            }
        }

        if ($input_variadic_param_idx && isset($input_type_part->params[$input_variadic_param_idx])) {
            $input_param = $input_type_part->params[$input_variadic_param_idx];

            foreach (array_slice($container_type_part->params ?? [], $input_variadic_param_idx) as $container_param) {
                if ($container_param->type
                    && !$container_param->type->hasMixed()
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $container_param->type,
                        $input_param->type ?: Type::getMixed(),
                        false,
                        false,
                        $atomic_comparison_result,
                    )
                ) {
                    return false;
                }
            }
        }

        if (isset($container_type_part->return_type)) {
            if (!isset($input_type_part->return_type)) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced = true;
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                }

                return false;
            }

            $input_return = $input_type_part->return_type;

            if ($input_return->isVoid() && $container_type_part->return_type->isNullable()) {
                return true;
            }

            if (!$container_type_part->return_type->isVoid()
                && !UnionTypeComparator::isContainedBy(
                    $codebase,
                    $input_return,
                    $container_type_part->return_type,
                    false,
                    false,
                    $atomic_comparison_result,
                )
            ) {
                return false;
            }
        }

        return true;
    }

    public static function isNotExplicitlyCallableTypeCallable(
        Codebase $codebase,
        Atomic $input_type_part,
        TCallable $container_type_part,
        ?TypeComparisonResult $atomic_comparison_result,
    ): bool {

        if ($input_type_part instanceof TArray) {
            if ($input_type_part->type_params[1]->isMixed()
                || $input_type_part->type_params[1]->hasScalar()
            ) {
                if ($atomic_comparison_result) {
                    $atomic_comparison_result->type_coerced_from_mixed = true;
                    $atomic_comparison_result->type_coerced = true;
                }

                return false;
            }

            if (!$input_type_part->type_params[1]->hasString()) {
                return false;
            }
        } elseif ($input_type_part instanceof TKeyedArray) {
            $method_id = self::getCallableMethodIdFromTKeyedArray($input_type_part);

            if ($method_id === 'not-callable') {
                return false;
            }

            if (!$method_id) {
                return true;
            }

            try {
                $method_id = $codebase->methods->getDeclaringMethodId($method_id);

                if (!$method_id) {
                    return false;
                }

                if (!$codebase->methods->hasStorage($method_id)) {
                    return false;
                }
            } catch (Exception) {
                return false;
            }
        }

        $input_callable = self::getCallableFromAtomic($codebase, $input_type_part, $container_type_part, null, true);

        if ($input_callable) {
            if (self::isContainedBy(
                $codebase,
                $input_callable,
                $container_type_part,
                $atomic_comparison_result,
            ) === false
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return TCallable|TClosure|null
     */
    public static function getCallableFromAtomic(
        Codebase $codebase,
        Atomic $input_type_part,
        ?TCallable $container_type_part = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        bool $expand_callable = false,
    ): ?Atomic {

        if ($input_type_part instanceof TCallable || $input_type_part instanceof TClosure) {
            return $input_type_part;
        }

        if ($input_type_part instanceof TLiteralString && $input_type_part->value) {
            try {
                $function_storage = $codebase->functions->getStorage(
                    $statements_analyzer,
                    strtolower($input_type_part->value),
                );

                if ($expand_callable) {
                    $params = [];

                    foreach ($function_storage->params as $param) {
                        if ($param->type) {
                            $param = $param->setType(
                                TypeExpander::expandUnion(
                                    $codebase,
                                    $param->type,
                                    null,
                                    null,
                                    null,
                                    true,
                                    true,
                                    false,
                                    false,
                                    true,
                                ),
                            );
                        }

                        $params[] = $param;
                    }

                    $return_type = null;

                    if ($function_storage->return_type) {
                        $return_type = TypeExpander::expandUnion(
                            $codebase,
                            $function_storage->return_type,
                            null,
                            null,
                            null,
                            true,
                            true,
                            false,
                            false,
                            true,
                        );
                    }
                } else {
                    $return_type = $function_storage->return_type;
                    $params = $function_storage->params;
                }

                return new TCallable(
                    'callable',
                    $params,
                    $return_type,
                    $function_storage->pure,
                );
            } catch (UnexpectedValueException) {
                if (InternalCallMapHandler::inCallMap($input_type_part->value)) {
                    $args = [];

                    $nodes = new NodeDataProvider();

                    if ($container_type_part && $container_type_part->params) {
                        foreach ($container_type_part->params as $i => $param) {
                            $arg = new Arg(
                                new Variable('_' . $i),
                            );

                            if ($param->type) {
                                $nodes->setType($arg->value, $param->type);
                            }

                            $args[] = $arg;
                        }
                    }

                    $matching_callable = InternalCallMapHandler::getCallableFromCallMapById(
                        $codebase,
                        $input_type_part->value,
                        $args,
                        $nodes,
                    );

                    $must_use = false;

                    $matching_callable = $matching_callable->setIsPure($codebase->functions->isCallMapFunctionPure(
                        $codebase,
                        $statements_analyzer->node_data ?? null,
                        $input_type_part->value,
                        null,
                        $must_use,
                    ));

                    return $matching_callable;
                }
            }
        } elseif ($input_type_part instanceof TKeyedArray) {
            $method_id = self::getCallableMethodIdFromTKeyedArray($input_type_part);
            if ($method_id && $method_id !== 'not-callable') {
                try {
                    $method_storage = $codebase->methods->getStorage($method_id);
                    $method_fqcln = $method_id->fq_class_name;

                    $converted_return_type = null;

                    if ($method_storage->return_type) {
                        $converted_return_type = TypeExpander::expandUnion(
                            $codebase,
                            $method_storage->return_type,
                            $method_fqcln,
                            $method_fqcln,
                            null,
                        );
                    }

                    return new TCallable(
                        'callable',
                        $method_storage->params,
                        $converted_return_type,
                        $method_storage->pure,
                    );
                } catch (UnexpectedValueException) {
                    // do nothing
                }
            }
        } elseif ($input_type_part instanceof TNamedObject
            && $input_type_part->value === 'Closure'
        ) {
            return new TCallable();
        } elseif ($input_type_part instanceof TNamedObject
            && $codebase->classExists($input_type_part->value)
        ) {
            $invoke_id = new MethodIdentifier(
                $input_type_part->value,
                '__invoke',
            );

            if ($codebase->methods->methodExists($invoke_id)) {
                $declaring_method_id = $codebase->methods->getDeclaringMethodId($invoke_id);
                $template_result = null;

                if ($input_type_part instanceof Atomic\TGenericObject) {
                    $invokable_storage = $codebase->methods->getClassLikeStorageForMethod(
                        $declaring_method_id ?? $invoke_id,
                    );
                    $type_params = [];

                    foreach ($invokable_storage->template_types ?? [] as $template => $for_class) {
                        foreach ($for_class as $type) {
                            $type_params[] = new Type\Union([
                                new TTemplateParam($template, $type, $input_type_part->value),
                            ]);
                        }
                    }

                    if (!empty($type_params)) {
                        $input_with_templates = new Atomic\TGenericObject($input_type_part->value, $type_params);
                        $template_result = new TemplateResult($invokable_storage->template_types ?? [], []);

                        TemplateStandinTypeReplacer::fillTemplateResult(
                            new Type\Union([$input_with_templates]),
                            $template_result,
                            $codebase,
                            null,
                            new Type\Union([$input_type_part]),
                        );
                    }
                }

                if ($declaring_method_id) {
                    $method_storage = $codebase->methods->getStorage($declaring_method_id);
                    $method_fqcln = $invoke_id->fq_class_name;
                    $converted_return_type = null;
                    if ($method_storage->return_type) {
                        $converted_return_type = TypeExpander::expandUnion(
                            $codebase,
                            $method_storage->return_type,
                            $method_fqcln,
                            $method_fqcln,
                            null,
                        );
                    }

                    $callable = new TCallable(
                        'callable',
                        $method_storage->params,
                        $converted_return_type,
                        $method_storage->pure,
                    );

                    if ($template_result) {
                        $callable = TemplateInferredTypeReplacer::replace(
                            new Union([$callable]),
                            $template_result,
                            $codebase,
                        )->getSingleAtomic();
                    }

                    return $callable;
                }
            }
        }

        return null;
    }

    /** @return null|'not-callable'|MethodIdentifier */
    public static function getCallableMethodIdFromTKeyedArray(
        TKeyedArray $input_type_part,
        ?Codebase $codebase = null,
        ?string $calling_method_id = null,
        ?string $file_name = null,
    ): string|MethodIdentifier|null {
        if (!isset($input_type_part->properties[0])
            || !isset($input_type_part->properties[1])
            || count($input_type_part->properties) > 2
        ) {
            return 'not-callable';
        }

        [$lhs, $rhs] = $input_type_part->properties;

        $rhs_low_info = $rhs->hasMixed() || $rhs->hasScalar();

        if ($rhs_low_info || !$rhs->isSingleStringLiteral()) {
            if (!$rhs_low_info && !$rhs->hasString()) {
                return 'not-callable';
            }

            if ($codebase && ($calling_method_id || $file_name)) {
                foreach ($lhs->getAtomicTypes() as $lhs_atomic_type) {
                    if ($lhs_atomic_type instanceof TNamedObject) {
                        $codebase->analyzer->addMixedMemberName(
                            strtolower($lhs_atomic_type->value) . '::',
                            $calling_method_id ?: $file_name,
                        );
                    } elseif ($lhs_atomic_type instanceof TTemplateParam) {
                        $lhs_template_type = $lhs_atomic_type->as;
                        if ($lhs_template_type->isSingle()) {
                            $lhs_template_atomic_type = $lhs_template_type->getSingleAtomic();
                            $member_id = null;
                            if ($lhs_template_atomic_type instanceof TNamedObject) {
                                $member_id = $lhs_template_atomic_type->value;
                            } elseif ($lhs_template_atomic_type instanceof TClassString) {
                                $member_id = $lhs_template_atomic_type->as;
                            }

                            if ($member_id) {
                                /** @psalm-suppress PossiblyNullArgument Psalm bug */
                                $codebase->analyzer->addMixedMemberName(
                                    strtolower($member_id) . '::',
                                    $calling_method_id ?: $file_name,
                                );
                            }
                        }
                    }
                }
            }

            return null;
        }

        $method_name = $rhs->getSingleStringLiteral()->value;

        $class_name = null;

        if ($lhs->isSingleStringLiteral()) {
            $class_name = $lhs->getSingleStringLiteral()->value;
            if ($class_name[0] === '\\') {
                $class_name = substr($class_name, 1);
            }
        } elseif ($lhs->isSingle()) {
            foreach ($lhs->getAtomicTypes() as $lhs_atomic_type) {
                if ($lhs_atomic_type instanceof TNamedObject) {
                    $class_name = $lhs_atomic_type->value;
                } elseif ($lhs_atomic_type instanceof TTemplateParam) {
                    $lhs_template_type = $lhs_atomic_type->as;
                    if ($lhs_template_type->isSingle()) {
                        $lhs_template_atomic_type = $lhs_template_type->getSingleAtomic();
                        if ($lhs_template_atomic_type instanceof TNamedObject) {
                            $class_name = $lhs_template_atomic_type->value;
                        } elseif ($lhs_template_atomic_type instanceof TClassString) {
                            $class_name = $lhs_template_atomic_type->as;
                        }
                    }
                } elseif ($lhs_atomic_type instanceof TClassString
                    && $lhs_atomic_type->as
                ) {
                    $class_name = $lhs_atomic_type->as;
                }
            }
        }

        if ($class_name === 'self'
            || $class_name === 'static'
            || $class_name === 'parent'
        ) {
            return null;
        }

        if (!$class_name) {
            if ($codebase && ($calling_method_id || $file_name)) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($method_name),
                    $calling_method_id ?: $file_name,
                );
            }

            return null;
        }

        return new MethodIdentifier(
            $class_name,
            strtolower($method_name),
        );
    }
}
