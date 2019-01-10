<?php
namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type\Atomic;
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

    public function __clone()
    {
        if ($this->params) {
            foreach ($this->params as &$param) {
                $param = clone $param;
            }
        }

        $this->return_type = $this->return_type ? clone $this->return_type : null;
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
                            $type_string = 'mixed';
                        } else {
                            $type_string = $param->type->toNamespacedString(
                                $namespace,
                                $aliased_classes,
                                $this_class,
                                false
                            );
                        }

                        return ($param->is_variadic ? '...' : '') . $type_string . ($param->is_optional ? '=' : '');
                    },
                    $this->params
                )
            ) . ')';
        }

        if ($this->return_type !== null) {
            $return_type_multiple = count($this->return_type->getTypes()) > 1;

            $return_type_string = ':' . ($return_type_multiple ? '(' : '') . $this->return_type->toNamespacedString(
                $namespace,
                $aliased_classes,
                $this_class,
                false
            ) . ($return_type_multiple ? ')' : '');
        }

        if ($this instanceof TNamedObject) {
            return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true)
                . $param_string . $return_type_string;
        }

        return 'callable' . $param_string . $return_type_string;
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
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        if ($this instanceof TNamedObject) {
            return parent::toNamespacedString($namespace, $aliased_classes, $this_class, true);
        }

        return $this->value;
    }

    /**
     * @return string
     */
    public function getId()
    {
        $param_string = '';
        $return_type_string = '';

        if ($this->params !== null) {
            $param_string = '(' . implode(', ', $this->params) . ')';
        }

        if ($this->return_type !== null) {
            $return_type_multiple = count($this->return_type->getTypes()) > 1;
            $return_type_string = ':' . ($return_type_multiple ? '(' : '')
                . $this->return_type->getId() . ($return_type_multiple ? ')' : '');
        }

        return $this->value . $param_string . $return_type_string;
    }

    public function __toString()
    {
        return $this->getId();
    }

    /**
     * @param  array<string, array{Union, ?string}>     $template_types
     * @param  array<string, array{Union, ?string}>     $generic_params
     * @param  Atomic|null              $input_type
     *
     * @return void
     */
    public function replaceTemplateTypesWithStandins(
        array &$template_types,
        array &$generic_params,
        Codebase $codebase = null,
        Atomic $input_type = null
    ) {
        if ($this->params) {
            foreach ($this->params as $offset => $param) {
                $input_param_type = null;

                if ($input_type instanceof Atomic\Fn
                    && isset($input_type->params[$offset])
                ) {
                    $input_param_type = $input_type->params[$offset]->type;
                }

                if (!$param->type) {
                    continue;
                }

                $param->type->replaceTemplateTypesWithStandins(
                    $template_types,
                    $generic_params,
                    $codebase,
                    $input_param_type,
                    true
                );
            }
        }

        if (($input_type instanceof Atomic\TCallable || $input_type instanceof Atomic\Fn)
            && $this->return_type
            && $input_type->return_type
        ) {
            $this->return_type->replaceTemplateTypesWithStandins(
                $template_types,
                $generic_params,
                $codebase,
                $input_type->return_type
            );
        }
    }

    /**
     * @param  array<string, array{Union, ?string}>  $template_types
     *
     * @return void
     */
    public function replaceTemplateTypesWithArgTypes(array $template_types)
    {
        if ($this->params) {
            foreach ($this->params as $param) {
                if (!$param->type) {
                    continue;
                }

                $param->type->replaceTemplateTypesWithArgTypes($template_types);
            }
        }

        if ($this->return_type) {
            $this->return_type->replaceTemplateTypesWithArgTypes($template_types);
        }
    }
}
