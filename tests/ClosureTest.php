<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ClosureTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'byRefUseVar' => [
                'code' => '<?php
                    $doNotContaminate = 123;

                    $test = 123;
                    
                    $testBefore = $test;

                    $testInsideBefore = null;
                    $testInsideAfter = null;

                    $v = function () use (&$test, &$testInsideBefore, &$testInsideAfter, $doNotContaminate): void {
                        $testInsideBefore = $test;
                        $test = "test";
                        $testInsideAfter = $test;

                        $doNotContaminate = "test";
                    };
                ',
                'assertions' => [
                    '$testBefore===' => '123',
                    '$testInsideBefore===' => "'test'|123|null",
                    '$testInsideAfter===' => "'test'|null",
                    '$test===' => "'test'|123",

                    '$doNotContaminate===' => '123',
                ],
            ],
            'byRefUseSelf' => [
                'code' => '<?php
                    $external = random_int(0, 1);
                    
                    $v = function (bool $callMe) use (&$v, $external): void {
                        echo($external.PHP_EOL);
                        if ($callMe) {
                            $v(false);
                        }
                    };
                    
                    $v(true);',
            ],
            'byRefUseVarChangeType' => [
                'code' => '<?php

                    function a(string $arg): int {
                        $v = function() use (&$arg): void {
                            if (is_integer($arg)) {
                                echo $arg;
                            }
                            if (random_bytes(1)) {
                                $arg = 123;
                            }
                        };
                        $v();
                        $v();
                        return 0;
                    }

                    a("test");',
            ],
            'inferredArg' => [
                'code' => '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        function(string $a) {
                            return $a . "blah";
                        },
                        $bar
                    );',
            ],
            'inferredArgArrowFunction' => [
                'code' => '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        fn(string $a) => $a . "blah",
                        $bar
                    );',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'varReturnType' => [
                'code' => '<?php
                    $add_one = function(int $a) : int {
                        return $a + 1;
                    };

                    $a = $add_one(1);',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'varReturnTypeArray' => [
                'code' => '<?php
                    $add_one = fn(int $a) : int => $a + 1;

                    $a = $add_one(1);',
                'assertions' => [
                    '$a' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'correctParamType' => [
                'code' => '<?php
                    $take_string = function(string $s): string { return $s; };
                    $take_string("string");',
            ],
            'arrayMapClosureVar' => [
                'code' => '<?php
                    $mirror = function(int $i) : int { return $i; };
                    $a = array_map($mirror, [1, 2, 3]);',
                'assertions' => [
                    '$a' => 'list{int, int, int}',
                ],
            ],
            'inlineCallableFunction' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = array_map(
                        function(int $type, string ...$args):string {
                            return "hello";
                        },
                        [1, 2, 3]
                    );',
            ],
            'returnsTypedClosure' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'returnsTypedClosureWithClasses' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => ['MissingClosureReturnType'],
            ],
            'inferArrayMapReturnTypeWithTypehints' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    namespace NS;
                    use Closure;
                    /** @param Closure(int):bool $c */
                    function acceptsIntToBool(Closure $c): void {}

                    acceptsIntToBool(Closure::fromCallable(function(int $n): bool { return $n > 0; }));',
            ],
            'singleLineClosures' => [
                'code' => '<?php
                    $a = function() : Closure { return function() : string { return "hello"; }; };
                    $b = $a()();',
                'assertions' => [
                    '$a' => 'pure-Closure():pure-Closure():string',
                    '$b' => 'string',
                ],
            ],
            'voidReturningArrayMap' => [
                'code' => '<?php
                    array_map(
                        function(int $i) : void {
                            echo $i;
                        },
                        [1, 2, 3]
                    );',
            ],
            'closureFromCallableInvokableNamedClass' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $closure = Closure::fromCallable("strlen");
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(string):int<0, max>',
                ],
            ],
            'allowClosureWithNarrowerReturn' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @param string[][] $arr */
                    function foo(array $arr) : void {
                        array_map(
                            function(array $a) {
                                return reset($a);
                            },
                            $arr
                        );
                    }',
            ],
            'refineCallableTypeWithoutTypehint' => [
                'code' => '<?php
                    /** @param string[][] $arr */
                    function foo(array $arr) : void {
                        array_map(
                            function($a) {
                                return reset($a);
                            },
                            $arr
                        );
                    }',
            ],
            'inferGeneratorReturnType' => [
                'code' => '<?php
                    function accept(Generator $gen): void {}

                    accept(
                        (function() {
                            yield;
                            return 42;
                        })()
                    );',
            ],
            'callingInvokeOnClosureIsSameAsCallingDirectly' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'annotateShortClosureReturn' => [
                'code' => '<?php
                    /** @psalm-suppress MissingReturnType */
                    function returnsBool() { return true; }
                    $a = fn() : bool => /** @var bool */ returnsBool();',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'rememberParentAssertions' => [
                'code' => '<?php
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
                    }',
            ],
            'CallableWithArrayMap' => [
                'code' => '<?php
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
                    '$result' => 'list{stdClass}',
                ],
            ],
            'CallableWithArrayReduce' => [
                'code' => '<?php
                    /**
                     * @return callable(int, int): int
                     */
                    function maker() {
                       return function(int $sum, int $e) {
                          return $sum + $e;
                       };
                    }
                    $maker = maker();
                    $result = array_reduce([1, 2, 3], $maker, 0);',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'FirstClassCallable:NamedFunction:is_int' => [
                'code' => '<?php
                    $closure = is_int(...);
                    $result = $closure(1);
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(mixed):bool',
                    '$result' => 'bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:NamedFunction:strlen' => [
                'code' => '<?php
                    $closure = strlen(...);
                    $result = $closure("test");
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(string):int<0, max>',
                    '$result' => 'int<0, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:UserDefined' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:Expr' => [
                'code' => '<?php
                    class Test {
                        public function __construct(private readonly string $string) {
                        }

                        public function length(): int {
                            return strlen($this->string);
                        }
                    }
                    $test = new Test("test");
                    $method_name = "length";
                    $closure = $test->$method_name(...);
                    $length = $closure();
                ',
                'assertions' => [
                    '$length' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:BuiltIn' => [
                'code' => '<?php
                    $queue = new \SplQueue;
                    $closure = $queue->count(...);
                    $count = $closure();
                ',
                'assertions' => [
                    '$count' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:StaticMethod' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:StaticMethod:Expr' => [
                'code' => '<?php
                    class Test {
                        public static function length(string $param): int {
                            return strlen($param);
                        }
                    }
                    $method_name = "length";
                    $closure = Test::$method_name(...);
                    $length = $closure("test");
                ',
                'assertions' => [
                    '$length' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InvokableObject' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:FromClosure' => [
                'code' => '<?php
                    $closure = fn (string $string): int => strlen($string);
                    $closure = $closure(...);
                ',
                'assertions' => [
                    '$closure' => 'pure-Closure(string):int<0, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:MagicInstanceMethod' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:MagicStaticMethod' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InheritedStaticMethod' => [
                'code' => '<?php

                    abstract class A
                    {
                        public function foo(int $i): string
                        {
                            return (string) $i;
                        }
                    }

                    class C extends A {}

                    /** @param \Closure(int):string $_ */
                    function takesIntToString(\Closure $_): void {}

                    takesIntToString(C::foo(...));',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InheritedStaticMethodWithStaticTypeParameter' => [
                'code' => '<?php

                    /** @template T */
                    class Holder
                    {
                        /** @param T $value */
                        public function __construct(public $value) {}
                    }

                    abstract class A
                    {
                        final public function __construct(public int $i) {}

                        /** @return Holder<static> */
                        public static function create(int $i): Holder
                        {
                            return new Holder(new static($i));
                        }
                    }

                    class C extends A {}

                    /** @param \Closure(int):Holder<C> $_ */
                    function takesIntToHolder(\Closure $_): void {}

                    takesIntToHolder(C::create(...));',
            ],
            'FirstClassCallable:WithArrayMap' => [
                'code' => '<?php
                    $array = [1, 2, 3];
                    $closure = fn (int $value): int => $value * $value;
                    $result1 = array_map((new \SplQueue())->enqueue(...), $array);
                    $result2 = array_map(strval(...), $array);
                    $result3 = array_map($closure(...), $array);
                ',
                'assertions' => [
                    '$result1' => 'list{null, null, null}',
                    '$result2' => 'list{string, string, string}',
                    '$result3' => 'list{int, int, int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:array_map' => [
                'code' => '<?php call_user_func(array_map(...), intval(...), ["1"]);',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:AssignmentVisitorMap' => [
                'code' => '<?php
                    class Test {
                        /** @var list<\Closure():void> */
                        public array $handlers = [];

                        public function register(): void {
                            foreach ([1, 2, 3] as $index) {
                                $this->push($this->handler(...));
                            }
                        }

                        /**
                         * @param Closure():void $closure
                         * @return void
                         */
                        private function push(\Closure $closure): void {
                            $this->handlers[] = $closure;
                        }

                        private function handler(): void {
                        }
                    }

                    $test = new Test();
                    $test->register();
                    $handlers = $test->handlers;
                ',
                'assertions' => [
                    '$handlers' => 'list<Closure():void>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:Method:Asserted' => [
                'code' => '<?php
                    $r = false;
                    /** @var object $o */;
                    /** @var string $m */;
                    if (method_exists($o, $m)) {
                        $r = $o->$m(...);
                    }
                ',
                'assertions' => [
                    '$r===' => 'Closure|false',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'arrowFunctionReturnsNeverImplicitly' => [
                'code' => '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        fn(string $a) => throw new Exception($a),
                        $bar
                    );',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'arrowFunctionReturnsNeverExplicitly' => [
                'code' => '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        /** @return never */
                        fn(string $a) => die(),
                        $bar
                    );',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'unknownFirstClassCallable' => [
                'code' => '<?php
                    /** @psalm-suppress UndefinedFunction */
                    unknown(...);',
            ],
            'reconcileClosure' => [
                'code' => '<?php
                    /**
                    * @param Closure|callable-string $callable
                    */
                    function use_callable($callable) : void
                    {
                    }

                    /**
                    * @param Closure|string $var
                    */
                    function test($var) : void
                    {
                        if (is_callable($var))
                            use_callable($var);
                        else
                            echo $var;  // $var should be string, instead it\'s considered to be Closure|string.
                    }',
            ],
            'classExistsInOuterScopeOfArrowFunction' => [
                'code' => <<<'PHP'
                    <?php
                    if (class_exists(Foo::class)) {
                        /** @return mixed */
                        fn() => Foo::bar(23, []);
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'classExistsInOuterScopeOfAClosure' => [
                'code' => <<<'PHP'
                    <?php
                    if (class_exists(Foo::class)) {
                        /** @return mixed */
                        function () {
                            return Foo::bar(23, []);
                        };
                    }
                    PHP,
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'wrongArg' => [
                'code' => '<?php
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
                'code' => '<?php
                    $bar = ["foo", "bar"];

                    $bam = array_map(
                        function(string $a): string {
                        },
                        $bar
                    );',
                'error_message' => 'InvalidReturnType',
            ],
            'possiblyNullFunctionCall' => [
                'code' => '<?php
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
                'code' => '<?php
                    $take_string = function(string $s): string { return $s; };
                    $take_string(42);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'missingClosureReturnType' => [
                'code' => '<?php
                    $a = function() {
                        return "foo";
                    };',
                'error_message' => 'MissingClosureReturnType',
            ],
            'returnsTypedClosureWithBadReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = function() use ($i) {};',
                'error_message' => 'UndefinedVariable',
            ],
            'voidReturningArrayMap' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class Foo {
                        public function __construct(UndefinedClass $o) {}
                    }
                    new Foo(function() : void {});',
                'error_message' => 'UndefinedClass',
            ],
            'useDuplicateName' => [
                'code' => '<?php
                    $foo = "bar";

                    $a = function (string $foo) use ($foo) : string {
                      return $foo;
                    };',
                'error_message' => 'DuplicateParam',
            ],
            'privateCallable' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function takesA(A $_a) : void {}
                    function takesB(B $_b) : void {}

                    $getAButReallyB = /** @return A */ function() {
                        return new B;
                    };

                    takesA($getAButReallyB());
                    takesB($getAButReallyB());',
                'error_message' => 'ArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:28 - Argument 1 of takesB expects B, but parent type A provided',
            ],
            'noCrashWhenComparingIllegitimateCallable' => [
                'code' => '<?php
                    class C {}

                    function foo() : C {
                        return fn (int $i) => "";
                    }',
                'error_message' => 'InvalidReturnStatement',
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'detectImplicitVoidReturn' => [
                'code' => '<?php
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
                'error_message' => 'InvalidArgument',
            ],
            'undefinedVariableInEncapsedString' => [
                'code' => '<?php
                    fn(): string => "$a";
                ',
                'error_message' => 'UndefinedVariable',
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'undefinedVariableInStringCast' => [
                'code' => '<?php
                    fn(): string => (string) $a;
                ',
                'error_message' => 'UndefinedVariable',
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'forbidTemplateAnnotationOnClosure' => [
                'code' => '<?php
                    /** @template T */
                    function (): void {};
                ',
                'error_message' => 'InvalidDocblock',
            ],
            'forbidTemplateAnnotationOnShortClosure' => [
                'code' => '<?php
                    /** @template T */
                    fn(): bool => false;
                ',
                'error_message' => 'InvalidDocblock',
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'closureInvalidArg' => [
                'code' => '<?php
                    /** @param Closure(int): string $c */
                    function takesClosure(Closure $c): void {}

                    takesClosure(5);',
                'error_message' => 'InvalidArgument',
            ],
            'FirstClassCallable:UndefinedMethod' => [
                'code' => '<?php
                    $queue = new \SplQueue;
                    $closure = $queue->undefined(...);
                    $count = $closure();
                ',
                'error_message' => 'UndefinedMethod',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:UndefinedMagicInstanceMethod' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:UndefinedMagicStaticMethod' => [
                'code' => '<?php
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
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'thisInStaticClosure' => [
                'code' => '<?php
                    class C {
                        public string $a = "zzz";
                        public function f(): void {
                            $f = static function (): void {
                                echo $this->a;
                            };
                            $f();
                        }
                    }
                ',
                'error_message' => 'InvalidScope',
            ],
            'thisInStaticArrowFunction' => [
                'code' => '<?php
                    class C {
                        public int $a = 1;
                        public function f(): int {
                            $f = static fn(): int => $this->a;
                            return $f();;
                        }
                    }
                ',
                'error_message' => 'InvalidScope',
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'FirstClassCallable:WithNew' => [
                'code' => <<<'PHP'
                    <?php
                        new stdClass(...);
                    PHP,
                'error_message' => 'ParseError',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
