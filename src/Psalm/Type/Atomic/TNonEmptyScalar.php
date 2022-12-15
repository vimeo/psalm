<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a `scalar` type that is also non-empty.
 *
 * @psalm-immutable
 */
final class TNonEmptyScalar extends TScalar
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'non-empty-scalar';
    }
}
