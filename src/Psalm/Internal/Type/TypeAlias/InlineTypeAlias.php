<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\TypeAlias;

use Psalm\Internal\Type\TypeAlias;
use Psalm\Storage\ImmutableNonCloneableTrait;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;

/**
 * @internal
 * @psalm-immutable
 */
final class InlineTypeAlias implements TypeAlias
{
    use ImmutableNonCloneableTrait;
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param list<array{0: string, 1: int, 2?: string}> $replacement_tokens
     * @psalm-mutation-free
     */
    public function __construct(public readonly array $replacement_tokens)
    {
    }
}
