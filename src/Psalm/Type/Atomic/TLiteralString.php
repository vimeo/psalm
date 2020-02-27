<?php
namespace Psalm\Type\Atomic;

use function preg_replace;
use function strlen;
use function substr;

class TLiteralString extends TString
{
    /** @var string */
    public $value;

    /**
     * @param string $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getKey(bool $include_extra = true)
    {
        return $this->getId();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'string';
    }

    /**
     * @return string
     */
    public function getId(bool $nested = false)
    {
        $no_newline_value = preg_replace("/\n/m", '\n', $this->value);
        if (strlen($this->value) > 80) {
            return 'string(' . substr($no_newline_value, 0, 80) . '...' . ')';
        }

        return 'string(' . $no_newline_value . ')';
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
        return $php_major_version >= 7 ? 'string' : null;
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
        return 'string';
    }
}
