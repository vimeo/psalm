<?php
namespace Psalm\Type;

use Psalm\Type;

class Union extends Type
{
    /**
     * @var array<string,Atomic>
     */
    public $types = [];

    /**
     * Constructs an Union instance
     * @param array<int,Atomic>     $types
     */
    public function __construct(array $types)
    {
        foreach ($types as $type) {
            $this->types[$type->value] = $type;
        }
    }

    public function __clone()
    {
        foreach ($this->types as &$type) {
            $type = clone $type;
        }
    }

    public function __toString()
    {
        return implode(
            '|',
            array_map(
                /**
                 * @param string $type
                 * @return string
                 */
                function ($type) {
                    return $type;
                },
                $this->types
            )
        );
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string        $this_class
     * @param  bool          $use_phpdoc_format
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        return implode(
            '|',
            array_map(
                /**
                 * @return string
                 */
                function (Atomic $type) use ($aliased_classes, $this_class, $use_phpdoc_format) {
                    return $type->toNamespacedString($aliased_classes, $this_class, $use_phpdoc_format);
                },
                $this->types
            )
        );
    }

    /** @return void */
    public function removeType($type_string)
    {
        unset($this->types[$type_string]);
    }

    /**
     * @param  string  $type_string
     * @return boolean
     */
    public function hasType($type_string)
    {
        return isset($this->types[$type_string]);
    }

    /**
     * @return boolean
     */
    public function hasGeneric()
    {
        foreach ($this->types as $type) {
            if ($type instanceof Generic) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function hasArray()
    {
        return isset($this->types['array']);
    }

    /**
     * @return boolean
     */
    public function hasObject()
    {
        return isset($this->types['object']);
    }

    /**
     * @return boolean
     */
    public function hasObjectLike()
    {
        return isset($this->types['array']) && $this->types['array'] instanceof ObjectLike;
    }

    /**
     * @return boolean
     */
    public function hasObjectType()
    {
        foreach ($this->types as $type) {
            if ($type->isObjectType()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return boolean
     */
    public function isNullable()
    {
        return isset($this->types['null']);
    }

    /**
     * @return boolean
     */
    public function hasString()
    {
        return isset($this->types['string']);
    }

    /**
     * @return boolean
     */
    public function hasInt()
    {
        return isset($this->types['int']);
    }

     /**
     * @return boolean
     */
    public function hasFloat()
    {
        return isset($this->types['float']);
    }

    /**
     * @return boolean
     */
    public function hasNumeric()
    {
        return isset($this->types['numeric']);
    }

    /**
     * @return boolean
     */
    public function hasNumericType()
    {
        return isset($this->types['int']) ||
            isset($this->types['float']) ||
            isset($this->types['string']);
    }

    /**
     * @return boolean
     */
    public function hasScalar()
    {
        return isset($this->types['scalar']);
    }

    /**
     * @return bool
     */
    public function hasScalarType()
    {
        return isset($this->types['int']) ||
            isset($this->types['float']) ||
            isset($this->types['string']) ||
            isset($this->types['bool']) ||
            isset($this->types['false']) ||
            isset($this->types['numeric']);
    }

    /**
     * @return boolean
     */
    public function hasResource()
    {
        return isset($this->types['resource']);
    }

    /**
     * @return boolean
     */
    public function hasCallable()
    {
        return isset($this->types['callable']);
    }

    /**
     * @return boolean
     */
    public function hasGenerator()
    {
        return isset($this->types['Generator']);
    }

    /**
     * @return boolean
     */
    public function isInt()
    {
        return isset($this->types['int']) && count($this->types) === 1;
    }

    /**
     * @return boolean
     */
    public function isMixed()
    {
        return isset($this->types['mixed']);
    }

    /**
     * @return boolean
     */
    public function isNull()
    {
        return count($this->types) === 1 && isset($this->types['null']);
    }

    /**
     * @return boolean
     */
    public function isVoid()
    {
        return isset($this->types['void']);
    }

    /**
     * @return boolean
     */
    public function isEmpty()
    {
        return isset($this->types['empty']);
    }

    /**
     * @return void
     */
    public function removeObjects()
    {
        foreach ($this->types as $key => $type) {
            if ($key[0] === strtoupper($key[0])) {
                unset($this->types[$key]);
            }
        }
    }

    /**
     * @return void
     */
    public function substitute(Union $old_type, Union $new_type = null)
    {
        if ($this->isMixed()) {
            return;
        }

        foreach ($old_type->types as $old_type_part) {
            $this->removeType($old_type_part->value);
        }

        if ($new_type) {
            foreach ($new_type->types as $key => $new_type_part) {
                $this->types[$key] = $new_type_part;
            }
        }
    }

    /**
     * @return boolean
     */
    public function isIn(Union $parent)
    {
        foreach ($this->types as $type) {
            if (!$type->isIn($parent)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return boolean
     */
    public function isSingle()
    {
        if (count($this->types) > 1) {
            return false;
        }

        $type = array_values($this->types)[0];

        if (!$type instanceof Generic) {
            return true;
        }

        return $type->type_params[count($type->type_params) - 1]->isSingle();
    }
}
