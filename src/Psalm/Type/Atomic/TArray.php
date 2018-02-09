<?php
namespace Psalm\Type\Atomic;

/**
 * Represents an array with generic type parameters.
 */
class TArray extends \Psalm\Type\Atomic
{
    use GenericTrait;

    /**
     * @var string
     */
    public $value = 'array';

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<int, \Psalm\Type\Union> $type_params
     */
    public function __construct(array $type_params)
    {
        $this->type_params = $type_params;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'array';
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
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp()
    {
        return $this->type_params[0]->isMixed() && $this->type_params[1]->isMixed();
    }
}
