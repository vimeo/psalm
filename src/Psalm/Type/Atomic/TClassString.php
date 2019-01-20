<?php
namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;
use Psalm\Type\Union;

class TClassString extends TString
{
    /**
     * @var string
     */
    public $as;

    /**
     * @var ?TNamedObject
     */
    public $as_type;

    /**
     * @param string $param_name
     */
    public function __construct(string $as = 'object', TNamedObject $as_type = null)
    {
        $this->as = $as;
        $this->as_type = $as_type;
    }

     /**
     * @return string
     */
    public function getKey()
    {
        return 'class-string' . ($this->as === 'object' ? '' : '<' . $this->as_type . '>');
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
        if ($this->as === 'object') {
            return 'class-string';
        }

        if ($namespace && stripos($this->as, $namespace . '\\') === 0) {
            return 'class-string<' . preg_replace(
                '/^' . preg_quote($namespace . '\\') . '/i',
                '',
                $this->as
            ) . '>';
        }

        if (!$namespace && stripos($this->as, '\\') === false) {
            return 'class-string<' . $this->as . '>';
        }

        if (isset($aliased_classes[strtolower($this->as)])) {
            return 'class-string<' . $aliased_classes[strtolower($this->as)] . '>';
        }

        return 'class-string<\\' . $this->as . '>';
    }

    /**
     * @return bool
     */
    public function canBeFullyExpressedInPhp()
    {
        return false;
    }
}
