<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * The `*` type argument of a generic object (`Pair<int, *>`): the template stands for an unknown
 * type. Any argument fits it, reading the template yields its bound and writing to it is
 * impossible (the parameter is `never`), like PHPStan's star projection.
 *
 * @psalm-immutable
 * @api
 */
final class TStarProjection extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param Union|null $as what is known of the unknown type, null when nothing narrower than the bound of the
     *                       template it stands for: once the type is expanded, that bound with the other arguments
     *                       of the generic object applied; it may mention templates (`Dep<T, *>` with `B of A`
     *                       knows `B` is a `T`), and is the union of what is known of each `*` combined into it
     */
    public function __construct(
        public ?Union $as = null,
        bool $from_docblock = false,
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
     * Two `*` are displayed alike whatever is known of them, but they are equal only when that is equal too
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
