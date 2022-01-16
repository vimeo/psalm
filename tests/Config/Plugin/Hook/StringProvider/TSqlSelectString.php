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

    public function getId(bool $exact = true, bool $nested = true): string
    {
        return 'sql-select-string(' . $this->value . ')';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
