<?php
namespace Psalm\Type\Atomic;

class TNumeric extends Scalar
{
    public function __toString(): string
    {
        return 'numeric';
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true): string
    {
        return 'numeric';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return null|string
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ): ?string {
        return null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }
}
