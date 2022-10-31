<?php

namespace Psalm\Internal\Fork;

/**
 * @psalm-immutable
 *
 * @internal
 */
class ForkProcessErrorMessage implements ForkMessage
{
    /** @var string */
    public $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }
}
