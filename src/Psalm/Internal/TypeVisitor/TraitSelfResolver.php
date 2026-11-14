<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;

use function strtolower;

/**
 * Replaces the `self` at any depth of a type by the class it stands for: `self` written in a method of a trait is the
 * class using the trait.
 *
 * @internal
 */
final class TraitSelfResolver extends MutableTypeVisitor
{
    public function __construct(private readonly string $class)
    {
    }

    /**
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if ($type instanceof TNamedObject && !$type->is_static && strtolower($type->value) === 'self') {
            $type = new TNamedObject($this->class);
        }

        return null;
    }
}
