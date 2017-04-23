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
            'multiple-valued-array' => [
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
            'int-or-string' => [
                'int|string',
                [
                    'int',
                    'string'
                ]
            ],
            'array-of-int-or-string' => [
                'array<mixed, int|string>',
                [
                    'array<int>',
                    'array<string>'
                ]
            ],
            'array-of-int-or-also-string' => [
                'array<mixed, int>|string',
                [
                    'array<int>',
                    'string'
                ]
            ],
            'empty-arrays' => [
                'array<empty, empty>',
                [
                    'array<empty,empty>',
                    'array<empty,empty>'
                ]
            ],
            'array-string-or-empty-array' => [
                'array<mixed, string>',
                [
                    'array<empty>',
                    'array<string>'
                ]
            ],
            'array-mixed-or-string' => [
                'array<mixed, mixed>',
                [
                    'array<mixed>',
                    'array<string>'
                ]
            ],
            'array-mixed-or-string-keys' => [
                'array<mixed, string>',
                [
                    'array<int|string,string>',
                    'array<mixed,string>'
                ]
            ],
            'array-mixed-or-empty' => [
                'array<mixed, mixed>',
                [
                    'array<empty>',
                    'array<mixed>'
                ]
            ],
            'array-big-combination' => [
                'array<mixed, int|float|string>',
                [
                    'array<int|float>',
                    'array<string>'
                ]
            ],
            'false-destruction' => [
                'bool',
                [
                    'false',
                    'bool'
                ]
            ],
            'only-false' => [
                'bool',
                [
                    'false'
                ]
            ],
            'false-false-destruction' => [
                'bool',
                [
                    'false',
                    'false'
                ]
            ],
            'a-and-a-of-b' => [
                'A<mixed>',
                [
                    'A',
                    'A<B>'
                ]
            ],
            'combine-object-type-1' => [
                'array{a:int, b:string}',
                [
                    'array{a:int}',
                    'array{b:string}'
                ]
            ],
            'combine-object-type-2' => [
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
