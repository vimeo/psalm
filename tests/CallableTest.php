<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class CallableTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string,error_levels?:list<string>}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'byRefUseVar' => [
                'code' => '<?php
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
            'inferArgFromClassContext' => [
                'code' => '<?php
                    final class Calc
                    {
                        /**
                         * @param Closure(int, int): int $_fn
                         */
                        public function __invoke(Closure $_fn): int
                        {
                            return $_fn(42, 42);
                        }
                    }

                    $calc = new Calc();

                    $a = $calc(fn($a, $b) => $a + $b);',
                'assertions' => [
                    '$a' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'inferArgFromClassContextWithNamedArguments' => [
                'code' => '<?php
                    final class Calc
                    {
                        /**
                         * @param Closure(int, int): int ...$_fn
                         */
                        public function __invoke(Closure ...$_fn): int
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    $calc = new Calc();

                    $a = $calc(
                        foo: fn($_a, $_b) => $_a + $_b,
                        bar: fn($_a, $_b) => $_a + $_b,
                    );',
                'assertions' => [
                    '$a' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'inferArgFromClassContextInGenericContext' => [
                'code' => '<?php
                    /**
                     * @template A
                     */
                    final class ArrayList
                    {
                        /**
                         * @template B
                         * @param Closure(A): B $ab
                         * @return ArrayList<B>
                         */
                        public function map(Closure $ab): ArrayList
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    /**
                     * @template T
                     * @param ArrayList<T> $list
                     * @return ArrayList<array{T}>
                     */
                    function asTupled(ArrayList $list): ArrayList
                    {
                        return $list->map(function ($_a) {
                            return [$_a];
                        });
                    }
                    /** @var ArrayList<int> $a */
                    $a = new ArrayList();
                    $b = asTupled($a);',
                'assertions' => [
                    '$b' => 'ArrayList<list{int}>',
                ],
            ],
            'inferArgByPreviousFunctionArg' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     *
                     * @param iterable<array-key, A> $_collection
                     * @param callable(A): B $_ab
                     * @return list<B>
                     */
                    function map(iterable $_collection, callable $_ab) { return []; }

                    /** @template T */
                    final class Foo
                    {
                        /** @return Foo<int> */
                        public function toInt() { throw new RuntimeException("???"); }
                    }

                    /** @var list<Foo<string>> */
                    $items = [];

                    $inferred = map($items, function ($i) {
                        return $i->toInt();
                    });',
                'assertions' => [
                    '$inferred' => 'list<Foo<int>>',
                ],
            ],
            'inferTemplateForExplicitlyTypedArgByPreviousFunctionArg' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     *
                     * @param iterable<array-key, A> $_collection
                     * @param callable(A): B $_ab
                     * @return list<B>
                     */
                    function map(iterable $_collection, callable $_ab) { return []; }

                    /** @template T */
                    final class Foo
                    {
                        /** @return Foo<int> */
                        public function toInt() { throw new RuntimeException("???"); }
                    }

                    /** @var list<Foo<string>> */
                    $items = [];

                    $inferred = map($items, function (Foo $i) {
                        return $i->toInt();
                    });',
                'assertions' => [
                    '$inferred' => 'list<Foo<int>>',
                ],
            ],
            'doNotInferTemplateForExplicitlyTypedWithPhpdocArgByPreviousFunctionArg' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     *
                     * @param iterable<array-key, A> $_collection
                     * @param callable(A): B $_ab
                     * @return list<B>
                     */
                    function map(iterable $_collection, callable $_ab) { return []; }

                    /** @template T */
                    final class Foo { }

                    /** @var list<Foo<string>> */
                    $items = [];

                    $inferred = map($items,
                        /** @param Foo $i */
                        function ($i) {
                            return $i;
                        }
                    );',
                'assertions' => [
                    '$inferred' => 'list<Foo>',
                ],
            ],
            'inferTemplateOfHighOrderFunctionArgByPreviousArg' => [
                'code' => '<?php
                    /**
                     * @return list<int>
                     */
                    function getList() { throw new RuntimeException("???"); }

                    /**
                     * @template T
                     * @return Closure(T): T
                     */
                    function id() { throw new RuntimeException("???"); }

                    /**
                     * @template A
                     * @template B
                     *
                     * @param list<A> $_items
                     * @param callable(A): B $_ab
                     * @return list<B>
                     */
                    function map(array $_items, callable $_ab) { throw new RuntimeException("???"); }

                    $result = map(getList(), id());
                ',
                'assertions' => [
                    '$result' => 'list<int>',
                ],
            ],
            'inferTemplateOfHighOrderFunctionArgByPreviousArgInClassContext' => [
                'code' => '<?php
                    /**
                     * @template A
                     */
                    final class ArrayList
                    {
                        /**
                         * @template B
                         *
                         * @param callable(A): B $ab
                         * @return ArrayList<B>
                         */
                        public function map(callable $ab) { throw new RuntimeException("???"); }
                    }

                    /**
                     * @return ArrayList<int>
                     */
                    function getList() { throw new RuntimeException("???"); }

                    /**
                     * @template T
                     * @return Closure(T): T
                     */
                    function id() { throw new RuntimeException("???"); }

                    $result = getList()->map(id());
                ',
                'assertions' => [
                    '$result' => 'ArrayList<int>',
                ],
            ],
            'inferTemplateOfHighOrderFunctionFromMethodArgByPreviousArg' => [
                'code' => '<?php
                     final class Ops
                     {
                         /**
                          * @template T
                          * @return Closure(list<T>): T
                          */
                         public function flatten() { throw new RuntimeException("???"); }
                     }
                     /**
                      * @return list<list<int>>
                      */
                     function getList() { throw new RuntimeException("???"); }
                     /**
                      * @template T
                      * @return Closure(list<T>): T
                      */
                     function flatten() { throw new RuntimeException("???"); }
                     /**
                      * @template A
                      * @template B
                      *
                      * @param list<A> $_a
                      * @param callable(A): B $_ab
                      * @return list<B>
                      */
                     function map(array $_a, callable $_ab) { throw new RuntimeException("???"); }

                     $ops = new Ops;
                     $result = map(getList(), $ops->flatten());
                 ',
                'assertions' => [
                    '$result' => 'list<int>',
                ],
            ],
            'inferTemplateOfHighOrderFunctionFromStaticMethodArgByPreviousArg' => [
                'code' => '<?php
                     final class StaticOps
                     {
                         /**
                          * @template T
                          * @return Closure(list<T>): T
                          */
                         public static function flatten() { throw new RuntimeException("???"); }
                     }
                     /**
                      * @return list<list<int>>
                      */
                     function getList() { throw new RuntimeException("???"); }
                     /**
                      * @template T
                      * @return Closure(list<T>): T
                      */
                     function flatten() { throw new RuntimeException("???"); }
                     /**
                      * @template A
                      * @template B
                      *
                      * @param list<A> $_a
                      * @param callable(A): B $_ab
                      * @return list<B>
                      */
                     function map(array $_a, callable $_ab) { throw new RuntimeException("???"); }

                     $result = map(getList(), StaticOps::flatten());
                 ',
                'assertions' => [
                    '$result' => 'list<int>',
                ],
            ],
            'PipeTest' => [
                'code' => '<?php
                     /**
                      * @template A
                      * @template B
                      */
                     final class MapOperator
                     {
                         /**
                          * @param Closure(A): B $ab
                          */
                         public function __construct(private Closure $ab) { }

                         /**
                          * @param list<A> $a
                          * @return list<B>
                          */
                         public function __invoke($a): array
                         {
                             $b = [];

                             foreach ($a as $item) {
                                 $b[] = ($this->ab)($item);
                             }

                             return $b;
                         }
                     }
                     /**
                      * @template A
                      * @template B
                      *
                      * @param Closure(A): B $ab
                      * @return MapOperator<A, B>
                      */
                     function map(Closure $ab): MapOperator
                     {
                         return new MapOperator($ab);
                     }
                     /**
                      * @template A
                      * @template B
                      *
                      * @param A $_a
                      * @param callable(A): B $_ab
                      * @return B
                      */
                     function pipe(array $_a, callable $_ab): array
                     {
                         throw new RuntimeException("???");
                     }
                     $result1 = pipe(
                         ["1", "2", "3"],
                         map(fn ($i) => (int) $i)
                     );
                     $result2 = pipe(
                         ["1", "2", "3"],
                         new MapOperator(fn ($i) => (int) $i)
                     );
                 ',
                'assertions' => [
                    '$result1' => 'list<int>',
                    '$result2' => 'list<int>',
                ],
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'inferPipelineWithPartiallyAppliedFunctions' => [
                'code' => '<?php
                    /**
                     * @template T
                     *
                     * @param callable(T, int): bool $_predicate
                     * @return Closure(list<T>): list<T>
                     */
                    function filter(callable $_predicate): Closure { throw new RuntimeException("???"); }
                    /**
                     * @template A
                     * @template B
                     *
                     * @param callable(A): B $_ab
                     * @return Closure(list<A>): list<B>
                     */
                    function map(callable $_ab): Closure { throw new RuntimeException("???"); }
                    /**
                     * @template T
                     * @return (Closure(list<T>): (non-empty-list<T> | null))
                     */
                    function asNonEmptyList(): Closure { throw new RuntimeException("???"); }
                    /**
                     * @template T
                     * @return Closure(T): T
                     */
                    function id(): Closure { throw new RuntimeException("???"); }

                    /**
                     * @template A
                     * @template B
                     * @template C
                     * @template D
                     * @template E
                     * @template F
                     *
                     * @param A $arg
                     * @param callable(A): B $ab
                     * @param callable(B): C $bc
                     * @param callable(C): D $cd
                     * @param callable(D): E $de
                     * @param callable(E): F $ef
                     * @return F
                     */
                    function pipe4(mixed $arg, callable $ab, callable $bc, callable $cd, callable $de, callable $ef): mixed
                    {
                        return $ef($de($cd($bc($ab($arg)))));
                    }

                    /**
                     * @template TFoo of string
                     * @template TBar of bool
                     */
                    final class Item
                    {
                        /**
                         * @param TFoo $foo
                         * @param TBar $bar
                         */
                        public function __construct(
                           public string $foo,
                           public bool $bar,
                       ) { }
                    }

                    /**
                     * @return list<Item>
                     */
                    function getList(): array { return []; }

                    $result = pipe4(
                        getList(),
                        filter(fn($i) => $i->bar),
                        filter(fn(Item $i) => $i->foo !== "bar"),
                        map(fn($i) => new Item("test: " . $i->foo, $i->bar)),
                        asNonEmptyList(),
                        id(),
                    );',
                'assertions' => [
                    '$result' => 'non-empty-list<Item<string, bool>>|null',
                ],
                'error_levels' => [],
                'php_version' => '8.0',
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
            'varCallableParamReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @return callable
                     */
                    function foo() {
                        return function(string $a): string {
                            return $a . "blah";
                        };
                    }',
            ],
            'callableToClosureArrow' => [
                'code' => '<?php
                    /**
                     * @return callable
                     */
                    function foo() {
                        return fn(string $a): string => $a . "blah";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'callable' => [
                'code' => '<?php
                    function foo(callable $c): void {
                        echo (string)$c();
                    }',
            ],
            'callableClass' => [
                'code' => '<?php
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
            'invokeMethodExists' => [
                'code' => '<?php
                    function call(object $obj): void {
                        if (!method_exists($obj, "__invoke")) {
                            return;
                        }
                        $obj();
                    }',
            ],
            'correctParamType' => [
                'code' => '<?php
                    $take_string = function(string $s): string { return $s; };
                    $take_string("string");',
            ],
            'callableMethodStringCallable' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(callable $c): void {}

                    /** @psalm-suppress MissingParamType */
                    function bar($a, $b) : void {
                        foo([$a, $b]);
                    }',
            ],
            'arrayMapCallableMethod' => [
                'code' => '<?php
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
                    '$a' => 'list{string, string}',
                    '$b' => 'list{string, string}',
                    '$c' => 'list{string, string}',
                    '$d' => 'list{string, string}',
                    '$e' => 'list{string, string}',
                    '$f' => 'list{string, string}',
                ],
            ],
            'arrayCallableMethod' => [
                'code' => '<?php
                    class A {
                        public static function bar(string $a): string {
                            return $a . "b";
                        }
                    }

                    function foo(callable $c): void {}

                    foo(["A", "bar"]);',
            ],
            'callableFunction' => [
                'code' => '<?php
                    function foo(callable $c): void {}

                    foo("trim");',
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
            'possiblyUndefinedFunction' => [
                'code' => '<?php
                      /**
                       * @param string|callable $middlewareOrPath
                       */
                      function pipe($middlewareOrPath, ?callable $middleware = null): void {  }

                    pipe("zzzz", function() : void {});',
            ],
            'callableWithNonInvokable' => [
                'code' => '<?php
                    function asd(): void {}
                    class B {}

                    /**
                     * @param callable|B $p
                     */
                    function passes($p): void {}

                    passes("asd");',
            ],
            'callableWithInvokable' => [
                'code' => '<?php
                    function asd(): void {}
                    class A { public function __invoke(): void {} }

                    /**
                     * @param callable|A $p
                     */
                    function fails($p): void {}

                    fails("asd");',
            ],
            'isCallableArray' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo(): void {}

                    function callMeMaybe(string $method): void {
                        if (is_callable($method)) {
                            $method();
                        }
                    }

                    callMeMaybe("foo");',
            ],
            'allowVoidCallable' => [
                'code' => '<?php
                    /**
                     * @param callable():void $p
                     */
                    function doSomething($p): void {}
                    doSomething(function(): bool { return false; });',
            ],
            'callableProperties' => [
                'code' => '<?php
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
            'nullableReturnTypeShorthand' => [
                'code' => '<?php
                    class A {}
                    /** @param callable(mixed):?A $a */
                    function foo(callable $a): void {}',
            ],
            'callablesCanBeObjects' => [
                'code' => '<?php
                    function foo(callable $c) : void {
                        if (is_object($c)) {
                            $c();
                        }
                    }',
            ],
            'objectsCanBeCallable' => [
                'code' => '<?php
                    function foo(object $c) : void {
                        if (is_callable($c)) {
                            $c();
                        }
                    }',
            ],
            'unionCanBeCallable' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param callable(string, string) : int $p
                     */
                    function f(callable $p): void {}',
            ],
            'fileExistsCallable' => [
                'code' => '<?php
                    /** @return string[] */
                    function foo(string $prospective_file_path) : array {
                        return array_filter(
                            glob($prospective_file_path),
                            "file_exists"
                        );
                    }',
            ],
            'callableSelfArg' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'callableStaticReturn' => [
                'code' => '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @param callable():static $f
                         */
                        function func1(callable $f): void {}
                    }

                    final class C extends B {}

                    $c = new C();

                    $c->func1(function(): C { return new C(); });',
            ],
            'callableSelfReturn' => [
                'code' => '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @param callable():self $f
                         */
                        function func2(callable $f): void {}
                    }

                    final class C extends B {}

                    $b = new B();
                    $c = new C();

                    $b->func2(function() { return new B(); });
                    $c->func2(function() { return new C(); });',
            ],
            'callableParentReturn' => [
                'code' => '<?php
                    class A {}

                    class B extends A {
                        /**
                         * @param callable():parent $f
                         */
                        function func3(callable $f): void {}
                    }

                    $b = new B();

                    $b->func3(function() { return new A(); });',
            ],
            'selfArrayMapCallableWrongClass' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'callableIsArrayAssertion' => [
                'code' => '<?php
                    function foo(callable $c) : void {
                        if (is_array($c)) {
                            echo $c[1];
                        }
                    }',
            ],
            'callableOrArrayIsArrayAssertion' => [
                'code' => '<?php
                    /**
                     * @param callable|array $c
                     */
                    function foo($c) : void {
                        if (is_array($c) && isset($c[1]) && is_string($c[1])) {
                            echo $c[1];
                        }
                    }',
            ],
            'dontInferMethodIdWhenFormatDoesntFit' => [
                'code' => '<?php
                    /** @param string|callable $p */
                    function f($p): array {
                      return [];
                    }
                    f("#b::a");'
            ],
            'removeCallableAssertionAfterReassignment' => [
                'code' => '<?php
                    function foo(string $key) : void {
                        $setter = "a" . $key;
                        if (is_callable($setter)) {
                            return;
                        }
                        $setter = "b" . $key;
                        if (is_callable($setter)) {}
                    }'
            ],
            'noExceptionOnSelfString' => [
                'code' => '<?php
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
            'noFatalErrorOnClassWithSlash' => [
                'code' => '<?php
                    class Func {
                        public function __construct(string $name, callable $callable) {}
                    }

                    class Foo {
                        public static function bar(): string { return "asd"; }
                    }

                    new Func("f", ["\Foo", "bar"]);',
            ],
            'staticReturningCallable' => [
                'code' => '<?php
                    abstract class Id
                    {
                        /**
                         * @var string
                         */
                        private $id;

                        final protected function __construct(string $id)
                        {
                            $this->id = $id;
                        }

                        /**
                         * @return static
                         */
                        final public static function fromString(string $id): self
                        {
                            return new static($id);
                        }
                    }

                    final class CriterionId extends Id
                    {
                    }

                    final class CriterionIds
                    {
                        /**
                         * @psalm-var non-empty-list<CriterionId>
                         */
                        private $ids;

                        /**
                         * @psalm-param non-empty-list<CriterionId> $ids
                         */
                        private function __construct(array $ids)
                        {
                            $this->ids = $ids;
                        }

                        /**
                         * @psalm-param non-empty-list<string> $ids
                         */
                        public static function fromStrings(array $ids): self
                        {
                            return new self(array_map([CriterionId::class, "fromString"], $ids));
                        }
                    }'
            ],
            'offsetOnCallable' => [
                'code' => '<?php
                    function c(callable $c) : void {
                        if (is_array($c)) {
                            new ReflectionClass($c[0]);
                        }
                    }'
            ],
            'destructureCallableArray' => [
                'code' => '<?php
                    function getCallable(): callable {
                        return [DateTimeImmutable::class, "createFromFormat"];
                    }

                    $callable = getCallable();

                    if (!is_array($callable)) {
                      exit;
                    }

                    [$classOrObject, $method] = $callable;',
                'assertions' => [
                    '$classOrObject' => 'class-string|object',
                    '$method' => 'string'
                ]
            ],
            'callableInterface' => [
                'code' => '<?php
                    interface CallableInterface{
                        public function __invoke(): bool;
                    }

                    function takesInvokableInterface(CallableInterface $c): void{
                        takesCallable($c);
                    }

                    function takesCallable(callable $c): void {
                        $c();
                    }'
            ],
            'notCallableArrayNoUndefinedClass' => [
                'code' => '<?php
                    /**
                     * @psalm-param array|callable $_fields
                     */
                    function f($_fields): void {}

                    f(["instance_date" => "ASC", "start_time" => "ASC"]);'
            ],
            'callOnInvokableOrCallable' => [
                'code' => '<?php
                    interface Callback {
                        public function __invoke(): void;
                    }

                    /** @var Callback|callable */
                    $test = function (): void {};

                    $test();'
            ],
            'resolveTraitClosureReturn' => [
                'code' => '<?php
                    class B {
                        /**
                         * @psalm-param callable(mixed...):static $i
                         */
                        function takesACall(callable $i) : void {}

                        public function call() : void {
                            $this->takesACall(function() {return $this;});
                        }
                    }'
            ],
            'returnClosureReturningStatic' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class C {
                        /**
                         * @return Closure():static
                         */
                        public static function foo() {
                            return function() {
                                return new static();
                            };
                        }
                    }',
            ],
            'returnsVoidAcceptableForNullable' => [
                'code' => '<?php
                    /** @param callable():?bool $c */
                    function takesCallable(callable $c) : void {}

                    takesCallable(function() { return; });',
            ],
            'byRefUsesAlwaysMixed' => [
                'code' => '<?php
                    $callback = function() use (&$isCalled) : void {
                        $isCalled = true;
                    };
                    $isCalled = false;
                    $callback();

                    if ($isCalled === true) {}'
            ],
            'notCallableListNoUndefinedClass' => [
                'code' => '<?php
                    /**
                     * @param array|callable $arg
                     */
                    function foo($arg): void {}

                    foo(["a", "b"]);'
            ],
            'abstractInvokeInTrait' => [
                'code' => '<?php
                    function testFunc(callable $func) : void {}

                    trait TestTrait {
                        abstract public function __invoke() : void;

                        public function apply() : void {
                            testFunc($this);
                        }
                    }

                    abstract class TestClass {
                        use TestTrait;
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedCallableClass' => [
                'code' => '<?php
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
                'ignored_issues' => ['UndefinedClass', 'MixedInferredReturnType'],
            ],
            'undefinedCallableMethodFullString' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(callable $c): void {}

                    foo("trime");',
                'error_message' => 'UndefinedFunction',
            ],
            'stringFunctionCall' => [
                'code' => '<?php
                    $bad_one = "hello";
                    $a = $bad_one(1);',
                'error_message' => 'MixedAssignment',
            ],
            'wrongCallableReturnType' => [
                'code' => '<?php
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
            'checkCallableTypeString' => [
                'code' => '<?php
                    /**
                     * @param callable(int,int):int $_p
                     */
                    function f(callable $_p): void {}

                    f("strcmp");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'checkCallableTypeArrayInstanceFirstArg' => [
                'code' => '<?php
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
                'code' => '<?php
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
            'callableWithSpaceAfterColonBadVarArg' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class one { public function two(string $_p): void {} }
                    array_map(["two", "three"], ["one", "two"]);',
                'error_message' => 'InvalidArgument',
            ],
            'noFatalErrorOnMissingClassWithSlash' => [
                'code' => '<?php
                    class Func {
                        public function __construct(string $name, callable $callable) {}
                    }

                    new Func("f", ["\Foo", "bar"]);',
                'error_message' => 'InvalidArgument'
            ],
            'noFatalErrorOnMissingClassWithoutSlash' => [
                'code' => '<?php
                    class Func {
                        public function __construct(string $name, callable $callable) {}
                    }

                    new Func("f", ["Foo", "bar"]);',
                'error_message' => 'InvalidArgument'
            ],
            'preventStringDocblockType' => [
                'code' => '<?php
                    /**
                     * @param string $mapper
                     */
                    function map2(callable $mapper): void {}

                    map2("foo");',
                'error_message' => 'MismatchingDocblockParamType',
            ],
            'moreSpecificCallable' => [
                'code' => '<?php
                    /** @param callable(string):void $c */
                    function takesSpecificCallable(callable $c) : void {
                        $c("foo");
                    }

                    function takesCallable(callable $c) : void {
                        takesSpecificCallable($c);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion'
            ],
            'undefinedVarInBareCallable' => [
                'code' => '<?php
                    $fn = function(int $a): void{};
                    function a(callable $fn): void{
                      $fn(++$a);
                    }
                    a($fn);',
                'error_message' => 'UndefinedVariable',
            ],
            'dontQualifyStringCallables' => [
                'code' => '<?php
                    namespace NS;

                    function ff() : void {}

                    function run(callable $f) : void {
                        $f();
                    }

                    run("ff");',
                'error_message' => 'UndefinedFunction',
            ],
            'badCustomFunction' => [
                'code' => '<?php
                    /**
                     * @param callable(int):bool $func
                     */
                    function takesFunction(callable $func) : void {}

                    function myFunction( string $foo ) : bool {
                        return false;
                    }

                    takesFunction("myFunction");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'emptyCallable' => [
                'code' => '<?php
                    $a = "";
                    $a();',
                'error_message' => 'InvalidFunctionCall',
            ],
            'ImpureFunctionCall' => [
                'code' => '<?php
                    /**
                     * @psalm-template T
                     *
                     * @psalm-param array<int, T> $values
                     * @psalm-param (callable(T): numeric) $num_func
                     *
                     * @psalm-return null|T
                     *
                     * @psalm-pure
                     */
                    function max_by(array $values, callable $num_func)
                    {
                        $max = null;
                        $max_num = null;
                        foreach ($values as $value) {
                            $value_num = $num_func($value);
                            if (null === $max_num || $value_num >= $max_num) {
                                $max = $value;
                                $max_num = $value_num;
                            }
                        }

                        return $max;
                    }

                    $c = max_by([1, 2, 3], static function(int $a): int {
                        return $a + mt_rand(0, $a);
                    });

                    echo $c;
                ',
                'error_message' => 'ImpureFunctionCall',
                'ignored_issues' => [],
            ],
            'constructCallableFromClassStringArray' => [
                'code' => '<?php
                    interface Foo {
                        public function bar() : int;
                    }

                    /**
                     * @param callable():string $c
                     */
                    function takesCallableReturningString(callable $c) : void {
                        $c();
                    }

                    /**
                     * @param class-string<Foo> $c
                     */
                    function foo(string $c) : void {
                        takesCallableReturningString([$c, "bar"]);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'inexistantCallableinCallableString' => [
                'code' => '<?php
                    /**
                     * @param callable-string $c
                     */
                    function c(string $c): void {
                        $c();
                    }

                    c("hii");',
                'error_message' => 'InvalidArgument',
            ],
            'mismatchParamTypeFromDocblock' => [
                'code' => '<?php
                    /**
                     * @template A
                     */
                    final class ArrayList
                    {
                        /**
                         * @template B
                         * @param Closure(A): B $effect
                         * @return ArrayList<B>
                         */
                        public function map(Closure $effect): ArrayList
                        {
                            throw new RuntimeException("???");
                        }
                    }

                    /**
                     * @template T
                     * @template B
                     *
                     * @param ArrayList<T> $list
                     * @return ArrayList<array{T}>
                     */
                    function genericContext(ArrayList $list): ArrayList
                    {
                        return $list->map(
                            /** @param B $_a */
                            function ($_a) {
                                return [$_a];
                            }
                        );
                    }',
                'error_message' => 'InvalidArgument',
            ]
        ];
    }
}
