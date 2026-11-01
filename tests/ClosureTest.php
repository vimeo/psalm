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
            'PartialApplication:NamedFunction' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add_one = add(1, ?);
                        $result = $add_one(2);
                    PHP,
                'assertions' => [
                    '$add_one===' => 'Closure[impure](int):int',
                    '$result===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BuiltInFunction' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = strlen(?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[pure](string):int<0, max>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholdersAreRequired' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b = 2): int {
                            return $a + $b;
                        }
                        $closure = add(?, ?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int, int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholdersKeepTheirOrder' => [
                'code' => <<<'PHP'
                    <?php
                        function format(int $a, string $b, float $c, bool $d = false): string {
                            return "$a$b$c$d";
                        }
                        $closure = format(c: ?, a: ?, b: "b");
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](float, int):string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderKeepsTheRest' => [
                'code' => <<<'PHP'
                    <?php
                        function format(int $a, string $b, float $c, bool $d = false): string {
                            return "$a$b$c$d";
                        }
                        $rest = format(1, ...);
                        $named_and_rest = format(?, c: 1.5, ...);
                    PHP,
                'assertions' => [
                    '$rest===' => 'Closure[impure](string, float, bool=):string',
                    '$named_and_rest===' => 'Closure[impure](int, string, bool=):string',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:Thunk' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $thunk = add(1, 2, ...);
                    PHP,
                'assertions' => [
                    '$thunk===' => 'Closure[impure]():int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicFunction' => [
                'code' => <<<'PHP'
                    <?php
                        function sum(int $a, int $b = 0, float ...$rest): float {
                            return $a + $b + array_sum($rest);
                        }
                        $keeps_variadic = sum(1, ...);
                        $fills_variadic = sum(?, ?, ?, ?);
                        $binds_variadic = sum(1, 2, 3.5, ...);
                    PHP,
                'assertions' => [
                    '$keeps_variadic===' => 'Closure[impure](int=, float...):float',
                    '$fills_variadic===' => 'Closure[impure](int, int, float, float):float',
                    '$binds_variadic===' => 'Closure[impure](float...):float',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:InstanceMethod' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        $closure = (new A)->add(?, 2);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:StaticMethod' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public static function make(int $a, string $b = ""): self {
                                return new self();
                            }
                        }
                        $closure = A::make(?);
                        $object = $closure(1);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):A',
                    '$object===' => 'A',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnknownCallee' => [
                'code' => <<<'PHP'
                    <?php
                        function f(callable $callable, int $a): void {
                            $closure = $callable($a, ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:InAssertionScrapedPositions' => [
                'code' => <<<'PHP'
                    <?php
                        $a = count(?) > 0;
                        $b = count(?) === 1;
                        $c = gettype(?) === "string";
                        $d = get_class(?) === "stdClass";
                        $e = substr(?, 1) === "x";
                        if (is_int(?)) {}
                        if (!is_string(?)) {}
                        define(?, 1);
                        $f = match (count(?)) { default => 1 };
                    PHP,
                'assertions' => [],
                'ignored_issues' => [
                    'TypeDoesNotContainType',
                    'RedundantCondition',
                ],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundArgumentSideEffectsAreKept' => [
                'code' => <<<'PHP'
                    <?php
                        function combine(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = combine($assigned = 1, ?);
                    PHP,
                'assertions' => [
                    '$assigned===' => '1',
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:AssertionOfTheCalleeDoesNotNarrowBoundArguments' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @psalm-assert int $x
                         */
                        function assertInt(mixed $x, int $other): void {}

                        function f(mixed $x): void {
                            $closure = assertInt($x, ?);
                            /** @psalm-check-type-exact $x = mixed */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:ImpureCalleeDoesNotInvalidatePropertyNarrowing' => [
                'code' => <<<'PHP'
                    <?php
                        final class A {
                            public ?int $prop = null;
                            public function reset(int $a, int $b): void {
                                $this->prop = null;
                            }
                        }
                        function f(A $a): void {
                            if ($a->prop === null) {
                                return;
                            }
                            $closure = $a->reset(1, ?);
                            $prop = $a->prop;
                            /** @psalm-check-type-exact $prop = int */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:CalleeIsEvaluatedOnce' => [
                'code' => <<<'PHP'
                    <?php
                        /** @var list<Closure(int, int): int> $functions */
                        $functions = [];
                        $index = 0;
                        $closure = ($functions[$index++])(?, 1);
                    PHP,
                'assertions' => [
                    '$index===' => '1',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnionOfClosuresIsNotNarrowedToOne' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @param Closure(int): int|Closure(string): string $closure
                         */
                        function f(Closure $closure): void {
                            $partial = $closure(?);
                            /** @psalm-check-type-exact $partial = Closure */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:ClassTemplateMethodsOfAGenericReceiver' => [
                'code' => <<<'PHP'
                    <?php
                        /** @template T */
                        final class Collection {
                            /** @param list<T> $items */
                            public function __construct(private array $items) {}
                            /**
                             * @template A
                             * @param A $initial
                             * @param callable(A, T): A $callback
                             * @return A
                             */
                            public function reduce(mixed $initial, callable $callback): mixed {
                                return array_reduce($this->items, $callback, $initial);
                            }
                            /** @param callable(T, int): bool $predicate */
                            public function filter(callable $predicate): void {}
                            /**
                             * @template U
                             * @param callable(T): U $callback
                             * @return Collection<U>
                             */
                            public function map(callable $callback): self {
                                return new self(array_map($callback, $this->items));
                            }
                        }
                        /** @param Collection<int> $ints */
                        function f(Collection $ints): void {
                            $reduce = $ints->reduce(0, ?);
                            $filter = $ints->filter(?);
                            $map = $ints->map(?);
                            $map(fn(int $i): string => (string) $i);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['UnusedVariable'],
                'php_version' => '8.6',
            ],
            'PartialApplication:TemplateFunctionWithABoundCallable' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @template T
                         * @template U
                         * @param callable(T): U $callback
                         * @param list<T> $items
                         * @return list<U>
                         */
                        function map(callable $callback, array $items): array {
                            return array_map($callback, $items);
                        }
                        $closure = map(fn(int $i): int => $i * 2, ?);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderLeavesTheFormatOpen' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = sprintf('%s', ...);
                        $named = sprintf(format: '%s', ...);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:FunctionWithTemplatesIsAPlainClosure' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @template T
                         * @param T $value
                         * @return T
                         */
                        function identity(mixed $value): mixed {
                            return $value;
                        }
                        $identity = identity(?);
                        $identity(5);
                    PHP,
                'assertions' => [
                    '$identity===' => 'Closure[impure]',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundArgumentIsAnalyzedOnce' => [
                'code' => <<<'PHP'
                    <?php
                        function needsOne(int $a, int $b): int {
                            return $a + $b;
                        }
                        $i = 0;
                        $closure = needsOne(++$i, ?);
                    PHP,
                'assertions' => [
                    '$i===' => '1',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundVariableOfAReferenceParameterIsDefined' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = preg_match('/a/', 'b', $matches, ...);
                        echo gettype($matches);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicParameterStaysOpenWhenBoundByName' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, string ...$rest): void {}
                        $closure = example(?, rest: 'bound', ...);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int, string...):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:StaticReturnIsTheClassOfTheReceiver' => [
                'code' => <<<'PHP'
                    <?php
                        class P {
                            public static function create(int $a): static {
                                return new static();
                            }
                            public function me(int $a): static {
                                return $this;
                            }
                        }
                        final class Q extends P {}
                        $static = Q::create(?);
                        $method = (new Q())->me(?);
                    PHP,
                'assertions' => [
                    '$static===' => 'Closure[impure](int):Q',
                    '$method===' => 'Closure[impure](int):Q',
                ],
                'ignored_issues' => ['UnsafeInstantiation'],
                'php_version' => '8.6',
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
            'PartialApplication:ParentOfAnInstanceMethodInAnInstanceContext' => [
                'code' => <<<'PHP'
                    <?php
                        class Base {
                            public function run(int $a, int $b): void {}
                        }
                        class Child extends Base {
                            public function go(): Closure {
                                return parent::run(1, ?);
                            }
                            public function again(): Closure {
                                return self::run(1, ?);
                            }
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:FunctionWithSeveralSignaturesInTheCallMap' => [
                'code' => <<<'PHP'
                    <?php
                        $max = max(?);
                        $named = max(value: ?);
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:SignatureOfFunctionsWithByReferenceParameters' => [
                'code' => <<<'PHP'
                    <?php
                        $sort = sort(?);
                        $replace = str_replace('a', 'b', ?);
                        $exists = array_key_exists(?, ['a' => 1]);
                    PHP,
                'assertions' => [
                    '$sort===' => 'Closure[pure](array<array-key, mixed>):bool',
                    '$replace===' => 'Closure[write-refs](array<array-key, float|int|string>|string)'
                        . ':(array<array-key, string>|string)',
                    '$exists===' => 'Closure[pure](array-key):bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:CalleeDoesNotMakeTheEnclosingClosureImpure' => [
                'code' => <<<'PHP'
                    <?php
                        final class Counter {
                            public int $n = 0;
                            public function bump(int $by, int $times): void {
                                $this->n += $by * $times;
                            }
                        }
                        $partial = static fn(Counter $counter): Closure => $counter->bump(1, ?);
                        $first_class = static fn(Counter $counter): Closure => $counter->bump(...);
                    PHP,
                'assertions' => [
                    '$partial===' => 'Closure[pure](Counter):Closure[impure](int):void',
                    '$first_class===' => 'Closure[pure](Counter):Closure[impure](int, int):void',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:AsABoundArgumentOfAnotherOne' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = add(add(1, ?)(2), ?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnknownNamedArgumentIsNotAlsoOneTooMany' => [
                'code' => <<<'PHP'
                    <?php
                        abstract class A {
                            abstract public function add(int $a, int $b): int;
                        }
                        function f(A $a): void {
                            $closure = $a->add(c: 1, ...);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['InvalidNamedArgument', 'UnusedVariable'],
                'php_version' => '8.6',
            ],
            'PartialApplication:InCoalesceLeftOperand' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = add(1, ?) ?? null;
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int|null',
                ],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.6',
            ],
            'PartialApplication:ConstantExpressions' => [
                'code' => <<<'PHP'
                    <?php
                        /** @pure */
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        #[Attribute]
                        final class Attr {
                            public function __construct(public mixed $a = null) {}
                        }
                        const ADD = add(1, ?);
                        final class C {
                            public const ADD = add(2, ?);
                            public mixed $p = add(3, ?);
                            public function __construct(public mixed $q = add(4, ?)) {}
                            #[Attr(add(5, ?))]
                            public function m(mixed $r = add(6, ?)): mixed {
                                static $s = add(7, ?);
                                return $s;
                            }
                        }
                        $add = ADD;
                    PHP,
                'assertions' => [
                    '$add===' => 'Closure[pure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:MagicMethodWithPseudoMethod' => [
                'code' => <<<'PHP'
                    <?php
                        /** @method int add(int $a, int $b) */
                        final class M {
                            public function __call(string $name, array $args): mixed {
                                return 1;
                            }
                        }
                        $closure = (new M())->add(1, ?);
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundPropertyOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        final class P {
                            public ?array $out = null;
                        }
                        function fill(int $n, ?array &$out, int $x): void {
                            $out = [$n, $x];
                        }
                        $p = new P();
                        $closure = fill(1, $p->out, ?);
                        $out = $p->out;
                    PHP,
                'assertions' => [
                    '$closure===' => 'Closure[impure](int):void',
                    '$out===' => 'array<array-key, mixed>|null',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:CallmapFunctionWithAStub' => [
                'code' => <<<'PHP'
                    <?php
                        $replace = str_replace(subject: ?, search: 'a', replace: 'b');
                    PHP,
                'assertions' => [
                    '$replace===' => 'Closure[write-refs](array<array-key, float|int|string>|string):(array<array-key, string>|string)',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundReferencesKeepTheirValue' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            public int|string $value = 'before';
                            public static int|string $static = 'before';
                        }
                        function f(H $h): void {
                            $items = ['k' => 'before'];
                            $property = fill($h->value, ?);
                            $static = fill(H::$static, ?);
                            $element = fill($items['k'], ?);
                            /** @psalm-check-type-exact $h->value = int|string */
                            /** @psalm-check-type-exact H::$static = int|string */
                            /** @psalm-check-type-exact $items = array{k: 'before'|int} */
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundPropertyOfAReferenceParameterIsNotWritten' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            public string $value = 'before';
                        }
                        /** @psalm-capabilities read-props */
                        function factory(H $h): Closure {
                            return fill($h->value, ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundItemOfAPropertyIsNotWritten' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            /** @var list<string> */
                            public array $items = ['before'];
                        }
                        /** @psalm-capabilities read-props */
                        function factory(H $h): Closure {
                            return fill($h->items[0], ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundItemOfAStaticPropertyIsNotWritten' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class H {
                            /** @var list<string> */
                            public static array $items = ['before'];
                        }
                        /** @psalm-capabilities read-globals */
                        function factory(): Closure {
                            return fill(H::$items[0], ?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundMissingOffsetOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        $items = [];
                        $closure = fill($items['k'], ?);
                    PHP,
                'assertions' => [
                    '$items===' => 'array{k: int|null}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeInTheClassOfAStaticCall' => [
                'code' => <<<'PHP'
                    <?php
                        final class G {
                            public static function go(int $a): int {
                                return $a;
                            }
                        }
                        final class O {
                            /** @var class-string<G> */
                            public string $p = G::class;
                        }
                        function f(O $o): Closure {
                            return $o?->p::go(?);
                        }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['RedundantCondition', 'TypeDoesNotContainNull'],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholderAsAttributeArgumentLeavesTheConstructorUnchecked' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a, public int $b = 0) {}
                        }
                        #[Attr(?)]
                        function f(): void {}
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['ParseError'],
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
            'PartialApplication:BeforePhp86' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add_one = add(1, ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'PartialApplication:WithNew' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public function __construct(public int $a, public int $b) {}
                        }
                        $make = new A(1, ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:WithNullsafeMethodCall' => [
                'code' => <<<'PHP'
                    <?php
                        class A {
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(?A $a): void {
                            $add_one = $a?->add(1, ?);
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:TooManyPlaceholders' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add = add(?, ?, ?);
                    PHP,
                'error_message' => 'TooManyArguments',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:MissingRequiredArgument' => [
                'code' => <<<'PHP'
                    <?php
                        function add(int $a, int $b): int {
                            return $a + $b;
                        }
                        $add = add(b: ?);
                    PHP,
                'error_message' => 'TooFewArguments',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundArgumentIsEvaluated' => [
                'code' => <<<'PHP'
                    <?php
                        function accept(int $a, int $b): int {
                            return $a + $b;
                        }
                        $closure = accept(does_not_exist(), ?);
                    PHP,
                'error_message' => 'UndefinedFunction',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderOfVariadicParameter' => [
                'code' => <<<'PHP'
                    <?php
                        function values(int ...$args): void {}
                        $closure = values(args: ?);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderOfUnknownParameter' => [
                'code' => <<<'PHP'
                    <?php
                        function values(int ...$args): void {}
                        $closure = values(x: ?);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NewInMethodOfClassWithPrivateConstructorProperties' => [
                'code' => <<<'PHP'
                    <?php
                        final class A {
                            public function __construct(private int $a, private readonly int $b) {}
                            public function make(): void {
                                $closure = new self(1, ?);
                            }
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentOfCallableWithUnnamedParameters' => [
                'code' => <<<'PHP'
                    <?php
                        /**
                         * @param callable(int, int=): int $callable
                         */
                        function f(callable $callable): void {
                            $named_value = $callable(a: 1, ...);
                            $named_placeholder = $callable(b: ?, ...);
                        }
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderBeforePlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(..., ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderBeforeArgument' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(..., a: 1);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:VariadicPlaceholderTwice' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(?, ..., ...);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnpackingBeforePlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(...[1], ?);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UnpackingAfterPlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function f(int $a, int $b): void {}
                        $closure = f(?, ...[1]);
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderLeavesRequiredParametersOfAFunctionWithByReferenceParameters' => [
                'code' => <<<'PHP'
                    <?php
                        $closure = str_replace(subject: ?);
                    PHP,
                'error_message' => 'TooFewArguments',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeInTheReceiverChain' => [
                'code' => <<<'PHP'
                    <?php
                        final class Node {
                            public ?Node $next = null;
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(?Node $node): void {
                            $closure = $node?->next->add(1, ?);
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentOverwritesAPlaceholder' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, int $b): void {}
                        $closure = example(?, a: 1);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedPlaceholderOverwritesAnArgument' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, int $b): void {}
                        $closure = example(1, a: ?);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentRepeated' => [
                'code' => <<<'PHP'
                    <?php
                        function example(int $a, int $b): void {}
                        $closure = example(a: 1, a: ?, ...);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NamedArgumentRepeatedForAVariadicParameter' => [
                'code' => <<<'PHP'
                    <?php
                        function values(int ...$rest): void {}
                        $closure = values(x: 1, x: 2, ...);
                    PHP,
                'error_message' => 'InvalidNamedArgument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeBeforeAParenthesis' => [
                'code' => <<<'PHP'
                    <?php
                        final class Node {
                            public ?Node $next = null;
                            public function add(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(?Node $node): void {
                            $closure = ($node?->next)->add(1, ?);
                        }
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholderAsAttributeArgument' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a = 0) {}
                        }
                        #[Attr(?)]
                        function f(): void {}
                    PHP,
                'error_message' => 'ParseError',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:InIncludePath' => [
                'code' => <<<'PHP'
                    <?php
                        include dirname(?, 2);
                    PHP,
                'error_message' => 'UnresolvableInclude',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:UndefinedMagicMethod' => [
                'code' => <<<'PHP'
                    <?php
                        final class Test {
                            public function __call(string $name, array $args): mixed {
                                return null;
                            }
                        }
                        $closure = (new Test())->length(1, ?);
                    PHP,
                'error_message' => 'UndefinedMagicMethod',
                'error_levels' => [],
                'php_version' => '8.6',
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
            'PartialApplication:PlaceholderAsAttributeArgumentOfAClassConstant' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a = 0) {}
                        }
                        final class K {
                            #[Attr(?)]
                            public const int X = 1;
                        }
                    PHP,
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:11 - Cannot create Closure as attribute argument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:PlaceholderAsAttributeArgumentOfAnEnumCase' => [
                'code' => <<<'PHP'
                    <?php
                        #[Attribute]
                        final class Attr {
                            public function __construct(public int $a = 0) {}
                        }
                        enum E {
                            #[Attr(...)]
                            case A;
                        }
                    PHP,
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:11 - Cannot create Closure as attribute argument',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:NullsafeInTheClassOfAStaticCallInTheReceiverChain' => [
                'code' => <<<'PHP'
                    <?php
                        final class G {
                            public static function make(): G {
                                return new G();
                            }
                            public function m(int $a): int {
                                return $a;
                            }
                        }
                        final class O {
                            /** @var class-string<G> */
                            public string $p = G::class;
                        }
                        function f(?O $o): void {
                            $closure = $o?->p::make()->m(?);
                        }
                    PHP,
                'error_message' => 'ParseError - src' . DIRECTORY_SEPARATOR . 'somefile.php:15:20 - Cannot combine nullsafe operator with Closure creation',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:TooManyPlaceholdersOfAMethod' => [
                'code' => <<<'PHP'
                    <?php
                        final class K {
                            public function m(int $a, int $b): int {
                                return $a + $b;
                            }
                        }
                        function f(K $k): void {
                            $closure = $k->m(1, 2, ?);
                        }
                    PHP,
                'error_message' => 'Too many arguments for partial application of K::m - expecting at most 2',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundReadonlyPropertyOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class R {
                            public function __construct(public readonly int $v = 1) {}
                        }
                        function f(R $r): void {
                            $closure = fill($r->v, ?);
                        }
                    PHP,
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:25 - R::$v is marked readonly',
                'error_levels' => [],
                'php_version' => '8.6',
            ],
            'PartialApplication:BoundItemOfAReadonlyPropertyOfAReferenceParameter' => [
                'code' => <<<'PHP'
                    <?php
                        /** @param-out int $x */
                        function fill(mixed &$x, int $v): void {
                            $x = $v;
                        }
                        final class R {
                            /** @param array<string, int> $items */
                            public function __construct(public readonly array $items = []) {}
                        }
                        function f(R $r): void {
                            $closure = fill($r->items['k'], ?);
                        }
                    PHP,
                'error_message' => 'InaccessibleProperty - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:25 - R::$items is marked readonly',
                'error_levels' => [],
                'php_version' => '8.6',
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
