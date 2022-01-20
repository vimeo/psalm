<?php

namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Atomic;

/**
 * Represents a value of a class constant array.
 */
class TValueOfClassConstant extends Atomic
{
    /** @var string */
    public $fq_classlike_name;

    /** @var string */
    public $const_name;

    public function __construct(string $fq_classlike_name, string $const_name)
    {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->const_name = $const_name;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'value-of<' . $this->fq_classlike_name . '::' . $this->const_name . '>';
    }

    public function __toString(): string
    {
        return 'value-of<' . $this->fq_classlike_name . '::' . $this->const_name . '>';
    }

    public function getId(bool $nested = false): string
    {
        return $this->getKey();
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string {
        return null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        if ($this->fq_classlike_name === 'static') {
            return 'value-of<static::' . $this->const_name . '>';
        }

        return 'value-of<'
            . Type::getStringFromFQCLN($this->fq_classlike_name, $namespace, $aliased_classes, $this_class)
            . '>::' . $this->const_name . '>';
    }

    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
