<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

use function is_nan;

/**
 * Denotes a floating point value where the exact numeric value is known.
 *
 * @psalm-immutable
 * @api
 */
final class TLiteralFloat extends TFloat
{
    public function __construct(public float $value, bool $from_docblock = false)
    {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        if (is_nan($this->value)) {
            return 'float(NAN)';
        }
        return 'float(' . $this->value . ')';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        if (!$exact) {
            return 'float';
        }
        if (is_nan($this->value)) {
            return 'float(NAN)';
        }

        return 'float(' . $this->value . ')';
    }

    /**
     * @param array<int, int> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toNamespacedString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
        bool $use_phpdoc_format,
    ): string {
        return 'float';
    }
}
