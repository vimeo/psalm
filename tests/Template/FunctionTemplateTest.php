<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class FunctionTemplateTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'validTemplatedType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @template T
                     * @param T $x
                     * @return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a): void { }

                    bar(foo("string"));',
            ],
            'validPsalmTemplatedFunctionType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @psalm-template T
                     * @psalm-param T $x
                     * @psalm-return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a): void { }

                    bar(foo("string"));',
            ],
            'validTemplatedStaticMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public static function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar(A::foo("string"));',
            ],
            'validTemplatedInstanceMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar((new A())->foo("string"));',
            ],
            'genericArrayKeys' => [
                '<?php
                    /**
                     * @template T as array-key
                     *
                     * @param array<T, mixed> $arr
                     * @return array<int, T>
                     */
                    function my_array_keys($arr) {
                        return array_keys($arr);
                    }

                    $a = my_array_keys(["hello" => 5, "goodbye" => new \Exception()]);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
            ],
            'genericArrayFlip' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue as array-key
                     *
                     * @param array<TKey, TValue> $arr
                     * @return array<TValue, TKey>
                     */
                    function my_array_flip($arr) {
                        return array_flip($arr);
                    }

                    $b = my_array_flip(["hello" => 5, "goodbye" => 6]);',
                'assertions' => [
                    '$b' => 'array<int, string>',
                ],
            ],
            'byRefKeyValueArray' => [
                '<?php
                    /**
                     * @template TValue
                     * @template TKey as array-key
                     *
                     * @param array<TKey, TValue> $arr
                     */
                    function byRef(array &$arr) : void {}

                    $b = ["a" => 5, "c" => 6];
                    byRef($b);',
                'assertions' => [
                    '$b' => 'array<string, int>',
                ],
            ],
            'byRefMixedKeyArray' => [
                '<?php
                    /**
                     * @template TValue
                     *
                     * @param array<mixed, TValue> $arr
                     */
                    function byRef(array &$arr) : void {}

                    $b = ["a" => 5, "c" => 6];
                    byRef($b);',
                'assertions' => [
                    '$b' => 'array<mixed, int>',
                ],
            ],
            'mixedArrayPop' => [
                '<?php
                    /**
                     * @template TValue
                     *
                     * @param array<array-key, TValue> $arr
                     * @return TValue|null
                     */
                    function my_array_pop(array &$arr) {
                        return array_pop($arr);
                    }

                    /** @var mixed */
                    $b = ["a" => 5, "c" => 6];
                    $a = my_array_pop($b);',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'array<array-key, mixed>',
                ],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'genericArrayPop' => [
                '<?php
                    /**
                     * @template TValue
                     * @template TKey as array-key
                     *
                     * @param array<TKey, TValue> $arr
                     * @return TValue|null
                     */
                    function my_array_pop(array &$arr) {
                        return array_pop($arr);
                    }

                    $b = ["a" => 5, "c" => 6];
                    $a = my_array_pop($b);',
                'assertions' => [
                    '$a' => 'int|null',
                    '$b' => 'array<string, int>',
                ],
            ],
            'templateCallableReturnType' => [
                '<?php
                    namespace NS;

                    /**
                     * @template T
                     * @psalm-param callable():T $action
                     * @psalm-return T
                     */
                    function retry(int $maxRetries, callable $action) {
                        return $action();
                    }

                    function takesInt(int $p): void{};

                    takesInt(retry(1, function(): int { return 1; }));',
            ],
            'templateClosureReturnType' => [
                '<?php
                    namespace NS;

                    /**
                     * @template T
                     * @psalm-param \Closure():T $action
                     * @psalm-return T
                     */
                    function retry(int $maxRetries, callable $action) {
                        return $action();
                    }

                    function takesInt(int $p): void{};

                    takesInt(retry(1, function(): int { return 1; }));',
            ],
            'replaceChildTypeWithGenerator' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @param Traversable<TKey, TValue> $t
                     * @return array<TKey, TValue>
                     */
                    function f(Traversable $t): array {
                        $ret = [];
                        foreach ($t as $k => $v) $ret[$k] = $v;
                        return $ret;
                    }

                    /** @return Generator<int, stdClass> */
                    function g():Generator { yield new stdClass; }

                    takesArrayOfStdClass(f(g()));

                    /** @param array<stdClass> $p */
                    function takesArrayOfStdClass(array $p): void {}',
            ],

            'splatTemplateParam' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $arr
                     * @param array $arr2
                     * @return array<TKey, TValue>
                     */
                    function splat_proof(array $arr, array $arr2) {
                        return $arr;
                    }

                    $foo = [
                        [1, 2, 3],
                        [1, 2],
                    ];

                    $a = splat_proof(...$foo);',
                'assertions' => [
                    '$a' => 'array<int, int>',
                ],
            ],
            'passArrayByRef' => [
                '<?php
                    function acceptsStdClass(stdClass $_p): void {}

                    $q = [new stdClass];
                    acceptsStdClass(fNoRef($q));
                    acceptsStdClass(fRef($q));
                    acceptsStdClass(fNoRef($q));

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $_arr
                     * @return null|TValue
                     * @psalm-ignore-nullable-return
                     */
                    function fRef(array &$_arr) {
                        return array_shift($_arr);
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $_arr
                     * @return null|TValue
                     * @psalm-ignore-nullable-return
                     */
                    function fNoRef(array $_arr) {
                        return array_shift($_arr);
                    }',
            ],

            'classTemplateAsCorrect' => [
                '<?php
                    class Foo {}
                    class FooChild extends Foo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    bar(new Foo());
                    bar(new FooChild());',
            ],
            'classTemplateOfCorrect' => [
                '<?php
                    class Foo {}
                    class FooChild extends Foo {}

                    /**
                     * @template T of Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    bar(new Foo());
                    bar(new FooChild());',
            ],
            'classTemplateAsInterface' => [
                '<?php
                    interface Foo {}
                    interface FooChild extends Foo {}
                    class FooImplementer implements Foo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    function takesFoo(Foo $f) : void {
                        bar($f);
                    }

                    function takesFooChild(FooChild $f) : void {
                        bar($f);
                    }

                    function takesFooImplementer(FooImplementer $f) : void {
                        bar($f);
                    }',
            ],
            'templateFunctionVar' => [
                '<?php
                    namespace A\B;

                    class C {
                        public function bar() : void {}
                    }

                    interface D {}

                    /**
                     * @template T as C
                     * @return T
                     */
                    function foo($some_t) : C {
                        /** @var T */
                        $a = $some_t;
                        $a->bar();

                        /** @var T&D */
                        $b = $some_t;
                        $b->bar();

                        /** @var D&T */
                        $b = $some_t;
                        $b->bar();

                        return $a;
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MissingParamType'],
            ],
            'bindFirstTemplatedClosureParameterValid' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure(T):void $t1
                     * @param T $t2
                     */
                    function apply(Closure $t1, $t2) : void {}

                    apply(function(int $_i) : void {}, 5);
                    apply(function(string $_i) : void {}, "hello");
                    apply(function(stdClass $_i) : void {}, new stdClass);

                    class A {}
                    class AChild extends A {}

                    apply(function(A $_i) : void {}, new AChild());',
            ],
            'callableReturnsItself' => [
                '<?php
                    $a =
                      /**
                       * @param callable():string $s
                       * @return string
                       */
                      function(callable $s) {
                        return $s();
                      };

                    /**
                     * @template T1
                     * @param callable(callable():T1):T1 $s
                     * @return void
                     */
                    function takesReturnTCallable(callable $s) {}

                    takesReturnTCallable($a);',
            ],
            'nonBindingParamReturn' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure():T $t1
                     * @param T $t2
                     */
                    function foo(Closure $t1, $t2) : void {}
                    foo(
                        function () : int {
                            return 5;
                        },
                        "hello"
                    );',
            ],
            'templatedInterfaceMethodInheritReturnType' => [
                '<?php
                    class Foo {}

                    class SomeIterator implements IteratorAggregate
                    {
                        public function getIterator() {
                            yield new Foo;
                        }
                    }

                    $i = (new SomeIterator())->getIterator();',
                [
                    '$i' => 'Traversable<mixed, mixed>',
                ],
            ],
            'upcastArrayToIterable' => [
                '<?php
                    /**
                     * @template K
                     * @template V
                     * @param iterable<K,V> $collection
                     * @return V
                     * @psalm-suppress InvalidReturnType
                     */
                    function first($collection) {}

                    $one = first([1,2,3]);',
                [
                    '$one' => 'int',
                ],
            ],
            'templateIntersectionLeft' => [
                '<?php
                    interface I1 {}
                    interface I2 {}

                    /**
                     * @template T as I1&I2
                     * @param T $a
                     */
                    function templatedBar(I1 $a) : void {}',
            ],
            'templateIntersectionRight' => [
                '<?php
                    interface I1 {}
                    interface I2 {}

                    /**
                     * @template T as I1&I2
                     * @param T $b
                     */
                    function templatedBar(I2 $b) : void {}',
            ],
            'matchMostSpecificTemplate' => [
                '<?php
                    /**
                     * @template TReturn
                     * @param callable():(\Generator<mixed, mixed, mixed, TReturn>|TReturn) $gen
                     * @return array<int, TReturn>
                     */
                    function call(callable $gen) : array {
                        $return = $gen();
                        if ($return instanceof Generator) {
                            return [$gen->getReturn()];
                        }
                        /** @var array<int, TReturn> */
                        $wrapped_gen = [$gen];
                        return $wrapped_gen;
                    }

                    $arr = call(
                        /**
                         * @return Generator<mixed, mixed, mixed, string>
                         */
                        function() {
                            yield 1;
                            return "hello";
                        }
                    );',
                [
                    '$arr' => 'array<int, string>',
                ],
            ],
            'templateOfWithSpace' => [
                '<?php
                    /**
                     * @template T of array<int, mixed>
                     */
                    class Foo
                    {
                    }

                    /**
                     * @param Foo<array<int, DateTime>> $a
                     */
                    function bar(Foo $a) : void {}',
            ],
            'allowUnionTypeParam' => [
                '<?php
                    /**
                     * @template T
                     * @param callable(T) $x
                     * @param array<T> $y
                     */
                    function example($x, $y): void {}

                    example(
                        /**
                         * @param int|false $x
                         */
                        function($x): void {},
                        [strpos("str", "str")]
                    );',
            ],
            'ignoreTooManyArrayArgs' => [
                '<?php

                    function takesArray(array $arr) : void {}

                    /**
                     * @psalm-suppress TooManyTemplateParams
                     * @var array<int, int, int>
                     */
                    $b = [1, 2, 3];
                    takesArray($b);',
            ],
            'functionTemplateUnionType' => [
                '<?php
                    /**
                     * @template T0 as int|string
                     * @param T0 $t
                     * @return T0
                     */
                    function foo($t) {
                        return $t;
                    }

                    $s = foo("hello");
                    $i = foo(5);',
                'assertions' => [
                    '$s' => 'string',
                    '$i' => 'int',
                ],
            ],
            'reconcileTraversableTemplatedAndNormal' => [
                '<?php
                    function foo(Traversable $t): void {
                        if ($t instanceof IteratorAggregate) {
                            $a = $t->getIterator();
                            $t = $a;
                        }

                        if (!$t instanceof Iterator) {
                            return;
                        }

                        if (rand(0, 1) && rand(0, 1)) {
                            $t->next();
                        }
                    }',
            ],
            'keyOfTemplate' => [
                '<?php
                    /**
                     * @template T as array
                     * @template K as key-of<T>
                     *
                     * @param T $o
                     * @param K $name
                     *
                     * @return T[K]
                     */
                    function getOffset(array $o, $name) {
                        return $o[$name];
                    }

                    $a = ["foo" => "hello", "bar" => 2];

                    $b = getOffset($a, "foo");
                    $c = getOffset($a, "bar");',
                [
                    '$b' => 'string',
                    '$c' => 'int',
                ],
            ],
            'dontGeneraliseBoundParamWithWiderCallable' => [
                '<?php
                    class C {
                        public function foo() : void {}
                    }

                    /**
                     * @psalm-template T
                     * @psalm-param T $t
                     * @psalm-param callable(?T):void $callable
                     * @return T
                     */
                    function makeConcrete($t, callable $callable) {
                        $callable(rand(0, 1) ? $t : null);
                        return $t;
                    }

                    $c = makeConcrete(new C(), function (?C $c) : void {});',
                [
                    '$c' => 'C',
                ],
            ],
            'allowTemplateTypeBeingUsedInsideFunction' => [
                '<?php
                    /**
                     * @template T of DateTime
                     * @param callable(T) $callable
                     * @param T $value
                     */
                    function foo(callable $callable, DateTime $value) : void {
                        $callable($value);
                    }',
            ],
            'callFindAnother' => [
                '<?php
                    /**
                     * @template T as Foo
                     * @param T $foo
                     * @return T
                     */
                    function loader($foo) {
                        return $foo::getAnother();
                    }

                    class Foo {
                        /** @return static */
                        public static function getAnother() {
                            return new static();
                        }
                    }',
            ],
            'templatedVarOnReturn' => [
                '<?php
                    namespace Ns;

                    class A {}
                    class B {}

                    /**
                     * @template T
                     * @param T $t
                     * @return T
                     */
                    function getAOrB($t) {
                        if ($t instanceof A) {
                            /** @var T */
                            return new A();
                        }

                        /** @var T */
                        return new B();
                    }',
            ],
            'assertOnTemplatedValue' => [
                '<?php
                    /**
                     * @template I
                     * @param I $foo
                     */
                    function bar($foo): void {
                        if (is_string($foo)) {}
                        if (!is_string($foo)) {}
                        if (is_int($foo)) {}
                        if (!is_int($foo)) {}
                        if (is_numeric($foo)) {}
                        if (!is_numeric($foo)) {}
                        if (is_scalar($foo)) {}
                        if (!is_scalar($foo)) {}
                        if (is_bool($foo)) {}
                        if (!is_bool($foo)) {}
                        if (is_object($foo)) {}
                        if (!is_object($foo)) {}
                        if (is_callable($foo)) {}
                        if (!is_callable($foo)) {}
                    }'
            ],
            'interpretFunctionCallableReturnValue' => [
                '<?php
                    final class Id
                    {
                        /**
                         * @var string
                         */
                        private $id;

                        private function __construct(string $id)
                        {
                            $this->id = $id;
                        }

                        public static function fromString(string $id): self
                        {
                            return new self($id);
                        }
                    }

                    /**
                     * @template T
                     * @psalm-param callable(string): T $generator
                     * @psalm-return callable(): T
                     */
                    function idGenerator(callable $generator)
                    {
                        return static function () use ($generator) {
                            return $generator("random id");
                        };
                    }

                    function client(Id $id): void
                    {
                    }

                    $staticIdGenerator = idGenerator([Id::class, "fromString"]);
                    client($staticIdGenerator());'
            ],
            'noCrashWhenTemplatedClassIsStatic' => [
                '<?php
                    abstract class Model {
                        /** @return static */
                        public function newInstance() {
                            return new static();
                        }
                    }

                    /**
                     * @template T of Model
                     * @param T $m
                     * @return T
                     */
                    function foo(Model $m) : Model {
                        return $m->newInstance();
                    }'
            ],
            'unboundVariableIsEmpty' => [
                '<?php
                    /**
                     * @template TE
                     * @template TR
                     *
                     * @param TE $elt
                     * @param TR ...$elts
                     *
                     * @return TE|TR
                     */
                    function collect($elt, ...$elts) {
                        $ret = $elt;
                        foreach ($elts as $item) {
                            if (rand(0, 1)) {
                                $ret = $item;
                            }
                        }
                        return $ret;
                    }

                    echo collect("a");'
            ],
            'paramOutDontLeak' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     *
                     * @param array<TKey, TValue> $arr
                     * @param-out list<TValue> $arr
                     */
                    function example_sort_by_ref(array &$arr): bool {
                        $arr = array_values($arr);
                        return true;
                    }

                    /**
                     * @param array<int, array{0: int, 1: string}> $array
                     * @return list<array{0: int, 1: string}>
                     */
                    function example(array $array): array {
                        example_sort_by_ref($array);
                        return $array;
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
            'invalidTemplatedType' => [
                '<?php
                    namespace FooFoo;

                    /**
                     * @template T
                     * @param T $x
                     * @return T
                     */
                    function foo($x) {
                        return $x;
                    }

                    function bar(string $a): void { }

                    bar(foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidTemplatedStaticMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public static function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar(A::foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidTemplatedInstanceMethodType' => [
                '<?php
                    namespace FooFoo;

                    class A {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        public function foo($x) {
                            return $x;
                        }
                    }

                    function bar(string $a): void { }

                    bar((new A())->foo(4));',
                'error_message' => 'InvalidScalarArgument',
            ],
            'replaceChildTypeNoHint' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @param Traversable<TKey, TValue> $t
                     * @return array<TKey, TValue>
                     */
                    function f(Traversable $t): array {
                        $ret = [];
                        foreach ($t as $k => $v) $ret[$k] = $v;
                        return $ret;
                    }

                    function g():Generator { yield new stdClass; }

                    takesArrayOfStdClass(f(g()));

                    /** @param array<stdClass> $p */
                    function takesArrayOfStdClass(array $p): void {}',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'classTemplateAsIncorrectClass' => [
                '<?php
                    class Foo {}
                    class NotFoo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    bar(new NotFoo());',
                'error_message' => 'InvalidArgument',
            ],
            'classTemplateAsIncorrectInterface' => [
                '<?php
                    interface Foo {}
                    interface NotFoo {}

                    /**
                     * @template T as Foo
                     * @param T $x
                     * @return T
                     */
                    function bar($x) {
                        return $x;
                    }

                    function takesNotFoo(NotFoo $f) : void {
                        bar($f);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'templateFunctionMethodCallWithoutMethod' => [
                '<?php
                    namespace A\B;

                    class C {}

                    /**
                     * @template T as C
                     * @param T $some_t
                     */
                    function foo($some_t) : void {
                        $some_t->bar();
                    }',
                'error_message' => 'PossiblyUndefinedMethod',
            ],
            'templateFunctionMethodCallWithoutAsType' => [
                '<?php
                    namespace A\B;

                    /**
                     * @template T
                     * @param T $some_t
                     */
                    function foo($some_t) : void {
                        $some_t->bar();
                    }',
                'error_message' => 'MixedMethodCall',
            ],
            'bindFirstTemplatedClosureParameterInvalidScalar' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure(T):void $t1
                     * @param T $t2
                     */
                    function apply(Closure $t1, $t2) : void
                    {
                        $t1($t2);
                    }

                    apply(function(int $_i) : void {}, "hello");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'bindFirstTemplatedClosureParameterTypeCoercion' => [
                '<?php
                    /**
                     * @template T
                     *
                     * @param Closure(T):void $t1
                     * @param T $t2
                     */
                    function apply(Closure $t1, $t2) : void
                    {
                        $t1($t2);
                    }

                    class A {}
                    class AChild extends A {}

                    apply(function(AChild $_i) : void {}, new A());',
                'error_message' => 'ArgumentTypeCoercion',
            ],

            'callableDoesNotReturnItself' => [
                '<?php
                    $b =
                      /**
                       * @param callable():int $s
                       * @return string
                       */
                      function(callable $s) {
                        return "#" . $s();
                      };

                    /**
                     * @template T1
                     * @param callable(callable():T1):T1 $s
                     * @return void
                     */
                    function takesReturnTCallable(callable $s) {}

                    takesReturnTCallable($b);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'multipleArgConstraintWithMoreRestrictiveFirstArg' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @template T
                     * @param callable(T):void $c1
                     * @param callable(T):void $c2
                     * @param T $a
                     */
                    function foo(callable $c1, callable $c2, $a): void {
                      $c1($a);
                      $c2($a);
                    }

                    foo(
                      function(AChild $_a) : void {},
                      function(A $_a) : void {},
                      new A()
                    );',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'multipleArgConstraintWithMoreRestrictiveSecondArg' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @template T
                     * @param callable(T):void $c1
                     * @param callable(T):void $c2
                     * @param T $a
                     */
                    function foo(callable $c1, callable $c2, $a): void {
                      $c1($a);
                      $c2($a);
                    }

                    foo(
                      function(A $_a) : void {},
                      function(AChild $_a) : void {},
                      new A()
                    );',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'multipleArgConstraintWithLessRestrictiveThirdArg' => [
                '<?php
                    class A {}
                    class AChild extends A {}

                    /**
                     * @template T
                     * @param callable(T):void $c1
                     * @param callable(T):void $c2
                     * @param T $a
                     */
                    function foo(callable $c1, callable $c2, $a): void {
                      $c1($a);
                      $c2($a);
                    }

                    foo(
                      function(AChild $_a) : void {},
                      function(AChild $_a) : void {},
                      new A()
                    );',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'possiblyInvalidArgumentWithUnionFirstArg' => [
                '<?php

                    /**
                     * @template T
                     * @param T $a
                     * @param T $b
                     * @return T
                     */
                    function foo($a, $b) {
                      return rand(0, 1) ? $a : $b;
                    }

                    echo foo([], "hello");',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'possiblyInvalidArgumentWithUnionSecondArg' => [
                '<?php

                    /**
                     * @template T
                     * @param T $a
                     * @param T $b
                     * @return T
                     */
                    function foo($a, $b) {
                      return rand(0, 1) ? $a : $b;
                    }

                    echo foo("hello", []);',
                'error_message' => 'PossiblyInvalidArgument',
            ],
            'preventTemplateTypeAsBeingUsedInsideFunction' => [
                '<?php
                    /**
                     * @template T of DateTime
                     * @param callable(T) $callable
                     */
                    function foo(callable $callable) : void {
                        $callable(new \DateTime());
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventWrongTemplateBeingPassed' => [
                '<?php
                    /**
                     * @template T of DateTime
                     * @template T2 of DateTime
                     * @param callable(T): T $parameter
                     * @param T2 $value
                     * @return T
                     */
                    function foo(callable $parameter, $value)
                    {
                        return $parameter($value);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventTemplateTypeReturnMoreGeneral' => [
                '<?php
                    /**
                     * @template T of DateTimeInterface
                     * @param T $x
                     * @return T
                     */
                    function foo($x)
                    {
                        return new \DateTime();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'preventReturningString' => [
                '<?php
                    /**
                     * @template T
                     * @psalm-param T $t
                     * @return T
                     */
                    function mirror($t) {
                        return "string";
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'unTemplatedVarOnReturn' => [
                '<?php
                    namespace Ns;

                    class A {}
                    class B {}

                    /**
                     * @template T
                     * @param T $t
                     * @return T
                     */
                    function getAOrB($t) {
                        if ($t instanceof A) {
                            return new A();
                        }

                        return new B();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'templateReturnTypeOfCallableWithIncompatibleType' => [
                '<?php
                    class A {}

                    class B {
                        public static function returnsObjectOrNull() : ?A {
                            return random_int(0, 1) ? new A() : null;
                        }
                    }


                    /**
                     * @psalm-template T as object
                     * @psalm-param callable() : T $callback
                     * @psalm-return T
                     */
                    function makeResultSet(callable $callback)
                    {
                        return $callback();
                    }

                    makeResultSet([B::class, "returnsObjectOrNull"]);',
                'error_message' => 'InvalidArgument',
            ],
            'templateInvokeArg' => [
                '<?php
                    /**
                     * @template T
                     * @param callable(T):void $c
                     * @param T $param
                     */
                    function apply(callable $c, $param):void{
                        call_user_func($c, $param);
                    }

                    class A {
                        public function __toString(){
                            return "a";
                        }
                    }

                    class B {}

                    class Printer{
                        public function __invoke(A $a) : void {
                            echo $a;
                        }
                    }

                    apply(new Printer(), new B());',
                'error_message' => 'InvalidArgument'
            ],
        ];
    }
}
