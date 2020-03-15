<?php
namespace Psalm\Type\Atomic;

use function count;
use function get_class;
use Psalm\Type\Atomic;
use Psalm\CodeLocation;
use Psalm\StatementsSource;

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
     * @param non-empty-list<\Psalm\Type\Union> $type_params
     */
    public function __construct(array $type_params)
    {
        $this->type_params = $type_params;
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
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
        return $this->type_params[0]->isArrayKey() && $this->type_params[1]->isMixed();
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (get_class($other_type) !== static::class) {
            return false;
        }

        if ($this instanceof TNonEmptyArray
            && $other_type instanceof TNonEmptyArray
            && $this->count !== $other_type->count
        ) {
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
        return $this->getKey();
    }
}
