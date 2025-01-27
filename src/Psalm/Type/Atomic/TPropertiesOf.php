<?php

declare(strict_types=1);

namespace Psalm\Type\Atomic;

use Psalm\Storage\UnserializeMemoryUsageSuppressionTrait;
use Psalm\Type\Atomic;

/**
 * Type that resolves to a keyed-array with properties of a class as keys and
 * their appropriate types as values.
 *
 * @psalm-type TokenName = 'properties-of'|'public-properties-of'|'protected-properties-of'|'private-properties-of'
 * @psalm-immutable
 */
final class TPropertiesOf extends Atomic
{
    use UnserializeMemoryUsageSuppressionTrait;
    // These should match the values of
    // `Psalm\Internal\Analyzer\ClassLikeAnalyzer::VISIBILITY_*`, as they are
    // used to compared against properties visibility.
    public const VISIBILITY_PUBLIC = 1;
    public const VISIBILITY_PROTECTED = 2;
    public const VISIBILITY_PRIVATE = 3;

    /**
     * @return list<TokenName>
     */
    public static function tokenNames(): array
    {
        return [
            'properties-of',
            'public-properties-of',
            'protected-properties-of',
            'private-properties-of',
        ];
    }

    /**
     * @param self::VISIBILITY_*|null $visibility_filter
     */
    public function __construct(
        public TNamedObject $classlike_type,
        public ?int $visibility_filter,
        bool $from_docblock = false,
    ) {
        parent::__construct($from_docblock);
    }

    /**
     * @return self::VISIBILITY_*|null
     */
    public static function filterForTokenName(string $token_name): ?int
    {
        return match ($token_name) {
            'public-properties-of' => self::VISIBILITY_PUBLIC,
            'protected-properties-of' => self::VISIBILITY_PROTECTED,
            'private-properties-of' => self::VISIBILITY_PRIVATE,
            default => null,
        };
    }

    /**
     * @psalm-pure
     * @return TokenName
     */
    public static function tokenNameForFilter(?int $visibility_filter): string
    {
        return match ($visibility_filter) {
            self::VISIBILITY_PUBLIC => 'public-properties-of',
            self::VISIBILITY_PROTECTED => 'protected-properties-of',
            self::VISIBILITY_PRIVATE => 'private-properties-of',
            default => 'properties-of',
        };
    }

    protected function getChildNodeKeys(): array
    {
        return ['classlike_type'];
    }

    public function getKey(bool $include_extra = true): string
    {
        return self::tokenNameForFilter($this->visibility_filter) . '<' . $this->classlike_type . '>';
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
