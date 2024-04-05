<?php

namespace Psalm\Type\Atomic;

/**
 * Represents a list key created from foreach ($list as $key => $value)
 *
 * @deprecated Will be removed in Psalm v6, use TIntRange instead
 * @psalm-immutable
 */
final class TDependentListKey extends TInt implements DependentType
{
    /**
     * Used to hold information as to what list variable this refers to
     *
     * @var string
     */
    public $var_id;

    /**
     * @param string $var_id the variable id
     */
    public function __construct(string $var_id)
    {
        $this->var_id = $var_id;
        parent::__construct(false);
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'list-key<' . $this->var_id . '>';
    }

    public function getVarId(): string
    {
        return $this->var_id;
    }

    public function getAssertionString(): string
    {
        return 'int';
    }

    public function getReplacement(): TInt
    {
        return new TInt();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
