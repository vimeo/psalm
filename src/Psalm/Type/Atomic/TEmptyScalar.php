<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a `scalar` type that is also empty.
 *
 * @psalm-immutable
 */
final class TEmptyScalar extends TScalar
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'empty-scalar';
    }
}
