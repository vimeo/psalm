<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Represents a string whose value is that of a type found by gettype($var)
 *
 * @psalm-immutable
 * @api
 */
final class TDependentGetType extends TString implements DependentType
{
    /**
     * @param string $typeof the variable id
     */
    public function __construct(public string $typeof)
    {
        parent::__construct(false);
    }

    #[Override]
    public function getVarId(): string
    {
        return $this->typeof;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getReplacement(): TNonFalsyString
    {
        return new TNonFalsyString();
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
