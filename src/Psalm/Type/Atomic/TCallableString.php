<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `callable-string` type, used to represent an unknown string that is also `callable`.
 *
 * @psalm-immutable
 */
final class TCallableString extends TNonFalsyString implements TCallableInterface
{

    public function getKey(bool $include_extra = true): string
    {
        return 'callable-string';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'string';
    }
}
