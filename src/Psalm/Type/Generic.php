<?php
namespace Psalm\Type;

class Generic extends Atomic
{
    /**
     * @var array<Union>
     */
    public $type_params;

    /**
     * Constructs a new instance of a generic type
     *
     * @param string            $value
     * @param array<int,Union>  $type_params
     */
    public function __construct($value, array $type_params)
    {
        $this->value = $value;
        $this->type_params = $type_params;
    }

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
     * @param  string        $this_class
     * @return string
     */
    public function toNamespacedString(array $aliased_classes, $this_class)
    {
        return $this->value .
                '<' .
                implode(
                    ', ',
                    array_map(
                        function (Union $type_param) use ($aliased_classes, $this_class) {
                            return $type_param->toNamespacedString($aliased_classes, $this_class);
                        },
                        $this->type_params
                    )
                ) .
                '>';
    }
}
