<?php
namespace Psalm\Type\Atomic;

class TNumericString extends TString
{
    /**
     * @return string
     */
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

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getAssertionString(): string
    {
        return 'string';
    }
}
