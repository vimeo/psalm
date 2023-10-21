<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Denotes a floating point value where the exact numeric value is known.
 *
 * @psalm-immutable
 */
final class TLiteralFloat extends TFloat
{
    public function __construct(public float $value, bool $from_docblock = false)
    {
        parent::__construct($from_docblock);
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
        bool $use_phpdoc_format,
    ): string {
        return 'float';
    }
}
