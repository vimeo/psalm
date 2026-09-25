<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\Capabilities;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * A set of capabilities used as a type: the purity of a closure type (`Closure<pure>(): void`),
 * the argument of a purity template (`Deferred<write-props>`) or the bound of one.
 *
 * `pure` (no capabilities) is a subtype of every other capability set, and `impure` is the top.
 *
 * @psalm-immutable
 * @api
 */
final class TCapabilities extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    public function __construct(
        public int $capabilities,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return Capabilities::toString($this->capabilities);
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->getKey();
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
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

    /** @psalm-pure */
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
        return $this->getKey();
    }
}
