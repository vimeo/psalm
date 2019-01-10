<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

class TGenericObject extends TNamedObject
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
     * @return string|null
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

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if (count($this->type_params) !== count($other_type->type_params)) {
            return false;
        }

        foreach ($this->type_params as $i => $type_param) {
            if (!$type_param->equals($other_type->type_params[$i])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return $this->value;
    }
}
