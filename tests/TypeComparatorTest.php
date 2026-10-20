<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Type;
use Psalm\Type\Atomic\TPropertiesOf;

use function array_diff_key;
use function array_keys;
use function array_map;

final class TypeComparatorTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();

        $providers = new Providers(
            $this->file_provider,
            new FakeParserCacheProvider(),
        );

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
        );
    }

    #[DataProvider('getAllBasicTypes')]
    public function testTypeAcceptsItself(string $type_string): void
    {
        $type_1 = Type::parseString($type_string);
        $type_2 = Type::parseString($type_string);

        $this->assertTrue(
            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $type_1,
                $type_2,
            ),
        );
    }

    /**
     * @return array<array{string}>
     * @psalm-pure
     */
    public static function getAllBasicTypes(): array
    {
        // these types are not valid without generics attached
        $basic_generic_types = [
            'key-of' => true,
            'arraylike-object' => true,
            'value-of' => true,
            'class-string-map' => true,
            'int-mask-of' => true,
            'int-mask' => true,
            'pure-Closure' => true,
            'pure-callable' => true,
        ];
        foreach (TPropertiesOf::tokenNames() as $token_name) {
            $basic_generic_types[$token_name] = true;
        }

        $basic_types = array_diff_key(
            TypeTokenizer::PSALM_RESERVED_WORDS,
            $basic_generic_types,
            [
                'open-resource' => true, // unverifiable
                'non-empty-countable' => true, // bit weird, maybe a bug?
            ],
            [
                'array' => true, // Requires a shape
                'list' => true, // Requires a shape
            ],
        );
        $basic_types['array{test: 123}'] = true;
        $basic_types['list{123}'] = true;

        return array_map(
            static fn($type) => [$type],
            array_keys($basic_types),
        );
    }

    #[DataProvider('getSuccessfulComparisons')]
    public function testTypeAcceptsType(string $parent_type_string, string $child_type_string): void
    {
        $parent_type = Type::parseString($parent_type_string);
        $child_type = Type::parseString($child_type_string);

        $this->assertTrue(
            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $child_type,
                $parent_type,
            ),
            'Type ' . $parent_type_string . ' should contain ' . $child_type_string,
        );
    }

    #[DataProvider('getUnsuccessfulComparisons')]
    public function testTypeDoesNotAcceptType(string $parent_type_string, string $child_type_string): void
    {
        $parent_type = Type::parseString($parent_type_string);
        $child_type = Type::parseString($child_type_string);

        $this->assertFalse(
            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $child_type,
                $parent_type,
            ),
            'Type ' . $parent_type_string . ' should not contain ' . $child_type_string,
        );
    }

    #[DataProvider('getCoercibleComparisons')]
    public function testTypeIsCoercible(string $parent_type_string, string $child_type_string): void
    {
        $parent_type = Type::parseString($parent_type_string);
        $child_type = Type::parseString($child_type_string);

        $result = new TypeComparisonResult();

        $contained = UnionTypeComparator::isContainedBy(
            $this->project_analyzer->getCodebase(),
            $child_type,
            $parent_type,
            false,
            false,
            $result,
        );

        $this->assertFalse($contained, 'Type ' . $parent_type_string . ' should not contain ' . $child_type_string);
        $this->assertTrue(
            $result->type_coerced,
            'Type ' . $parent_type_string . ' should be coercible into ' . $child_type_string,
        );
    }

    /**
     * @return iterable<string, list{string, string}>
     * @psalm-pure
     */
    public static function getCoercibleComparisons(): iterable
    {
        yield 'callableStringIntoLowercaseString' => [
            'lowercase-string',
            'callable-string',
        ];
        yield 'lowercaseStringIntoCallableString' => [
            'callable-string',
            'lowercase-string',
        ];
    }

    /**
     * @return array<array{string, string}>
     * @psalm-pure
     */
    public static function getSuccessfulComparisons(): array
    {
        return [
            'iterableAcceptsArray' => [
                'iterable',
                'array',
            ],
            'listAcceptsEmptyArray' => [
                'list',
                'array<never, never>',
            ],
            'arrayAcceptsEmptyArray' => [
                'array',
                'array<never, never>',
            ],
            'arrayOptionalKeyed1AcceptsEmptyArray' => [
                'array{foo?: string}',
                'array<never, never>',
            ],
            'arrayOptionalKeyed2AcceptsEmptyArray' => [
                'array{foo?: string}&array<string, mixed>',
                'array<never, never>',
            ],
            'callableUnionAcceptsCallableUnion' => [
                '(callable(int,string[]): void)|(callable(int): void)',
                '(callable(int): void)|(callable(int,string[]): void)',
            ],
            'callableAcceptsCallableArray' => [
                'callable',
                "callable-array{0: class-string, 1: 'from'}",
            ],
            'callableAcceptsCallableObject' => [
                'callable',
                "callable-object",
            ],
            'callableAcceptsCallableString' => [
                'callable',
                'callable-string',
            ],
            'callableAcceptsCallableKeyedList' => [
                'callable',
                "callable-list{class-string, 'from'}",
            ],
            'lowercaseStringAcceptsNonFalsyLowercaseString' => [
                'lowercase-string',
                'lowercase-string&non-falsy-string',
            ],
            'nonEmptyLowercaseStringAcceptsNonFalsyLowercaseString' => [
                'non-empty-lowercase-string',
                'lowercase-string&non-falsy-string',
            ],
            'nonFalsyStringAcceptsNonFalsyLowercaseString' => [
                'non-falsy-string',
                'lowercase-string&non-falsy-string',
            ],
            'nonFalsyLowercaseStringAcceptsLowercaseNonFalsyLiteral' => [
                'lowercase-string&non-falsy-string',
                "'abc'",
            ],
            'numericStringAcceptsNonFalsyNumericString' => [
                'numeric-string',
                'non-falsy-string&numeric-string',
            ],
            'nonFalsyStringAcceptsNonFalsyNumericString' => [
                'non-falsy-string',
                'non-falsy-string&numeric-string',
            ],
            'nonFalsyNumericStringAcceptsNonZeroNumericLiteral' => [
                'non-falsy-string&numeric-string',
                "'5'",
            ],
            'lowercaseStringAcceptsLowercaseNumericString' => [
                'lowercase-string',
                'lowercase-string&numeric-string',
            ],
            'numericStringAcceptsLowercaseNumericString' => [
                'numeric-string',
                'lowercase-string&numeric-string',
            ],
            'lowercaseNumericStringAcceptsLowercaseNumericLiteral' => [
                'lowercase-string&numeric-string',
                "'1e3'",
            ],
            'nonFalsyLowercaseStringAcceptsNonFalsyLowercaseNumericString' => [
                'lowercase-string&non-falsy-string',
                'lowercase-string&non-falsy-string&numeric-string',
            ],
            'nonFalsyNumericStringAcceptsNonFalsyLowercaseNumericString' => [
                'non-falsy-string&numeric-string',
                'lowercase-string&non-falsy-string&numeric-string',
            ],
            'decimalIntStringAcceptsDecimalIntLiteral' => [
                'decimal-int-string',
                "'-5'|'0'|'123'",
            ],
            'numericStringAcceptsDecimalIntString' => [
                'numeric-string',
                'decimal-int-string',
            ],
            'nonEmptyLowercaseStringAcceptsDecimalIntString' => [
                'non-empty-lowercase-string',
                'decimal-int-string',
            ],
            'arrayKeyAcceptsDecimalIntString' => [
                'array-key',
                'decimal-int-string',
            ],
            'nonDecimalIntStringAcceptsNonDecimalIntLiteral' => [
                'non-decimal-int-string',
                "''|'-0'|'0123'|'1.0'|'abc'|'+1'|' 1'|'9223372036854775808'",
            ],
            'nonDecimalIntStringAcceptsClassString' => [
                'non-decimal-int-string',
                'class-string',
            ],
            'lowercaseNumericStringAcceptsDecimalIntString' => [
                'lowercase-string&numeric-string',
                'decimal-int-string',
            ],
            'stringAcceptsNonDecimalIntString' => [
                'string',
                'non-decimal-int-string',
            ],
        ];
    }

    /**
     * @return iterable<string, list{string,string}>
     * @psalm-pure
     */
    public static function getUnsuccessfulComparisons(): iterable
    {
        yield 'nonFalsyLowercaseStringDoesNotAcceptFalsyLiteral' => [
            'lowercase-string&non-falsy-string',
            "'0'",
        ];
        yield 'nonFalsyLowercaseStringDoesNotAcceptUppercaseLiteral' => [
            'lowercase-string&non-falsy-string',
            "'Abc'",
        ];
        yield 'nonFalsyLowercaseStringDoesNotAcceptNonEmptyLowercaseString' => [
            'lowercase-string&non-falsy-string',
            'non-empty-lowercase-string',
        ];
        yield 'nonFalsyLowercaseStringDoesNotAcceptNonFalsyString' => [
            'lowercase-string&non-falsy-string',
            'non-falsy-string',
        ];
        yield 'nonFalsyLowercaseStringDoesNotAcceptCallableString' => [
            'lowercase-string&non-falsy-string',
            'callable-string',
        ];
        yield 'nonFalsyNumericStringDoesNotAcceptZero' => [
            'non-falsy-string&numeric-string',
            "'0'",
        ];
        yield 'nonFalsyNumericStringDoesNotAcceptNumericString' => [
            'non-falsy-string&numeric-string',
            'numeric-string',
        ];
        yield 'nonFalsyNumericStringDoesNotAcceptLowercaseNumericString' => [
            'non-falsy-string&numeric-string',
            'lowercase-string&numeric-string',
        ];
        yield 'lowercaseNumericStringDoesNotAcceptUppercaseNumericLiteral' => [
            'lowercase-string&numeric-string',
            "'1E3'",
        ];
        yield 'lowercaseNumericStringDoesNotAcceptNonFalsyLowercaseString' => [
            'lowercase-string&numeric-string',
            'lowercase-string&non-falsy-string',
        ];
        yield 'nonFalsyLowercaseNumericStringDoesNotAcceptNonFalsyNumericString' => [
            'lowercase-string&non-falsy-string&numeric-string',
            'non-falsy-string&numeric-string',
        ];
        yield 'nonFalsyLowercaseNumericStringDoesNotAcceptDecimalIntString' => [
            'lowercase-string&non-falsy-string&numeric-string',
            'decimal-int-string',
        ];
        yield 'lowercaseNumericStringDoesNotAcceptNonDecimalIntString' => [
            'lowercase-string&numeric-string',
            'non-decimal-int-string',
        ];
        yield 'decimalIntStringDoesNotAcceptNumericString' => [
            'decimal-int-string',
            'numeric-string',
        ];
        yield 'decimalIntStringDoesNotAcceptNonCanonicalLiteral' => [
            'decimal-int-string',
            "'0123'",
        ];
        yield 'decimalIntStringDoesNotAcceptNegativeZero' => [
            'decimal-int-string',
            "'-0'",
        ];
        yield 'decimalIntStringDoesNotAcceptNonDecimalIntString' => [
            'decimal-int-string',
            'non-decimal-int-string',
        ];
        yield 'nonFalsyStringDoesNotAcceptDecimalIntString' => [
            'non-falsy-string',
            'decimal-int-string',
        ];
        yield 'nonDecimalIntStringDoesNotAcceptDecimalIntLiteral' => [
            'non-decimal-int-string',
            "'123'",
        ];
        yield 'nonDecimalIntStringDoesNotAcceptDecimalIntString' => [
            'non-decimal-int-string',
            'decimal-int-string',
        ];
        yield 'nonDecimalIntStringDoesNotAcceptString' => [
            'non-decimal-int-string',
            'string',
        ];
        yield 'nonEmptyStringDoesNotAcceptNonDecimalIntString' => [
            'non-empty-string',
            'non-decimal-int-string',
        ];
        yield 'genericListDoesNotAcceptListTupleWithMismatchedTypes' => [
            'list<int>',
            'list{int, string}',
        ];
        yield 'genericListDoesNotAcceptArrayTupleWithMismatchedTypes' => [
            'list<int>',
            'array{int, string}',
        ];
        yield 'nonEmptyMixedDoesNotAcceptMixed' => [
            'non-empty-mixed',
            'mixed',
        ];
    }
}
