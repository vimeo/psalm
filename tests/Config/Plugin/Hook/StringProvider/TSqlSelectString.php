<?php
namespace Psalm\Test\Config\Plugin\Hook\StringProvider;

class TSqlSelectString extends \Psalm\Type\Atomic\TLiteralString
{
    public function getKey(bool $include_extra = true) : string
    {
        return 'sql-select-string';
    }

    /**
     * @return string
     */
    public function getId(bool $nested = true)
    {
        return 'sql-select-string(' . $this->value . ')';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
