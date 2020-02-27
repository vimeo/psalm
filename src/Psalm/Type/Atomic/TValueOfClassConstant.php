<?php
namespace Psalm\Type\Atomic;

class TValueOfClassConstant extends \Psalm\Type\Atomic
{
    /** @var string */
    public $fq_classlike_name;

    /** @var string */
    public $const_name;

    /**
     * @param string $fq_classlike_name
     * @param string $const_name
     */
    public function __construct($fq_classlike_name, $const_name)
    {
        $this->fq_classlike_name = $fq_classlike_name;
        $this->const_name = $const_name;
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return 'value-of<' . $this->fq_classlike_name . '::' . $this->const_name . '>';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'value-of<' . $this->fq_classlike_name . '::' . $this->const_name . '>';
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
        if ($this->fq_classlike_name === 'static') {
            return 'value-of<static::' . $this->const_name . '>';
        }

        return 'value-of<'
            . \Psalm\Type::getStringFromFQCLN($this->fq_classlike_name, $namespace, $aliased_classes, $this_class)
            . '>::' . $this->const_name . '>';
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'mixed';
    }
}
