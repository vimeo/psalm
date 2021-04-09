<?php
namespace Psalm\Type\Atomic;

/**
 * Represents a string whose value is that of a type found by gettype($var)
 */
class TDependentGetType extends TString
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        TNonEmptyString::class => true,
        TNonFalsyString::class => true,
    ];

    protected const COERCIBLE_TO = parent::COERCIBLE_TO + [
        TLowercaseString::class => true,
        TNonEmptyLowercaseString::class => true,
    ];

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
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return false;
    }
}
