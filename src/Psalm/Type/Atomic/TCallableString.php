<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `callable-string` type, used to represent an unknown string that is also `callable`.
 *
 * @psalm-immutable
 */
final class TCallableString extends TNonFalsyString
{
    #[Override]
    public function isCallableType(): bool
    {
        return true;
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'callable-string';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->getKey();
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'string';
    }
}
