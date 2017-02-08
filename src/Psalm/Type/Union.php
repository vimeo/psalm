<?php
namespace Psalm\Type;

use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Type;

class Union
{
    /**
     * @var array<string, Atomic>
     */
    public $types = [];

    /**
     * Whether the type originated in a docblock
     *
     * @var boolean
     */
    public $from_docblock = false;

    /**
     * Whether the property that this type has been derived from has been initialized in a constructor
     *
     * @var boolean
     */
    public $initialized = true;

    /**
     * Constructs an Union instance
     * @param array<int, Atomic>     $types
     */
    public function __construct(array $types)
    {
        foreach ($types as $type) {
            $this->types[$type->getKey()] = $type;
        }
    }

    public function __clone()
    {
        foreach ($this->types as &$type) {
            $type = clone $type;
        }
    }

    /**
     * @return string
     */
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
                    return (string)$type;
                },
                $this->types
            )
        );
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
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

    /**
     * @return void
     */
    public function setFromDocblock()
    {
        $this->from_docblock = true;

        foreach ($this->types as $type) {
            $type->setFromDocblock();
        }
    }

    /**
     * @param  string $type_string
     * @return void
     */
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
            if ($type instanceof Atomic\Generic) {
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
    public function hasObjectLike()
    {
        return isset($this->types['array']) && $this->types['array'] instanceof Atomic\ObjectLike;
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
    public function hasNumericType()
    {
        return isset($this->types['int']) ||
            isset($this->types['float']) ||
            isset($this->types['string']);
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
            isset($this->types['numeric']) ||
            isset($this->types['numeric-string']);
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
            if ($type instanceof Atomic\TNamedObject) {
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
            $this->removeType($old_type_part->getKey());
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
    public function isSingle()
    {
        if (count($this->types) > 1) {
            return false;
        }

        $type = array_values($this->types)[0];

        if (!$type instanceof Atomic\TArray && !$type instanceof Atomic\TGenericObject) {
            return true;
        }

        return $type->type_params[count($type->type_params) - 1]->isSingle();
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @return void
     */
    public function check(StatementsSource $source, CodeLocation $code_location, array $suppressed_issues)
    {
        foreach ($this->types as $atomic_type) {
            $atomic_type->check($source, $code_location, $suppressed_issues);
        }
    }
}
