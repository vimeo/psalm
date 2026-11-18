<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

/**
 * Narrows the bound of a template to what a conditional type on it says, in the branch where it holds: in
 * `(T is Animal ? Zoo<T> : string)` the `T` of `Zoo<T>` is an `Animal`
 *
 * @internal
 */
final class TemplateBoundNarrower extends MutableTypeVisitor
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly string $param_name,
        private readonly string $defining_class,
        private readonly Union $narrowed_bound,
        private readonly Codebase $codebase,
    ) {
    }

    #[Override]
    protected function enterNode(TypeNode &$type): ?int
    {
        if (!$type instanceof TTemplateParam
            || $type->param_name !== $this->param_name
            || $type->defining_class !== $this->defining_class
        ) {
            return null;
        }

        $type = $type->replaceAs(
            $type->as->isMixed()
                ? $this->narrowed_bound
                : Type::intersectUnionTypes($type->as, $this->narrowed_bound, $this->codebase) ?? $type->as,
        );

        return null;
    }
}
