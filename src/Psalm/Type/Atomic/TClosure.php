<?php

namespace Psalm\Type\Atomic;

/**
 * Represents a closure where we know the return type and params
 */
final class TClosure extends TNamedObject
{
    use CallableTrait;

    /** @var array<string, bool> */
    public $byref_uses = [];

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
