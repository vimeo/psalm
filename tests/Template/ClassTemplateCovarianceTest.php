<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ClassTemplateCovarianceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'allowBoundedType' => [
                'code' => '<?php
                    class Base {}
                    class Child extends Base {}

                    /**
                     * @template-covariant T
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
                'code' => '<?php
                    /** @template-covariant T */
                    class Foo {
                        /** @param \Closure():T $closure */
                        public function __construct($closure) {}
                    }

                    class Bar {
                        /** @var Foo<array> */
                        private $arrayOfFoo;

                        public function __construct() {
                            $this->arrayOfFoo = new Foo(function(): array { return ["foo" => "bar"]; });
                        }
                    }',
            ],
            'allowPassingToCovariantCollectionWithoutExtends' => [
                'code' => '<?php
                    abstract class Animal {
                        abstract public function getSound() : string;
                    }
                    class Dog extends Animal {
                        public function getSound() : string {
                            return "Woof!";
                        }
                    }

                    /**
                     * @template-covariant TValue
                     * @implements IteratorAggregate<int, TValue>
                     */
                    class Collection implements IteratorAggregate {
                        private $arr;

                        /**
                         * @param array<int,TValue> $arr
                         */
                        public function __construct(array $arr) {
                            $this->arr = $arr;
                        }

                        /** @return Traversable<int, TValue> */
                        public function getIterator() {
                            foreach ($this->arr as $k => $v) {
                                yield $k => $v;
                            }
                        }
                    }

                    /**
                     * @param Collection<Animal> $list
                     */
                    function getSounds(Collection $list) : void {
                        foreach ($list as $l) {
                            $l->getSound();
                        }
                    }

                    /**
                     * @param Collection<Dog> $list
                     */
                    function takesDogList(Collection $list) : void {
                        getSounds($list); // this probably should not be an error
                    }',
            ],
            'allowPassingToCovariantCollectionWithExtends' => [
                'code' => '<?php
                    abstract class Animal {
                        abstract public function getSound() : string;
                    }

                    class Dog extends Animal {
                        public function getSound() : string {
                            return "Woof!";
                        }
                    }

                    /**
                     * @template-covariant TValue
                     * @implements IteratorAggregate<int, TValue>
                     */
                    class Collection implements IteratorAggregate {
                        private $arr;

                        /**
                         * @param array<int,TValue> $arr
                         */
                        public function __construct(array $arr) {
                            $this->arr = $arr;
                        }

                        /** @return Traversable<int, TValue> */
                        public function getIterator() {
                            foreach ($this->arr as $k => $v) {
                                yield $k => $v;
                            }
                        }
                    }

                    /** @template-extends Collection<Dog> */
                    class HardwiredDogCollection extends Collection {}

                    /**
                     * @param Collection<Animal> $list
                     */
                    function getSounds(Collection $list) : void {
                        foreach ($list as $l) {
                            echo $l->getSound();
                        }
                    }

                    getSounds(new HardwiredDogCollection([new Dog]));',
            ],
            'butWithCatInstead' => [
                'code' => '<?php
                    /** @template-covariant T as object **/
                    interface Viewable
                    {
                        /** @psalm-return T **/
                        public function view(): object;
                    }

                    class CatView
                    {
                        /**
                          * @var string
                          * @readonly
                          */
                        public $name;

                        public function __construct(string $name) {
                            $this->name = $name;
                        }
                    }

                    /** @implements Viewable<CatView> */
                    class Cat implements Viewable
                    {
                        public function view(): object {
                            return new CatView("Kittie");
                        }
                    }

                    /** @psalm-param Viewable<object> $viewable */
                    function getView(Viewable $viewable): object {
                        return $viewable->view();
                    }

                    getView(new Cat());',
            ],
            'allowExtendingInterfaceWithExtraParam' => [
                'code' => '<?php
                    usesElementInterfaceCollection(new Collection([ new Element ]));

                    /**
                     * @template TKey as array-key
                     * @template-covariant TValue
                     */
                    interface CollectionInterface {}

                    interface ElementInterface {}

                    /**
                     * @template-covariant T
                     * @template-implements CollectionInterface<int, T>
                     */
                    class Collection implements CollectionInterface
                    {
                      /** @param list<T> $elements */
                      public function __construct(array $elements) {}
                    }

                    class Element implements ElementInterface {}

                    /** @param CollectionInterface<int, ElementInterface> $col */
                    function usesElementInterfaceCollection(CollectionInterface $col) :void {}',
            ],
            'extendsCovariantCoreClassWithSameParamCount' => [
                'code' => '<?php
                    /**
                     * @template TKey as array-key
                     * @template TValue
                     * @template-implements IteratorAggregate<TKey,TValue>
                     */
                    class MyArray implements IteratorAggregate {
                        /** @var array<TKey,TValue> */
                        private $values = [];

                        public function __construct() {
                            $this->values = [];
                        }

                        public function getIterator() : Traversable {
                            return new ArrayObject($this->values);
                        }
                    }

                    class A {}
                    class AChild extends A {}

                    /** @param IteratorAggregate<int, A> $i */
                    function takesIteratorAggregate(IteratorAggregate $i) : void {}

                    /** @param MyArray<int, AChild> $a */
                    function takesMyArrayOfException(MyArray $a) : void {
                        takesIteratorAggregate($a);
                    }',
            ],
            'extendsCovariantCoreClassWithSubstitutedParam' => [
                'code' => '<?php
                    /**
                     * @template TValue
                     * @template-implements IteratorAggregate<int,TValue>
                     */
                    class MyArray implements IteratorAggregate {
                        /** @var array<int,TValue> */
                        private $values = [];

                        public function __construct() {
                            $this->values = [];
                        }

                        public function getIterator() : Traversable {
                            return new ArrayObject($this->values);
                        }
                    }

                    class A {}
                    class AChild extends A {}

                    /** @param IteratorAggregate<int, A> $i */
                    function takesIteratorAggregate(IteratorAggregate $i) : void {}

                    /** @param MyArray<AChild> $a */
                    function takesMyArrayOfException(MyArray $a) : void {
                        takesIteratorAggregate($a);
                    }',
            ],
            'allowImmutableCovariance' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Animal {}

                    /**
                     * @psalm-immutable
                     */
                    class Dog extends Animal{}

                    /**
                     * @psalm-immutable
                     */
                    class Cat extends Animal{}

                    /**
                      * @psalm-immutable
                      * @template-covariant T
                      */
                    class Collection {

                        /** @var list<T> */
                        private $arr = [];

                        /**
                          * @no-named-arguments
                          * @param T ...$a
                          */
                        public function __construct(...$a) {
                            $this->arr = $a;
                        }

                       /**
                         * @param T $a
                         * @return Collection<T>
                         */
                        public function add($a) : Collection
                        {
                          return new Collection(...$this->arr, $a);
                        }
                    }

                    /**
                      * @template T
                      * @param Collection<Animal> $c
                      * @return Collection<Animal>
                      */
                    function covariant(Collection $c) : Collection
                    {
                      return $c->add(new Cat());
                    }

                    $dogs = new Collection(new Dog(), new Dog());
                    $cats = new Collection(new Cat(), new Cat());
                    $misc = new Collection(new Cat(), new Dog());

                    covariant($dogs);
                    covariant($cats);
                    covariant($misc);',
            ],
            'allowCovariantReferenceToMapToCovariant' => [
                'code' => '<?php
                    /** @template-covariant T */
                    class CovariantReference
                    {
                        /** @var T */
                        private $value;

                        /** @param T $value */
                        public function __construct($value)
                        {
                            $this->value = $value;
                        }

                        /** @return T */
                        public function get()
                        {
                            return $this->value;
                        }
                    }

                    /**
                     * @template-covariant T
                     */
                    class C
                    {
                        /** @var CovariantReference<T> */
                        private $reference;

                        /** @param CovariantReference<T> $reference */
                        public function __construct($reference)
                        {
                            $this->reference = $reference;
                        }

                        /** @return CovariantReference<T> */
                        function getReference()
                        {
                            return $this->reference;
                        }
                    }',
            ],
            'allowCovariantReturnOnArrays' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     */
                    class A {
                        private $arr;

                        /** @psalm-param array<T> $arr */
                        public function __construct(array $arr) {
                            $this->arr = $arr;
                        }

                        /** @psalm-return array<T> */
                        public function foo(): array {
                            return $this->arr;
                        }
                    }',
            ],
            'allowIteratorCovariance' => [
                'code' => '<?php
                    /**
                     * @template-covariant T
                     */
                    interface ITraversable
                    {
                        /** @psalm-return Traversable<T> */
                        public function foo(): Traversable;
                    }

                    /**
                     * @template-covariant T
                     */
                    interface IArray
                    {
                        /** @psalm-return array<T> */
                        public function foo(): array;
                    }

                    /**
                     * @template-covariant T
                     */
                    interface IIterable
                    {
                        /** @psalm-return iterable<T> */
                        public function foo(): iterable;
                    }',
            ],
            'extendWithArrayTemplate' => [
                'code' => '<?php
                    /**
                     * @template-covariant T1
                     */
                    interface C {
                        /**
                         * @psalm-return self<array<int, T1>>
                         */
                        public function zip();
                    }

                    /**
                     * @template T2
                     * @extends C<T2>
                     */
                    interface AC extends C {
                        /**
                         * @psalm-return self<array<int, T2>>
                         */
                        public function zip();
                    }',
            ],
            'extendsArrayWithCovariant' => [
                'code' => '<?php
                    /**
                     * @template-covariant T1
                     */
                    interface IParentCollection {
                        /**
                         * @return IParentCollection<array<T1>>
                         */
                        public function getNested(): IParentCollection;
                    }

                    /**
                     * @template T2
                     *
                     * @extends IParentCollection<T2>
                     */
                    interface IChildCollection extends IParentCollection {
                        /**
                         * @return IChildCollection<array<T2>>
                         */
                        public function getNested(): IChildCollection;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'extendsTKeyedArrayWithCovariant' => [
                'code' => '<?php
                    /**
                     * @template-covariant T1
                     */
                    interface IParentCollection {
                        /**
                         * @return IParentCollection<array{0: T1}>
                         */
                        public function getNested(): IParentCollection;
                    }

                    /**
                     * @template T2
                     *
                     * @extends IParentCollection<T2>
                     */
                    interface IChildCollection extends IParentCollection {
                        /**
                         * @return IChildCollection<array{0: T2}>
                         */
                        public function getNested(): IChildCollection;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'noNeedForCovarianceWithFunctionTemplate' => [
                'code' => '<?php
                    class SomeParent {}
                    class TypeA extends SomeParent {}
                    class TypeB extends SomeParent {}

                    /** @template T of SomeParent */
                    class Container{
                        /** @var T */
                        public $value;
                        /** @param T $value */
                        public function __construct(SomeParent $value) {
                            $this->value = $value;
                        }
                    }

                    /**
                     * @template T of SomeParent
                     * @param Container<T> $container
                     */
                    function run(Container $container): void{}

                    run(new Container(new TypeA()));',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'preventCovariantParamUsage' => [
                'code' => '<?php
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
            'preventExtendingWithCovariance' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class InvariantFoo
                    {
                        /**
                         * @param T $value
                         */
                        public function set($value): void {}
                    }

                    /**
                     * @template-covariant T
                     * @extends InvariantFoo<T>
                     */
                    class CovariantFoo extends InvariantFoo {}',
                'error_message' => 'InvalidTemplateParam',
            ],
            'expectsTemplatedObject' => [
                'code' => '<?php
                    /**
                     * @template T
                     * @template-implements ArrayAccess<int,T>
                     */
                    class MyArray implements ArrayAccess, IteratorAggregate {
                        /** @var array<int,T> */
                        private $values = [];
                        public function __construct() {
                            $this->values = [];
                        }

                        /**
                         * @param int $offset
                         * @param T $value
                         */
                        public function offsetSet($offset, $value) {
                            $this->values[$offset] = $value;
                        }
                        /**
                         * @param int $offset
                         * @return T
                         */
                        public function offsetGet($offset) {
                            return $this->values[$offset];
                        }
                        /**
                         * @param int $offset
                         * @return bool
                         */
                        public function offsetExists($offset) {
                            return isset($this->values[$offset]);
                        }
                        /**
                         * @param int $offset
                         */
                        public function offsetUnset($offset) {
                            unset($this->values[$offset]);
                        }

                        public function getIterator() : Traversable {
                            return new ArrayObject($this->values);
                        }
                    }

                    class A {}
                    class AChild extends A {}

                    /** @param IteratorAggregate<int, A> $i */
                    function expectsIteratorAggregateOfA(IteratorAggregate $i) : void {}

                    /** @param MyArray<AChild> $m */
                    function takesMyArrayOfAChild(MyArray $m) : void {
                        expectsIteratorAggregateOfA($m);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion',
            ],
            'preventGeneratorVariance' => [
                'code' => '<?php
                    class Foo {
                        function a(): void {}
                    }

                    class Bar extends Foo {
                      function b(): void {}
                    }

                    /**
                     * @return Generator<int, Bar, Bar, mixed>
                     */
                    function gen() : Generator {
                      $bar = yield new Bar();
                      $bar->b();
                    }

                    /** @param Generator<int,Bar,Foo,mixed> $gen */
                    function sendFoo(Generator $gen): void {
                      $gen->send(new Foo());
                    }

                    $gen = gen();
                    sendFoo($gen);',
                'error_message' => 'InvalidArgument',
            ],
            'preventCovariantParamMappingToInvariant' => [
                'code' => '<?php
                    /** @template T */
                    class InvariantReference
                    {
                        /** @var T */
                        private $value;

                        /** @param T $value */
                        public function __construct($value)
                        {
                            $this->value = $value;
                        }

                        /** @return T */
                        public function get()
                        {
                            return $this->value;
                        }
                    }

                    /**
                     * @template-covariant T
                     */
                    class C
                    {
                        /** @var InvariantReference<T> */
                        private InvariantReference $reference;

                        /** @param InvariantReference<T> $reference */
                        public function __construct(InvariantReference $reference)
                        {
                            $this->reference = $reference;
                        }

                        /** @return InvariantReference<T> */
                        public function getReference() : InvariantReference
                        {
                            return $this->reference;
                        }
                    }',
                'error_message' => 'InvalidTemplateParam',
            ],
            'preventExtendingCoreWithCovariantParam' => [
                'code' => '<?php
                    /**
                     * @template-covariant TValue
                     * @template-extends \ArrayObject<int,TValue>
                     */
                    class Collection extends \ArrayObject {}',
                'error_message' => 'InvalidTemplateParam',
            ],
        ];
    }
}
