<?php
namespace Psalm\Type\Atomic;

class TCallable extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'callable';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'callable';
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
        return null;
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
