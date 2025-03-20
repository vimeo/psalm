<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;

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

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        $key = 'callable-object';
        if ($this->callable !== null) {
            $key .= $this->callable->getParamString() . $this->callable->getReturnTypeString();
        }

        return $key;
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
