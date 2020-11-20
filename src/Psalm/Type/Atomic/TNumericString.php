<?php
namespace Psalm\Type\Atomic;

class TNumericString extends TString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'numeric-string';
    }

    public function __toString(): string
    {
        return 'numeric-string';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'string';
    }
}
