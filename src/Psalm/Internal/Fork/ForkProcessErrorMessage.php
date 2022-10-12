<?php

namespace Psalm\Internal\Fork;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 *
 * @internal
 */
class ForkProcessErrorMessage implements ForkMessage
{
    use ImmutableNonCloneableTrait;
    /** @var string */
    public $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
