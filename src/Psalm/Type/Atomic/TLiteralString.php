<?php

namespace Psalm\Type\Atomic;

use InvalidArgumentException;
use Psalm\Config;

use function addcslashes;
use function mb_strlen;
use function mb_substr;
use function strlen;

/**
 * Denotes a string whose value is known.
 *
 * @psalm-immutable
 */
class TLiteralString extends TString
{
    /** @var string */
    public $value;

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
     */
    public static function make(string $value, bool $from_docblock = false): self
    {
        return new self($value, $from_docblock);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
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

    public function getKey(bool $include_extra = true): string
    {
        return 'string(' . $this->value . ')';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'string';
        }
        // quote control characters, backslashes and double quote
        $no_newline_value = addcslashes($this->value, "\0..\37\\\"");
        if (mb_strlen($this->value) > 80) {
            return "'" . mb_substr($no_newline_value, 0, 80) . '...' . "'";
        }

        return "'" . $no_newline_value . "'";
    }

    public function getAssertionString(): string
    {
        return 'string(' . $this->value . ')';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $use_phpdoc_format ? 'string' : "'" . $this->value . "'";
    }
}
