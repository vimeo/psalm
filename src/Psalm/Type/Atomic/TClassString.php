<?php
namespace Psalm\Type\Atomic;

use function stripos;
use function preg_replace;
use function preg_quote;
use function strtolower;

class TClassString extends TString implements HasClassString
{
    /**
     * @var string
     */
    public $as;

    /**
     * @var ?TNamedObject
     */
    public $as_type;

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
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ) {
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

    public function hasSingleNamedObject() : bool
    {
        return (bool) $this->as_type;
    }

    public function getSingleNamedObject() : TNamedObject
    {
        if (!$this->as_type) {
            throw new \UnexpectedValueException('Bad object');
        }

        return $this->as_type;
    }
}
