<?php
namespace Psalm\Type\Atomic;

use Psalm\Type;
use Psalm\Type\Union;

class ObjectLike extends \Psalm\Type\Atomic
{
    /**
     * @var array<string|int, Union>
     */
    public $properties;

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<string|int, Union> $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }

    public function __toString()
    {
        return 'array{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @param  string|int $name
                         * @param  string $type
                         *
                         * @return string
                         */
                        function ($name, $type) {
                            return $name . ':' . $type;
                        },
                        array_keys($this->properties),
                        $this->properties
                    )
                ) .
                '}';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString($namespace, array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if ($use_phpdoc_format) {
            return $this->getGenericArrayType()->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                $use_phpdoc_format
            );
        }

        return 'array{' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @param  string|int $name
                         * @param  Union  $type
                         *
                         * @return string
                         */
                        function (
                            $name,
                            Union $type
                        ) use (
                            $namespace,
                            $aliased_classes,
                            $this_class,
                            $use_phpdoc_format
                        ) {
                            return $name . ':' . $type->toNamespacedString(
                                $namespace,
                                $aliased_classes,
                                $this_class,
                                $use_phpdoc_format
                            );
                        },
                        array_keys($this->properties),
                        $this->properties
                    )
                ) .
                '}';
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
    public function toPhpString($namespace, array $aliased_classes, $this_class, $php_major_version, $php_minor_version)
    {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    /**
     * @return Union
     */
    public function getGenericKeyType()
    {
        $key_types = [];

        foreach ($this->properties as $key => $_) {
            if (is_int($key) || preg_match('/^\d+$/', $key)) {
                $key_types[] = new Type\Atomic\TInt();
            } else {
                $key_types[] = new Type\Atomic\TString();
            }
        }

        return Type::combineTypes($key_types);
    }

    /**
     * @return Union
     */
    public function getGenericValueType()
    {
        $value_type = null;

        foreach ($this->properties as $property) {
            if ($value_type === null) {
                $value_type = clone $property;
            } else {
                $value_type = Type::combineUnionTypes($property, $value_type);
            }
        }

        if (!$value_type) {
            throw new \UnexpectedValueException('$value_type should not be null here');
        }

        return $value_type;
    }

    /**
     * @return Type\Atomic\TArray
     */
    public function getGenericArrayType()
    {
        $key_types = [];
        $value_type = null;

        foreach ($this->properties as $key => $property) {
            if (is_int($key) || preg_match('/^\d+$/', $key)) {
                $key_types[] = new Type\Atomic\TInt();
            } else {
                $key_types[] = new Type\Atomic\TString();
            }

            if ($value_type === null) {
                $value_type = clone $property;
            } else {
                $value_type = Type::combineUnionTypes($property, $value_type);
            }
        }

        if (!$value_type) {
            throw new \UnexpectedValueException('$value_type should not be null here');
        }

        return new TArray([Type::combineTypes($key_types), $value_type]);
    }

    public function __clone()
    {
        foreach ($this->properties as &$property) {
            $property = clone $property;
        }
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'array';
    }

    public function setFromDocblock()
    {
        foreach ($this->properties as $property_type) {
            $property_type->setFromDocblock();
        }
    }
}
