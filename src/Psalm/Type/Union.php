<?php

namespace Psalm\Type;

use Psalm\Type;
use Psalm\Checker\ClassLikeChecker;
use Psalm\Checker\ClassChecker;

class Union extends Type
{
    /** @var array<string, Atomic> */
    public $types = [];

    /**
     * Constructs an Union instance
     * @param array<int, AtomicType>     $types
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
                function ($type) {
                    return (string) $type;
                },
                $this->types
            )
        );
    }

    public function removeType($type_string)
    {
        unset($this->types[$type_string]);
    }

    public function hasType($type_string)
    {
        return isset($this->types[$type_string]);
    }

    public function hasGeneric()
    {
        foreach ($this->types as $type) {
            if ($type instanceof Generic) {
                return true;
            }
        }

        return false;
    }

    public function hasArray()
    {
        return isset($this->types['array']);
    }

    public function hasObject()
    {
        return isset($this->types['object']);
    }

    public function hasObjectType()
    {
        foreach ($this->types as $type) {
            if ($type->isObjectType()) {
                return true;
            }
        }

        return false;
    }

    public function isNullable()
    {
        return isset($this->types['null']);
    }

    public function hasString()
    {
        return isset($this->types['string']);
    }

    public function hasNumeric()
    {
        return isset($this->types['numeric']);
    }

    public function hasScalar()
    {
        return isset($this->types['scalar']);
    }

    public function hasResource()
    {
        return isset($this->types['resource']);
    }

    public function hasCallable()
    {
        return isset($this->types['callable']);
    }

    public function removeObjects()
    {
        foreach ($this->types as $key => $type) {
            if ($key[0] === strtoupper($key[0])) {
                unset($this->types[$key]);
            }
        }
    }

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

    public function isIn(Union $parent)
    {
        foreach ($this->types as $type) {
            if (!$type->isIn($parent)) {
                return false;
            }
        }

        return true;
    }
}
