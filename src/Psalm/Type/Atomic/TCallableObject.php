<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

/**
 * Denotes an object that is also `callable` (i.e. it has `__invoke` defined).
 *
 * @psalm-immutable
 */
final class TCallableObject extends TObject implements TCallableInterface
{
    use HasIntersectionTrait;

    public function __construct(bool $from_docblock = false, public ?TCallable $callable = null)
    {
        parent::__construct($from_docblock);
    }

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
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): ?string {
        return $analysis_php_version_id >= 7_02_00 ? 'object' : null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'object';
    }
}
