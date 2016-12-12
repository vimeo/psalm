<?php
namespace Psalm\Type;

use Psalm\Type;
use Psalm\Checker\ClassChecker;
use Psalm\Checker\ClassLikeChecker;

class Atomic extends Type
{
    /**
     * @var string
     */
    public $value;

    /**
     * Constructs an Atomic instance
     *
     * @param string    $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string        $this_class
     * @param  bool          $use_phpdoc_format
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if ($this->value === $this_class) {
            $class_parts = explode('\\', $this_class);
            return array_pop($class_parts);
        }

        if (isset($aliased_classes[strtolower($this->value)])) {
            return $aliased_classes[strtolower($this->value)];
        }

        if ($this->isObjectType()) {
            return '\\' . $this->value;
        }

        return $this->value;
    }

    /**
     * @param   Union   $parent
     * @return  bool
     */
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

    /**
     * @return bool
     */
    public function isArray()
    {
        return $this->value === 'array';
    }

    /**
     * @return bool
     */
    public function isGenericArray()
    {
        return $this->value === 'array' && $this instanceof Generic;
    }

    /**
     * @return bool
     */
    public function isObjectLike()
    {
        return $this instanceof ObjectLike;
    }

    /**
     * @return bool
     */
    public function isObject()
    {
        return $this->value === 'object';
    }

    /**
     * @return bool
     */
    public function isNumericType()
    {
        return $this->value === 'int' || $this->value === 'float' || $this->value === 'string';
    }

    /**
     * @return bool
     */
    public function isScalarType()
    {
        return $this->value === 'int' ||
                $this->value === 'string' ||
                $this->value === 'float' ||
                $this->value === 'bool' ||
                $this->value === 'false' ||
                $this->value === 'numeric';
    }

    /**
     * @return bool
     */
    public function isObjectType()
    {
        return $this->isObject()
                || (
                    !$this->isScalarType()
                    && !$this->isCallable()
                    && !$this->isArray()
                    && !$this->isMixed()
                    && !$this->isNull()
                    && !$this->isVoid()
                    && !$this->isEmpty()
                    && !$this->isResource()
                );
    }

    /**
     * @return bool
     */
    public function isString()
    {
        return $this->value === 'string';
    }

    /**
     * @return bool
     */
    public function isNumeric()
    {
        return $this->value === 'numeric';
    }

    /**
     * @return bool
     */
    public function isScalar()
    {
        return $this->value === 'scalar';
    }

    /**
     * @return bool
     */
    public function isResource()
    {
        return $this->value === 'resource';
    }

    /**
     * @return bool
     */
    public function isCallable()
    {
        return $this->value === 'callable';
    }

    /**
     * @return bool
     */
    public function isGenerator()
    {
        return $this->value === 'Generator';
    }

    /**
     * @return bool
     */
    public function isMixed()
    {
        return $this->value === 'mixed';
    }

    /**
     * @return bool
     */
    public function isNull()
    {
        return $this->value === 'null';
    }

    /**
     * @return bool
     */
    public function isVoid()
    {
        return $this->value === 'void';
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->value === 'empty';
    }
}
