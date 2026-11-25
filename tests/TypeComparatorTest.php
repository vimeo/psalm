<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeTokenizer;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TGenericObject;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TIterable;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyLowercaseString;
use Psalm\Type\Atomic\TNonEmptyNonspecificLiteralString;
use Psalm\Type\Atomic\TNonEmptyString;
use Psalm\Type\Atomic\TNonFalsyString;
use Psalm\Type\Atomic\TNumericString;
use Psalm\Type\Atomic\TPropertiesOf;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTypeVariable;
use Psalm\Type\Union;

use function array_diff_key;
use function array_keys;
use function array_map;
use function array_reverse;
use function hrtime;
use function is_int;

final class TypeComparatorTest extends TestCase
{
    protected const ANALYSIS_PHP_VERSION = null;

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

    public function testDecompositionRestrictsMixedArrayKeys(): void
    {
        $this->assertTrue(UnionTypeComparator::isContainedBy(
            $this->project_analyzer->getCodebase(),
            Type::parseString('iterable<mixed, int>'),
            new Union([
                Type::parseString('array<array-key, int>')->getSingleAtomic(),
                Type::parseString('Traversable<mixed, int>')->getSingleAtomic(),
            ]),
        ));
    }

    #[DataProvider('decomposedIterableInferenceBoundCases')]
    public function testDecomposedIterablePreservesInferenceBounds(bool $upper): void
    {
        $variable = new Union([new TTypeVariable('_test')]);
        $input_value = $upper ? $variable : Type::getInt();
        $container_value = $upper ? Type::getInt() : $variable;
        $result = new TypeComparisonResult();
        $this->assertTrue(UnionTypeComparator::isContainedBy(
            $this->project_analyzer->getCodebase(),
            new Union([new TIterable([Type::getInt(), $input_value])]),
            new Union([
                new TArray([Type::getInt(), $container_value]),
                new TGenericObject('Traversable', [Type::getInt(), $container_value]),
            ]),
            false,
            false,
            $result,
        ));
        $bounds = $upper ? $result->type_variable_upper_bounds : $result->type_variable_lower_bounds;
        $this->assertNotEmpty($bounds);
        foreach ($bounds as [$name, $bound]) {
            $this->assertSame('_test', $name);
            $this->assertSame('int', $bound->type->getId());
        }
    }

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function decomposedIterableInferenceBoundCases(): iterable
    {
        yield 'lower bounds' => [false];
        yield 'upper bounds' => [true];
    }

    public function testSymbolicContainerKeepsIterableBound(): void
    {
        $input = Type::parseString('iterable<int, string>');
        $result = new TypeComparisonResult();
        $this->assertTrue(UnionTypeComparator::isContainedBy(
            $this->project_analyzer->getCodebase(),
            $input,
            new Union([new TTypeVariable('_test')]),
            false,
            false,
            $result,
        ));
        $this->assertCount(1, $result->type_variable_lower_bounds);
        $this->assertSame($input->getId(), $result->type_variable_lower_bounds[0][1]->type->getId());
    }

    public function testIterableContainedBySeparateArrayAndTraversableAtoms(): void
    {
        $container = new Union([
            Type::parseString('array<string, int>')->getSingleAtomic(),
            Type::parseString('Traversable<string, int>')->getSingleAtomic(),
        ]);
        $result = new TypeComparisonResult();
        $this->assertTrue(UnionTypeComparator::isContainedBy(
            $this->project_analyzer->getCodebase(),
            Type::parseString('iterable<string, int>'),
            $container,
            false,
            false,
            $result,
        ));
        $this->assertNotTrue($result->type_coerced);
        $this->assertNotTrue($result->type_coerced_from_mixed);
        $this->assertNotTrue($result->to_string_cast);

        $failure = new TypeComparisonResult();
        $this->assertFalse(UnionTypeComparator::isContainedBy(
            $this->project_analyzer->getCodebase(),
            Type::parseString('iterable<mixed, mixed>'),
            $container,
            false,
            false,
            $failure,
        ));
        $this->assertTrue($failure->type_coerced);
        $this->assertTrue($failure->type_coerced_from_mixed);
    }

    #[DataProvider('iterableDiagnosticCases')]
    public function testIterableDiagnostics(string $input, string $container, bool $coerced, bool $mixed): void
    {
        $container_type = Type::parseString($container);
        foreach ([$container_type, $container_type->setTypes(array_reverse($container_type->getAtomicTypes()))] as $type) {
            $result = new TypeComparisonResult();
            $this->assertFalse(UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                Type::parseString($input),
                $type,
                false,
                false,
                $result,
            ));
            $this->assertSame($coerced, (bool) $result->type_coerced);
            $this->assertSame($mixed, (bool) $result->type_coerced_from_mixed);
        }
    }

    /**
     * @return iterable<string, array{string, string, bool, bool}>
     * @psalm-pure
     */
    public static function iterableDiagnosticCases(): iterable
    {
        yield 'hard keys with mixed values' => ['array<int, mixed>', 'iterable<string, int>', false, true];
        yield 'valid keys with mixed values' => ['array<string, mixed>', 'iterable<string, int>', true, true];
        yield 'mixed keys with hard values' => ['array<mixed, string>', 'iterable<int, int>', false, true];
        yield 'hard keys and values' => ['array<int, string>', 'iterable<string, int>', false, false];
        yield 'direct iterable coercion' => ['iterable<mixed, mixed>', 'iterable<string>', true, true];
        yield 'array to iterable coercion' => ['array', 'iterable<string>', true, true];
        yield 'both halves coerce' => ['iterable<mixed, mixed>', 'array<string>|Traversable<string>', true, true];
        yield 'array half cannot cover traversable' => ['iterable<mixed, mixed>', 'array<string>', false, false];
        yield 'uncovered traversable half' => ['iterable<mixed, mixed>', 'array<string>|int', false, false];
        yield 'known generic mismatch' => ['iterable<string, string>', 'list<int>|Traversable<int, int>', false, false];
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

    #[DataProvider('getUnionMemberOrderComparisons')]
    public function testComparisonResultDoesNotDependOnUnionMemberOrder(
        string $container_type_string,
        string $input_member_1,
        string $input_member_2,
    ): void {
        $container_type = Type::parseString($container_type_string);
        $results = [];

        foreach ([[$input_member_1, $input_member_2], [$input_member_2, $input_member_1]] as $members) {
            $result = new TypeComparisonResult();

            UnionTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                Type::parseString($members[0] . '|' . $members[1]),
                $container_type,
                false,
                false,
                $result,
            );

            $results[] = [
                $result->scalar_type_match_found,
                $result->type_coerced,
                $result->type_coerced_from_mixed,
                $result->type_coerced_from_as_mixed,
                $result->type_coerced_from_scalar,
                $result->to_string_cast,
                $result->missing_shape_fields,
            ];
        }

        $this->assertSame($results[0], $results[1]);
    }

    /**
     * @return iterable<string, list{string, string, string}>
     * @psalm-pure
     */
    public static function getUnionMemberOrderComparisons(): iterable
    {
        yield 'scalar and callable into int' => ['int', 'scalar', 'callable'];
        yield 'scalar and object into int' => ['int', 'scalar', 'object'];
        yield 'string and callable into int' => ['int', 'string', 'callable'];
        yield 'string and null into int' => ['int', 'string', 'null'];
        yield 'literal and callable into int' => ['int', '1', 'callable'];
        yield 'array of mixed and string into array of int' => ['array<int>', 'array<mixed>', 'string'];
        yield 'array of mixed and callable into array of int' => ['array<int>', 'array<mixed>', 'callable'];
        yield 'array of mixed and null into array of int' => ['array<int>', 'array<mixed>', 'null'];
        yield 'array of mixed and int into array of string' => ['array<string>', 'array<mixed>', 'int'];
        yield 'int and float into string' => ['string', 'int', 'float'];
        yield 'float and int into int literals' => ['1|1024', 'float', 'int'];
        yield 'array of mixed and int into string' => ['string', 'array<mixed>', 'int'];
        yield 'int and array of mixed into string' => ['string', 'int', 'array<mixed>'];
        yield 'matching and coercible member' => ['non-empty-string', 'non-empty-string', 'string'];
        yield 'non-matching and coercible shapes' => [
            'array{a: int, b: string}',
            'array{a: int}',
            'array{b: string}',
        ];
    }

    /**
     * @return iterable<string, list{string, string}>
     * @psalm-pure
     */
    public static function getCoercibleComparisons(): iterable
    {
        yield 'objectShapeIntoObjectShapeIntersection' => [
            'object{a: int}&Countable',
            'object{a: int}',
        ];
        yield 'callableStringIntoLowercaseString' => [
            'lowercase-string',
            'callable-string',
        ];
        yield 'lowercaseStringIntoCallableString' => [
            'callable-string',
            'lowercase-string',
        ];
        yield 'literalStringIntoClassStringAmongLiterals' => [
            "'a'|'b'|class-string",
            "'Foo'",
        ];
        yield 'literalStringIntoLiteralClassStringAmongLiterals' => [
            "'a'|'b'|Foo::class",
            "'Foo'",
        ];
        yield 'starArgumentIntoInvariantArgument' => [
            'ArrayObject<int, string>',
            'ArrayObject<int, *>',
        ];
        yield 'starArgumentIntoInvariantMixedArgument' => [
            'ArrayObject<int, mixed>',
            'ArrayObject<int, *>',
        ];
    }

    /**
     * @return array<array{string, string}>
     * @psalm-pure
     */
    public static function getSuccessfulComparisons(): array
    {
        return [
            'objectShapeIntersectionAcceptsItsIntersection' => [
                'object{a: int}&Countable',
                'object{a: int}&Countable',
            ],
            'iterableUnion' => ['array<string, int>|Traversable<string, int>', 'iterable<string, int>'],
            'mixedIterableUnion' => ['array<array-key, mixed>|Traversable<mixed, mixed>', 'iterable<mixed, mixed>'],
            'unparameterizedIterableUnion' => ['array|Traversable', 'iterable'],
            'iterableAcceptsArray' => [
                'iterable',
                'array',
            ],
            'arrayOrObjectAcceptsIterable' => [
                'array|object',
                'iterable<int>',
            ],
            'arrayOrObjectOrNullAcceptsIterable' => [
                'array|object|null',
                'iterable<string, int>',
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
            'literalIntsAcceptSomeOfThem' => [
                '1|2|3',
                '3|1',
            ],
            'literalStringsAndNumericAcceptAnotherLiteral' => [
                "'a'|'b'|numeric",
                "'5'",
            ],
            'literalIntsAndFloatAcceptAnotherLiteral' => [
                '1|2|float',
                '3',
            ],
            'literalsOfBothClassesAndFloatAcceptLiterals' => [
                "'a'|1|float",
                "'a'|1|2",
            ],
            'literalIntsAndStringsAcceptBoth' => [
                "1|'1'|2",
                "'1'|1",
            ],
            'starArgumentAcceptsAnyArgument' => [
                'ArrayObject<int, *>',
                'ArrayObject<int, string>',
            ],
            'starArgumentAcceptsStarArgument' => [
                'ArrayObject<int, *>',
                'ArrayObject<int, *>',
            ],
        ];
    }

    /**
     * @return iterable<string, list{string,string}>
     * @psalm-pure
     */
    public static function getUnsuccessfulComparisons(): iterable
    {
        yield 'iterableIntersectionDoesNotAcceptIterable' => [
            'iterable&Countable',
            'iterable',
        ];
        yield 'iterableIntersectionDoesNotAcceptArray' => [
            'iterable<int, string>&Countable',
            'list<string>',
        ];
        yield 'objectOrStringDoesNotAcceptIterable' => [
            'object|string',
            'iterable<int>',
        ];
        yield 'arrayOrIntDoesNotAcceptIterable' => [
            'array|int',
            'iterable<int>',
        ];
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
        yield 'literalIntsDoNotAcceptAnotherLiteral' => [
            '1|2|3',
            '2|4',
        ];
        yield 'literalStringsDoNotAcceptLiteralIntOfTheSameValue' => [
            "'1'|'2'",
            '1',
        ];
        yield 'literalIntsDoNotAcceptLiteralStringOfTheSameValue' => [
            '1|2',
            "'1'",
        ];
        yield 'iterableListGenericMismatch' => ['list<int>|Traversable<int, int>', 'iterable<string, string>'];
        yield 'nestedTraversableIsNotContainer' => ['array<int, Traversable>|int', 'iterable<string, string>'];
        yield 'iterableKeyMismatch' => ['array<int, string>|Traversable<int, string>', 'iterable<string, string>'];
        yield 'iterableValueMismatch' => ['array<string, int>|Traversable<string, int>', 'iterable<string, string>'];
    }

    #[DataProvider('getDifferentLiterals')]
    public function testLiteralDoesNotContainDifferentLiteral(int|string $input, int|string $container): void
    {
        $result = new TypeComparisonResult();

        $this->assertFalse(
            AtomicTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                is_int($input) ? new TLiteralInt($input) : new TLiteralString($input),
                is_int($container) ? new TLiteralInt($container) : new TLiteralString($container),
                false,
                true,
                $result,
            ),
        );
        // UnionTypeComparator skips these comparisons, so they must not report anything either
        $this->assertEquals(new TypeComparisonResult(), $result);
    }

    /**
     * @return iterable<string, list{int|string, int|string}>
     * @psalm-pure
     */
    public static function getDifferentLiterals(): iterable
    {
        yield 'ints' => [1, 2];
        yield 'emptyStringAndZero' => ['', '0'];
        yield 'zeroAndEmptyString' => ['0', ''];
        yield 'lowercaseAndUppercase' => ['a', 'A'];
        yield 'numericStrings' => ['1', '01'];
        yield 'intAndNumericString' => [1, '1'];
        yield 'numericStringAndInt' => ['1', 1];
        yield 'zeroAndEmptyStringOfOtherClasses' => [0, ''];
        yield 'stringAndInt' => ['a', 0];
    }

    #[DataProvider('getSingleLetterComparisons')]
    public function testSingleLetterContainment(Closure $input, bool $contained, bool $coerced): void
    {
        $result = new TypeComparisonResult();

        $this->assertSame(
            $contained,
            AtomicTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $input(),
                new TSingleLetter(),
                false,
                true,
                $result,
            ),
        );
        $this->assertSame($coerced, $result->type_coerced === true);
    }

    /**
     * @return iterable<string, list{Closure(): Atomic, bool, bool}>
     * @psalm-pure
     */
    public static function getSingleLetterComparisons(): iterable
    {
        yield 'singleLetter' => [static fn(): Atomic => new TSingleLetter(), true, false];
        yield 'oneLetterLiteral' => [static fn(): Atomic => new TLiteralString('a'), true, false];
        yield 'emptyLiteral' => [static fn(): Atomic => new TLiteralString(''), false, false];
        yield 'longerLiteral' => [static fn(): Atomic => new TLiteralString('ab'), false, false];
        yield 'string' => [static fn(): Atomic => new TString(), false, true];
        yield 'nonEmptyString' => [static fn(): Atomic => new TNonEmptyString(), false, true];
        yield 'nonFalsyString' => [static fn(): Atomic => new TNonFalsyString(), false, true];
        yield 'nonEmptyLowercaseString' => [static fn(): Atomic => new TNonEmptyLowercaseString(), false, true];
        yield 'nonEmptyLiteralString' => [static fn(): Atomic => new TNonEmptyNonspecificLiteralString(), false, true];
        yield 'numericString' => [static fn(): Atomic => new TNumericString(), false, true];
        yield 'classString' => [static fn(): Atomic => new TClassString(), false, true];
        yield 'int' => [static fn(): Atomic => new TInt(), false, false];
    }

    #[DataProvider('getNonEmptyStringRefinements')]
    public function testNonEmptyStringContainsItsRefinements(Atomic $input): void
    {
        $this->assertTrue(
            AtomicTypeComparator::isContainedBy(
                $this->project_analyzer->getCodebase(),
                $input,
                new TNonEmptyString(),
            ),
        );
    }

    /**
     * @return iterable<string, list{Atomic}>
     * @psalm-pure
     */
    public static function getNonEmptyStringRefinements(): iterable
    {
        yield 'singleLetter' => [new TSingleLetter()];
        yield 'nonEmptyLowercaseString' => [new TNonEmptyLowercaseString()];
        yield 'nonEmptyLiteralString' => [new TNonEmptyNonspecificLiteralString()];
    }

    /**
     * @param 'int'|'string'|'both' $literal_class
     */
    #[DataProvider('getLargeLiteralUnionClasses')]
    public function testLargeLiteralUnionIsComparedWithoutComparingEachPair(string $literal_class): void
    {
        // e.g. an int-mask of 12 bits
        $literals = [];
        for ($value = 0; $value < 4_096; $value++) {
            $literals[] = $literal_class === 'int' || ($literal_class === 'both' && $value % 2 === 0)
                ? new TLiteralInt($value)
                : new TLiteralString((string) $value);
        }

        $container_type = new Union($literals);
        $input_type = new Union(array_reverse($literals));

        $start = hrtime(true);
        $contained = UnionTypeComparator::isContainedBy(
            $this->project_analyzer->getCodebase(),
            $input_type,
            $container_type,
        );
        $nanoseconds = hrtime(true) - $start;

        $this->assertTrue($contained);
        // comparing each of the millions of pairs takes seconds to tens of seconds
        $this->assertLessThan(2_000_000_000, $nanoseconds);
    }

    /**
     * @return iterable<string, list{'int'|'string'|'both'}>
     * @psalm-pure
     */
    public static function getLargeLiteralUnionClasses(): iterable
    {
        yield 'ints' => ['int'];
        yield 'strings' => ['string'];
        yield 'intsAndStrings' => ['both'];
    }
}
