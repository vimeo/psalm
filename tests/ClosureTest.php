<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ClosureTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'byRefUseVar' => [
                '<?php
                    /** @return void */
                    function run_function(\Closure $fnc) {
                        $fnc();
                    }

                    /**
                     * @return void
                     * @psalm-suppress MixedArgument
                     */
                    function f() {
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

                    f();',
            ],
            'inferredArg' => [
                '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        function(string $a) {
                            return $a . "blah";
                        },
                        $bar
                    );',
            ],
            'inferredArgArrowFunction' => [
                '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        fn(string $a) => $a . "blah",
                        $bar
                    );',
                'assertions' => [],
                'error_levels' => [],
                '7.4'
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
            'varReturnTypeArray' => [
                '<?php
                    $add_one = fn(int $a) : int => $a + 1;

                    $a = $add_one(1);',
                'assertions' => [
                    '$a' => 'int',
                ],
                'error_levels' => [],
                '7.4'
            ],
            'correctParamType' => [
                '<?php
                    $take_string = function(string $s): string { return $s; };
                    $take_string("string");',
            ],
            'arrayMapClosureVar' => [
                '<?php
                    $mirror = function(int $i) : int { return $i; };
                    $a = array_map($mirror, [1, 2, 3]);',
                'assertions' => [
                    '$a' => 'array{int, int, int}',
                ],
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
            'arrayMapVariadicClosureArg' => [
                '<?php
                    $a = array_map(
                        function(int $type, string ...$args):string {
                            return "hello";
                        },
                        [1, 2, 3]
                    );',
            ],
            'returnsTypedClosure' => [
                '<?php
                    /**
                     * @param Closure(int):int $f
                     * @param Closure(int):int $g
                     *
                     * @return Closure(int):int
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (int $x) use ($f, $g) : int {
                            return $f($g($x));
                        };
                    }',
            ],
            'returnsTypedClosureArrow' => [
                '<?php
                    /**
                     * @param Closure(int):int $f
                     * @param Closure(int):int $g
                     *
                     * @return Closure(int):int
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return fn(int $x):int => $f($g($x));
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4'
            ],
            'returnsTypedClosureWithClasses' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}

                    /**
                     * @param Closure(B):A $f
                     * @param Closure(C):B $g
                     *
                     * @return Closure(C):A
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (C $x) use ($f, $g) : A {
                            return $f($g($x));
                        };
                    }

                    $a = foo(
                        function(B $b) : A { return new A;},
                        function(C $c) : B { return new B;}
                    )(new C);',
                'assertions' => [
                    '$a' => 'A',
                ],
            ],
            'returnsTypedClosureWithSubclassParam' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}
                    class C2 extends C {}

                    /**
                     * @param Closure(B):A $f
                     * @param Closure(C):B $g
                     *
                     * @return Closure(C2):A
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (C $x) use ($f, $g) : A {
                            return $f($g($x));
                        };
                    }

                    $a = foo(
                        function(B $b) : A { return new A;},
                        function(C $c) : B { return new B;}
                    )(new C2);',
                'assertions' => [
                    '$a' => 'A',
                ],
            ],
            'returnsTypedClosureWithParentReturn' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}
                    class A2 extends A {}

                    /**
                     * @param Closure(B):A2 $f
                     * @param Closure(C):B $g
                     *
                     * @return Closure(C):A
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (C $x) use ($f, $g) : A2 {
                            return $f($g($x));
                        };
                    }

                    $a = foo(
                        function(B $b) : A2 { return new A2;},
                        function(C $c) : B { return new B;}
                    )(new C);',
                'assertions' => [
                    '$a' => 'A',
                ],
            ],
            'inferArrayMapReturnTypeWithoutTypehints' => [
                '<?php
                    /**
                     * @param array{0:string,1:string}[] $ret
                     * @return array{0:string,1:int}[]
                     */
                    function f(array $ret) : array
                    {
                        return array_map(
                            /**
                             * @param array{0:string,1:string} $row
                             */
                            function (array $row) {
                                return [
                                    strval($row[0]),
                                    intval($row[1]),
                                ];
                            },
                            $ret
                        );
                    }',
                'assertions' => [],
                'error_levels' => ['MissingClosureReturnType'],
            ],
            'inferArrayMapReturnTypeWithTypehints' => [
                '<?php
                    /**
                     * @param array{0:string,1:string}[] $ret
                     * @return array{0:string,1:int}[]
                     */
                    function f(array $ret): array
                    {
                        return array_map(
                            /**
                             * @param array{0:string,1:string} $row
                             */
                            function (array $row): array {
                                return [
                                    strval($row[0]),
                                    intval($row[1]),
                                ];
                            },
                            $ret
                        );
                    }',
            ],
            'invokableProperties' => [
                '<?php
                    class A {
                        public function __invoke(): bool { return true; }
                    }

                    class C {
                        /** @var A $invokable */
                        private $invokable;

                        public function __construct(A $invokable) {
                            $this->invokable = $invokable;
                        }

                        public function callTheInvokableDirectly(): bool {
                            return ($this->invokable)();
                        }

                        public function callTheInvokableIndirectly(): bool {
                            $r = $this->invokable;
                            return $r();
                        }
                    }',
            ],
            'mirrorCallableParams' => [
                '<?php
                    namespace NS;
                    use Closure;
                    /** @param Closure(int):bool $c */
                    function acceptsIntToBool(Closure $c): void {}

                    acceptsIntToBool(Closure::fromCallable(function(int $n): bool { return $n > 0; }));',
            ],
            'singleLineClosures' => [
                '<?php
                    $a = function() : Closure { return function() : string { return "hello"; }; };
                    $b = $a()();',
                'assertions' => [
                    '$a' => 'pure-Closure():pure-Closure():"hello"',
                    '$b' => 'string',
                ],
            ],
            'voidReturningArrayMap' => [
                '<?php
                    array_map(
                        function(int $i) : void {
                            echo $i;
                        },
                        [1, 2, 3]
                    );',
            ],
            'closureFromCallableInvokableNamedClass' => [
                '<?php
                    namespace NS;
                    use Closure;

                    /** @param Closure(int):bool $c */
                    function acceptsIntToBool(Closure $c): void {}

                    class NamedInvokable {
                        public function __invoke(int $p): bool {
                            return $p > 0;
                        }
                    }

                    acceptsIntToBool(Closure::fromCallable(new NamedInvokable));',
            ],
            'closureFromCallableInvokableAnonymousClass' => [
                '<?php
                    namespace NS;
                    use Closure;

                    /** @param Closure(int):bool $c */
                    function acceptsIntToBool(Closure $c): void {}

                    $anonInvokable = new class {
                        public function __invoke(int $p):bool {
                            return $p > 0;
                        }
                    };

                    acceptsIntToBool(Closure::fromCallable($anonInvokable));',
            ],
            'publicCallableFromInside' => [
                '<?php
                    class Base  {
                        public function publicMethod() : void {}
                    }

                    class Example extends Base {
                        public function test() : Closure {
                            return Closure::fromCallable([$this, "publicMethod"]);
                        }
                    }',
            ],
            'protectedCallableFromInside' => [
                '<?php
                    class Base  {
                        protected function protectedMethod() : void {}
                    }

                    class Example extends Base {
                        public function test() : Closure {
                            return Closure::fromCallable([$this, "protectedMethod"]);
                        }
                    }',
            ],
            'closureFromCallableNamedFunction' => [
                '<?php
                    $closure = Closure::fromCallable("strlen");
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(string):(0|positive-int)',
                ]
            ],
            'allowClosureWithNarrowerReturn' => [
                '<?php
                    class A {}
                    class B extends A {}

                    /**
                     * @param Closure():A $x
                     */
                    function accept_closure($x) : void {
                        $x();
                    }
                    accept_closure(
                        function () : B {
                            return new B();
                        }
                    );',
            ],
            'allowCallableWithWiderParam' => [
                '<?php
                    class A {}
                    class B extends A {}

                    /**
                     * @param Closure(B $a):A $x
                     */
                    function accept_closure($x) : void {
                        $x(new B());
                    }
                    accept_closure(
                        function (A $a) : A {
                            return $a;
                        }
                    );',
            ],
            'allowCallableWithOptionalArg' => [
                '<?php
                    /**
                     * @param Closure():int $x
                     */
                    function accept_closure($x) : void {
                        $x();
                    }
                    accept_closure(
                        function (int $x = 5) : int {
                            return $x;
                        }
                    );',
            ],
            'refineCallableTypeWithTypehint' => [
                '<?php
                    /** @param string[][] $arr */
                    function foo(array $arr) : void {
                        array_map(
                            function(array $a) {
                                return reset($a);
                            },
                            $arr
                        );
                    }'
            ],
            'refineCallableTypeWithoutTypehint' => [
                '<?php
                    /** @param string[][] $arr */
                    function foo(array $arr) : void {
                        array_map(
                            function($a) {
                                return reset($a);
                            },
                            $arr
                        );
                    }'
            ],
            'inferGeneratorReturnType' => [
                '<?php
                    function accept(Generator $gen): void {}

                    accept(
                        (function() {
                            yield;
                            return 42;
                        })()
                    );'
            ],
            'callingInvokeOnClosureIsSameAsCallingDirectly' => [
                '<?php
                    class A {
                        /** @var Closure(int):int */
                        private Closure $a;

                        public function __construct() {
                            $this->a = fn(int $a) : int => $a + 5;
                        }

                        public function invoker(int $b) : int {
                            return $this->a->__invoke($b);
                        }
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4'
            ],
            'annotateShortClosureReturn' => [
                '<?php
                    /** @psalm-suppress MissingReturnType */
                    function returnsBool() { return true; }
                    $a = fn() : bool => /** @var bool */ returnsBool();',
                [],
                [],
                '7.4'
            ],
            'rememberParentAssertions' => [
                '<?php
                    class A {
                        public ?A $a = null;
                        public function foo() : void {}
                    }

                    function doFoo(A $a): void {
                        if ($a->a instanceof A) {
                            function () use ($a): void {
                                $a->a->foo();
                            };
                        }
                    }'
            ],
            'CallableWithArrayMap' => [
                '<?php
                    /**
                     * @psalm-template T
                     * @param class-string<T> $className
                     * @return callable(...mixed):T
                     */
                    function maker(string $className) {
                       return function(...$args) use ($className) {
                          /** @psalm-suppress MixedMethodCall */
                          return new $className(...$args);
                       };
                    }
                    $maker = maker(stdClass::class);
                    $result = array_map($maker, ["abc"]);',
                'assertions' => [
                    '$result' => 'array{stdClass}'
                ],
            ],
            'FirstClassCallable:NamedFunction:is_int' => [
                '<?php
                    $closure = is_int(...);
                    $result = $closure(1);
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(mixed):bool',
                    '$result' => 'bool',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:NamedFunction:strlen' => [
                '<?php
                    $closure = strlen(...);
                    $result = $closure("test");
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(string):(0|positive-int)',
                    '$result' => 'int|positive-int',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:InstanceMethod:UserDefined' => [
                '<?php
                    class Test {
                        public function __construct(private readonly string $string) {
                        }

                        public function length(): int {
                            return strlen($this->string);
                        }
                    }
                    $test = new Test("test");
                    $closure = $test->length(...);
                    $length = $closure();
                ',
                'assertions' => [
                    '$length' => 'int',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:InstanceMethod:BuiltIn' => [
                '<?php
                    $queue = new \SplQueue;
                    $closure = $queue->count(...);
                    $count = $closure();
                ',
                'assertions' => [
                    '$count' => 'int',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:StaticMethod' => [
                '<?php
                    class Test {
                        public static function length(string $param): int {
                            return strlen($param);
                        }
                    }
                    $closure = Test::length(...);
                    $length = $closure("test");
                ',
                'assertions' => [
                    '$length' => 'int',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:InvokableObject' => [
                '<?php
                    class Test {
                        public function __invoke(string $param): int {
                            return strlen($param);
                        }
                    }
                    $test = new Test();
                    $closure = $test(...);
                    $length = $closure("test");
                ',
                'assertions' => [
                    '$length' => 'int',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:FromClosure' => [
                '<?php
                    $closure = fn (string $string): int => strlen($string);
                    $closure = $closure(...);
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(string):(0|positive-int)',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:MagicInstanceMethod' => [
                '<?php
                    /**
                     * @method int length()
                     */
                    class Test {
                        public function __construct(private readonly string $string) {
                        }

                        public function __call(string $name, array $args): mixed {
                            return match ($name) {
                                "length" => strlen($this->string),
                                default => throw new \Error("Undefined method"),
                            };
                        }
                    }
                    $test = new Test("test");
                    $closure = $test->length(...);
                    $length = $closure();
                ',
                'assertions' => [
                    '$length' => 'int',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:MagicStaticMethod' => [
                '<?php
                    /**
                     * @method static int length(string $length)
                     */
                    class Test {
                        public static function __callStatic(string $name, array $args): mixed {
                            return match ($name) {
                                "length" => strlen((string) $args[0]),
                                default => throw new \Error("Undefined method"),
                            };
                        }
                    }
                    $closure = Test::length(...);
                    $length = $closure("test");
                ',
                'assertions' => [
                    '$length' => 'int',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:WithArrayMap' => [
                '<?php
                    $array = [1, 2, 3];
                    $closure = fn (int $value): int => $value * $value;
                    $result1 = array_map((new \SplQueue())->enqueue(...), $array);
                    $result2 = array_map(strval(...), $array);
                    $result3 = array_map($closure(...), $array);
                ',
                'assertions' => [
                    '$result1' => 'array{null, null, null}',
                    '$result2' => 'array{string, string, string}',
                    '$result3' => 'array{int, int, int}',
                ],
                [],
                '8.1'
            ],
            'FirstClassCallable:array_map' => [
                '<?php call_user_func(array_map(...), intval(...), ["1"]);',
                'assertions' => [],
                [],
                '8.1',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
            'possiblyNullFunctionCall' => [
                '<?php
                    /**
                     * @var Closure|null $foo
                     */
                    $foo = null;


                    $foo =
                        /**
                         * @param mixed $bar
                         * @psalm-suppress MixedFunctionCall
                         */
                        function ($bar) use (&$foo): string
                        {
                            if (is_array($bar)) {
                                return $foo($bar);
                            }

                            return $bar;
                        };',
                'error_message' => 'MixedReturnStatement',
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
                    };',
                'error_message' => 'MissingClosureReturnType',
            ],
            'returnsTypedClosureWithBadReturnType' => [
                '<?php
                    /**
                     * @param Closure(int):int $f
                     * @param Closure(int):int $g
                     *
                     * @return Closure(int):string
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (int $x) use ($f, $g) : int {
                            return $f($g($x));
                        };
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'returnsTypedCallableWithBadReturnType' => [
                '<?php
                    /**
                     * @param Closure(int):int $f
                     * @param Closure(int):int $g
                     *
                     * @return callable(int):string
                     */
                    function foo(Closure $f, Closure $g) : callable {
                        return function (int $x) use ($f, $g) : int {
                            return $f($g($x));
                        };
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'returnsTypedClosureWithBadParamType' => [
                '<?php
                    /**
                     * @param Closure(int):int $f
                     * @param Closure(int):int $g
                     *
                     * @return Closure(string):int
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (int $x) use ($f, $g) : int {
                            return $f($g($x));
                        };
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'returnsTypedCallableWithBadParamType' => [
                '<?php
                    /**
                     * @param Closure(int):int $f
                     * @param Closure(int):int $g
                     *
                     * @return callable(string):int
                     */
                    function foo(Closure $f, Closure $g) : callable {
                        return function (int $x) use ($f, $g) : int {
                            return $f($g($x));
                        };
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'returnsTypedClosureWithBadCall' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}
                    class D {}

                    /**
                     * @param Closure(B):A $f
                     * @param Closure(C):B $g
                     *
                     * @return Closure(C):A
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (int $x) use ($f, $g) : int {
                            return $f($g($x));
                        };
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'returnsTypedClosureWithSubclassParam' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}
                    class C2 extends C {}

                    /**
                     * @param Closure(B):A $f
                     * @param Closure(C):B $g
                     *
                     * @return Closure(C):A
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (C2 $x) use ($f, $g) : A {
                            return $f($g($x));
                        };
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'returnsTypedClosureWithSubclassReturn' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}
                    class A2 extends A {}

                    /**
                     * @param Closure(B):A $f
                     * @param Closure(C):B $g
                     *
                     * @return Closure(C):A2
                     */
                    function foo(Closure $f, Closure $g) : Closure {
                        return function (C $x) use ($f, $g) : A {
                            return $f($g($x));
                        };
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'returnsTypedClosureFromCallable' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}

                    /**
                     * @param Closure(B):A $f
                     * @param Closure(C):B $g
                     *
                     * @return callable(C):A
                     */
                    function foo(Closure $f, Closure $g) : callable {
                        return function (C $x) use ($f, $g) : A {
                            return $f($g($x));
                        };
                    }

                    /**
                     * @param Closure(B):A $f
                     * @param Closure(C):B $g
                     *
                     * @return Closure(C):A
                     */
                    function bar(Closure $f, Closure $g) : Closure {
                        return foo($f, $g);
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'undefinedVariable' => [
                '<?php
                    $a = function() use ($i) {};',
                'error_message' => 'UndefinedVariable',
            ],
            'voidReturningArrayMap' => [
                '<?php
                    $arr = array_map(
                        function(int $i) : void {
                            echo $i;
                        },
                        [1, 2, 3]
                    );

                    foreach ($arr as $a) {
                        if ($a) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'closureFromCallableInvokableNamedClassWrongArgs' => [
                '<?php
                    namespace NS;
                    use Closure;

                    /** @param Closure(string):bool $c */
                    function acceptsIntToBool(Closure $c): void {}

                    class NamedInvokable {
                        public function __invoke(int $p): bool {
                            return $p > 0;
                        }
                    }

                    acceptsIntToBool(Closure::fromCallable(new NamedInvokable));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'undefinedClassForCallable' => [
                '<?php
                    class Foo {
                        public function __construct(UndefinedClass $o) {}
                    }
                    new Foo(function() : void {});',
                'error_message' => 'UndefinedClass',
            ],
            'useDuplicateName' => [
                '<?php
                    $foo = "bar";

                    $a = function (string $foo) use ($foo) : string {
                      return $foo;
                    };',
                'error_message' => 'DuplicateParam',
            ],
            'privateCallable' => [
                '<?php
                    class Base  {
                        private function privateMethod() : void {}
                    }

                    class Example extends Base {
                        public function test() : Closure {
                            return Closure::fromCallable([$this, "privateMethod"]);
                        }
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'prohibitCallableWithRequiredArg' => [
                '<?php
                    /**
                     * @param Closure():int $x
                     */
                    function accept_closure($x) : void {
                        $x();
                    }
                    accept_closure(
                      function (int $x) : int {
                        return $x;
                      }
                    );',
                'error_message' => 'InvalidArgument',
            ],
            'useClosureDocblockType' => [
                '<?php
                    class A {}
                    class B extends A {}

                    function takesA(A $_a) : void {}
                    function takesB(B $_b) : void {}

                    $getAButReallyB = /** @return A */ function() {
                        return new B;
                    };

                    takesA($getAButReallyB());
                    takesB($getAButReallyB());',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:28 - Argument 1 of takesB expects B, parent type A provided',
            ],
            'closureByRefUseToMixed' => [
                '<?php
                    function assertInt(int $int): int {
                        $s = static function() use(&$int): void {
                            $int = "42";
                        };

                        $s();

                        return $int;
                    }',
                'error_message' => 'MixedReturnStatement'
            ],
            'noCrashWhenComparingIllegitimateCallable' => [
                '<?php
                    class C {}

                    function foo() : C {
                        return fn (int $i) => "";
                    }',
                'error_message' => 'InvalidReturnStatement',
                [],
                false,
                '7.4',
            ],
            'detectImplicitVoidReturn' => [
                '<?php
                    /**
                     * @param Closure():Exception $c
                     */
                    function takesClosureReturningException(Closure $c) : void {
                        echo $c()->getMessage();
                    }

                    takesClosureReturningException(
                        function () {
                            echo "hello";
                        }
                    );',
                'error_message' => 'InvalidArgument'
            ],
            'undefinedVariableInEncapsedString' => [
                '<?php
                    fn(): string => "$a";
                ',
                'error_message' => 'UndefinedVariable',
                [],
                false,
                '7.4'
            ],
            'undefinedVariableInStringCast' => [
                '<?php
                    fn(): string => (string) $a;
                ',
                'error_message' => 'UndefinedVariable',
                [],
                false,
                '7.4'
            ],
            'forbidTemplateAnnotationOnClosure' => [
                '<?php
                    /** @template T */
                    function (): void {};
                ',
                'error_message' => 'InvalidDocblock',
            ],
            'forbidTemplateAnnotationOnShortClosure' => [
                '<?php
                    /** @template T */
                    fn(): bool => false;
                ',
                'error_message' => 'InvalidDocblock',
                [],
                false,
                '7.4'
            ],
            'closureInvalidArg' => [
                '<?php
                    /** @param Closure(int): string $c */
                    function takesClosure(Closure $c): void {}

                    takesClosure(5);',
                'error_message' => 'InvalidArgument',
            ],
            'FirstClassCallable:UndefinedMethod' => [
                '<?php
                    $queue = new \SplQueue;
                    $closure = $queue->undefined(...);
                    $count = $closure();
                ',
                'error_message' => 'UndefinedMethod',
                [],
                false,
                '8.1'
            ],
            'FirstClassCallable:UndefinedMagicInstanceMethod' => [
                '<?php
                    class Test {
                        public function __call(string $name, array $args): mixed {
                            return match ($name) {
                                default => throw new \Error("Undefined method"),
                            };
                        }
                    }
                    $test = new Test();
                    $closure = $test->length(...);
                    $length = $closure();
                ',
                'error_message' => 'UndefinedMagicMethod',
                [],
                false,
                '8.1'
            ],
            'FirstClassCallable:UndefinedMagicStaticMethod' => [
                '<?php
                    class Test {
                        public static function __callStatic(string $name, array $args): mixed {
                            return match ($name) {
                                default => throw new \Error("Undefined method"),
                            };
                        }
                    }
                    $closure = Test::length(...);
                    $length = $closure();
                ',
                'error_message' => 'MixedAssignment',
                [],
                false,
                '8.1',
            ],
        ];
    }
}
