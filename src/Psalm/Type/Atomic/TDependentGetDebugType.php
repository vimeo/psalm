<?php

namespace Psalm\Type\Atomic;

/**
 * Represents a string whose value is that of a type found by get_debug_type($var)
 *
 * @psalm-immutable
 */
final class TDependentGetDebugType extends TString implements DependentType
{
    /**
     * Used to hold information as to what this refers to
     *
     * @var string
     */
    public $typeof;

    /**
     * @param string $typeof the variable id
     */
    public function __construct(string $typeof)
    {
        $this->typeof = $typeof;
        parent::__construct(false);
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'get-debug-type-of<' . $this->typeof . '>';
    }

    public function getVarId(): string
    {
        return $this->typeof;
    }

    public function getReplacement(): TString
    {
        return new TString();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
