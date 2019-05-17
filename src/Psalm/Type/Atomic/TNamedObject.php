<?php
namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

class TNamedObject extends Atomic
{
    use HasIntersectionTrait;

    /**
     * @var string
     */
    public $value;

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

    public function getId()
    {
        return $this->getKey();
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

        if ($this->value === 'static') {
            return 'static';
        }

        if ($this->value === $this_class) {
            return 'self' . $intersection_types;
        }

        if ($namespace && stripos($this->value, $namespace . '\\') === 0) {
            return preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->value
            ) . $intersection_types;
        }

        if (!$namespace && stripos($this->value, '\\') === false) {
            return $this->value . $intersection_types;
        }

        if (isset($aliased_classes[strtolower($this->value)])) {
            return $aliased_classes[strtolower($this->value)] . $intersection_types;
        }

        return '\\' . $this->value . $intersection_types;
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
        if ($this->value === 'static') {
            return null;
        }

        return $this->toNamespacedString($namespace, $aliased_classes, $this_class, false);
    }

    public function canBeFullyExpressedInPhp()
    {
        return $this->value !== 'static';
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types)
    {
        $this->replaceIntersectionTemplateTypesWithArgTypes($template_types);
    }
}
