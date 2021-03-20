<?php
namespace Psalm\Tests;

class MatchTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
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
            'getClassWithMethod' => [
                '<?php
                    interface Foo {}

                    class Bar implements Foo
                    {
                        public function hello(): string
                        {
                            return "a";
                        }
                    }

                    function foo(Foo $value): string {
                        return match (get_class($value)) {
                            Bar::class => $value->hello(),
                            default => "b",
                        };
                    }',
                [],
                [],
                '8.0'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
            'notAllEnumsMet' => [
                '<?php
                    /**
                     * @param "foo"|"bar" $foo
                     */
                    function foo(string $foo): string {
                        return match ($foo) {
                            "foo" => "foo",
                        };
                    }',
                'error_message' => 'UnhandledMatchCondition',
                [],
                false,
                '8.0',
            ],
            'notAllConstEnumsMet' => [
                '<?php
                    class Airport {
                        const JFK = "jfk";
                        const LHR = "lhr";
                        const LGA = "lga";

                        /**
                         * @param self::* $airport
                         */
                        public static function getName(string $airport): string {
                            return match ($airport) {
                                self::JFK => "John F Kennedy Airport",
                                self::LHR => "London Heathrow",
                            };
                        }
                    }',
                'error_message' => 'UnhandledMatchCondition',
                [],
                false,
                '8.0',
            ],
            'paradoxWithDuplicateValue' => [
                '<?php
                    function foo(int $i) : void {
                        echo match ($i) {
                            1 => 0,
                            1 => 1,
                        };
                    };',
                'error_message' => 'ParadoxicalCondition',
                [],
                false,
                '8.0',
            ],
            'noCrashWithEmptyMatch' => [
                '<?php
                    function foo(int $i) {
                        match ($i) {

                        };
                    }',
                'error_message' => 'UnhandledMatchCondition',
                [],
                false,
                '8.0',
            ],
            'exitIsLikeThrow' => [
                '<?php
                    /**
                     * @param 1|2|3 $i
                     */
                    function foo(int $i): void {
                        $a = match ($i) {
                            1 => exit(),
                            2, 3 => $i,
                        };
                        $a === "aaa";
                    }',
                'error_message' => 'DocblockTypeContradiction',
                [],
                false,
                '8.0',
            ],
            'matchTrueImpossible' => [
                '<?php
                    $foo = new \stdClass();
                    $a = match (true) {
                        $foo instanceof \stdClass => 1,
                        $foo instanceof \Exception => 1,
                    };',
                'error_message' => 'TypeDoesNotContainType',
                [],
                false,
                '8.0',
            ],
        ];
    }
}
