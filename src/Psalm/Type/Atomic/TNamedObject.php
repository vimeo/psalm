<?php
namespace Psalm\Type\Atomic;

class TNamedObject extends \Psalm\Type\Atomic
{
    /**
     * @var string
     */
    public $value;

    /**
     * @param string $value the name of the object
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->value;
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if ($this->value === $this_class) {
            $class_parts = explode('\\', $this_class);
            return array_pop($class_parts);
        }

        if (isset($aliased_classes[strtolower($this->value)])) {
            return $aliased_classes[strtolower($this->value)];
        }

        return '\\' . $this->value;
    }
}
