<?php
namespace Psalm\Tests;

use function array_values;
use Psalm\Internal\Type\TypeCombination;
use Psalm\Type;

class TypeCombinationTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @dataProvider providerTestValidTypeCombination
     *
     * @param string $expected
     * @param non-empty-list<string> $types
     *
     * @return void
     */
    public function testValidTypeCombination($expected, $types)
    {
        $converted_types = [];

        foreach ($types as $type) {
            $converted_type = self::getAtomic($type);
            $converted_type->from_docblock = true;
            $converted_types[] = $converted_type;
        }

        $this->assertSame(
            $expected,
            TypeCombination::combineTypes($converted_types)->getId()
        );
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'multipleValuedArray' => [
                '<?php
                    class A {}
                    class B {}
                    $var = [];
                    $var[] = new A();
                    $var[] = new B();',
            ],
            'preventLiteralAndClassString' => [
                '<?php
                    /**
                     * @param "array"|class-string $type_name
                     */
                    function foo(string $type_name) : bool {
                        return $type_name === "array";
                    }',
            ],
        ];
    }

    /**
     * @return array<string,array{string,non-empty-list<string>}>
     */
    public function providerTestValidTypeCombination()
    {
        return [
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
            'mixedOrEmpty' => [
                'mixed',
                [
                    'empty',
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
                'array<empty, empty>|mixed',
                [
                    'mixed',
                    'array<empty, empty>',
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
                'array<empty, empty>',
                [
                    'array<empty,empty>',
                    'array<empty,empty>',
                ],
            ],
            'arrayStringOrEmptyArray' => [
                'array<array-key, string>',
                [
                    'array<empty>',
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
                    'array<empty>',
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
                'ArrayObject<int, string>|array<empty, empty>',
                [
                    'ArrayObject<int, string>',
                    'array<empty, empty>',
                ],
            ],
            'emptyArrayWithArrayObjectAndParams' => [
                'ArrayObject<int, string>|array<empty, empty>',
                [
                    'array<empty, empty>',
                    'ArrayObject<int, string>',
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
                'array<int|string, int|string>',
                [
                    'array{a: int}',
                    'array<int, string>',
                ],
            ],
            'combineNestedObjectTypeWithObjectLikeIntKeyedArray' => [
                'array{a: array<int|string, int|string>}',
                [
                    'array{a: array{a: int}}',
                    'array{a: array<int, string>}',
                ],
            ],
            'combineIntKeyedObjectTypeWithNestedIntKeyedArray' => [
                'array<int, array<int|string, int|string>>',
                [
                    'array<int, array{a:int}>',
                    'array<int, array<int, string>>',
                ],
            ],
            'combineNestedObjectTypeWithNestedIntKeyedArray' => [
                'array<int|string, array<int|string, int|string>>',
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
            'combineMixedArrayWithObjectLike' => [
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
                'array<string, int(1)|int(2)|int(3)>',
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
                    'callable'
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
                    'callable'
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
                    'callable'
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
            'combineObjectLikeArrayAndArray' => [
                'array<array-key, mixed>',
                [
                    'array{hello: int}',
                    'array<array-key, mixed>',
                ],
            ],
            'combineObjectLikeArrayAndNestedArray' => [
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
            'combineNonEmptyListWithObjectLikeList' => [
                'array{0: null|string}<int, string>',
                [
                    'non-empty-list<string>',
                    'array{null}'
                ],
            ],
        ];
    }

    /**
     * @param  string $string
     *
     * @return Type\Atomic
     */
    private static function getAtomic($string)
    {
        return array_values(Type::parseString($string)->getAtomicTypes())[0];
    }
}
