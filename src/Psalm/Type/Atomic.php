<?php

namespace Psalm\Type;

use Psalm\Type;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;

class Atomic extends Type
{
    /** @var string */
    public $value;

    /**
     * Constructs an Atomic instance
     * @param string    $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }

    public function isIn(Union $parent)
    {
        if ($parent->isMixed()) {
            return true;
        }

        if ($parent->hasType('object') && ClassLikeChecker::classOrInterfaceExists($this->value)) {
            return true;
        }

        if ($parent->hasType('numeric') && $this->isNumericType()) {
            return true;
        }

        if ($parent->hasType('array') && $this->isObjectLike()) {
            return true;
        }

        if ($this->value === 'false' && $parent->hasType('bool')) {
            // this is fine
            return true;
        }

        if ($parent->hasType($this->value)) {
            return true;
        }

        // last check to see if class is subclass
        if (ClassChecker::classExists($this->value)) {
            $this_is_subclass = false;

            foreach ($parent->types as $parent_type) {
                if (ClassChecker::classExtendsOrImplements($this->value, $parent_type->value)) {
                    $this_is_subclass = true;
                    break;
                }
            }

            if ($this_is_subclass) {
                return true;
            }
        }

        return false;
    }

    public function isArray()
    {
        return $this->value === 'array';
    }

    public function isObjectLike()
    {
        return $this->value === 'object-like';
    }

    public function isObject()
    {
        return $this->value === 'object';
    }

    public function isNumericType()
    {
        return $this->value === 'int' || $this->value === 'float';
    }

    public function isScalarType()
    {
        return $this->value === 'int' ||
                $this->value === 'string' ||
                $this->value === 'float' ||
                $this->value === 'bool' ||
                $this->value === 'false' ||
                $this->value === 'numeric';
    }

    public function isObjectType()
    {
        return $this->isObject()
                || (
                    !$this->isScalarType()
                    && !$this->isCallable()
                    && !$this->isArray()
                    && !$this->isMixed()
                    && !$this->isNull()
                    && !$this->isResource()
                );
    }

    public function isString()
    {
        return $this->value === 'string';
    }

    public function isNumeric()
    {
        return $this->value === 'numeric';
    }

    public function isScalar()
    {
        return $this->value === 'scalar';
    }

    public function isResource()
    {
        return $this->value === 'resource';
    }

    public function isCallable()
    {
        return $this->value === 'callable';
    }
}
