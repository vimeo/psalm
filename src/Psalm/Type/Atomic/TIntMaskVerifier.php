<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

use function array_merge;
use function array_unique;
use function implode;
use function in_array;
use function sort;

/**
 * An Atomic type that performs checks for TIntMask and TIntMaskOf.
 *
 * @psalm-immutable
 */
final class TIntMaskVerifier extends TInt
{
    public ?int $mask;

    /**
     * @param array<int> $potential_ints
     */
    public function __construct(
        public array $potential_ints,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);

        if (!in_array(0, $this->potential_ints)) {
            array_unshift($this->potential_ints, 0);
        }

        $this->mask = 0;
        foreach ($this->potential_ints as $int) {
            $this->mask |= $int;
        }
    }


    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return 'int-mask-verifier<' . implode(',', $this->potential_ints) . '>';
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * Checks if the given integer is a valid value based on the mask.
     *
     * @param int $i The integer to check.
     * @return bool True if the integer is valid, false otherwise.
     */
    public function isValidValue(int $i): bool
    {
        if ($i === 0) {
            return true;
        }

        return ($this->mask & $i) === $i;
    }

    /**
     * Checks if this verifier is a superset of the given verifier.
     *
     * @param TIntMaskVerifier $input_type_part The verifier to check against.
     * @return bool True if this verifier is a superset of the other, false otherwise.
     */
    public function isSupersetOf(TIntMaskVerifier $input_type_part): bool
    {
        return ($this->mask & $input_type_part->mask) === $input_type_part->mask;
    }

    /**
     * Compute all possible concrete ints that can be formed by OR-ing any subset of potential_ints.
     * Note: This may grow exponentially with the number of elements; callers should guard usage.
     *
     * @return array<int> Sorted unique list of possible ints.
     */
    public function getPossibleInts(): array
    {
        $values = [0];
        $bits = $this->potential_ints;

        foreach ($bits as $bit) {
            $new = [];
            foreach ($values as $v) {
                $new[] = $v | $bit;
            }
            $values = array_unique(array_merge($values, $new));
        }

        sort($values);
        return $values;
    }
}
