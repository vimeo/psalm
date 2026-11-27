<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Type\Atomic;

use function implode;

/**
 * Denotes an object that is also `callable` (i.e. it has `__invoke` defined).
 *
 * @psalm-immutable
 * @api
 */
final class TCallableObject extends TObject
{
    use HasIntersectionTrait;

    /**
     * @return true
     * @psalm-pure
     */
    #[Override]
    public function isCallableType(): bool
    {
        return true;
    }

    public function __construct(bool $from_docblock = false, public ?TCallable $callable = null)
    {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        $key = 'callable-object';
        if ($this->callable !== null) {
            $key .= $this->callable->getParamString() . $this->callable->getReturnTypeString();
        }
        if ($include_extra && $this->extra_types) {
            // parenthesised so that the intersection doesn't read as part of the return type
            $key = '(' . $key . ')&' . implode('&', $this->extra_types);
        }
        return $key;
    }

    #[Override]
    public function getId(bool $exact = true): string
    {
        $id = 'callable-object';
        if ($this->callable !== null) {
            $id .= $this->callable->getParamString() . $this->callable->getReturnTypeString();
        }
        return $id . $this->getIntersectionTypesId($exact);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['callable', 'extra_types'];
    }

    #[Override]
    public function equals(Atomic $other_type, bool $ensure_source_equality): bool
    {
        if (!$other_type instanceof self
            || ($this->callable === null) !== ($other_type->callable === null)
            || !$this->intersectionTypesEqual($other_type->extra_types, $ensure_source_equality)
        ) {
            return false;
        }

        return $this->callable === null
            || $other_type->callable === null
            || $this->callable->equals($other_type->callable, $ensure_source_equality);
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
        return $analysis_php_version_id >= 7_02_00 ? 'object' : null;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAssertionString(): string
    {
        return 'object';
    }
}
