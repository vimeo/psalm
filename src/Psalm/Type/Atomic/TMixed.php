<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `mixed` type, used when you don’t know the type of an expression.
 *
 * @psalm-immutable
 */
class TMixed extends Atomic
{
    /** @var bool */
    public $from_loop_isset = false;

    public function __construct(bool $from_loop_isset = false, bool $from_docblock = false)
    {
        $this->from_loop_isset = $from_loop_isset;
        $this->from_docblock = $from_docblock;
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'mixed';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): ?string {
        return $analysis_php_version_id >= 8_00_00 ? 'mixed' : null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $analysis_php_version_id >= 8_00_00;
    }

    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
