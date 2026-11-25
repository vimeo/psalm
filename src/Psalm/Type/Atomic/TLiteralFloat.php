<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

use function is_nan;
use function sprintf;

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
        return 'float(' . $this->getValueString() . ')';
    }

    #[Override]
    public function getId(bool $exact = true): string
    {
        if (!$exact) {
            return 'float';
        }

        return 'float(' . $this->getValueString() . ')';
    }

    /**
     * The value in full, whatever the `precision` and `serialize_precision` settings: a float cut to fewer digits
     * could read the same as another
     */
    private function getValueString(): string
    {
        if (is_nan($this->value)) {
            return 'NAN';
        }

        $value = (string) $this->value;

        for ($digits = 15; (float) $value !== $this->value && $digits <= 17; $digits++) {
            $value = sprintf("%.{$digits}G", $this->value);
        }

        return $value;
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        return 'float';
    }
}
