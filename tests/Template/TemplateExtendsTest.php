<?php
namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class TemplateExtendsTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
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
                     * @extends Tuple1<T0>
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
            'iterateOverExtendedArrayObjectWithParam' => [
                '<?php
                    class O {}
                    class Foo extends O {
                        public function bar() : void {}
                    }

                    /**
                     * @template T as O
                     * @template-extends ArrayObject<int, T>
                     */
                    class Collection extends ArrayObject
                    {
                        /** @var class-string<T> */
                        public $class;

                        /** @param class-string<T> $class */
                        public function __construct(string $class) {
                                $this->class = $class;
                        }
                    }

                    /** @return Collection<Foo> */
                    function getFooCollection() : Collection {
                        return new Collection(Foo::class);
                    }

                    foreach (getFooCollection() as $i => $foo) {
                        $foo->bar();
                    }',
            ],
            'iterateOverExtendedArrayObjectWithoutParam' => [
                '<?php
                    class O {}
                    class Foo extends O {
                        public function bar() : void {}
                    }

                    /**
                     * @template T as O
                     * @template-extends ArrayObject<int, T>
                     */
                    class Collection extends ArrayObject
                    {
                        /** @var class-string<T> */
                        public $class;

                        /** @param class-string<T> $class */
                        public function __construct(string $class) {
                                $this->class = $class;
                        }
                    }

                    function getFooCollection() : Collection {
                        return new Collection(Foo::class);
                    }

                    foreach (getFooCollection() as $i => $foo) {}',
            ],
            'iterateOverExtendedArrayObjectFromClassCall' => [
                '<?php
                    class O {}
                    class Foo extends O {
                        public function bar() : void {}

                        /** @return Collection<self> */
                        public static function getSelfCollection() : Collection {
                            return new Collection(self::class);
                        }
                    }

                    /**
                     * @template T as O
                     * @template-extends ArrayObject<int, T>
                     */
                    class Collection extends ArrayObject
                    {
                        /** @var class-string<T> */
                        public $class;

                        /** @param class-string<T> $class */
                        public function __construct(string $class) {
                            $this->class = $class;
                        }
                    }

                    foreach (Foo::getSelfCollection() as $i => $foo) {
                        $foo->bar();
                    }',
            ],
            'iterateOverExtendedArrayObjectInsideClass' => [
                '<?php
                    class O {}
                    class Foo extends O {
                        public function bar() : void {}

                        /**
                         * @param Collection<self> $c
                         */
                        public static function takesSelfCollection(Collection $c) : void {
                            foreach ($c as $i => $foo) {
                                $foo->bar();
                            }
                        }
                    }

                    /**
                     * @template T as O
                     * @template-extends ArrayObject<int, T>
                     */
                    class Collection extends ArrayObject
                    {
                        /** @var class-string<T> */
                        public $class;

                        /** @param class-string<T> $class */
                        public function __construct(string $class) {
                            $this->class = $class;
                        }
                    }',
            ],
            'iterateOverExtendedArrayObjectThisClassIteration' => [
                '<?php
                    class O {}

                    /**
                     * @template T as O
                     * @template-extends ArrayObject<int, T>
                     */
                    class Collection extends ArrayObject
                    {
                        /** @var class-string<T> */
                        public $class;

                        /** @param class-string<T> $class */
                        public function __construct(string $class) {
                            $this->class = $class;
                        }

                        private function iterate() : void {
                            foreach ($this as $o) {}
                        }
                    }',
            ],
            'iterateOverExtendedArrayObjectThisClassIterationWithExplicitGetIterator' => [
                '<?php
                    class O {}
                    class Foo extends O {
                        /** @return Collection<self> */
                        public static function getSelfCollection() : Collection {
                            return new Collection(self::class);
                        }

                        public function bar() : void {}
                    }

                    /**
                     * @template T as O
                     * @template-extends ArrayObject<int, T>
                     */
                    class Collection extends ArrayObject
                    {
                        /** @var class-string<T> */
                        public $class;

                        /** @param class-string<T> $class */
                        public function __construct(string $class) {
                            $this->class = $class;
                        }

                        /**
                         * @return \ArrayIterator<int, T>
                         */
                        public function getIterator()
                        {
                            /** @var ArrayIterator<int, O> */
                            return parent::getIterator();
                        }
                    }

                    /** @return Collection<Foo> */
                    function getFooCollection() : Collection {
                        return new Collection(Foo::class);
                    }

                    foreach (getFooCollection() as $i => $foo) {
                        $foo->bar();
                    }

                    foreach (Foo::getSelfCollection() as $i => $foo) {
                        $foo->bar();
                    }',
            ],
            'iterateOverSelfImplementedIterator' => [
                '<?php
                    class O {}
                    class Foo extends O {}

                    /**
                     * @template-implements Iterator<int, Foo>
                     */
                    class FooCollection implements Iterator {
                        private function iterate() : void {
                            foreach ($this as $foo) {}
                        }
                        public function current() { return new Foo(); }
                        public function key(): int { return 0; }
                        public function next(): void {}
                        public function rewind(): void {}
                        public function valid(): bool { return false; }
                    }',
            ],
            'traitUse' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait CollectionTrait
                    {
                        /**
                         * @return array<T>
                         */
                        abstract function elements() : array;

                        /**
                         * @return T|null
                         */
                        public function first()
                        {
                            return $this->elements()[0] ?? null;
                        }
                    }

                    class Service
                    {
                        /**
                         * @use CollectionTrait<int>
                         */
                        use CollectionTrait;

                        /**
                         * @return array<int>
                         */
                        public function elements(): array
                        {
                            return [1, 2, 3, 4];
                        }
                    }',
            ],
            'extendedTraitUse' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait CollectionTrait
                    {
                        /**
                         * @return array<T>
                         */
                        abstract function elements() : array;

                        /**
                         * @return T|null
                         */
                        public function first()
                        {
                            return $this->elements()[0] ?? null;
                        }
                    }

                    /**
                     * @template TValue
                     */
                    trait BridgeTrait
                    {
                        /**
                         * @use CollectionTrait<TValue>
                         */
                        use CollectionTrait;
                    }

                    class Service
                    {
                        /**
                         * @use BridgeTrait<int>
                         */
                        use BridgeTrait;

                        /**
                         * @return array<int>
                         */
                        public function elements(): array
                        {
                            return [1, 2, 3, 4];
                        }
                    }',
            ],
            'extendedTraitUseAlreadyBound' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait CollectionTrait
                    {
                        /**
                         * @return array<T>
                         */
                        abstract function elements() : array;

                        /**
                         * @return T|null
                         */
                        public function first()
                        {
                            return $this->elements()[0] ?? null;
                        }
                    }

                    trait BridgeTrait
                    {
                        /**
                         * @use CollectionTrait<int>
                         */
                        use CollectionTrait;
                    }

                    class Service
                    {
                        use BridgeTrait;

                        /**
                         * @return array<int>
                         */
                        public function elements(): array
                        {
                            return [1, 2, 3, 4];
                        }
                    }',
            ],
            'extendClassThatParameterizesTemplatedParent' => [
                '<?php
                    /**
                     * @template T
                     */
                    abstract class Collection
                    {
                        /**
                         * @return array<T>
                         */
                        abstract function elements() : array;

                        /**
                         * @return T|null
                         */
                        public function first()
                        {
                            return $this->elements()[0] ?? null;
                        }
                    }

                    /**
                     * @template-extends Collection<int>
                     */
                    abstract class Bridge extends Collection {}


                    class Service extends Bridge
                    {
                        /**
                         * @return array<int>
                         */
                        public function elements(): array
                        {
                            return [1, 2, 3];
                        }
                    }

                    $a = (new Service)->first();',
                [
                    '$a' => 'null|int',
                ]
            ],
            'splObjectStorage' => [
                '<?php
                    class SomeService
                    {
                        /**
                         * @var \SplObjectStorage<\stdClass, mixed>
                         */
                        public $handlers;

                        /**
                         * @param SplObjectStorage<\stdClass, mixed> $handlers
                         */
                        public function __construct(SplObjectStorage $handlers)
                        {
                            $this->handlers = $handlers;
                        }
                    }

                    /** @var SplObjectStorage<\stdClass, string> */
                    $storage = new SplObjectStorage();
                    new SomeService($storage);

                    $c = new \stdClass();
                    $storage[$c] = "hello";
                    $b = $storage->offsetGet($c);',
                [
                    '$b' => 'string',
                ]
            ],
            'extendsArrayIterator' => [
                '<?php
                    class User {}

                    /**
                     * @template-extends ArrayIterator<int, User>
                     */
                    class Users extends ArrayIterator
                    {
                        public function __construct(User ...$users) {
                            parent::__construct($users);
                        }
                    }',
            ],
            'extendsAndCallsParent' => [
                '<?php
                    /**
                     * @template T
                     */
                    abstract class Foo
                    {
                        /**
                         * @param T::class $str
                         *
                         * @return T::class
                         */
                        public static function DoThing(string $str)
                        {
                            return $str;
                        }
                    }
                    /**
                     * @template-extends Foo<DateTimeInterface>
                     */
                    class Bar extends Foo
                    {
                        /**
                         * @param class-string<DateTimeInterface> $str
                         *
                         * @return class-string<DateTimeInterface>
                         */
                        public static function DoThing(string $str)
                        {
                            return parent::DoThing($str);
                        }
                    }'
            ],
            'genericStaticAndSelf' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface Functor
                    {
                        /**
                         * @template U
                         *
                         * @param Closure(T):U $c
                         *
                         * @return static<U>
                         */
                        public function map(Closure $c);
                    }
                    /**
                     * @template T
                     */
                    class Box implements Functor
                    {
                        /**
                         * @var T
                         */
                        public $value;
                        /**
                         * @param T $x
                         */
                        public function __construct($x)
                        {
                            $this->value = $x;
                        }
                        /**
                         * @template U
                         *
                         * @param Closure(T):U $c
                         *
                         * @return self<U>
                         */
                        public function map(Closure $c)
                        {
                            return new Box($c($this->value));
                        }
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
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
                'error_message' => 'ImplementedReturnTypeMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:29 - The return type \'A\Bar\' for',
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
            'mismatchingTypesAfterExtends' => [
                '<?php
                    class Foo {}
                    class Bar {}

                    /**
                     * @implements IteratorAggregate<int, Foo>
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
                     * @implements IteratorAggregate<int, Foo>
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
            'badTemplateExtendsInt' => [
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
                     * @template-extends int
                     */
                    class B extends A {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateExtendsBadFormat' => [
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
                     * @template-extends A< >
                     */
                    class B extends A {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateExtendsUnionType' => [
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
                     * @template-extends A<int|string>
                     */
                    class B extends A {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateImplementsShouldBeExtends' => [
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
                     * @template-implements A<int>
                     */
                    class B extends A {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateImplements' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /** @param T $t */
                        public function __construct($t);
                    }

                    /**
                     * @template TT
                     * @template-implements I<Z>
                     */
                    class B implements I {}',
                'error_message' => 'UndefinedClass'
            ],
            'badTemplateImplementsInt' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /** @param T $t */
                        public function __construct($t);
                    }

                    /**
                     * @template TT
                     * @template-implements int
                     */
                    class B implements I {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateImplementsBadFormat' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /** @param T $t */
                        public function __construct($t);
                    }

                    /**
                     * @template TT
                     * @template-implements I< >
                     */
                    class B implements I {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateImplementsUnionType' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /** @param T $t */
                        public function __construct($t);
                    }

                    /**
                     * @template TT
                     * @template-implements I<int|string>
                     */
                    class B implements I {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateExtendsShouldBeImplements' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /** @param T $t */
                        public function __construct($t);
                    }

                    /**
                     * @template TT
                     * @template-extends I<string>
                     */
                    class B implements I {}',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateUse' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use T<Z>
                         */
                        use T;
                    }',
                'error_message' => 'UndefinedClass'
            ],
            'badTemplateUseBadFormat' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use T< >
                         */
                        use T;
                    }',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateUseInt' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use int
                         */
                        use T;
                    }',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateExtendsShouldBeUse' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-extends T<int>
                         */
                        use T;
                    }',
                'error_message' => 'InvalidDocblock'
            ],
            'badTemplateUseUnionType' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait T {
                        /** @var T */
                        public $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template TT
                     */
                    class B {
                        /**
                         * @template-use T<int|string>
                         */
                        use T;
                    }',
                'error_message' => 'InvalidDocblock'
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
            'templateImplementsWithoutAllParams' => [
                '<?php
                    /**
                     * @template T
                     * @template V
                     * @template U
                     */
                    interface I {}

                    /**
                     * @implements I<int>
                     */
                    class CC implements I {}',
                'error_message' => 'MissingTemplateParam'
            ],
            'extendsTemplateButLikeBadly' => [
                '<?php
                    /**
                     * @template T as object
                     */
                    class Base {
                        /** @param T $_o */
                        public function __construct($_o) {}
                        /**
                         * @return T
                         * @psalm-suppress InvalidReturnType
                         */
                        public function t() {}
                    }

                    /** @template-extends Base<int> */
                    class SpecializedByInheritance extends Base {}',
                'error_message' => 'InvalidTemplateParam'
            ],
        ];
    }
}
