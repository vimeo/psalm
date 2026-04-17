<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Override;
use Psalm\CodeLocation;
use Psalm\Storage\Mutations;
use Stringable;

use function strtolower;

/**
 * @psalm-consistent-constructor
 * @internal
 * @psalm-external-mutation-free
 */
final class UseFlowNode implements Stringable
{
    public bool $visited_secondary = false;
    /**
     * @psalm-mutation-free
     */
    public function __construct(
        public readonly string $id,
        /**
         * @var Mutations::*
         */
        public int $mutation_level = Mutations::LEVEL_NONE,
        public bool $used = false,
    ) {
    }

    /**
     * @psalm-mutation-free
     */
    public function addMutationLevel(int $mutation_level): void
    {
        if ($this->mutation_level >= $mutation_level) {
            return;
        }
        $this->mutation_level = $mutation_level;
    }

    /**
     * @psalm-mutation-free
     */
    private function __clone()
    {
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function __toString(): string
    {
        return $this->id;
    }
}
