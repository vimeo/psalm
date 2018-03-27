<?php
namespace Psalm\Type\Atomic;

use Psalm\FunctionLikeParameter;
use Psalm\Type\Union;

trait CallableTrait
{
    /**
     * @var array<int, FunctionLikeParameter>|null
     */
    public $params = [];

    /**
     * @var Union|null
     */
    public $return_type;

    /**
     * @var string
     */
    public $value;

    /**
     * Constructs a new instance of a generic type
     *
     * @param string                            $value
     * @param array<int, FunctionLikeParameter> $params
     * @param Union                             $return_type
     */
    public function __construct($value = 'callable', array $params = null, Union $return_type = null)
    {
        $this->value = $value;
        $this->params = $params;
        $this->return_type = $return_type;
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
            if ($this instanceof TNamedObject) {
                return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true);
            }

            return $this->value;
        }

        $param_string = '';
        $return_type_string = '';

        if ($this->params !== null) {
            $param_string = '(' . implode(
                ', ',
                array_map(
                    /**
                     * @return string
                     */
                    function (FunctionLikeParameter $param) use ($namespace, $aliased_classes, $this_class) {
                        if (!$param->type) {
                            throw new \UnexpectedValueException('Param type must not be null');
                        }

                        $type_string = $param->type->toNamespacedString(
                            $namespace,
                            $aliased_classes,
                            $this_class,
                            false
                        );

                        return ($param->is_variadic ? '...' : '') . $type_string . ($param->is_optional ? '=' : '');
                    },
                    $this->params
                )
            ) . ')';
        }

        if ($this->return_type !== null) {
            $return_type_string = ' : ' . $this->return_type->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                false
            );
        }

        if ($this instanceof TNamedObject) {
            return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true)
                . $param_string . $return_type_string;
        }

        return 'callable' . $param_string . $return_type_string;
    }
}
