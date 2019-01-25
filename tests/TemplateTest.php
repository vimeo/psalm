<?php
namespace Psalm\Tests;

class TemplateTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
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
                    'RedundantConditionGivenDocblockType',
                    'TypeCoercion'
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
            'phanTuple' => [
                '<?php
                    namespace Phan\Library;

                    /**
                     * An abstract tuple.
                     */
                    abstract class Tuple
                    {
                        const ARITY = 0;

                        /**
                         * @return int
                         * The arity of this tuple
                         */
                        public function arity(): int
                        {
                            return (int)static::ARITY;
                        }

                        /**
                         * @return array
                         * An array of all elements in this tuple.
                         */
                        abstract public function toArray(): array;
                    }

                    /**
                     * A tuple of 1 element.
                     *
                     * @template T0
                     * The type of element zero
                     */
                    class Tuple1 extends Tuple
                    {
                        /** @var int */
                        const ARITY = 1;

                        /** @var T0 */
                        public $_0;

                        /**
                         * @param T0 $_0
                         * The 0th element
                         */
                        public function __construct($_0) {
                            $this->_0 = $_0;
                        }

                        /**
                         * @return int
                         * The arity of this tuple
                         */
                        public function arity(): int
                        {
                            return (int)static::ARITY;
                        }

                        /**
                         * @return array
                         * An array of all elements in this tuple.
                         */
                        public function toArray(): array
                        {
                            return [
                                $this->_0,
                            ];
                        }
                    }

                    /**
                     * A tuple of 2 elements.
                     *
                     * @template T0
                     * The type of element zero
                     *
                     * @template T1
                     * The type of element one
                     *
                     * @inherits Tuple1<T0>
                     */
                    class Tuple2 extends Tuple1
                    {
                        /** @var int */
                        const ARITY = 2;

                        /** @var T1 */
                        public $_1;

                        /**
                         * @param T0 $_0
                         * The 0th element
                         *
                         * @param T1 $_1
                         * The 1st element
                         */
                        public function __construct($_0, $_1) {
                            parent::__construct($_0);
                            $this->_1 = $_1;
                        }

                        /**
                         * @return array
                         * An array of all elements in this tuple.
                         */
                        public function toArray(): array
                        {
                            return [
                                $this->_0,
                                $this->_1,
                            ];
                        }
                    }

                    $a = new Tuple2("cool", 5);

                    /** @return void */
                    function takes_int(int $i) {}

                    /** @return void */
                    function takes_string(string $s) {}

                    takes_string($a->_0);
                    takes_int($a->_1);',
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
            'validPsalmTemplatedType' => [
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
                     * @template TValue
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
                     * @param ArrayCollection<mixed,Item> $i
                     */
                    function takesCollectionOfItems(ArrayCollection $i): void {}

                    $c = new ArrayCollection([ new Item ]);
                    takesCollectionOfItems($c);
                    takesCollectionOfItems($c->map(function(Item $i): Item { return $i;}));
                    takesCollectionOfItems($c->map(function(Item $i): Item { return $i;}));'
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
                        private function ensureFoo(array $items): EntitySeries
                        {
                            $type = $items[0] instanceof A ? A::class : B::class;
                            return new Foo($type);
                        }
                    }

                    class A {}
                    class B {}'
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

                    $a = splat_proof(... $foo);',
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

                    foreach ($c as $k => $v) { atan($v); strlen($k); }'
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

                    foreach ($c->getIterator() as $k => $v) { atan($v); strlen($k); }'
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

                    takesIteratorOfInts(new SomeIterator());'
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
                         * @param T&Foo $closure
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
                    }'
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

                    bar(foo(A::class));'
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
                        public function loader(string $class) : void {
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
                    '$b' => 'mixed'
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
                    '$b' => 'mixed'
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'templateExtendsSameName' => [
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
                     * @template-extends ValueContainer<TValue>
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
                    '$b' => 'int'
                ],
            ],
            'templateExtendsDifferentName' => [
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
                     * @template-extends ValueContainer<Tv>
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
                    '$b' => 'int'
                ],
            ],
            'extendsWithNonTemplate' => [
                '<?php
                    /**
                     * @template T
                     */
                    abstract class Container
                    {
                        /**
                         * @return T
                         */
                        public abstract function getItem();
                    }

                    class Foo
                    {
                    }

                    /**
                     * @template-extends Container<Foo>
                     */
                    class FooContainer extends Container
                    {
                        /**
                         * @return Foo
                         */
                        public function getItem()
                        {
                            return new Foo();
                        }
                    }

                    /**
                     * @template TItem
                     * @param Container<TItem> $c
                     * @return TItem
                     */
                    function getItemFromContainer(Container $c) {
                        return $c->getItem();
                    }

                    $fc = new FooContainer();

                    $f1 = $fc->getItem();
                    $f2 = getItemFromContainer($fc);',
                [
                    '$fc' => 'FooContainer',
                    '$f1' => 'Foo',
                    '$f2' => 'Foo',
                ]
            ],
            'supportBareExtends' => [
                '<?php
                    /**
                     * @template T
                     */
                    abstract class Container
                    {
                        /**
                         * @return T
                         */
                        public abstract function getItem();
                    }

                    class Foo
                    {
                    }

                    /**
                     * @extends Container<Foo>
                     */
                    class FooContainer extends Container
                    {
                        /**
                         * @return Foo
                         */
                        public function getItem()
                        {
                            return new Foo();
                        }
                    }

                    /**
                     * @template TItem
                     * @param Container<TItem> $c
                     * @return TItem
                     */
                    function getItemFromContainer(Container $c) {
                        return $c->getItem();
                    }

                    $fc = new FooContainer();

                    $f1 = $fc->getItem();
                    $f2 = getItemFromContainer($fc);',
                [
                    '$fc' => 'FooContainer',
                    '$f1' => 'Foo',
                    '$f2' => 'Foo',
                ]
            ],
            'allowExtendingParameterisedTypeParam' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    abstract class Container
                    {
                        /**
                         * @param T $obj
                         */
                        abstract public function uri($obj) : string;
                    }

                    class Foo {}

                    /**
                     * @template-extends Container<Foo>
                     */
                    class FooContainer extends Container {
                        /** @param Foo $obj */
                        public function uri($obj) : string {
                            return "hello";
                        }
                    }'
            ],
            'extendsWithNonTemplateWithoutImplementing' => [
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

                    /**
                     * @template-extends User<int>
                     */
                    class AppUser extends User {}

                    $au = new AppUser(-1);
                    $id = $au->getId();',
                [
                    '$au' => 'AppUser',
                    '$id' => 'int',
                ]
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
                ]
            ],
            'extendsTwiceSameName' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Container
                    {
                        /**
                         * @var T
                         */
                        private $v;
                        /**
                         * @param T $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return T
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template T
                     * @template-extends Container<T>
                     */
                    class ChildContainer extends Container {}

                    /**
                     * @template T
                     * @template-extends ChildContainer<T>
                     */
                    class GrandChildContainer extends ChildContainer {}

                    $fc = new GrandChildContainer(5);
                    $a = $fc->getValue();',
                [
                    '$a' => 'int',
                ]
            ],
            'extendsTwiceDifferentNameUnbrokenChain' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class Container
                    {
                        /**
                         * @var T1
                         */
                        private $v;
                        /**
                         * @param T1 $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return T1
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template T2
                     * @template-extends Container<T2>
                     */
                    class ChildContainer extends Container {}

                    /**
                     * @template T3
                     * @template-extends ChildContainer<T3>
                     */
                    class GrandChildContainer extends ChildContainer {}

                    $fc = new GrandChildContainer(5);
                    $a = $fc->getValue();',
                [
                    '$a' => 'int',
                ]
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

                    takesReturnTCallable($a);'
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
                    );'
            ],
            'templateExtendsOnceAndBound' => [
                '<?php
                    /** @template T1 */
                    class Repo {
                        /** @return ?T1 */
                        public function findOne() {}
                    }

                    class SpecificEntity {}

                    /** @template-extends Repo<SpecificEntity> */
                    class AnotherRepo extends Repo {}

                    $a = new AnotherRepo();
                    $b = $a->findOne();',
                [
                    '$a' => 'AnotherRepo',
                    '$b' => 'null|SpecificEntity',
                ]
            ],
            'templateExtendsTwiceAndBound' => [
                '<?php
                    /** @template T1 */
                    class Repo {
                        /** @return ?T1 */
                        public function findOne() {}
                    }

                    /**
                     * @template T2
                     * @template-extends Repo<T2>
                     */
                    class CommonAppRepo extends Repo {}

                    class SpecificEntity {}

                    /** @template-extends CommonAppRepo<SpecificEntity> */
                    class SpecificRepo extends CommonAppRepo {}

                    $a = new SpecificRepo();
                    $b = $a->findOne();',
                [
                    '$a' => 'SpecificRepo',
                    '$b' => 'null|SpecificEntity',
                ]
            ],
            'multipleArgConstraints' => [
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
                        function(A $_a) : void {},
                        new A()
                    );

                    foo(
                        function(A $_a) : void {},
                        function(A $_a) : void {},
                        new AChild()
                    );'
            ],
            'templatedInterfaceExtendedMethodInheritReturnType' => [
                '<?php
                    class Foo {}

                    /**
                     * @template-implements IteratorAggregate<int, Foo>
                     */
                    class SomeIterator implements IteratorAggregate
                    {
                        public function getIterator() {
                            yield new Foo;
                        }
                    }

                    $i = (new SomeIterator())->getIterator();',
                [
                    '$i' => 'Traversable<int, Foo>',
                ]
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
                ]
            ],
            'templateCountOnExtendedAndImplemented' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    interface Selectable {}

                    /**
                     * @template T
                     * @template-implements Selectable<int,T>
                     */
                    class Repository implements Selectable {}

                    interface SomeEntity {}

                    /**
                     * @template-extends Repository<SomeEntity>
                     */
                    class SomeRepository extends Repository {}'
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
                ]
            ],
        ];
    }

    /**
     * @return array
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
                     * @template TKey
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
                'error_message' => 'MixedTypeCoercion',
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
                'error_message' => 'InvalidArgument - src/somefile.php:20 - Argument 1 of type expects string, callable(State):(T as mixed)&Foo provided',
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
                     * @return T
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
                'error_message' => 'MixedTypeCoercion',
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
                'error_message' => 'TypeCoercion',
            ],
            'extendsWithUnfulfilledNonTemplate' => [
                '<?php
                    namespace A;

                    /**
                     * @template T
                     */
                    abstract class Container
                    {
                        /**
                         * @return T
                         */
                        public abstract function getItem();
                    }

                    class Foo
                    {
                    }

                    class Bar
                    {
                    }

                    /**
                     * @template-extends Container<Bar>
                     */
                    class BarContainer extends Container
                    {
                        /**
                         * @return Foo
                         */
                        public function getItem()
                        {
                            return new Foo();
                        }
                    }',
                'error_message' => 'ImplementedReturnTypeMismatch - src/somefile.php:29 - The return type \'A\Bar\' for',
            ],
            'extendTemplateAndDoesNotOverrideWithWrongArg' => [
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

                    /**
                     * @template-extends User<int>
                     */
                    class AppUser extends User {}

                    $au = new AppUser("string");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'extendsTwiceDifferentNameBrokenChain' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class Container
                    {
                        /**
                         * @var T1
                         */
                        private $v;
                        /**
                         * @param T1 $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return T1
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template T2
                     */
                    class ChildContainer extends Container {}

                    /**
                     * @template T3
                     * @template-extends ChildContainer<T3>
                     */
                    class GrandChildContainer extends ChildContainer {}

                    $fc = new GrandChildContainer(5);
                    $a = $fc->getValue();',
                'error_message' => 'MixedAssignment',
            ],
            'extendsTwiceSameNameBrokenChain' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Container
                    {
                        /**
                         * @var T
                         */
                        private $v;
                        /**
                         * @param T $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return T
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template T
                     */
                    class ChildContainer extends Container {}

                    /**
                     * @template T
                     * @template-extends ChildContainer<T>
                     */
                    class GrandChildContainer extends ChildContainer {}

                    $fc = new GrandChildContainer(5);
                    $a = $fc->getValue();',
                'error_message' => 'MixedAssignment',
            ],
            'extendsTwiceSameNameLastDoesNotExtend' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Container
                    {
                        /**
                         * @var T
                         */
                        private $v;
                        /**
                         * @param T $v
                         */
                        public function __construct($v)
                        {
                            $this->v = $v;
                        }
                        /**
                         * @return T
                         */
                        public function getValue()
                        {
                            return $this->v;
                        }
                    }

                    /**
                     * @template T
                     * @template-extends Container<T>
                     */
                    class ChildContainer extends Container {}

                    /**
                     * @template T
                     */
                    class GrandChildContainer extends ChildContainer {}

                    $fc = new GrandChildContainer(5);
                    $a = $fc->getValue();',
                'error_message' => 'MixedAssignment',
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
                'error_message' => 'TypeCoercion',
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
                'error_message' => 'TypeCoercion',
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
                'error_message' => 'TypeCoercion',
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
            'mismatchingTypesAfterExtends' => [
                '<?php
                    class Foo {}
                    class Bar {}

                    /**
                     * @extends IteratorAggregate<int, Foo>
                     */
                    class SomeIterator implements IteratorAggregate
                    {
                        /**
                         * @return Traversable<int, Bar>
                         */
                        public function getIterator()
                        {
                            yield new Bar;
                        }
                    }',
                'error_message' => 'ImplementedReturnTypeMismatch',
            ],
            'mismatchingTypesAfterExtendsInherit' => [
                '<?php
                    class Foo {}
                    class Bar {}

                    /**
                     * @extends IteratorAggregate<int, Foo>
                     */
                    class SomeIterator implements IteratorAggregate
                    {
                        public function getIterator()
                        {
                            yield new Bar;
                        }
                    }',
                'error_message' => 'InvalidReturnType',
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
            'badTemplateExtends' => [
                '<?php
                    /**
                     * @template T
                     */
                    class A {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     * @template-extends A<Z>
                     */
                    class B extends A {}',
                'error_message' => 'UndefinedClass'
            ],
            'templateExtendsWithoutAllParams' => [
                '<?php
                    /**
                     * @template T
                     * @template V
                     * @template U
                     */
                    class A {}

                    /**
                     * @extends A<int>
                     */
                    class CC extends A {}',
                'error_message' => 'MissingTemplateParam'
            ],
        ];
    }
}
