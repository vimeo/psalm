<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression\Call;

use AssertionError;
use Psalm\Codebase;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Type\PurityArguments;
use Psalm\Internal\Type\TemplateDefaults;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeArgumentProjection;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Union;

use function array_keys;
use function array_merge;
use function array_search;

/**
 * @internal
 */
final class ClassTemplateParamCollector
{
    /**
     * @param lowercase-string $method_name
     * @return array<string, non-empty-array<string, Union>>|null
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public static function collect(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        ClassLikeStorage $static_class_storage,
        ?string $method_name = null,
        ?Atomic $lhs_type_part = null,
        bool $self_call = false,
    ): ?array {
        $class_storage = self::getDocumentingClassStorage(
            $codebase,
            $class_storage,
            $static_class_storage,
            $method_name,
        );

        $non_trait_class_storage = $class_storage->is_trait
            ? $static_class_storage
            : $class_storage;

        $template_types = $class_storage->template_types;

        [$template_types, $candidate_class_storages] = self::getOverriddenTemplates(
            $codebase,
            $class_storage,
            $static_class_storage,
            $non_trait_class_storage,
            $method_name,
            $template_types,
        );

        if (!$template_types) {
            return null;
        }

        $class_template_params = [];
        $e = $static_class_storage->template_extended_params;

        if ($lhs_type_part instanceof TGenericObject) {
            if ($class_storage === $static_class_storage && $class_storage->template_types) {
                $class_template_params = self::getOwnTemplateParams($codebase, $class_storage, $lhs_type_part);
            }

            $template_result = null;
            if ($class_storage !== $static_class_storage && $static_class_storage->template_types) {
                $templates = self::collect(
                    $codebase,
                    $static_class_storage,
                    $static_class_storage,
                    null,
                    $lhs_type_part,
                );
                if ($templates === null) {
                    throw new AssertionError("Could not collect templates!");
                }
                $template_result = new TemplateResult(
                    $static_class_storage->template_types,
                    $templates,
                );
            }
            foreach ($template_types as $type_name => $_) {
                // the templates of the classes documenting the method resolve against the receiver as well
                foreach ($candidate_class_storages as $candidate_class_storage) {
                    if ($candidate_class_storage !== $class_storage
                        && $candidate_class_storage !== $static_class_storage
                        && $template_result !== null
                        && isset($e[$candidate_class_storage->name][$type_name])
                        && !isset($class_template_params[$type_name][$candidate_class_storage->name])
                    ) {
                        $resolved_type = self::resolveTemplateParam(
                            $codebase,
                            $e[$candidate_class_storage->name][$type_name],
                            $static_class_storage,
                            $lhs_type_part,
                            $template_result,
                        );

                        if ($resolved_type !== null) {
                            $class_template_params[$type_name][$candidate_class_storage->name] = $resolved_type;
                        }
                    }
                }

                if (isset($class_template_params[$type_name][$class_storage->name])) {
                    continue;
                }

                if ($class_storage !== $static_class_storage
                    && isset($e[$class_storage->name][$type_name])
                ) {
                    $input_type_extends = $e[$class_storage->name][$type_name];

                    $output_type_extends = self::resolveTemplateParam(
                        $codebase,
                        $input_type_extends,
                        $static_class_storage,
                        $lhs_type_part,
                        $template_result,
                    );

                    $class_template_params[$type_name][$class_storage->name]
                        = $output_type_extends ?? Type::getMixed();
                } else {
                    $class_template_params[$type_name][$class_storage->name] = Type::getMixed();
                }
            }
        }

        foreach ($template_types as $type_name => $type_map) {
            foreach ($type_map as $type) {
                foreach ($candidate_class_storages as $candidate_class_storage) {
                    if ($candidate_class_storage !== $static_class_storage
                        && isset($e[$candidate_class_storage->name][$type_name])
                        && !isset($class_template_params[$type_name][$candidate_class_storage->name])
                    ) {
                        // the arguments the object was created with replace the templates of its own class
                        $resolved_type = null;

                        if ($lhs_type_part instanceof TGenericObject
                            && $static_class_storage->template_types !== null
                        ) {
                            $own_params = self::collect(
                                $codebase,
                                $static_class_storage,
                                $static_class_storage,
                                null,
                                $lhs_type_part,
                            );

                            $resolved_type = self::resolveTemplateParam(
                                $codebase,
                                $e[$candidate_class_storage->name][$type_name],
                                $static_class_storage,
                                $lhs_type_part,
                                $own_params !== null
                                    ? new TemplateResult($static_class_storage->template_types, $own_params)
                                    : null,
                            );
                        }

                        $class_template_params[$type_name][$candidate_class_storage->name] = $resolved_type
                            ?? new Union(
                                self::expandType(
                                    $codebase,
                                    $e[$candidate_class_storage->name][$type_name],
                                    $e,
                                    $static_class_storage->name,
                                    $static_class_storage->template_types,
                                ),
                            );
                    }
                }

                if (!$self_call) {
                    // a receiver without type arguments is its templates' bounds, also where the templates of the
                    // classes it extends are mapped already
                    if (!isset($class_template_params[$type_name][$class_storage->name])) {
                        $class_template_params[$type_name][$class_storage->name]
                            = PurityArguments::getOmittedArgument($type);
                    }
                }
            }
        }

        return $class_template_params;
    }

    /**
     * The class templates, bound to the type arguments of the receiver
     *
     * @return array<string, array<string, Union>>
     * @psalm-capabilities read-props|write-this-props|write-props
     */
    private static function getOwnTemplateParams(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        TGenericObject $lhs_type_part,
    ): array {
        $lhs_type_params = TemplateDefaults::fillTypeArguments(
            $lhs_type_part->type_params,
            $class_storage,
            $codebase,
            $lhs_type_part->from_docblock,
        );
        $own_template_params = [];
        $i = 0;

        foreach ($class_storage->template_types ?? [] as $type_name => $_) {
            if (isset($lhs_type_params[$i])) {
                $own_template_params[$type_name][$class_storage->name] = $lhs_type_params[$i];
            } elseif (isset($class_storage->purity_templates[$type_name])
                && isset($class_storage->template_defaults[$type_name])
            ) {
                // a purity template left out (`Box<int>`) has its default
                $own_template_params[$type_name][$class_storage->name] = $class_storage->template_defaults[$type_name];
            }

            $i++;
        }

        return $own_template_params;
    }

    /**
     * The templates of the class and of the classes declaring the methods it overrides, and those classes
     *
     * @param array<string, non-empty-array<string, Union>>|null $template_types the templates of the class
     * @return array{array<string, non-empty-array<string, Union>>|null, non-empty-list<ClassLikeStorage>}
     * @psalm-capabilities read-props
     */
    private static function getOverriddenTemplates(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        ClassLikeStorage $static_class_storage,
        ClassLikeStorage $non_trait_class_storage,
        ?string $method_name,
        ?array $template_types,
    ): array {
        $candidate_class_storages = [$class_storage];

        if ($static_class_storage->template_extended_params
            && $method_name
            && !empty($non_trait_class_storage->overridden_method_ids[$method_name])
        ) {
            foreach ($non_trait_class_storage->overridden_method_ids[$method_name] as $overridden_method_id) {
                $overridden_storage = $codebase->methods->getStorage($overridden_method_id);

                if (!$overridden_storage->return_type || $overridden_storage->return_type->isNull()) {
                    continue;
                }

                $overridden_class_storage = $codebase->classlike_storage_provider->get(
                    $overridden_method_id->fq_class_name,
                );
                $overridden_template_types = $overridden_class_storage->template_types;

                if (!$template_types) {
                    $template_types = $overridden_template_types;
                } elseif ($overridden_template_types) {
                    foreach ($overridden_template_types as $template_name => $template_map) {
                        $template_types[$template_name] = isset($template_types[$template_name])
                            ? array_merge($template_types[$template_name], $template_map)
                            : $template_map;
                    }
                }

                $candidate_class_storages[] = $overridden_class_storage;
            }
        }

        return [$template_types, $candidate_class_storages];
    }

    /**
     * A trait method without a docblock takes the one of the parent method in the class using it, so its types
     * are written with the templates of that class (which the receiver may extend)
     *
     * @param lowercase-string|null $method_name
     * @psalm-capabilities read-props
     */
    private static function getDocumentingClassStorage(
        Codebase $codebase,
        ClassLikeStorage $class_storage,
        ClassLikeStorage $static_class_storage,
        ?string $method_name,
    ): ClassLikeStorage {
        if (!$class_storage->is_trait || $class_storage === $static_class_storage || $method_name === null) {
            return $class_storage;
        }

        $appearing_method_id = $codebase->methods->getAppearingMethodId(
            new MethodIdentifier($static_class_storage->name, $method_name),
        );

        if ($appearing_method_id === null) {
            return $class_storage;
        }

        $appearing_class_storage = $codebase->classlike_storage_provider->get($appearing_method_id->fq_class_name);

        return isset($appearing_class_storage->documenting_method_ids[$method_name])
            ? $appearing_class_storage
            : $class_storage;
    }

    /**
     * Marks the templates, by name and defining class, that the receiver binds to one of its `*` arguments: its own
     * template of that argument, and those of the classes it extends that stand for exactly that template (`true`).
     * A template of a class it extends that stands for a type wrapping one of them (`@implements Getter<Box<T>>`)
     * is marked with that type, with the `*` in it, as that is what is read from the receiver (and with `never` in
     * it as what is written to it): the type that its parameters take stays the one with the bound.
     *
     * @param array<string, array<string, Union>>|null $class_template_params what collect() gave
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function markStarBindings(
        TemplateResult $template_result,
        Codebase $codebase,
        ClassLikeStorage $static_class_storage,
        ?Atomic $lhs_type_part,
        ?array $class_template_params,
    ): void {
        if (!$lhs_type_part instanceof TGenericObject
            || !$lhs_type_part->hasStarProjection()
            || $class_template_params === null
        ) {
            return;
        }

        $star_templates = [];

        foreach ($class_template_params as $template_name => $defining_classes) {
            foreach ($defining_classes as $defining_class => $_) {
                $offset = self::getOwnTemplateOffset($static_class_storage, $defining_class, $template_name);

                if ($offset !== null && $lhs_type_part->getProjection($offset) === TypeArgumentProjection::Star) {
                    $star_templates[$template_name][$defining_class] = true;
                }
            }
        }

        $own_stars = [];

        foreach (array_keys($static_class_storage->template_types ?? []) as $offset => $template_name) {
            if ($lhs_type_part->getProjection($offset) === TypeArgumentProjection::Star) {
                $own_stars[$template_name][$static_class_storage->name] = true;
            }
        }

        $own_params = self::getOwnTemplateParams($codebase, $static_class_storage, $lhs_type_part);
        $bound_result = new TemplateResult($static_class_storage->template_types ?? [], $own_params);
        $star_result = new TemplateResult($static_class_storage->template_types ?? [], $own_params);
        $star_result->markStarBindings($own_stars);
        $input_result = $star_result->withStarPosition(true);
        $input_bounds = [];

        foreach ($class_template_params as $template_name => $defining_classes) {
            foreach ($defining_classes as $defining_class => $_) {
                $extended_type = $static_class_storage->template_extended_params[$defining_class][$template_name]
                    ?? null;

                if ($extended_type === null || isset($star_templates[$template_name][$defining_class])) {
                    continue;
                }

                $starred_type = TemplateInferredTypeReplacer::replace($extended_type, $star_result, $codebase);
                $bound_type = TemplateInferredTypeReplacer::replace($extended_type, $bound_result, $codebase);
                $input_type = TemplateInferredTypeReplacer::replace($extended_type, $input_result, $codebase);

                if ($starred_type->getId() !== $bound_type->getId() || $input_type->getId() !== $bound_type->getId()) {
                    $star_templates[$template_name][$defining_class] = $starred_type;
                    $input_bounds[$template_name][$defining_class] = $input_type;
                }
            }
        }

        $template_result->markStarBindings($star_templates, $input_bounds);
    }

    /**
     * The offset of the template of the class that a template of a class it extends stands for exactly, through the
     * classes in between (`ParentBox::T` is `Middle::U`, which is `Child::V`)
     *
     * @psalm-mutation-free
     */
    private static function getOwnTemplateOffset(
        ClassLikeStorage $static_class_storage,
        string $defining_class,
        string $template_name,
    ): ?int {
        $seen = [];

        while ($defining_class !== $static_class_storage->name) {
            if (isset($seen[$defining_class . '::' . $template_name])) {
                return null;
            }

            $seen[$defining_class . '::' . $template_name] = true;
            $extended_type = $static_class_storage->template_extended_params[$defining_class][$template_name] ?? null;
            $extended_atomic = $extended_type?->isSingle() ? $extended_type->getSingleAtomic() : null;

            if (!$extended_atomic instanceof TTemplateParam) {
                return null;
            }

            $defining_class = $extended_atomic->defining_class;
            $template_name = $extended_atomic->param_name;
        }

        $offset = array_search($template_name, array_keys($static_class_storage->template_types ?? []), true);

        return $offset === false ? null : $offset;
    }

    /**
     * @psalm-external-mutation-free
     */
    private static function resolveTemplateParam(
        Codebase $codebase,
        Union $input_type_extends,
        ClassLikeStorage $static_class_storage,
        TGenericObject $lhs_type_part,
        ?TemplateResult $template_result = null,
    ): ?Union {
        $output_type_extends = null;
        foreach ($input_type_extends->getAtomicTypes() as $type_extends_atomic) {
            if ($type_extends_atomic instanceof TTemplateParam) {
                if (isset(
                    $static_class_storage
                            ->template_types
                                [$type_extends_atomic->param_name]
                                [$type_extends_atomic->defining_class],
                )
                ) {
                    $mapped_offset = array_search(
                        $type_extends_atomic->param_name,
                        array_keys($static_class_storage->template_types),
                        true,
                    );

                    if ($mapped_offset !== false
                        && isset($lhs_type_part->type_params[$mapped_offset])
                    ) {
                        $output_type_extends = Type::combineUnionTypes(
                            $lhs_type_part->type_params[$mapped_offset],
                            $output_type_extends,
                        );
                    }
                } elseif (isset(
                    $static_class_storage
                        ->template_extended_params
                            [$type_extends_atomic->defining_class]
                            [$type_extends_atomic->param_name],
                )) {
                    $nested_output_type = self::resolveTemplateParam(
                        $codebase,
                        $static_class_storage
                        ->template_extended_params
                            [$type_extends_atomic->defining_class]
                            [$type_extends_atomic->param_name],
                        $static_class_storage,
                        $lhs_type_part,
                        $template_result,
                    );
                    if ($nested_output_type !== null) {
                        $output_type_extends = Type::combineUnionTypes(
                            $nested_output_type,
                            $output_type_extends,
                        );
                    }
                }
            } else {
                if ($template_result !== null) {
                    $type_extends_atomic = $type_extends_atomic->replaceTemplateTypesWithArgTypes(
                        $template_result,
                        $codebase,
                    );
                }
                $output_type_extends = Type::combineUnionTypes(
                    new Union([$type_extends_atomic]),
                    $output_type_extends,
                );
            }
        }
        return $output_type_extends;
    }

    /**
     * @param array<string, array<string, Union>> $e
     * @return non-empty-list<Atomic>
     */
    private static function expandType(
        Codebase $codebase,
        Union $input_type_extends,
        array $e,
        string $static_fq_class_name,
        ?array $static_template_types,
    ): array {
        $output_type_extends = [];

        foreach ($input_type_extends->getAtomicTypes() as $type_extends_atomic) {
            if ($type_extends_atomic instanceof TTemplateParam
                && ($static_fq_class_name !== $type_extends_atomic->defining_class
                    || !isset($static_template_types[$type_extends_atomic->param_name]))
                && isset($e[$type_extends_atomic->defining_class][$type_extends_atomic->param_name])
            ) {
                $output_type_extends = [...$output_type_extends, ...self::expandType(
                    $codebase,
                    $e[$type_extends_atomic->defining_class][$type_extends_atomic->param_name],
                    $e,
                    $static_fq_class_name,
                    $static_template_types,
                )];
            } elseif ($type_extends_atomic instanceof TClassConstant) {
                $expanded = TypeExpander::expandAtomic(
                    $codebase,
                    $type_extends_atomic,
                    $type_extends_atomic->fq_classlike_name,
                    $type_extends_atomic->fq_classlike_name,
                    null,
                    true,
                    true,
                );

                foreach ($expanded as $type) {
                    $output_type_extends[] = $type;
                }
            } else {
                $output_type_extends[] = $type_extends_atomic;
            }
        }

        return $output_type_extends;
    }
}
