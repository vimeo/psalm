<?php
namespace Psalm\Type\Atomic;

class TIterable extends \Psalm\Type\Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $value = 'iterable';

    public function __toString()
    {
        return 'iterable';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'iterable';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return true;
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return string|null
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return $php_major_version >= 7 && $php_minor_version >= 2 ? 'iterable' : null;
    }
}
