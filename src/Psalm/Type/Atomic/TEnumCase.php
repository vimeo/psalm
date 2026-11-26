<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Type;
use Psalm\Type\Atomic;

/**
 * Denotes an enum with a specific value
 *
 * @psalm-immutable
 * @api
 */
final class TEnumCase extends TNamedObject
{
    public function __construct(string $fq_enum_name, public string $case_name)
    {
        parent::__construct($fq_enum_name);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'enum(' . $this->value . '::' . $this->case_name . ')';
    }

    #[Override]
    public function getId(bool $exact = true): string
    {
        return 'enum(' . $this->value . '::' . $this->case_name . ')';
    }

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        return $other_type instanceof self
            && $this->case_name === $other_type->case_name
            && parent::equals($other_type, $ensure_source_equality);
    }

    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return Type::getStringFromFQCLN($this->value, $namespace, $aliased_classes, $this_class, true);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        return Type::getStringFromFQCLN($this->value, $namespace, $aliased_classes, $this_class, true)
            . '::' . $this->case_name;
    }
}
