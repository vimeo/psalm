<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

/**
 * An Atomic type that performs checks for TIntMask and TIntMaskOf.
 * @psalm-immutable
 */
final class TIntMaskVerifier extends TInt
{
    public ?int $mask = null;

    /**
     * @param array<int> $potential_ints
     */
    public function __construct(
        public array $potential_ints,
        bool $from_docblock = false
    ) {
        parent::__construct($from_docblock);
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

    public function isValidValue(int $i): bool
    {
        if ($i === 0) {
            return true;
        }

        if ($this->mask === null) {
            $this->mask = 0;
            foreach ($this->potential_ints as $int) {
                $this->mask |= $int;
            }
        }

        return ($this->mask & $i) === $i;
    }


}