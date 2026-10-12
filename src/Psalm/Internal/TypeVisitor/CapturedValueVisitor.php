<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TCallable;
use Psalm\Type\Atomic\TClosure;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * Turns the type of a value captured by a closure, and of the values it contains, like the elements
 * of an array, into the type the closure sees: not fresh, as the enclosing scope holds it too, but
 * state of the closure where it was fresh in the enclosing scope.
 *
 * The types of callables are left as they are: that their calls return fresh values does not
 * depend on who holds the callable.
 *
 * @internal
 */
final class CapturedValueVisitor extends MutableTypeVisitor
{
    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TClosure || $type instanceof TCallable) {
            return self::DONT_TRAVERSE_CHILDREN;
        }

        if ($type instanceof Union && $type->reference_free) {
            $type = $type->setProperties([
                'reference_free' => false,
                'closure_state' => $type->allow_mutations,
            ]);
        }

        return null;
    }
}
