<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TNamedObject extends Atomic
{
    /**
     * @var string
     */
    public $value;

    /**
     * @var TNamedObject[]|null
     */
    public $extra_types;

    /**
     * @param string $value the name of the object
     */
    public function __construct($value)
    {
        if ($value[0] === '\\') {
            $value = substr($value, 1);
        }

        $this->value = $value;
    }

    public function __toString()
    {
        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getKey()
    {
        if ($this->extra_types) {
            return $this->value . '&' . implode('&', $this->extra_types);
        }

        return $this->value;
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
        $class_parts = explode('\\', $this->value);
        $class_name = array_pop($class_parts);

        if ($this->value === $this_class) {
            return 'self';
        }

        if ($namespace && preg_match('/^' . preg_quote($namespace) . '\\\\' . $class_name . '$/i', $this->value)) {
            return $class_name;
        }

        if (!$namespace && stripos($this->value, '\\') === false) {
            return $this->value;
        }

        if (isset($aliased_classes[strtolower($this->value)])) {
            return $aliased_classes[strtolower($this->value)];
        }

        return '\\' . $this->value;
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
        return $this->toNamespacedString($namespace, $aliased_classes, $this_class, false);
    }

    public function canBeFullyExpressedInPhp()
    {
        return true;
    }

    /**
     * @param TNamedObject $type
     *
     * @return void
     */
    public function addIntersectionType(TNamedObject $type)
    {
        $this->extra_types[] = $type;
    }

    /**
     * @return TNamedObject[]|null
     */
    public function getIntersectionTypes()
    {
        return $this->extra_types;
    }
}
