<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

trait GenericTrait
{
    /**
     * @var array<int, Union>
     */
    public $type_params;

    public function __toString()
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param . ', ';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
    }

    public function getId()
    {
        $s = '';
        foreach ($this->type_params as $type_param) {
            $s .= $type_param->getId() . ', ';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode('&', $this->extra_types);
        }

        return $this->value . '<' . substr($s, 0, -2) . '>' . $extra_types;
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
        $base_value = $this instanceof TNamedObject
            ? parent::toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format)
            : $this->value;

        if ($base_value === 'non-empty-string') {
            $base_value = 'array';
        }

        if ($use_phpdoc_format) {
            if ($this instanceof TNamedObject) {
                return $base_value;
            }

            $value_type = $this->type_params[1];

            if ($value_type->isMixed()) {
                return $base_value;
            }

            $value_type_string = $value_type->toNamespacedString($namespace, $aliased_classes, $this_class, true);

            if (!$value_type->isSingle()) {
                return '(' . $value_type_string . ')[]';
            }

            return $value_type_string . '[]';
        }

        $extra_types = '';

        if ($this instanceof TNamedObject && $this->extra_types) {
            $extra_types = '&' . implode(
                '&',
                array_map(
                    /**
                     * @return string
                     */
                    function (Atomic $extra_type) use ($namespace, $aliased_classes, $this_class) {
                        return $extra_type->toNamespacedString($namespace, $aliased_classes, $this_class, false);
                    },
                    $this->extra_types
                )
            );
        }

        return $base_value .
                '<' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @return string
                         */
                        function (Union $type_param) use ($namespace, $aliased_classes, $this_class) {
                            return $type_param->toNamespacedString($namespace, $aliased_classes, $this_class, false);
                        },
                        $this->type_params
                    )
                ) .
                '>' . $extra_types;
    }

    public function __clone()
    {
        foreach ($this->type_params as &$type_param) {
            $type_param = clone $type_param;
        }
    }

    /**
     * @return void
     */
    public function setFromDocblock()
    {
        $this->from_docblock = true;

        foreach ($this->type_params as $type_param) {
            $type_param->setFromDocblock();
        }
    }

    /**
     * @param  array<string, Union>     $template_types
     * @param  array<string, Union>     $generic_params
     * @param  Atomic|null              $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array $template_types,
        array &$generic_params,
        Codebase $codebase = null,
        Atomic $input_type = null
    ) {
        foreach ($this->type_params as $offset => $type_param) {
            $input_type_param = null;

            if (($input_type instanceof Atomic\TGenericObject || $input_type instanceof Atomic\TArray) &&
                    isset($input_type->type_params[$offset])
            ) {
                $input_type_param = $input_type->type_params[$offset];
            } elseif ($input_type instanceof Atomic\ObjectLike) {
                if ($offset === 0) {
                    $input_type_param = $input_type->getGenericKeyType();
                } elseif ($offset === 1) {
                    $input_type_param = $input_type->getGenericValueType();
                } else {
                    throw new \UnexpectedValueException('Not expecting offset of ' . $offset);
                }
            }

            $type_param->replaceTemplateTypesWithStandins(
                $template_types,
                $generic_params,
                $codebase,
                $input_type_param
            );
        }
    }

    /**
     * @param  array<string, Union>     $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types)
    {
        foreach ($this->type_params as $type_param) {
            $type_param->replaceTemplateTypesWithArgTypes($template_types);
        }
    }
}
