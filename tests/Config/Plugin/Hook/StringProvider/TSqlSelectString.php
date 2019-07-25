<?php
namespace Psalm\Test\Config\Plugin\Hook\StringProvider;

class TSqlSelectString extends \Psalm\Type\Atomic\TLiteralString
{
    /**
     * @return string
     */
    public function getKey()
    {
        return 'sql-select-string';
    }

    /**
     * @return string
     */
    public function getId()
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
