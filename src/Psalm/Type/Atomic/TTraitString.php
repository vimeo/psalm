<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * Denotes the `trait-string` type, used to describe a string representing a valid PHP trait.
 *
 * @psalm-immutable
 * @api
 */
final class TTraitString extends TString
{
    /**
     * @param bool $is_loaded whether the trait is known to be loaded already (`trait_exists($t, false)`), so that a
     *                        failed check says nothing about the string
     */
    public function __construct(
        bool $from_docblock = false,
        public bool $is_loaded = false,
    ) {
        parent::__construct($from_docblock);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'trait-string';
    }

    #[Override]
    public function getId(bool $exact = true): string
    {
        return ($this->is_loaded ? 'loaded-' : '') . 'trait-string';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAssertionString(): string
    {
        return 'trait-string';
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
        return 'string';
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
        return 'trait-string';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
