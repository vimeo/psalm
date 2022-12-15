<?php

namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Atomic;

/**
 * Denotes a class constant whose value might not yet be known.
 *
 * @psalm-immutable
 */
final class TClassConstant extends Atomic
{
    /** @var string */
    public $fq_classlike_name;

    /** @var string */
    public $const_name;

    public function __construct(string $fq_classlike_name, string $const_name, bool $from_docblock = false)
    {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->const_name = $const_name;
        $this->from_docblock = $from_docblock;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'class-constant(' . $this->fq_classlike_name . '::' . $this->const_name . ')';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->fq_classlike_name . '::' . $this->const_name;
    }

    public function getAssertionString(): string
    {
        return 'class-constant(' . $this->fq_classlike_name . '::' . $this->const_name . ')';
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
            return 'static::' . $this->const_name;
        }

        return Type::getStringFromFQCLN($this->fq_classlike_name, $namespace, $aliased_classes, $this_class)
            . '::'
            . $this->const_name;
    }
}
