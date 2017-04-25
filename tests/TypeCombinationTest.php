<?php
namespace Psalm\Tests;

use Psalm\Type;

class TypeCombinationTest extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @dataProvider providerTestValidTypeCombination
     * @param string $expected
     * @param array<string> $types
     * @return void
     */
    public function testValidTypeCombination($expected, $types)
    {
        foreach ($types as $k => $type) {
            $types[$k] = self::getAtomic($type);
        }

        $this->assertEquals(
            $expected,
            (string) Type::combineTypes($types)
        );
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'multipleValuedArray' => [
                '<?php
                    class A {}
                    class B {}
                    $var = [];
                    $var[] = new A();
                    $var[] = new B();'
            ]
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
                    'string'
                ]
            ],
            'arrayOfIntOrString' => [
                'array<mixed, int|string>',
                [
                    'array<int>',
                    'array<string>'
                ]
            ],
            'arrayOfIntOrAlsoString' => [
                'array<mixed, int>|string',
                [
                    'array<int>',
                    'string'
                ]
            ],
            'emptyArrays' => [
                'array<empty, empty>',
                [
                    'array<empty,empty>',
                    'array<empty,empty>'
                ]
            ],
            'arrayStringOrEmptyArray' => [
                'array<mixed, string>',
                [
                    'array<empty>',
                    'array<string>'
                ]
            ],
            'arrayMixedOrString' => [
                'array<mixed, mixed>',
                [
                    'array<mixed>',
                    'array<string>'
                ]
            ],
            'arrayMixedOrStringKeys' => [
                'array<mixed, string>',
                [
                    'array<int|string,string>',
                    'array<mixed,string>'
                ]
            ],
            'arrayMixedOrEmpty' => [
                'array<mixed, mixed>',
                [
                    'array<empty>',
                    'array<mixed>'
                ]
            ],
            'arrayBigCombination' => [
                'array<mixed, int|float|string>',
                [
                    'array<int|float>',
                    'array<string>'
                ]
            ],
            'falseDestruction' => [
                'bool',
                [
                    'false',
                    'bool'
                ]
            ],
            'onlyFalse' => [
                'bool',
                [
                    'false'
                ]
            ],
            'falseFalseDestruction' => [
                'bool',
                [
                    'false',
                    'false'
                ]
            ],
            'aAndAOfB' => [
                'A<mixed>',
                [
                    'A',
                    'A<B>'
                ]
            ],
            'combineObjectType1' => [
                'array{a:int, b:string}',
                [
                    'array{a:int}',
                    'array{b:string}'
                ]
            ],
            'combineObjectType2' => [
                'array{a:int|string, b:string}',
                [
                    'array{a:int}',
                    'array{a:string,b:string}'
                ]
            ]
        ];
    }

    /**
     * @param  string $string
     * @return Type\Atomic
     */
    private static function getAtomic($string)
    {
        return array_values(Type::parseString($string)->types)[0];
    }
}
