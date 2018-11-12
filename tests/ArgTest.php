<?php
namespace Psalm\Tests;

class ArgTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
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
                    '$a' => 'array{b:int, a:int}',
                    '$b' => 'array<int, int>',
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
                    '$a' => 'array<string|int, int|bool>',
                    '$b' => 'array<string|int, int|bool>',
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
        ];
    }

    /**
     * @return array
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
