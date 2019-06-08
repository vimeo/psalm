<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class TemplateTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'classTemplate' => [
                '<?php
                    class A {}
                    class B {}
                    class C {}
                    class D {}

                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var T::class */
                        public $T;

                        /**
                         * @param class-string $T
                         * @template-typeof T $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    $at = "A";

                    /** @var Foo<A> */
                    $afoo = new Foo($at);
                    $afoo_bar = $afoo->bar();

                    $bfoo = new Foo(B::class);
                    $bfoo_bar = $bfoo->bar();

                    // this shouldn’t cause a problem as it’s a docbblock type
                    if (!($bfoo_bar instanceof B)) {}

                    $c = C::class;
                    $cfoo = new Foo($c);
                    $cfoo_bar = $cfoo->bar();',
                'assertions' => [
                    '$afoo' => 'Foo<A>',
                    '$afoo_bar' => 'A',

                    '$bfoo' => 'Foo<B>',
                    '$bfoo_bar' => 'B',

                    '$cfoo' => 'Foo<C>',
                    '$cfoo_bar' => 'C',
                ],
                'error_levels' => [
                    'MixedReturnStatement',
                    'LessSpecificReturnStatement',
                    'DocblockTypeContradiction',
                    'TypeCoercion',
                ],
            ],
            'classTemplateSelfs' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var T::class */
                        public $T;

                        /**
                         * @param class-string $T
                         * @template-typeof T $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    class E {
                        /**
                         * @return Foo<self>
                         */
                        public static function getFoo() {
                            return new Foo(__CLASS__);
                        }

                        /**
                         * @return Foo<self>
                         */
                        public static function getFoo2() {
                            return new Foo(self::class);
                        }

                        /**
                         * @return Foo<static>
                         */
                        public static function getFoo3() {
                            return new Foo(static::class);
                        }
                    }

                    class G extends E {}

                    $efoo = E::getFoo();
                    $efoo2 = E::getFoo2();
                    $efoo3 = E::getFoo3();

                    $gfoo = G::getFoo();
                    $gfoo2 = G::getFoo2();
                    $gfoo3 = G::getFoo3();',
                'assertions' => [
                    '$efoo' => 'Foo<E>',
                    '$efoo2' => 'Foo<E>',
                    '$efoo3' => 'Foo<E>',
                    '$gfoo' => 'Foo<E>',
                    '$gfoo2' => 'Foo<E>',
                    '$gfoo3' => 'Foo<G>',
                ],
                'error_levels' => [
                    'LessSpecificReturnStatement',
                    'RedundantConditionGivenDocblockType',
                ],
            ],
            'classTemplateExternalClasses' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var T::class */
                        public $T;

                        /**
                         * @param class-string $T
                         * @template-typeof T $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    $efoo = new Foo(\Exception::class);
                    $efoo_bar = $efoo->bar();

                    $ffoo = new Foo(\LogicException::class);
                    $ffoo_bar = $ffoo->bar();',
                'assertions' => [
                    '$efoo' => 'Foo<Exception>',
                    '$efoo_bar' => 'Exception',

                    '$ffoo' => 'Foo<LogicException>',
                    '$ffoo_bar' => 'LogicException',
                ],
                'error_levels' => ['LessSpecificReturnStatement'],
            ],
            'classTemplateContainer' => [
                '<?php
                    class A {}

                    /**
                     * @template T
                     */
                    class Foo {
                        /** @var T */
                        public $obj;

                        /**
                         * @param T $obj
                         */
                        public function __construct($obj) {
                            $this->obj = $obj;
                        }

                        /**
                         * @return T
                         */
                        public function bar() {
                            return $this->obj;
                        }

                        /**
                         * @return T
                         */
                        public function bat() {
                            return $this->bar();
                        }

                        public function __toString(): string {
                            return "hello " . $this->obj;
                        }
                    }

                    $afoo = new Foo(new A());
                    $afoo_bar = $afoo->bar();',
                'assertions' => [
                    '$afoo' => 'Foo<A>',
                    '$afoo_bar' => 'A',
                ],
                'error_levels' => ['MixedOperand'],
            ],
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
            'validPsalmTemplatedClassType' => [
                '<?php
                    class A {}

                    /**
                     * @psalm-template T
                     */
                    class Foo {
                        /**
                         * @param T $x
                         */
                        public function bar($x): void { }
                    }

                    $afoo = new Foo();
                    $afoo->bar(new A());',
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
                    '$a' => 'null|int',
                    '$b' => 'array<string, int>',
                ],
            ],
            'intersectionTemplatedTypes' => [
                '<?php
                    namespace NS;
                    use Countable;

                    /** @template T */
                    class Collection
                    {
                        /** @psalm-var iterable<T> */
                        private $data;

                        /** @psalm-param iterable<T> $data */
                        public function __construct(iterable $data) {
                            $this->data = $data;
                        }
                    }

                    class Item {}
                    /** @psalm-param Collection<Item> $c */
                    function takesCollectionOfItems(Collection $c): void {}

                    /** @psalm-var iterable<Item> $data2 */
                    $data2 = [];
                    takesCollectionOfItems(new Collection($data2));

                    /** @psalm-var iterable<Item>&Countable $data */
                    $data = [];
                    takesCollectionOfItems(new Collection($data));',
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
            'repeatedCall' => [
                '<?php
                    namespace NS;

                    use Closure;

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    class ArrayCollection {
                        /** @var array<TKey,TValue> */
                        private $data;

                        /** @param array<TKey,TValue> $data */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template T
                         * @param Closure(TValue):T $func
                         * @return ArrayCollection<TKey,T>
                         */
                        public function map(Closure $func) {
                          return new static(array_map($func, $this->data));
                        }
                    }

                    class Item {}
                    /**
                     * @param ArrayCollection<array-key,Item> $i
                     */
                    function takesCollectionOfItems(ArrayCollection $i): void {}

                    $c = new ArrayCollection([ new Item ]);
                    takesCollectionOfItems($c);
                    takesCollectionOfItems($c->map(function(Item $i): Item { return $i;}));
                    takesCollectionOfItems($c->map(function(Item $i): Item { return $i;}));',
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
            'noRepeatedTypeException' => [
                '<?php
                    /** @template T */
                    class Foo
                    {
                        /**
                         * @psalm-var class-string
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param class-string $type
                         * @template-typeof T $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class FooFacade
                    {
                        /**
                         * @template T
                         * @param  T $item
                         */
                        public function add($item): void
                        {
                            $foo = $this->ensureFoo([$item]);
                            $foo->add($item);
                        }

                        /**
                         * @template T
                         * @param  array<mixed,T> $items
                         * @return Foo<T>
                         */
                        private function ensureFoo(array $items): Foo
                        {
                            $type = $items[0] instanceof A ? A::class : B::class;
                            return new Foo($type);
                        }
                    }

                    class A {}
                    class B {}',
            ],
            'collectionOfClosure' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class Collection {
                        /**
                         * @param Closure(TValue):bool $p
                         * @return Collection<TKey,TValue>
                         * @psalm-suppress MixedTypeCoercion
                         */
                        public function filter(Closure $p) {
                            return $this;
                        }
                    }
                    class I {}

                    /** @var Collection<mixed,Collection<mixed,I>> $c */
                    $c = new Collection;

                    $c->filter(
                      /** @param Collection<mixed,I> $elt */
                      function(Collection $elt): bool { return (bool) rand(0,1); }
                    );

                    $c->filter(
                      /** @param Collection<mixed,I> $elt */
                      function(Collection $elt): bool { return true; }
                    );',
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
            'templatedInterfaceIteration' => [
                '<?php
                    namespace NS;

                    /**
                     * @template TKey
                     * @template TValue
                     */
                    interface ICollection extends \IteratorAggregate {
                        /** @return \Traversable<TKey,TValue> */
                        public function getIterator();
                    }

                    class Collection implements ICollection {
                        /** @var array */
                        private $data;
                        public function __construct(array $data) {
                            $this->data = $data;
                        }
                        /** @psalm-suppress LessSpecificImplementedReturnType */
                        public function getIterator(): \Traversable {
                            return new \ArrayIterator($this->data);
                        }
                    }

                    /** @var ICollection<string, int> */
                    $c = new Collection(["a" => 1]);

                    foreach ($c as $k => $v) { atan($v); strlen($k); }',
            ],
            'templatedInterfaceGetIteratorIteration' => [
                '<?php
                    namespace NS;

                    /**
                     * @template TKey
                     * @template TValue
                     */
                    interface ICollection extends \IteratorAggregate {
                        /** @return \Traversable<TKey,TValue> */
                        public function getIterator();
                    }

                    class Collection implements ICollection {
                        /** @var array */
                        private $data;
                        public function __construct(array $data) {
                            $this->data = $data;
                        }
                        /** @psalm-suppress LessSpecificImplementedReturnType */
                        public function getIterator(): \Traversable {
                            return new \ArrayIterator($this->data);
                        }
                    }

                    /** @var ICollection<string, int> */
                    $c = new Collection(["a" => 1]);

                    foreach ($c->getIterator() as $k => $v) { atan($v); strlen($k); }',
            ],
            'implictIteratorTemplating' => [
                '<?php
                    /**
                     * @template-implements IteratorAggregate<int, int>
                     */
                    class SomeIterator implements IteratorAggregate
                    {
                        function getIterator()
                        {
                            yield 1;
                        }
                    }

                    /** @param \IteratorAggregate<mixed, int> $i */
                    function takesIteratorOfInts(\IteratorAggregate $i) : void {
                        foreach ($i as $j) {
                            echo $j;
                        }
                    }

                    takesIteratorOfInts(new SomeIterator());',
            ],
            'allowTemplatedIntersectionToExtend' => [
                '<?php
                    interface Foo {}

                    interface AlmostFoo {
                        /**
                         * @return Foo
                         */
                        public function makeFoo();
                    }

                    /**
                     * @template T
                     */
                    final class AlmostFooMap implements AlmostFoo {
                        /** @var T&Foo */
                        private $bar;

                        /**
                         * @param T&Foo $bar
                         */
                        public function __construct(Foo $bar)
                        {
                            $this->bar = $bar;
                        }

                        /**
                         * @return T&Foo
                         */
                        public function makeFoo()
                        {
                            return $this->bar;
                        }
                    }',
            ],
            'restrictTemplateInputWithTClassGoodInput' => [
                '<?php
                    namespace Bar;

                    /** @template T */
                    class Foo
                    {
                        /**
                         * @psalm-var T::class
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param T::class $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class A {}
                    class B {}

                    $foo = new Foo(A::class);
                    $foo->add(new A);',
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
            'classTemplateFunctionImplementsInterface' => [
                '<?php
                    namespace A\B;

                    interface Foo {}

                    interface IFooGetter {
                        /**
                         * @return Foo
                         */
                        public function getFoo();
                    }

                    /**
                     * @template T as Foo
                     */
                    class FooGetter implements IFooGetter {
                        /** @var T */
                        private $t;

                        /**
                         * @param T $t
                         */
                        public function __construct(Foo $t)
                        {
                            $this->t = $t;
                        }

                        /**
                         * @return T
                         */
                        public function getFoo()
                        {
                            return $this->t;
                        }
                    }

                    function passFoo(Foo $f) : Foo {
                        return (new FooGetter($f))->getFoo();
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
            'returnClassString' => [
                '<?php
                    /**
                     * @template T
                     * @param T::class $s
                     * @return T::class
                     */
                    function foo(string $s) : string {
                        return $s;
                    }

                    /**
                     * @param  A::class $s
                     */
                    function bar(string $s) : void {
                    }

                    class A {}

                    bar(foo(A::class));',
            ],
            'callStaticMethodOnTemplatedClassName' => [
                '<?php
                    /**
                     * @template T
                     * @param class-string $class
                     * @template-typeof T $class
                     */
                    function foo(string $class, array $args) : void {
                        $class::bar($args);
                    }',
                'assertions' => [],
                'error_levels' => ['MixedMethodCall'],
            ],
            'returnTemplatedClassClassName' => [
                '<?php
                    class I {
                        /**
                         * @template T as Foo
                         * @param class-string $class
                         * @template-typeof T $class
                         * @return T|null
                         */
                        public function loader(string $class) {
                            return $class::load();
                        }
                    }

                    class Foo {
                        /** @return static */
                        public static function load() {
                            return new static();
                        }
                    }

                    class FooChild extends Foo{}

                    $a = (new I)->loader(FooChild::class);',
                'assertions' => [
                    '$a' => 'null|FooChild',
                ],
            ],
            'upcastIterableToTraversable' => [
                '<?php
                    /**
                     * @template T as iterable
                     * @param T::class $class
                     */
                    function foo(string $class) : void {
                        $a = new $class();

                        foreach ($a as $b) {}
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment'],
            ],
            'upcastGenericIterableToGenericTraversable' => [
                '<?php
                    /**
                     * @template T as iterable<int>
                     * @param T::class $class
                     */
                    function foo(string $class) : void {
                        $a = new $class();

                        foreach ($a as $b) {}
                    }',
                'assertions' => [],
                'error_levels' => [],
            ],
            'bindFirstTemplatedClosureParameter' => [
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

                    apply(function(int $_i) : void {}, 5);
                    apply(function(string $_i) : void {}, "hello");
                    apply(function(stdClass $_i) : void {}, new stdClass);

                    class A {}
                    class AChild extends A {}

                    apply(function(A $_i) : void {}, new AChild());',
            ],
            'getPropertyOnClass' => [
                '<?php
                    class Foo {
                        /** @var int */
                        public $id = 0;
                    }

                    /**
                     * @template T as Foo
                     */
                    class Collection {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type) {
                            $this->type = $type;
                        }

                        /**
                         * @return class-string<T>
                         */
                        public function getType()
                        {
                           return $this->type;
                        }

                        /**
                         * @param T $object
                         */
                        public function bar(Foo $object) : void
                        {
                            if ($this->getType() !== get_class($object)) {
                                return;
                            }

                            echo $object->id;
                        }
                    }

                    class FooChild extends Foo {}

                    /** @param Collection<Foo> $c */
                    function handleCollectionOfFoo(Collection $c) : void {
                        if ($c->getType() === FooChild::class) {}
                    }',
            ],
            'getEquateClass' => [
                '<?php
                    class Foo {
                        /** @var int */
                        public $id = 0;
                    }

                    /**
                     * @template T as Foo
                     */
                    class Container {
                        /**
                         * @var T
                         */
                        private $obj;

                        /**
                         * @param T $obj
                         */
                        public function __construct(Foo $obj) {
                            $this->obj = $obj;
                        }

                        /**
                         * @param T $object
                         */
                        public function bar(Foo $object) : void
                        {
                            if ($this->obj === $object) {}
                        }
                    }',
            ],
            'allowComparisonGetTypeResult' => [
                '<?php
                    class Foo {}

                    /**
                     * @template T as Foo
                     */
                    class Collection {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type) {
                            $this->type = $type;
                        }

                        /**
                         * @return class-string<T>|null
                         */
                        public function getType()
                        {
                           return $this->type;
                        }
                    }

                    function foo(Collection $c) : void {
                        $val = $c->getType();
                        if (!$val) {}
                        if ($val) {}
                    }',
            ],
            'mixedTemplatedParamOutWithNoExtendedTemplate' => [
                '<?php
                    /**
                     * @template TValue
                     */
                    class ValueContainer
                    {
                        /**
                         * @var TValue
                         */
                        private $v;
                        /**
                         * @param TValue $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return TValue
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class KeyValueContainer extends ValueContainer
                    {
                        /**
                         * @var TKey
                         */
                        private $k;
                        /**
                         * @param TKey $k
                         * @param TValue $v
                         */
                        public function __construct($k, $v)
                        {
                            $this->k = $k;
                            parent::__construct($v);
                        }
                        /**
                         * @return TKey
                         */
                        public function getKey()
                        {
                            return $this->k;
                        }
                    }
                    $a = new KeyValueContainer("hello", 15);
                    $b = $a->getValue();',
                [
                    '$a' => 'KeyValueContainer<string, int>',
                    '$b' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'mixedTemplatedParamOutDifferentParamName' => [
                '<?php
                    /**
                     * @template TValue
                     */
                    class ValueContainer
                    {
                        /**
                         * @var TValue
                         */
                        private $v;
                        /**
                         * @param TValue $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return TValue
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template TKey
                     * @template Tv
                     */
                    class KeyValueContainer extends ValueContainer
                    {
                        /**
                         * @var TKey
                         */
                        private $k;
                        /**
                         * @param TKey $k
                         * @param Tv $v
                         */
                        public function __construct($k, $v)
                        {
                            $this->k = $k;
                            parent::__construct($v);
                        }
                        /**
                         * @return TKey
                         */
                        public function getKey()
                        {
                            return $this->k;
                        }
                    }
                    $a = new KeyValueContainer("hello", 15);
                    $b = $a->getValue();',
                [
                    '$a' => 'KeyValueContainer<string, int>',
                    '$b' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
            ],

            'doesntExtendTemplateAndDoesNotOverride' => [
                '<?php
                    /**
                     * @template T as array-key
                     */
                    abstract class User
                    {
                        /**
                         * @var T
                         */
                        private $id;
                        /**
                         * @param T $id
                         */
                        public function __construct($id)
                        {
                            $this->id = $id;
                        }
                        /**
                         * @return T
                         */
                        public function getID()
                        {
                            return $this->id;
                        }
                    }

                    class AppUser extends User {}

                    $au = new AppUser(-1);
                    $id = $au->getId();',
                [
                    '$au' => 'AppUser',
                    '$id' => 'array-key',
                ],
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
            'templateObjectLikeValues' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class Collection {
                        /**
                         * @return array{0:Collection<TKey,TValue>,1:Collection<TKey,TValue>}
                         * @psalm-suppress InvalidReturnType
                         */
                        public function partition() {}
                    }

                    /** @var Collection<int,string> $c */
                    $c = new Collection;
                    [$partA, $partB] = $c->partition();',
                [
                    '$partA' => 'Collection<int, string>',
                    '$partB' => 'Collection<int, string>',
                ],
            ],
            'understandTemplatedCalculationInOtherFunction' => [
                '<?php
                    /**
                     * @template T as Exception
                     * @param T::class $type
                     * @return T
                     */
                    function a(string $type): Exception {
                        return new $type;
                    }

                    /**
                     * @template T as InvalidArgumentException
                     * @param T::class $type
                     * @return T
                     */
                    function b(string $type): InvalidArgumentException {
                        return a($type);
                    }',
            ],
            'doublyLinkedListConstructor' => [
                '<?php
                    $list = new SplDoublyLinkedList();
                    $list->add(5, "hello");
                    $list->add("hello", 5);

                    /** @var SplDoublyLinkedList<int, string> */
                    $templated_list = new SplDoublyLinkedList();
                    $templated_list->add(5, "hello");
                    $a = $templated_list->bottom();',
                [
                    '$a' => 'string',
                ],
            ],
            'objectReturn' => [
                '<?php
                    /**
                     * @template T as object
                     *
                     * @param class-string<T> $foo
                     *
                     * @return T
                     */
                    function Foo(string $foo) : object {
                      return new $foo;
                    }

                    echo Foo(DateTime::class)->format("c");',
            ],
            'genericInterface' => [
                '<?php
                    /**
                     * @template T as object
                     * @param class-string<T> $t
                     * @return T
                     */
                    function generic(string $t) {
                        return f($t)->get();
                    }

                    /** @template T as object */
                    interface I {
                        /** @return T */
                        public function get() {}
                    }

                    /**
                     * @template T as object
                     * @template-implements I<T>
                     */
                    class C implements I {
                        /**
                         * @var T
                         */
                        public $t;

                        /**
                         * @param T $t
                         */
                        public function __construct(object $t) {
                            $this->t = $t;
                        }

                        /**
                         * @return T
                         */
                        public function get() {
                            return $this->t;
                        }
                    }

                    /**
                     * @template T as object
                     * @param class-string<T> $t
                     * @return I<T>
                     */
                    function f(string $t) {
                        return new C(new $t);
                    }',
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
                        return [$gen];
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
            'templatedClassStringParamAsClass' => [
                '<?php
                    abstract class C {
                        public function foo() : void{}
                    }

                    class E {
                        /**
                         * @template T as C
                         * @param class-string<T> $c_class
                         *
                         * @return C
                         * @psalm-return T
                         */
                        public static function get(string $c_class) : C {
                            $c = new $c_class;
                            $c->foo();
                            return $c;
                        }
                    }

                    /**
                     * @param class-string<C> $c_class
                     */
                    function bar(string $c_class) : void {
                        $c = E::get($c_class);
                        $c->foo();
                    }

                    /**
                     * @psalm-suppress TypeCoercion
                     */
                    function bat(string $c_class) : void {
                        $c = E::get($c_class);
                        $c->foo();
                    }',
            ],
            'templatedClassStringParamAsObject' => [
                '<?php
                    abstract class C {
                        public function foo() : void{}
                    }

                    class E {
                        /**
                         * @template T as object
                         * @param class-string<T> $c_class
                         *
                         * @psalm-return T
                         */
                        public static function get(string $c_class) {
                            return new $c_class;
                        }
                    }

                    /**
                     * @psalm-suppress TypeCoercion
                     */
                    function bat(string $c_class) : void {
                        $c = E::get($c_class);
                        $c->bar = "bax";
                    }',
            ],
            'templatedClassStringParamMoreSpecific' => [
                '<?php
                    abstract class C {
                        public function foo() : void{}
                    }

                    class D extends C {
                        public function faa() : void{}
                    }

                    class E {
                        /**
                         * @template T as C
                         * @param class-string<T> $c_class
                         *
                         * @return C
                         * @psalm-return T
                         */
                        public static function get(string $c_class) : C {
                            $c = new $c_class;
                            $c->foo();
                            return $c;
                        }
                    }

                    /**
                     * @param class-string<D> $d_class
                     */
                    function moreSpecific(string $d_class) : void {
                        $d = E::get($d_class);
                        $d->foo();
                        $d->faa();
                    }',
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
            'templateDefaultSimpleString' => [
                '<?php
                    /**
                     * @template T as string
                     */
                    class C {
                        /** @var T */
                        public $t;

                        /**
                         * @param T $t
                         */
                        function __construct(string $t = "hello") {
                            $this->t = $t;
                        }
                    }

                    $c = new C();',
                'assertions' => [
                    '$c===' => 'C<string(hello)>',
                ],
            ],
            'SKIPPED-templateDefaultConstant' => [
                '<?php
                    const FOO = "bar";

                    /**
                     * @template T as string
                     */
                    class E {
                        /** @var T */
                        public $t;

                        /**
                         * @param T $t
                         */
                        function __construct(string $t = FOO) {
                            $this->t = $t;
                        }
                    }

                    $e = new E();',
                'assertions' => [
                    '$e===' => 'E<string(bar)>',
                ],
            ],
            'SKIPPED-templateDefaultClassConstant' => [
                '<?php
                    class D {
                        const FOO = "bar";
                    }

                    /**
                     * @template T as string
                     */
                    class E {
                        /** @var T */
                        public $t;

                        /**
                         * @param T $t
                         */
                        function __construct(string $t = D::FOO) {
                            $this->t = $t;
                        }
                    }

                    $e = new E();',
                'assertions' => [
                    '$e===' => 'E<string(bar)>',
                ],
            ],
            'allowNullablePropertyAssignment' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface I {
                        /**
                         * @return T1
                         */
                        public function get();
                    }

                    /**
                     * @template T2
                     */
                    class C {
                        /**
                         * @var T2|null
                         */
                        private $bar;

                        /**
                         * @param I<T2> $foo
                         */
                        public function __construct(I $foo) {
                            $this->bar = $foo->get();
                        }
                    }',
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
            'reflectionClass' => [
                '<?php
                    /**
                     * @template T as object
                     *
                     * @property-read class-string<T> $name
                     */
                    class CustomReflectionClass {
                        /**
                         * @var class-string<T>
                         */
                        public $name;

                        /**
                         * @param T|class-string<T> $argument
                         */
                        public function __construct($argument) {
                            if (is_object($argument)) {
                                $this->name = get_class($argument);
                            } else {
                                $this->name = $argument;
                            }
                        }
                    }

                    /**
                     * @template T as object
                     * @param class-string<T> $className
                     * @return CustomReflectionClass<T>
                     */
                    function getTypeOf(string $className) {
                        return new CustomReflectionClass($className);
                    }',
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
            'ignoreTooManyGenericObjectArgs' => [
                '<?php
                    /**
                     * @template T
                     */
                    class C {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /** @param C<int> $c */
                    function takesC(C $c) : void {}

                    /**
                     * @psalm-suppress TooManyTemplateParams
                     * @var C<int, int>
                     */
                    $c = new C(5);
                    takesC($c);',
            ],
            'classTemplateUnionType' => [
                '<?php
                    /**
                     * @template T0 as int|string
                     */
                    class C {
                        /**
                         * @param T0 $t
                         */
                        public function foo($t) : void {}
                    }

                    /** @param C<int> $c */
                    function foo(C $c) : void {}

                    /** @param C<string> $c */
                    function bar(C $c) : void {}',
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
            'unionAsTypeReturnType' => [
                '<?php
                    /**
                     * @template TKey of ?array-key
                     * @template T
                     */
                    interface Collection
                    {
                        /**
                         * @param Closure(T=):bool $p
                         * @return Collection<TKey, T>
                         */
                        public function filter(Closure $p);
                    }',
            ],
            'converterObject' => [
                '<?php
                    /**
                     * @template I as array-key
                     * @template V
                     */
                    class Converter
                    {
                        /**
                         * @var array<I, V> $records
                         */
                        public $records;

                        /**
                          * @param array<I, V> $records
                          */
                        public function __construct(array $records) {
                            $this->records = $records;
                        }

                        /**
                         * @template Q2 as object
                         *
                         * @param Q2 $obj2
                         *
                         * @return array<I, V|Q2>
                         */
                        private function appender(object $obj2): array
                        {
                            $arr = [];
                            foreach ($this->records as $key => $obj) {
                                if (rand(0, 1)) {
                                  $obj = $obj2;
                                }
                                $arr[$key] = $obj;
                            }

                            return $arr;
                        }

                        /**
                         * @template Q1 as object
                         *
                         * @param Q1 $obj
                         *
                         * @return array<I, V|Q1>
                         */
                        public function appendProperty(object $obj): array
                        {
                            return $this->appender($obj);
                        }
                    }',
            ],
            'converterClassString' => [
                '<?php
                    /**
                     * @template I as array-key
                     * @template V
                     */
                    class Converter
                    {
                       /**
                        * @var array<I, V> $records
                        */
                       public $records;

                       /**
                        * @param array<I, V> $records
                        */
                       public function __construct(array $records) {
                           $this->records = $records;
                       }

                       /**
                         * @template Q as object
                         *
                         * @param class-string<Q> $obj
                         *
                         * @return array<I, V>
                         */
                        public function appendProperty(string $obj): array
                        {
                            return $this->appender($obj);
                        }

                        /**
                         * @template Q as object
                         *
                         * @param class-string<Q> $obj2
                         *
                         * @return array<I, V|Q>
                         */
                        private function appender(string $obj2): array
                        {
                            $arr = [];
                            foreach ($this->records as $key => $obj) {
                                if (rand(0, 1)) {
                                  $obj = new $obj2;
                                }
                                $arr[$key] = $obj;
                            }

                            return $arr;
                        }
                    }',
            ],
            'allowTemplateReconciliation' => [
                '<?php
                    /**
                     * @template T
                     */
                    abstract class C {
                        /** @param T $t */
                        public function foo($t): void {
                            if (!$t) {}
                            if ($t) {}
                         }
                    }'
            ],
            'allowTemplateParamsToCoerceToMinimumTypes' => [
                '<?php
                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * @var array<TKey,T>
                         */
                        private $elements;

                        /**
                         * @param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }
                    }

                    /** @psalm-suppress MixedArgument */
                    $c = new ArrayCollection($_GET["a"]);',
                [
                    '$c' => 'ArrayCollection<array-key, mixed>',
                ],
            ],
            'doNotCombineTypes' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @template T
                     */
                    class C {
                        /**
                         * @var T
                         */
                        private $t;

                        /**
                         * @param T $t
                         */
                        public function __construct($t) {
                            $this->t = $t;
                        }

                        /**
                         * @return T
                         */
                        public function get() {
                            return $this->t;
                        }
                    }

                    /**
                     * @param C<A> $a
                     * @param C<B> $b
                     * @return C<A>|C<B>
                     */
                    function randomCollection(C $a, C $b) : C {
                        if (rand(0, 1)) {
                            return $a;
                        }

                        return $b;
                    }

                    $random_collection = randomCollection(new C(new A), new C(new B));

                    $a_or_b = $random_collection->get();',
                [
                    '$random_collection' => 'C<A>|C<B>',
                    '$a_or_b' => 'B|A',
                ],
            ],
            'inferClosureParamTypeFromContext' => [
                '<?php
                    /**
                     * @template E
                     */
                    interface Collection {
                        /**
                         * @template R
                         * @param callable(E):R $action
                         * @return Collection<R>
                         */
                        function map(callable $action): self;
                    }

                    /**
                     * @template T
                     */
                    interface Optional {
                        /**
                         * @return T
                         */
                        function get();
                    }

                    /**
                     * @param Collection<Optional<string>> $collection
                     * @return Collection<string>
                     */
                    function expandOptions(Collection $collection) : Collection {
                        return $collection->map(
                            function ($optional) {
                                return $optional->get();
                            }
                        );
                    }',
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
            'templateArrayIntersection' => [
                '<?php
                    /**
                     * @template T as object
                     * @template S as object
                     * @param array<T> $a
                     * @param class-string<S> $type
                     * @return array<T&S>
                     */
                    function filter(array $a, string $type): array {
                        $result = [];
                        foreach ($a as $item) {
                            if (is_a($item, $type)) {
                                $result[] = $item;
                            }
                        }
                        return $result;
                    }

                    interface A {}
                    interface B {}

                    /** @var array<A> */
                    $x = [];
                    $y = filter($x, B::class);',
                [
                    '$y' => 'array<array-key, A&B>',
                ]
            ],
            'templateEmptyParamCoercion' => [
                '<?php
                    namespace NS;
                    use Countable;

                    /** @template T */
                    class Collection
                    {
                        /** @psalm-var iterable<T> */
                        private $data;

                        /** @psalm-param iterable<T> $data */
                        public function __construct(iterable $data = []) {
                            $this->data = $data;
                        }
                    }

                    class Item {}
                    /** @psalm-param Collection<Item> $c */
                    function takesCollectionOfItems(Collection $c): void {}

                    takesCollectionOfItems(new Collection());
                    takesCollectionOfItems(new Collection([]));',
            ],
            'templatedGet' => [
                '<?php
                    /**
                     * @template P as string
                     * @template V as mixed
                     */
                    class PropertyBag {
                        /** @var array<P,V> */
                        protected $data = [];

                        /** @param array<P,V> $data */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /** @param P $name */
                        public function __isset(string $name): bool {
                            return isset($this->data[$name]);
                        }

                        /**
                         * @param P $name
                         * @return V
                         */
                        public function __get(string $name) {
                            return $this->data[$name];
                        }
                    }

                    $p = new PropertyBag(["a" => "data for a", "b" => "data for b"]);

                    $a = $p->a;',
                [
                    '$a' => 'string'
                ]
            ],
            'templateAsArray' => [
                '<?php
                    /**
                     * @template DATA as array<string, scalar|array|object|null>
                     */
                    abstract class Foo {
                        /**
                         * @var DATA
                         */
                        protected $data;

                        /**
                         * @param DATA $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @return scalar|array|object|null
                         */
                        public function __get(string $property) {
                            return $this->data[$property] ?? null;
                        }

                        /**
                         * @param scalar|array|object|null $value
                         */
                        public function __set(string $property, $value) {
                            $this->data[$property] = $value;
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
                ]
            ],
            'keyOfClassTemplateAcceptingIndexedAccess' => [
                '<?php
                    /**
                     * @template TData as array
                     */
                    abstract class DataBag {
                        /**
                         * @var TData
                         */
                        protected $data;

                        /**
                         * @param TData $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         * @param TData[K] $value
                         */
                        public function __set(string $property, $value) {
                            $this->data[$property] = $value;
                        }
                    }'
            ],
            'keyOfClassTemplateReturningIndexedAccess' => [
                '<?php
                    /**
                     * @template TData as array
                     */
                    abstract class DataBag {
                        /**
                         * @var TData
                         */
                        protected $data;

                        /**
                         * @param TData $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         *
                         * @return TData[K]
                         */
                        public function __get(string $property) {
                            return $this->data[$property];
                        }
                    }'
            ],
            'unionTOrClassStringTPassedClassString' => [
                '<?php
                    /**
                     * @psalm-template T of object
                     * @psalm-param T|class-string<T> $someType
                     * @psalm-return T
                     */
                    function getObject($someType) {
                        if (is_object($someType)) {
                            return $someType;
                        }

                        return new $someType();
                    }

                    class C {
                        function sayHello() : string {
                            return "hi";
                        }
                    }

                    getObject(C::class)->sayHello();'
            ],
            'unionTOrClassStringTPassedObject' => [
                '<?php
                    /**
                     * @psalm-template T of object
                     * @psalm-param T|class-string<T> $someType
                     * @psalm-return T
                     */
                    function getObject($someType) {
                        if (is_object($someType)) {
                            return $someType;
                        }

                        return new $someType();
                    }

                    class C {
                        function sayHello() : string {
                            return "hi";
                        }
                    }

                    getObject(new C())->sayHello();'
            ],
            'SKIPPED-templatedInterfaceIntersectionFirst' => [
                '<?php
                    /** @psalm-template T */
                    interface IParent {
                        /** @psalm-return T */
                        function foo();
                    }

                    interface IChild extends IParent {}

                    class C {}

                    /** @psalm-return IParent<C>&IChild */
                    function makeConcrete() : IChild {
                        return new class() implements IChild {
                            public function foo() {
                                return new C();
                            }
                        };
                    }

                    $a = makeConcrete()->foo();',
                [
                    '$a' => 'C',
                ]
            ],
            'templatedInterfaceIntersectionSecond' => [
                '<?php
                    /** @psalm-template T */
                    interface IParent {
                        /** @psalm-return T */
                        function foo();
                    }

                    interface IChild extends IParent {}

                    class C {}

                    /** @psalm-return IChild&IParent<C> */
                    function makeConcrete() : IChild {
                        return new class() implements IChild {
                            public function foo() {
                                return new C();
                            }
                        };
                    }

                    $a = makeConcrete()->foo();',
                [
                    '$a' => 'C',
                ]
            ],
            'returnTemplateIntersectionGenericObjectAndTemplate' => [
                '<?php
                    /** @psalm-template Tp */
                    interface I {
                        /** @psalm-return Tp */
                        function getMe();
                    }

                    class C {}

                    /**
                     * @psalm-template T as object
                     *
                     * @psalm-param class-string<T> $className
                     *
                     * @psalm-return T&I<T>
                     */
                    function makeConcrete(string $className) : object
                    {
                        return new class() extends C implements I {
                            public function getMe() {
                                return $this;
                            }
                        };
                    }

                    $a = makeConcrete(C::class);',
                [
                    '$a' => 'C&I<C>'
                ]
            ],
            'dontModifyByRefTemplatedArray' => [
                '<?php
                    class A {}
                    class B {}

                    /**
                     * @template T of object
                     * @param class-string<T> $className
                     * @param array<T> $map
                     * @param-out array<T> $map
                     * @param int $id
                     * @return T
                     */
                    function get(string $className, array &$map, int $id) {
                        if(!array_key_exists($id, $map)) {
                            $map[$id] = new $className();
                        }
                        return $map[$id];
                    }

                    /**
                     * @param array<A> $mapA
                     */
                    function getA(int $id, array $mapA): A {
                        return get(A::class, $mapA, $id);
                    }

                    /**
                     * @param array<B> $mapB
                     */
                    function getB(int $id, array $mapB): B {
                        return get(B::class, $mapB, $id);
                    }'
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
                ]
            ],
            'unionClassStringTWithTReturnsObjectWhenCoerced' => [
                '<?php
                    /**
                     * @template T as object
                     * @param T|class-string<T> $s
                     * @return T
                     */
                    function bar($s) {
                        if (is_object($s)) {
                            return $s;
                        }

                        return new $s();
                    }

                    function foo(string $s) : object {
                        /** @psalm-suppress ArgumentTypeCoercion */
                        return bar($s);
                    }'
            ],
            'keyOfArrayGet' => [
                '<?php
                    /**
                     * @template DATA as array<string, int|bool>
                     */
                    abstract class Foo {
                        /**
                         * @var DATA
                         */
                        protected $data;

                        /**
                         * @param DATA $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<DATA>
                         *
                         * @param K $property
                         *
                         * @return DATA[K]
                         */
                        public function __get(string $property) {
                            return $this->data[$property];
                        }
                    }',
            ],
            'keyOfArrayRandomKey' => [
                '<?php
                    /**
                     * @template DATA as array<string, int|bool>
                     */
                    abstract class Foo {
                        /**
                         * @var DATA
                         */
                        protected $data;

                        /**
                         * @param DATA $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @return key-of<DATA>
                         */
                        abstract public function getRandomKey() : string;
                    }'
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
            'restrictTemplateInputWithClassString' => [
                '<?php
                    /** @template T as object */
                    class Foo
                    {
                        /**
                         * @psalm-var class-string
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param T::class $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class A {}
                    class B {}

                    $foo = new Foo(A::class);
                    $foo->add(new B);',
                'error_message' => 'InvalidArgument',
            ],
            'restrictTemplateInputWithTClassBadInput' => [
                '<?php
                    /** @template T */
                    class Foo
                    {
                        /**
                         * @psalm-var class-string
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param T::class $type
                         */
                        public function __construct(string $type)
                        {
                            if (!in_array($type, [A::class, B::class], true)) {
                                throw new \InvalidArgumentException;
                            }
                            $this->type = $type;
                            $this->items = [];
                        }

                        /** @param T $item */
                        public function add($item): void
                        {
                            $this->items[] = $item;
                        }
                    }

                    class A {}
                    class B {}

                    $foo = new Foo(A::class);
                    $foo->add(new B);',
                'error_message' => 'InvalidArgument',
            ],
            'templatedClosureProperty' => [
                '<?php
                    final class State
                    {}

                    interface Foo
                    {}

                    function type(string ...$_p): void {}

                    /**
                     * @template T
                     */
                    final class AlmostFooMap
                    {
                        /**
                         * @param callable(State):(T&Foo) $closure
                         */
                        public function __construct(callable $closure)
                        {
                            type($closure);
                        }
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:20:34 - Argument 1 of type expects string, callable(State):(T as mixed)&Foo provided',
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
            'forbidLossOfInformationWhenCoercing' => [
                '<?php
                    /**
                     * @template T as iterable<int>
                     * @param T::class $class
                     */
                    function foo(string $class) : void {}

                    function bar(Traversable $t) : void {
                        foo(get_class($t));
                    }',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'bindFirstTemplatedClosureParameter' => [
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
            'templateWithNoReturn' => [
                '<?php
                    /**
                     * @template T
                     */
                    class A {
                        /** @return T */
                        public function foo() {}
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'templateInvalidDocblockArgument' => [
                '<?php
                    /** @template T as object */
                    class Generic {}

                    /**
                     * @template T
                     * @param T $p
                     * @return Generic<T>
                     * @psalm-suppress InvalidReturnType
                     */
                    function violate($p) {}',
                'error_message' => 'InvalidTemplateParam',
            ],
            'doublyLinkedListBadParam' => [
                '<?php
                    /** @var SplDoublyLinkedList<int, string> */
                    $templated_list = new SplDoublyLinkedList();
                    $templated_list->add(5, []);',
                'error_message' => 'InvalidArgument',
            ],
            'copyScopedClassInFunction' => [
                '<?php
                    /**
                     * @template Throwable as DOMNode
                     *
                     * @param class-string<Throwable> $foo
                     */
                    function Foo(string $foo) : string {
                        return $foo;
                    }',
                'error_message' => 'ReservedWord',
            ],
            'copyScopedClassInNamespacedFunction' => [
                '<?php
                    namespace Foo;

                    class Bar {}

                    /**
                     * @template Bar as DOMNode
                     *
                     * @param class-string<Bar> $foo
                     */
                    function Foo(string $foo) : string {
                        return $foo;
                    }',
                'error_message' => 'ReservedWord',
            ],
            'copyScopedClassInNamespacedClass' => [
                '<?php
                    namespace Foo;

                    /**
                     * @template Bar as DOMNode
                     */
                    class Bar {}',
                'error_message' => 'ReservedWord',
            ],
            'duplicateTemplateFunction' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Foo
                    {
                        /** @var T */
                        private $value;

                        /**
                         * @template T
                         * @param T $value
                         * @return self<T>
                         */
                        static function of($value): self
                        {
                            return new self($value);
                        }

                        /**
                         * @param T $value
                         */
                        private function __construct($value)
                        {
                            $this->value = $value;
                        }
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'preventDogCatSnafu' => [
                '<?php
                    class Animal {}
                    class Dog extends Animal {}
                    class Cat extends Animal {}

                    /**
                     * @template T
                     */
                    class Collection {
                        /**
                         * @param T $t
                         */
                        public function add($t) : void {}
                    }

                    /**
                     * @param Collection<Animal> $list
                     */
                    function addAnimal(Collection $list) : void {
                        $list->add(new Cat());
                    }

                    /**
                     * @param Collection<Dog> $list
                     */
                    function takesDogList(Collection $list) : void {
                        addAnimal($list); // this should be an error
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventCovariantParamUsage' => [
                '<?php
                    /**
                     * @template-covariant T
                     */
                    class Covariant {
                        /**
                         * @param T $value
                         */
                        public function set($value): void {}
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'templateEmptyParamCoercionChangeVariable' => [
                '<?php
                    namespace NS;
                    use Countable;

                    /** @template T */
                    class Collection
                    {
                        /** @psalm-var iterable<T> */
                        private $data;

                        /** @psalm-param iterable<T> $data */
                        public function __construct(iterable $data = []) {
                            $this->data = $data;
                        }
                    }

                    /** @psalm-param Collection<string> $c */
                    function takesStringCollection(Collection $c): void {}

                    /** @psalm-param Collection<int> $c */
                    function takesIntCollection(Collection $c): void {}

                    $collection = new Collection();

                    takesStringCollection($collection);
                    takesIntCollection($collection);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'argumentExpectsFleshOutTIndexedAccess' => [
                '<?php
                    /**
                     * @template TData as array
                     */
                    abstract class Row {
                        /**
                         * @var TData
                         */
                        protected $data;

                        /**
                         * @param TData $data
                         */
                        public function __construct(array $data) {
                            $this->data = $data;
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         *
                         * @return TData[K]
                         */
                        public function __get(string $property) {
                            // validation logic would go here
                            return $this->data[$property];
                        }

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         * @param TData[K] $value
                         */
                        public function __set(string $property, $value) {
                            // data updating would go here
                            $this->data[$property] = $value;
                        }
                    }

                    /** @extends Row<array{id: int, name: string, height: float}> */
                    class CharacterRow extends Row {}

                    $mario = new CharacterRow(["id" => 5, "name" => "Mario", "height" => 3.5]);

                    $mario->ame = "Luigi";',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:47:29 - Argument 1 of CharacterRow::__set expects string(id)|string(name)|string(height), string(ame) provided',
            ],
            'constrainTemplateTypeWhenClassStringUsed' => [
                '<?php
                    class GenericObjectFactory {
                       /**
                        * @psalm-template T
                        * @psalm-param class-string<T> $type
                        * @psalm-return T
                        */
                        public function getObject(string $type)
                        {
                            return 3;
                        }
                    }',
                'error_message' => 'InvalidReturnStatement'
            ],
        ];
    }
}
