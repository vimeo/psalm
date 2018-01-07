<?php
namespace Psalm\Type\Atomic;

class TBool extends Scalar
{
    public function __toString()
    {
        return 'bool';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'bool';
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return ?string
     */
    public function toPhpString(array $aliased_classes, $this_class, $php_major_version, $php_minor_version)
    {
        return $php_major_version >= 7 ? 'bool' : null;
    }
}
