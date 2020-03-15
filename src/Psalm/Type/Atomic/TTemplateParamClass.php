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
     * @var string
     */
    public $defining_class;

    public function __construct(
        string $param_name,
        string $as,
        ?TNamedObject $as_type,
        string $defining_class
    ) {
        $this->param_name = $param_name;
        $this->as = $as;
        $this->as_type = $as_type;
        $this->defining_class = $defining_class;
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
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
    public function getId(bool $nested = false)
    {
        return 'class-string<' . $this->param_name . ':' . $this->defining_class . ' as ' . $this->as . '>';
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'class-string<' . $this->param_name . '>';
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

    public function getChildNodes() : array
    {
        return $this->as_type ? [$this->as_type] : [];
    }
}
