<?php
namespace Psalm\Tests;

class ClosureTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'byRefUseVar' => [
                '<?php
                    /** @return void */
                    function run_function(\Closure $fnc) {
                        $fnc();
                    }

                    // here we have to make sure $data exists as a side-effect of calling `run_function`
                    // because it could exist depending on how run_function is implemented
                    /**
                     * @return void
                     * @psalm-suppress MixedArgument
                     */
                    function fn() {
                        run_function(
                            /**
                             * @return void
                             */
                            function() use(&$data) {
                                $data = 1;
                            }
                        );
                        echo $data;
                    }

                    fn();',
            ],
            'inferredArg' => [
                '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        /**
                         * @psalm-suppress MissingClosureReturnType
                         */
                        function(string $a) {
                            return $a . "blah";
                        },
                        $bar
                    );',
            ],
            'varReturnType' => [
                '<?php
                    $add_one = function(int $a) : int {
                        return $a + 1;
                    };

                    $a = $add_one(1);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'callableToClosure' => [
                '<?php
                    /**
                     * @return callable
                     */
                    function foo() {
                        return function(string $a) : string {
                            return $a . "blah";
                        };
                    }',
            ],
            'callable' => [
                '<?php
                    function foo(callable $c) : void {
                        echo (string)$c();
                    }',
            ],
            'callableClass' => [
                '<?php
                    class C {
                        public function __invoke() : string {
                            return "You ran?";
                        }
                    }

                    function foo(callable $c) : void {
                        echo (string)$c();
                    }

                    foo(new C());

                    $c2 = new C();
                    $c2();',
            ],
            'correctParamType' => [
                '<?php
                    $take_string = function(string $s) : string { return $s; };
                    $take_string("string");',
            ],
            'callableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a) : string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c) : void {}

                    foo("A::bar");
                    foo(["A", "bar"]);
                    foo([A::class, "bar"]);
                    $a = new A();
                    foo([$a, "bar"]);',
            ],
            'arrayMapCallableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a) : string {
                            return $a . "b";
                        }
                    }

                    function baz(string $a) : string {
                        return $a . "b";
                    }

                    $a = array_map("A::bar", ["one", "two"]);
                    $b = array_map(["A", "bar"], ["one", "two"]);
                    $c = array_map([A::class, "bar"], ["one", "two"]);
                    $d = array_map([new A(), "bar"], ["one", "two"]);
                    $a_instance = new A();
                    $e = array_map([$a_instance, "bar"], ["one", "two"]);
                    $f = array_map("baz", ["one", "two"]);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                    '$b' => 'array<int, string>',
                    '$c' => 'array<int, string>',
                    '$d' => 'array<int, string>',
                    '$e' => 'array<int, string>',
                    '$f' => 'array<int, string>',
                ],
            ],
            'arrayCallableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a) : string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c) : void {}

                    foo(["A", "bar"]);',
            ],
            'callableFunction' => [
                '<?php
                    function foo(callable $c) : void {}

                    foo("trim");',
            ],
            'inlineCallableFunction' => [
                '<?php
                    class A {
                        function bar() : void {
                            function foobar(int $a, int $b) : int {
                                return $a > $b ? 1 : 0;
                            }

                            $arr = [5, 4, 3, 1, 2];

                            usort($arr, "fooBar");
                        }
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'wrongArg' => [
                '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        function(int $a) : int {
                            return $a + 1;
                        },
                        $bar
                    );',
                'error_message' => 'InvalidScalarArgument',
            ],
            'noReturn' => [
                '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        function(string $a) : string {
                        },
                        $bar
                    );',
                'error_message' => 'InvalidReturnType',
            ],
            'undefinedCallableClass' => [
                '<?php
                    class A {
                        public function getFoo() : Foo
                        {
                            return new Foo([]);
                        }

                        public function bar($argOne, $argTwo)
                        {
                            $this->getFoo()($argOne, $argTwo);
                        }
                    }',
                'error_message' => 'InvalidFunctionCall',
                'error_levels' => ['UndefinedClass'],
            ],
            'undefinedCallableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a) : string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c) : void {}

                    foo("A::barr");',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedCallableMethodClass' => [
                '<?php
                    class A {
                        public static function bar(string $a) : string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c) : void {}

                    foo("B::bar");',
                'error_message' => 'UndefinedClass',
            ],
            'undefinedCallableFunction' => [
                '<?php
                    function foo(callable $c) : void {}

                    foo("trime");',
                'error_message' => 'UndefinedFunction',
            ],
            'possiblyNullFunctionCall' => [
                '<?php
                    /**
                     * @var Closure|null $foo
                     */
                    $foo = null;

                    $foo = function ($bar) use (&$foo) : string
                    {
                        if (is_array($bar)) {
                            return $foo($bar);
                        }

                        return $bar;
                    };',
                'error_message' => 'PossiblyNullFunctionCall',
            ],
            'stringFunctionCall' => [
                '<?php
                    $bad_one = "hello";
                    $a = $bad_one(1);',
                'error_message' => 'InvalidFunctionCall',
            ],
            'wrongParamType' => [
                '<?php
                    $take_string = function(string $s) : string { return $s; };
                    $take_string(42);',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
