<?php
namespace Psalm\Type\Atomic;

class TClassString extends TString
{
    /**
     * @var string
     */
    public $class_type;

    /**
     * @param string $class_type string
     */
    public function __construct($class_type = 'object') {
        $this->class_type = $class_type;
    }

    public function __toString()
    {
        return 'class-string';
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'class-string';
    }

    public function getId()
    {
        return $this->getKey();
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
