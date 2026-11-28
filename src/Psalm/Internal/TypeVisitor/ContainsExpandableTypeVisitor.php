<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\Atomic\TIntMask;
use Psalm\Type\Atomic\TIntMaskOf;
use Psalm\Type\Atomic\TKeyOf;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TTypeAlias;
use Psalm\Type\Atomic\TValueOf;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;

/**
 * Whether a type holds parts that only {@see \Psalm\Internal\Type\TypeExpander} turns into the types they stand for:
 * class constants, type aliases, `key-of`, `value-of`, `properties-of`, int masks and conditionals.
 *
 * @internal
 */
final class ContainsExpandableTypeVisitor extends TypeVisitor
{
    private bool $contains_expandable = false;

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TClassConstant
            || $type instanceof TTypeAlias
            || $type instanceof TKeyOf
            || $type instanceof TValueOf
            || $type instanceof TPropertiesOf
            || $type instanceof TIntMask
            || $type instanceof TIntMaskOf
            || $type instanceof TConditional
        ) {
            $this->contains_expandable = true;

            return self::STOP_TRAVERSAL;
        }

        return null;
    }

    public function matches(): bool
    {
        return $this->contains_expandable;
    }
}
