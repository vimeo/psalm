<?php
namespace Psalm\Type\Atomic;

abstract class Scalar extends \Psalm\Type\Atomic
{
    /** @var true|null */
    public $strict;

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
        return $php_major_version >= 7 ? $this->getKey() : null;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return true;
    }
}
