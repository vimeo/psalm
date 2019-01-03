<?php
namespace Psalm\Type\Atomic;

class TClassString extends TString
{
    /**
     * @var string
     */
    public $extends;

    /**
     * @param string $param_name
     */
    public function __construct(string $extends = 'object')
    {
        $this->extends = $extends;
    }

     /**
     * @return string
     */
    public function getKey()
    {
        return 'class-string' . ($this->extends === 'object' ? '' : '<' . $this->extends . '>');
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getKey();
    }

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
        return 'string';
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
        if ($this->extends === 'object') {
            return 'class-string';
        }

        if ($namespace && stripos($this->extends, $namespace . '\\') === 0) {
            return 'class-string<' . preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->extends
            ) . '>';
        }

        if (!$namespace && stripos($this->extends, '\\') === false) {
            return 'class-string<' . $this->extends . '>';
        }

        if (isset($aliased_classes[strtolower($this->extends)])) {
            return 'class-string<' . $aliased_classes[strtolower($this->extends)] . '>';
        }

        return 'class-string<\\' . $this->extends . '>';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
