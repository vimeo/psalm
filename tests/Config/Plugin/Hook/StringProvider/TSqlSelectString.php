<?php
namespace Psalm\Test\Config\Plugin\Hook\StringProvider;

use Psalm\Type\Atomic\TLiteralString;

/**
 * Special type, specifically for consumption by plugins.
 */
class TSqlSelectString extends TLiteralString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'sql-select-string';
    }

    public function getId(bool $nested = true): string
    {
        return 'sql-select-string(' . $this->value . ')';
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
