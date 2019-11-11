<?php
namespace Psalm\Tests;

class CallableTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
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
            'callableMethodStringCallable' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo("A::bar");
                    foo(A::class . "::bar");',
            ],
            'callableMethodArrayCallable' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(["A", "bar"]);
                    foo([A::class, "bar"]);
                    $a = new A();
                    foo([$a, "bar"]);',
            ],
            'callableMethodArrayCallableMissingTypes' => [
                '<?php
                    function foo(callable $c): void {}

                    /** @psalm-suppress MissingParamType */
                    function bar($a, $b) : void {
                        foo([$a, $b]);
                    }',
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
                    '$a' => 'array{0: string, 1: string}',
                    '$b' => 'array{0: string, 1: string}',
                    '$c' => 'array{0: string, 1: string}',
                    '$d' => 'array{0: string, 1: string}',
                    '$e' => 'array{0: string, 1: string}',
                    '$f' => 'array{0: string, 1: string}',
                ],
            ],
            'arrayMapClosureVar' => [
                '<?php
                    $mirror = function(int $i) : int { return $i; };
                    $a = array_map($mirror, [1, 2, 3]);',
                'assertions' => [
                    '$a' => 'array{0: int, 1: int, 2: int}',
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
            'returnsTypedCallableFromClosure' => [
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

                    $a = foo(
                        function(B $b) : A { return new A;},
                        function(C $c) : B { return new B;}
                    )(new C);',
                'assertions' => [
                    '$a' => 'A',
                ],
            ],
            'inferClosureTypeWithTypehint' => [
                '<?php
                    $adder1 = function(int $i) : callable {
                      return function(int $j) use ($i) : int {
                        return $i + $j;
                      };
                    };
                    $adder2 = function(int $i) {
                      return function(int $j) use ($i) : int {
                        return $i + $j;
                      };
                    };',
                'assertions' => [
                    '$adder1' => 'Closure(int):Closure(int):int',
                    '$adder2' => 'Closure(int):Closure(int):int',
                ],
                'error_levels' => ['MissingClosureReturnType'],
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
            'allowVoidCallable' => [
                '<?php
                    /**
                     * @param callable():void $p
                     */
                    function doSomething($p): void {}
                    doSomething(function(): bool { return false; });',
            ],
            'callableProperties' => [
                '<?php
                    class C {
                        /** @psalm-var callable():bool */
                        private $callable;

                        /**
                         * @psalm-param callable():bool $callable
                         */
                        public function __construct(callable $callable) {
                            $this->callable = $callable;
                        }

                        public function callTheCallableDirectly(): bool {
                            return ($this->callable)();
                        }

                        public function callTheCallableIndirectly(): bool {
                            $r = $this->callable;
                            return $r();
                        }
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
            'PHP71-mirrorCallableParams' => [
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
                    '$a' => 'Closure():Closure():string(hello)',
                    '$b' => 'string',
                ],
            ],
            'nullableReturnTypeShorthand' => [
                '<?php
                    class A {}
                    /** @param callable(mixed):?A $a */
                    function foo(callable $a): void {}',
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
            'callablesCanBeObjects' => [
                '<?php
                    function foo(callable $c) : void {
                        if (is_object($c)) {
                            $c();
                        }
                    }',
            ],
            'objectsCanBeCallable' => [
                '<?php
                    function foo(object $c) : void {
                        if (is_callable($c)) {
                            $c();
                        }
                    }',
            ],
            'unionCanBeCallable' => [
                '<?php
                    class A {}
                    class B {
                        public function __invoke() : string {
                            return "hello";
                        }
                    }
                    /**
                     * @param A|B $c
                     */
                    function foo($c) : void {
                        if (is_callable($c)) {
                            $c();
                        }
                    }',
            ],
            'goodCallableArgs' => [
                '<?php
                    /**
                     * @param callable(string,string):int $_p
                     */
                    function f(callable $_p): void {}

                    class C {
                        public static function m(string $a, string $b): int { return $a <=> $b; }
                    }

                    f("strcmp");
                    f([new C, "m"]);
                    f([C::class, "m"]);',
            ],
            'callableWithSpaces' => [
                '<?php
                    /**
                     * @param callable(string, string) : int $p
                     */
                    function f(callable $p): void {}',
            ],
            'fileExistsCallable' => [
                '<?php
                    /** @return string[] */
                    function foo(string $prospective_file_path) : array {
                        return array_filter(
                            glob($prospective_file_path),
                            "file_exists"
                        );
                    }',
            ],
            'PHP71-closureFromCallableInvokableNamedClass' => [
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
            'PHP71-closureFromCallableInvokableAnonymousClass' => [
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
            'callableSelfArg' => [
                '<?php
                    class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func2(function(B $x): void {});
                    $c->func2(function(B $x): void {});

                    class A {}

                    class B extends A {
                        /**
                         * @param callable(self) $f
                         */
                        function func2(callable $f): void {
                            $f($this);
                        }
                    }',
            ],
            'callableParentArg' => [
                '<?php
                    class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func3(function(A $x): void {});
                    $c->func3(function(A $x): void {});

                    class A {}

                    class B extends A {
                        /**
                         * @param callable(parent) $f
                         */
                        function func3(callable $f): void {
                            $f($this);
                        }
                    }',
            ],
            'callableStaticArg' => [
                '<?php
                    class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func1(function(B $x): void {});
                    $c->func1(function(C $x): void {});

                    class A {}

                    class B extends A {
                        /**
                         * @param callable(static) $f
                         */
                        function func1(callable $f): void {
                            $f($this);
                        }
                    }',
            ],
            'callableSelfReturn' => [
                '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @param callable():static $f
                         */
                        function func1(callable $f): void {}

                        /**
                         * @param callable():self $f
                         */
                        function func2(callable $f): void {}

                        /**
                         * @param callable():parent $f
                         */
                        function func3(callable $f): void {}
                    }

                    class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func1(function(): B { return new B(); });
                    $c->func1(function(): C { return new C(); });
                    $b->func2(function(): B { return new B(); });
                    $c->func2(function(): B { return new B(); });',
            ],
            'selfArrayMapCallableWrongClass' => [
                '<?php
                    class Foo {
                        public function __construct(int $param) {}

                        public static function foo(int $param): Foo {
                            return new self($param);
                        }
                        public static function baz(int $param): self {
                            return new self($param);
                        }
                    }

                    class Bar {
                        /**
                         * @return array<int, Foo>
                         */
                        public function bar() {
                            return array_map([Foo::class, "foo"], [1,2,3]);
                        }
                        /** @return array<int, Foo> */
                        public function bat() {
                            return array_map([Foo::class, "baz"], [1]);
                        }
                    }',
            ],
            'dynamicCallableArray' => [
                '<?php
                    class A {
                        /** @var string */
                        private $value = "default";

                        private function modify(string $name, string $value): void {
                            call_user_func([$this, "modify" . $name], $value);
                        }

                        public function modifyFoo(string $value): void {
                            $this->value = $value;
                        }
                    }',
            ],
            'PHP71-publicCallableFromInside' => [
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
            'PHP71-protectedCallableFromInside' => [
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
            'callableIsArrayAssertion' => [
                '<?php
                    function foo(callable $c) : void {
                        if (is_array($c)) {
                            echo $c[1];
                        }
                    }',
            ],
            'callableOrArrayIsArrayAssertion' => [
                '<?php
                    /**
                     * @param callable|array $c
                     */
                    function foo($c) : void {
                        if (is_array($c) && is_string($c[1])) {
                            echo $c[1];
                        }
                    }',
            ],
            'allowCallableWithNarrowerReturn' => [
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
            'dontInferMethodIdWhenFormatDoesntFit' => [
                '<?php
                    /** @param string|callable $p */
                    function f($p): array {
                      return [];
                    }
                    f("#b::a");'
            ],
            'removeCallableAssertionAfterReassignment' => [
                '<?php
                    function foo(string $key) : void {
                        $setter = "a" . $key;
                        if (is_callable($setter)) {
                            return;
                        }
                        $setter = "b" . $key;
                        if (is_callable($setter)) {}
                    }'
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
            'noExceptionOnSelfString' => [
                '<?php
                    class Fish {
                        public static function example(array $vals): void {
                            usort($vals, ["self", "compare"]);
                        }

                        /**
                         * @param mixed $a
                         * @param mixed $b
                         */
                        public static function compare($a, $b): int {
                            return -1;
                        }
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
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
                'error_levels' => ['UndefinedClass', 'MixedInferredReturnType'],
            ],
            'undefinedCallableMethodFullString' => [
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
            'undefinedCallableMethodClassConcat' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(A::class . "::barr");',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedCallableMethodArray' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo([A::class, "::barr"]);',
                'error_message' => 'InvalidArgument',
            ],
            'undefinedCallableMethodArrayWithoutClass' => [
                '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(["A", "::barr"]);',
                'error_message' => 'InvalidArgument',
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
                    };',
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
            'checkCallableTypeString' => [
                '<?php
                    /**
                     * @param callable(int,int):int $_p
                     */
                    function f(callable $_p): void {}

                    f("strcmp");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'checkCallableTypeArrayInstanceFirstArg' => [
                '<?php
                    /**
                     * @param callable(int,int):int $_p
                     */
                    function f(callable $_p): void {}

                    class C {
                        public static function m(string $a, string $b): int { return $a <=> $b; }
                    }

                    f([new C, "m"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'checkCallableTypeArrayClassStringFirstArg' => [
                '<?php
                    /**
                     * @param callable(int,int):int $_p
                     */
                    function f(callable $_p): void {}

                    class C {
                        public static function m(string $a, string $b): int { return $a <=> $b; }
                    }

                    f([C::class, "m"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'PHP71-closureFromCallableInvokableNamedClassWrongArgs' => [
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
            'callableWithSpaceAfterColonBadVarArg' => [
                '<?php
                    class C {
                        /**
                         * @var callable(string, string): bool $p
                         */
                        public $p;

                        public function __construct() {
                            $this->p = function (string $s, string $t): stdClass {
                                return new stdClass;
                            };
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'callableWithSpaceBeforeColonBadVarArg' => [
                '<?php
                    class C {
                        /**
                         * @var callable(string, string) :bool $p
                         */
                        public $p;

                        public function __construct() {
                            $this->p = function (string $s, string $t): stdClass {
                                return new stdClass;
                            };
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'callableWithSpacesEitherSideOfColonBadVarArg' => [
                '<?php
                    class C {
                        /**
                         * @var callable(string, string) : bool $p
                         */
                        public $p;

                        public function __construct() {
                            $this->p = function (string $s, string $t): stdClass {
                                return new stdClass;
                            };
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'badArrayMapArrayCallable' => [
                '<?php
                    class one { public function two(string $_p): void {} }
                    array_map(["two", "three"], ["one", "two"]);',
                'error_message' => 'InvalidArgument',
            ],
            'PHP71-privateCallable' => [
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
                'error_message' => 'ArgumentTypeCoercion - src/somefile.php:13:28 - Argument 1 of takesB expects B, parent type A provided',
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
        ];
    }
}
