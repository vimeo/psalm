<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Interner;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Atomic;

/**
 * Denotes a class constant whose value might not yet be known.
 *
 * @psalm-immutable
 * @api
 */
final class TClassConstant extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    /**
     * @param int $fq_classlike_name interned class name
     * @param int $const_name interned constant name (may contain `*` wildcards)
     */
    public function __construct(
        public int $fq_classlike_name,
        public int $const_name,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'class-constant(' . $this->getId() . ')';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return Interner::str($this->fq_classlike_name) . '::' . Interner::str($this->const_name);
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'class-constant(' . $this->getId() . ')';
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
     * @param array<int, int> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        if ($this->fq_classlike_name === StrId::static) {
            return 'static::' . Interner::str($this->const_name);
        }

        return Type::getStringFromFQCLN($this->fq_classlike_name, $namespace, $aliased_classes, $this_class)
            . '::'
            . Interner::str($this->const_name);
    }
}
