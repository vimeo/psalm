<?php
namespace Psalm\Tests\Template;

use const DIRECTORY_SEPARATOR;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class ClassTemplateTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'cachingIterator' => [
                '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new CachingIterator($arrayIterator);
                    $next = $decoratorIterator->hasNext();
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int|null',
                    '$value' => 'null|string',
                    '$next' => 'bool',
                ],
            ],
            'infiniteIterator' => [
                '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new InfiniteIterator($arrayIterator);
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int|null',
                    '$value' => 'null|string',
                ],
            ],
            'limitIterator' => [
                '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new LimitIterator($arrayIterator, 1, 1);
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int|null',
                    '$value' => 'null|string',
                ],
            ],
            'callbackFilterIterator' => [
                '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new CallbackFilterIterator(
                        $arrayIterator,
                        static function (string $value): bool {return "a" === $value;}
                    );
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int|null',
                    '$value' => 'null|string',
                ],
            ],
            'noRewindIterator' => [
                '<?php

                    $input = range("a", "z");

                    $arrayIterator = new ArrayIterator($input);
                    $decoratorIterator = new NoRewindIterator($arrayIterator);
                    $key = $decoratorIterator->key();
                    $value = $decoratorIterator->current();
                ',
                'assertions' => [
                    '$key' => 'int|null',
                    '$value' => 'null|string',
                ],
            ],
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
                         * @param class-string<T> $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         * @psalm-suppress MixedMethodCall
                         */
                        public function bar() {
                            $t = $this->T;
                            return new $t();
                        }
                    }

                    $at = "A";

                    /**
                     * @var Foo<A>
                     * @psalm-suppress ArgumentTypeCoercion
                     */
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
                ],
            ],
            'classTemplateSelfs' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    class Foo {
                        /** @var class-string<T> */
                        public $T;

                        /**
                         * @param class-string<T> $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         * @psalm-suppress MixedMethodCall
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
                         * @param class-string<T> $T
                         */
                        public function __construct(string $T) {
                            $this->T = $T;
                        }

                        /**
                         * @return T
                         * @psalm-suppress MixedMethodCall
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
            'classTemplateContainerSimpleCall' => [
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
                    }

                    $afoo = new Foo(new A());
                    $afoo_bar = $afoo->bar();',
                'assertions' => [
                    '$afoo' => 'Foo<A>',
                    '$afoo_bar' => 'A',
                ],
            ],
            'classTemplateContainerThisCall' => [
                '<?php
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
                    }',
                [],
                'error_levels' => ['MixedOperand'],
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
            'repeatedCall' => [
                '<?php
                    namespace NS;

                    use Closure;

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
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
            'noRepeatedTypeException' => [
                '<?php
                    /** @template T as object */
                    class Foo
                    {
                        /**
                         * @psalm-var class-string<T>
                         */
                        private $type;

                        /** @var array<T> */
                        private $items;

                        /**
                         * @param class-string<T> $type
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
                         * @template T as object
                         * @param  T $item
                         */
                        public function add(object $item): void
                        {
                            $foo = $this->ensureFoo([$item]);
                            $foo->add($item);
                        }

                        /**
                         * @template T as object
                         * @param  array<mixed,T> $items
                         * @return Foo<T>
                         */
                        private function ensureFoo(array $items): Foo
                        {
                            /** @var class-string<T> */
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
                        public function getIterator(): \Traversable {
                            return new \ArrayIterator($this->data);
                        }
                    }

                    /** @var ICollection<string, int> */
                    $c = new Collection(["a" => 1]);

                    foreach ($c as $k => $v) { atan($v); strlen($k); }',
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

                    /** @template T as object */
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
            'getMagicPropertyOnClass' => [
                '<?php
                   class A {}

                   /**
                    * @template T as A
                    * @property ?T $x
                    */
                   class B {
                       /** @var ?T */
                       public $y;

                       public function __get() {}
                   }

                   $b = new B();
                   $b_x = $b->x;
                   $b_y = $b->y;
                ',
                'assertions' => [
                    '$b_x' => 'A|null',
                    '$b_y' => 'A|null',
                ],
            ],
            'getMagicPropertyOnThis' => [
                '<?php
                   abstract class A {}

                   class X extends A {}

                   /**
                    * @template T as A
                    * @property ?T $x
                    */
                   class B {
                       /** @var ?T */
                       public $y;

                       public function __get() {}

                       public function test(): void {
                           if ($this->x instanceof X) {}
                           if ($this->y instanceof X) {}
                       }
                   }
                ',
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
            'templateTKeyedArrayValues' => [
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
                    '$c===' => 'C<"hello">',
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
            'SKIPPED-templateDefaultClassMemberConstant' => [
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
            'templateDefaultClassConstant' => [
                '<?php
                    class D {}

                    /**
                     * @template T as object
                     */
                    class E {
                        /** @var class-string<T> */
                        public $t;

                        /**
                         * @param class-string<T> $t
                         */
                        function __construct(string $t = D::class) {
                            $this->t = $t;
                        }
                    }

                    $e = new E();',
                'assertions' => [
                    '$e===' => 'E<D>',
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
            'psalmReflectionClass' => [
                '<?php
                    /**
                     * @template T as object
                     *
                     * @psalm-property-read class-string<T> $name
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
                         * @return array<I, V|Q>
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
                         *
                         * @psalm-suppress MixedMethodCall
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
                    }',
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
                    '$a_or_b' => 'A|B',
                ],
            ],
            'doNotCombineTypesWhenMemoized' => [
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
                         * @psalm-mutation-free
                         */
                        public function get() {
                            return $this->t;
                        }
                    }

                    /** @var C<A>|C<B> $random_collection **/
                    $a_or_b = $random_collection->get();',
                [
                    '$random_collection' => 'C<A>|C<B>',
                    '$a_or_b' => 'A|B',
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
                    '$a' => 'string',
                ],
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
                            return isset($this->data[$property]) ? $this->data[$property] : null;
                        }
                    }',
            ],
            'SKIPPED-keyOfClassTemplateAcceptingIndexedAccess' => [
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
                    }',
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
                    }',
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
                ],
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
                ],
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
                        /** @var T&I<T> */
                        return new class() extends C implements I {
                            public function getMe() {
                                return $this;
                            }
                        };
                    }

                    $a = makeConcrete(C::class);',
                [
                    '$a' => 'C&I<C>',
                ],
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
                    }',
            ],
            'allowBoolTemplateCoercion' => [
                '<?php
                    /** @template T */
                    class TestPromise {
                        /** @psalm-param T $value */
                        public function __construct($value) {}
                    }

                    /** @return TestPromise<bool> */
                    function test(): TestPromise {
                        return new TestPromise(true);
                    }',
            ],
            'classTemplatedPropertyEmptyAssignment' => [
                '<?php
                    /** @template T */
                    class Foo {
                        /** @param \Closure():T $closure */
                        public function __construct($closure) {}
                    }

                    class Bar {
                        /** @var Foo<array> */
                        private $FooArray;

                        public function __construct() {
                            $this->FooArray = new Foo(function(): array { return []; });
                        }
                    }',
            ],
            'classTemplatedPropertyAssignmentWithMoreSpecificArray' => [
                '<?php
                    /** @template T */
                    class Foo {
                        /** @param \Closure():T $closure */
                        public function __construct($closure) {}
                    }
                    class Bar {
                        /** @var Foo<array> */
                        private $FooArray;
                        public function __construct() {
                            $this->FooArray = new Foo(function(): array { return []; });
                        }
                    }',
            ],
            'insideClosureVarTemplate' => [
                '<?php
                    /**
                     * @template T of object
                     */
                    class Foo {
                        /**
                         * @psalm-return callable(T): ?T
                         */
                        public function bar() {
                            return
                                /**
                                 * @param T $data
                                 * @return ?T
                                 */
                                function($data) {
                                    $data = rand(0, 1) ? $data : null;
                                    return $data;
                                };
                        }
                    }',
            ],
            'reflectTemplatedClass' => [
                '<?php
                    /** @template T1 of object */
                    class Foo {
                        /**
                         * @param class-string<T1> $a
                         * @psalm-return ReflectionClass<T1>
                         */
                        public function reflection(string $a) {
                            return new ReflectionClass($a);
                        }
                    }',
            ],
            'anonymousClassMustNotBreakParentTemplate' => [
                '<?php
                    /** @template T */
                    class Foo {
                        /** @psalm-var ?T */
                        private $value;

                        /** @psalm-param T $val */
                        public function set($val) : void {
                            $this->value = $val;
                            new class extends Foo {};
                        }

                        /** @psalm-return ?T */
                        public function get() {
                            return $this->value;
                        }
                    }'
            ],
            'templatedInvoke' => [
                '<?php
                    /**
                     * @template T
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class Foo {
                        /** @var T */
                        private $value;

                        /** @param T $val */
                        public function __construct($val) {
                            $this->value = $val;
                        }

                        /** @return T */
                        public function get() {
                            return $this->value;
                        }

                        /**
                         * @param T $val
                         * @return Foo<T>
                         */
                        public function __invoke($val) {
                            return new static($val);
                        }

                        /**
                         * @param T $val
                         * @return Foo<T>
                         */
                        public function create($val) {
                            return new static($val);
                        }
                    }

                    function bar(string $s) : string {
                        $foo = new Foo($s);
                        $bar = $foo($s);
                        return $bar->get();
                    }'
            ],
            'templatedLiteralStringReplacement' => [
                '<?php
                    /**
                     * @template T
                     */
                    final class Value {
                        /**
                         * @psalm-var T
                         */
                        private $value;

                        /**
                         * @psalm-param T $value
                         */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function value() {
                            return $this->value;
                        }
                    }

                    /**
                     * @template T
                     * @psalm-param T $value
                     * @psalm-return Value<T>
                     */
                    function value($value): Value {
                        return new Value($value);
                    }

                    /**
                     * @psalm-param Value<string> $value
                     */
                    function client($value): void {}
                    client(value("awdawd"));'
            ],
            'yieldFromGenericObjectNotExtendingIterator' => [
                '<?php
                    /** @extends \ArrayObject<int, int> */
                    class Foo extends \ArrayObject {}

                    class A {
                        /**
                         * @var Foo<string>
                         */
                        public Foo $vector;

                        /**
                         * @param Foo<string> $v
                         */
                        public function __construct(Foo $v) {
                            $this->vector = $v;
                        }

                        public function getIterator(): Iterator
                        {
                            yield from $this->vector;
                        }
                    }',
                [],
                ['TooManyTemplateParams']
            ],
            'coerceEmptyArrayToGeneral' => [
                '<?php
                    /** @template-covariant T */
                    class Foo
                    {
                        /** @param \Closure(string):T $closure */
                        public function __construct($closure) {}
                    }

                    class Bar
                    {
                        /** @var Foo<array> */
                        private $FooArray;

                        public function __construct() {
                            $this->FooArray = new Foo(function(string $s): array {
                                /** @psalm-suppress MixedAssignment */
                                $json = \json_decode($s, true);

                                if (! \is_array($json)) {
                                    return [];
                                }

                                return $json;
                            });

                            takesFooArray($this->FooArray);
                        }
                    }

                    /** @param Foo<array> $_ */
                    function takesFooArray($_): void {}',
            ],
            'allowListAcceptance' => [
                '<?php
                    /** @template T */
                    class Collection
                    {
                        /** @var list<T> */
                        public $values;

                        /** @param list<T> $values */
                        function __construct(array $values)
                        {
                            $this->values = $values;
                        }
                    }

                    /** @return Collection<string> */
                    function makeStringCollection()
                    {
                        return new Collection(getStringList()); // gets typed as Collection<mixed> for some reason
                    }

                    /** @return list<string> */
                    function getStringList(): array
                    {
                        return ["foo", "baz"];
                    }'
            ],
            'allowListAcceptanceIntoArray' => [
                '<?php
                    /** @template T */
                    class Collection
                    {
                        /** @var array<T> */
                        public $values;

                        /** @param array<T> $values */
                        function __construct(array $values)
                        {
                            $this->values = $values;
                        }
                    }

                    /** @return Collection<string> */
                    function makeStringCollection()
                    {
                        return new Collection(getStringList()); // gets typed as Collection<mixed> for some reason
                    }

                    /** @return list<string> */
                    function getStringList(): array
                    {
                        return ["foo", "baz"];
                    }'
            ],
            'allowInternalNullCheck' => [
                '<?php
                    /**
                     * @template TP as ?scalar
                     */
                    class Entity
                    {
                        /**
                         * @var TP
                         */
                        private $parent;

                        /** @param TP $parent */
                        public function __construct($parent) {
                            $this->parent = $parent;
                        }

                        public function hasNoParent() : bool
                        {
                            return $this->parent === null; // So TP does contain null
                        }
                    }'
            ],
            'useMethodWithExistingGenericParam' => [
                '<?php
                    class Bar {
                        public function getFoo(): string {
                            return "foo";
                        }
                    }

                    /**
                     * @template TKey
                     * @template T
                     */
                    interface Collection {
                        /**
                         * @param Closure(T=):bool $p
                         * @return Collection<TKey, T>
                         */
                        public function filter(Closure $p);
                    }

                    /**
                     * @param Collection<int, Bar> $c
                     * @psalm-return Collection<int, Bar>
                     */
                    function filter(Collection $c, string $name) {
                        return $c->filter(
                            function (Bar $f) use ($name) {
                                return $f->getFoo() === "foo";
                            }
                        );
                    }'
            ],
            'unboundVariableIsEmptyInInstanceMethod' => [
                '<?php
                    class A {
                        /**
                         * @template TE
                         * @template TR
                         *
                         * @param TE $elt
                         * @param TR ...$elts
                         *
                         * @return TE|TR
                         */
                        public function collectInstance($elt, ...$elts) {
                            $ret = $elt;
                            foreach ($elts as $item) {
                                if (rand(0, 1)) {
                                    $ret = $item;
                                }
                            }
                            return $ret;
                        }
                    }

                    echo (new A)->collectInstance("a");'
            ],
            'unboundVariableIsEmptyInStaticMethod' => [
                '<?php
                    class A {
                        /**
                         * @template TE
                         * @template TR
                         *
                         * @param TE $elt
                         * @param TR ...$elts
                         *
                         * @return TE|TR
                         */
                        public static function collectStatic($elt, ...$elts) {
                            $ret = $elt;
                            foreach ($elts as $item) {
                                if (rand(0, 1)) {
                                    $ret = $item;
                                }
                            }
                            return $ret;
                        }
                    }

                    echo A::collectStatic("a");'
            ],
            'traversableToIterable' => [
                '<?php
                    /**
                     * @template T1 as array-key
                     * @template T2
                     *
                     * @param iterable<T1,T2> $x
                     *
                     * @return array<T1,T2>
                     */
                    function iterableToArray (iterable $x): array {
                        if (is_array($x)) {
                            return $x;
                        }
                        else {
                            return iterator_to_array($x);
                        }
                    }

                    /**
                     * @param Traversable<int, int> $t
                     * @return array<int, int>
                     */
                    function withParams(Traversable $t) : array {
                        return iterableToArray($t);
                    }',
            ],
            'templateStaticWithParam' => [
                '<?php
                    /**
                     * @template T
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class ArrayCollection {
                        /** @var list<T> */
                        private $elements;

                        /**
                         * @param list<T> $elements
                         */
                        public function __construct(array $elements) {
                            $this->elements = $elements;
                        }

                        /**
                         * @template U
                         * @param callable(T=):U $callback
                         * @return static<U>
                         */
                        public function map(callable $callback) {
                            return new static(array_values(array_map($callback, $this->elements)));
                        }
                    }

                    /** @param ArrayCollection<int> $ints */
                    function takesInts(ArrayCollection $ints) :void {}

                    takesInts((new ArrayCollection([ "a", "bc" ]))->map("strlen"));

                    /**
                     * @template T
                     * @extends ArrayCollection<T>
                     */
                    class LazyArrayCollection extends ArrayCollection {}'
            ],
            'weakReferenceIsTyped' => [
                '<?php
                    $e = new Exception;
                    $r = WeakReference::create($e);
                    $ex = $r->get();
                ',
                [ '$ex' => 'Exception|null' ],
            ],
            'weakReferenceIsCovariant' => [
                '<?php
                    /** @param WeakReference<Throwable> $_ref */
                    function acceptsThrowableRef(WeakReference $_ref): void {}

                    acceptsThrowableRef(WeakReference::create(new Exception));
                '
            ],
            'mapTypeParams' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    class Map {
                        /** @var array<TKey, TValue> */
                        public $arr;

                        /** @param array<TKey, TValue> $arr */
                        function __construct(array $arr) {
                            $this->arr = $arr;
                        }
                    }

                    /**
                     * @template TInputKey as array-key
                     * @template TInputValue
                     * @param Map<TInputKey, TInputValue> $map
                     * @return Map<TInputKey, TInputValue>
                     */
                    function copyMapUsingProperty(Map $map): Map {
                        return new Map($map->arr);
                    }',
            ],
            'mapStaticClassTemplatedFromClassString' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class Base {
                        /** @return static */
                        public static function factory(): self {
                            return new static();
                        }
                    }

                    /**
                     * @template T of Base
                     * @param class-string<T> $t
                     * @return T
                     */
                    function f(string $t) {
                        return $t::factory();
                    }

                    /** @template T of Base */
                    class C {
                        /** @var class-string<T> */
                        private string $t;

                        /** @param class-string<T> $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }

                        /** @return T */
                        public function f(): Base {
                            $t = $this->t;
                            return $t::factory();
                        }
                    }'
            ],
            'uasortCallableInMethod' => [
                '<?php
                    class C {
                        /**
                         * @template T of object
                         * @psalm-param array<T> $collection
                         * @psalm-param callable(T, T): int $sorter
                         * @psalm-return array<T>
                         */
                        function order(array $collection, callable $sorter): array {
                            usort($collection, $sorter);

                            return $collection;
                        }
                    }'
            ],
            'intersectOnTOfObject' => [
                '<?php
                    /**
                     * @psalm-template TO of object
                     */
                    interface A {
                        /**
                         * @psalm-param Closure(TO&A):mixed $c
                         */
                        public function setClosure(Closure $c): void;
                    }

                    function foo(A $i) : void {
                        $i->setClosure(
                            function(A $i) : string {
                                return "hello";
                            }
                        );
                    }'
            ],
            'assertionOnTemplatedClassString' => [
                '<?php
                    class TEM {
                        /**
                         * @template Entity as object
                         * @psalm-param class-string<Entity> $type
                         * @psalm-return EQB<Entity>
                         */
                        public function createEQB(string $type) {
                            if (!class_exists($type)) {
                                throw new InvalidArgumentException();
                            }
                            return new EQB($type);
                        }
                    }

                    /**
                     * @template Entity as object
                     */
                    class EQB {
                        /**
                         * @psalm-var class-string<Entity>
                         */
                        protected $type;

                        /**
                         * @psalm-param class-string<Entity> $type
                         */
                        public function __construct(string $type) {
                            $this->type = $type;
                        }
                    }'
            ],
            'createEmptyArrayCollection' => [
                '<?php
                    $a = new ArrayCollection([]);

                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * An array containing the entries of this collection.
                         *
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements = [];

                        /**
                         * Initializes a new ArrayCollection.
                         *
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @param TKey $key
                         * @param T $t
                         */
                        public function add($key, $t) : void {
                            $this->elements[$key] = $t;
                        }
                    }',
                [
                    '$a' => 'ArrayCollection<empty, empty>'
                ]
            ],
            'newGenericBecomesPropertyTypeValidArg' => [
                '<?php
                    class B {}

                    class A {
                        /** @var ArrayCollection<int, B> */
                        public ArrayCollection $b_collection;

                        public function __construct() {
                            $this->b_collection = new ArrayCollection([]);
                            $this->b_collection->add(5, new B());
                        }
                    }

                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * An array containing the entries of this collection.
                         *
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements = [];

                        /**
                         * Initializes a new ArrayCollection.
                         *
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @param TKey $key
                         * @param T $t
                         */
                        public function add($key, $t) : void {
                            $this->elements[$key] = $t;
                        }
                    }'
            ],
            'allowPropertyCoercion' => [
                '<?php
                    class Test
                    {
                        /**
                         * @var ArrayCollection<int, DateTime>
                         */
                        private $c;

                        public function __construct()
                        {
                            $this->c = new ArrayCollection();
                            $this->c->filter(function (DateTime $dt): bool {
                                return $dt === $dt;
                            });
                        }
                    }

                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements;

                        /**
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @psalm-param Closure(T=):bool $p
                         * @psalm-return self<TKey, T>
                         */
                        public function filter(Closure $p)
                        {
                            return $this;
                        }
                    }'
            ],
            'unionClassStringInferenceAndDefaultEmptyArray' => [
                '<?php
                    class A{}

                    $packages = Collection::fromClassString(A::class);

                    /**
                     * @template T
                     */
                    class Collection{
                        /** @var array<T> $items */
                        protected $items = [];

                        /**
                         * @param array<string, T> $items
                         */
                        public function __construct(array $items = [])
                        {
                            $this->items = $items;
                        }

                        /**
                         * @template C as object
                         * @param class-string<C> $classString
                         * @param array<string, C> $elements
                         * @return Collection<C>
                         */
                        public static function fromClassString(string $classString, array $elements = []) : Collection
                        {
                            return new Collection($elements);
                        }
                    }',
                [
                    '$packages' => 'Collection<A>'
                ]
            ],
            'assertSameOnTemplatedProperty' => [
                '<?php
                    /** @template E as object */
                    final class Box
                    {
                        /** @var E */
                        private $contents;

                        /** @param E $contents */
                        public function __construct(object $contents)
                        {
                            $this->contents = $contents;
                        }

                        /** @param E $thing */
                        public function contains(object $thing) : bool
                        {
                            if ($this->contents !== $thing) {
                                return false;
                            }

                            return true;
                        }
                    }'
            ],
            'assertNotNullOnTemplatedProperty' => [
                '<?php
                    /**
                     * @template T of object
                     */
                    final class A {
                        /**
                         * @psalm-var ?callable(T): bool
                         */
                        public $filter;
                    }

                    /** @psalm-var A<A> */
                    $a = new A();

                    if (null !== $a->filter) {}'
            ],
            'setTemplatedPropertyOutsideClass' => [
                '<?php
                    /**
                     * @template TValue as scalar
                     */
                    class Watcher {
                        /**
                         * @psalm-var TValue
                         */
                        public $value;

                        /**
                         * @psalm-param TValue $value
                         */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /** @psalm-var Watcher<int> $watcher */
                    $watcher = new Watcher(0);
                    $watcher->value = 0;'
            ],
            'callableAsClassStringArray' => [
                '<?php
                    abstract class Id
                    {
                        protected string $id;

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

                    /**
                     * @template T of Id
                     */
                    final class Ids
                    {
                        /**
                         * @psalm-var list<T>
                         */
                        private array $ids;

                        /**
                         * @psalm-param list<T> $ids
                         */
                        private function __construct(array $ids)
                        {
                            $this->ids = $ids;
                        }

                        /**
                         * @template T1 of Id
                         * @psalm-param T1 $class
                         * @psalm-param list<string> $ids
                         * @psalm-return self<T1>
                         */
                        public static function fromObjects(Id $class, array $ids): self
                        {
                            return new self(array_map([$class, "fromString"], $ids));
                        }

                        /**
                         * @template T1 of Id
                         * @psalm-param class-string<T1> $class
                         * @psalm-param list<string> $ids
                         * @psalm-return self<T1>
                         */
                        public static function fromStrings(string $class, array $ids): self
                        {
                            return new self(array_map([$class, "fromString"], $ids));
                        }
                    }'
            ],
            'noCrashTemplateInsideGenerator' => [
                '<?php
                    namespace Foo;

                    /**
                     * @template T
                     */
                    final class Set
                    {
                        /** @var \Iterator<T> */
                        private \Iterator $values;

                        /**
                         * @param \Iterator<T> $values
                         */
                        public function __construct(\Iterator $values)
                        {
                            $this->values = $values;
                        }

                        /**
                         * @param T $element
                         *
                         * @return self<T>
                         */
                        public function __invoke($element): self
                        {
                            return new self(
                                (
                                    function($values, $element): \Generator {
                                        /** @var T $value */
                                        foreach ($values as $value) {
                                            yield $value;
                                        }

                                        yield $element;
                                    }
                                )($this->values, $element),
                            );
                        }
                    }',
                [],
                ['MissingClosureParamType']
            ],
            'templatedPropertyAllowsNull' => [
                '<?php
                    /**
                     * @template TKey as string|null
                     */
                    class A {
                        /** @var TKey  */
                        public $key;

                        /**
                         * @param TKey $key
                         */
                        public function __construct(?string $key)
                        {
                            $this->key = $key;
                        }
                    }'
            ],
            'templatePropertyWithoutParams' => [
                '<?php
                    /**
                     * @template T of object
                     */
                    class Batch {
                        /**
                         * @var iterable<T>
                         */
                        public iterable $objects = [];

                        /**
                         * @var callable(T): void
                         */
                        public $onEach;

                        public function __construct() {
                            $this->onEach = function (): void {};
                        }
                    }

                    function handle(Batch $message, object $o): void {
                        $fn = $message->onEach;
                        $fn($o);
                    }'
            ],
            'changePropertyTypeOfTemplate' => [
                '<?php
                    class A {
                        public int $x = 0;
                    }

                    /**
                     * @template T as A
                     * @param T $obj
                     * @param-out T $obj
                     */
                    function foo(A &$obj): void {
                        $obj->x = 1;
                    }'
            ],
            'multipleMatchingObjectsInUnion' => [
                '<?php
                    /** @template T */
                    interface Container {
                        /** @return T */
                        public function get();
                    }

                    /**
                     * @template T
                     * @param array<Container<T>> $containers
                     * @return T
                     */
                    function unwrap(array $containers) {
                        return array_map(
                            fn($container) => $container->get(),
                            $containers
                        )[0];
                    }

                    /**
                     * @param array<Container<int>|Container<string>> $typed_containers
                     */
                    function takesDifferentTypes(array $typed_containers) : void {
                        $ret = unwrap($typed_containers);

                        if (is_string($ret)) {}
                        if (is_int($ret)) {}
                    }',
                [],
                [],
                '7.4'
            ],
            'templateWithLateResolvedType' => [
                '<?php
                    /**
                     * @template A of Enum::TYPE_*
                     */
                    class Foo {}

                    class Enum
                    {
                        const TYPE_ONE = 1;
                        const TYPE_TWO = 2;
                    }

                    /** @var Foo<Enum::TYPE_ONE> $foo */
                    $foo = new Foo();'
            ],
            'SKIPPED-extendedPropertyTypeParameterised' => [
                '<?php
                    namespace App;

                    use DateTimeImmutable;
                    use Ds\Map;

                    abstract class Z
                    {
                        public function test(): void
                        {
                            $map = $this->createMap();

                            $date = $map->get("test");

                            echo $date->format("Y");
                        }

                        /**
                         * @return Map<string, DateTimeImmutable>
                         */
                        abstract protected function createMap(): Map;
                    }'
            ],
            'looseEquality' => [
                '<?php

                    /**
                     * @psalm-immutable
                     * @template T of self::READ_UNCOMMITTED|self::READ_COMMITTED|self::REPEATABLE_READ|self::SERIALIZABLE
                     */
                    final class TransactionIsolationLevel
                    {
                        private const READ_UNCOMMITTED = "read uncommitted";
                        private const READ_COMMITTED = "read committed";
                        private const REPEATABLE_READ = "repeatable read";
                        private const SERIALIZABLE = "serializable";

                        /**
                         * @psalm-var T $level
                         */
                        private string $level;

                        /**
                         * @psalm-param T $level
                         */
                        private function __construct(string $level)
                        {
                            $this->level = $level;
                        }

                        /**
                         * @psalm-return self<self::READ_UNCOMMITTED>
                         */
                        public static function readUncommitted(): self
                        {
                            return new self(self::READ_UNCOMMITTED);
                        }

                        /**
                         * @psalm-return T
                         */
                        public function toString(): string
                        {
                            return $this->level;
                        }

                        /**
                         * @psalm-template TResult
                         * @psalm-param callable(self::READ_UNCOMMITTED): TResult $readUncommitted
                         * @psalm-return TResult
                         */
                        public function resolve(callable $readUncommitted) {
                            if ($this->level == self::READ_UNCOMMITTED) {
                                return $readUncommitted($this->level);
                            }

                            throw new \LogicException("bad");
                        }
                    }'
            ],
            'narrowTemplateTypeWithInstanceof' => [
                '<?php
                    class Foo {}
                    class Bar {}

                    /** @template FooOrBarOrNull of Foo|Bar|null */
                    class Resolved
                    {
                        /**
                         * @var FooOrBarOrNull
                         */
                        private $entity = null;

                        /**
                         * @psalm-param FooOrBarOrNull $qux
                         */
                        public function __contruct(?object $qux)
                        {
                            if ($qux instanceof Foo) {
                                $this->entity = $qux;
                            }
                        }
                    }'
            ],
            'flippedParamsMethodInside' => [
                '<?php
                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @return Traversable<A, B> */
                        public abstract function getTraversable() : Traversable;

                        /**
                         * @param Foo<B, A> $flipped
                         * @return Traversable<B, A>
                         */
                        public function getFlippedTraversable(Foo $flipped): Traversable
                        {
                            return $flipped->getTraversable();
                        }
                    }'
            ],
            'flippedParamsMethodOutside' => [
                '<?php
                    /**
                     * @template B
                     * @template A
                     * @param Foo<B, A> $flipped
                     * @return Traversable<B, A>
                     */
                    function getFlippedTraversable(Foo $flipped): Traversable {
                        return $flipped->getTraversable();
                    }

                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @return Traversable<A, B> */
                        public abstract function getTraversable() : Traversable;
                    }'
            ],
            'flippedParamsPropertyInside' => [
                '<?php
                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @var Traversable<A, B> */
                        public $traversable;

                        /**
                         * @param Foo<B, A> $flipped
                         * @return Traversable<B, A>
                         */
                        public function getFlippedTraversable(Foo $flipped): Traversable
                        {
                            return $flipped->traversable;
                        }
                    }'
            ],
            'flippedParamsPropertyOutside' => [
                '<?php
                    /**
                     * @template B
                     * @template A
                     * @param Foo<B, A> $flipped
                     * @return Traversable<B, A>
                     */
                    function getFlippedTraversable(Foo $flipped): Traversable {
                        return $flipped->traversable;
                    }

                    /**
                     * @template A
                     * @template B
                     */
                    abstract class Foo
                    {
                        /** @var Traversable<A, B> */
                        public $traversable;
                    }'
            ],
            'simpleTemplate' => [
                '<?php
                    /** @template T */
                    interface F {}

                    /** @param F<mixed> $f */
                    function takesFMixed(F $f) : void {}

                    function sendsF(F $f) : void {
                        takesFMixed($f);
                    }'
            ],
            'arrayCollectionMapInternal' => [
                '<?php
                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     * @psalm-consistent-constructor
                     * @psalm-consistent-templates
                     */
                    class ArrayCollection
                    {
                        /** @psalm-var array<TKey,T> */
                        private $elements;

                        /** @psalm-param array<TKey,T> $elements */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @template TNewKey of array-key
                         * @template TNew
                         * @psalm-param array<TNewKey, TNew> $elements
                         * @psalm-return static<TNewKey, TNew>
                         */
                        protected static function createFrom(array $elements)
                        {
                            return new static($elements);
                        }

                        /**
                         * @psalm-template U
                         * @psalm-param Closure(T=):U $func
                         * @psalm-return static<TKey, U>
                         */
                        public function map(Closure $func)
                        {
                            $new_elements = array_map($func, $this->elements);
                            return self::createFrom($new_elements);
                        }
                    }'
            ],
            'arrayCollectionMapExternal' => [
                '<?php
                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     * @psalm-consistent-constructor
                     */
                    class ArrayCollection
                    {
                        /** @psalm-var array<TKey,T> */
                        private $elements;

                        /** @psalm-param array<TKey,T> $elements */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @psalm-template U
                         * @psalm-param Closure(T=):U $func
                         * @psalm-return ArrayCollection<TKey, U>
                         */
                        public function map(Closure $func)
                        {
                            $new_elements = array_map($func, $this->elements);
                            return Creator::createFrom($new_elements);
                        }
                    }

                    class Creator {
                        /**
                         * @template TNewKey of array-key
                         * @template TNew
                         * @psalm-param array<TNewKey, TNew> $elements
                         * @psalm-return ArrayCollection<TNewKey, TNew>
                         */
                        public static function createFrom(array $elements) {
                            return new ArrayCollection($elements);
                        }
                    }'
            ],
            'templateWithClassConstants' => [
                '<?php
                    /**
                     * @psalm-immutable
                     * @template T of self::A|self::B|self::C
                     */
                    final class Foo
                    {
                        public const A = "aa";
                        public const B = "bb";
                        public const C = "cc";

                        /**
                         * @psalm-var T $level
                         */
                        private string $level;

                        /**
                         * @psalm-param T $level
                         */
                        public function __construct(string $level)
                        {
                            $this->level = $level;
                        }
                    }

                    /**
                     * @psalm-return Foo<Foo::A>
                     */
                    function getFooA(): Foo {
                        return new Foo(Foo::A);
                    }'
            ],
            'callTemplatedMethodOnSameClass' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    class Mapper {
                        /**
                         * @param T $e
                         * @return T
                         */
                        public function foo($e) {
                            return $e;
                        }

                        /**
                         * @param T $e
                         * @return T
                         */
                        public function passthru($e) {
                            return $this->foo($e);
                        }
                    }'
            ],
            'templatedStaticUnion' => [
                '<?php
                    /**
                     * @template T
                     * @psalm-consistent-templates
                     */
                    abstract class A {
                        /**
                          * @var T
                          */
                        private $v;

                        /**
                          * @param T $v
                          */
                        final public function __construct($v) {
                            $this->v = $v;
                        }

                        /**
                          * @return static<T>
                          */
                        public function foo(): A {
                            if (rand(0, 1)) {
                                return new static($this->v);
                            } else {
                                return new static($this->v);
                            }
                        }
                    }'
            ],
            'templatedTypeWithLimitGoesIntoTemplatedType' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    abstract class A {}

                    function takesA(A $a) : void {}

                    function foo(A $a) : void {
                        takesA($a);
                    }',
            ],
            'templateIsAComplexMultilineType' => [
                '<?php
                /**
                 * @template T of array{
                 *    a: string,
                 *    b: int
                 * }
                 */
                class MyContainer {
                    /** @var T */
                    private $value;
                    /** @param T $value */
                    public function __construct($value) {
                        $this->value = $value;
                    }
                    /** @return T */
                    public function getValue() {
                      return $this->value;
                    }
                }'
            ],
            'newWithoutInferredTemplate' => [
                '<?php
                    /**
                     * @psalm-template T2 of object
                     */
                    final class Foo {}

                    $f = new Foo();',
                [
                    '$f' => 'Foo<object>'
                ]
            ],
            'PHP80-weakmapIsGeneric' => [
                '<?php
                    /** @param WeakMap<Throwable,int> $wm */
                    function isCountable(WeakMap $wm): int {
                        return count($wm);
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     * @return array{Throwable,int}
                     */
                    function isTraverable(WeakMap $wm): array {
                        foreach ($wm as $k => $v) {
                            return [$k, $v];
                        }
                        throw new RuntimeException;
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     * @return Traversable<Throwable,int>
                     */
                    function hasAggregateIterator(WeakMap $wm): Traversable {
                        return $wm->getIterator();
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     */
                    function readsLikeAnArray(WeakMap $wm): int {
                        $ex = new Exception;
                        if (isset($wm[$ex])) {
                            return $wm[$ex];
                        }
                        throw new RuntimeException;
                    }

                    /**
                     * @param WeakMap<Throwable,int> $wm
                     */
                    function writesLikeAnArray(WeakMap $wm): void {
                        $ex = new Exception;
                        $wm[$ex] = 42;
                    }
                ',
            ],
            'combineTwoTemplatedArrays' => [
                '<?php
                    /** @template T */
                    class Option
                    {
                        /** @param T $v */
                        public function __construct(private $v) {}

                        /**
                         * @template E
                         * @param E $else
                         * @return T|E
                         */
                        public function getOrElse($else)
                        {
                           return rand(0, 1) === 1 ? $this->v : $else;
                        }
                    }

                    $opt = new Option([1, 3]);

                    $b = $opt->getOrElse([2, 4])[0];',
                [
                    '$b===' => '1|2'
                ]
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
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
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:20:34 - Argument 1 of type expects string, callable(State):(T:AlmostFooMap as mixed)&Foo provided',
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
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:47:29 - Argument 1 of CharacterRow::__set expects "height"|"id"|"name", "ame" provided',
            ],
            'specialiseTypeBeforeReturning' => [
                '<?php
                    class Base {}
                    class Derived extends Base {}

                    /**
                     * @template T of Base
                     */
                    class Foo {
                        /**
                         * @param T $t
                         */
                        public function __construct ($t) {}
                    }

                    /**
                     * @return Foo<Base>
                     */
                    function returnFooBase() {
                        $f = new Foo(new Derived());
                        takesFooDerived($f);
                        return $f;
                    }

                    /**
                     * @param Foo<Derived> $foo
                     */
                    function takesFooDerived($foo): void {}',
                'error_message' => 'InvalidReturnStatement'
            ],
            'possiblySpecialiseTypeBeforeReturning' => [
                '<?php
                    class Base {}
                    class Derived extends Base {}

                    /**
                     * @template T of Base
                     */
                    class Foo {
                        /**
                         * @param T $t
                         */
                        public function __construct ($t) {}
                    }

                    /**
                     * @return Foo<Base>
                     */
                    function returnFooBase() {
                        $f = new Foo(new Derived());

                        if (rand(0, 1)) {
                            takesFooDerived($f);
                        }

                        return $f;
                    }

                    /**
                     * @param Foo<Derived> $foo
                     */
                    function takesFooDerived($foo): void {}',
                'error_message' => 'InvalidReturnStatement'
            ],
            'preventUseWithMoreSpecificParamInt' => [
                '<?php
                    /** @template T */
                    abstract class Collection {
                        /** @param T $elem */
                        public function add($elem): void {}
                    }

                    /**
                     * @template T
                     * @param Collection<T> $col
                     */
                    function usesCollection(Collection $col): void {
                        $col->add(456);
                    }',
                'error_message' => 'InvalidArgument'
            ],
            'preventUseWithMoreSpecificParamEmptyArray' => [
                '<?php
                    /** @template T */
                    abstract class Collection {
                        /** @param T $elem */
                        public function add($elem): void {}
                    }

                    /**
                     * @template T
                     * @param Collection<T> $col
                     */
                    function usesCollection(Collection $col): void {
                        $col->add([]);
                    }',
                'error_message' => 'InvalidArgument'
            ],
            'preventTemplatedCorrectionBeingWrittenTo' => [
                '<?php
                    namespace NS;

                    /**
                     * @template TKey
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
                         * @param TKey $key
                         * @param TValue $value
                         */
                        public function addItem($key, $value) : void {
                            $this->data[$key] = $value;
                        }
                    }

                    class Item {}
                    class SubItem extends Item {}
                    class OtherSubItem extends Item {}

                    /**
                     * @param ArrayCollection<int,Item> $i
                     */
                    function takesCollectionOfItems(ArrayCollection $i): void {
                       $i->addItem(10, new OtherSubItem);
                    }

                    $subitem_collection = new ArrayCollection([ new SubItem ]);

                    takesCollectionOfItems($subitem_collection);',
                'error_message' => 'InvalidArgument'
            ],
            'noClassTemplatesInStaticMethods' => [
                '<?php
                    /**
                     * @template T
                     */
                    class C {
                        /**
                         * @param T $t
                         */
                        public static function foo($t) : void {}
                    }',
                'error_message' => 'UndefinedDocblockClass'
            ],
            'newGenericBecomesPropertyTypeInvalidArg' => [
                '<?php
                    class B {}
                    class C {}

                    class A {
                        /** @var ArrayCollection<int, B> */
                        public ArrayCollection $b_collection;

                        public function __construct() {
                            $this->b_collection = new ArrayCollection([]);
                            $this->b_collection->add(5, new C());
                        }
                    }

                    /**
                     * @psalm-template TKey
                     * @psalm-template T
                     */
                    class ArrayCollection
                    {
                        /**
                         * An array containing the entries of this collection.
                         *
                         * @psalm-var array<TKey,T>
                         * @var array
                         */
                        private $elements = [];

                        /**
                         * Initializes a new ArrayCollection.
                         *
                         * @param array $elements
                         *
                         * @psalm-param array<TKey,T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        /**
                         * @param TKey $key
                         * @param T $t
                         */
                        public function add($key, $t) : void {
                            $this->elements[$key] = $t;
                        }
                    }',
                'error_message' => 'InvalidArgument'
            ],
            'preventIteratorAggregateToIterableWithDifferentTypes' => [
                '<?php
                    class Foo {}

                    class Bar {}

                    /** @param iterable<int, Foo> $foos */
                    function consume(iterable $foos): void {}

                    /** @param IteratorAggregate<int, Bar> $t */
                    function foo(IteratorAggregate $t) : void {
                        consume($t);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventPassingToBoundParam' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Container
                    {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        public function __construct($t)
                        {
                            $this->t = $t;
                        }

                        /**
                         * @param T $t
                         * @return T
                         */
                        protected function makeNew($t)
                        {
                            return $t;
                        }

                        /**
                         * @template U
                         * @param U $u
                         */
                        public function map($u) : void
                        {
                            $this->makeNew($u);
                        }
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'bindRedirectedTemplate' => [
                '<?php
                    /**
                     * @template TIn
                     * @template TOut
                     */
                    final class Map
                    {
                        /** @param Closure(TIn): TOut $c */
                        public function __construct(private Closure $c) {}

                        /**
                         * @template TIn2 as list<TIn>
                         * @param TIn2 $in
                         * @return list<TOut>
                         */
                        public function __invoke(array $in) : array {
                            return array_map(
                                $this->c,
                                $in
                            );
                        }
                    }

                    $m = new Map(fn(int $num) => (string) $num);
                    $m(["a"]);',
                'error_message' => 'InvalidScalarArgument',
                [],
                false,
                '8.0'
            ],
            'bindClosureParamAccurately' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    interface Collection {
                        /**
                         * @template T
                         * @param Closure(TValue):T $func
                         * @return Collection<TKey,T>
                         */
                        public function map(Closure $func);

                    }

                    /**
                     * @param Collection<int, string> $c
                     */
                    function f(Collection $c): void {
                        $fn = function(int $_p): bool { return true; };
                        $c->map($fn);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'limitTemplateTypeWithSameName' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    abstract class A {}

                    function takesA(A $a) : void {}

                    /** @param A<stdClass> $a */
                    function foo(A $a) : void {
                        takesA($a);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'limitTemplateTypeExtended' => [
                '<?php

                    /**
                     * @template T as object
                     */
                    abstract class A {}

                    /**
                     * @extends A<stdClass>
                     */
                    class AChild extends A {}

                    function takesA(A $a) : void {}

                    $child = new AChild();
                    takesA($child);',
                'error_message' => 'InvalidArgument',
            ],
            'noCrashTemplatedClosure' => [
                '<?php
                    /**
                     * @template TCallback as Closure():string
                     */
                    class A {
                        /** @var TCallback */
                        private $callback;

                        /** @param TCallback $callback */
                        public function __construct(Closure $callback) {
                            $this->callback = $callback;
                        }

                        /** @param TCallback $callback */
                        public function setCallback(Closure $callback): void {
                            $this->callback = $callback;
                        }
                    }
                    $a = new A(function() { return "a";});
                    $a->setCallback(function() { return "b";});',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
