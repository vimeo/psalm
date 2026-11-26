<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use InvalidArgumentException;
use Override;
use Psalm\Config;

use function addcslashes;
use function mb_strlen;
use function mb_substr;
use function preg_match;
use function preg_replace_callback;
use function str_replace;
use function strlen;

/**
 * Denotes a string whose value is known.
 *
 * @psalm-immutable
 * @api
 */
class TLiteralString extends TString
{
    public string $value;

    /**
     * Creates a literal string with a known value.
     *
     * Internal.
     * String interpreters should use {@see TLiteralString::make} instead.
     * All other clients should use {@see Type::getAtomicStringFromLiteral}.
     *
     * @psalm-internal Psalm\Type::getAtomicStringFromLiteral
     * @psalm-internal Psalm\Type\Atomic\TLiteralClassString::__construct
     * @psalm-internal Psalm\Type\Atomic\TLiteralString::make
     */
    public function __construct(string $value, bool $from_docblock = false)
    {
        $config = Config::getInstance();
        if (strlen($value) >= $config->max_string_length) {
            throw new InvalidArgumentException(
                'Literal string length should be below the configured limit ('
                . $config->max_string_length
                . ')',
            );
        }
        $this->value = $value;
        parent::__construct($from_docblock);
    }

    /**
     * Should only be used by string interpreters to avoid recursive calls.
     *
     * For all other purposes use {@see Type::getAtomicStringFromLiteral}
     *
     * @psalm-api
     * @psalm-pure
     */
    public static function make(string $value, bool $from_docblock = false): self
    {
        return new self($value, $from_docblock);
    }

    /**
     * @return static
     */
    public function setValue(string $value): self
    {
        if ($value === $this->value) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->value = $value;
        return $cloned;
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'string(' . $this->value . ')';
    }

    #[Override]
    public function getId(bool $exact = true): string
    {
        if (!$exact) {
            return 'string';
        }

        if (mb_strlen($this->value) > 80) {
            return self::quote(mb_substr($this->value, 0, 80) . '...');
        }

        return self::quote($this->value);
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'string(' . $this->value . ')';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        return $use_phpdoc_format ? 'string' : self::quote($this->value);
    }

    /**
     * The value as a string literal of a type: in single quotes, or in double quotes for control characters, which
     * only have escapes there, and for `*` followed by `/`, which would end the docblock the type is written in
     *
     * @psalm-pure
     */
    private static function quote(string $value): string
    {
        if (!preg_match('@[\0-\37\177]|\*/@', $value)) {
            return "'" . addcslashes($value, "'\\") . "'";
        }

        // PHP has no \a and \b escapes
        $escaped = (string) preg_replace_callback(
            '/\\\\(.)/s',
            static fn(array $match): string => match ($match[1] ?? '') {
                'a' => '\\007',
                'b' => '\\010',
                default => $match[0] ?? '',
            },
            addcslashes($value, "\0..\37\177\\\""),
        );

        return '"' . str_replace('*/', '*\\057', $escaped) . '"';
    }
}
