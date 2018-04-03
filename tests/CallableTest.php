<?php
namespace Psalm\Tests;

class CallableTest extends TestCase
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
                    $add_one = function(int $a): int {
                        return $a + 1;
                    };

                    $a = $add_one(1);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'varCallableParamReturnType' => [
                '<?php
                    $add_one = function(int $a): int {
                        return $a + 1;
                    };

                    /**
                     * @param  callable(int) : int $c
                     */
                    function bar(callable $c) : int {
                        return $c(1);
                    }

                    bar($add_one);',
            ],
            'callableToClosure' => [
                '<?php
                    /**
                     * @return callable
                     */
                    function foo() {
                        return function(string $a): string {
                            return $a . "blah";
                        };
                    }',
            ],
            'callable' => [
                '<?php
                    function foo(callable $c): void {
                        echo (string)$c();
                    }',
            ],
            'callableClass' => [
                '<?php
                    class C {
                        public function __invoke(): string {
                            return "You ran?";
                        }
                    }

                    function foo(callable $c): void {
                        echo (string)$c();
                    }

                    foo(new C());

                    $c2 = new C();
                    $c2();',
            ],
            'correctParamType' => [
                '<?php
                    $take_string = function(string $s): string { return $s; };
                    $take_string("string");',
            ],
            'callableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo("A::bar");
                    foo(["A", "bar"]);
                    foo([A::class, "bar"]);
                    $a = new A();
                    foo([$a, "bar"]);',
            ],
            'arrayMapCallableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function baz(string $a): string {
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
                    '$a' => 'array{0:string, 1:string}',
                    '$b' => 'array{0:string, 1:string}',
                    '$c' => 'array{0:string, 1:string}',
                    '$d' => 'array{0:string, 1:string}',
                    '$e' => 'array{0:string, 1:string}',
                    '$f' => 'array{0:string, 1:string}',
                ],
            ],
            'arrayCallableMethod' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(["A", "bar"]);',
            ],
            'callableFunction' => [
                '<?php
                    function foo(callable $c): void {}

                    foo("trim");',
            ],
            'inlineCallableFunction' => [
                '<?php
                    class A {
                        function bar(): void {
                            function foobar(int $a, int $b): int {
                                return $a > $b ? 1 : 0;
                            }

                            $arr = [5, 4, 3, 1, 2];

                            usort($arr, "fooBar");
                        }
                    }',
            ],
            'closureSelf' => [
                '<?php
                    class A
                    {
                        /**
                         * @var self[]
                         */
                        private $subitems;

                        /**
                         * @param self[] $in
                         */
                        public function __construct(array $in = [])
                        {
                            array_map(function(self $i): self { return $i; }, $in);

                            $this->subitems = array_map(
                              function(self $i): self {
                                return $i;
                              },
                              $in
                            );
                        }
                    }

                    new A([new A, new A]);',
            ],
            'possiblyUndefinedFunction' => [
                '<?php
                      /**
                       * @param string|callable $middlewareOrPath
                       */
                      function pipe($middlewareOrPath, ?callable $middleware = null): void {  }

                    pipe("zzzz", function() : void {});',
            ],
            'callableWithNonInvokable' => [
                '<?php
                    function asd(): void {}
                    class B {}

                    /**
                     * @param callable|B $p
                     */
                    function passes($p): void {}

                    passes("asd");',
            ],
            'callableWithInvokable' => [
                '<?php
                    function asd(): void {}
                    class A { public function __invoke(): void {} }

                    /**
                     * @param callable|A $p
                     */
                    function fails($p): void {}

                    fails("asd");',
            ],
            'isCallableArray' => [
                '<?php
                    class A
                    {
                        public function callMeMaybe(string $method): void
                        {
                            $handleMethod = [$this, $method];

                            if (is_callable($handleMethod)) {
                                $handleMethod();
                            }
                        }

                        public function foo(): void {}
                    }
                    $a = new A();
                    $a->callMeMaybe("foo");',
            ],
            'isCallableString' => [
                '<?php
                    function foo(): void {}

                    function callMeMaybe(string $method): void {
                        if (is_callable($method)) {
                            $method();
                        }
                    }

                    callMeMaybe("foo");',
            ],
            'arrayMapVariadicClosureArg' => [
                '<?php
                    $a = array_map(
                      function(int $type, string ...$args):string {
                        return "hello";
                      },
                      [1, 2, 3]
                    );',
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
                        function(int $a): int {
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
                        function(string $a): string {
                        },
                        $bar
                    );',
                'error_message' => 'InvalidReturnType',
            ],
            'undefinedCallableClass' => [
                '<?php
                    class A {
                        public function getFoo(): Foo
                        {
                            return new Foo([]);
                        }

                        /**
                         * @param  mixed $argOne
                         * @param  mixed $argTwo
                         * @return void
                         */
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
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo("A::barr");',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedCallableMethodClass' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo("B::bar");',
                'error_message' => 'UndefinedClass',
            ],
            'undefinedCallableFunction' => [
                '<?php
                    function foo(callable $c): void {}

                    foo("trime");',
                'error_message' => 'UndefinedFunction',
            ],
            'possiblyNullFunctionCall' => [
                '<?php
                    /**
                     * @var Closure|null $foo
                     */
                    $foo = null;


                    $foo =
                        /** @param mixed $bar */
                        function ($bar) use (&$foo): string
                        {
                            if (is_array($bar)) {
                                return $foo($bar);
                            }

                            return $bar;
                        };',
                'error_message' => 'MixedReturnStatement',
            ],
            'stringFunctionCall' => [
                '<?php
                    $bad_one = "hello";
                    $a = $bad_one(1);',
                'error_message' => 'MixedAssignment',
            ],
            'wrongParamType' => [
                '<?php
                    $take_string = function(string $s): string { return $s; };
                    $take_string(42);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'missingClosureReturnType' => [
                '<?php
                    $a = function() {
                        return "foo";
                    }',
                'error_message' => 'MissingClosureReturnType',
            ],
            'wrongCallableReturnType' => [
                '<?php
                    $add_one = function(int $a): int {
                        return $a + 1;
                    };

                    /**
                     * @param callable(int) : int $c
                     */
                    function bar(callable $c) : string {
                        return $c(1);
                    }

                    bar($add_one);',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
