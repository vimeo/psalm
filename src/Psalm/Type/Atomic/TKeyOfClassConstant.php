<?php
namespace Psalm\Type\Atomic;

use function preg_quote;
use function preg_replace;
use function stripos;
use function strtolower;

class TKeyOfClassConstant extends Scalar
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
        return 'key-of<' . $this->fq_classlike_name . '::' . $this->const_name . '>';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'key-of<' . $this->fq_classlike_name . '::' . $this->const_name . '>';
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
        if ($this->fq_classlike_name === 'static') {
            return 'key-of<static::' . $this->const_name . '>';
        }

        if ($this->fq_classlike_name === $this_class) {
            return 'key-of<self::' . $this->const_name . '>';
        }

        if ($namespace && stripos($this->fq_classlike_name, $namespace . '\\') === 0) {
            return 'key-of<' . preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->fq_classlike_name
            ) . '::' . $this->const_name . '>';
        }

        if (!$namespace && stripos($this->fq_classlike_name, '\\') === false) {
            return 'key-of<' . $this->fq_classlike_name . '::' . $this->const_name . '>';
        }

        if (isset($aliased_classes[strtolower($this->fq_classlike_name)])) {
            return 'key-of<'
                . $aliased_classes[strtolower($this->fq_classlike_name)]
                . '::'
                . $this->const_name
                . '>';
        }

        return 'key-of<\\' . $this->fq_classlike_name . '::' . $this->const_name . '>';
    }

    /**
     * @return string
     */
    public function getAssertionString()
    {
        return 'mixed';
    }
}
