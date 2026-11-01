<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ClosureTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'byRefUseVarUnsetInClosure' => [
                'code' => '<?php
                    function reserve(): Closure {
                        $reserved = str_repeat("x", 10);
                        return function () use (&$reserved): void {
                            $reserved = "";
                            unset($reserved);
                        };
                    }',
            ],
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
                    '$a' => 'Closure[pure]():Closure[pure]():string',
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
                    '$closure' => 'Closure[pure](string):int<0, max>',
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
            'templateShenanigans' => [
                'code' => '<?php
                    class inner {}
                    class b {
                        public inner $key;

                        public function __construct() {
                            $this->key = new inner;
                        }
                    }

                    /**
                     * @template-covariant TKey as array-key
                     * @template TValue as b
                     */
                    class a {
                        /**
                         * @template TMappedValue
                         *
                         * @param (\Closure(TValue): TMappedValue)|true $callback Callback or null
                         *
                         * @return list<$callback is true ? array : TMappedValue>
                         */
                        public function toArray1(Closure|true $callback): array {
                            return [];
                        }
                        /**
                         * @template TMappedValue
                         * @template T as (\Closure(TValue): TMappedValue)
                         *
                         * @param T $callback Callback or null
                         *
                         * @return list<TMappedValue>
                         */
                        public function toArray2(Closure $callback): array {
                            return [];
                        }
                        /**
                         * @template TMappedValue
                         *
                         * @param (\Closure(TValue): TMappedValue) $callback Callback or null
                         *
                         * @return list<TMappedValue>
                         */
                        public function toArray3(Closure $callback): array {
                            return [];
                        }
                    }

                    $a = (new a)->toArray1(static fn ($obj) => $obj->key);

                    $b = (new a)->toArray2(static fn ($obj) => $obj->key);

                    $c = (new a)->toArray3(static fn ($obj) => $obj->key);',

                'assertions' => [
                    '$a===' => 'list<inner>',
                    '$b===' => 'list<inner>',
                    '$c===' => 'list<inner>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
            'FirstClassCallable:StringStaticMethod' => [
                'code' => '<?php
                    class C {
                        public static function sm(int $i): string {
                            return (string) $i;
                        }

                        /**
                         * @template T
                         * @param T $value
                         * @return T
                         */
                        public static function id($value) {
                            return $value;
                        }
                    }

                    $closure = "C::sm"(...);
                    $result = $closure(1);
                    $namespaced = "\\C::sm"(...);
                    $other_case = "c::SM"(...);
                    $generic = "C::id"(...);',
                'assertions' => [
                    '$closure' => 'Closure[impure](int):string',
                    '$result' => 'string',
                    '$namespaced' => 'Closure[impure](int):string',
                    '$other_case' => 'Closure[impure](int):string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:StringStaticMethodOfAliasedClass' => [
                'code' => '<?php
                    class C {
                        public static function sm(int $i): string {
                            return (string) $i;
                        }
                    }

                    class_alias(C::class, "Alias");

                    $closure = "Alias::sm"(...);',
                'assertions' => [
                    '$closure' => 'Closure[impure](int):string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:UndefinedStringStaticMethod' => [
                'code' => '<?php
                    class C {}

                    $closure = "C::missing"(...);',
                'assertions' => [
                    '$closure' => 'Closure[impure]',
                ],
                'ignored_issues' => ['InvalidFunctionCall', 'UndefinedMethod'],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:NamedFunction:is_int' => [
                'code' => '<?php
                    $closure = is_int(...);
                    $result = $closure(1);
                ',
                'assertions' => [
                    '$closure' => 'Closure[pure](mixed):bool',
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
                    '$closure' => 'Closure[pure](string):int<0, max>',
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
                    '$closure' => 'Closure[pure](string):int<0, max>',
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
            'FirstClassCallable:ParentMagicInstanceMethod' => [
                'code' => '<?php
                    /** @method string has(string $s) */
                    class Base {
                        public function __call(string $name, array $args): string { return "x"; }
                    }
                    final class Child extends Base {
                        public function test(): void {
                            $fcc = parent::has(...);
                            /** @psalm-check-type-exact $fcc = Closure(string):string */;
                        }
                    }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InheritedMagicStaticMethod' => [
                'code' => '<?php
                    class ParentClass {
                        public static function __callStatic(string $name, array $args): int { return 1; }
                    }
                    final class Child extends ParentClass {}
                    $c = Child::foo(...);
                ',
                'assertions' => [
                    '$c===' => 'Closure[impure]:int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InheritedStaticMethod:LiteralExpr' => [
                'code' => '<?php
                    class ParentClass {
                        public static function inherited(string $s): int { return strlen($s); }
                    }
                    final class Child extends ParentClass {}
                    $c = Child::{"inherited"}(...);
                ',
                'assertions' => [
                    '$c===' => 'Closure[impure](string):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InheritedInstanceMethod:LiteralExpr' => [
                'code' => '<?php
                    class ParentClass {
                        public function inherited(string $s): int { return strlen($s); }
                    }
                    final class Child extends ParentClass {}
                    $c = (new Child)->{"inherited"}(...);
                ',
                'assertions' => [
                    '$c===' => 'Closure[impure](string):int',
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
                    '$handlers' => 'list<Closure[impure]():void>',
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
                    '$r===' => 'Closure[impure]|false',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:ClassTemplatesAndStatic' => [
                'code' => '<?php
                    class Model {}

                    /** @template TModel of Model */
                    final class Builder {
                        /** @return Builder<TModel> */
                        public function has(string $relation): self { return $this; }

                        /** @param TModel $model */
                        public function save(Model $model): void {}
                    }

                    /** @template T */
                    class Collection {
                        public function filter(): static { return $this; }
                    }

                    /** @extends Collection<int> */
                    final class FinalCollection extends Collection {}

                    /** @var Builder<Model> $builder */;
                    /** @var Collection<int> $collection */;
                    /** @var FinalCollection $final_collection */;
                    $has = $builder->has(...);
                    $save = $builder->save(...);
                    $filter = $collection->filter(...);
                    $final_filter = $final_collection->filter(...);
                ',
                'assertions' => [
                    '$has===' => 'Closure[impure](string):Builder<Model>',
                    '$save===' => 'Closure[impure](Model):void',
                    '$filter===' => 'Closure[impure]():Collection<int>&static',
                    '$final_filter===' => 'Closure[impure]():FinalCollection',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:MethodTemplatesStandForTheirBounds' => [
                'code' => '<?php
                    /** @template T */
                    final class Coll {
                        /** @param list<T> $items */
                        public function __construct(private array $items) {}

                        /**
                         * @template U
                         * @param callable(T): U $f
                         * @return list<U>
                         */
                        public function map(callable $f): array { return array_map($f, $this->items); }

                        /**
                         * @template U of T
                         * @param U $u
                         * @return list<U>
                         */
                        public function wrap(mixed $u): array { return [$u]; }

                        /**
                         * @template V
                         * @param V $v
                         * @return list<V>
                         */
                        public static function listOf(mixed $v): array { return [$v]; }
                    }

                    /** @var Coll<int> $coll */;
                    $map = $coll->map(...);
                    $mapped = $map(fn(int $i): string => (string) $i);
                    $wrap = $coll->wrap(...);
                    $wrapped = $wrap(5);
                    $list_of = Coll::listOf(...);
                ',
                'assertions' => [
                    '$map===' => 'Closure[impure](callable[impure](int):mixed):list<mixed>',
                    '$mapped===' => 'list<mixed>',
                    '$wrap===' => 'Closure[impure](int):list<int>',
                    '$wrapped===' => 'list<int>',
                    '$list_of===' => 'Closure[impure](mixed):list<mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:StaticMethod:PassedToTemplatedCallableParam' => [
                'code' => '<?php
                    final class Util {
                        /**
                         * @template V
                         * @param V $v
                         * @return list<V>
                         */
                        public static function listOf(mixed $v): array { return [$v]; }
                    }

                    /**
                     * @template A
                     * @template B
                     * @param list<A> $list
                     * @param callable(A):B $f
                     * @return list<B>
                     */
                    function mapList(array $list, callable $f): array { return array_map($f, $list); }

                    /** @var list<int> $ints */;
                    $mapped = mapList($ints, Util::listOf(...));
                ',
                'assertions' => [
                    '$mapped===' => 'list<list<int>>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:MethodTemplatesBoundByArrayMap' => [
                'code' => '<?php
                    final class Util {
                        /**
                         * @template V
                         * @param V $v
                         * @return list<V>
                         */
                        public static function listOf(mixed $v): array { return [$v]; }

                        /**
                         * @template V
                         * @param V $v
                         * @return list<V>
                         */
                        public function instanceListOf(mixed $v): array { return [$v]; }
                    }

                    /** @var list<int> $ints */;
                    $util = new Util();
                    $static_mapped = array_map(Util::listOf(...), $ints);
                    $instance_mapped = array_map($util->instanceListOf(...), $ints);
                ',
                'assertions' => [
                    '$static_mapped===' => 'list<list<int>>',
                    '$instance_mapped===' => 'list<list<int>>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:MethodTemplatesBoundWhenTheArgumentEscapes' => [
                'code' => '<?php
                    final class O {
                        /**
                         * @template T
                         * @param T $t
                         * @return T
                         */
                        public function ident(mixed $t): mixed { return $t; }
                    }

                    /**
                     * @template F
                     * @param F $f
                     * @return F
                     */
                    function tap(mixed $f): mixed { return $f; }

                    $o = new O();
                    $t = tap($o->ident(...));
                    $t(5);
                ',
                'assertions' => [
                    '$t===' => 'Closure[impure](mixed):mixed',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:ChainedMethodTemplateBounds' => [
                'code' => '<?php
                    final class Model {}

                    /** @template T of Model */
                    final class Container {
                        /**
                         * @template U of T
                         * @template V of U
                         * @param V $value
                         * @return V
                         */
                        public function identity(Model $value): Model { return $value; }

                        /**
                         * @template A
                         * @template B of A
                         * @param A $a
                         * @param B $b
                         * @return list<B>
                         */
                        public static function chained(mixed $a, mixed $b): array { return [$b]; }
                    }

                    /** @var Container<Model> $container */;
                    $identity = $container->identity(...);
                    $model = $identity(new Model());
                    $chained = Container::chained(...);
                    $list = $chained(1, 2);
                ',
                'assertions' => [
                    '$identity===' => 'Closure[impure](Model):Model',
                    '$model===' => 'Model',
                    '$chained===' => 'Closure[impure](mixed, mixed):list<mixed>',
                    '$list===' => 'list<mixed>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:UnresolvedClassTemplates' => [
                'code' => '<?php
                    /** @template T of scalar */
                    final class Box {
                        /** @param T $v */
                        public function __construct(private mixed $v) {}

                        /** @return T */
                        public function get(): mixed { return $this->v; }

                        /** @return Closure():T */
                        public function getter(): Closure { return $this->get(...); }
                    }

                    /**
                     * @template T of scalar
                     * @param Box<T> $box
                     * @return Closure():T
                     */
                    function getter(Box $box): Closure {
                        return $box->get(...);
                    }

                    /** @var Box $raw */;
                    $raw_get = $raw->get(...);
                ',
                'assertions' => [
                    '$raw_get===' => 'Closure[read-props]():scalar',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:InheritedTemplatesAndInterface' => [
                'code' => '<?php
                    /** @template T */
                    class Collection {
                        /** @return T */
                        public function first(): mixed { throw new Exception(); }
                    }

                    /** @extends Collection<string> */
                    final class Strings extends Collection {}

                    /** @template T */
                    interface Repository {
                        /** @return T */
                        public function find(int $id): mixed;

                        public function fresh(): static;
                    }

                    /** @var Strings $strings */;
                    /** @var Repository<int> $repository */;
                    $first = $strings->first(...);
                    $find = $repository->find(...);
                    $fresh = $repository->fresh(...);
                ',
                'assertions' => [
                    '$first===' => 'Closure[impure]():string',
                    '$find===' => 'Closure[impure](int):int',
                    '$fresh===' => 'Closure[impure]():Repository<int>&static',
                ],
                'ignored_issues' => ['MissingAbstractPureAnnotation'],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:PassedToTemplatedCallableParam' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @return T */
                        public function get(): mixed { throw new Exception(); }

                        /**
                         * @template U
                         * @param U $u
                         * @return list<U>
                         */
                        public function wrap(mixed $u): array { return [$u]; }
                    }

                    /**
                     * @template R
                     * @param callable():R $f
                     * @return R
                     */
                    function call(callable $f): mixed { return $f(); }

                    /**
                     * @template A
                     * @template B
                     * @param list<A> $list
                     * @param callable(A):B $f
                     * @return list<B>
                     */
                    function mapList(array $list, callable $f): array { return array_map($f, $list); }

                    /** @var Box<int> $box */;
                    /** @var list<int> $ints */;
                    $result = call($box->get(...));
                    $lists = mapList($ints, $box->wrap(...));
                ',
                'assertions' => [
                    '$result===' => 'int',
                    '$lists===' => 'list<list<int>>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InstanceMethod:LiteralName' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @return T */
                        public function get(): mixed { throw new Exception(); }
                    }

                    /** @var Box<int> $box */;
                    $get = $box->{"get"}(...);
                ',
                'assertions' => [
                    '$get===' => 'Closure[impure]():int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:StaticMethod:ClassTemplatesAndStatic' => [
                'code' => '<?php
                    /** @template T */
                    abstract class Collection {
                        /** @return T */
                        public static function one(): mixed { throw new Exception(); }

                        public static function make(): static { throw new Exception(); }
                    }

                    /** @extends Collection<int> */
                    class Ints extends Collection {
                        public function test(): void {
                            $static_make = static::make(...);
                            /** @psalm-check-type-exact $static_make = Closure():(Collection&static) */;
                            $self_make = self::make(...);
                            /** @psalm-check-type-exact $self_make = Closure():(Collection&static) */;
                            $parent_one = parent::one(...);
                            /** @psalm-check-type-exact $parent_one = Closure():int */;
                        }
                    }

                    final class FinalInts extends Ints {}

                    $one = Ints::one(...);
                    $literal_one = Ints::{"one"}(...);
                    $make = Ints::make(...);
                    $final_make = FinalInts::make(...);
                ',
                'assertions' => [
                    '$one===' => 'Closure[impure]():int',
                    '$literal_one===' => 'Closure[impure]():int',
                    '$make===' => 'Closure[impure]():Ints',
                    '$final_make===' => 'Closure[impure]():FinalInts',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:MixinAndPseudoMethods' => [
                'code' => '<?php
                    /** @template T */
                    final class Inner {
                        /** @return T */
                        public function get(): mixed { throw new Exception(); }

                        /** @param T $t */
                        public function put(mixed $t): void {}
                    }

                    /**
                     * @template T
                     * @mixin Inner<T>
                     */
                    final class Outer {
                        public function __call(string $name, array $args): mixed { return null; }
                    }

                    /**
                     * @template T
                     * @method T pseudoGet()
                     * @method static T pseudoOne()
                     */
                    class Magic {
                        public function __call(string $name, array $args): mixed { return null; }
                        public static function __callStatic(string $name, array $args): mixed { return null; }
                    }

                    /** @extends Magic<int> */
                    final class IntMagic extends Magic {}

                    /** @var Outer<float> $outer */;
                    /** @var IntMagic $magic */;
                    $mixin_get = $outer->get(...);
                    $mixin_put = $outer->put(...);
                    $pseudo_get = $magic->pseudoGet(...);
                    $pseudo_one = IntMagic::pseudoOne(...);
                ',
                'assertions' => [
                    '$mixin_get===' => 'Closure[impure]():float',
                    '$mixin_put===' => 'Closure[impure](float):void',
                    '$pseudo_get===' => 'Closure[impure]():int',
                    '$pseudo_one===' => 'Closure[impure]():int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:InheritedMethodReturningParent' => [
                'code' => '<?php
                    class Base {}

                    class A extends Base {
                        public function m(): parent { return new Base; }

                        /** @return list<parent> */
                        public function l(): array { return [new Base]; }
                    }

                    final class B extends A {}

                    /** @var B $b */;
                    $direct = $b->m();
                    $fcc = $b->m(...);
                    $literal = $b->{"m"}(...);
                    $literal_list = $b->{"l"}(...);
                ',
                'assertions' => [
                    '$direct===' => 'Base',
                    '$fcc===' => 'Closure[impure]():Base',
                    '$literal===' => 'Closure[impure]():Base',
                    '$literal_list===' => 'Closure[impure]():list<Base>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:ComparedToAMixedValue' => [
                'code' => <<<'PHP'
                    <?php
                        function useIt(mixed $x, Closure $callback): void {
                            if ($x === $callback(...)) {
                                $x();
                            }
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
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
            'returnByReferenceVariableInClosure' => [
                'code' => '<?php
                    function &(): int {
                        /** @var int $x */
                        static $x = 1;
                        return $x;
                    };
                ',
            ],
            'returnByReferenceVariableInShortClosure' => [
                'code' => '<?php
                    fn &(int &$x): int => $x;
                ',
            ],
            'arrowFunctionArg' => [
                'code' => '<?php
                /** @var array<string,array{o:int, s:array<int, string>}> $existingIssue */
                array_reduce(
                    $existingIssue,
                    /**
                     * @param array{o:int, s:array<int, string>} $existingIssue
                     */
                    static fn(int $carry, array $existingIssue): int => $carry + $existingIssue["o"],
                    0,
                );
                ',
            ],
            'closureInArray' => [
                'code' => '<?php
                    $arr = [
                        /**
                         * @param array{last_access: int} $item
                         */
                        static function (array $item): int {
                            return $item["last_access"];
                        },
                    ];

                    $arr[0](["last_access" => 0]);',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
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
                'error_levels' => [],
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
            'undefinedVariableInInterpolatedString' => [
                'code' => '<?php
                    fn(): string => "$a";
                ',
                'error_message' => 'UndefinedVariable',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'undefinedVariableInStringCast' => [
                'code' => '<?php
                    fn(): string => (string) $a;
                ',
                'error_message' => 'UndefinedVariable',
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
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
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:ParentMagicInstanceMethodInStaticContext' => [
                'code' => '<?php
                    /** @method string has(string $s) */
                    class Base {
                        public function __call(string $name, array $args): string { return "x"; }
                    }
                    final class Child extends Base {
                        public static function test(): void {
                            $fcc = parent::has(...);
                        }
                    }
                ',
                'error_message' => 'UndefinedMethod',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:ParentMagicInstanceMethodWithoutCall' => [
                'code' => '<?php
                    /** @method string has(string $s) */
                    class Base {}
                    final class Child extends Base {
                        public function test(): void {
                            $fcc = parent::has(...);
                        }
                    }
                ',
                'error_message' => 'UndefinedMethod',
                'error_levels' => [],
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
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'FirstClassCallable:WithNew' => [
                'code' => <<<'PHP'
                    <?php
                        new stdClass(...);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'FirstClassCallable:AsAttributeArgument' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public mixed $a = null) {}
                        }
                        #[Attr(...)]
                        function f(): void {}
                    PHP,
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:7 - Cannot create Closure as attribute argument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'returnByReferenceNonVariableInClosure' => [
                'code' => '<?php
                    function &(): int {
                        return 45;
                    };
                ',
                'error_message' => 'NonVariableReferenceReturn',
            ],
            'returnByReferenceNonVariableInShortClosure' => [
                'code' => '<?php
                    fn &(): int => 45;
                ',
                'error_message' => 'NonVariableReferenceReturn',
            ],
        ];
    }
}
