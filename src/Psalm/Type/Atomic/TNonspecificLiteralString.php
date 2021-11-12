<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `literal-string` type, where the exact value is unknown but
 * we know that the string is not from user input
 */
class TNonspecificLiteralString extends TString
{
    public function __toString(): string
    {
        return 'literal-string';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'string';
    }
}
