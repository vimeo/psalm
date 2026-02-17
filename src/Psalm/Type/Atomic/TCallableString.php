<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\Mutations;

/**
 * Denotes the `callable-string` type, used to represent an unknown string that is also `callable`.
 *
 * @psalm-immutable
 */
final class TCallableString extends TNonFalsyString
{
    public int $allowed_mutations = Mutations::LEVEL_ALL;

    public function __construct(bool $from_docblock = false, int $allowed_mutations = Mutations::LEVEL_ALL)
    {
        $this->allowed_mutations = $allowed_mutations;
        parent::__construct($from_docblock);
    }

    /**
     * @param Mutations::LEVEL_* $allowed_mutations
     * @return static
     */
    public function setAllowedMutations(int $allowed_mutations): self
    {
        if ($this->allowed_mutations === $allowed_mutations) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->allowed_mutations = $allowed_mutations;
        return $cloned;
    }

    #[Override]
    public function isCallableType(): bool
    {
        return true;
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        $prefix = match ($this->allowed_mutations) {
            Mutations::LEVEL_NONE => 'pure-',
            Mutations::LEVEL_INTERNAL_READ => 'self-accessing-',
            Mutations::LEVEL_INTERNAL_READ_WRITE => 'self-mutating-',
            Mutations::LEVEL_EXTERNAL => 'impure-',
        };

        return $prefix . 'callable-string';
    }

    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return $this->getKey();
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'string';
    }
}
