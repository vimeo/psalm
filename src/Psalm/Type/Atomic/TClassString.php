<?php
namespace Psalm\Type\Atomic;

class TClassString extends TLiteralString
{
    /**
     * @param string $value string
     */
    public function __construct($value = 'object')
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return 'class-string';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return string
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return 'string';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
