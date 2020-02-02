<?php
namespace Psalm\Type\Atomic;

class THtmlEscapedString extends TString
{
    /**
     * @return string
     */
    public function getKey()
    {
        return 'html-escaped-string';
    }

    public function getId(bool $nested = false)
    {
        return $this->getKey();
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
