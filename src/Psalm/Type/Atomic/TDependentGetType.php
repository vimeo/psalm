<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Represents a string whose value is that of a type found by gettype($var)
 *
 * @psalm-immutable
 */
final class TDependentGetType extends TString
{
    /**
     * @param string $typeof the variable id
     */
    public function __construct(public string $typeof)
    {
        parent::__construct(false);
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
