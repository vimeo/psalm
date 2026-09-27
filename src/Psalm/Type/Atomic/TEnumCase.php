<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Interner;

/**
 * Denotes an enum with a specific value
 *
 * @psalm-immutable
 * @api
 */
final class TEnumCase extends TNamedObject
{
    /**
     * @param int $fq_enum_name interned enum name
     * @param int $case_name interned case name
     */
    public function __construct(int $fq_enum_name, public int $case_name)
    {
        parent::__construct($fq_enum_name);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'enum(' . Interner::str($this->value) . '::' . Interner::str($this->case_name) . ')';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'enum(' . Interner::str($this->value) . '::' . Interner::str($this->case_name) . ')';
    }

    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return Interner::str($this->value);
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
     * @param  array<int, int> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        return Interner::str($this->value) . '::' . Interner::str($this->case_name);
    }
}
