<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Override;
use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;
use Psalm\Type\Union;

use function array_map;
use function count;
use function implode;

/**
 * Represents the values having all of the given types, such as `value-of<Foo::BAR>&int`, until its types can be
 * intersected: they refer to class constants or type aliases.
 *
 * @psalm-immutable
 * @api
 */
final class TIntersection extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;

    /**
     * @param non-empty-list<Union> $types
     */
    public function __construct(public array $types, bool $from_docblock = false)
    {
        parent::__construct($from_docblock);
    }

    /**
     * @psalm-pure
     */
    #[Override]
    protected function getChildNodeKeys(): array
    {
        return ['types'];
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function getKey(bool $include_extra = true): string
    {
        return $this->getId();
    }

    /**
     * @psalm-mutation-free
     */
    #[Override]
    public function getId(bool $exact = true, bool $nested = false): string
    {
        return implode(
            '&',
            array_map(
                static fn(Union $type): string => count($type->getAtomicTypes()) > 1
                    ? '(' . $type->getId($exact) . ')'
                    : $type->getId($exact),
                $this->types,
            ),
        );
    }

    /**
     * @param array<lowercase-string, string> $aliased_classes
     * @psalm-mutation-free
     */
    #[Override]
    public function toNamespacedString(
        ?string $namespace,
        array $aliased_classes,
        ?string $this_class,
        bool $use_phpdoc_format,
    ): string {
        $type_strings = [];

        foreach ($this->types as $type) {
            $type_string = $type->toNamespacedString($namespace, $aliased_classes, $this_class, $use_phpdoc_format);
            $type_strings[] = count($type->getAtomicTypes()) > 1 ? '(' . $type_string . ')' : $type_string;
        }

        return implode('&', $type_strings);
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

    /**
     * @psalm-pure
     */
    #[Override]
    public function getAssertionString(): string
    {
        return 'mixed';
    }

    /**
     * @param non-empty-list<Union> $types
     * @return static
     */
    public function setTypes(array $types): self
    {
        if ($types === $this->types) {
            return $this;
        }

        $cloned = clone $this;
        $cloned->types = $types;

        return $cloned;
    }
}
