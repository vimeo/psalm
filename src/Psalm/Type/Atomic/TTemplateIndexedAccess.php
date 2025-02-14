<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 */
final class TTemplateIndexedAccess extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(
        public string $array_param_name,
        public string $offset_param_name,
        public string $defining_class,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return $this->array_param_name . '[' . $this->offset_param_name . ']';
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
        return null;
    }

    #[Override]
    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
