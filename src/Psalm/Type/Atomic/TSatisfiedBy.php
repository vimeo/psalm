<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Codebase;
use Psalm\Storage\EnumCaseStorage;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_map;
use function array_values;
use function assert;

/**
 * Represents the as_type of a Union.
 *
 * @psalm-immutable
 */
final class TSatisfiedBy extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    public function __construct(public Union $type, bool $from_docblock = false)
    {
        parent::__construct($from_docblock);
    }

    protected function getChildNodeKeys(): array
    {
        return ['type'];
    }


    public function getKey(bool $include_extra = true): string
    {
        return 'satisfied-by<' . $this->type . '>';
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
        return null;
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }

    public function getAssertionString(): string
    {
        return 'mixed';
    }
}
