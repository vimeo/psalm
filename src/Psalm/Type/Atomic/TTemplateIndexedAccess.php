<?php
namespace Psalm\Type\Atomic;

class TTemplateIndexedAccess extends \Psalm\Type\Atomic
{
    /**
     * @var string
     */
    public $array_param_name;

    /**
     * @var string
     */
    public $offset_param_name;

    /**
     * @var string
     */
    public $defining_class;

    public function __construct(
        string $array_param_name,
        string $offset_param_name,
        string $defining_class
    ) {
        $this->array_param_name = $array_param_name;
        $this->offset_param_name = $offset_param_name;
        $this->defining_class = $defining_class;
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return $this->array_param_name . '[' . $this->offset_param_name . ']';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getKey();
    }

    /**
     * @return string
     */
    public function getId(bool $nested = false)
    {
        return $this->getKey();
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
        return null;
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
        return $this->getKey();
    }
}
