<?php

namespace Psalm\Internal\Fork;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class ForkProcessErrorMessage implements ForkMessage
{
    use ImmutableNonCloneableTrait;

    public string $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
