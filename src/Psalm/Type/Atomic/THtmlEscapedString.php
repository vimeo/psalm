<?php

namespace Psalm\Type\Atomic;

/**
 * Special type, specifically for consumption by plugins.
 * @deprecated going to be removed in Psalm 5. Use taints instead.
 */
class THtmlEscapedString extends TString
{
    public function getKey(bool $include_extra = true): string
    {
        return 'html-escaped-string';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
