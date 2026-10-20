<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * Denotes the global constants matching a wildcard (`PHP_URL_*`), whose values might not yet be known.
 *
 * @psalm-immutable
 * @api
 */
final class TGlobalConstant extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param string $const_name the fully qualified name of the constants, with wildcards
     * @param ?string $fallback_const_name the name of the global constants matched when the constants
     *        aren't qualified and none of the constants of the namespace match, as for `const_name` in PHP
     */
    public function __construct(
        public string $const_name,
        public ?string $fallback_const_name = null,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'global-constant(' . $this->const_name
            . ($this->fallback_const_name === null ? '' : '|' . $this->fallback_const_name) . ')';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->const_name;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'global-constant(' . $this->const_name . ')';
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
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        return $this->fallback_const_name ?? '\\' . $this->const_name;
    }
}
