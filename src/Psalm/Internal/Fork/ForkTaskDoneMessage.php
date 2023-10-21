<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class ForkTaskDoneMessage implements ForkMessage
{
    use ImmutableNonCloneableTrait;

    public mixed $data;

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }
}
