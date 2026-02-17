<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
use Psalm\Type;
use Psalm\Type\Atomic;

use function array_reverse;

final class TypeCombinationTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @dataProvider providerTestValidTypeCombination
     * @param non-empty-list<string> $types
     */
    public function testValidTypeCombination(string $expected, array $types): void
    {
        $converted_types = [];

        foreach ($types as $type) {
            $converted_type = self::getAtomic($type);
            /** @psalm-suppress InaccessibleProperty */
            $converted_type->from_docblock = true;
            $converted_types[] = $converted_type;
        }

        $this->assertSame(
            $expected,
            TypeCombiner::combine($converted_types)->getId(),
        );

        $this->assertSame(
            $expected,
            TypeCombiner::combine(array_reverse($converted_types))->getId(),
        );
    }

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'multipleValuedArray' => [
                'code' => '<?php
                    class A {}
                    class B {}
                    $var = [];
                    $var[] = new A();
                    $var[] = new B();',
            ],
            'preventLiteralAndClassString' => [
                'code' => '<?php
                    /**
                     * @param "array"|class-string $type_name
                     */
                    function foo(string $type_name) : bool {
                        return $type_name === "array";
                    }',
            ],
            'NeverTwice' => [
                'code' => '<?php
                    /** @return no-return */
                    function other() {
                        throw new Exception();
                    }

                    rand(0,1) ? die() : other();',
            ],
            'ArrayAndTraversableNotIterable' => [
                'code' => '<?php declare(strict_types=1);

                    /** @param mixed $identifier */
                    function isNullIdentifier($identifier): bool
                    {
                        if ($identifier instanceof \Traversable || is_array($identifier)) {
                            expectsTraversableOrArray($identifier);
                        }

                        return false;
                    }

                    /** @param Traversable|array<array-key, mixed> $_a */
                    function expectsTraversableOrArray($_a): void
                    {

                    }
                    ',
            ],
            'emptyStringNumericStringDontCombine' => [
                'code' => '<?php
                    /**
                     * @param numeric-string $arg
                     * @return void
                     */
                    function takesNumeric($arg) {}

                    $b = rand(0, 10);
                    $a = $b < 5 ? "" : (string) $b;
                    if ($a !== "") {
                        takesNumeric($a);
                    }

                    /** @var ""|numeric-string $c */
                    if (is_numeric($c)) {
                        takesNumeric($c);
                    }',
            ],
            'emptyStringNumericStringDontCombineNegation' => [
                'code' => '<?php
                    /**
                     * @param ""|"hello" $arg
                     * @return void
                     */
                    function takesLiteralString($arg) {}

                    /** @var ""|numeric-string $c */
                    if (!is_numeric($c)) {
                        takesLiteralString($c);
                    }',
            ],
            'tooLongLiteralShouldBeNonFalsyString' => [
                'code' => '<?php
                    $x = "aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";',
                'assertions' => [
                    '$x===' => 'non-falsy-string',
                ],
            ],
            'loopNonFalsyWithZeroShouldBeNonEmpty' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     * @return string[]
                     */
                    function getStringArray() {}

                    $x = array();
                    foreach (getStringArray() as $id) {
                        $x[] = "0";
                        $x[] = "some_" . $id;
                    }',
                'assertions' => [
                    '$x===' => 'list<non-empty-string>',
                ],
            ],
            'loopNonLowercaseLiteralWithNonEmptyLowercaseShouldBeNonEmptyAndNotLowercase' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress InvalidReturnType
                     * @return int[]
                     */
                    function getIntArray() {}

                    $x = array();
                    foreach (getIntArray() as $id) {
                        $x[] = "TEXT";
                        $x[] = "some_" . $id;
                    }',
                'assertions' => [
                    '$x===' => 'list<non-empty-string>',
                ],
            ],
            'nonemptyliteralstring' => [
                'code' => '<?php
                    /** @var non-empty-literal-string */
                    $instance = "test";
                    $provider = "test";
                    $key = "";
                    if (random_int(0, 1)) {
                        $key = "{$instance}_{$provider}";
                        /** @psalm-check-type-exact $key=non-empty-literal-string */;
                    }
                    /** @psalm-check-type-exact $key=literal-string */;

                    $_ = $key !== "" ? "test{$key}]" : "test";
                ',
            ],
        ];
    }

    /**
     * @return array<string,array{string,non-empty-list<string>}>
     */
    public function providerTestValidTypeCombination(): array
    {
        return [
            'complexArrayFallback1' => [
                'array{other_references: list<Psalm\Internal\Analyzer\DataFlowNodeData>|null, taint_trace: list<array<array-key, mixed>>|null, ...<string, mixed>}',
                [
                    'array{other_references: list<Psalm\Internal\Analyzer\DataFlowNodeData>|null, taint_trace: null}&array<string, mixed>',
                    'array{other_references: list<Psalm\Internal\Analyzer\DataFlowNodeData>|null, taint_trace: list<array<array-key, mixed>>}&array<string, mixed>',
                ],
            ],
            'complexArrayFallback2' => [
                'list{0?: 0|a, 1?: 0|a, ...<a>}',
                [
                    'list<a>',
                    'list{0, 0}',
                ],
            ],
            'intOrString' => [
                'int|string',
                [
                    'int',
                    'string',
                ],
            ],
            'mixedOrNull' => [
                'mixed|null',
                [
                    'mixed',
                    'null',
                ],
            ],
            'mixedOrNever' => [
                'mixed',
                [
                    'never',
                    'mixed',
                ],
            ],
            'mixedOrObject' => [
                'mixed|object',
                [
                    'mixed',
                    'object',
                ],
            ],
            'mixedOrEmptyArray' => [
                'array<never, never>|mixed',
                [
                    'mixed',
                    'array<never, never>',
                ],
            ],
            'falseTrueToBool' => [
                'bool',
                [
                    'false',
                    'true',
                ],
            ],
            'trueFalseToBool' => [
                'bool',
                [
                    'true',
                    'false',
                ],
            ],
            'trueBoolToBool' => [
                'bool',
                [
                    'true',
                    'bool',
                ],
            ],
            'boolTrueToBool' => [
                'bool',
                [
                    'bool',
                    'true',
                ],
            ],
            'intOrTrueOrFalseToBool' => [
                'bool|int',
                [
                    'int',
                    'false',
                    'true',
                ],
            ],
            'intOrBoolOrTrueToBool' => [
                'bool|int',
                [
                    'int',
                    'bool',
                    'true',
                ],
            ],
            'intOrTrueOrBoolToBool' => [
                'bool|int',
                [
                    'int',
                    'true',
                    'bool',
                ],
            ],
            'arrayOfIntOrString' => [
                'array<array-key, int|string>',
                [
                    'array<int>',
                    'array<string>',
                ],
            ],
            'arrayOfIntOrAlsoString' => [
                'array<array-key, int>|string',
                [
                    'array<int>',
                    'string',
                ],
            ],
            'emptyArrays' => [
                'array<never, never>',
                [
                    'array<never, never>',
                    'array<never, never>',
                ],
            ],
            'arrayStringOrEmptyArray' => [
                'array<array-key, string>',
                [
                    'array<never>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrString' => [
                'array<array-key, mixed|string>',
                [
                    'array<mixed>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrStringKeys' => [
                'array<array-key, string>',
                [
                    'array<int|string,string>',
                    'array<mixed,string>',
                ],
            ],
            'arrayMixedOrEmpty' => [
                'array<array-key, mixed>',
                [
                    'array<never>',
                    'array<mixed>',
                ],
            ],
            'arrayBigCombination' => [
                'array<array-key, float|int|string>',
                [
                    'array<int|float>',
                    'array<string>',
                ],
            ],
            'arrayTraversableToIterable' => [
                'iterable<array-key|mixed, mixed>',
                [
                    'array',
                    'Traversable',
                ],
            ],
            'arrayIterableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'array',
                    'iterable',
                ],
            ],
            'iterableArrayToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'iterable',
                    'array',
                ],
            ],
            'traversableIterableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'Traversable',
                    'iterable',
                ],
            ],
            'iterableTraversableToIterable' => [
                'iterable<mixed, mixed>',
                [
                    'iterable',
                    'Traversable',
                ],
            ],
            'arrayTraversableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'array<int, string>',
                    'Traversable<int, bool>',
                ],
            ],
            'arrayIterableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'array<int, string>',
                    'iterable<int, bool>',
                ],
            ],
            'iterableArrayToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'iterable<int, string>',
                    'array<int, bool>',
                ],
            ],
            'traversableIterableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'Traversable<int, string>',
                    'iterable<int, bool>',
                ],
            ],
            'iterableTraversableToIterableWithParams' => [
                'iterable<int, bool|string>',
                [
                    'iterable<int, string>',
                    'Traversable<int, bool>',
                ],
            ],
            'arrayObjectAndParamsWithEmptyArray' => [
                'ArrayObject<int, string>|array<never, never>',
                [
                    'ArrayObject<int, string>',
                    'array<never, never>',
                ],
            ],
            'emptyArrayWithArrayObjectAndParams' => [
                'ArrayObject<int, string>|array<never, never>',
                [
                    'array<never, never>',
                    'ArrayObject<int, string>',
                ],
            ],
            'emptyArrayAndFalse' => [
                'array<never, never>|false',
                [
                    'array<never, never>',
                    'false',
                ],
            ],
            'emptyArrayAndTrue' => [
                'array<never, never>|true',
                [
                    'array<never, never>',
                    'true',
                ],
            ],
            'emptyArrayWithTrueAndFalse' => [
                'array<never, never>|bool',
                [
                    'array<never, never>',
                    'true',
                    'false',
                ],
            ],
            'falseDestruction' => [
                'bool',
                [
                    'false',
                    'bool',
                ],
            ],
            'onlyFalse' => [
                'false',
                [
                    'false',
                ],
            ],
            'onlyTrue' => [
                'true',
                [
                    'true',
                ],
            ],
            'falseFalseDestruction' => [
                'false',
                [
                    'false',
                    'false',
                ],
            ],
            'aAndAOfB' => [
                'A|A<B>',
                [
                    'A',
                    'A<B>',
                ],
            ],
            'combineObjectType1' => [
                'array{a?: int, b?: string}',
                [
                    'array{a: int}',
                    'array{b: string}',
                ],
            ],
            'combineObjectType2' => [
                'array{a: int|string, b?: string}',
                [
                    'array{a: int}',
                    'array{a: string,b: string}',
                ],
            ],
            'combineObjectTypeWithIntKeyedArray' => [
                "array<'a'|int, int|string>",
                [
                    'array{a: int}',
                    'array<int, string>',
                ],
            ],
            'combineNestedObjectTypeWithTKeyedArrayIntKeyedArray' => [
                "array{a: array<'a'|int, int|string>}",
                [
                    'array{a: array{a: int}}',
                    'array{a: array<int, string>}',
                ],
            ],
            'combineIntKeyedObjectTypeWithNestedIntKeyedArray' => [
                "array<int, array<'a'|int, int|string>>",
                [
                    'array<int, array{a:int}>',
                    'array<int, array<int, string>>',
                ],
            ],
            'combineNestedObjectTypeWithNestedIntKeyedArray' => [
                "array<'a'|int, array<'a'|int, int|string>>",
                [
                    'array{a: array{a: int}}',
                    'array<int, array<int, string>>',
                ],
            ],
            'combinePossiblyUndefinedKeys' => [
                'array{a: bool, b?: mixed, d?: mixed}',
                [
                    'array{a: false, b: mixed}',
                    'array{a: true, d: mixed}',
                    'array{a: true, d: mixed}',
                ],
            ],
            'combinePossiblyUndefinedKeysAndString' => [
                'array{a: string, b?: int}|string',
                [
                    'array{a: string, b?: int}',
                    'string',
                ],
            ],
            'combineMixedArrayWithTKeyedArray' => [
                'array<array-key, mixed>',
                [
                    'array{a: int}',
                    'array',
                ],
            ],
            'traversableAorB' => [
                'Traversable<mixed, A|B>',
                [
                    'Traversable<A>',
                    'Traversable<B>',
                ],
            ],
            'iterableAorB' => [
                'iterable<mixed, A|B>',
                [
                    'iterable<A>',
                    'iterable<B>',
                ],
            ],
            'FooAorB' => [
                'Foo<A>|Foo<B>',
                [
                    'Foo<A>',
                    'Foo<B>',
                ],
            ],
            'traversableOfMixed' => [
                'Traversable<mixed, mixed>',
                [
                    'Traversable',
                    'Traversable<mixed, mixed>',
                ],
            ],
            'traversableAndIterator' => [
                'Traversable&Iterator',
                [
                    'Traversable&Iterator',
                    'Traversable&Iterator',
                ],
            ],
            'traversableOfMixedAndIterator' => [
                'Traversable<mixed, mixed>&Iterator',
                [
                    'Traversable<mixed, mixed>&Iterator',
                    'Traversable<mixed, mixed>&Iterator',
                ],
            ],
            'objectLikePlusArrayEqualsArray' => [
                "array<'a'|'b'|'c', 1|2|3>",
                [
                    'array<"a"|"b"|"c", 1|2|3>',
                    'array{a: 1|2, b: 2|3, c: 1|3}',
                ],
            ],
            'combineClosures' => [
                'Closure(A):void|Closure(B):void',
                [
                    'Closure(A):void',
                    'Closure(B):void',
                ],
            ],
            'combineClassStringWithString' => [
                'string',
                [
                    'class-string',
                    'string',
                ],
            ],
            'combineClassStringWithFalse' => [
                'class-string|false',
                [
                    'class-string',
                    'false',
                ],
            ],
            'combineRefinedClassStringWithString' => [
                'string',
                [
                    'class-string<Exception>',
                    'string',
                ],
            ],
            'combineRefinedClassStrings' => [
                'class-string<Exception>|class-string<Iterator>',
                [
                    'class-string<Exception>',
                    'class-string<Iterator>',
                ],
            ],
            'combineClassStringsWithLiteral' => [
                'class-string',
                [
                    'class-string',
                    'Exception::class',
                ],
            ],
            'combineClassStringWithNumericString' => [
                'class-string|numeric-string',
                [
                    'class-string',
                    'numeric-string',
                ],
            ],
            'combineRefinedClassStringWithNumericString' => [
                'class-string<Exception>|numeric-string',
                [
                    'class-string<Exception>',
                    'numeric-string',
                ],
            ],
            'combineClassStringWithTraitString' => [
                'class-string|trait-string',
                [
                    'class-string',
                    'trait-string',
                ],
            ],
            'combineRefinedClassStringWithTraitString' => [
                'class-string<Exception>|trait-string',
                [
                    'class-string<Exception>',
                    'trait-string',
                ],
            ],
            'combineCallableAndCallableString' => [
                'callable',
                [
                    'callable',
                    'callable-string',
                ],
            ],
            'combineCallableStringAndCallable' => [
                'callable',
                [
                    'callable-string',
                    'callable',
                ],
            ],
            'combineCallableAndCallableObject' => [
                'callable',
                [
                    'callable',
                    'callable-object',
                ],
            ],
            'combineCallableObjectAndCallable' => [
                'callable',
                [
                    'callable-object',
                    'callable',
                ],
            ],
            'combineCallableAndCallableArray' => [
                'callable',
                [
                    'callable',
                    'callable-array',
                ],
            ],
            'combineCallableArrayAndCallable' => [
                'callable',
                [
                    'callable-array',
                    'callable',
                ],
            ],
            'combineCallableAndCallableList' => [
                'callable',
                [
                    'callable',
                    'callable-list',
                ],
            ],
            'combineCallableListAndCallable' => [
                'callable',
                [
                    'callable-list',
                    'callable',
                ],
            ],
            'combineCallableArrayAndArray' => [
                'array<array-key, mixed>',
                [
                    'callable-array{class-string, string}',
                    'array',
                ],
            ],
            'combineGenericArrayAndMixedArray' => [
                'array<array-key, int|mixed>',
                [
                    'array<string, int>',
                    'array<array-key, mixed>',
                ],
            ],
            'combineTKeyedArrayAndArray' => [
                'array<array-key, mixed>',
                [
                    'array{hello: int}',
                    'array<array-key, mixed>',
                ],
            ],
            'combineTKeyedArrayAndNestedArray' => [
                'array<array-key, mixed>',
                [
                    'array{hello: array{goodbye: int}}',
                    'array<array-key, mixed>',
                ],
            ],
            'combineNumericStringWithLiteralString' => [
                'numeric-string',
                [
                    'numeric-string',
                    '"1"',
                ],
            ],
            'combineLiteralStringWithNumericString' => [
                'numeric-string',
                [
                    '"1"',
                    'numeric-string',
                ],
            ],
            'combineNonEmptyListWithTKeyedArrayList' => [
                'list{null|string, ...<string>}',
                [
                    'non-empty-list<string>',
                    'array{null}',
                ],
            ],
            'combineZeroAndPositiveInt' => [
                'int<0, max>',
                [
                    '0',
                    'positive-int',
                ],
            ],
            'combinePositiveIntAndZero' => [
                'int<0, max>',
                [
                    'positive-int',
                    '0',
                ],
            ],
            'combinePositiveIntAndMinusOne' => [
                'int<-1, max>',
                [
                    'positive-int',
                    '-1',
                ],
            ],
            'combinePositiveIntZeroAndMinusOne' => [
                'int<-1, max>',
                [
                    '0',
                    'positive-int',
                    '-1',
                ],
            ],
            'combineMinusOneAndPositiveInt' => [
                'int<-1, max>',
                [
                    '-1',
                    'positive-int',
                ],
            ],
            'combineZeroMinusOneAndPositiveInt' => [
                'int<-1, max>',
                [
                    '0',
                    '-1',
                    'positive-int',
                ],
            ],
            'combineZeroOneAndPositiveInt' => [
                'int<0, max>',
                [
                    '0',
                    '1',
                    'positive-int',
                ],
            ],
            'combinePositiveIntOneAndZero' => [
                'int<0, max>',
                [
                    'positive-int',
                    '1',
                    '0',
                ],
            ],
            'combinePositiveInts' => [
                'int<1, max>',
                [
                    'positive-int',
                    'positive-int',
                ],
            ],
            'combineNonEmptyArrayAndKeyedArray' => [
                'array<int, int>',
                [
                    'non-empty-array<int, int>',
                    'array{0?:int}',
                ],
            ],
            'combineNonEmptyStringAndLiteral' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    '"foo"',
                ],
            ],
            'combineLiteralAndNonEmptyString' => [
                'non-empty-string',
                [
                    '"foo"',
                    'non-empty-string',
                ],
            ],
            'combineTruthyStringAndNonEmptyString' => [
                'non-empty-string',
                [
                    'truthy-string',
                    'non-empty-string',
                ],
            ],
            'combineNonFalsyNonEmptyString' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    'non-empty-string',
                ],
            ],
            'combineNonEmptyNonFalsyString' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    'non-falsy-string',
                ],
            ],
            'combineNonEmptyStringAndNumericString' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    'numeric-string',
                ],
            ],
            'combineNumericStringAndNonEmptyString' => [
                'non-empty-string',
                [
                    'numeric-string',
                    'non-empty-string',
                ],
            ],
            'combineNonEmptyLowercaseAndNonFalsyString' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    'non-empty-lowercase-string',
                ],
            ],
            'combineNonEmptyAndEmptyScalar' => [
                'scalar',
                [
                    'non-empty-scalar',
                    'empty-scalar',
                ],
            ],
            'combineLiteralStringAndNonspecificLiteral' => [
                'literal-string',
                [
                    'literal-string',
                    '"foo"',
                ],
            ],
            'combineNonspecificLiteralAndLiteralString' => [
                'literal-string',
                [
                    '"foo"',
                    'literal-string',
                ],
            ],
            'combineLiteralIntAndNonspecificLiteral' => [
                'literal-int',
                [
                    'literal-int',
                    '5',
                ],
            ],
            'combineNonspecificLiteralAndLiteralInt' => [
                'literal-int',
                [
                    '5',
                    'literal-int',
                ],
            ],
            'combineNonspecificLiteralAndPositiveInt' => [
                'int',
                [
                    'positive-int',
                    'literal-int',
                ],
            ],
            'combinePositiveAndLiteralInt' => [
                'int',
                [
                    'literal-int',
                    'positive-int',
                ],
            ],
            'combineNonEmptyStringAndNonEmptyNonSpecificLiteralString' => [
                'non-empty-string',
                [
                    'non-empty-literal-string',
                    'non-empty-string',
                ],
            ],
            'combineNonEmptyNonSpecificLiteralStringAndNonEmptyString' => [
                'non-empty-string',
                [
                    'non-empty-string',
                    'non-empty-literal-string',
                ],
            ],
            'nonFalsyStringAndFalsyLiteral' => [
                'non-empty-string',
                [
                    'non-falsy-string',
                    '"0"',
                ],
            ],
            'unionOfClassStringAndClassStringWithIntersection' => [
                'class-string<IFoo>',
                [
                    'class-string<IFoo>',
                    'class-string<IFoo & IBar>',
                ],
            ],
            'unionNonEmptyLiteralStringAndLiteralString' => [
                'literal-string',
                [
                    'non-empty-literal-string',
                    'literal-string',
                ],
            ],
            'unionLiteralStringAndNonEmptyLiteralString' => [
                'literal-string',
                [
                    'literal-string',
                    'non-empty-literal-string',
                ],
            ],
        ];
    }

    private static function getAtomic(string $string): Atomic
    {
        return Type::parseString($string)->getSingleAtomic();
    }
}
