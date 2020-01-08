<?php
namespace Psalm\Tests\Template;

use const DIRECTORY_SEPARATOR;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class ClassTemplateExtendsTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
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
                    '$b' => 'int',
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
                    '$b' => 'int',
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
                ],
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
                ],
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
                    }',
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
                ],
            ],
            'extendsTwiceSameNameCorrect' => [
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
                ],
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
                ],
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
                    '$b' => 'SpecificEntity|null',
                ],
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
                    '$b' => 'SpecificEntity|null',
                ],
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
                    );',
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
                ],
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
                    class SomeRepository extends Repository {}',
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
            'constructExtendedArrayIteratorWithTemplateExtends' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @template-extends ArrayIterator<TKey, TValue>
                     */
                    class Collection1 extends ArrayIterator{}

                    class Collection2 extends Collection1{}

                    class Collection3 extends Collection2{}

                    foreach ((new Collection1(["a" => "b"])) as $a) {}

                    /** @psalm-suppress MixedAssignment */
                    foreach ((new Collection2(["a" => "b"])) as $a) {}

                    /** @psalm-suppress MixedAssignment */
                    foreach ((new Collection3(["a" => "b"])) as $a) {}

                    foreach ((new Collection1([])) as $i) {}

                    /** @psalm-suppress MixedAssignment */
                    foreach ((new Collection2([])) as $i) {}

                    /** @psalm-suppress MixedAssignment */
                    foreach ((new Collection3([])) as $i) {}',
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
            'extendWithExplicitOverriddenTemplatedSignature' => [
                '<?php
                    class Obj {}

                    /**
                     * @template T1
                     */
                    class BaseContainer {
                        /** @var T1 */
                        private $t1;

                        /** @param T1 $t1 */
                        public function __construct($t1) {
                            $this->t1 = $t1;
                        }

                        /**
                         * @return T1
                         */
                        public function getValue()
                        {
                            return $this->t1;
                        }
                    }

                    /**
                     * @template T2 as Obj
                     * @template-extends BaseContainer<T2>
                     */
                    class Container extends BaseContainer {
                        /** @param T2 $t2 */
                        public function __construct($t2) {
                            parent::__construct($t2);
                        }

                        /**
                         * @return T2
                         */
                        public function getValue()
                        {
                            return parent::getValue();
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
                        public function getIterator() {
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
            'traitUseNotExtended' => [
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
                    '$a' => 'int|null',
                ],
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
                ],
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
            'useGenericParentMethod' => [
                '<?php
                    /**
                     * @template-extends ArrayObject<string, string>
                     */
                    class Foo extends ArrayObject
                    {
                        public function bar() : void {
                            $c = $this->getArrayCopy();
                            foreach ($c as $d) {
                                echo $d;
                            }
                        }
                    }',
            ],
            'templateExtendsOnceWithSpecificStaticCall' => [
                '<?php
                    /** @template T */
                    class Container {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        private function __construct($t) {
                            $this->t = $t;
                        }

                        /**
                         * @template U
                         * @param U $t
                         * @return static<U>
                         */
                        public static function getContainer($t) {
                            return new static($t);
                        }

                        /**
                         * @return T
                         */
                        public function getValue()
                        {
                            return $this->t;
                        }
                    }

                    /**
                     * @template T1 as A
                     * @template-extends Container<T1>
                     */
                    class AContainer extends Container {}

                    class A {
                        function foo() : void {}
                    }

                    $b = AContainer::getContainer(new A());',
                [
                    '$b' => 'AContainer<A>',
                ],
            ],
            'templateExtendsDifferentNameWithStaticCall' => [
                '<?php
                    /** @template T */
                    class Container {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        private function __construct($t) {
                            $this->t = $t;
                        }

                        /**
                         * @template U
                         * @param U $t
                         * @return static<U>
                         */
                        public static function getContainer($t) {
                            return new static($t);
                        }

                        /**
                         * @return T
                         */
                        public function getValue()
                        {
                            return $this->t;
                        }
                    }

                    /**
                     * @template T1 as object
                     * @template-extends Container<T1>
                     */
                    class ObjectContainer extends Container {}

                    /**
                     * @template T2 as A
                     * @template-extends ObjectContainer<T2>
                     */
                    class AContainer extends ObjectContainer {}

                    class A {
                        function foo() : void {}
                    }

                    $b = AContainer::getContainer(new A());',
                [
                    '$b' => 'AContainer<A>',
                ],
            ],
            'templateExtendsSameNameWithStaticCall' => [
                '<?php
                    /** @template T */
                    class Container {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        private function __construct($t) {
                            $this->t = $t;
                        }

                        /**
                         * @template U
                         * @param U $t
                         * @return static<U>
                         */
                        public static function getContainer($t) {
                            return new static($t);
                        }

                        /**
                         * @return T
                         */
                        public function getValue()
                        {
                            return $this->t;
                        }
                    }

                    /**
                     * @template T as object
                     * @template-extends Container<T>
                     */
                    class ObjectContainer extends Container {}

                    /**
                     * @template T as A
                     * @template-extends ObjectContainer<T>
                     */
                    class AContainer extends ObjectContainer {}

                    class A {
                        function foo() : void {}
                    }

                    $b = AContainer::getContainer(new A());',
                [
                    '$b' => 'AContainer<A>',
                ],
            ],
            'returnParentExtendedTemplateProperty' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Container {
                        /**
                         * @var T
                         */
                        public $t;

                        /**
                         * @param T $t
                         */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template-extends Container<int>
                     */
                    class IntContainer extends Container {
                        public function __construct(int $i) {
                            parent::__construct($i);
                        }

                        public function getValue() : int {
                            return $this->t;
                        }
                    }',
            ],
            'childSetInConstructor' => [
                '<?php
                    /**
                     * @template T0
                     */
                    class Container {
                        /**
                         * @var T0
                         */
                        public $t;

                        /**
                         * @param T0 $t
                         */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template T1 as object
                     * @template-extends Container<T1>
                     */
                    class ObjectContainer extends Container {}',
            ],
            'grandChildSetInConstructor' => [
                '<?php
                    /**
                     * @template T0
                     */
                    class Container {
                        /**
                         * @var T0
                         */
                        public $t;

                        /**
                         * @param T0 $t
                         */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template T1 as object
                     * @template-extends Container<T1>
                     */
                    class ObjectContainer extends Container {}

                    /**
                     * @template T2 as A
                     * @template-extends ObjectContainer<T2>
                     */
                    class AContainer extends ObjectContainer {}

                    class A {}',
            ],
            'extendArrayObjectWithTemplateParams' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @template-extends \ArrayObject<TKey,TValue>
                     */
                    class C extends \ArrayObject {
                        /**
                         * @param array<TKey,TValue> $kv
                         */
                        public function __construct(array $kv) {
                            parent::__construct($kv);
                        }
                    }

                    $c = new C(["a" => 1]);
                    $i = $c->getIterator();',
                [
                    '$c' => 'C<string, int>',
                    '$i' => 'ArrayIterator<string, int>',
                ],
            ],
            'extendsParamCountDifference' => [
                '<?php
                    /**
                     * @template E
                     * @implements \Iterator<int,E>
                     */
                    abstract class Collection implements \Iterator {}

                    /**
                     * @param Collection<string> $collection
                     * @return \Iterator<int,string>
                     */
                    function foo(Collection $collection) {
                        return $collection;
                    }',
            ],
            'dontInheritParamTemplatedTypeSameName' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                      /**
                       * @param T $t
                       */
                      public function add($t) : void;
                    }

                    /**
                     * @template T
                     */
                    class C implements I {
                      /** @var array<T> */
                      private $t;

                      /**
                       * @param array<T> $t
                       */
                      public function __construct(array $t) {
                        $this->t = $t;
                      }

                      /**
                       * @inheritdoc
                       */
                      public function add($t) : void {
                        $this->t[] = $t;
                      }
                    }

                    /** @param C<string> $c */
                    function foo(C $c) : void {
                        $c->add(new stdClass);
                    }',
            ],
            'dontInheritParamTemplatedTypeDifferentTemplateNames' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface I {
                      /**
                       * @param T1 $t
                       */
                      public function add($t) : void;
                    }

                    /**
                     * @template T2
                     */
                    class C implements I {
                      /** @var array<T2> */
                      private $t;

                      /**
                       * @param array<T2> $t
                       */
                      public function __construct(array $t) {
                        $this->t = $t;
                      }

                      /**
                       * @inheritdoc
                       */
                      public function add($t) : void {
                        $this->t[] = $t;
                      }
                    }

                    /** @param C<string> $c */
                    function foo(C $c) : void {
                        $c->add(new stdClass);
                    }',
            ],
            'templateExtendsUnionType' => [
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
                    class B implements I {
                        /** @var int|string */
                        public $t;

                        /** @param int|string $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }',
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
            ],
            'extendWithEnoughArgs' => [
                '<?php
                    /**
                     * @template TKey of array-key
                     * @template T
                     * @template-extends IteratorAggregate<TKey, T>
                     */
                    interface Collection extends IteratorAggregate
                    {
                    }

                    /**
                     * @template T
                     * @template TKey of array-key
                     * @template-implements Collection<TKey, T>
                     */
                    class ArrayCollection implements Collection
                    {
                        /**
                         * @psalm-var array<TKey, T>
                         */
                        private $elements;

                        /**
                         * @psalm-param array<TKey, T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        public function getIterator()
                        {
                            return new ArrayIterator($this->elements);
                        }

                        /**
                         * @psalm-suppress MissingTemplateParam
                         *
                         * @psalm-param array<T> $elements
                         * @psalm-return ArrayCollection<T>
                         */
                        protected function createFrom(array $elements)
                        {
                            return new static($elements);
                        }
                    }',
            ],
            'extendWithTooFewArgs' => [
                '<?php
                    /**
                     * @template TKey of array-key
                     * @template T
                     * @template-extends IteratorAggregate<TKey, T>
                     */
                    interface Collection extends IteratorAggregate
                    {
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     * @template T
                     * @template TKey of array-key
                     * @template-implements Collection<TKey>
                     */
                    class ArrayCollection implements Collection
                    {
                        /**
                         * @psalm-var T[]
                         */
                        private $elements;

                        /**
                         * @psalm-param array<T> $elements
                         */
                        public function __construct(array $elements = [])
                        {
                            $this->elements = $elements;
                        }

                        public function getIterator()
                        {
                            return new ArrayIterator($this->elements);
                        }

                        /**
                         * @psalm-suppress MissingTemplateParam
                         *
                         * @psalm-param array<T> $elements
                         * @psalm-return ArrayCollection<T>
                         */
                        protected function createFrom(array $elements)
                        {
                            return new static($elements);
                        }
                    }',
            ],
            'abstractGetIterator' => [
                '<?php
                    /**
                     * @template E
                     * @template-extends \IteratorAggregate<int, E>
                     */
                    interface Collection extends \IteratorAggregate
                    {
                        /**
                         * @return \Iterator<int,E>
                         */
                        public function getIterator(): \Iterator;
                    }

                    /**
                     * @template-implements Collection<string>
                     */
                    abstract class Set implements Collection {
                        public function forEach(callable $action): void {
                            $i = $this->getIterator();
                            foreach ($this as $bar) {
                                $action($bar);
                            }
                        }
                    }',
            ],
            'paramInsideTemplatedFunctionShouldKnowRestriction' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface Hasher {
                        /**
                         * @param T $value
                         */
                        function hash($value): int;
                    }

                    /**
                     * @implements Hasher<int>
                     */
                    class IntHasher implements Hasher {
                        function hash($value): int {
                            return $value % 10;
                        }
                    }

                    /**
                     * @implements Hasher<string>
                     */
                    class StringHasher implements Hasher {
                        function hash($value): int {
                            return strlen($value);
                        }
                    }',
            ],
            'implementsAndExtendsWithTemplateReturningValid' => [
                '<?php
                    /**
                     * @template TReal
                     */
                    interface Collection
                    {
                        /**
                         * @return array<TReal>
                         */
                        function toArray();
                    }

                    /**
                     * @template TDummy
                     * @implements Collection<string>
                     */
                    class IntCollection implements Collection
                    {
                        /** @param TDummy $t */
                        public function __construct($t) {

                        }

                        public function toArray() {
                            return ["foo"];
                        }
                    }',
            ],
            'templateNotExtendedButSignatureInherited' => [
                '<?php
                    class Base {
                        /**
                         * @template T
                         * @param T $x
                         * @return T
                         */
                        function example($x) {
                            return $x;
                        }
                    }

                    class Child extends Base {
                        function example($x) {
                            return $x;
                        }
                    }

                    ord((new Child())->example("str"));',
            ],
            'allowTraitExtendAndImplementWithExplicitParamType' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait ValueObjectTrait
                    {
                        /**
                         * @psalm-var ?T
                         */
                        protected $value;

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        private function setValue($value): void {
                            $this->validate($value);

                            $this->value = $value;
                        }

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        abstract protected function validate($value): void;
                    }

                    final class StringValidator {
                        /**
                         * @template-use ValueObjectTrait<string>
                         */
                        use ValueObjectTrait;

                        /**
                         * @param string $value
                         */
                        protected function validate($value): void
                        {
                            if (strlen($value) > 30) {
                                throw new \Exception("bad");
                            }
                        }
                    }',
            ],
            'allowTraitExtendAndImplementWithoutExplicitParamType' => [
                '<?php
                    /**
                     * @template T
                     */
                    trait ValueObjectTrait
                    {
                        /**
                         * @psalm-var ?T
                         */
                        protected $value;

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        private function setValue($value): void {
                            $this->validate($value);

                            $this->value = $value;
                        }

                        /**
                         * @psalm-param T $value
                         *
                         * @param $value
                         */
                        abstract protected function validate($value): void;
                    }

                    final class StringValidator {
                        /**
                         * @template-use ValueObjectTrait<string>
                         */
                        use ValueObjectTrait;

                        protected function validate($value): void
                        {
                            if (strlen($value) > 30) {
                                throw new \Exception("bad");
                            }
                        }
                    }',
            ],

            'keyOfClassTemplateExtended' => [
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

                        /**
                         * @template K as key-of<TData>
                         *
                         * @param K $property
                         * @param TData[K] $value
                         */
                        public function __set(string $property, $value) {
                            $this->data[$property] = $value;
                        }
                    }

                    /** @extends DataBag<array{a: int, b: string}> */
                    class FooBag extends DataBag {}

                    $foo = new FooBag(["a" => 5, "b" => "hello"]);

                    $foo->a = 9;
                    $foo->b = "hello";

                    $a = $foo->a;
                    $b = $foo->b;',
                [
                    '$a' => 'int',
                    '$b' => 'string',
                ],
            ],
            'templateExtendsWithNewlineAfter' => [
                '<?php
                    namespace Ns;

                    /**
                     * @template DATA as array<string, scalar|array|object|null>
                     */
                    abstract class Foo {}

                    /**
                     * @template-extends Foo<array{id:int}>
                     *
                     * @internal
                     */
                    class Bar extends Foo {}',
            ],
            'implementsArrayReturnTypeWithTemplate' => [
                '<?php
                    /** @template T as mixed */
                    interface I {
                        /**
                         * @param  T $v
                         * @return array<string,T>
                         */
                        public function indexById($v): array;
                    }

                    /** @template-implements I<string> */
                    class C implements I {
                        public function indexById($v): array {
                          return [$v => $v];
                        }
                    }',
            ],
            'keyOfArrayInheritance' => [
                '<?php
                    /**
                     * @template DATA as array<string, int|string>
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
                        abstract public function getIdProperty() : string;
                    }

                    /**
                     * @template-extends Foo<array{id:int, name:string}>
                     */
                    class FooChild extends Foo {
                        public function getIdProperty() : string {
                            return "id";
                        }
                    }',
            ],
            'interfaceParentExtends' => [
                '<?php
                    /** @template T */
                    interface Foo {
                        /** @return T */
                        public function getValue();
                    }

                    /** @extends Foo<int> */
                    interface FooChild extends Foo {}

                    class F implements FooChild {
                        public function getValue() {
                            return 10;
                        }
                    }

                    echo (new F())->getValue();',
            ],
            'classParentExtends' => [
                '<?php
                    /** @template T */
                    abstract class Foo {
                        /** @return T */
                        abstract public function getValue();
                    }

                    /** @extends Foo<int> */
                    abstract class FooChild extends Foo {}

                    class F extends FooChild {
                        public function getValue() {
                            return 10;
                        }
                    }

                    echo (new F())->getValue();',
            ],
            'lessSpecificNonGenericReturnType' => [
                '<?php
                    /**
                     * @template-implements IteratorAggregate<int, int>
                     */
                    class Bar implements IteratorAggregate {
                        public function getIterator() : Traversable {
                            yield from range(0, 100);
                        }
                    }

                    $bat = new Bar();

                    foreach ($bat as $num) {}',
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
            'extendWithExplicitOverriddenTemplatedSignatureHopped' => [
                '<?php
                    class Obj {}

                    /**
                     * @template T1
                     */
                    class Container1 {
                        /** @var T1 */
                        private $t1;

                        /** @param T1 $t1 */
                        public function __construct($t1) {
                            $this->t1 = $t1;
                        }

                        /**
                         * @return T1
                         */
                        public function getValue()
                        {
                            return $this->t1;
                        }
                    }

                    /**
                     * @template T2
                     * @template-extends Container1<T2>
                     */
                    class Container2 extends Container1 {}

                    /**
                     * @template T3 as Obj
                     * @template-extends Container2<T3>
                     */
                    class Container3 extends Container2 {
                        /** @param T3 $t3 */
                        public function __construct($t3) {
                            Container1::__construct($t3);
                        }

                        /**
                         * @return T3
                         */
                        public function getValue()
                        {
                            return parent::getValue();
                        }
                    }',
            ],
            'extendsArryObjectGetIterator' => [
                '<?php
                    class Obj {}

                    /**
                     * @template T1
                     * @template-extends ArrayObject<int, T1>
                     */
                    class Collection extends ArrayObject {}

                    /**
                     * @template T2 as Obj
                     * @template-extends Collection<T2>
                     */
                    class Collection2 extends Collection {
                        /**
                         * called to get the collection ready when we go to loop through it
                         *
                         * @return \ArrayIterator<int, T2>
                         */
                        public function getIterator() {
                            return parent::getIterator();
                        }
                    }',
            ],
            'templatedInterfaceGetIteratorIteration' => [
                '<?php
                    namespace NS;

                    /**
                     * @template TKey
                     * @template TValue
                     * @template-extends \IteratorAggregate<TKey, TValue>
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

                    foreach ($c->getIterator() as $k => $v) { atan($v); strlen($k); }',
            ],
            'traitInImplicitExtendedClass' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface Foo {
                        /**
                         * @return T
                         */
                        public function getItem();
                    }

                    trait FooTrait {
                        public function getItem() {
                            return "hello";
                        }
                    }

                    /**
                     * @template-implements Foo<string>
                     */
                    class Bar implements Foo {
                        use FooTrait;
                    }',
            ],
            'extendedPropertyType' => [
                '<?php
                    interface I {}

                    /** @template T of I */
                    abstract class C {
                        /** @var ?T */
                        protected $m;
                    }

                    class Impl implements I {}

                    /** @template-extends C<Impl> */
                    class Test extends C {
                        protected function foo() : void {
                            $this->m = new Impl();
                        }
                    }'
            ],
            'constructorCheckInChildClassArrayType' => [
                '<?php
                    interface I {}

                    /** @template T of I */
                    abstract class C
                    {
                        /** @var array<string, T> */
                        protected $items = [];

                        // added to trigger constructor initialisation checks
                        // in descendant classes
                        public int $i;

                        /** @param array<string, T> $items */
                        public function __construct($items = []) {
                            $this->i = 5;

                            foreach ($items as $k => $v) {
                                $this->items[$k] = $v;
                            }
                        }
                    }

                    class Impl implements I {}

                    /**
                     * @template-extends C<Impl>
                     */
                    class Test extends C {}'
            ],
            'eitherType' => [
                '<?php
                    /**
                     * @template L
                     * @template R
                     */
                    interface Either{}

                    /**
                     * @template L
                     * @template-implements Either<L, mixed>
                     */
                    final class Left implements Either {
                        /**
                         * @param L $value
                         */
                        public function __construct($value) {}
                    }

                    /**
                     * @template R
                     * @template-implements Either<mixed,R>
                     */
                    final class Right implements Either {
                        /**
                         * @param R $value
                         */
                        public function __construct($value) {}
                    }

                    class A {}
                    class B {}

                    /**
                     * @return Either<A,B>
                     */
                    function result() {
                        if (rand(0, 1)) {
                            return new Left(new A());
                        }

                        return new Right(new B());
                    }'
            ],
            'refineGenericWithInstanceof' => [
                '<?php
                    /** @template T */
                    interface Maybe {}

                    /**
                     * @template T
                     * @implements Maybe<T>
                     */
                    class Some implements Maybe {
                        /** @var T */
                        private $value;

                        /** @psalm-param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        /** @psalm-return T */
                        public function extract() { return $this->value; }
                    }

                    /**
                     * @psalm-return Maybe<int>
                     */
                    function repository(): Maybe {
                        return new Some(5);
                    }

                    $maybe = repository();

                    if ($maybe instanceof Some) {
                        $anInt = $maybe->extract();
                    }'
            ],
            'extendIterable' => [
                '<?php
                    class MyTestCase {
                        /** @return iterable<int,array<int,int>> */
                        public function provide() {
                            yield [1];
                        }
                    }'
            ],
            'extendsWithMoreTemplateParams' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Container {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }

                        /** @return static<T> */
                        public function getAnother() {
                            return clone $this;
                        }
                    }

                    class MyContainer extends Container {}

                    $a = (new MyContainer("hello"))->getAnother();',
            ],
            'staticClassCreationIndirect' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    class Collection
                    {
                        private $arr;

                        /**
                         * @param array<TKey, TValue> $arr
                         */
                        public function __construct(array $arr) {
                            $this->arr = $arr;
                        }

                        /**
                         * @template T1 as array-key
                         * @template T2
                         * @param array<T1, T2> $arr
                         * @return static<T1, T2>
                         */
                        public static function getInstance(array $arr) {
                            return new static($arr);
                        }

                        /**
                         * @param array<TKey, TValue> $arr
                         * @return static<TKey, TValue>
                         */
                        public function map(array $arr) {
                            return static::getInstance($arr);
                        }
                    }'
            ],
            'allowExtendingWithTemplatedClass' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class Foo {
                        /** @var T1 */
                        public $t;

                        /** @param T1 $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template T2
                     */
                    class Bar {
                        /** @var T2 */
                        public $t;

                        /** @param T2 $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template T3
                     * @extends Bar<Foo<T3>>
                     */
                    class BarOfFoo extends Bar {
                        /** @param T3 $t */
                        public function __construct($t) {
                            parent::__construct(new Foo($t));
                        }
                    }

                    /**
                     * @template T4
                     * @param T4 $t
                     * @return Bar<Foo<T4>>
                     */
                    function baz($t) {
                        return new BarOfFoo($t);
                    }'
            ],
            'inheritTemplateParamViaConstructorSameName' => [
                '<?php
                    class Dog {}

                    /**
                     * @template T
                     */
                    class Collection {
                        /** @var array<T> */
                        protected $arr = [];

                        /**
                          * @param array<T> $arr
                          */
                        public function __construct(array $arr) {
                            $this->arr = $arr;
                        }
                    }

                    /**
                     * @template T
                     * @template V
                     * @extends Collection<V>
                     */
                    class CollectionChild extends Collection {
                    }

                    $dogs = new CollectionChild([new Dog(), new Dog()]);',
                [
                    '$dogs' => 'CollectionChild<mixed, Dog>'
                ]
            ],
            'inheritTemplateParamViaConstructorDifferentName' => [
                '<?php
                    class Dog {}

                    /**
                     * @template T
                     */
                    class Collection {
                        /** @var array<T> */
                        protected $arr = [];

                        /**
                          * @param array<T> $arr
                          */
                        public function __construct(array $arr) {
                            $this->arr = $arr;
                        }
                    }

                    /**
                     * @template U
                     * @template V
                     * @extends Collection<V>
                     */
                    class CollectionChild extends Collection {
                    }

                    $dogs = new CollectionChild([new Dog(), new Dog()]);',
                [
                    '$dogs' => 'CollectionChild<mixed, Dog>'
                ]
            ],
            'extendsClassWithClassStringProperty' => [
                '<?php
                    class Some {}

                    /** @template T of object */
                    abstract class Y {
                        /** @var class-string<T> */
                        protected $c;
                    }

                    /**
                     * @template T of Some
                     * @extends Y<Some>
                     */
                    class Z extends Y {
                        /** @param class-string<T> $c */
                        public function __construct(string $c) {
                            $this->c = $c;
                        }
                    }'
            ],
            'useTraitReturnTypeForInheritedInterface' => [
                '<?php
                    /**
                     * @template TValue
                     * @template TNormalizedValue
                     */
                    interface Normalizer
                    {
                        /**
                         * @param TValue $v
                         * @return TNormalizedValue
                         */
                        function normalize($v);
                    }

                    /**
                     * @template TTraitValue
                     * @template TTraitNormalizedValue
                     */
                    trait NormalizerTrait
                    {
                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        function normalize($v)
                        {
                            return $this->doNormalize($v);
                        }

                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        abstract protected function doNormalize($v);
                    }

                    /** @implements Normalizer<string, string> */
                    class StringNormalizer implements Normalizer
                    {
                        /** @use NormalizerTrait<string, string> */
                        use NormalizerTrait;

                        protected function doNormalize($v): string
                        {
                            return trim($v);
                        }
                    }'
            ],
            'useTraitReturnTypeForInheritedClass' => [
                '<?php
                    /**
                     * @template TValue
                     * @template TNormalizedValue
                     */
                    abstract class Normalizer
                    {
                        /**
                         * @param TValue $v
                         * @return TNormalizedValue
                         */
                        abstract function normalize($v);
                    }

                    /**
                     * @template TTraitValue
                     * @template TTraitNormalizedValue
                     */
                    trait NormalizerTrait
                    {
                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        function normalize($v)
                        {
                            return $this->doNormalize($v);
                        }

                        /**
                         * @param TTraitValue $v
                         * @return TTraitNormalizedValue
                         */
                        abstract protected function doNormalize($v);
                    }

                    /** @extends Normalizer<string, string> */
                    class StringNormalizer extends Normalizer
                    {
                        /** @use NormalizerTrait<string, string> */
                        use NormalizerTrait;

                        protected function doNormalize($v): string
                        {
                            return trim($v);
                        }
                    }'
            ],
            'extendWithArrayTemplate' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface C {
                        /**
                         * @psalm-return C<array<int, T1>>
                         */
                        public function zip(): C;
                    }

                    /**
                     * @template T2
                     * @extends C<T2>
                     */
                    interface AC extends C {
                        /**
                         * @psalm-return AC<array<int, T2>>
                         */
                        public function zip(): C;
                    }',
            ],
            'implementsParameterisedIterator' => [
                '<?php
                    /**
                     * @implements \IteratorAggregate<int,\stdClass>
                     */
                    class SelectEntries implements \IteratorAggregate
                    {
                        public function getIterator(): SelectIterator {
                            return new SelectIterator();
                        }
                    }

                    /**
                     * @implements \Iterator<int,\stdClass>
                     * @psalm-suppress UnimplementedInterfaceMethod
                     */
                    class SelectIterator implements \Iterator
                    {
                    }'
            ],
            'extendWithExtraParam' => [
                '<?php
                    /**
                     * @template Tk of array-key
                     * @template Tv
                     */
                    interface ICollection {
                        /**
                         * @psalm-return ICollection<Tk, Tv>
                         */
                        public function slice(int $start, int $length): ICollection;
                    }

                    /**
                     * @template T
                     *
                     * @extends ICollection<int, T>
                     */
                    interface IVector extends ICollection {
                        /**
                         * @psalm-return IVector<T>
                         */
                        public function slice(int $start, int $length): ICollection;
                    }'
            ]
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
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
                'error_message' => 'ImplementedReturnTypeMismatch - src' . DIRECTORY_SEPARATOR . 'somefile.php:29:36 - The inherited return type \'A\Bar\' for',
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
                'error_message' => 'UndefinedDocblockClass',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'UndefinedDocblockClass',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'UndefinedDocblockClass',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'InvalidDocblock',
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
                'error_message' => 'MissingTemplateParam',
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
                'error_message' => 'MissingTemplateParam',
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
                'error_message' => 'InvalidTemplateParam',
            ],
            'doInheritParamTemplatedTypeSameName' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                      /**
                       * @param T $t
                       */
                      public function add($t) : void;
                    }

                    /**
                     * @template T
                     * @template-implements I<T>
                     */
                    class C implements I {
                      /** @var array<T> */
                      private $t;

                      /**
                       * @param array<T> $t
                       */
                      public function __construct(array $t) {
                        $this->t = $t;
                      }

                      /**
                       * @inheritdoc
                       */
                      public function add($t) : void {
                        $this->t[] = $t;
                      }
                    }

                    /** @param C<string> $c */
                    function foo(C $c) : void {
                        $c->add(new stdClass);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'doInheritParamTemplatedTypeDifferentTemplateNames' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface I {
                      /**
                       * @param T1 $t
                       */
                      public function add($t) : void;
                    }

                    /**
                     * @template T2
                     * @template-implements I<T2>
                     */
                    class C implements I {
                      /** @var array<T2> */
                      private $t;

                      /**
                       * @param array<T2> $t
                       */
                      public function __construct(array $t) {
                        $this->t = $t;
                      }

                      /**
                       * @inheritdoc
                       */
                      public function add($t) : void {
                        $this->t[] = $t;
                      }
                    }

                    /** @param C<string> $c */
                    function foo(C $c) : void {
                        $c->add(new stdClass);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'invalidArgumentForInheritedImplementedInterfaceMethodParam' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I1 {
                        /** @param T $t */
                        public function takeT($t) : void;
                    }

                    /**
                     * @template T as array-key
                     * @template-extends I1<T>
                     */
                    interface I2 extends I1 {}

                    /**
                     * @template T as array-key
                     * @template-implements I2<T>
                     */
                    class C implements I2 {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                        public function takeT($t) : void {}
                    }

                    /** @param C<string> $c */
                    function bar(C $c) : void {
                        $c->takeT(new stdClass);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'implementsAndExtendsWithoutTemplate' => [
                '<?php
                    /**
                     * @template E
                     */
                    interface Collection
                    {
                        /**
                         * @return array<E>
                         */
                        function toArray();
                    }

                    /**
                     * @implements Collection<int>
                     */
                    class IntCollection implements Collection
                    {
                        function toArray() {
                            return ["foo"];
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'implementsAndExtendsWithTemplateReturningInvalid' => [
                '<?php
                    /**
                     * @template TReal
                     */
                    interface Collection
                    {
                        /**
                         * @return array<TReal>
                         */
                        function toArray();
                    }

                    /**
                     * @template TDummy
                     * @implements Collection<int>
                     */
                    class IntCollection implements Collection
                    {
                        /** @param TDummy $t */
                        public function __construct($t) {

                        }

                        public function toArray() {
                            return ["foo"];
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'implementsChildClassWithNonExtendedTemplate' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Base {
                        /** @var T */
                        private $t;

                        /** @param T $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }

                        /**
                         * @param T $x
                         * @return T
                         */
                        function example($x) {
                            return $x;
                        }
                    }

                    class Child extends Base {
                        function example($x) {
                            return $x;
                        }
                    }

                    /** @param Child $c */
                    function bar(Child $c) : void {
                        ord($c->example("boris"));
                    }',
                'error_message' => 'MixedArgument - src/somefile.php:31:29 - Argument 1 of ord cannot be mixed, expecting string',
            ],
            'preventWiderParentType' => [
                '<?php
                    /**
                     * @template T
                     */
                    abstract class Stringer {
                        /**
                         * @param T $t
                         */
                        public function getString($t, object $o = null) : string {
                            return "hello";
                        }
                    }

                    class A {}

                    /**
                     * @template-extends Stringer<A>
                     */
                    class AStringer extends Stringer {
                        public function getString($t, object $o = null) : string {
                            if ($o) {
                                return parent::getString($o);
                            }

                            return "a";
                        }
                    }',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'invalidExtendsAnnotation' => [
                '<?php
                    /**
                    * @template-extends
                    */
                    class Foo extends DateTimeImmutable {}',
                'error_message' => 'InvalidDocblock'
            ],
            'invalidReturnParamType' => [
                '<?php
                    /**
                     * @template L
                     * @template R
                     */
                    interface Either {}

                    /**
                     * @template L
                     * @template-implements Either<L,mixed>
                     */
                    class Left implements Either {
                        /** @param L $value */
                        public function __construct($value) { }
                    }

                    class A {}
                    class B {}

                    /** @return Either<A,B> */
                    function result(): Either {
                        return new Left(new B());
                    }',
                'error_message' => 'InvalidReturnStatement'
            ],
            'possiblyNullReferenceOnTraitDefinedMethod' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    trait T1 {
                        /**
                         * @var array<TKey, TValue>
                         */
                        protected $mocks = [];

                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         * @psalm-suppress LessSpecificImplementedReturnType
                         * @psalm-suppress ImplementedParamTypeMismatch
                         */
                        public function offsetGet($offset) {
                            return $this->mocks[$offset] ?? null;
                        }
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    interface Arr {
                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         */
                        public function offsetGet($offset);
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @implements Arr<TKey, TValue>
                     */
                    class C implements Arr {
                        /** @use T1<TKey, TValue> */
                        use T1;

                        /**
                         * @param TKey $offset
                         * @psalm-suppress MixedMethodCall
                         */
                        public function foo($offset) : void {
                            $this->offsetGet($offset)->bar();
                        }
                    }',
                'error_message' => 'PossiblyNullReference'
            ],
            'possiblyNullReferenceOnTraitDefinedMethodExtended' => [
                '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    trait T1 {
                        /**
                         * @var array<TKey, TValue>
                         */
                        protected $mocks = [];

                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         * @psalm-suppress LessSpecificImplementedReturnType
                         * @psalm-suppress ImplementedParamTypeMismatch
                         */
                        public function offsetGet($offset) {
                            return $this->mocks[$offset] ?? null;
                        }
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     */
                    interface Arr {
                        /**
                         * @param TKey $offset
                         * @return TValue|null
                         */
                        public function offsetGet($offset);
                    }

                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @implements Arr<TKey, TValue>
                     */
                    class C implements Arr {
                        /** @use T1<TKey, TValue> */
                        use T1;
                    }

                    class D extends C {
                        /**
                         * @param mixed $offset
                         * @psalm-suppress MixedArgument
                         */
                        public function foo($offset) : void {
                            $this->offsetGet($offset)->bar();
                        }
                    }',
                'error_message' => 'MixedMethodCall'
            ],
            'preventExtendingWithTemplatedClassWithExplicitTypeGiven' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class Foo {
                        /** @var T1 */
                        public $t;

                        /** @param T1 $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template T2
                     */
                    class Bar {
                        /** @var T2 */
                        public $t;

                        /** @param T2 $t */
                        public function __construct($t) {
                            $this->t = $t;
                        }
                    }

                    /**
                     * @template T3
                     * @extends Bar<Foo<T3>>
                     */
                    class BarOfFoo extends Bar {
                        /** @param T3 $t */
                        public function __construct($t) {
                            parent::__construct(new Foo($t));
                        }
                    }

                    /**
                     * @template T4
                     * @param T4 $t
                     * @return Bar<Foo<T4>>
                     */
                    function baz($t) {
                        return new BarOfFoo("hello");
                    }',
                'error_message' => 'InvalidReturnStatement'
            ],
            'noCrashForTooManyTemplateParams' => [
                '<?php
                    interface InterfaceA {}

                    class ImplemX implements InterfaceA {}

                    interface DoStuff {
                        public function stuff(InterfaceA $object): void;
                    }

                    /**
                     * @implements DoStuff<ImplemX>
                     */
                    class DoStuffX implements DoStuff {
                        public function stuff(InterfaceA $object): void {}
                    }

                    final class Foo {
                        /**
                         * @template A of InterfaceA
                         * @psalm-param DoStuff<A> $stuff
                         */
                        public function __construct(DoStuff $stuff) {}
                    }

                    new Foo(new DoStuffX());',
                'error_message' => 'InvalidArgument'
            ]
        ];
    }
}
