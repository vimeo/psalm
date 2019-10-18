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
                        /** @var class-string<T> */
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
                         * @psalm-return callable(): ?T
                         */
                        public function bar() {
                            return
                                /** @psalm-return ?T */
                                function() {
                                    /** @psalm-var ?T */
                                    $data = null;
                                    return $data;
                                };
                        }
                    }',
            ],
            'allowBoundedType' => [
                '<?php
                    class Base {}
                    class Child extends Base {}

                    /**
                     * @template T
                     */
                    class Foo
                    {
                        /** @param Closure():T $t */
                        public function __construct(Closure $t) {}
                    }

                    /**
                     * @return Foo<Base>
                     */
                    function returnFooBase() : Foo {
                        $f = new Foo(function () { return new Child(); });
                        return $f;
                    }',
            ],
            'allowMoreSpecificArray' => [
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
                            $this->FooArray = new Foo(function(): array { return ["foo" => "bar"]; });
                        }
                    }'
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
                    class Foo{}

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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
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
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:20:34 - Argument 1 of type expects string, callable(State):(T as mixed)&Foo provided',
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
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:47:29 - Argument 1 of CharacterRow::__set expects string(height)|string(id)|string(name), string(ame) provided',
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
            'specializeTypeInPropertyAssignment' => [
                '<?php
                    /** @template T */
                    class Foo {
                        /** @var \Closure():T $closure */
                        private $closure;

                        /** @param \Closure():T $closure */
                        public function __construct($closure)
                        {
                            $this->closure = $closure;
                        }
                    }

                    class Bar {
                        /** @var Foo<array> */
                        private $FooArray;

                        public function __construct() {
                            $this->FooArray = new Foo(function(): array { return ["foo" => "bar"]; });
                            expectsShape($this->FooArray);
                        }
                    }

                    /** @param Foo<array{foo: string}> $_ */
                    function expectsShape($_): void {}',
                'error_message' => 'MixedArgumentTypeCoercion'
            ],
            'coerceEmptyArrayToGeneral' => [
                '<?php
                    /** @template T */
                    class Foo
                    {
                        /** @param \Closure(string):T $closure */
                        public function __construct($closure) {}
                    }

                    class Bar
                    {
                      /** @var Foo<array> */
                      private $FooArray;

                      public function __construct()
                      {
                          $this->FooArray = new Foo(function(string $s): array {
                              /** @psalm-suppress MixedAssignment */
                              $json = \json_decode($s, true);

                              if (! \is_array($json)) {
                                  return [];
                              }

                              return $json;
                          });

                          takesEmpty($this->FooArray);
                        }
                    }

                    /** @param Foo<array<empty, empty>> $_ */
                    function takesEmpty($_): void {}',
                'error_message' => 'MixedArgumentTypeCoercion'
            ],
        ];
    }
}
