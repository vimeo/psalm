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
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\UnionTemplateHandler;
use function array_merge;
use function array_values;

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
        ?Codebase $codebase = null,
        ?StatementsAnalyzer $statements_analyzer = null,
        Atomic $input_type = null,
        ?int $input_arg_offset = null,
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
                $statements_analyzer,
                $input_type_param,
                $input_arg_offset,
                $calling_class,
                $calling_function,
                $replace,
                $add_upper_bound,
                $depth
            );
        }

        return $object_like;
    }

    public function replaceTemplateTypesWithArgTypes(
        TemplateResult $template_result,
        ?Codebase $codebase
    ) : void {
        foreach ($this->properties as $property) {
            $property->replaceTemplateTypesWithArgTypes(
                $template_result,
                $codebase
            );
        }
    }

    public function getChildNodes() : array
    {
        return array_merge($this->properties, $this->extra_types !== null ? array_values($this->extra_types) : []);
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return $this->getKey();
    }
}
