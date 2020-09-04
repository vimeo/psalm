<?php
namespace Psalm\Type\Atomic;

class TCallableObject extends TObject
{
    public function __toString(): string
    {
        return 'callable-object';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'callable-object';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ): ?string {
        return $php_major_version >= 7 && $php_minor_version >= 2 ? 'object' : null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'object';
    }
}
