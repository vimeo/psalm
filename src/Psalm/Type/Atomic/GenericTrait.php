<?php
namespace Psalm\Type\Atomic;

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

        return $this->value . '<' . substr($s, 0, -2) . '>';
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
            if ($this->value !== 'array') {
                return $this->value;
            }

            $value_type = $this->type_params[1];

            if ($value_type->isMixed()) {
                return $this->value;
            }

            $value_type_string = $value_type->toNamespacedString($namespace, $aliased_classes, $this_class, true);

            if (count($value_type->types) > 1) {
                return '(' . $value_type_string . ')[]';
            }

            return $value_type_string . '[]';
        }

        return $this->value .
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
                '>';
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
        foreach ($this->type_params as $type_param) {
            $type_param->setFromDocblock();
        }
    }

    /**
     * @param  array<string, string>    $template_types
     * @param  array<string, Union>     $generic_params
     * @param  Atomic|null              $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array $template_types,
        array &$generic_params,
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
                $input_type_param
            );
        }
    }

    /**
     * @param  array<string, string|Union>     $template_types
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
