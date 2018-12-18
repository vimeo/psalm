<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TGenericParamClass extends TClassString
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $extends;

    /**
     * @param string $param_name
     */
    public function __construct($param_name, string $extends = 'object')
    {
        $this->param_name = $param_name;
        $this->extends = $extends;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'class-string<' . $this->param_name . '>';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'class-string<' . $this->param_name . '>';
    }

    /**
     * @return string
     */
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
     * @return string|null
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

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
