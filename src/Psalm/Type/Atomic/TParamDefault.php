<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Internal\Scanner\UnresolvedConstantComponent;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * The value of a parameter default that reads constants, which `addParamDefaultToDocblockType` adds to the type of
 * the parameter: it is known once the codebase is scanned, and expanded by TypeExpander.
 *
 * @internal
 * @psalm-immutable
 */
final class TParamDefault extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param string $param_id the parameter, as `f($name)` or `A::f($name)`
     */
    public function __construct(
        public UnresolvedConstantComponent $value,
        public string $param_id,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'default-of(' . $this->param_id . ')';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'default-of(' . $this->param_id . ')';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAssertionString(): string
    {
        return 'mixed';
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
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

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        return 'mixed';
    }
}
