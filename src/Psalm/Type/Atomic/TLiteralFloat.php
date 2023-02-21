<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes a floating point value where the exact numeric value is known.
 *
 * @psalm-immutable
 */
final class TLiteralFloat extends TFloat
{
    /** @var float */
    public $value;

    public function __construct(float $value, bool $from_docblock = false)
    {
        $this->value = $value;
        $this->from_docblock = $from_docblock;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'float(' . $this->value . ')';
    }

    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'float';
        }

        return 'float(' . $this->value . ')';
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
        return 'float';
    }
}
