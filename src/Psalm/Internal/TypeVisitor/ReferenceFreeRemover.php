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
 * Marks a value and the values it contains, like the elements of an array, as not fresh.
 *
 * The types of callables are left as they are: that their calls return fresh values does not
 * depend on who holds the callable.
 *
 * @internal
 */
final class ReferenceFreeRemover extends MutableTypeVisitor
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

        if ($type instanceof Union) {
            $type = $type->setProperties(['reference_free' => false]);
        }

        return null;
    }
}
