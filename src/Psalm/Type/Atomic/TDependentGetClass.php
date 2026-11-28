<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Type\Union;

use function array_keys;
use function implode;

/**
 * Represents a string whose value is a fully-qualified class found by get_class($var)
 *
 * @psalm-immutable
 * @api
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
    public function getId(bool $exact = true): string
    {
        if ($this->as_type->isMixed() || $this->as_type->hasObject()) {
            return 'class-string';
        }

        // the class of an enum case is the enum
        $classes = [];
        $has_enum_case = false;

        foreach ($this->as_type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TEnumCase) {
                $has_enum_case = true;
                $classes[$atomic_type->value] = true;
            } else {
                $classes[$atomic_type->getId($exact)] = true;
            }
        }

        if (!$has_enum_case) {
            return 'class-string<' . $this->as_type->getId($exact) . '>';
        }

        return 'class-string<' . implode('|', array_keys($classes)) . '>';
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

    /**
     * @psalm-pure
     */
    #[Override]
    public function getReplacement(): TClassString
    {
        return new TClassString();
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
