<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Codebase;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TStarProjection;
use Psalm\Type\Union;

use function array_any;
use function array_keys;
use function array_values;
use function reset;

/**
 * The `*` type argument of a generic object (`Pair<int, *>`), see {@see TStarProjection}.
 *
 * A template of a `Pair<int, *>` is bound to the `*`, which knows the template's bound with the other type arguments
 * applied (see boundStars()). The template replacers read the binding as that, through
 * TemplateStandinTypeReplacer::getMostSpecificTypeFromBounds() and where they take a class template's binding.
 *
 * @internal
 */
final class StarProjection
{
    public const NAME = '*';

    /** @var array<string, true> the classes whose template bounds boundStars() is expanding */
    private static array $expanding = [];

    /**
     * @psalm-pure
     */
    public static function create(bool $from_docblock, ?Union $as = null): Union
    {
        return new Union([new TStarProjection($from_docblock, $as)], ['from_docblock' => $from_docblock]);
    }

    /**
     * @psalm-pure
     */
    public static function isStar(Union $type): bool
    {
        return $type->isSingle() && $type->getSingleAtomic() instanceof TStarProjection;
    }

    /**
     * What reading a template bound to $argument gives: for a `*`, what is known of it, else $bound
     *
     * @psalm-pure
     */
    public static function read(Union $argument, ?Union $bound = null): Union
    {
        $star = $argument->isSingle() ? $argument->getSingleAtomic() : null;

        return $star instanceof TStarProjection ? $star->as ?? $bound ?? Type::getMixed() : $argument;
    }

    /**
     * The generic object with each `*` argument read as what is known of it, else as the bound of its template, for
     * where its type arguments are read directly rather than through its templates (iterating it, a return type
     * provider)
     *
     * @psalm-mutation-free
     */
    public static function readArguments(TGenericObject $type, Codebase $codebase): TGenericObject
    {
        if (!array_any($type->type_params, self::isStar(...))) {
            return $type;
        }

        $template_types = $codebase->classlike_storage_provider->has($type->value)
            ? array_values($codebase->classlike_storage_provider->get($type->value)->template_types ?? [])
            : [];
        $type_params = [];

        foreach ($type->type_params as $offset => $type_param) {
            $bound = isset($template_types[$offset]) ? reset($template_types[$offset]) : null;
            $type_params[] = self::read($type_param, $bound ?: null);
        }

        return $type->setTypeParams($type_params);
    }

    /**
     * The generic object with each `*` argument knowing what it is known to be, see boundStars()
     */
    public static function boundArguments(TGenericObject $type, Codebase $codebase): TGenericObject
    {
        if (!array_any($type->type_params, self::isStar(...))
            || !$codebase->classlike_storage_provider->has($type->value)
        ) {
            return $type;
        }

        $type_params = self::boundStars(
            $type->type_params,
            $codebase->classlike_storage_provider->get($type->value),
            $codebase,
        );

        return $type_params === [] || $type_params === $type->type_params ? $type : $type->setTypeParams($type_params);
    }

    /**
     * The type arguments of a use of the class with each `*` knowing the bound of its template, expanded, with the
     * other arguments applied (`Pair<int, *>` with `@template B of list<A>` knows `list<int>`)
     *
     * @param list<Union> $type_params
     * @return list<Union>
     */
    public static function boundStars(array $type_params, ClassLikeStorage $storage, Codebase $codebase): array
    {
        $unbound_offsets = [];

        foreach ($type_params as $offset => $type_param) {
            $star = $type_param->isSingle() ? $type_param->getSingleAtomic() : null;

            if ($star instanceof TStarProjection && $star->as === null) {
                $unbound_offsets[] = $offset;
            }
        }

        // a bound naming its own class with a `*` (`T of Node<*>`) is expanded once
        if ($unbound_offsets === [] || isset(self::$expanding[$storage->name])) {
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
            foreach ($unbound_offsets as $offset) {
                if (!isset($template_names[$offset])) {
                    break;
                }

                $template_name = $template_names[$offset];
                $other_bindings = $bindings;
                unset($other_bindings[$template_name]);

                $type_params[$offset] = $bindings[$template_name][$storage->name] = self::create(
                    $type_params[$offset]->from_docblock,
                    TypeExpander::expandUnion(
                        $codebase,
                        TemplateInferredTypeReplacer::replace(
                            reset($template_types[$template_name]),
                            new TemplateResult([], $other_bindings),
                            $codebase,
                        ),
                        $storage->name,
                        $storage->name,
                        $storage->parent_class,
                    ),
                );
            }
        } finally {
            unset(self::$expanding[$storage->name]);
        }

        return $type_params;
    }
}
