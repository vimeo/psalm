<?php
namespace Psalm\Type\Atomic;

class THtmlEscapedString extends TString
{
    /**
     * @return string
     */
    public function getKey(bool $include_extra = true): string
    {
        return 'html-escaped-string';
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
}
