<?php

namespace Psalm\Type\Atomic;

/**
 * Denotes an int that is also positive (strictly > 0)
 * @deprecated will be removed in Psalm 5
 */
class TPositiveInt extends TInt
{
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return 'positive-int';
    }

    /**
     * @return false
     */
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     *
     */
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format
    ): string {
        return $use_phpdoc_format ? 'int' : 'positive-int';
    }
}
