<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Denotes a string that has a length of 1
 *
 * @psalm-immutable
 */
final class TSingleLetter extends TNonEmptyString
{
}
