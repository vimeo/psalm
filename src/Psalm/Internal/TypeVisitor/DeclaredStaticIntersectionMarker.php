<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

/**
 * Rewrites a declared `static&I`, which the parser gives as `I` marked static, to `static` intersected with `I`, so
 * that DeclaredStaticReplacer can tell it from the caller's late static types the bounds put in the type later. Run it
 * on a declared type only, before any bound is put in it.
 *
 * @internal
 */
final class DeclaredStaticIntersectionMarker extends MutableTypeVisitor
{
    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof TNamedObject
            || ($type::class !== TNamedObject::class && !$type instanceof TGenericObject)
            || !$type->is_static
            || $type->is_static_resolved
            || $type->value === 'static'
        ) {
            return null;
        }

        $intersected = $type->setIsStatic(false)->setIntersectionTypes([]);

        $type = new TNamedObject(
            'static',
            false,
            false,
            [$intersected->getKey() => $intersected, ...$type->extra_types],
            $type->from_docblock,
        );

        return self::DONT_TRAVERSE_CHILDREN;
    }
}
