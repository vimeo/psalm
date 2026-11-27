<?php

declare(strict_types=1);

namespace Psalm\Internal\TypeVisitor;

use Override;
use Psalm\Type\Atomic\TConditional;
use Psalm\Type\TypeNode;
use Psalm\Type\TypeVisitor;
use Psalm\Type\Union;

/**
 * Collects the types a conditional type tests its subject against (`INF` in `($n is INF ? 'a' : string)`)
 *
 * @internal
 */
final class ConditionTypeCollector extends TypeVisitor
{
    /** @var list<Union> */
    private array $conditions = [];

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    protected function enterNode(TypeNode $type): ?int
    {
        if ($type instanceof TConditional) {
            $this->conditions[] = $type->conditional_type;
        }

        return null;
    }

    /**
     * @return list<Union>
     * @psalm-mutation-free
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }
}
