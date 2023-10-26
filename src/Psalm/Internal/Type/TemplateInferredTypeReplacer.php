<?php

namespace Psalm\Internal\Type;

use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTemplatePropertiesOf;
use Psalm\Type\Atomic\TTemplateValueOf;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_merge;
use function array_shift;
use function array_values;
use function strpos;

/**
 * @internal
 */
final class TemplateInferredTypeReplacer
{
    /**
     * This replaces template types in unions with the inferred types they should be
     *
     * @psalm-external-mutation-free
     */
    public static function replace(
        Union $union,
        TemplateResult $template_result,
        ?Codebase $codebase
    ): Union {
        $new_types = [];

        $is_mixed = false;

        $inferred_lower_bounds = $template_result->lower_bounds ?: [];

        $types = [];

        foreach ($union->getAtomicTypes() as $key => $atomic_type) {
            $should_set = true;
            $atomic_type = $atomic_type->replaceTemplateTypesWithArgTypes($template_result, $codebase);

            if ($atomic_type instanceof TTemplateParam) {
                $template_type = self::replaceTemplateParam(
                    $codebase,
                    $atomic_type,
                    $inferred_lower_bounds,
                    $key,
                );

                if ($template_type) {
                    $should_set = false;

                    foreach ($template_type->getAtomicTypes() as $template_type_part) {
                        if ($template_type_part instanceof TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[] = $template_type_part;
                    }
                }
            } elseif ($atomic_type instanceof TTemplateParamClass) {
                $template_type = isset($inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
                    ? TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                        $inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                        $codebase,
                    )
                    : null;

                $class_template_type = null;

                if ($template_type) {
                    foreach ($template_type->getAtomicTypes() as $template_type_part) {
                        if ($template_type_part instanceof TMixed
                            || $template_type_part instanceof TObject
                        ) {
                            $class_template_type = new TClassString();
                        } elseif ($template_type_part instanceof TNamedObject) {
                            $class_template_type = new TClassString(
                                $template_type_part->value,
                                $template_type_part,
                            );
                        } elseif ($template_type_part instanceof TTemplateParam) {
                            $first_atomic_type = $template_type_part->as->getSingleAtomic();

                            $class_template_type = new TTemplateParamClass(
                                $template_type_part->param_name,
                                $template_type_part->as->getId(),
                                $first_atomic_type instanceof TNamedObject ? $first_atomic_type : null,
                                $template_type_part->defining_class,
                            );
                        }
                    }
                }

                if ($class_template_type) {
                    $should_set = false;
                    $new_types[] = $class_template_type;
                }
            } elseif ($atomic_type instanceof TTemplateIndexedAccess) {
                $should_set = false;

                $template_type = null;

                if (isset($inferred_lower_bounds[$atomic_type->array_param_name][$atomic_type->defining_class])
                    && !empty($inferred_lower_bounds[$atomic_type->offset_param_name])
                ) {
                    $array_template_type
                        = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                            $inferred_lower_bounds[$atomic_type->array_param_name][$atomic_type->defining_class],
                            $codebase,
                        );

                    $offset_template_type
                        = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                            array_values($inferred_lower_bounds[$atomic_type->offset_param_name])[0],
                            $codebase,
                        );

                    if ($array_template_type->isSingle()
                        && $offset_template_type->isSingle()
                        && !$array_template_type->isMixed()
                        && !$offset_template_type->isMixed()
                    ) {
                        $array_template_type = $array_template_type->getSingleAtomic();
                        $offset_template_type = $offset_template_type->getSingleAtomic();

                        if ($array_template_type instanceof TKeyedArray
                            && ($offset_template_type instanceof TLiteralString
                                || $offset_template_type instanceof TLiteralInt)
                            && isset($array_template_type->properties[$offset_template_type->value])
                        ) {
                            $template_type = $array_template_type->properties[$offset_template_type->value];
                        }
                    }
                }

                if ($template_type) {
                    foreach ($template_type->getAtomicTypes() as $template_type_part) {
                        if ($template_type_part instanceof TMixed) {
                            $is_mixed = true;
                        }

                        $new_types[] = $template_type_part;
                    }
                } else {
                    $new_types[] = new TMixed();
                }
            } elseif ($atomic_type instanceof TTemplateKeyOf
                || $atomic_type instanceof TTemplateValueOf
            ) {
                $new_type = self::replaceTemplateKeyOfValueOf(
                    $codebase,
                    $atomic_type,
                    $inferred_lower_bounds,
                );

                if ($new_type) {
                    $should_set = false;
                    $new_types[] = $new_type;
                }
            } elseif ($atomic_type instanceof TTemplatePropertiesOf) {
                $new_type = self::replaceTemplatePropertiesOf(
                    $codebase,
                    $atomic_type,
                    $inferred_lower_bounds,
                );

                if ($new_type) {
                    $should_set = false;
                    $new_types[] = $new_type;
                }
            } elseif ($atomic_type instanceof TConditional
                && $codebase
            ) {
                $class_template_type = self::replaceConditional(
                    $template_result,
                    $codebase,
                    $atomic_type,
                    $inferred_lower_bounds,
                );

                $should_set = false;

                foreach ($class_template_type->getAtomicTypes() as $class_template_atomic_type) {
                    $new_types[] = $class_template_atomic_type;
                }
            }
            if ($should_set) {
                $types []= $atomic_type;
            }
        }

        if ($is_mixed) {
            if (!$new_types) {
                throw new UnexpectedValueException('This array should be full');
            }

            return $union->getBuilder()->setTypes(
                TypeCombiner::combine(
                    $new_types,
                    $codebase,
                )->getAtomicTypes(),
            )->freeze();
        }

        $atomic_types = [...$types, ...$new_types];
        if (!$atomic_types) {
            throw new UnexpectedValueException('This array should be full');
        }

        return $union->getBuilder()->setTypes(
            TypeCombiner::combine(
                $atomic_types,
                $codebase,
            )->getAtomicTypes(),
        )->freeze();
    }

    /**
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $inferred_lower_bounds
     */
    private static function replaceTemplateParam(
        ?Codebase $codebase,
        TTemplateParam $atomic_type,
        array $inferred_lower_bounds,
        string $key
    ): ?Union {
        $template_type = null;

        $traversed_type = TemplateStandinTypeReplacer::getRootTemplateType(
            $inferred_lower_bounds,
            $atomic_type->param_name,
            $atomic_type->defining_class,
            [],
            $codebase,
        );

        if ($traversed_type) {
            $template_type = $traversed_type;

            if ($template_type->isMixed() && !$atomic_type->as->isMixed()) {
                $template_type = $atomic_type->as;
            }

            if ($atomic_type->extra_types) {
                $types = [];
                foreach ($template_type->getAtomicTypes() as $atomic_template_type) {
                    if ($atomic_template_type instanceof TNamedObject
                        || $atomic_template_type instanceof TTemplateParam
                        || $atomic_template_type instanceof TIterable
                        || $atomic_template_type instanceof TObjectWithProperties
                    ) {
                        $types []= $atomic_template_type->setIntersectionTypes(array_merge(
                            $atomic_type->extra_types,
                            $atomic_template_type->extra_types,
                        ));
                    } elseif ($atomic_template_type instanceof TObject) {
                        $first_atomic_type = array_shift($atomic_type->extra_types);

                        if ($atomic_type->extra_types) {
                            $first_atomic_type = $first_atomic_type->setIntersectionTypes($atomic_type->extra_types);
                        }

                        $types []= $first_atomic_type;
                    } else {
                        $types []= $atomic_template_type;
                    }
                }
                $template_type = $template_type->getBuilder()->setTypes($types)->freeze();
            }
        } elseif ($codebase) {
            foreach ($inferred_lower_bounds as $template_type_map) {
                foreach ($template_type_map as $template_class => $_) {
                    if (strpos($template_class, 'fn-') === 0) {
                        continue;
                    }

                    try {
                        $classlike_storage = $codebase->classlike_storage_provider->get($template_class);

                        if ($classlike_storage->template_extended_params) {
                            $defining_class = $atomic_type->defining_class;

                            if (isset($classlike_storage->template_extended_params[$defining_class])) {
                                $param_map = $classlike_storage->template_extended_params[$defining_class];

                                if (isset($param_map[$key])) {
                                    $template_name = (string) $param_map[$key];
                                    if (isset($inferred_lower_bounds[$template_name][$template_class])) {
                                        $template_type
                                            = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                                                $inferred_lower_bounds[$template_name][$template_class],
                                                $codebase,
                                            );
                                    }
                                }
                            }
                        }
                    } catch (InvalidArgumentException $e) {
                    }
                }
            }
        }

        return $template_type;
    }

    /**
     * @param TTemplateKeyOf|TTemplateValueOf $atomic_type
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $inferred_lower_bounds
     */
    private static function replaceTemplateKeyOfValueOf(
        ?Codebase $codebase,
        Atomic $atomic_type,
        array $inferred_lower_bounds
    ): ?Atomic {
        if (!isset($inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])) {
            return null;
        }

        $template_type = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
            $inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
            $codebase,
        );

        if ($atomic_type instanceof TTemplateKeyOf
            && TKeyOf::isViableTemplateType($template_type)
        ) {
            return new TKeyOf($template_type);
        }

        if ($atomic_type instanceof TTemplateValueOf
            && TValueOf::isViableTemplateType($template_type)
        ) {
            return new TValueOf($template_type);
        }

        return null;
    }

    /**
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $inferred_lower_bounds
     */
    private static function replaceTemplatePropertiesOf(
        ?Codebase $codebase,
        TTemplatePropertiesOf $atomic_type,
        array $inferred_lower_bounds
    ): ?Atomic {
        if (!isset($inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])) {
            return null;
        }

        $template_type = TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
            $inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
            $codebase,
        );

        $classlike_type = $template_type->getSingleAtomic();
        if (!$classlike_type instanceof TNamedObject) {
            return null;
        }

        return new TPropertiesOf(
            $classlike_type,
            $atomic_type->visibility_filter,
        );
    }

    /**
     * @param array<string, array<string, non-empty-list<TemplateBound>>> $inferred_lower_bounds
     */
    private static function replaceConditional(
        TemplateResult $template_result,
        Codebase $codebase,
        TConditional &$atomic_type,
        array $inferred_lower_bounds
    ): Union {
        $template_type = isset($inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class])
            ? TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds(
                $inferred_lower_bounds[$atomic_type->param_name][$atomic_type->defining_class],
                $codebase,
            )
            : null;

        $if_template_type = null;
        $else_template_type = null;

        $as_type = $atomic_type->as_type;
        $conditional_type = $atomic_type->conditional_type;
        $if_type = $atomic_type->if_type;
        $else_type = $atomic_type->else_type;

        if ($template_type) {
            $as_type = self::replace(
                $as_type,
                $template_result,
                $codebase,
            );

            if ($as_type->isNullable() && $template_type->isVoid()) {
                $template_type = Type::getNull();
            }

            $matching_if_types = [];
            $matching_else_types = [];

            foreach ($template_type->getAtomicTypes() as $candidate_atomic_type) {
                if (UnionTypeComparator::isContainedBy(
                    $codebase,
                    new Union([$candidate_atomic_type]),
                    $conditional_type,
                    false,
                    false,
                    null,
                    false,
                    false,
                )
                    && (!$candidate_atomic_type instanceof TInt
                        || $conditional_type->getId() !== 'float')
                ) {
                    $matching_if_types[] = $candidate_atomic_type;
                } elseif (!UnionTypeComparator::isContainedBy(
                    $codebase,
                    $conditional_type,
                    new Union([$candidate_atomic_type]),
                    false,
                    false,
                    null,
                    false,
                    false,
                )) {
                    $matching_else_types[] = $candidate_atomic_type;
                }
            }

            $if_candidate_type = $matching_if_types ? new Union($matching_if_types) : null;
            $else_candidate_type = $matching_else_types ? new Union($matching_else_types) : null;

            if ($if_candidate_type
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $if_candidate_type,
                    $conditional_type,
                    false,
                    false,
                    null,
                    false,
                    false,
                )
            ) {
                $if_template_type = $if_type;

                $refined_template_result = clone $template_result;

                $refined_template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class]
                    = [
                    new TemplateBound(
                        $if_candidate_type,
                    ),
                ];

                $if_template_type = self::replace(
                    $if_template_type,
                    $refined_template_result,
                    $codebase,
                );
            }

            if ($else_candidate_type
                && UnionTypeComparator::isContainedBy(
                    $codebase,
                    $else_candidate_type,
                    $as_type,
                    false,
                    false,
                    null,
                    false,
                    false,
                )
            ) {
                $else_template_type = $else_type;

                $refined_template_result = clone $template_result;

                $refined_template_result->lower_bounds[$atomic_type->param_name][$atomic_type->defining_class]
                    = [
                    new TemplateBound(
                        $else_candidate_type,
                    ),
                ];

                $else_template_type = self::replace(
                    $else_template_type,
                    $refined_template_result,
                    $codebase,
                );
            }
        }

        if (!$if_template_type && !$else_template_type) {
            $if_type = self::replace(
                $if_type,
                $template_result,
                $codebase,
            );

            $else_type = self::replace(
                $else_type,
                $template_result,
                $codebase,
            );

            $class_template_type = Type::combineUnionTypes(
                $if_type,
                $else_type,
                $codebase,
            );
        } else {
            $class_template_type = Type::combineUnionTypes(
                $if_template_type,
                $else_template_type,
                $codebase,
            );
        }

        $atomic_type = $atomic_type->setTypes(
            $as_type,
            $conditional_type,
            $if_type,
            $else_type,
        );

        return $class_template_type;
    }
}
