<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

/**
 * Represents a string whose value is a fully-qualified class found by get_class($var)
 *
 * @psalm-immutable
 */
final class TDependentGetClass extends TString implements DependentType
{
    /**
     * Used to hold information as to what this refers to
     *
     * @var string
     */
    public $typeof;

    /**
     * @var Union
     */
    public $as_type;

    /**
     * @param string $typeof the variable id
     */
    public function __construct(string $typeof, Union $as_type)
    {
        $this->typeof = $typeof;
        $this->as_type = $as_type;
        parent::__construct(false);
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->as_type->isMixed()
            || $this->as_type->hasObject()
            ? 'class-string'
            : 'class-string<' . $this->as_type->getId($exact) . '>';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'get-class-of<' . $this->typeof
            . (!$this->as_type->isMixed() && !$this->as_type->hasObject() ? ', ' . $this->as_type->getId() : '')
            . '>';
    }

    public function getVarId(): string
    {
        return $this->typeof;
    }

    public function getReplacement(): TClassString
    {
        return new TClassString();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
