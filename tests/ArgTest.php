<?php
namespace Psalm\Tests;

class ArgTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'callMapClassOptionalArg' => [
                '<?php
                    $m = new ReflectionMethod("hello", "goodbye");
                    $m->invoke(null, "cool");',
            ],
            'sortFunctions' => [
                '<?php
                    $a = ["b" => 5, "a" => 8];
                    ksort($a);
                    $b = ["b" => 5, "a" => 8];
                    sort($b);
                ',
                'assertions' => [
                    '$a' => 'array{a: int, b: int}',
                    '$b' => 'list<int>',
                ],
            ],
            'arrayModificationFunctions' => [
                '<?php
                    $a = ["b" => 5, "a" => 8];
                    array_unshift($a, (bool)rand(0, 1));
                    $b = ["b" => 5, "a" => 8];
                    array_push($b, (bool)rand(0, 1));
                ',
                'assertions' => [
                    '$a' => 'non-empty-array<int|string, bool|int>',
                    '$b' => 'non-empty-array<int|string, bool|int>',
                ],
            ],
            'byRefArgAssignment' => [
                '<?php
                    $a = ["hello", "goodbye"];
                    shuffle($a);
                    $a = [0, 1];',
            ],
            'correctOrderValidation' => [
                '<?php
                    function getString(int $i) : string {
                        return rand(0, 1) ? "hello" : "";
                    }

                    function takesInt(int $i) : void {}

                    $i = rand(0, 10);

                    if (!($i = getString($i))) {}',
            ],
            'allowNullInObjectUnion' => [
                '<?php
                    /**
                     * @param string|null|object $b
                     */
                    function foo($b) : void {}
                    foo(null);',
            ],
            'allowArrayIntScalarForArrayStringWithScalarIgnored' => [
                '<?php
                    /** @param array<int|string> $arr */
                    function foo(array $arr) : void {
                    }

                    /** @return array<int, scalar> */
                    function bar() : array {
                      return [];
                    }

                    /** @psalm-suppress InvalidScalarArgument */
                    foo(bar());',
            ],
            'allowArrayScalarForArrayStringWithScalarIgnored' => [
                '<?php declare(strict_types=1);
                    /** @param array<string> $arr */
                    function foo(array $arr) : void {}

                    /** @return array<int, scalar> */
                    function bar() : array {
                        return [];
                    }

                    /** @psalm-suppress InvalidScalarArgument */
                    foo(bar());',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'possiblyInvalidArgument' => [
                '<?php
                    $foo = [
                        "a",
                        ["b"],
                    ];

                    $a = array_map(
                        function (string $uuid): string {
                            return $uuid;
                        },
                        $foo[rand(0, 1)]
                    );',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'possiblyInvalidArgumentWithOverlap' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}

                    $foo = rand(0, 1) ? new A : new B;

                    /** @param B|C $b */
                    function bar($b) : void {}

                    bar($foo);',
                'error_message' => 'PossiblyInvalidArgument',
            ],
        ];
    }
}
