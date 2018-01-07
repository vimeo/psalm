<?php
namespace Psalm\Type\Atomic;

class TEmpty extends Scalar
{
    public function __toString()
    {
        return 'empty';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'empty';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return ?string
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return null;
    }
}
