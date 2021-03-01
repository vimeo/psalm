<?php
namespace Psalm\Type\Atomic;

use function preg_replace;
use function mb_strlen;
use function mb_substr;

/**
 * Denotes a string whose value is known.
 */
class TLiteralString extends TString
{
    /** @var string */
    public $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function getKey(bool $include_extra = true) : string
    {
        return 'string(' . $this->value . ')';
    }

    public function __toString(): string
    {
        return 'string';
    }

    public function getId(bool $nested = false): string
    {
        $no_newline_value = preg_replace("/\n/m", '\n', $this->value);
        if (mb_strlen($this->value) > 80) {
            return '"' . mb_substr($no_newline_value, 0, 80) . '...' . '"';
        }

        return '"' . $no_newline_value . '"';
    }

    public function getAssertionString(bool $exact = false): string
    {
        return 'string(' . $this->value . ')';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $php_major_version,
        int $php_minor_version
    ): ?string {
        return $php_major_version >= 7 ? 'string' : null;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return 'string';
    }
}
