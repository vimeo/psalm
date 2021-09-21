<?php
namespace Psalm\Type\Atomic;

/**
 * Denotes the `literal-string` type, where the exact value is unknown but
 * we know that the string is not from user input
 */
class TNonEmptyNonspecificLiteralString extends TNonspecificLiteralString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        return 'non-empty-literal-string';
    }
}
