<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Union;

trait GenericTrait
{
    /**
     * @var array<Union>
     */
    public $type_params;

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value .
                '<' .
                implode(
                    ', ',
                    array_map(
                        /**
                         * @return string
                         */
                        function (Union $type_param) {
                            return (string)$type_param;
                        },
                        $this->type_params
                    )
                ) .
                '>';
    }

    /**
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  bool          $use_phpdoc_format
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if ($use_phpdoc_format) {
            if ($this->value !== 'array') {
                return $this->value;
            }

            $value_type = $this->type_params[1];

            if ($value_type->isMixed()) {
                return $this->value;
            }

            $value_type_string = $value_type->toNamespacedString($aliased_classes, $this_class, true);

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
                        function (Union $type_param) use ($aliased_classes, $this_class) {
                            return $type_param->toNamespacedString($aliased_classes, $this_class, false);
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
}
