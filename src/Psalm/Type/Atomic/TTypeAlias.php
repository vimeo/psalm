<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class TTypeAlias extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(
        public string $declaring_fq_classlike_name,
        public string $alias_name,
    ) {
        parent::__construct(true);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'type-alias(' . $this->declaring_fq_classlike_name . '::' . $this->alias_name . ')';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->getKey();
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return null;
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
