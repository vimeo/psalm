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
                     * @implements Functor<T>
                     */
                    final class Box implements Functor
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
                    /**
                     * @template T
                     * @psalm-consistent-constructor
                     */
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
                    /**
                     * @template T
                     * @psalm-consistent-constructor
                     */
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
                    /**
                     * @template T
                     * @psalm-consistent-constructor
                     */
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
                     * @psalm-consistent-constructor
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
                     * @psalm-consistent-constructor
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

                        /**
                         * @psalm-suppress InvalidReturnType
                         */
                        public function getIterator()
                        {
                            /**
                             * @psalm-suppress InvalidReturnStatement
                             */
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
            'concreteGetIterator' => [
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
                        public function getIterator();
                    }

                    /**
                     * @template-implements Collection<string>
                     */
                    class Set implements Collection {
                        public function forEach(callable $action): void {
                            $i = $this->getIterator();
                            foreach ($this as $bar) {
                                $action($bar);
                            }
                        }

                        public function getIterator() {
                            return new ArrayIterator(["hello"]);
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
                     * @psalm-suppress MixedMethodCall
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
                     * @psalm-consistent-constructor
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
            ],
            'concreteDefinesNoSignatureTypes' => [
                '<?php
                    interface IView {}

                    class ConcreteView implements IView {}

                    /**
                     * @template-covariant TView as IView
                     */
                    interface IViewCreator {
                        /** @return TView */
                        public function view();
                    }

                    /**
                     * @template-covariant TView as IView
                     * @implements IViewCreator<TView>
                     */
                    abstract class AbstractViewCreator implements IViewCreator {
                        public function view() {
                            return $this->doView();
                        }

                        /** @return TView */
                        abstract protected function doView();
                    }

                    /**
                     * @extends AbstractViewCreator<ConcreteView>
                     */
                    class ConcreteViewerCreator extends AbstractViewCreator {
                        protected function doView() {
                            return new ConcreteView;
                        }
                    }'
            ],
            'concreteDefinesSignatureTypes' => [
                '<?php
                    interface IView {}

                    class ConcreteView implements IView {}

                    /**
                     * @template-covariant TView as IView
                     */
                    interface IViewCreator {
                        /** @return TView */
                        public function view() : IView;
                    }

                    /**
                     * @template-covariant TView as IView
                     * @implements IViewCreator<TView>
                     */
                    abstract class AbstractViewCreator implements IViewCreator {
                        public function view() : IView {
                            return $this->doView();
                        }

                        /** @return TView */
                        abstract protected function doView();
                    }

                    /**
                     * @extends AbstractViewCreator<ConcreteView>
                     */
                    class ConcreteViewerCreator extends AbstractViewCreator {
                        protected function doView() {
                            return new ConcreteView;
                        }
                    }'
            ],
            'allowStaticMethodClassTemplates' => [
                '<?php
                    namespace A;

                    class DeliveryTimeAggregated {}

                    /**
                     * @template T of object
                     */
                    interface ReplayMessageInterface
                    {
                        /**
                         * @return class-string<T>
                         */
                        public static function messageName(): string;
                    }

                    /**
                     * @template-implements ReplayMessageInterface<DeliveryTimeAggregated>
                     */
                    class ReplayDeliveryTimeAggregated implements ReplayMessageInterface
                    {
                        /**
                         * @return class-string<DeliveryTimeAggregated>
                         */
                        public static function messageName(): string
                        {
                            return DeliveryTimeAggregated::class;
                        }
                    }'
            ],
            'allowExplicitMethodClassTemplateReturn' => [
                '<?php
                    /**
                     * @template T of object
                     */
                    interface I
                    {
                        /**
                         * @return class-string<T>
                         */
                        public function m(): string;
                    }

                    /**
                     * @template T2 of object
                     * @template-implements I<T2>
                     */
                    class C implements I
                    {
                        /** @var T2 */
                        private object $o;

                        /** @param T2 $o */
                        public function __construct(object $o) {
                            $this->o = $o;
                        }

                        /**
                         * @return class-string<T2>
                         */
                        public function m(): string {
                            return get_class($this->o);
                        }
                    }',
            ],
            'templateInheritanceWithParentTemplateTypes' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class A {
                        /**
                         * @template T2
                         * @param class-string<T2> $t
                         * @return ?T2
                         * @psalm-suppress MixedMethodCall
                         */
                        public function get($t) {
                            return new $t;
                        }
                    }

                    class AChild extends A {
                        /**
                         * @template T3
                         * @param class-string<T3> $t
                         * @return ?T3
                         * @psalm-suppress MixedMethodCall
                         */
                        public function get($t) {
                            return new $t;
                        }
                    }'
            ],
            'extendsInheritingReturnType' => [
                '<?php
                    /**
                     * @template T1
                     */
                    class Container {
                        /**
                         * @return T1
                         * @psalm-suppress InvalidReturnStatement
                         * @psalm-suppress InvalidReturnType
                         */
                        public function get(int $key) { return new stdClass(); }
                    }

                    /**
                     * @template T2
                     * @inherits Container<T2>
                     */
                    class ContainerSubclass extends Container {
                        /**
                         * @psalm-suppress InvalidReturnType
                         */
                        public function get(int $key) {}
                    }

                    class MyTestContainerUsage {
                        /** @var ContainerSubclass<stdClass> */
                        private $container;

                        /** @param ContainerSubclass<stdClass> $container */
                        public function __construct(ContainerSubclass $container) {
                            $this->container = $container;
                        }

                        public function modify() : void {
                            $this->container->get(1)->foo = 2;
                        }
                    }'
            ],
            'templateYieldFrom' => [
                '<?php
                    /**
                     * @extends \IteratorAggregate<int, string>
                     */
                    interface IStringList extends \IteratorAggregate
                    {
                        /** @return \Iterator<int, string> */
                        public function getIterator(): \Iterator;
                    }

                    class StringListDecorator implements IStringList
                    {
                        private IStringList $decorated;

                        public function __construct(IStringList $decorated) {
                            $this->decorated = $decorated;
                        }

                        public function getIterator(): \Iterator
                        {
                            yield from $this->decorated;
                        }
                    }'
            ],
            'extendsTemplatedInterface' => [
                '<?php
                    class Animal {}

                    /**
                     * @template DC1 of Animal
                     */
                    interface IStroker {
                        /**
                         * @psalm-param DC1 $animal
                         */
                        public function stroke(Animal $animal): void;
                    }

                    /**
                     * @template DC2 of Animal
                     * @template-extends IStroker<DC2>
                     */
                    interface IStroker2 extends IStroker {}

                    class Dog extends Animal {}

                    /**
                     * @implements IStroker2<Dog>
                     */
                    class DogStroker implements IStroker2 {
                        public function stroke(Animal $animal): void {
                            $this->doDeletePerson($animal);
                        }

                        private function doDeletePerson(Dog $animal): void {}
                    }'
            ],
            'extendsTemplatedClass' => [
                '<?php
                    class Animal {}

                    /**
                     * @template DC1 of Animal
                     */
                    class IStroker {
                        /**
                         * @psalm-param DC1 $animal
                         */
                        public function stroke(Animal $animal): void {}
                    }

                    /**
                     * @template DC2 of Animal
                     * @template-extends IStroker<DC2>
                     */
                    class IStroker2 extends IStroker {}

                    class Dog extends Animal {}

                    /**
                     * @extends IStroker2<Dog>
                     */
                    class DogStroker extends IStroker2 {
                        public function stroke(Animal $animal): void {
                            $this->doDeletePerson($animal);
                        }

                        private function doDeletePerson(Dog $animal): void {}
                    }'
            ],
            'sameNameTemplateFromParent' => [
                '<?php
                    /**
                     * @psalm-template T
                     */
                    interface C {
                        /**
                         * @psalm-param T $p
                         * @psalm-return C<T>
                         */
                        public function filter($p) : self;
                    }

                    /**
                     * @psalm-template T
                     * @template-implements C<T>
                     */
                    abstract class AC implements C {
                        /**
                         * @psalm-var C<T>
                         */
                        protected $c;

                        /**
                         * @param T $p
                         */
                        public function filter($p) : C {
                            return $this->c->filter($p);
                        }
                    }'
            ],
            'implementsTemplatedTwice' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface A {
                        /** @return T1 */
                        public function get();
                    }

                    /**
                     * @template T2
                     * @extends A<T2>
                     */
                    interface B extends A {}

                    /**
                     * @template T3
                     * @implements B<T3>
                     */
                    class C implements B {
                        /** @var T3 */
                        private $val;

                        /**
                         * @psalm-param T3 $val
                         */
                        public function __construct($val) {
                            $this->val = $val;
                        }

                        public function get() {
                            return $this->val;
                        }
                    }

                    $foo = (new C("foo"))->get();',
                [
                    '$foo' => 'string',
                ]
            ],
            'extendsWithJustParentConstructor' => [
                '<?php
                    class Subject{}

                    /**
                     * @template U of Subject
                     * @template-extends EventAbstract<U>
                     */
                    class EventInstance extends EventAbstract
                    {
                        /**
                         * @template S of Subject
                         * @param S $subject
                         * @return self<S>
                         */
                        public static function createInstance(Subject $subject) : self
                        {
                            return new self($subject);
                        }
                    }

                    /**
                     * @template T of Subject
                     */
                    abstract class EventAbstract
                    {
                        /** @var T */
                        protected $subject;

                        /**
                         * @param T $subject
                         */
                        public function __construct(Subject $subject)
                        {
                            $this->subject = $subject;
                        }
                    }'
            ],
            'annotationDefinedInInheritedInterface' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface X {
                        /**
                         * @param T1 $x
                         * @return T1
                         */
                        public function boo($x);
                    }

                    /**
                     * @template T2
                     * @extends X<T2>
                     */
                    interface Y extends X {}

                    /**
                     * @template T3
                     * @implements Y<T3>
                     */
                    class A implements Y {
                        public function boo($x) {
                            return $x;
                        }
                    }

                    function foo(A $a) : void {
                        $a->boo("boo");
                    }'
            ],
            'allowPropertyCoercionExtendedParam' => [
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
                    interface Collection
                    {
                        /**
                         * @param Closure $p
                         *
                         * @return Collection A
                         *
                         * @psalm-param Closure(T=):bool $p
                         * @psalm-return Collection<TKey, T>
                         */
                        public function filter(Closure $p);
                    }

                    /**
                     * @psalm-template TKey of array-key
                     * @psalm-template T
                     * @template-implements Collection<TKey,T>
                     */
                    class ArrayCollection implements Collection
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
                         * {@inheritDoc}
                         *
                         * @return static
                         */
                        public function filter(Closure $p)
                        {
                            return $this;
                        }
                    }'
            ],
            'listTemplating' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface X {
                        /**
                         * @param list<T> $x
                         * @return T
                         */
                        public function boo($x);
                    }

                    /**
                     * @template T
                     * @implements X<T>
                     */
                    class A implements X {
                        /**
                         * @return T
                         */
                        public function boo($x) {
                            return $x[0];
                        }
                    }'
            ],
            'sameNamedTemplateDefinedInParentFunction' => [
                '<?php
                    /**
                     * @template T2
                     */
                    class Query {
                        /** @var T2 **/
                        private $value;

                        /**
                         * @param T2 $value
                         */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    interface Temporal {
                        /**
                         * @template T
                         * @param Query<T> $query
                         */
                        public function execute(Query $query) : void;
                    }

                    /**
                     * @template T
                     */
                    class Result implements Temporal {
                        /** @var T **/
                        private $value;

                        /**
                         * @param T $value
                         */
                        public function __construct($value) {
                            $this->value = $value;
                        }

                        public function execute(Query $query) : void {}
                    }

                    /**
                     * @param  Result<string> $result
                     * @param  Query<string> $query
                     */
                    function takesArgs(Result $result, Query $query) : void {
                        $result->execute($query);
                    }'
            ],
            'respectExtendsAnnotationWhenVerifyingFinalChildReturnType' => [
                '<?php
                    /**
                     * @template T of Enum
                     */
                    class EnumSet
                    {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type)
                        {
                            $this->type = $type;
                        }
                    }

                    abstract class Enum {
                        /**
                         * @return EnumSet<static>
                         */
                        public static function all()
                        {
                            return new EnumSet(static::class);
                        }
                    }

                    /**
                     * @extends EnumSet<CustomEnum>
                     */
                    final class CustomEnumSet extends EnumSet {
                        public function __construct()
                        {
                            parent::__construct(CustomEnum::class);
                        }
                    }

                    final class CustomEnum extends Enum
                    {
                        /**
                         * @return CustomEnumSet
                         */
                        public static function all()
                        {
                            return new CustomEnumSet();
                        }
                    }'
            ],
            'allowValidChildReturnType' => [
                '<?php
                    /**
                     * @template T of Enum
                     */
                    class EnumSet
                    {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type)
                        {
                            $this->type = $type;
                        }
                    }

                    abstract class Enum {
                        /**
                         * @return EnumSet<static>
                         */
                        public static function all()
                        {
                            return new EnumSet(static::class);
                        }
                    }

                    /**
                     * @extends EnumSet<CustomEnum>
                     */
                    final class CustomEnumSet extends EnumSet {
                        public function __construct()
                        {
                            parent::__construct(CustomEnum::class);
                        }
                    }

                    class CustomEnum extends Enum
                    {
                        public static function all()
                        {
                            return new EnumSet(static::class);
                        }
                    }',
            ],
            'extendsWithTemplatedProperty' => [
                '<?php
                    /**
                     * @template I as object
                     */
                    class Foo {
                        /** @var I */
                        protected $collection;

                        /** @param I $collection */
                        public function __construct($collection) {
                            $this->collection = $collection;
                        }
                    }

                    /**
                     * @template I2 as object
                     *
                     * @extends Foo<I2>
                     */
                    class FooChild extends Foo {
                        /** @return I2 */
                        public function getCollection() {
                            return $this->collection;
                        }
                    }'
            ],
            'setInheritedTemplatedPropertyOutsideClass' => [
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

                    /** @extends Watcher<int> */
                    class IntWatcher extends Watcher {}

                    $watcher = new IntWatcher(0);
                    $watcher->value = 10;'
            ],
            'setRetemplatedPropertyOutsideClass' => [
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

                    /**
                     * @template T as scalar
                     * @extends Watcher<T>
                     */
                    class Watcher2 extends Watcher {}

                    /** @psalm-var Watcher2<int> $watcher */
                    $watcher = new Watcher2(0);
                    $watcher->value = 10;'
            ],
            'argInSameLocationShouldHaveConvertedParams' => [
                '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /**
                         * @param T $argument
                         */
                        public function i($argument): void;
                    }

                    /**
                     * @implements I<int>
                     */
                    class X implements I {
                        public function i($argument): void {
                            echo sprintf("%d", $argument);
                        }
                    }

                    /**
                     * @implements I<int>
                     */
                    class XWithChangedArgumentName implements I {
                        public function i($changedArgumentName): void {
                            echo sprintf("%d", $changedArgumentName);
                        }
                    }'
            ],
            'acceptTemplatedObjectAsStaticParam' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    abstract class Id
                    {
                        protected string $id;

                        final protected function __construct(string $id)
                        {
                            $this->id = $id;
                        }

                        /**
                         * @param static $id
                         */
                        final public function equals(self $id): bool
                        {
                            return $this->id === $id->id;
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
                         * @psalm-param T $id
                         */
                        public function contains(Id $id): bool
                        {
                            foreach ($this->ids as $oneId) {
                                if ($oneId->equals($id)) {
                                    return true;
                                }
                            }

                            return false;
                        }
                    }'
            ],
            'nestedTemplateExtends' => [
                '<?php
                    namespace Foo;

                    interface IBaseViewData {}

                    /**
                     * @template TViewData
                     */
                    class BaseModel {}

                    /**
                     * @template TViewData as IBaseViewData
                     * @template TModel as BaseModel<TViewData>
                     */
                    abstract class BaseRepository {}

                    class StudentViewData implements IBaseViewData {}
                    class TeacherViewData implements IBaseViewData {}

                    /** @extends BaseModel<StudentViewData> */
                    class StudentModel extends BaseModel {}

                    /** @extends BaseModel<TeacherViewData> */
                    class TeacherModel extends BaseModel {}

                    /** @extends BaseRepository<StudentViewData, StudentModel> */
                    class StudentRepository extends BaseRepository {}

                    /** @extends BaseRepository<TeacherViewData, TeacherModel> */
                    class TeacherRepository extends BaseRepository {}'
            ],
            'templateInheritedPropertyCorrectly' => [
                '<?php
                    /**
                     * @template TKey1
                     * @template TValue1
                     */
                    class Pair
                    {
                        /** @psalm-var TKey1 */
                        public $one;

                        /** @psalm-var TValue1 */
                        public $two;

                        /**
                         * @psalm-param TKey1 $key
                         * @psalm-param TValue1 $value
                         */
                        public function __construct($key, $value) {
                            $this->one = $key;
                            $this->two = $value;
                        }
                    }

                    /**
                     * @template TValue2
                     * @extends Pair<string, TValue2>
                     */
                    class StringKeyedPair extends Pair {
                        /**
                         * @param TValue2 $value
                         */
                        public function __construct(string $key, $value) {
                            parent::__construct($key, $value);
                        }
                    }

                    $pair = new StringKeyedPair("somekey", 250);
                    $a = $pair->two;
                    $b = $pair->one;',
                [
                    '$pair' => 'StringKeyedPair<int>',
                    '$a' => 'int',
                    '$b' => 'string',
                ]
            ],
            'templateInheritedPropertySameName' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class Pair
                    {
                        /** @psalm-var TKey */
                        public $one;

                        /** @psalm-var TValue */
                        public $two;

                        /**
                         * @psalm-param TKey $key
                         * @psalm-param TValue $value
                         */
                        public function __construct($key, $value) {
                            $this->one = $key;
                            $this->two = $value;
                        }
                    }

                    /**
                     * @template TValue
                     * @extends Pair<string, TValue>
                     */
                    class StringKeyedPair extends Pair {
                        /**
                         * @param TValue $value
                         */
                        public function __construct(string $key, $value) {
                            parent::__construct($key, $value);
                        }
                    }

                    $pair = new StringKeyedPair("somekey", 250);
                    $a = $pair->two;
                    $b = $pair->one;',
                [
                    '$pair' => 'StringKeyedPair<int>',
                    '$a' => 'int',
                    '$b' => 'string',
                ]
            ],
            'templateInheritedPropertySameNameFlipped' => [
                '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class Pair
                    {
                        /** @psalm-var TKey */
                        public $one;

                        /** @psalm-var TValue */
                        public $two;

                        /**
                         * @psalm-param TKey $key
                         * @psalm-param TValue $value
                         */
                        public function __construct($key, $value) {
                            $this->one = $key;
                            $this->two = $value;
                        }
                    }

                    /**
                     * @template TValue
                     * @extends Pair<TValue, string>
                     */
                    class StringKeyedPair extends Pair {
                        /**
                         * @param TValue $value
                         */
                        public function __construct(string $key, $value) {
                            parent::__construct($value, $key);
                        }
                    }

                    $pair = new StringKeyedPair("somekey", 250);
                    $a = $pair->one;
                    $b = $pair->two;',
                [
                    '$pair' => 'StringKeyedPair<int>',
                    '$a' => 'int',
                    '$b' => 'string',
                ]
            ],
            'implementExtendedInterfaceWithMethodOwnTemplateParams' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface IFoo {
                        /**
                         * @template T2
                         * @psalm-param T2 $f
                         * @psalm-return self<T2>
                         */
                        public static function doFoo($f): self;
                    }

                    /**
                     * @template T3
                     * @extends IFoo<T3>
                     */
                    interface IFooChild extends IFoo {}

                    /**
                     * @template T5
                     * @implements IFooChild<T5>
                     */
                    class ConcreteFooChild implements IFooChild {
                        /** @var T5 */
                        private $baz;

                        /** @param T5 $baz */
                        public function __construct($baz) {
                            $this->baz = $baz;
                        }

                        /**
                         * @template T6
                         * @psalm-param T6 $f
                         * @psalm-return ConcreteFooChild<T6>
                         */
                        public static function doFoo($f): self {
                            $r = new self($f);
                            return $r;
                        }
                    }',
                [],
                [],
                '7.4'
            ],
            'implementInterfaceWithMethodOwnTemplateParams' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface IFoo {
                        /**
                         * @template T2
                         * @psalm-param T2 $f
                         * @psalm-return self<T2>
                         */
                        public static function doFoo($f): self;
                    }


                    /**
                     * @template T5
                     * @implements IFoo<T5>
                     */
                    class ConcreteFooChild implements IFoo {
                        /** @var T5 */
                        private $baz;

                        /** @param T5 $baz */
                        public function __construct($baz) {
                            $this->baz = $baz;
                        }

                        /**
                         * @template T6
                         * @psalm-param T6 $f
                         * @psalm-return ConcreteFooChild<T6>
                         */
                        public static function doFoo($f): self
                        {
                            $r = new self($f);
                            return $r;
                        }
                    }',
                [],
                [],
                '7.4'
            ],
            'inheritTraitPropertyObjectLike' => [
                '<?php
                    /** @template TValue */
                    trait A {
                        /** @psalm-var array{TValue} */
                        private $foo;

                        /** @psalm-param array{TValue} $foo */
                        public function __construct(array $foo)
                        {
                            $this->foo = $foo;
                        }
                    }

                    /** @template TValue */
                    class B {
                        /** @use A<TValue> */
                        use A;
                    }'
            ],
            'inheritTraitPropertyArray' => [
                '<?php
                    /** @template TValue */
                    trait A {
                        /** @psalm-var array<TValue> */
                        private $foo;

                        /** @psalm-param array<TValue> $foo */
                        public function __construct(array $foo)
                        {
                            $this->foo = $foo;
                        }
                    }

                    /** @template TValue */
                    class B {
                        /** @use A<TValue> */
                        use A;
                    }'
            ],
            'staticShouldBeBoundInCall' => [
                '<?php
                    /**
                     * @template TVehicle of Vehicle
                     */
                    class VehicleCollection {
                        /**
                         * @param class-string<TVehicle> $item
                         */
                        public function __construct(string $item) {}
                    }

                    abstract class Vehicle {
                        /**
                         * @return VehicleCollection<static>
                         */
                        public static function all() {
                            return new VehicleCollection(static::class);
                        }
                    }

                    class Car extends Vehicle {}

                    class CarRepository {
                        /**
                        * @return VehicleCollection<Car>
                        */
                        public function getAllCars(): VehicleCollection {
                            return Car::all();
                        }
                    }'
            ],
            'templatedParameterIsNotMoreSpecific' => [
                '<?php
                    interface I {
                        /**
                         * @param bool $b
                         */
                        public function foo($b): bool;
                    }

                    class T implements I
                    {
                        /**
                         * @template TBool as bool
                         * @param TBool $b
                         *
                         * @psalm-return TBool
                         */
                        public function foo($b): bool {
                            return $b;
                        }
                    }'
            ],
            'finalOverridesStatic' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Collection {
                        /**
                         * @param T $item
                         */
                        public function __construct($item) {}
                    }

                    /**
                     * @psalm-consistent-constructor
                     */
                    abstract class Food {
                        /**
                         * @return Collection<static>
                         */
                        public function getTypes() {
                            return new Collection(new static);
                        }
                    }

                    final class Cheese extends Food {}

                    /**
                     * @return Collection<Cheese>
                     */
                    function test(Cheese $cheese): Collection {
                        return $cheese->getTypes();
                    }'
            ],
            'unwrapExtendedTypeWhileInferring' => [
                '<?php
                    /** @template T1 */
                    interface I {}

                    /** @template T2 */
                    interface J {}

                    /**
                     * @template T3
                     * @template-implements I<J<T3>>
                     */
                    final class IC implements I {
                        /** @var T3 */
                        public $var;

                        /** @param T3 $var */
                        public function __construct($var) {
                            $this->var = $var;
                        }
                    }

                    /** @template T4 */
                    final class Container
                    {
                        /** @var I<T4> $var */
                        public I $var;

                        /** @param I<T4> $var */
                        public function __construct(I $var) {
                            $this->var = $var;
                        }
                    }

                    final class Obj {}

                    final class B {
                        /** @return Container<J<int>> */
                        public function foo(int $i): Container
                        {
                            $ic = new IC($i);

                            $container = new Container($ic);

                            return $container;
                        }
                    }'
            ],
            'extendIteratorIterator' => [
                '<?php
                    /**
                     * @template-covariant TKey
                     * @template-covariant TValue
                     *
                     * @template-extends IteratorIterator<TKey, TValue, Traversable<TKey, TValue>>
                     */
                    abstract class MyFilterIterator extends IteratorIterator {
                         /** @return bool */
                         public abstract function accept () {}
                    }'
            ],
            'extendedIntoIterable' => [
                '<?php
                    interface ISubject {}

                    /**
                     * @extends \IteratorAggregate<int, ISubject>
                     */
                    interface SubjectCollection extends \IteratorAggregate
                    {
                        /**
                         * @return \Iterator<int, ISubject>
                         */
                        public function getIterator(): \Iterator;
                    }

                    /** @param iterable<int, ISubject> $_ */
                    function takesSubjects(iterable $_): void {}

                    function givesSubjects(SubjectCollection $subjects): void {
                        takesSubjects($subjects);
                    }'
            ],
            'implementMixedReturnNull' => [
                '<?php
                    /** @template T */
                    interface Templated {
                        /** @return T */
                        public function foo();
                    }

                    class Concrete implements Templated {
                        private array $t;

                        public function __construct(array $t) {
                            $this->t = $t;
                        }

                        public function foo() {
                            if (rand(0, 1)) {
                                return null;
                            }

                            return $this->t;
                        }
                    }'
            ],
            'applyTemplatedValueInTraitProperty' => [
                '<?php
                    /** @template T */
                    trait ValueTrait {
                        /** @psalm-param T $value */
                        public function setValue($value): void {
                            $this->value = $value;
                        }
                    }

                    class C {
                        /** @use ValueTrait<string> */
                        use ValueTrait;

                        /** @var string */
                        private $value;

                        public function __construct(string $value) {
                            $this->value = $value;
                        }
                    }'
            ],
            'classStringTemplatedExtends' => [
                '<?php
                    /** @template T */
                    interface CrudRequest {}

                    /** @implements CrudRequest<string> */
                    class StringRequest implements CrudRequest {}

                    /** @template T */
                    interface CrudNew {
                        /** @param class-string<CrudRequest<T>> $requestClass */
                        public function handle(string $requestClass): void;
                    }

                    class StringNew {
                        /** @param CrudNew<string> $crudNew */
                        public function foo($crudNew): void {
                            $crudNew->handle(StringRequest::class);
                        }
                    }'
            ],
            'extendTemplateTypeInParamAsType' => [
                '<?php
                    /**
                     * @template TKey as object
                     * @template-implements Operation<TKey>
                     */
                    final class Apply implements Operation
                    {
                        /**
                         * @return \Closure(array<TKey>): void
                         */
                        public function i(): Closure
                        {
                            return
                                /**
                                 * @psalm-param array<TKey> $collection
                                 */
                                static function (array $collection): void{};
                        }
                    }

                    /**
                     * @template TKey as object
                     */
                    interface Operation
                    {
                        /**
                         * @psalm-return \Closure(array<TKey>): void
                         */
                        public function i(): Closure;
                    }'
            ],
            'traitSelfAsParam' => [
                '<?php
                    trait InstancePool {
                        /**
                         * @template T as self
                         * @param callable():?T $callback
                         * @return ?T
                         */
                        public static function getInstance(callable $callback)
                        {
                            return $callback();
                        }
                    }

                    class Foo
                    {
                        use InstancePool;
                    }

                    class Bar
                    {
                        public function a(): void
                        {
                            Foo::getInstance(function () {
                                return new Foo();
                            });
                        }
                    }'
            ],
            'extendsWithArraySameObject' => [
                '<?php
                    /**
                     * @template Tv
                     */
                    interface C1 {
                        /**
                         * @return C1<array<int, Tv>>
                         */
                        public function zip(): C1;
                    }

                    /**
                     * @template Tv
                     * @extends C1<Tv>
                     */
                    interface C2 extends C1 {
                        /**
                         * @return C2<array<int, Tv>>
                         */
                        public function zip(): C2;
                    }',
                [],
                [],
                '7.4'
            ],
            'extendsWithArrayDifferentObject' => [
                '<?php
                    /**
                     * @template Tv
                     */
                    interface C1 {
                        /**
                         * @return D1<array<int, Tv>>
                         */
                        public function zip(): D1;
                    }

                    /**
                     * @template Tv
                     * @extends C1<Tv>
                     */
                    interface C2 extends C1 {
                        /**
                         * @return D2<array<int, Tv>>
                         */
                        public function zip(): D2;
                    }

                    /**
                     * @template Tv
                     */
                    interface D1 {}

                    /**
                     * @template Tv
                     * @extends D1<Tv>
                     */
                    interface D2 extends D1 {}',
                [],
                [],
                '7.4'
            ],
            'allowNestedInterfaceDefinitions' => [
                '<?php
                    class A {}

                    /** @template T as object */
                    interface Container {
                        /** @return T */
                        public function get();
                    }

                    /** @extends Container<A> */
                    interface AContainer extends Container {
                        public function get(): A;
                    }

                    interface AContainer2 extends AContainer {}

                    class ConcreteAContainer implements AContainer2 {
                        public function get(): A {
                            return new A();
                        }
                    }'
            ],
            'paramTypeInheritedWithTemplate' => [
                '<?php
                    /**
                     * @template T1
                     */
                    interface Container {}

                    /**
                     * @template T2
                     */
                    abstract class SimpleClass {
                        /**
                         * @psalm-param T2 $param
                         */
                        abstract public function foo($param): void;
                    }

                    /**
                     * @template T3
                     *
                     * @extends SimpleClass<Container<T3>>
                     */
                    abstract class ContainerClass extends SimpleClass {
                        /**
                         * @psalm-param Container<T3> $param
                         */
                        abstract public function foo($param): void;
                    }

                    /**
                     * @extends ContainerClass<int>
                     */
                    abstract class Complex extends ContainerClass {
                        /**
                         * @psalm-param Container<int> $param
                         */
                        abstract public function foo($param): void;
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
                'error_message' => 'InvalidReturnStatement',
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
                'error_message' => 'InvalidReturnStatement',
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
                'error_message' => 'MixedArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:29 - Argument 1 of ord cannot be mixed, expecting string',
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
            ],
            'concreteDefinesSignatureTypesDifferent' => [
                '<?php
                    interface IView {}

                    class ConcreteView implements IView {}
                    class OtherConcreteView implements IView {}

                    /**
                     * @template-covariant TView as IView
                     */
                    interface IViewCreator {
                        /** @return TView */
                        public function view() : IView;
                    }

                    /**
                     * @template-covariant TView as IView
                     * @implements IViewCreator<TView>
                     */
                    abstract class AbstractViewCreator implements IViewCreator {
                        public function view() : IView {
                            return $this->doView();
                        }

                        /** @return TView */
                        abstract protected function doView();
                    }

                    /**
                     * @extends AbstractViewCreator<ConcreteView>
                     */
                    class ConcreteViewerCreator extends AbstractViewCreator {
                        protected function doView() {
                            return new OtherConcreteView;
                        }
                    }',
                'error_message' => 'InvalidReturnStatement'
            ],
            'preventExplicitMethodClassTemplateReturn' => [
                '<?php
                    /**
                     * @template T of object
                     */
                    interface I
                    {
                        /**
                         * @return class-string<T>
                         */
                        public function m(): string;
                    }

                    /**
                     * @template T2 of object
                     * @template-implements I<T2>
                     */
                    class C implements I
                    {
                        /** @var T2 */
                        private object $o;

                        /** @param T2 $o */
                        public function __construct(object $o) {
                            $this->o = $o;
                        }

                        /**
                         * @return class-string<T2>
                         */
                        public function m(): string {
                            return static::class;
                        }
                    }',
                'error_message' => 'LessSpecificReturnStatement'
            ],
            'preventImplicitMethodClassTemplateReturn' => [
                '<?php
                    /**
                     * @template T of object
                     */
                    interface I
                    {
                        /**
                         * @return class-string<T>
                         */
                        public function m(): string;
                    }

                    /**
                     * @template T2 of object
                     * @template-implements I<T2>
                     */
                    class C implements I
                    {
                        /** @var T2 */
                        private object $o;

                        /** @param T2 $o */
                        public function __construct(object $o) {
                            $this->o = $o;
                        }

                        public function m(): string {
                            return static::class;
                        }
                    }',
                'error_message' => 'LessSpecificReturnStatement'
            ],
            'preventBadOverrideWhenVerifyingNonFinalChildReturnType' => [
                '<?php
                    /**
                     * @template T of Enum
                     */
                    class EnumSet
                    {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type)
                        {
                            $this->type = $type;
                        }
                    }

                    abstract class Enum {
                        /**
                         * @return EnumSet<static>
                         */
                        public static function all()
                        {
                            return new EnumSet(static::class);
                        }
                    }

                    /**
                     * @extends EnumSet<CustomEnum>
                     */
                    final class CustomEnumSet extends EnumSet {
                        public function __construct()
                        {
                            parent::__construct(CustomEnum::class);
                        }
                    }

                    class CustomEnum extends Enum
                    {
                        /**
                         * @return CustomEnumSet
                         */
                        public static function all()
                        {
                            return new CustomEnumSet();
                        }
                    }',
                'error_message' => 'LessSpecificImplementedReturnType'
            ],
            'preventBadLocallyDefinedDocblockWhenVerifyingChildReturnType' => [
                '<?php
                    /**
                     * @template T of Enum
                     */
                    class EnumSet
                    {
                        /**
                         * @var class-string<T>
                         */
                        private $type;

                        /**
                         * @param class-string<T> $type
                         */
                        public function __construct(string $type)
                        {
                            $this->type = $type;
                        }
                    }

                    abstract class Enum {
                        /**
                         * @return EnumSet<static>
                         */
                        public static function all()
                        {
                            return new EnumSet(static::class);
                        }
                    }

                    /**
                     * @extends EnumSet<CustomEnum>
                     */
                    final class CustomEnumSet extends EnumSet {
                        public function __construct()
                        {
                            parent::__construct(CustomEnum::class);
                        }
                    }

                    class CustomEnum extends Enum
                    {
                        /**
                         * @return EnumSet<static>
                         */
                        public static function all()
                        {
                            return new CustomEnumSet();
                        }
                    }',
                'error_message' => 'LessSpecificReturnStatement'
            ],
            'nestedTemplateExtendsInvalid' => [
                '<?php
                    namespace Foo;

                    interface IBaseViewData {}

                    /**
                     * @template TViewData
                     */
                    class BaseModel {}

                    /**
                     * @template TViewData as IBaseViewData
                     * @template TModel as BaseModel<TViewData>
                     */
                    abstract class BaseRepository {}

                    class StudentViewData implements IBaseViewData {}
                    class TeacherViewData implements IBaseViewData {}

                    /** @extends BaseModel<StudentViewData> */
                    class StudentModel extends BaseModel {}

                    /** @extends BaseModel<TeacherViewData> */
                    class TeacherModel extends BaseModel {}

                    /** @extends BaseRepository<StudentViewData, TeacherModel> */
                    class StudentRepository extends BaseRepository {}',
                'error_message' => 'InvalidTemplateParam'
            ],
        ];
    }
}
