<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `literal-int` type, where the exact value is unknown but
 * we know that the int is not from user input
 *
 * @psalm-immutable
 */
final class TNonspecificLiteralInt extends TInt
{
    #[Override]
    public function getId(bool $exact = true, bool $nested = true): string
    {
        return 'literal-int';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'int';
    }
}
