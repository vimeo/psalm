<?php
namespace Psalm\Tests;

class PropertyTypeInvarianceTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'validcode' => [
                '<?php
                    class ParentClass
                    {
                        /** @var null|string */
                        protected $mightExist;

                        protected ?string $mightExistNative = null;

                        /** @var string */
                        protected $doesExist = "";

                        protected string $doesExistNative = "";
                    }

                    class ChildClass extends ParentClass
                    {
                        /** @var null|string */
                        protected $mightExist = "";

                        protected ?string $mightExistNative = null;

                        /** @var string */
                        protected $doesExist = "";

                        protected string $doesExistNative = "";
                    }',
            ],
            'allowTemplatedInvariance' => [
                '<?php
                    /**
                     * @template T as string|null
                     */
                    abstract class A {
                        /** @var T */
                        public $foo;
                    }

                    /**
                     * @extends A<string>
                     */
                    class AChild extends A {
                        /** @var string */
                        public $foo = "foo";
                    }',
            ],
            'allowTemplatedInvarianceWithListTemplate' => [
                '<?php
                    abstract class Item {}
                    class Foo extends Item {}

                    /** @template TItem of Item */
                    abstract class ItemCollection
                    {
                        /** @var list<TItem> */
                        protected $items = [];
                    }

                    /** @extends ItemCollection<Foo> */
                    class FooCollection extends ItemCollection
                    {
                        /** @var list<Foo> */
                        protected $items = [];
                    }',
            ],
            'allowTemplatedInvarianceWithClassTemplate' => [
                '<?php
                    abstract class Item {}
                    class Foo extends Item {}

                    /** @template T */
                    class Collection {}

                    /** @template TItem of Item */
                    abstract class ItemCollection
                    {
                        /** @var Collection<TItem>|null */
                        protected $items;
                    }

                    /** @extends ItemCollection<Foo> */
                    class FooCollection extends ItemCollection
                    {
                        /** @var Collection<Foo>|null */
                        protected $items;
                    }',
            ],
            'allowTemplatedInvarianceWithClassStringTemplate' => [
                '<?php
                    abstract class Item {}
                    class Foo extends Item {}

                    /** @template T of Item */
                    abstract class ItemType
                    {
                        /** @var class-string<T>|null */
                        protected $type;
                    }

                    /** @extends ItemType<Foo> */
                    class FooTypes extends ItemType
                    {
                        /** @var class-string<Foo>|null */
                        protected $type;
                    }',
            ],
            'templatedInvarianceGrandchild' => [
                '<?php
                    abstract class Item {}
                    class Foo extends Item {}
                    class Bar extends Foo {}

                    /** @template T of Item */
                    abstract class ItemCollection
                    {
                        /** @var list<T> */
                        protected $items = [];
                    }

                    /**
                     * @template T of Foo
                     * @extends ItemCollection<T>
                     */
                    class FooCollection extends ItemCollection
                    {
                        /** @var list<T> */
                        protected $items = [];
                    }

                    /** @extends FooCollection<Bar> */
                    class BarCollection extends FooCollection
                    {
                        /** @var list<Bar> */
                        protected $items = [];
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'variantDocblockProperties' => [
                '<?php
                    class ParentClass
                    {
                        /** @var null|string */
                        protected $mightExist;
                    }

                    class ChildClass extends ParentClass
                    {
                        /** @var string */
                        protected $mightExist = "";
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
            ],
            'variantProperties' => [
                '<?php
                    class ParentClass
                    {
                        protected ?string $mightExist = null;
                    }

                    class ChildClass extends ParentClass
                    {
                        protected string $mightExist = "";
                    }',
                'error_message' => 'NonInvariantPropertyType',
            ],
            'variantTemplatedProperties' => [
                '<?php
                    /**
                     * @template T as string|null
                     */
                    abstract class A {
                        /** @var T */
                        public $foo;
                    }

                    /**
                     * @extends A<string>
                     */
                    class AChild extends A {
                        /** @var int */
                        public $foo = 0;
                    }',
                'error_message' => 'NonInvariantDocblockPropertyType',
            ],
        ];
    }
}
