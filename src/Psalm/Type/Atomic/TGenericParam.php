<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

class TGenericParam extends \Psalm\Type\Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $param_name;

    /**
     * @var Union
     */
    public $as;

    /**
     * @var ?string
     */
    public $defining_class;

    /**
     * @param string $param_name
     */
    public function __construct($param_name, Union $extends, string $defining_class = null)
    {
        $this->param_name = $param_name;
        $this->as = $extends;
        $this->defining_class = $defining_class;
    }

    public function __toString()
    {
        return $this->param_name;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        if ($this->extra_types) {
            return $this->param_name . '&' . implode('&', $this->extra_types);
        }

        return $this->param_name;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return $this->as->getId();
    }

    public function getId()
    {
        if ($this->extra_types) {
            return '(' . $this->param_name. ' as ' . $this->as->getId()
                . ')&' . implode('&', $this->extra_types);
        }

        return $this->param_name . ' as ' . $this->as->getId();
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return null
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

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString($namespace, array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        $intersection_types = $this->getNamespacedIntersectionTypes(
            $namespace,
            $aliased_classes,
            $this_class,
            $use_phpdoc_format
        );

        return $this->param_name . $intersection_types;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
