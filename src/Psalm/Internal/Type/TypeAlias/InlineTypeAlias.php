<?php

declare(strict_types=1);

namespace Psalm\Internal\Type\TypeAlias;

use Psalm\Internal\Type\TypeAlias;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class InlineTypeAlias implements TypeAlias
{
    use ImmutableNonCloneableTrait;

    /**
     * @param list<array{0: string, 1: int, 2?: string}> $replacement_tokens
     */
    public function __construct(public readonly array $replacement_tokens)
    {
    }
}
