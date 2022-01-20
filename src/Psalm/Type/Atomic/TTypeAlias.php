<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

use function array_map;
use function implode;

class TTypeAlias extends Atomic
{
    /**
     * @var array<string, TTypeAlias>|null
     */
    public $extra_types;

    /** @var string */
    public $declaring_fq_classlike_name;

    /** @var string */
    public $alias_name;

    public function __construct(string $declaring_fq_classlike_name, string $alias_name)
    {
        $this->declaring_fq_classlike_name = $declaring_fq_classlike_name;
        $this->alias_name = $alias_name;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'type-alias(' . $this->declaring_fq_classlike_name . '::' . $this->alias_name . ')';
    }

    public function __toString(): string
    {
        if ($this->extra_types) {
            return $this->getKey() . '&' . implode(
                '&',
                array_map(
                    'strval',
                    $this->extra_types
                )
            );
        }

        return $this->getKey();
    }

    public function getId(bool $nested = false): string
    {
        if ($this->extra_types) {
            return $this->getKey() . '&' . implode(
                '&',
                array_map(
                    fn($type) => $type->getId(true),
                    $this->extra_types
                )
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
