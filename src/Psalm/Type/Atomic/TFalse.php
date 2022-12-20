<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes the `false` value type
 *
 * @psalm-immutable
 */
final class TFalse extends TBool
{
    /** @var false */
    public $value = false;

    public function getKey(bool $include_extra = true): string
    {
        return 'false';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
