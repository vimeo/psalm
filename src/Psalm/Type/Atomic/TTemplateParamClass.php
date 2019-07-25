<?php
namespace Psalm\Type\Atomic;

class TTemplateParamClass extends TClassString
{
    /**
     * @var string
     */
    public $param_name;

    /**
     * @var string
     */
    public $as;

    /**
     * @var ?TNamedObject
     */
    public $as_type;

    /**
     * @var ?string
     */
    public $defining_class;

    /**
     * @param string $param_name
     */
    public function __construct(
        string $param_name,
        string $as = 'object',
        TNamedObject $as_type = null,
        string $defining_class = null
    ) {
        $this->param_name = $param_name;
        $this->as = $as;
        $this->as_type = $as_type;
        $this->defining_class = $defining_class;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return 'class-string<' . $this->param_name . '>';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'class-string<' . $this->param_name . '>';
    }

    /**
     * @return string
     */
    public function getId()
    {
        return 'class-string<' . $this->param_name . ' as ' . $this->as . '>';
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
     * @param  string|null   $this_class
     * @param  int           $php_major_version
     * @param  int           $php_minor_version
     *
     * @return string|null
     */
    public function toPhpString(
        $namespace,
        array $aliased_classes,
        $this_class,
        $php_major_version,
        $php_minor_version
    ) {
        return 'string';
    }

    public function canBeFullyExpressedInPhp()
    {
        return false;
    }

    /**
     * @param  string|null   $namespace
     * @param  array<string> $aliased_classes
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
        return $this->param_name . '::class';
    }
}
