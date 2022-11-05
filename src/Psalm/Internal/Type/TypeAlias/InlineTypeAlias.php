<?php

namespace Psalm\Internal\Type\TypeAlias;

use Psalm\Internal\Type\TypeAlias;
use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 *
 * @internal
 */
class InlineTypeAlias implements TypeAlias
{
    use ImmutableNonCloneableTrait;

    /**
     * @var list<strict-array{0: string, 1: int, 2?: string}>
     */
    public $replacement_tokens;

    /**
     * @param list<strict-array{0: string, 1: int, 2?: string}> $replacement_tokens
     */
    public function __construct(array $replacement_tokens)
    {
        $this->replacement_tokens = $replacement_tokens;
    }
}
