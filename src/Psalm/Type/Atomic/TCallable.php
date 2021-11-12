<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Denotes the `callable` type. Can result from an `is_callable` check.
 */
class TCallable extends Atomic
{
    use CallableTrait;

    /**
     * @var string
     */
    public $value;

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id
    ): string {
        return 'callable';
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return $this->params === null && $this->return_type === null;
    }
}
