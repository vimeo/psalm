<?php

namespace CodeInspector\Type;

use CodeInspector\Type;
use CodeInspector\ClassChecker;

class Union extends Type
{
    /** @var array<Type> */
    public $types = [];

    /**
     * Constructs an Union instance
     * @param array<AtomicType>     $types
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
            if ($parent->hasType('object') && ClassChecker::classExists($type->value)) {
                continue;
            }

            if ($type->value === 'false' && $parent->hasType('bool')) {
                // this is fine
                continue;
            }

            if ($parent->hasType($type->value)) {
                continue;
            }

            // last check to see if class is subclass
            if (ClassChecker::classExists($type->value)) {
                $type_is_subclass = false;

                foreach ($parent->types as $parent_type) {
                    if (ClassChecker::classExtendsOrImplements($type->value, $parent_type->value)) {
                        $type_is_subclass = true;
                        break;
                    }
                }

                if ($type_is_subclass) {
                    continue;
                }
            }

            return false;
        }

        return true;
    }
}
