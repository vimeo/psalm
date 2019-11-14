<?php
namespace Psalm\Tests\Template;

use const DIRECTORY_SEPARATOR;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits;

class ClassTemplateCovarianceTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'allowBoundedType' => [
                '<?php
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
                '<?php
                    /** @template-covariant T */
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
            'specializeTypeInPropertyAssignment' => [
                '<?php
                    /** @template-covariant T */
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
            ],
            'allowPassingToCovariantCollection' => [
                '<?php
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
                     * @template-extends \ArrayObject<int,TValue>
                     */
                    class Collection extends \ArrayObject {
                        /**
                         * @param array<int,TValue> $kv
                         */
                        public function __construct(array $kv) {
                            parent::__construct($kv);
                        }
                    }

                    /**
                     * @param Collection<Animal> $list
                     */
                    function getSounds(Traversable $list) : void {
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
                '<?php
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
                     * @template-extends \ArrayObject<int,TValue>
                     */
                    class Collection extends \ArrayObject {}

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
                '<?php
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

                    getView(new Cat());'
            ],
            'allowExtendingInterfaceWithExtraParam' => [
                '<?php
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
                    function usesElementInterfaceCollection(CollectionInterface $col) :void {}'
            ],
            'extendsCovariantCoreClassWithSameParamCount' => [
                '<?php
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
                    }'
            ],
            'extendsCovariantCoreClassWithSubstitutedParam' => [
                '<?php
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
            'preventExtendingWithCovariance' => [
                '<?php
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
                '<?php
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
        ];
    }
}
