<?php

declare(strict_types=1);

namespace Psalm\Internal\ExecutionEnvironment;

use function in_array;

/**
 * The pull request a build is made for.
 *
 * @psalm-immutable
 * @internal
 */
final class PullRequest
{
    /**
     * @param ?Repository $head the repository the pull request comes from, when it is known
     */
    public function __construct(
        public readonly string $number,
        public readonly ?Repository $head = null,
    ) {
    }

    /**
     * The pull request of a number, or null when the number says there is none: CI services put "false"
     * or "0" there, or nothing, for a build that is not made for a pull request.
     *
     * @psalm-pure
     */
    public static function fromNumber(?string $number, ?Repository $head = null): ?self
    {
        if ($number === null || in_array($number, ['', '0', 'false'], true)) {
            return null;
        }

        return new self($number, $head);
    }
}
