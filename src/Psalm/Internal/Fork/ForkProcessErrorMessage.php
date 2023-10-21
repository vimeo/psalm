<?php

declare(strict_types=1);

namespace Psalm\Internal\Fork;

use Psalm\Storage\ImmutableNonCloneableTrait;

/**
 * @psalm-immutable
 * @internal
 */
final class ForkProcessErrorMessage implements ForkMessage
{
    use ImmutableNonCloneableTrait;

    public function __construct(public string $message)
    {
    }
}
