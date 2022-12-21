<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

use function array_map;
use function implode;

/**
 * @psalm-immutable
 */
final class TTypeAlias extends Atomic
{
    /**
     * @var array<string, TTypeAlias>|null
     */
    public $extra_types;

    /** @var string */
    public $declaring_fq_classlike_name;

    /** @var string */
    public $alias_name;

    /**
     * @param array<string, TTypeAlias>|null $extra_types
     */
    public function __construct(string $declaring_fq_classlike_name, string $alias_name, ?array $extra_types = null)
    {
        $this->declaring_fq_classlike_name = $declaring_fq_classlike_name;
        $this->alias_name = $alias_name;
        $this->extra_types = $extra_types;
    }
    /**
     * @param array<string, TTypeAlias>|null $extra_types
     */
    public function setIntersectionTypes(?array $extra_types): self
    {
        if ($extra_types === $this->extra_types) {
            return $this;
        }
        return new self(
            $this->declaring_fq_classlike_name,
            $this->alias_name,
            $extra_types,
        );
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'type-alias(' . $this->declaring_fq_classlike_name . '::' . $this->alias_name . ')';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if ($this->extra_types) {
            return $this->getKey() . '&' . implode(
                '&',
                array_map(
                    static fn(Atomic $type): string => $type->getId($exact, true),
                    $this->extra_types,
                ),
            );
        }

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

    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
