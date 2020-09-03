<?php
namespace Psalm\Type\Atomic;

class TVoid extends \Psalm\Type\Atomic
{
    public function __toString(): string
    {
        return 'void';
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'void';
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
        return $php_major_version >= 7 && $php_minor_version >= 1 ? $this->getKey() : null;
    }

    public function canBeFullyExpressedInPhp(): bool
    {
        return true;
    }
}
