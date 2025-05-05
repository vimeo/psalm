<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class CallableTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
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
                     */
                    function f() {
                        $data = 0;
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
                        baz: fn($_a, $_b) => $_a + $_b,
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
            'inferArgByPreviousMethodArg' => [
                'code' => '<?php
                    final class ArrayList
                    {
                        /**
                         * @template A
                         * @template B
                         * @template C
                         * @param list<A> $list
                         * @param callable(A): B $first
                         * @param callable(B): C $second
                         * @return list<C>
                         */
                        public function map(array $list, callable $first, callable $second): array
                        {
                            throw new RuntimeException("never");
                        }
                    }
                    $result = (new ArrayList())->map([1, 2, 3], fn($i) => ["num" => $i], fn($i) => ["object" => $i]);',
                'assertions' => [
                    '$result' => 'list<array{object: array{num: int}}>',
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
            'inferInvokableClassCallable' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     */
                    final class MapOperator
                    {
                        /** @var Closure(A): B */
                        private Closure $ab;

                        /**
                         * @param callable(A): B $ab
                         */
                        public function __construct(callable $ab)
                        {
                            $this->ab = Closure::fromCallable($ab);
                        }

                        /**
                         * @template K
                         * @param array<K, A> $a
                         * @return array<K, B>
                         */
                        public function __invoke(array $a): array
                        {
                            $b = [];

                            foreach ($a as $k => $v) {
                                $b[$k] = ($this->ab)($v);
                            }

                            return $b;
                        }
                    }
                    /**
                     * @template A
                     * @template B
                     * @param A $a
                     * @param callable(A): B $ab
                     * @return B
                     */
                    function pipe(mixed $a, callable $ab): mixed
                    {
                        return $ab($a);
                    }
                    /**
                     * @return array<string, int>
                     */
                    function getDict(): array
                    {
                        return ["fst" => 1, "snd" => 2, "thr" => 3];
                    }
                    $result = pipe(getDict(), new MapOperator(fn($i) => ["num" => $i]));
                ',
                'assertions' => [
                    '$result' => 'array<string, array{num: int}>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inferConstCallableLikeFirstClassCallable' => [
                'code' => '<?php
                    namespace Functions {
                        use Closure;

                        final class Module
                        {
                            const id = "Functions\Module::id";
                            /**
                             * @template A
                             * @param A $value
                             * @return A
                             */
                            public static function id(mixed $value): mixed
                            {
                                return $value;
                            }
                        }
                        const classId = Module::id;
                        const id = "Functions\id";
                        /**
                         * @template A
                         * @param A $value
                         * @return A
                         */
                        function id(mixed $value): mixed
                        {
                            return $value;
                        }
                        /**
                         * @template A
                         * @template B
                         * @param callable(A): B $callback
                         * @return Closure(list<A>): list<B>
                         */
                        function map(callable $callback): Closure
                        {
                            return fn(array $list) => array_map($callback, $list);
                        }
                        /**
                         * @template A
                         * @template B
                         * @param A $a
                         * @param callable(A): B $ab
                         * @return B
                         */
                        function pipe1(mixed $a, callable $ab): mixed
                        {
                            return $ab($a);
                        }
                        /**
                         * @template A
                         * @template B
                         * @template C
                         * @param A $a
                         * @param callable(A): B $ab
                         * @param callable(B): C $bc
                         * @return C
                         */
                        function pipe2(mixed $a, callable $ab, callable $bc): mixed
                        {
                            return $bc($ab($a));
                        }
                    }

                    namespace App {
                        use Functions\Module;
                        use function Functions\map;
                        use function Functions\pipe1;
                        use function Functions\pipe2;
                        use const Functions\classId;
                        use const Functions\id;

                        $class_const_id = pipe1([42], Module::id);
                        $class_const_composition = pipe1([42], map(Module::id));
                        $class_const_sequential = pipe2([42], map(fn($i) => ["num" => $i]), Module::id);

                        $class_const_alias_id = pipe1([42], classId);
                        $class_const_alias_composition = pipe1([42], map(classId));
                        $class_const_alias_sequential = pipe2([42], map(fn($i) => ["num" => $i]), classId);

                        $const_id = pipe1([42], id);
                        $const_composition = pipe1([42], map(id));
                        $const_sequential = pipe2([42], map(fn($i) => ["num" => $i]), id);

                        $string_id = pipe1([42], "Functions\id");
                        $string_composition = pipe1([42], map("Functions\id"));
                        $string_sequential = pipe2([42], map(fn($i) => ["num" => $i]), "Functions\id");

                        $class_string_id = pipe1([42], "Functions\Module::id");
                        $class_string_composition = pipe1([42], map("Functions\Module::id"));
                        $class_string_sequential = pipe2([42], map(fn($i) => ["num" => $i]), "Functions\Module::id");
                    }
                ',
                'assertions' => [
                    '$class_const_id===' => 'list{42}',
                    '$class_const_composition===' => 'list<42>',
                    '$class_const_sequential===' => 'list<array{num: 42}>',
                    '$class_const_alias_id===' => 'list{42}',
                    '$class_const_alias_composition===' => 'list<42>',
                    '$class_const_alias_sequential===' => 'list<array{num: 42}>',
                    '$const_id===' => 'list{42}',
                    '$const_composition===' => 'list<42>',
                    '$const_sequential===' => 'list<array{num: 42}>',
                    '$string_id===' => 'list{42}',
                    '$string_composition===' => 'list<42>',
                    '$string_sequential===' => 'list<array{num: 42}>',
                    '$class_string_id===' => 'list{42}',
                    '$class_string_composition===' => 'list<42>',
                    '$class_string_sequential===' => 'list<array{num: 42}>',
                ],
                'ignored_issues' => [],
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
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inferPipelineWithPartiallyAppliedFunctionsAndFirstClassCallable' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $value
                     * @return T
                     */
                    function id(mixed $value): mixed
                    {
                        return $value;
                    }

                    /**
                     * @template A
                     * @template B
                     * @param A $a
                     * @param callable(A): B $ab
                     * @return B
                     */
                    function pipe(mixed $a, callable $ab): mixed
                    {
                        return $ab($a);
                    }

                    /**
                     * @template A
                     * @template B
                     * @param callable(A): B $callback
                     * @return Closure(list<A>): list<B>
                     */
                    function map(callable $callback): Closure
                    {
                        return fn($array) => array_map($callback, $array);
                    }

                    /**
                     * @return list<int>
                     */
                    function getNums(): array
                    {
                        return [];
                    }

                    /**
                     * @template T of float|int
                     */
                    final class ObjectNum
                    {
                        /**
                         * @psalm-param T $value
                         */
                        public function __construct(
                            public readonly float|int $value,
                        ) {}
                    }

                    /**
                     * @return list<ObjectNum<int>>
                     */
                    function getObjectNums(): array
                    {
                        return [];
                    }

                    $id = pipe(getNums(), id(...));
                    $wrapped_id = pipe(getNums(), map(id(...)));
                    $id_nested = pipe(getObjectNums(), map(id(...)));
                    $id_nested_simple = pipe(getObjectNums(), id(...));
                ',
                'assertions' => [
                    '$id' => 'list<int>',
                    '$wrapped_id' => 'list<int>',
                    '$id_nested' => 'list<ObjectNum<int>>',
                    '$id_nested_simple' => 'list<ObjectNum<int>>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferFirstClassCallableWithGenericObject' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     * @param A $a
                     * @param callable(A): B $ab
                     * @return B
                     */
                    function pipe($a, callable $ab)
                    {
                        return $ab($a);
                    }
                    /**
                     * @template A
                     * @psalm-immutable
                     */
                    final class Container
                    {
                        /** @param A $value */
                        public function __construct(
                            public readonly mixed $value,
                        ) {}
                    }
                    /**
                     * @template A
                     * @param Container<A> $container
                     * @return A
                     */
                    function unwrap(Container $container)
                    {
                        return $container->value;
                    }
                    $result = pipe(
                        new Container(42),
                        unwrap(...),
                    );
                ',
                'assertions' => [
                    '$result===' => '42',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferFirstClassCallableOnMethodCall' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     */
                    final class Processor
                    {
                        /**
                         * @param A $a
                         * @param B $b
                         */
                        public function __construct(
                            public readonly mixed $a,
                            public readonly mixed $b,
                        ) {}

                        /**
                         * @template AProcessed
                         * @template BProcessed
                         * @param callable(A): AProcessed $processA
                         * @param callable(B): BProcessed $processB
                         * @return list{AProcessed, BProcessed}
                         */
                        public function process(callable $processA, callable $processB): array
                        {
                            return [$processA($this->a), $processB($this->b)];
                        }
                    }

                    /**
                     * @template A
                     * @param A $value
                     * @return A
                     */
                    function id(mixed $value): mixed
                    {
                        return $value;
                    }

                    function intToString(int $value): string
                    {
                        return (string) $value;
                    }

                    /**
                     * @template A
                     * @param A $value
                     * @return list{A}
                     */
                    function singleToList(mixed $value): array
                    {
                        return [$value];
                    }

                    $processor = new Processor(a: 1, b: 2);

                    $test_id = $processor->process(id(...), id(...));
                    $test_complex = $processor->process(intToString(...), singleToList(...));
                ',
                'assertions' => [
                    '$test_id' => 'list{int, int}',
                    '$test_complex' => 'list{string, list{int}}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferFirstClassCallableOnMethodCallWithMultipleParams' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     * @template C
                     */
                    final class Processor
                    {
                        /**
                         * @param A $a
                         * @param B $b
                         * @param C $c
                         */
                        public function __construct(
                            public readonly mixed $a,
                            public readonly mixed $b,
                            public readonly mixed $c,
                        ) {}

                        /**
                         * @template AProcessed
                         * @template BProcessed
                         * @template CProcessed
                         * @param callable(A, B, C): list{AProcessed, BProcessed, CProcessed} $processAB
                         * @return list{AProcessed, BProcessed, CProcessed}
                         */
                        public function process(callable $processAB): array
                        {
                            return $processAB($this->a, $this->b, $this->c);
                        }
                    }

                    /**
                     * @template A
                     * @template B
                     * @template C
                     * @param A $value1
                     * @param B $value2
                     * @param C $value3
                     * @return list{A, B, C}
                     */
                    function tripleId(mixed $value1, mixed $value2, mixed $value3): array
                    {
                        return [$value1, $value2, $value3];
                    }

                    $processor = new Processor(a: 1, b: 2, c: 3);

                    $test = $processor->process(tripleId(...));
                ',
                'assertions' => [
                    '$test' => 'list{int, int, int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferFirstClassCallableOnMethodCallWithTemplatedAndNonTemplatedParams' => [
                'code' => '<?php
                    /**
                     * @template T1
                     * @template T2
                     */
                    final class App
                    {
                        /**
                         * @param T1 $param1
                         * @param T2 $param2
                         */
                        public function __construct(
                            private readonly mixed $param1,
                            private readonly mixed $param2,
                        ) {
                        }

                        /**
                         * @template T3
                         * @param callable(T1, T2): T3 $callback
                         * @return T3
                         */
                        public function run(callable $callback): mixed
                        {
                            return $callback($this->param1, $this->param2);
                        }
                    }

                    /**
                     * @template T of int|float
                     * @param T $param2
                     * @return array{param1: int, param2: T}
                     */
                    function appHandler1(int $param1, int|float $param2): array
                    {
                        return ["param1" => $param1, "param2" => $param2];
                    }

                    /**
                     * @template T of int|float
                     * @param T $param1
                     * @return array{param1: T, param2: int}
                     */
                    function appHandler2(int|float $param1, int $param2): array
                    {
                        return ["param1" => $param1, "param2" => $param2];
                    }

                    /**
                     * @return array{param1: int, param2: int}
                     */
                    function appHandler3(int $param1, int $param2): array
                    {
                        return ["param1" => $param1, "param2" => $param2];
                    }

                    $app = new App(param1: 42, param2: 42);

                    $result1 = $app->run(appHandler1(...));
                    $result2 = $app->run(appHandler2(...));
                    $result3 = $app->run(appHandler3(...));
                ',
                'assertions' => [
                    '$result1===' => 'array{param1: int, param2: 42}',
                    '$result2===' => 'array{param1: 42, param2: int}',
                    '$result3===' => 'array{param1: int, param2: int}',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'firstClassCallableFromObject' => [
                'code' => '<?php

                function onKernelController(callable $controller): void
                {
                    if (\is_array($controller)) {
                        $controller = $controller[0];
                    } else {
                        try {
                            $reflection = new \ReflectionFunction($controller(...));
                            $controller = $reflection->getClosureThis();
                        } catch (\ReflectionException) {
                            return;
                        }
                    }
                }
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inferTypeWhenClosureParamIsOmitted' => [
                'code' => '<?php
                    /**
                     * @template A
                     * @template B
                     * @param A $a
                     * @param callable(A): B $ab
                     * @return B
                     */
                    function pipe(mixed $a, callable $ab): mixed
                    {
                        return $ab($a);
                    }
                    /**
                     * @template A
                     * @param callable(A): void $callback
                     * @return Closure(list<A>): list<A>
                     */
                    function iterate(callable $callback): Closure
                    {
                        return function(array $list) use ($callback) {
                            foreach ($list as $item) {
                                $callback($item);
                            }
                            return $list;
                        };
                    }
                    $result1 = pipe(
                        [1, 2, 3],
                        iterate(fn($i) => print_r($i)),
                    );
                    $result2 = pipe(
                        [1, 2, 3],
                        iterate(fn() => print_r("noop")),
                    );',
                'assertions' => [
                    '$result1===' => 'list<1|2|3>',
                    '$result2===' => 'list<1|2|3>',
                ],
                'ignored_issues' => [],
                'php_version' => '8.1',
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
                    /**
                     * @param non-empty-string $prospective_file_path
                     * @return non-empty-string[]
                     */
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
                    f("#b::a");',
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
                    }',
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
                    }',
            ],
            'offsetOnCallable' => [
                'code' => '<?php
                    function c(callable $c) : void {
                        if (is_array($c)) {
                            new ReflectionClass($c[0]);
                        }
                    }',
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
                    '$method' => 'string',
                ],
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
                    }',
            ],
            'notCallableArrayNoUndefinedClass' => [
                'code' => '<?php
                    /**
                     * @psalm-param array|callable $_fields
                     */
                    function f($_fields): void {}

                    f(["instance_date" => "ASC", "start_time" => "ASC"]);',
            ],
            'callOnInvokableOrCallable' => [
                'code' => '<?php
                    interface Callback {
                        public function __invoke(): void;
                    }

                    /** @var Callback|callable */
                    $test = function (): void {};

                    $test();',
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
                    }',
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
            'callableMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public static function hello(): void {
                        echo "hello";
                    }
                }

                $foo = new Foo();
                run(array($foo, "hello"));',
            ],
            'callableMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function hello(): void {
                        echo "hello";
                    }
                }

                $foo = new Foo();
                run(array($foo, "hello"));',
            ],
            'callableClassStringArrayMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public static function hello(): void {
                        echo "hello";
                    }
                }

                run(array(Foo::class, "hello"));',
            ],
            'callableClassStringMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public static function hello(): void {
                        echo "hello";
                    }
                }

                run("Foo::hello");',
            ],
            'callableInClassStringArrayMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(Foo::class, "hello"));
                    }

                    public static function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInClassLiteralStringArrayMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array("Foo", "hello"));
                    }

                    public static function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInClassConstantArrayMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(__CLASS__, "hello"));
                    }

                    public static function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInClassStringMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run("Foo::hello");
                    }

                    public static function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInstanceArrayMethodOutOfClassContextNonStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array($this, "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInstanceArrayMethodOutOfClassContextStaticPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array($this, "hello"));
                    }

                    public static function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInstanceArrayMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array($this, "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInstanceArrayMethodClassContextPhpNativeNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        call_user_func(array($this, "hello"));
                    }

                    private function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableClassStringArrayMethodClassContextPhpNativeNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        call_user_func(array(Foo::class, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableClassLiteralStringMethodClassContextPhpNativeNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        call_user_func("Foo::hello");
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableClassConstantArrayMethodClassContextPhpNativeNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        call_user_func(array(__CLASS__, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
            ],
            'callableInstanceArrayMethodClassContextNonStaticPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $this->run_in_c(array($this, "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }',
            ],
            'callableInstanceArrayMethodClassContextNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $this->run_in_c(array($this, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    private function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }',
            ],
            'callableClassConstantArrayMethodClassContextStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $this->run_in_c(array(Foo::class, "hello"));
                    }

                    protected static function hello(): void {
                        echo "hello";
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    private function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }',
            ],
            'callableClassConstantArrayMethodClassContextNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $this->run_in_c(array(Foo::class, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    private function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }',
            ],
            'callableClassStringArrayMethodOtherClassContextStaticPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $bar = new Bar();
                        $bar->run_in_c(array(Foo::class, "hello"));
                    }

                    public static function hello(): void {
                        echo "hello";
                    }
                }

                class Bar {
                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }
                ',
            ],
            'callableInstanceArrayMethodOtherClassContextNonStaticPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $bar = new Bar();
                        $bar->run_in_c(array($this, "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }

                class Bar {
                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }
                ',
            ],
            'callableClassLiteralStringMethodOtherClassContextStaticPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $bar = new Bar();
                        $bar->run_in_c("Foo::hello");
                    }

                    public static function hello(): void {
                        echo "hello";
                    }
                }

                class Bar {
                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }
                ',
            ],
            # @todo valid
            'notCallableListNoUndefinedClass' => [
                'code' => '<?php
                    /**
                     * @param array|callable $arg
                     */
                    function foo($arg): void {}

                    foo(["a", "b"]);',
            ],
            'notCallableArray' => [
                'code' => '<?php
                    /**
                     * @param array{class-string, string}|callable $arg
                     */
                    function foo($arg): void {}

                    foo([\DateTime::class, "format"]);',
            ],
            'notCallableString' => [
                'code' => '<?php
                    /**
                     * @param string|callable $arg
                     */
                    function foo($arg): void {}

                    foo("notACallable");',
            ],
            'callableOptionalOrAdditionalOptional' => [
                'code' => '<?php
                    /**
                     * @param callable(string, string, string, string=):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    function bar(string $a, string $b, string $c, string $d = ""): bool {}

                    foo("bar");

                    /**
                     * @param callable(string, string, string):bool $arg
                     * @return void
                     */
                    function foo1($arg) {}

                    function bar1(string $a, string $b, string $c, string $d = ""): bool {}

                    foo1("bar1");',
                'assertions' => [],
                'ignored_issues' => ['InvalidReturnType'],
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
                    }',
            ],
            'variadicClosureAssignability' => [
                'code' => '<?php
                    function withVariadic(int $a, int $b, int ...$rest): int
                    {
                        return 0;
                    }

                    /** @param Closure(int, int): int $f */
                    function int_int(Closure $f): void {}

                    /** @param Closure(int, int, int): int $f */
                    function int_int_int(Closure $f): void {}

                    /** @param Closure(int, int, int, int): int $f */
                    function int_int_int_int(Closure $f): void {}

                    int_int(withVariadic(...));
                    int_int_int(withVariadic(...));
                    int_int_int_int(withVariadic(...));',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'callableArrayTypes' => [
                'code' => '<?php
                    /** @var callable-list $c */
                    $c;
                    [$a, $b] = $c;
                    ',
                'assertions' => [
                    '$a' => 'class-string|object',
                    '$b' => 'string',
                    '$c' => 'list{class-string|object, string}',
                ],
            ],
            'inferTypeWithNestedTemplatesAndExplicitTypeHint' => [
                'code' => '<?php
                    /**
                     * @template TResult
                     */
                    interface Message {}

                    /**
                     * @implements Message<list<int>>
                     */
                    final class GetListOfNumbers implements Message {}

                    /**
                     * @template TResult
                     * @template TMessage of Message<TResult>
                     */
                    final class Envelope {}

                    /**
                     * @template TResult
                     * @template TMessage of Message<TResult>
                     * @param class-string<TMessage> $_message
                     * @param callable(TMessage, Envelope<TResult, TMessage>): TResult $_handler
                     */
                    function addHandler(string $_message, callable $_handler): void {}

                    addHandler(GetListOfNumbers::class, function (Message $_message, Envelope $_envelope) {
                        /**
                         * @psalm-check-type-exact $_message = GetListOfNumbers
                         * @psalm-check-type-exact $_envelope = Envelope<list<int>, GetListOfNumbers>
                         */
                        return [1, 2, 3];
                    });',
            ],
            'unsealedAllOptionalCbParam' => [
                'code' => '<?php
                    /**
                     * @param callable(array<string, string>) $arg
                     * @return void
                     */
                    function foo($arg) {}

                    /**
                     * @param array{a?: string}&array<string, string> $cb_arg
                     * @return void
                     */
                    function bar($cb_arg) {}

                    foo("bar");',
            ],
            'callableWithNamedArguments' => [
                'code' => <<<'PHP'
                <?php
                /** @param callable(int $i) $c */
                function f(callable $c): void {
                    $c(i: 1);
                }
                PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'callableArrayPassedAsCallable' => [
                'code' => <<<'PHP'
                <?php
                function f(callable $c): void {
                    $c();
                }
                /** @var object $o */;

                $ca = [$o::class, 'createFromFormat'];
                if (!is_callable($ca)) {
                    exit;
                }
                f($ca);
                PHP,
            ],
            'callableWithoutArray' => [
                'code' => '<?php
                    /**
                     * @param array|(callable():array) $var
                     */
                    function text($var): array
                    {
                        if (is_array($var)) {
                            return $var;
                        }

                        //callable-string can\'t specify return type but it doesn\'t error
                        return call_user_func($var);
                    }',
            ],
        ];
    }

    #[Override]
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
                'ignored_issues' => ['UndefinedClass'],
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
                'error_message' => 'InvalidArgument',
            ],
            'noFatalErrorOnMissingClassWithoutSlash' => [
                'code' => '<?php
                    class Func {
                        public function __construct(string $name, callable $callable) {}
                    }

                    new Func("f", ["Foo", "bar"]);',
                'error_message' => 'InvalidArgument',
            ],
            'invalidArrayCallable' => [
                'code' => '<?php
                    function foo(callable $callback) : void {
                        $callback();
                    }

                    final class Bar {
                        public static function baz() : void {}
                    }

                    foo([Bar::class, "baz", 1231233]);',
                'error_message' => 'InvalidArgument',
            ],
            'callableMissingOptional' => [
                'code' => '<?php
                    /**
                     * @param callable(string=):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    function bar(): bool {
                        return rand(0, 10) > 5 ? true : false;
                    }

                    foo("bar");',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'callableMissingOptionalThisArray' => [
                'code' => '<?php
                    /**
                     * @param callable(string=):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    class A {
                        public function __construct() {
                            foo([$this, "bar"]);
                        }

                        public function bar(): bool {
                            return true;
                        }
                    }',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'callableMissingOptionalVariableInstanceArray' => [
                'code' => '<?php
                    /**
                     * @param callable(string=):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    class A {
                        public function bar(): bool {
                            return true;
                        }
                    }

                    $a_instance = new A();
                    $y = [$a_instance, "bar"];
                    foo($y);',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'callableMissingOptionalMultipleParams' => [
                'code' => '<?php
                    /**
                     * @param callable(string, string, string, string=):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    function bar(string $a, string $b, string $c): bool {}

                    foo("bar");',
                'error_message' => 'PossiblyInvalidArgument',
                'ignored_issues' => ['InvalidReturnType'],
            ],
            'callableMissingRequiredMultipleParams' => [
                'code' => '<?php
                    /**
                     * @param callable(string, string, string, string):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    function bar(string $a, string $b, string $c): bool {}

                    foo("bar");',
                'error_message' => 'PossiblyInvalidArgument',
                'ignored_issues' => ['InvalidReturnType'],
            ],
            'callableAdditionalRequiredParam' => [
                'code' => '<?php
                    /**
                     * @param callable(string, string, string):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    function bar(string $a, string $b, string $c, string $d): bool {}

                    foo("bar");',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => ['InvalidReturnType'],
            ],
            'callableMultipleParamsWithOptional' => [
                'code' => '<?php
                    /**
                     * @param callable(string, string, string=):bool $arg
                     * @return void
                     */
                    function foo($arg) {}

                    function bar(string $a, string $b, string $c): bool {}

                    foo("bar");',
                'error_message' => 'PossiblyInvalidArgument',
                'ignored_issues' => ['InvalidReturnType'],
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
                'error_message' => 'MixedArgumentTypeCoercion',
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
            'callableMethodOutOfClassContextNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    private static function hello(): void {
                        echo "hello";
                    }
                }

                $foo = new Foo();
                run(array($foo, "hello"));',
                'error_message' => 'InvalidArgument',
            ],
            'callableMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    protected function hello(): void {
                        echo "hello";
                    }
                }

                $foo = new Foo();
                run(array($foo, "hello"));',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassStringArrayMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function hello(): void {
                        echo "hello";
                    }
                }

                run(array(Foo::class, "hello"));',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassStringArrayMethodOutOfClassContextNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    private static function hello(): void {
                        echo "hello";
                    }
                }

                run(array(Foo::class, "hello"));',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassStringArrayMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    protected function hello(): void {
                        echo "hello";
                    }
                }

                run(array(Foo::class, "hello"));',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassStringMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function hello(): void {
                        echo "hello";
                    }
                }

                run("Foo::hello");',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassStringMethodOutOfClassContextNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    private static function hello(): void {
                        echo "hello";
                    }
                }

                run("Foo::hello");',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassStringMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    protected function hello(): void {
                        echo "hello";
                    }
                }

                run("Foo::hello");',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassStringArrayMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(Foo::class, "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassStringArrayMethodOutOfClassContextNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(Foo::class, "hello"));
                    }

                    private static function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassStringArrayMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(Foo::class, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassLiteralStringArrayMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array("Foo", "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassLiteralStringArrayMethodOutOfClassContextNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array("Foo", "hello"));
                    }

                    private static function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassLiteralStringArrayMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array("Foo", "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassConstantArrayMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(__CLASS__, "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassConstantArrayMethodOutOfClassContextNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(__CLASS__, "hello"));
                    }

                    private static function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassConstantArrayMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array(__CLASS__, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassStringMethodOutOfClassContextNonStatic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run("Foo::hello");
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassStringMethodOutOfClassContextNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run("Foo::hello");
                    }

                    private static function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInClassStringMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run("Foo::hello");
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInstanceArrayMethodClassContextPhpNativeUnsupportedNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        header_register_callback(array($this, "hello"));
                    }

                    private function hello(): void {
                        header("X-Test: hello");
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInstanceArrayMethodOutOfClassContextNonStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array($this, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInstanceArrayMethodOutOfClassContextStaticNonPublic' => [
                'code' => '<?php
                /**
                 * @param callable $callable
                 * @return void
                 */
                function run($callable) {
                    call_user_func($callable);
                }

                class Foo {
                    public function __construct() {
                        run(array($this, "hello"));
                    }

                    protected static function hello(): void {
                        echo "hello";
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassStringArrayMethodOtherClassContextNonStaticPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $bar = new Bar();
                        $bar->run_in_c(array(Foo::class, "hello"));
                    }

                    public function hello(): void {
                        echo "hello";
                    }
                }

                class Bar {
                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableInstanceArrayMethodOtherClassContextNonStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $bar = new Bar();
                        $bar->run_in_c(array($this, "hello"));
                    }

                    protected function hello(): void {
                        echo "hello";
                    }
                }

                class Bar {
                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            'callableClassLiteralStringMethodOtherClassContextStaticNonPublic' => [
                'code' => '<?php
                class Foo {
                    public function __construct() {
                        $bar = new Bar();
                        $bar->run_in_c("Foo::hello");
                    }

                    protected static function hello(): void {
                        echo "hello";
                    }
                }

                class Bar {
                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run_in_c($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'InvalidArgument',
            ],
            # @todo invalid
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
            'inexistentCallableinCallableString' => [
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
            ],
            'callableArrayParentConstantDeprecated' => [
                'code' => '<?php
                class Z {
                    public static function hello(): void {
                        echo "hello";
                    }
                }

                class A extends Z {
                    public function __construct() {
                        $this->run(["parent", "hello"]);
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'callableParentConstantDeprecated' => [
                'code' => '<?php
                class Z {
                    public static function hello(): void {
                        echo "hello";
                    }
                }

                class A extends Z {
                    public function __construct() {
                        $this->run("parent::hello");
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'callableSelfConstantDeprecated' => [
                'code' => '<?php
                class A {
                    public function __construct() {
                        $this->run("self::hello");
                    }

                    public static function hello(): void {
                        echo "hello";
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'callableStaticConstantDeprecated' => [
                'code' => '<?php
                class A {
                    public function __construct() {
                        $this->run("static::hello");
                    }

                    public static function hello(): void {
                        echo "hello";
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'callableArrayStaticConstantDeprecated' => [
                'code' => '<?php
                class A {
                    public function __construct() {
                        $this->run(["static", "hello"]);
                    }

                    public static function hello(): void {
                        echo "hello";
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'DeprecatedConstant',
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'invalidFirstClassCallableCannotBeInferred' => [
                'code' => '<?php
                    /**
                     * @template T1
                     */
                    final class App
                    {
                        /**
                         * @param T1 $param1
                         */
                        public function __construct(
                            private readonly mixed $param1,
                        ) {}

                        /**
                         * @template T2
                         * @param callable(T1): T2 $callback
                         * @return T2
                         */
                        public function run(callable $callback): mixed
                        {
                            return $callback($this->param1);
                        }
                    }

                    /**
                     * @template P1 of int|float
                     * @param P1 $param1
                     * @return array{param1: P1}
                     */
                    function appHandler(mixed $param1): array
                    {
                        return ["param1" => $param1];
                    }

                    $result = (new App(param1: [42]))->run(appHandler(...));
                ',
                'error_message' => 'InvalidArgument',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'variadicClosureAssignability' => [
                'code' => '<?php
                    function add(int $a, int $b, int ...$rest): int
                    {
                        return 0;
                    }

                    /** @param Closure(int, int, string, int, int): int $f */
                    function int_int_string_int_int(Closure $f): void {}

                    /** @param Closure(int, int, int, string, int): int $f */
                    function int_int_int_string_int(Closure $f): void {}

                    /** @param Closure(int, int, int, int, string): int $f */
                    function int_int_int_int_string(Closure $f): void {}

                    int_int_string_int_int(add(...));
                    int_int_int_string_int(add(...));
                    int_int_int_int_string(add(...));',
                'error_message' => 'InvalidScalarArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'callableWithInvalidNamedArguments' => [
                'code' => <<<'PHP'
                <?php
                /** @param callable(int $a) $c */
                function f(callable $c): void {
                    $c(b: 1);
                }
                PHP,
                'error_message' => 'InvalidNamedArgument',
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'parentCallableArrayWithoutParent' => [
                'code' => '<?php
                class A {
                    public function __construct() {
                        $this->run(["parent", "hello"]);
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'ParentNotFound',
            ],
            'parentCallableWithoutParent' => [
                'code' => '<?php
                class A {
                    public function __construct() {
                        $this->run("parent::hello");
                    }

                    /**
                     * @param callable $callable
                     * @return void
                     */
                    public function run($callable) {
                        call_user_func($callable);
                    }
                }',
                'error_message' => 'ParentNotFound',
            ],
            'wrongCallableInUnion' => [
                'code' => '<?php
                    /**
                     * @param int|callable $arg
                     */
                    function foo($arg): void {}

                    foo([\DateTime::class, "wrongMethod"]);',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
