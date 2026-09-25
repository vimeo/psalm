<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class MixinAnnotationTest extends TestCase
{
    use InvalidCodeAnalysisWithIssuesTestTrait;
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'validSimpleAnnotations' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    $a->b();',
            ],
            'allowConstructor' => [
                'code' => '<?php
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
                    class A extends AParent {}',
            ],
            'implicitMixin' => [
                'code' => '<?php
                    function foo(string $dir) : void {
                        $iterator = new \RecursiveIteratorIterator(
                            new \RecursiveDirectoryIterator($dir)
                        );

                        while ($iterator->valid()) {
                            if (!$iterator->isDot() && $iterator->isLink()) {}

                            $iterator->next();
                        }
                    }',
            ],
            'wrapCustomIterator' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<1, 2>
                     */
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
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'templatedMixin' => [
                'code' => '<?php

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
                'assertions' => [
                    '$b' => 'string',
                ],
            ],
            'templatedMixinSelf' => [
                'code' => '<?php
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
                    }',
            ],
            'inheritPropertyAnnotations' => [
                'code' => '<?php
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
                    }',
            ],
            'inheritTemplatedMixinWithStatic' => [
                'code' => '<?php
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
                     * @psalm-suppress PropertyNotSetInConstructor
                     */
                    final class FooGrandChild extends FooChild {}

                    function test2() : FooGrandChild {
                        return FooGrandChild::type();
                    }

                    function test() : FooGrandChild {
                        return (new FooGrandChild)->type();
                    }',
            ],
            'inheritTemplatedMixinWithStaticAndFinalClass' => [
                'code' => '<?php
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
                    }',
            ],
            'mixinParseWithTextAfter' => [
                'code' => '<?php
                    class M {}

                    /**
                     * @mixin M
                     * Hello
                     */
                    class C {}',
            ],
            'templatedMixinWithTemplateWithStatic' => [
                'code' => '<?php
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
                    }',
            ],
            'multipleMixins' => [
                'code' => '<?php
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
                'code' => '<?php
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
                     * @psalm-suppress PropertyNotSetInConstructor
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
                    }',
            ],
            'multipleMixinsWithSameMethod' => [
                'code' => '<?php

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

                    $bar->foo();',
            ],
            'templatedMixinBindStatic' => [
                'code' => '<?php
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
                'assertions' => [
                    '$g' => 'list<FooModel>',
                ],
            ],
            'mixinInheritMagicMethods' => [
                'code' => '<?php
                    /**
                     * @method $this active()
                     */
                    class A {
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin A
                     */
                    class B {
                        public function __call(string $name, array $arguments) {}
                    }

                    $b = new B;
                    $c = $b->active();',
                'assertions' => [
                    '$c' => 'B',
                ],
            ],
            'nestedMixinResolvesTransitiveMethod' => [
                'code' => '<?php
                    namespace T;

                    class QueryB {
                        public function whereNull(string $c): static { return $this; }
                    }

                    /** @mixin \T\QueryB */
                    class EloquentB {}

                    /** @mixin \T\EloquentB */
                    class ClassHost {}

                    /** @mixin \T\EloquentB */
                    interface IfaceContract {}

                    function direct(EloquentB $q): void   { $q->whereNull("x"); }
                    function viaClass(ClassHost $q): void { $q->whereNull("x"); }
                    function viaIface(IfaceContract $q): void { $q->whereNull("x"); }',
            ],
            'nestedMixinResolvesReturnType' => [
                'code' => '<?php
                    namespace T;

                    class Base {
                        public function value(): int { return 1; }
                    }

                    /** @mixin \T\Base */
                    class Middle {}

                    /** @mixin \T\Middle */
                    class Top {}

                    $top = new Top();
                    $result = $top->value();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinDirectShadowsTransitive' => [
                'code' => '<?php
                    namespace T;

                    class Deep {
                        public function foo(): int { return 1; }
                    }

                    /** @mixin \T\Deep */
                    class Near {
                        public function foo(): string { return ""; }
                    }

                    /** @mixin \T\Near */
                    class Host {}

                    $host = new Host();
                    $result = $host->foo();',
                'assertions' => [
                    '$result' => 'string',
                ],
            ],
            'mutualMixinsDoNotRecurseInfinitely' => [
                'code' => '<?php
                    namespace T;

                    /** @mixin \T\Pong */
                    class Ping {
                        public function ping(): void {}
                    }

                    /** @mixin \T\Ping */
                    class Pong {
                        public function pong(): void {}
                    }

                    function test(Ping $p): void {
                        $p->ping();
                        $p->pong();
                    }',
            ],
            'nestedMixinStaticCall' => [
                'code' => '<?php
                    namespace T;

                    class StaticBase {
                        public static function make(): int { return 1; }
                    }

                    /** @mixin \T\StaticBase */
                    class StaticMiddle {}

                    /** @mixin \T\StaticMiddle */
                    class StaticTop {}

                    $result = StaticTop::make();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinPropertyFetch' => [
                'code' => '<?php
                    namespace T;

                    class PropBase {
                        public int $count = 0;
                    }

                    /** @mixin \T\PropBase */
                    class PropMiddle {}

                    /** @mixin \T\PropMiddle */
                    class PropTop {}

                    $top = new PropTop();
                    $result = $top->count;',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinDiamondResolvesSharedTarget' => [
                'code' => '<?php
                    namespace T;

                    class Shared {
                        public function shared(): int { return 1; }
                    }

                    /** @mixin \T\Shared */
                    class LeftArm {}

                    /** @mixin \T\Shared */
                    class RightArm {}

                    /**
                     * @mixin \T\LeftArm
                     * @mixin \T\RightArm
                     */
                    class Diamond {}

                    $d = new Diamond();
                    $result = $d->shared();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinTargetInheritsMixinFromParent' => [
                'code' => '<?php
                    namespace T;

                    class GrandChildMethods {
                        public function deep(): int { return 1; }
                    }

                    /** @mixin \T\GrandChildMethods */
                    class MixinParent {}

                    class MixinChild extends MixinParent {}

                    /** @mixin \T\MixinChild */
                    class HostDeclaredFirst {}

                    $host = new HostDeclaredFirst();
                    $result = $host->deep();',
                'assertions' => [
                    '$result' => 'int',
                ],
            ],
            'nestedMixinLaterSiblingWinsAtSameDepth' => [
                'code' => '<?php
                    namespace T;

                    class SibTargetA { public function val(): int { return 1; } }
                    class SibTargetB { public function val(): string { return ""; } }

                    /** @mixin \T\SibTargetA */
                    class SibLeft {}

                    /** @mixin \T\SibTargetB */
                    class SibRight {}

                    /**
                     * @mixin \T\SibLeft
                     * @mixin \T\SibRight
                     */
                    class SibHost {}

                    $host = new SibHost();
                    $result = $host->val();',
                'assertions' => [
                    // SibRight is declared after SibLeft, so its transitively mixed-in val() wins, matching
                    // the analyzers last-match-wins precedence for same-depth mixins.
                    '$result' => 'string',
                ],
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedMixinClass' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'nestedGenericMixinChainIsNotFollowed' => [
                // Transitive flattening only follows named (non-templated) mixins. A generic mixin hop
                // would need per-hop template binding, so a method reachable only through one stays
                // unresolved rather than leaking an unbound template param into the host.
                'code' => '<?php
                    namespace T;

                    /** @template TItem */
                    class GenInner {
                        /** @return TItem */
                        public function item() { throw new \Exception(); }
                    }

                    /**
                     * @template TMid
                     * @mixin \T\GenInner<TMid>
                     */
                    class GenMiddle {}

                    /** @mixin \T\GenMiddle<string> */
                    class GenHost {}

                    $host = new GenHost();
                    $host->item();',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedMixinClassWithPropertyFetch' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'undefinedMixinClassWithPropertyFetch_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @property string $baz
                     * @mixin B
                     */
                    class A {
                        public function __get(string $name): string {
                            return "";
                        }
                    }

                    (new A)->foo;',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'undefinedMixinClassWithPropertyAssignment' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo = "bar";',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'undefinedMixinClassWithPropertyAssignment_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @property string $baz
                     * @mixin B
                     */
                    class A {
                        public function __set(string $name, string $value) {}
                    }

                    (new A)->foo = "bar";',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'undefinedMixinClassWithMethodCall' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    (new A)->foo();',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedMixinClassWithMethodCall_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @method baz()
                     * @mixin B
                     */
                    class A {
                        public function __call(string $name, array $arguments) {}
                    }

                    (new A)->foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'undefinedMixinClassWithStaticMethodCall' => [
                'code' => '<?php
                    /** @mixin B */
                    class A {}

                    A::foo();',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedMixinClassWithStaticMethodCall_WithMagicMethod' => [
                'code' => '<?php
                    /**
                     * @method baz()
                     * @mixin B
                     */
                    class A {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    A::foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'inheritTemplatedMixinWithSelf' => [
                'code' => '<?php
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
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'mixinStaticCallShouldNotPolluteContext' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class Foo
                    {
                        public function foobar(): void {}
                    }

                    /**
                     * @template T
                     * @mixin Foo<T>
                     */
                    class Bar
                    {
                        public function baz(): self
                        {
                            self::foobar();
                            return $__tmp_mixin_var__;
                        }
                    }',
                'error_message' => 'UndefinedVariable',
            ],
        ];
    }
}
