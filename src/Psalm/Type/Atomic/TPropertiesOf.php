<?php

namespace Psalm\Type\Atomic;

use Psalm\Type\Atomic;

/**
 * Type that resolves to a keyed-array with properties of a class as keys and
 * their apropriate types as values.
 *
 * @psalm-type TokenName = 'properties-of'|'public-properties-of'|'protected-properties-of'|'private-properties-of'
 * @psalm-immutable
 */
final class TPropertiesOf extends Atomic
{
    // These should match the values of
    // `Psalm\Internal\Analyzer\ClassLikeAnalyzer::VISIBILITY_*`, as they are
    // used to compared against properties visibililty.
    public const VISIBILITY_PUBLIC = 1;
    public const VISIBILITY_PROTECTED = 2;
    public const VISIBILITY_PRIVATE = 3;

    public TNamedObject $classlike_type;
    /**
     * @var self::VISIBILITY_*|null
     */
    public $visibility_filter;

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
        TNamedObject $classlike_type,
        ?int $visibility_filter,
        bool $from_docblock = false
    ) {
        $this->classlike_type = $classlike_type;
        $this->visibility_filter = $visibility_filter;
        $this->from_docblock = $from_docblock;
    }

    /**
     * @return self::VISIBILITY_*|null
     */
    public static function filterForTokenName(string $token_name): ?int
    {
        switch ($token_name) {
            case 'public-properties-of':
                return self::VISIBILITY_PUBLIC;
            case 'protected-properties-of':
                return self::VISIBILITY_PROTECTED;
            case 'private-properties-of':
                return self::VISIBILITY_PRIVATE;
            default:
                return null;
        }
    }

    /**
     * @psalm-pure
     * @return TokenName
     */
    public static function tokenNameForFilter(?int $visibility_filter): string
    {
        switch ($visibility_filter) {
            case self::VISIBILITY_PUBLIC:
                return 'public-properties-of';
            case self::VISIBILITY_PROTECTED:
                return 'protected-properties-of';
            case self::VISIBILITY_PRIVATE:
                return  'private-properties-of';
            default:
                return 'properties-of';
        }
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
        int $analysis_php_version_id
    ): string {
        return $this->getKey();
    }

    public function canBeFullyExpressedInPhp(int $analysis_php_version_id): bool
    {
        return false;
    }
}
