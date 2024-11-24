<?php

declare(strict_types=1);

namespace Psalm\Internal\DataFlow;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class Path
{
    use ImmutableNonCloneableTrait;

    /**
     * @param ?array<string> $unescaped_taints
     * @param ?array<string> $escaped_taints
     */
    public function __construct(
        public readonly string $type,
        public readonly int $length,
        public readonly ?array $unescaped_taints = null,
        public readonly ?array $escaped_taints = null,
    ) {
    }
}
