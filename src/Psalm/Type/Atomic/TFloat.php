<?php
namespace Psalm\Type\Atomic;

/**
 * Denotes the `float` type, where the exact value is unknown.
 */
class TFloat extends TScalar
{
    protected const SUPERTYPES = parent::SUPERTYPES + [
        self::class => true,
        TNumeric::class => true,
    ];

    public function __toString(): string
    {
        return 'float';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'float';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return $php_major_version >= 7 ? 'float' : null;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return true;
    }
}
