<?php
namespace Psalm\Type\Atomic;

class TClassString extends TString
{
    /**
     * @var string
     */
    public $extends;

    /**
     * @param string $param_name
     */
    public function __construct(string $extends = 'object')
    {
        $this->extends = $extends;
    }

     /**
     * @return string
     */
    public function getKey()
    {
        return 'class-string' . ($this->extends === 'object' ? '' : '<' . $this->extends . '>');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getKey();
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

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
