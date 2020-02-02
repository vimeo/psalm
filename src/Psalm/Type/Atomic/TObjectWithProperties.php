<?php
namespace Psalm\Type\Atomic;

use function array_keys;
use function array_map;
use function count;
use function implode;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\StatementsSource;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Union;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;

class TObjectWithProperties extends TObject
{
    use HasIntersectionTrait;

    /**
     * @var array<string|int, Union>
     */
    public $properties;

    /**
     * @var array<string, string>
     */
    public $methods;

    /**
     * Constructs a new instance of a generic type
     *
     * @param array<string|int, Union> $properties
     * @param array<string, string> $methods
     */
    public function __construct(array $properties, array $methods = [])
    {
        $this->properties = $properties;
        $this->methods = $methods;
    }

    public function __toString()
    {
        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        $properties_string = implode(
            ', ',
            array_map(
                /**
                 * @param  string|int $name
                 * @param  Union $type
                 *
                 * @return string
                 */
                function ($name, Union $type) {
                    return $name . ($type->possibly_undefined ? '?' : '') . ':' . $type;
                },
                array_keys($this->properties),
                $this->properties
            )
        );

        $methods_string = implode(
            ', ',
            array_map(
                function (string $name) {
                    return $name . '()';
                },
                array_keys($this->methods)
            )
        );

        return 'object{'
            . $properties_string . ($methods_string && $properties_string ? ', ' : '')
            . $methods_string
            . '}' . $extra_types;
    }

    public function getId(bool $nested = false)
    {
        $extra_types = '';

        if ($this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        $properties_string = implode(
            ', ',
            array_map(
                /**
                 * @param  string|int $name
                 * @param  Union $type
                 *
                 * @return string
                 */
                function ($name, Union $type) {
                    return $name . ($type->possibly_undefined ? '?' : '') . ':' . $type->getId();
                },
                array_keys($this->properties),
                $this->properties
            )
        );

        $methods_string = implode(
            ', ',
            array_map(
                function (string $name) {
                    return $name . '()';
                },
                array_keys($this->methods)
            )
        );

        return 'object{'
            . $properties_string . ($methods_string && $properties_string ? ', ' : '')
            . $methods_string
            . '}' . $extra_types;
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string, string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     *
     * @return string
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) {
        if ($use_phpdoc_format) {
            return 'object';
        }

        return 'object{' .
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
                            return $name . ($type->possibly_undefined ? '?' : '') . ':' . $type->toNamespacedString(
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

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    public function __clone()
    {
        foreach ($this->properties as &$property) {
            $property = clone $property;
        }
    }

    public function setFromDocblock()
    {
        $this->from_docblock = true;

        foreach ($this->properties as $property_type) {
            $property_type->setFromDocblock();
        }
    }

    /**
     * @return bool
     */
    public function equals(Atomic $other_type)
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if (count($this->properties) !== count($other_type->properties)) {
            return false;
        }

        if ($this->methods !== $other_type->methods) {
            return false;
        }

        foreach ($this->properties as $property_name => $property_type) {
            if (!isset($other_type->properties[$property_name])) {
                return false;
            }

            if (!$property_type->equals($other_type->properties[$property_name])) {
                return false;
            }
        }

        return true;
    }

    public function replaceTemplateTypesWithStandins(
        TemplateResult $template_result,
        Codebase $codebase = null,
        Atomic $input_type = null,
        ?string $calling_class = null,
        ?string $calling_function = null,
        bool $replace = true,
        bool $add_upper_bound = false,
        int $depth = 0
    ) : Atomic {
        $object_like = clone $this;

        foreach ($this->properties as $offset => $property) {
            $input_type_param = null;

            if ($input_type instanceof Atomic\ObjectLike
                && isset($input_type->properties[$offset])
            ) {
                $input_type_param = $input_type->properties[$offset];
            }

            $object_like->properties[$offset] = UnionTemplateHandler::replaceTemplateTypesWithStandins(
                $property,
                $template_result,
                $codebase,
                $input_type_param,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth
            );
        }

        return $object_like;
    }

    /**
     * @param  array<string, array<string, array{Type\Union, 1?:int}>>     $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types, ?\Psalm\Codebase $codebase)
    {
        foreach ($this->properties as $property) {
            $property->replaceTemplateTypesWithArgTypes(
                $template_types,
                $codebase
            );
        }
    }

    /**
     * @return list<Type\Atomic\TTemplateParam>
     */
    public function getTemplateTypes() : array
    {
        $template_types = [];

        foreach ($this->properties as $property) {
            $template_types = \array_merge($template_types, $property->getTemplateTypes());
        }

        return $template_types;
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return $this->getKey();
    }

    /**
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  array<string, bool> $phantom_classes
     * @param  bool             $inferred
     *
     * @return void
     */
    public function check(
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        array $phantom_classes = [],
        bool $inferred = true,
        bool $prevent_template_covariance = false
    ) {
        if ($this->checked) {
            return;
        }

        foreach ($this->properties as $property_type) {
            $property_type->check(
                $source,
                $code_location,
                $suppressed_issues,
                $phantom_classes,
                $inferred,
                $prevent_template_covariance
            );
        }

        $this->checked = true;
    }
}
