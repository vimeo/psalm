<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Represents a string whose value is that of a type found by get_debug_type($var)
 *
 * @psalm-immutable
 */
final class TDependentGetDebugType extends TString implements DependentType
{
    /**
     * @param string $typeof the variable id
     */
    public function __construct(public string $typeof)
    {
        parent::__construct(false);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'get-debug-type-of<' . $this->typeof . '>';
    }

    #[Override]
    public function getVarId(): string
    {
        return $this->typeof;
    }

    #[Override]
    public function getReplacement(): TString
    {
        return new TString();
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
