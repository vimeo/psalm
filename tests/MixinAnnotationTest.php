<?php
namespace Psalm\Tests;

class MixinAnnotationTest extends TestCase
{
    use Traits\ValidCodeAnalysisTestTrait;
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'validSimpleAnnotations' => [
                '<?php
                    class ParentClass {
                        public function __call(string $name, array $args) {}
                        public static function __callStatic(string $name, array $args) {}
                    }

                    class Provider {
                        public function getString() : string {
                            return "hello";
                        }

                        public function setInteger(int $i) : void {}

                        public static function getInt() : int {
                            return 5;
                        }
                    }

                    /** @mixin Provider */
                    class Child extends ParentClass {}

                    $child = new Child();

                    $a = $child->getString();
                    $b = $child::getInt();',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'anotherSimpleExample' => [
                '<?php
                    /**
                     * @mixin B
                     */
                    class A {
                        /** @var B */
                        private $b;

                        public function __construct() {
                            $this->b = new B();
                        }

                        public function c(string $s) : void {}

                        /**
                         * @param array<mixed> $arguments
                         * @return mixed
                         */
                        public function __call(string $method, array $arguments)
                        {
                            return $this->b->$method(...$arguments);
                        }
                    }

                    class B {
                        public function b(): void {
                            echo "b";
                        }

                        public function c(int $s) : void {}
                    }

                    $a = new A();
                    $a->b();'
            ],
            'allowConstructor' => [
                '<?php
                    abstract class AParent {
                        protected int $i;

                        public function __construct() {
                            $this->i = 1;
                        }
                    }

                    class M {
                        public function __construct() {}
                    }

                    /**
                     * @mixin M
                     */
                    class A extends AParent {}'
            ],
            'implicitMixin' => [
                '<?php
                    function foo(string $dir) : void {
                        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($dir)
                        );

                        while ($iterator->valid()) {
                            if (!$iterator->isDot() && $iterator->isLink()) {}

                            $iterator->next();
                        }
                    }'
            ],
            'wrapCustomIterator' => [
                '<?php
                    class Subject implements Iterator {
                        /**
                         * the index method exists
                         *
                         * @param int $index
                         * @return bool
                         */
                        public function index($index) {
                            return true;
                        }

                        public function current() {
                            return 2;
                        }

                        public function next() {}

                        public function key() {
                            return 1;
                        }

                        public function valid() {
                            return false;
                        }

                        public function rewind() {}
                    }

                    $iter = new IteratorIterator(new Subject());
                    $b = $iter->index(0);',
                [
                    '$b' => 'bool',
                ]
            ],
            'templatedMixin' => [
                '<?php

                    /**
                     * @template T
                     */
                    abstract class Foo {
                        /** @return T */
                        abstract public function hi();
                    }

                    /**
                     * @mixin Foo<string>
                     */
                    class Bar {}

                    $bar = new Bar();
                    $b = $bar->hi();',
                [
                    '$b' => 'string',
                ]
            ],
            'templatedMixinSelf' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Animal {
                        /** @var T */
                        private $item;

                        /**
                         * @param T $item
                         */
                        public function __construct($item) {
                            $this->item = $item;
                        }

                        /**
                         * @return T
                         */
                        public function get() {
                            return $this->item;
                        }
                    }

                    /**
                     * @mixin Animal<self>
                     */
                    class Dog {
                        public function __construct() {}
                    }

                    function getDog(): Dog {
                        return (new Dog())->get();
                    }'
            ],
            'inheritPropertyAnnotations' => [
                '<?php
                    /**
                     * @property string $foo
                     */
                    class A {}

                    /**
                     * @mixin A
                     */
                    class B {
                        /** @return mixed */
                        public function __get(string $s) {
                            return 5;
                        }
                    }

                    function toArray(B $b) : string {
                        return $b->foo;
                    }'
            ],
            'inheritTemplatedMixinWithStatic' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function type() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T as object
                     * @mixin Mixin<T>
                     * @psalm-consistent-constructor
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        public function __call(string $name, array $args) {
                            return $this->obj->$name(...$args);
                        }

                        public function __callStatic(string $name, array $args) {
                            return (new static)->obj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<static>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    function test2() : FooGrandChild {
                        return FooGrandChild::type();
                    }

                    function test() : FooGrandChild {
                        return (new FooGrandChild)->type();
                    }'
            ],
            'inheritTemplatedMixinWithStaticAndFinalClass' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return self<T>
                         */
                        public function getMixin() {
                            return $this;
                        }
                    }

                    /**
                     * @template T as object
                     * @mixin Mixin<T>
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        public function __call(string $name, array $args) {
                            return $this->obj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<static>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    /**
                    * @psalm-return Mixin<FooGrandChild>
                    */
                    function test() : Mixin {
                        return (new FooGrandChild)->getMixin();
                    }'
            ],
            'mixinParseWithTextAfter' => [
                '<?php
                    class M {}

                    /**
                     * @mixin M
                     * Hello
                     */
                    class C {}'
            ],
            'templatedMixinWithTemplateWithStatic' => [
                '<?php
                    /**
                     * @template T as object
                     * @mixin T
                     */
                    class Builder {
                        private $t;

                        /** @param T $t */
                        public function __construct(object $t) {
                            $this->t = $t;
                        }

                        public function __call(string $method, array $parameters) {
                            /** @psalm-suppress MixedMethodCall */
                            return $this->t->$method($parameters);
                        }
                    }

                    /**
                     * @method self active()
                     */
                    class Model {
                        /**
                         * @return Builder<static>
                         */
                        public function query(): Builder {
                            return new Builder($this);
                        }

                        public function __call(string $method, array $parameters) {
                            if ($method === "active") {
                                return new Model();
                            }
                        }
                    }

                    /** @param Builder<Model> $b */
                    function foo(Builder $b) : Model {
                        return $b->active();
                    }'
            ],
            'multipleMixins' => [
                '<?php
                    class MixinA {
                        function a(): string { return "foo"; }
                    }

                    class MixinB {
                        function b(): int { return 0; }
                    }

                    /**
                     * @mixin MixinA
                     * @mixin MixinB
                     */
                    class Test {}

                    $test = new Test();

                    $a = $test->a();
                    $b = $test->b();',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'inheritMultipleTemplatedMixinsWithStatic' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function type() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T
                     */
                    class OtherMixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function other() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T as object
                     * @template T2 as string
                     * @mixin Mixin<T>
                     * @mixin OtherMixin<T2>
                     * @psalm-consistent-constructor
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        /** @var OtherMixin<T2> */
                        public object $otherObj;

                        public function __call(string $name, array $args) {
                            if ($name === "test") {
                                return $this->obj->$name(...$args);
                            }

                            return $this->otherObj->$name(...$args);
                        }

                        public function __callStatic(string $name, array $args) {
                            if ($name === "test") {
                                return (new static)->obj->$name(...$args);
                            }

                            return (new static)->otherObj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<static, string>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    function test() : FooGrandChild {
                        return FooGrandChild::type();
                    }

                    function testStatic() : FooGrandChild {
                        return (new FooGrandChild)->type();
                    }

                    function other() : string {
                        return FooGrandChild::other();
                    }

                    function otherStatic() : string {
                        return (new FooGrandChild)->other();
                    }'
            ],
            'multipleMixinsWithSameMethod' => [
                '<?php

                    class Mix1
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    class Mix2
                    {
                        public function foo(): string
                        {
                            return "";
                        }
                    }

                    /**
                     * @mixin Mix1
                     * @mixin Mix2
                     */
                    class Bar
                    {

                    }

                    $bar = new Bar();

                    $bar->foo();'
            ],
            'templatedMixinBindStatic' => [
                '<?php
                    /**
                     * @template-covariant TModel of Model
                     */
                    class QueryBuilder {
                        /**
                         * @return list<TModel>
                         */
                        public function getInner() {
                            return [];
                        }
                    }

                    /**
                     * @mixin QueryBuilder<static>
                     */
                    abstract class Model {}

                    class FooModel extends Model {}

                    $f = new FooModel();
                    $g = $f->getInner();',
                [
                    '$g' => 'list<FooModel>',
                ]
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedMixinClass' => [
                '<?php
                    /** @mixin B */
                    class A {}',
                'error_message' => 'UndefinedDocblockClass'
            ],
            'undefinedMixinClassWithPropertyFetch' => [
                '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch'
            ],
            'undefinedMixinClassWithPropertyAssignment' => [
                '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo = "bar";',
                'error_message' => 'UndefinedPropertyAssignment'
            ],
            'undefinedMixinClassWithMethodCall' => [
                '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo();',
                'error_message' => 'UndefinedMethod'
            ],
            'inheritTemplatedMixinWithSelf' => [
                '<?php
                    /**
                     * @template T
                     */
                    class Mixin {
                        /**
                         * @psalm-var T
                         */
                        private $var;

                        /**
                         * @psalm-param T $var
                         */
                        public function __construct ($var) {
                            $this->var = $var;
                        }

                        /**
                         * @psalm-return T
                         */
                        public function type() {
                            return $this->var;
                        }
                    }

                    /**
                     * @template T as object
                     * @mixin Mixin<T>
                     */
                    abstract class Foo {
                        /** @var Mixin<T> */
                        public object $obj;

                        public function __call(string $name, array $args) {
                            return $this->obj->$name(...$args);
                        }
                    }

                    /**
                     * @extends Foo<self>
                     */
                    abstract class FooChild extends Foo{}

                    /**
                     * @psalm-suppress MissingConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    function test() : FooGrandChild {
                        return (new FooGrandChild)->type();
                    }',
                'error_message' => 'LessSpecificReturnStatement'
            ],
        ];
    }
}
