<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TIterable extends Atomic
{
    use HasIntersectionTrait;
    use GenericTrait;

    /**
     * @var string
     */
    public $value = 'iterable';

    /**
     * @var bool
     */
    public $has_docblock_params = false;

    /**
     * @param array<int, \Psalm\Type\Union>     $type_params
     */
    public function __construct(array $type_params = [])
    {
        if ($type_params) {
            $this->has_docblock_params = true;
            $this->type_params = $type_params;
        } else {
            $this->type_params = [\Psalm\Type::getMixed(), \Psalm\Type::getMixed()];
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'iterable';
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
        return $php_major_version >= 7 && $php_minor_version >= 1 ? 'iterable' : null;
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return $this->type_params[0]->isMixed() && $this->type_params[1]->isMixed();
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (!$other_type instanceof self) {
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
}
