<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

/**
 * Type that resolves to a keyed-array with properties of a class as keys and
 * their appropriate types as values.
 *
 * @psalm-type TokenName = 'recursive-properties-of'
 * @psalm-immutable
 */
final class TRecursivePropertiesOf extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @return list<TokenName>
     */
    public static function tokenNames(): array
    {
        return [
            'recursive-properties-of',
        ];
    }

    public function __construct(
        public Union $type,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    protected function getChildNodeKeys(): array
    {
        return ['type'];
    }

    public function getKey(bool $include_extra = true): string
    {
        return 'recursive-properties-of<' . $this->type . '>';
    }

    /**
     * @param  array<lowercase-string, string> $aliased_classes
     */
    public function toPhpString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        int $analysis_php_version_id,
    ): string {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
