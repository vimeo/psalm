<?php
namespace Psalm\Type\Atomic;

class TScalar extends Scalar
{
    public function __toString(): string
    {
        return 'scalar';
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true): string
    {
        return 'scalar';
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

    /**
     * @return string
     */
    public function getAssertionString(): string
    {
        return 'scalar';
    }
}
