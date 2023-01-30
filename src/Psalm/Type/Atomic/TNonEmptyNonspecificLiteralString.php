<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `literal-string` type, where the exact value is unknown but
 * we know that the string is not from user input
 *
 * @psalm-immutable
 */
final class TNonEmptyNonspecificLiteralString extends TNonspecificLiteralString
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }

        return 'non-empty-literal-string';
    }
}
