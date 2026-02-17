<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\Mutations;

/**
 * Denotes an object that is also `callable` (i.e. it has `__invoke` defined).
 *
 * @psalm-immutable
 */
final class TCallableObject extends TObject
{
    use HasIntersectionTrait;

    /** @return true */
    #[Override]
    public function isCallableType(): bool
    {
        return true;
    }

    public function __construct(bool $from_docblock = false, public ?TCallable $callable = null)
    {
        parent::__construct($from_docblock);
    }

    public function setAllowedMutations(int $allowed_mutations): self
    {
        $current = $this->callable?->allowed_mutations ?? Mutations::LEVEL_EXTERNAL;
        if ($current === $allowed_mutations) {
            return $this;
        }
        $cloned = clone $this;
        $cloned->callable = $cloned->callable?->setAllowedMutations($allowed_mutations) ?? new TCallable(allowed_mutations: $allowed_mutations);
        return $cloned;
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        $key = 'callable-object';
        $prefix = match ($this->callable?->allowed_mutations ?? Mutations::LEVEL_EXTERNAL) {
            Mutations::LEVEL_NONE => 'pure-',
            Mutations::LEVEL_INTERNAL_READ => 'self-accessing-',
            Mutations::LEVEL_INTERNAL_READ_WRITE => 'self-mutating-',
            Mutations::LEVEL_EXTERNAL => 'impure-',
        };
        if ($this->callable !== null) {
            $key .= $this->callable->getParamString() . $this->callable->getReturnTypeString();
        }
        return $prefix . $key;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return $analysis_php_version_id >= 7_02_00 ? 'object' : null;
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    #[Override]
    public function getAssertionString(): string
    {
        return 'object';
    }
}
