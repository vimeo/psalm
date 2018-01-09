<?php
namespace Psalm\Type\Atomic;

class TGenericObject extends TNamedObject implements Generic
{
    use GenericTrait;

    /**
     * @param string                            $value the name of the object
     * @param array<int, \Psalm\Type\Union>     $type_params
     */
    public function __construct($value, array $type_params)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }
        $this->value = $value;
        $this->type_params = $type_params;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
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
        return parent::toNamespacedString($namespace, $aliased_classes, $this_class, false);
    }
}
