<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Union;

use function array_keys;
use function array_values;
use function in_array;
use function reset;

/**
 * The `*` type argument of a generic object (`Pair<int, *>`), see TypeArgumentProjection::Star.
 *
 * The type a `*` slot holds is the bound of its template with the other type arguments applied. The parser has no
 * class storage to compute it with and holds `mixed`, which resolve() replaces.
 *
 * @internal
 */
final class StarProjection
{
    public const NAME = '*';

    /** @var array<string, true> the classes whose template bounds boundStars() is expanding */
    private static array $expanding = [];

    /**
     * The generic object with each `*` slot holding the bound of its template: the slots left at the `mixed` of the
     * parser, or those of $offsets
     *
     * @param list<int>|null $offsets
     */
    public static function resolve(TGenericObject $type, Codebase $codebase, ?array $offsets = null): TGenericObject
    {
        if (!$type->hasStarProjection() || !$codebase->classlike_storage_provider->has($type->value)) {
            return $type;
        }

        $storage = $codebase->classlike_storage_provider->get($type->value);

        if ($offsets === null) {
            $template_types = $storage->template_types ?? [];
            $template_names = array_keys($template_types);
            $offsets = [];

            foreach ($type->getProjections() as $offset => $projection) {
                if ($projection === TypeArgumentProjection::Star
                    && $type->type_params[$offset]->isMixed()
                    && isset($template_names[$offset])
                    && !reset($template_types[$template_names[$offset]])->isMixed()
                ) {
                    $offsets[] = $offset;
                }
            }
        }

        $type_params = self::boundStars($type, $type->type_params, $storage, $codebase, $offsets);

        return $type_params === [] || $type_params === $type->type_params
            ? $type
            : $type->setHeldTypes($type_params);
    }

    /**
     * The generic object with each `contravariant X` argument holding what is read through it, see readTypeParams().
     * Code that reads the arguments without it reads `X`, what is written to it.
     *
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function resolveReads(TGenericObject $type, Codebase $codebase): TGenericObject
    {
        if (!in_array(TypeArgumentProjection::Contravariant, $type->getProjections(), true)
            || !$codebase->classlike_storage_provider->has($type->value)
        ) {
            return $type;
        }

        $type_params = self::readTypeParams(
            $type,
            $type->type_params,
            $codebase->classlike_storage_provider->get($type->value),
            $codebase,
        );

        return $type_params === [] ? $type : $type->setHeldTypes($type_params);
    }

    /**
     * The type arguments of a use of the class with each `contravariant X` slot holding what is read through it: the
     * bound of its template with the other arguments applied, as for a `*`, but not expanded
     *
     * @param list<Union> $type_params
     * @return list<Union>
     * @psalm-capabilities read-props|write-this-props|write-props|write-refs
     */
    public static function readTypeParams(
        TGenericObject $type,
        array $type_params,
        ClassLikeStorage $storage,
        Codebase $codebase,
    ): array {
        if (!in_array(TypeArgumentProjection::Contravariant, $type->getProjections(), true)) {
            return $type_params;
        }

        $template_types = $storage->template_types ?? [];
        $template_names = array_keys($template_types);
        $bindings = [];

        foreach ($type_params as $offset => $type_param) {
            if (isset($template_names[$offset])
                && $type->getProjection($offset) !== TypeArgumentProjection::Contravariant
            ) {
                $bindings[$template_names[$offset]][$storage->name] = $type_param;
            }
        }

        $read_type_params = [];

        foreach ($type_params as $offset => $type_param) {
            $read_type_params[] = isset($template_names[$offset])
                && $type->getProjection($offset) === TypeArgumentProjection::Contravariant
                ? TemplateInferredTypeReplacer::replace(
                    reset($template_types[$template_names[$offset]]),
                    new TemplateResult([], $bindings),
                    $codebase,
                )
                : $type_param;
        }

        return $read_type_params;
    }

    /**
     * The type arguments of a use of the class with the `*` slots of $offsets (all of them, if null) holding the
     * bound of their template, expanded, with the other arguments applied (`Pair<int, *>` with
     * `@template B of list<A>` holds `list<int>`)
     *
     * @param list<Union> $type_params
     * @param list<int>|null $offsets
     * @return list<Union>
     */
    public static function boundStars(
        TGenericObject $type,
        array $type_params,
        ClassLikeStorage $storage,
        Codebase $codebase,
        ?array $offsets = null,
    ): array {
        $offsets ??= array_keys($type->getProjections(), TypeArgumentProjection::Star, true);

        // a bound naming its own class with a `*` (`T of Node<*>`) is expanded once
        if ($offsets === [] || isset(self::$expanding[$storage->name])) {
            return $type_params;
        }

        $template_types = $storage->template_types ?? [];
        $template_names = array_keys($template_types);
        $bindings = [];

        foreach ($type_params as $offset => $type_param) {
            if (!isset($template_names[$offset])) {
                break;
            }

            $bindings[$template_names[$offset]][$storage->name] = $type_param;
        }

        self::$expanding[$storage->name] = true;

        try {
            foreach ($offsets as $offset) {
                if (!isset($template_names[$offset])) {
                    break;
                }

                $template_name = $template_names[$offset];
                $other_bindings = $bindings;
                unset($other_bindings[$template_name]);

                $type_params[$offset] = $bindings[$template_name][$storage->name] = TypeExpander::expandUnion(
                    $codebase,
                    TemplateInferredTypeReplacer::replace(
                        reset($template_types[$template_name]),
                        new TemplateResult([], $other_bindings),
                        $codebase,
                    ),
                    $storage->name,
                    $storage->name,
                    $storage->parent_class,
                );
            }
        } finally {
            unset(self::$expanding[$storage->name]);
        }

        return array_values($type_params);
    }
}
