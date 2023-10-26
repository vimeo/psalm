<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class FunctionTemplateTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'validTemplatedType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @template T as array-key
                     *
                     * @param array<T, mixed> $arr
                     * @return list<T>
                     */
                    function my_array_keys($arr) {
                        return array_keys($arr);
                    }

                    $a = my_array_keys(["hello" => 5, "goodbye" => new \Exception()]);',
                'assertions' => [
                    '$a' => 'list<string>',
                ],
            ],
            'genericNonEmptyArrayKeys' => [
                'code' => '<?php
                    /**
                     * @template T as array-key
                     *
                     * @param non-empty-array<T, mixed> $arr
                     * @return non-empty-list<T>
                     */
                    function my_array_keys($arr) {
                        return array_keys($arr);
                    }

                    $a = my_array_keys(["hello" => 5, "goodbye" => new \Exception()]);',
                'assertions' => [
                    '$a' => 'non-empty-list<string>',
                ],
            ],
            'genericArrayFlip' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @template TValue
                     *
                     * @param array<TValue> $arr
                     */
                    function byRef(array &$arr) : void {}

                    $b = ["a" => 5, "c" => 6];
                    byRef($b);',
                'assertions' => [
                    '$b' => 'array<array-key, int>',
                ],
            ],
            'mixedArrayPop' => [
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'genericArrayPop' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                    '$a' => 'array<int<0, 2>, int>',
                ],
            ],
            'passArrayByRef' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'ignored_issues' => ['MixedAssignment', 'MissingParamType'],
            ],
            'bindFirstTemplatedClosureParameterValid' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class Foo {}

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class SomeIterator implements IteratorAggregate
                    {
                        public function getIterator() {
                            yield new Foo;
                        }
                    }

                    $i = (new SomeIterator())->getIterator();',
                'assertions' => [
                    '$i' => 'Traversable<mixed, mixed>',
                ],
            ],
            'upcastArrayToIterable' => [
                'code' => '<?php
                    /**
                     * @template K
                     * @template V
                     * @param iterable<K,V> $collection
                     * @return V
                     * @psalm-suppress InvalidReturnType
                     */
                    function first($collection) {}

                    $one = first([1,2,3]);',
                'assertions' => [
                    '$one' => 'int',
                ],
            ],
            'templateIntersectionLeft' => [
                'code' => '<?php
                    interface I1 {}
                    interface I2 {}

                    /**
                     * @template T as I1&I2
                     * @param T $a
                     */
                    function templatedBar(I1 $a) : void {}',
            ],
            'templateIntersectionRight' => [
                'code' => '<?php
                    interface I1 {}
                    interface I2 {}

                    /**
                     * @template T as I1&I2
                     * @param T $b
                     */
                    function templatedBar(I2 $b) : void {}',
            ],
            'matchMostSpecificTemplate' => [
                'code' => '<?php
                    /**
                     * @template TReturn
                     * @param callable():(\Generator<mixed, mixed, mixed, TReturn>|TReturn) $gen
                     * @return array<int, TReturn>
                     */
                    function call(callable $gen) : array {
                        $return = $gen();
                        if ($return instanceof Generator) {
                            return [$return->getReturn()];
                        }
                        /** @var array<int, TReturn> */
                        $wrapped_gen = [$gen];
                        return $wrapped_gen;
                    }

                    $arr = call(
                        function() {
                            yield 1;
                            return "hello";
                        }
                    );',
                'assertions' => [
                    '$arr' => 'array<int, string>',
                ],
            ],
            'templateOfWithSpace' => [
                'code' => '<?php
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
                'code' => '<?php
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
                        [rand(0, 1) ? 5 : false]
                    );',
            ],
            'functionTemplateUnionType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'assertions' => [
                    '$b' => 'string',
                    '$c' => 'int',
                ],
            ],
            'dontGeneraliseBoundParamWithWiderCallable' => [
                'code' => '<?php
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
                'assertions' => [
                    '$c' => 'C',
                ],
            ],
            'allowTemplateTypeBeingUsedInsideFunction' => [
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @template T as Foo
                     * @param T $foo
                     * @return T
                     */
                    function loader($foo) {
                        return $foo::getAnother();
                    }

                    /**
                     * @psalm-consistent-constructor
                     */
                    class Foo {
                        /** @return static */
                        public static function getAnother() {
                            return new static();
                        }
                    }',
            ],
            'templatedVarOnReturn' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'interpretFunctionCallableReturnValue' => [
                'code' => '<?php
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
                    client($staticIdGenerator());',
            ],
            'noCrashWhenTemplatedClassIsStatic' => [
                'code' => '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
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
                    }',
            ],
            'unboundVariableIsEmpty' => [
                'code' => '<?php
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

                    echo collect("a");',
            ],
            'paramOutDontLeak' => [
                'code' => '<?php
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
            'narrowTemplateTypeWithIsObject' => [
                'code' => '<?php
                    function takesObject(object $object): void {}

                    /**
                     * @template T as mixed
                     * @param T $value
                     */
                    function example($value): void {
                        if (is_object($value)) {
                            takesObject($value);
                        }
                    }',
            ],
            'falseDefault' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $v
                     * @return T
                     */
                    function exampleWithNullDefault($v = false) {
                       return $v;
                    }',
            ],
            'nullDefault' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T $v
                     * @return T
                     */
                    function exampleWithNullDefault($v = null) {
                       return $v;
                    }',
            ],
            'uasortCallable' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @psalm-param array<T> $collection
                     * @psalm-param callable(T, T): int $sorter
                     * @psalm-return array<T>
                     */
                    function order(array $collection, callable $sorter): array {
                        usort($collection, $sorter);

                        return $collection;
                    }',
            ],
            'callableInference' => [
                'code' => '<?php
                    class Foo {}
                    class FooChild extends Foo {}
                    class Bar {}

                    /**
                     * @psalm-template ThingType of Foo
                     * @psalm-param ThingType $t
                     * @return ThingType|Bar
                     */
                    function from_other(Foo $t) {
                        if  (rand(0, 1)) {
                            return new Bar();
                        }
                        return $t;
                    }

                    /**
                     * @param list<FooChild> $a
                     * @return list<Bar|FooChild>
                     */
                    function baz(array $a) {
                        return array_map("from_other", $a);
                    }',
            ],
            'templateFlipIntersection' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     * @template S as object
                     * @param S&T $item
                     * @return T&S
                     */
                    function filter(object $item) {
                        return $item;
                    }',
            ],
            'splatIntoTemplatedArray' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param array<T> ...$iterators
                     * @return Generator<T>
                     */
                    function joinBySplat(array ...$iterators): iterable {
                        foreach ($iterators as $iter) {
                            foreach ($iter as $value) {
                                yield $value;
                            }
                        }
                    }

                    /**
                     * @return Generator<int, array<int>>
                     */
                    function genIters(): Generator {
                        yield [1,2,3];
                        yield [4,5,6];
                    }

                    $values = joinBySplat(...genIters());

                    foreach ($values as $value) {
                        echo $value;
                    }',
            ],
            'allowTemplatedCast' => [
                'code' => '<?php
                    /**
                     * @psalm-template Tk of array-key
                     * @psalm-param Tk $key
                     */
                    function at($key) : void {
                        echo (string) $key;
                    }',
            ],
            'uksortNoNamespace' => [
                'code' => '<?php
                    /**
                     * @template Tk of array-key
                     * @template Tv
                     *
                     * @param array<Tk, Tv> $result
                     * @param (callable(Tk, Tk): int) $comparator
                     *
                     * @preturn array<Tk, Tv>
                     */
                    function sort_by_key(array $result, callable $comparator): array
                    {
                        \uksort($result, $comparator);
                        return $result;
                    }',
            ],
            'uksortNamespaced' => [
                'code' => '<?php
                    namespace Psl\Arr;

                    /**
                     * @template Tk of array-key
                     * @template Tv
                     *
                     * @param array<Tk, Tv> $result
                     * @param (callable(Tk, Tk): int) $comparator
                     *
                     * @preturn array<Tk, Tv>
                     */
                    function sort_by_key(array $result, callable $comparator): array
                    {
                        \uksort($result, $comparator);
                        return $result;
                    }',
            ],
            'mockObject' => [
                'code' => '<?php
                    class MockObject {}

                    /**
                     * @psalm-template T1 of object
                     * @psalm-param    class-string<T1> $originalClassName
                     * @psalm-return   MockObject&T1
                     */
                    function foo(string $originalClassName): MockObject {
                        return createMock($originalClassName);
                    }

                    /**
                     * @psalm-suppress InvalidReturnType
                     * @psalm-suppress InvalidReturnStatement
                     *
                     * @psalm-template T2 of object
                     * @psalm-param class-string<T2> $originalClassName
                     * @psalm-return MockObject&T2
                     */
                    function createMock(string $originalClassName): MockObject {
                        return new MockObject;
                    }',
            ],
            'testStringCallableInference' => [
                'code' => '<?php
                    class A {
                        public static function dup(string $a): string {
                            return $a . $a;
                        }
                    }

                    /**
                     * @template T
                     * @param iterable<T> $iter
                     * @return list<T>
                     */
                    function toArray(iterable $iter): array {
                        $data = [];
                        foreach ($iter as $val) {
                            $data[] = $val;
                        }
                        return $data;
                    }

                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return callable(iterable<int, T>): iterable<int, U>
                     */
                    function map(callable $predicate): callable {
                        return
                        /** @param iterable<int, T> $iter */
                        function(iterable $iter) use ($predicate): iterable {
                            foreach ($iter as $key => $value) {
                                yield $key => $predicate($value);
                            }
                        };
                    }

                    /** @param list<string> $strings */
                    function _test(array $strings): void {}
                    $a =  map([A::class, "dup"])(["a", "b", "c"]);',
                'assertions' => [
                    '$a' => 'iterable<int, string>',
                ],
            ],
            'testClosureCallableInference' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param iterable<T> $iter
                     * @return list<T>
                     */
                    function toArray(iterable $iter): array {
                        $data = [];
                        foreach ($iter as $val) {
                            $data[] = $val;
                        }
                        return $data;
                    }

                    /**
                     * @template T
                     * @template U
                     * @param callable(T): U $predicate
                     * @return callable(iterable<int, T>): iterable<int, U>
                     */
                    function map(callable $predicate): callable {
                        return
                        /** @param iterable<int, T> $iter */
                        function(iterable $iter) use ($predicate): iterable {
                            foreach ($iter as $key => $value) {
                                yield $key => $predicate($value);
                            }
                        };
                    }

                    /** @param list<string> $strings */
                    function _test(array $strings): void {}

                    $a = map(
                        function (string $a) {
                            return $a . $a;
                        }
                    )(["a", "b", "c"]);',
                'assertions' => [
                    '$a' => 'iterable<int, string>',
                ],
            ],
            'possiblyNullMatchesTemplateType' => [
                'code' => '<?php
                    /**
                     * @template T as object
                     * @param T $o
                     * @return T
                     */
                    function takesObject(object $o) : object {
                        return $o;
                    }

                    class A {}

                    /** @psalm-suppress PossiblyNullArgument */
                    $a = takesObject(rand(0, 1) ? new A() : null);',
                'assertions' => [
                    '$a' => 'A',
                ],
            ],
            'possiblyNullMatchesAnotherTemplateType' => [
                'code' => '<?php
                    /**
                     * @psalm-template RealObjectType of object
                     *
                     * @psalm-param class-string<RealObjectType> $className
                     * @psalm-param Closure(
                     *   RealObjectType|null
                     * ) : void $initializer
                     */
                    function createProxy(
                        string $className,
                        Closure $initializer
                    ) : void {}

                    class Foo {}

                    createProxy(Foo::class, function (?Foo $f) : void {});',
            ],
            'assertIntersectionsOnTemplatedTypes' => [
                'code' => '<?php
                    interface Input {}
                    interface HasFoo {}
                    interface HasBar {}

                    /**
                     * @psalm-template InputType of Input
                     * @psalm-param InputType $input
                     * @psalm-return InputType&HasFoo
                     */
                    function decorateWithFoo(Input $input): Input
                    {
                        assert($input instanceof HasFoo);
                        return $input;
                    }

                    /**
                     * @psalm-template InputType of Input
                     * @psalm-param InputType $input
                     * @psalm-return InputType&HasBar
                     */
                    function decorateWithBar(Input $input): Input
                    {
                        assert($input instanceof HasBar);
                        return $input;
                    }

                    /** @param HasFoo&HasBar $input */
                    function useFooAndBar(object $input): void {}

                    function consume(Input $input): void {
                        useFooAndBar(decorateWithFoo(decorateWithBar($input)));
                    }',
            ],
            'bottomTypeInClosureShouldNotBind' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param class-string<T> $className
                     * @param Closure(T):void $outmaker
                     * @return T
                     */
                    function createProxy(
                        string $className,
                        Closure $outmaker
                    ) : object {
                        /** @psalm-suppress MixedMethodCall */
                        $t = new $className();
                        $outmaker($t);
                        return $t;
                    }

                    class A {
                        public function bar() : void {}
                    }

                    createProxy(A::class, function(object $o):void {})->bar();',
            ],
            'bottomTypeInNamespacedCallableShouldMatch' => [
                'code' => '<?php
                    namespace Ns;

                    /**
                     * @template T
                     * @param class-string<T> $className
                     * @param callable(T):void $outmaker
                     * @return T
                     */
                    function createProxy(
                        string $className,
                        callable $outmaker
                    ) : object {
                        /** @psalm-suppress MixedMethodCall */
                        $t = new $className();
                        $outmaker($t);
                        return $t;
                    }

                    class A {
                        public function bar() : void {}
                    }

                    function foo(A $o):void {}

                    createProxy(A::class, \'Ns\foo\')->bar();',
            ],
            'compareToEmptyArray' => [
                'code' => '<?php
                    /**
                     * @template T
                     *
                     * @param T $a
                     * @return T
                     */
                    function ex($a) {
                        if($a === []) {}
                        return $a;
                    }',
            ],
            'compareToFalse' => [
                'code' => '<?php
                    /**
                     * @template T as int|false
                     * @param T $value
                     * @return int
                     */
                    function foo($value) {
                        if ($value === false) {
                           return -1;
                        }
                        return $value;
                    }',
            ],
            'refineTemplateTypeNotEmpty' => [
                'code' => '<?php
                    /**
                     * @template T of Iterator|null
                     * @param T $iterator
                     */
                    function toArray($iterator): array
                    {
                        if ($iterator) {
                            return iterator_to_array($iterator);
                        }

                        return [];
                    }',
            ],
            'manyGenericParams' => [
                'code' => '<?php
                    /**
                     * @template TArg1
                     * @template TArg2
                     * @template TRes
                     *
                     * @psalm-param Closure(TArg1, TArg2): TRes $func
                     * @psalm-param TArg1 $arg1
                     *
                     * @psalm-return Closure(TArg2): TRes
                     */
                    function partial(Closure $func, $arg1): Closure {
                        return fn($arg2) => $func($arg1, $arg2);
                    }

                    /**
                     * @template TArg1
                     * @template TArg2
                     * @template TRes
                     *
                     * @template T as (Closure(): TRes | Closure(TArg1): TRes | Closure(TArg1, TArg2): TRes)
                     *
                     * @psalm-param T $fn
                     * @psalm-param TArg1 $arg
                     */
                    function foo(Closure $fn, $arg): void {
                        $a = partial($fn, $arg);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'mixedDoesntSwallowNull' => [
                'code' => '<?php
                    /**
                     * @template E
                     * @param E $e
                     * @param mixed $d
                     * @return ?E
                     * @psalm-suppress MixedInferredReturnType
                     */
                    function reduce_values($e, $d) {
                        if (rand(0, 1)) {
                            $c = $e;
                        } elseif (rand(0, 1)) {
                            /** @psalm-suppress MixedAssignment */
                            $c = $d;
                        } else {
                            $c = null;
                        }

                        /** @psalm-suppress MixedReturnStatement */
                        return $c;
                    }',
            ],
            'mixedDoesntSwallowNullProgressive' => [
                'code' => '<?php
                    /**
                     * @template E
                     * @param E $e
                     * @param mixed $d
                     * @return ?E
                     * @psalm-suppress MixedInferredReturnType
                     */
                    function reduce_values($e, $d)
                    {
                        if (rand(0, 1)) {
                            $d = $e;
                        }

                        if (rand(0, 1)) {
                            /** @psalm-suppress MixedReturnStatement */
                            return $d;
                        }

                        return null;
                    }',
            ],
            'inferIterableArrayKeyAfterIsArrayCheck' => [
                'code' => '<?php
                    /**
                     * @template Key
                     * @template Element
                     * @psalm-param iterable<Key, Element> $input
                     * @psalm-return Iterator<Key, Element>
                     */
                    function to_iterator(iterable $input): Iterator
                    {
                        if (\is_array($input)) {
                            return new \ArrayIterator($input);
                        } elseif ($input instanceof Iterator) {
                            return $input;
                        } else {
                            return new \IteratorIterator($input);
                        }
                    }',
            ],
            'doublyNestedFunctionTemplates' => [
                'code' => '<?php
                    /**
                     * @psalm-template Tk
                     * @psalm-template Tv
                     *
                     * @psalm-param iterable<Tk, Tv>                $iterable
                     * @psalm-param (callable(Tk, Tv): bool)|null   $predicate
                     *
                     * @psalm-return iterable<Tk, Tv>
                     */
                    function filter_with_key(iterable $iterable, ?callable $predicate = null): iterable
                    {
                        return (static function () use ($iterable, $predicate): Generator {
                            $predicate = $predicate ??
                                /**
                                 * @psalm-param Tk $_k
                                 * @psalm-param Tv $v
                                 *
                                 * @return bool
                                 */
                                function($_k, $v) { return (bool) $v; };

                            foreach ($iterable as $k => $v) {
                                if ($predicate($k, $v)) {
                                    yield $k => $v;
                                }
                            }
                        })();
                    }',
            ],
            'allowClosureParamLowerBoundAndUpperBound' => [
                'code' => '<?php
                    class Foo {}

                    /**
                     * @template TParam as Foo
                     * @psalm-param Closure(TParam): void $func
                     * @psalm-return Closure(TParam): TParam
                     */
                    function takesClosure(callable $func): callable {
                        return
                            /**
                             * @psalm-param TParam $value
                             * @psalm-return TParam
                             */
                            function ($value) use ($func) {
                                $func($value);
                                return $value;
                            };
                    }

                    $value = takesClosure(function(Foo $foo) : void {})(new Foo());',
            ],
            'subtractTemplatedNull' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T|null $var
                     * @return T
                     */
                    function notNull($var) {
                        if ($var === null) {
                            throw new \InvalidArgumentException("");
                        }

                        return $var;
                    }

                    function takesNullableString(?string $s) : string {
                        return notNull($s);
                    }',
            ],
            'subtractTemplatedInt' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param T|int $var
                     * @return T
                     */
                    function notNull($var) {
                        if (\is_int($var)) {
                            throw new \InvalidArgumentException("");
                        }

                        return $var;
                    }

                    function takesNullableString(string|int $s) : string {
                        return notNull($s);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'templateChildClass' => [
                'code' => '<?php
                    /** @template T */
                    class Collection {
                        /**
                         * @param T $t
                         */
                        private function add($t) : void {}

                        /**
                         * @template TChild as T
                         * @param TChild $default
                         *
                         * @return TChild
                         */
                        public function get($default)
                        {
                            $this->add($default);

                            return $default;
                        }
                    }',
            ],
            'isArrayCheckOnTemplated' => [
                'code' => '<?php
                    /**
                     * @template TIterable of iterable
                     */
                    function toList(iterable $iterable): void {
                        if (is_array($iterable)) {}
                    }',
            ],
            'transformNestedTemplateWherePossible' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template TArray of non-empty-array<TValue>
                     * @param TArray $arr
                     * @return TValue
                     */
                    function toList(array $arr): array {
                        return reset($arr);
                    }',
            ],
            'callTemplatedFunctionWithTemplatedClassString' => [
                'code' => '<?php
                    /**
                     * @template Ta of object
                     * @psalm-param Ta $obj
                     * @return Ta
                     */
                    function a(string $str, object $obj) {
                        $class = get_class($obj);
                        return deserialize_object($str, $class);
                    }

                    /**
                     * @psalm-template Tb
                     * @psalm-param class-string<Tb> $type
                     * @psalm-return Tb
                     * @psalm-suppress InvalidReturnType
                     */
                    function deserialize_object(string $data, string $type) {}',
            ],
            'arrayKeyInTemplateOfArrayKey' => [
                'code' => '<?php

                    /**
                     * @template TKey of array-key
                     * @template TValue
                     * @template TNewKey of array-key
                     * @template TNewValue
                     * @psalm-param iterable<TKey, TValue> $iterable
                     * @psalm-param callable(TKey): iterable<TNewKey, TNewValue> $mapper
                     * @psalm-return \Generator<TNewKey, TNewValue>
                     */
                    function map(iterable $iterable, callable $mapper): Generator
                    {
                        foreach ($iterable as $key => $_) {
                            yield from $mapper($key);
                        }
                    }

                    /**
                     * @psalm-return iterable<array-key, \stdClass>
                     */
                    function iter(): iterable
                    {
                        return [];
                    }

                    /**
                     * @template TKey of array-key
                     * @psalm-param TKey $key
                     * @psalm-return Generator<TKey, string>
                     */
                    function mapper($key): Generator
                    {
                        yield $key => "a";
                    }

                    map(iter(), "mapper");',
            ],
            'dontScreamForArithmeticsOnIntTemplates' => [
                'code' => '<?php

                    /**
                     * @template T of int|string
                     * @param T $p
                     */
                    function foo($p): void {
                        if (is_int($p)) {
                            $q = $p - 1;
                        }
                    }',
            ],
            'dontScreamForArithmeticsOnFloatTemplates' => [
                'code' => '<?php
                    /**
                     * @template T of ?float
                     * @param T $p
                     * @return (T is null ? null : float)
                     */
                    function foo(?float $p): ?float
                    {
                        if ($p === null) {
                            return null;
                        }
                        return $p - 1;
                    }',
            ],
            'literalIsAlwaysContainedInString' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface Norm
                    {
                        /**
                         * @param T $input
                         * @return T
                         */
                        public function normalize(mixed $input): mixed;
                    }

                    /**
                     * @implements Norm<string>
                     */
                    class StringNorm implements Norm
                    {
                        public function normalize(mixed $input): mixed
                        {
                            return strtolower($input);
                        }
                    }

                    /**
                     * @template TNorm
                     *
                     * @param TNorm $value
                     * @param Norm<TNorm> $n
                     */
                    function normalizeField(mixed $value, Norm $n): void
                    {
                        $n->normalize($value);
                    }

                    normalizeField("foo", new StringNorm());',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'templateWithCommentAfterSimpleType' => [
                'code' => '<?php
                    /**
                     * @template T of string
                     *
                     * lorem ipsumm
                     *
                     * @param T $t
                     */
                    function foo(string $t): string
                    {
                        return $t;
                    }',
            ],
            'typeWithNestedTemplates' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     */
                    interface AType {}

                    /**
                     * @template T of object
                     * @template B of AType<T>
                     */
                    final class BType {}

                    /**
                     * @param BType<object, AType<object>> $_value
                     */
                    function test1(BType $_value): void {}

                    /**
                     * @param BType<stdClass, AType<stdClass>> $_value
                     */
                    function test2(BType $_value): void {}',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidTemplatedType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'error_message' => 'InvalidArgument',
            ],
            'multipleArgConstraintWithMoreRestrictiveFirstArg' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php

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
                'code' => '<?php

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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'error_message' => 'InvalidArgument',
            ],
            'invalidTemplateDocblock' => [
                'code' => '<?php
                    /** @template */
                    function f():void {}',
                'error_message' => 'MissingDocblockType',
            ],
            'returnNamedObjectWhereTemplateIsExpected' => [
                'code' => '<?php
                    class Bar {}

                    /**
                     * @template T as object
                     * @param T $t
                     * @return T
                     */
                    function shouldComplain(object $t) {
                        return new Bar();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'returnIntersectionWhenTemplateIsExpectedForward' => [
                'code' => '<?php
                    interface Baz {}

                    /**
                     * @template T as object
                     * @param T $t
                     * @return T&Baz
                     */
                    function returnsTemplatedIntersection(object $t) {
                        return $t;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'returnIntersectionWhenTemplateIsExpectedBackward' => [
                'code' => '<?php
                    interface Baz {}

                    /**
                     * @template T as object
                     * @param T $t
                     * @return Baz&T
                     */
                    function returnsTemplatedIntersection(object $t) {
                        return $t;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'bottomTypeInClosureShouldClash' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @param class-string<T> $className
                     * @param Closure(T):void $outmaker
                     * @return T
                     */
                    function createProxy(
                        string $className,
                        Closure $outmaker
                    ) : object {
                        /** @psalm-suppress MixedMethodCall */
                        $t = new $className();
                        $outmaker($t);
                        return $t;
                    }

                    class A {
                        public function bar() : void {}
                    }

                    class B {}

                    createProxy(A::class, function(B $o):void {})->bar();',
                'error_message' => 'InvalidArgument',
            ],
            'bottomTypeInNamespacedCallableShouldClash' => [
                'code' => '<?php
                    namespace Ns;

                    /**
                     * @template T
                     * @param class-string<T> $className
                     * @param callable(T):void $outmaker
                     * @return T
                     */
                    function createProxy(
                        string $className,
                        callable $outmaker
                    ) : object {
                        /** @psalm-suppress MixedMethodCall */
                        $t = new $className();
                        $outmaker($t);
                        return $t;
                    }

                    class A {
                        public function bar() : void {}
                    }

                    class B {}

                    function foo(B $o):void {}

                    createProxy(A::class, \'Ns\foo\')->bar();',
                'error_message' => 'InvalidArgument',
            ],
            'preventBadArraySubtyping' => [
                'code' => '<?php
                    /**
                     * @template T as array{a: int}
                     * @return T
                     */
                    function foo() : array {
                        $b = ["a" => 123];
                        return $b;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'modifyTemplatedShape' => [
                'code' => '<?php
                    /**
                     * @template T as array{a: int}
                     * @param T $s
                     * @return T
                     */
                    function foo(array $s) : array {
                        $s["a"] = 123;
                        return $s;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'preventArrayOverwriting' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @return T
                     */
                    function foo(array $b) : array {
                        return $b;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'catchIssueInTemplatedFunctionInsideClass' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface Container {
                        /** @param T $value */
                        public function take($value): void;
                    }

                    class Foo {
                        /**
                         * @template T
                         * @param Container<T> $c
                         */
                        function jsonFromEntityCollection(Container $c): void {
                            $c->take("foo");
                        }
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'catchInvalidTemplateTypeWithNestedTemplates' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface AType {}

                    /**
                     * @template T
                     * @template B of AType<T>
                     */
                    final class BType {}

                    /**
                     * @param BType<string, AType<int>> $_value
                     */
                    function test1(BType $_value): void {}

                    /**
                     * @param BType<int, AType<string>> $_value
                     */
                    function test2(BType $_value): void {}',
                'error_message' => 'InvalidTemplateParam',
            ],
        ];
    }
}
