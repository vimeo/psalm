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

    public function getId()
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
