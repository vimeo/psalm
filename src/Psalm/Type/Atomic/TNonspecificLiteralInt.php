<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `literal-int` type, where the exact value is unknown but
 * we know that the int is not from user input
 *
 * @psalm-immutable
 */
final class TNonspecificLiteralInt extends TInt
{
    public function getId(bool $exact = true, bool $nested = true): string
    {
        return 'literal-int';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'int';
    }
}
