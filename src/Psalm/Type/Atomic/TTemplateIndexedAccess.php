<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Interner;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * @psalm-immutable
 * @api
 */
final class TTemplateIndexedAccess extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(
        public int $array_param_name,
        public int $offset_param_name,
        public int $defining_class,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return Interner::str($this->array_param_name) . '[' . Interner::str($this->offset_param_name) . ']';
    }

    /**
     * @param array<int, int> $aliased_classes
     * @psalm-pure
     */
    #[Override]
    public function toPhpString(
        ?int $namespace,
        array $aliased_classes,
        ?int $this_class,
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
