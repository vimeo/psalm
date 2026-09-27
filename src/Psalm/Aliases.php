<?php

declare(strict_types=1);

namespace Psalm;

use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @api
 */
final class Aliases
{
    use UnserializeMemoryUsageSuppressionTrait;

    public ?int $namespace_first_stmt_start = null;

    public ?int $uses_start = null;

    public ?int $uses_end = null;

    /**
     * @param ?int $namespace interned namespace name
     * @param array<int, int> $uses alias id (as written) => class/namespace name id
     * @param array<int, int> $functions alias id (as written) => function name id
     * @param array<int, int> $constants alias id => constant name id
     * @param array<int, int> $uses_flipped class/namespace name id => alias id
     * @param array<int, int> $functions_flipped function name id => alias id
     * @param array<int, int> $constants_flipped constant name id => alias id
     * @internal
     * @psalm-mutation-free
     */
    public function __construct(
        public ?int $namespace = null,
        public array $uses = [],
        public array $functions = [],
        public array $constants = [],
        public array $uses_flipped = [],
        public array $functions_flipped = [],
        public array $constants_flipped = [],
    ) {
    }
}
