<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Type\Union;

/**
 * Represents a string whose value is a fully-qualified class found by get_class($var)
 *
 * @psalm-immutable
 */
final class TDependentGetClass extends TString implements DependentType
{
    /**
     * @param string $typeof the variable id
     */
    public function __construct(public string $typeof, public Union $as_type)
    {
        parent::__construct(false);
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->as_type->isMixed()
            || $this->as_type->hasObject()
            ? 'class-string'
            : 'class-string<' . $this->as_type->getId($exact) . '>';
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'get-class-of<' . $this->typeof
            . (!$this->as_type->isMixed() && !$this->as_type->hasObject() ? ', ' . $this->as_type->getId() : '')
            . '>';
    }

    #[Override]
    public function getVarId(): string
    {
        return $this->typeof;
    }

    #[Override]
    public function getReplacement(): TClassString
    {
        return new TClassString();
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
