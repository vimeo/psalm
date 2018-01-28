<?php
namespace Psalm\Type\Atomic;

class TObject extends \Psalm\Type\Atomic
{
    public function __toString()
    {
        return 'object';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'object';
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
    ) {
        return $php_major_version >= 7 && $php_minor_version >= 2 ? $this->getKey() : null;
    }

    public function canBeFullyExpressedInPhp()
    {
        return true;
    }
}
