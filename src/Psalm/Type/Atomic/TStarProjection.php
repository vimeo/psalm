<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * The `*` type argument of a generic object (`Pair<int, *>`, PHPStan's star projection): the template stands for
 * some unknown type within what is known of it ($as). It holds any type, and is held by what holds all of $as.
 *
 * @psalm-immutable
 * @api
 */
final class TStarProjection extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param Union|null $as what is known of the unknown type, null for nothing (mixed). Once the generic object is
     *                       expanded, it is the bound of the template the `*` stands for, with the other type
     *                       arguments applied: `Pair<int, *>` with `@template B of list<A>` knows `list<int>`.
     */
    public function __construct(
        bool $from_docblock = false,
        public ?Union $as = null,
    ) {
        parent::__construct($from_docblock);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return '*';
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['as'];
    }

    /**
     * Two `*` are displayed alike whatever is known of them, but are equal only when that is equal too
     *
     * @psalm-mutation-free
     */
    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (!$other_type instanceof self) {
            return false;
        }

        if ($this->as === null || $other_type->as === null) {
            return $this->as === $other_type->as;
        }

        return $this->as->equals($other_type->as, false, false);
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return null;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
