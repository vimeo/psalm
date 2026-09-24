<?php

declare(strict_types=1);

namespace Psalm\Internal\Type;

use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;

use function count;
use function strtolower;

/**
 * The purity template of the iterator types (`TPurity`, the last template of Traversable, Iterator,
 * IteratorAggregate and Generator): what iterating over a value of the type may do.
 *
 * @internal
 */
final class IterationPurity
{
    /**
     * A Generator, Iterator or Traversable type with its purity template bound to $purity, unless
     * the type binds it already; null for any other type.
     *
     * @psalm-pure
     */
    public static function bindGenerator(Atomic $atomic_type, Union $purity): ?Atomic
    {
        if (!$atomic_type instanceof TNamedObject) {
            return null;
        }

        $param_count = match (strtolower($atomic_type->value)) {
            'generator' => 5,
            'iterator', 'traversable' => 3,
            default => 0,
        };

        if ($param_count === 0) {
            return null;
        }

        $type_params = $atomic_type instanceof TGenericObject ? $atomic_type->type_params : [];

        if (count($type_params) >= $param_count) {
            return null;
        }

        while (count($type_params) < $param_count - 1) {
            $type_params[] = Type::getMixed();
        }

        $type_params[] = $purity;

        return $atomic_type instanceof TGenericObject
            ? $atomic_type->setTypeParams($type_params)
            : new TGenericObject(
                $atomic_type->value,
                $type_params,
                false,
                $atomic_type->is_static,
                $atomic_type->extra_types,
                $atomic_type->from_docblock,
            );
    }

    /**
     * The purity a Generator, Iterator or Traversable type in $type binds, if any.
     *
     * @psalm-pure
     */
    public static function getBoundPurity(Union $type): ?Union
    {
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if (!$atomic_type instanceof TGenericObject) {
                continue;
            }

            $param_count = match (strtolower($atomic_type->value)) {
                'generator' => 5,
                'iterator', 'traversable' => 3,
                default => 0,
            };

            if ($param_count !== 0 && count($atomic_type->type_params) === $param_count) {
                return $atomic_type->type_params[$param_count - 1];
            }
        }

        return null;
    }

    /**
     * $type with the purity template of every Generator, Iterator or Traversable in it that does
     * not bind one bound to $purity.
     *
     * @psalm-pure
     */
    public static function bindGenerators(Union $type, Union $purity): Union
    {
        $atomic_types = [];
        $changed = false;

        foreach ($type->getAtomicTypes() as $atomic_type) {
            $bound_type = self::bindGenerator($atomic_type, $purity);

            if ($bound_type !== null) {
                $atomic_type = $bound_type;
                $changed = true;
            }

            $atomic_types[] = $atomic_type;
        }

        return $changed ? $type->setTypes($atomic_types) : $type;
    }
}
