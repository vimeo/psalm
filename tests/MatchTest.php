<?php
namespace Psalm\Tests;

class MatchTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'switchTruthy' => [
                '<?php
                    class A {
                       public ?string $a = null;
                       public ?string $b = null;
                    }

                    function f(A $obj): string {
                        return match (true) {
                            $obj->a !== null => $obj->a,
                            $obj->b !== null => $obj->b,
                            default => throw new \InvalidArgumentException("$obj->a or $obj->b must be set"),
                        };
                    }',
                [],
                [],
                '8.0'
            ],
            'defaultAboveCase' => [
                '<?php
                    function foo(string $a) : string {
                        return match ($a) {
                            "a" => "hello",
                            default => "yellow",
                            "b" => "goodbye",
                        };
                    }',
                [],
                [],
                '8.0'
            ],
            'allMatchedNoRedundantCondition' => [
                '<?php
                    function foo() : string {
                        $a = rand(0, 1) ? "a" : "b";
                        return match ($a) {
                            "a" => "hello",
                            "b" => "goodbye",
                        };
                    }',
                [],
                [],
                '8.0'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'getClassArgWrongClass' => [
                '<?php
                    class A {}

                    class B {}

                    $a = rand(0, 10) ? new A(): new B();

                    $a = match (get_class($a)) {
                        A::class => $a->barBar(),
                    };',
                'error_message' => 'UndefinedMethod',
                [],
                false,
                '8.0'
            ],
            'getClassMissingClass' => [
                '<?php
                    class A {}
                    class B {}

                    $a = rand(0, 10) ? new A(): new B();

                    $a = match (get_class($a)) {
                        C::class => 5,
                    };',
                'error_message' => 'UndefinedClass',
                [],
                false,
                '8.0'
            ],
            'allMatchedDefaultImpossible' => [
                '<?php
                    function foo() : string {
                        $a = rand(0, 1) ? "a" : "b";
                        return match ($a) {
                            "a" => "hello",
                            "b" => "goodbye",
                            default => "impossible",
                        };
                    }',
                'error_message' => 'TypeDoesNotContainType',
                [],
                false,
                '8.0'
            ],
            'allMatchedAnotherImpossible' => [
                '<?php
                    function foo() : string {
                        $a = rand(0, 1) ? "a" : "b";
                        return match ($a) {
                            "a" => "hello",
                            "b" => "goodbye",
                            "c" => "impossible",
                        };
                    }',
                'error_message' => 'TypeDoesNotContainType',
                [],
                false,
                '8.0'
            ],
        ];
    }
}
