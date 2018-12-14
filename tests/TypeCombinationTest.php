<?php
namespace Psalm\Tests;

use Psalm\Type;
use Psalm\Internal\Type\TypeCombination;

class TypeCombinationTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @dataProvider providerTestValidTypeCombination
     *
     * @param string $expected
     * @param array<int, string> $types
     *
     * @return void
     */
    public function testValidTypeCombination($expected, $types)
    {
        foreach ($types as $k => $type) {
            $types[$k] = self::getAtomic($type);
        }

        /** @psalm-suppress InvalidArgument */
        $this->assertSame(
            $expected,
            (string) TypeCombination::combineTypes($types)
        );
    }

    /**
     * @return array
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
        ];
    }

    /**
     * @return array
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
                'int|bool',
                [
                    'int',
                    'false',
                    'true',
                ],
            ],
            'intOrBoolOrTrueToBool' => [
                'int|bool',
                [
                    'int',
                    'bool',
                    'true',
                ],
            ],
            'intOrTrueOrBoolToBool' => [
                'int|bool',
                [
                    'int',
                    'true',
                    'bool',
                ],
            ],
            'arrayOfIntOrString' => [
                'array<mixed, int|string>',
                [
                    'array<int>',
                    'array<string>',
                ],
            ],
            'arrayOfIntOrAlsoString' => [
                'array<mixed, int>|string',
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
                'array<mixed, string>',
                [
                    'array<empty>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrString' => [
                'array<mixed, mixed|string>',
                [
                    'array<mixed>',
                    'array<string>',
                ],
            ],
            'arrayMixedOrStringKeys' => [
                'array<int|string|mixed, string>',
                [
                    'array<int|string,string>',
                    'array<mixed,string>',
                ],
            ],
            'arrayMixedOrEmpty' => [
                'array<mixed, mixed>',
                [
                    'array<empty>',
                    'array<mixed>',
                ],
            ],
            'arrayBigCombination' => [
                'array<mixed, int|float|string>',
                [
                    'array<int|float>',
                    'array<string>',
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
                'A',
                [
                    'A',
                    'A<B>',
                ],
            ],
            'combineObjectType1' => [
                'array{a?:int, b?:string}',
                [
                    'array{a:int}',
                    'array{b:string}',
                ],
            ],
            'combineObjectType2' => [
                'array{a:int|string, b?:string}',
                [
                    'array{a:int}',
                    'array{a:string,b:string}',
                ],
            ],
            'combineObjectTypeWithIntKeyedArray' => [
                'array<int|string, string|int>',
                [
                    'array{a:int}',
                    'array<int, string>',
                ],
            ],
            'combineNestedObjectTypeWithObjectLikeIntKeyedArray' => [
                'array{a:array<int|string, string|int>}',
                [
                    'array{a:array{a:int}}',
                    'array{a:array<int, string>}',
                ],
            ],
            'combineIntKeyedObjectTypeWithNestedIntKeyedArray' => [
                'array<int, array<int|string, string|int>>',
                [
                    'array<int, array{a:int}>',
                    'array<int, array<int, string>>',
                ],
            ],
            'combineNestedObjectTypeWithNestedIntKeyedArray' => [
                'array<int|string, array<int|string, string|int>>',
                [
                    'array{a:array{a:int}}',
                    'array<int, array<int, string>>',
                ],
            ],
            'combinePossiblyUndefinedKeys' => [
                'array{a:bool, b?:mixed, d?:mixed}',
                [
                    'array{a:false, b:mixed}',
                    'array{a:true, d:mixed}',
                    'array{a:true, d:mixed}',
                ],
            ],
            'combinePossiblyUndefinedKeysAndString' => [
                'array{a:string, b?:int}|string',
                [
                    'array{a:string, b?:int}',
                    'string',
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
        return array_values(Type::parseString($string)->getTypes())[0];
    }
}
