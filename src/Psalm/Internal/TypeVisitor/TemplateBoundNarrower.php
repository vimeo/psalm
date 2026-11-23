<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Type;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\MutableTypeVisitor;
use Psalm\Type\TypeNode;
use Psalm\Type\Union;

use function count;

/**
 * Narrows the bound of a template to what a conditional type on it says, in the branch where it holds: in
 * `(T is Animal ? Zoo<T> : string)` the `T` of `Zoo<T>` is an `Animal`. In the other branch the template is none of
 * the types of its bound the condition holds for: in `(T is array ? string : Zoo<T>)` with `T of array|Iterator`,
 * the `T` of `Zoo<T>` is an `Iterator`
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
        private readonly bool $in_else_branch = false,
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

        if ($this->in_else_branch) {
            $remaining_bound = [];

            foreach ($type->as->getAtomicTypes() as $key => $bound_type) {
                $comparison_result = new TypeComparisonResult();

                if (!UnionTypeComparator::isContainedBy(
                    $this->codebase,
                    new Union([$bound_type]),
                    $this->narrowed_bound,
                    false,
                    false,
                    $comparison_result,
                    false,
                    false,
                ) || $comparison_result->to_string_cast) {
                    $remaining_bound[$key] = $bound_type;
                }
            }

            if ($remaining_bound && count($remaining_bound) < count($type->as->getAtomicTypes())) {
                $type = $type->replaceAs(new Union($remaining_bound));
            }

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
