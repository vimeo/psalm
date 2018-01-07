<?php
namespace Psalm\Type\Atomic;

class TVoid extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'void';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'void';
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
        return $php_major_version >= 7 && $php_minor_version >= 1 ? $this->getKey() : null;
    }

    public function canBeFullyExpressedInPhp()
    {
        return true;
    }
}
