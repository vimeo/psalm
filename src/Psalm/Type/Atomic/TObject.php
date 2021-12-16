<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `object` type
 */
class TObject extends Atomic
{
    /** @var array<class-string<Atomic>, true> */
    protected const CONTAINED_BY = parent::CONTAINED_BY + [
        self::class => true,
    ];

    /**
     * This intentionally does not include parent's, since TObject cannot be coerced to TFalse.
     *
     * @var array<class-string<Atomic>, true>
     */
    protected const COERCIBLE_TO = [
        TTrue::class => true,
    ];

    public function __toString(): string
    {
        return 'object';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'object';
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
        return $php_major_version > 7
            || ($php_major_version === 7 && $php_minor_version >= 2)
            ? $this->getKey()
            : null;
    }

    public function canBeFullyExpressedInPhp(int $php_major_version, int $php_minor_version): bool
    {
        return true;
    }
}
