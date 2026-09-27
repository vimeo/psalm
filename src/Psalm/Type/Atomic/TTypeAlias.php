<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Interner;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 * @api
 */
final class TTypeAlias extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param int $declaring_fq_classlike_name interned class name
     * @param int $alias_name interned alias name
     */
    public function __construct(
        public int $declaring_fq_classlike_name,
        public int $alias_name,
    ) {
        parent::__construct(true);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'type-alias(' . Interner::str($this->declaring_fq_classlike_name)
            . '::' . Interner::str($this->alias_name) . ')';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->getKey();
    }

    /**
     * @param array<int, int> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return null;
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
     * @psalm-pure
     */
    #[Override]
    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
