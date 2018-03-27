<?php
namespace Psalm\Type\Atomic;

class TCallable extends \Psalm\Type\Atomic
{
    use CallableTrait;

    /**
     * @var string
     */
    public $value;

    public function __toString()
    {
        $param_string = '';
        $return_type_string = '';

        if ($this->params !== null) {
            $param_string = '(' . implode(', ', $this->params) . ')';
        }

        if ($this->return_type !== null) {
            $return_type_string = ' : ' . $this->return_type;
        }

        return 'callable' . $param_string . $return_type_string;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'callable';
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
        return 'callable';
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
