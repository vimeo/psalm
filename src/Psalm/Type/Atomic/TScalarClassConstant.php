<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

class TScalarClassConstant extends Scalar
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
    public function getKey()
    {
        return 'scalar-class-constant(' . $this->fq_classlike_name . '::' . $this->const_name . ')';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'scalar-class-constant';
    }

    /**
     * @return string
     */
    public function getId()
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
    public function toNamespacedString($namespace, array $aliased_classes, $this_class, $use_phpdoc_format)
    {
        if ($this->fq_classlike_name === 'static') {
            return 'static::' . $this->const_name;
        }

        if ($this->fq_classlike_name === $this_class) {
            return 'self::' . $this->const_name;
        }

        if ($namespace && stripos($this->fq_classlike_name, $namespace . '\\') === 0) {
            return preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->fq_classlike_name
            ) . '::' . $this->const_name;
        }

        if (!$namespace && stripos($this->fq_classlike_name, '\\') === false) {
            return $this->fq_classlike_name . '::' . $this->const_name;
        }

        if (isset($aliased_classes[strtolower($this->fq_classlike_name)])) {
            return $aliased_classes[strtolower($this->fq_classlike_name)] . '::' . $this->const_name;
        }

        return '\\' . $this->fq_classlike_name . '::' . $this->const_name;
    }
}
