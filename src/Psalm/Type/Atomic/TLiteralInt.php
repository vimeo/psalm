<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an integer value where the exact numeric value is known.
 *
 * @psalm-immutable
 */
final class TLiteralInt extends TInt
{
    /** @var int */
    public $value;

    public function __construct(int $value, bool $from_docblock = false)
    {
        $this->value = $value;
        parent::__construct($from_docblock);
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'int(' . $this->value . ')';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'int';
        }

        return (string) $this->value;
    }

    public function getAssertionString(): string
    {
        return 'int(' . $this->value . ')';
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
        return $use_phpdoc_format ? 'int' : (string) $this->value;
    }
}
