<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class InterfaceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'extendsAndImplements' => [
                'code' => '<?php
                    interface A
                    {
                        /**
                         * @return string
                         */
                        public function fooFoo();
                    }

                    interface B
                    {
                        /**
                         * @return string
                         */
                        public function barBar();
                    }

                    interface C extends A, B
                    {
                        /**
                         * @return string
                         */
                        public function baz();
                    }

                    class D implements C
                    {
                        /**
                         * @return string
                         */
                        public function fooFoo()
                        {
                            return "hello";
                        }

                        /**
                         * @return string
                         */
                        public function barBar()
                        {
                            return "goodbye";
                        }

                        /**
                         * @return string
                         */
                        public function baz()
                        {
                            return "hello again";
                        }
                    }

                    $cee = (new D())->baz();
                    $dee = (new D())->fooFoo();',
                'assertions' => [
                    '$cee' => 'string',
                    '$dee' => 'string',
                ],
            ],
            'isExtendedInterface' => [
                'code' => '<?php
                    interface A {}
                    class B implements A {}

                    /**
                     * @param  A      $a
                     * @return void
                     */
                    function qux(A $a) { }

                    qux(new B());',
            ],
            'isDoubleExtendedInterface' => [
                'code' => '<?php
                    interface A {}
                    interface B extends A {}
                    class C implements B {}

                    /**
                     * @param  A      $a
                     * @return void
                     */
                    function qux(A $a) {
                    }

                    qux(new C());',
            ],
            'extendsWithMethod' => [
                'code' => '<?php
                    interface A
                    {
                        /**
                         * @return string
                         */
                        public function fooFoo();
                    }

                    interface B extends A
                    {
                        public function barBar() : void;
                    }

                    /** @return void */
                    function mux(B $b) {
                        $b->fooFoo();
                    }',
            ],
            'correctInterfaceMethodSignature' => [
                'code' => '<?php
                    interface A {
                        public function fooFoo(int $a): void;
                    }

                    class B implements A {
                        public function fooFoo(int $a): void {

                        }
                    }',
            ],
            'interfaceMethodImplementedInParent' => [
                'code' => '<?php
                    interface MyInterface {
                        public function fooFoo(int $a): void;
                    }

                    class B {
                        public function fooFoo(int $a): void {

                        }
                    }

                    class C extends B implements MyInterface { }',
            ],
            'interfaceMethodSignatureInTrait' => [
                'code' => '<?php
                    interface A {
                        public function fooFoo(int $a, int $b): void;
                    }

                    trait T {
                        public function fooFoo(int $a, int $b): void {
                        }
                    }

                    class B implements A {
                        use T;
                    }',
            ],
            'delayedInterface' => [
                'code' => '<?php
                    // fails in PHP, whatcha gonna do
                    $c = new C;

                    class A { }

                    interface B { }

                    class C extends A implements B { }',
            ],
            'typeDoesNotContainType' => [
                'code' => '<?php
                    interface A { }
                    interface B {
                        function foo() : void;
                    }
                    function bar(A $a): void {
                        if ($a instanceof B) {
                            $a->foo();
                        }
                    }',
            ],
            'abstractInterfaceImplements' => [
                'code' => '<?php
                    interface I {
                        public function fnc() : void;
                    }

                    abstract class A implements I {}',
            ],
            'abstractInterfaceImplementsButCallMethod' => [
                'code' => '<?php
                    interface I {
                        public function foo() : void;
                    }

                    abstract class A implements I {
                        public function bar(): void {
                            $this->foo();
                        }
                    }',
            ],
            'implementsPartialInterfaceMethods' => [
                'code' => '<?php
                    namespace Bat;

                    interface I  {
                      public function foo();
                      public function bar();
                    }
                    abstract class A implements I {
                      public function foo() {
                        return "hello";
                      }
                    }
                    class B extends A {
                      public function bar() {
                        return "goodbye";
                      }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MissingReturnType'],
            ],
            'interfaceConstants' => [
                'code' => '<?php
                    interface I1 {
                        const A = 5;
                        const B = "two";
                        const C = 3.0;
                    }

                    interface I2 extends I1 {
                        const D = 5;
                        const E = "two";
                    }

                    class A implements I2 {
                        /** @var int */
                        public $foo = I1::A;

                        /** @var string */
                        public $bar = self::B;

                        /** @var float */
                        public $bar2 = I2::C;

                        /** @var int */
                        public $foo2 = I2::D;

                        /** @var string */
                        public $bar3 = self::E;
                    }',
            ],
            'interfaceExtendsReturnType' => [
                'code' => '<?php
                    interface A {}
                    interface B extends A {}

                    function foo(B $a): A {
                        return $a;
                    }',
            ],
            'interfaceInstanceofReturningInitial' => [
                'code' => '<?php
                    interface A {}
                    interface B {}

                    class C implements A, B {}

                    function takesB(B $b): void {}

                    function foo(A $i): A {
                        if ($i instanceof B) {
                            takesB($i);
                            return $i;
                        }
                        return $i;
                    }

                    foo(new C);',
            ],
            'interfaceInstanceofAndReturn' => [
                'code' => '<?php
                    interface A {}
                    interface B {}

                    class C implements A, B {}

                    function foo(A $i): B {
                        if ($i instanceof B) {
                            return $i;
                        }
                        throw new \Exception("bad");
                    }

                    foo(new C);',
            ],
            'extendIteratorIterator' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     * @extends IteratorIterator<TKey, TValue, Traversable<TKey, TValue>>
                     */
                    class SomeIterator extends IteratorIterator {}',
            ],
            'SKIPPED-suppressMismatch' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @return int
                         */
                        public function check();
                    }

                    class C implements I
                    {
                        /**
                         * @psalm-suppress ImplementedReturnTypeMismatch
                         */
                        public function check(): bool
                        {
                            return false;
                        }
                    }',
            ],
            'implementStaticReturn' => [
                'code' => '<?php
                    class A {}
                    interface I {
                      /** @return A */
                      public function foo();
                    }

                    class B extends A implements I {
                      /** @return static */
                      public function foo() {
                        return $this;
                      }
                    }',
            ],
            'implementThisReturn' => [
                'code' => '<?php
                    class A {}
                    interface I {
                      /** @return A */
                      public function foo();
                    }

                    class B extends A implements I {
                      /** @return $this */
                      public function foo() {
                        return $this;
                      }
                    }',
            ],
            'inheritMultipleInterfacesWithDocblocks' => [
                'code' => '<?php
                    interface I1 {
                      /** @return string */
                      public function foo();
                    }
                    interface I2 {
                      /** @return string */
                      public function bar();
                    }
                    class A implements I1, I2 {
                      public function foo() {
                        return "hello";
                      }
                      public function bar() {
                        return "goodbye";
                      }
                    }',
            ],
            'interfaceReturnType' => [
                'code' => '<?php
                    interface A {
                        /** @return string|null */
                        public function blah();
                    }

                    class B implements A {
                        public function blah() {
                            return rand(0, 10) === 4 ? "blah" : null;
                        }
                    }

                    $blah = (new B())->blah();',
            ],
            'interfaceExtendsTraversible' => [
                'code' => '<?php
                    /**
                     * @extends IteratorAggregate<mixed, mixed>
                     * @extends ArrayAccess<mixed, mixed>
                     */
                    interface Collection extends Countable, IteratorAggregate, ArrayAccess {}

                    function takesCollection(Collection $c): void {
                        takesIterable($c);
                    }

                    function takesIterable(iterable $i): void {}',
            ],
            'interfaceInstanceofInterfaceOrClass' => [
                'code' => '<?php
                    interface A {}
                    class B extends Exception {}

                    function foo(Throwable $e): void {
                        if ($e instanceof A || $e instanceof B) {
                            return;
                        }

                        return;
                    }

                    class C extends Exception {}
                    interface D {}

                    function bar(Throwable $e): void {
                        if ($e instanceof C || $e instanceof D) {
                            return;
                        }

                        return;
                    }',
            ],
            'filterIteratorExtension' => [
                'code' => '<?php
                    /**
                     * @extends Iterator<mixed, mixed>
                     */
                    interface I2 extends Iterator {}

                    /**
                     * @extends FilterIterator<mixed, mixed, Iterator<mixed, mixed>>
                     */
                    class DedupeIterator extends FilterIterator {
                        public function __construct(I2 $i) {
                            parent::__construct($i);
                        }

                        public function accept() : bool {
                            return true;
                        }
                    }',
            ],
            'interfacInstanceMayContainOtherInterfaceInstance' => [
                'code' => '<?php
                    interface I1 {}
                    interface I2 {}
                    class C implements I1,I2 {}

                    function f(I1 $a, I2 $b): bool {
                        return $a === $b;
                    }

                    /**
                     * @param  array<I1> $a
                     * @param  array<I2> $b
                     */
                    function g(array $a, array $b): bool {
                        return $a === $b;
                    }

                    $o = new C;
                    f($o, $o);',
            ],
            'interfacePropertyIntersection' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $a;
                    }

                    class B extends A implements I {}

                    interface I {}

                    function takeI(I $i) : void {
                        if ($i instanceof A) {
                            echo $i->a;
                            $i->a = "hello";
                        }
                    }',
            ],
            'interfacePropertyIntersectionMockPropertyAccess' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        private $a;
                    }

                    /** @psalm-override-property-visibility */
                    interface I {}

                    function takeI(I $i) : void {
                        if ($i instanceof A) {
                            echo $i->a;
                            $i->a = "hello";
                        }
                    }',
            ],
            'interfacePropertyIntersectionMockMethodAccess' => [
                'code' => '<?php
                    class A {
                        private function foo() : void {}
                    }

                    /** @psalm-override-method-visibility */
                    interface I {}

                    function takeI(I $i) : void {
                        if ($i instanceof A) {
                            $i->foo();
                        }
                    }

                    function takeA(A $a) : void {
                        if ($a instanceof I) {
                            $a->foo();
                        }
                    }',
            ],
            'docblockParamInheritance' => [
                'code' => '<?php
                    interface I {
                        /** @param string[] $f */
                        function foo(array $f) : void {}
                    }

                    class C implements I {
                        /** @var string[] */
                        private $f = [];

                        /**
                         * {@inheritdoc}
                         */
                        public function foo(array $f) : void {
                            $this->f = $f;
                        }
                    }

                    class C2 implements I {
                        /** @var string[] */
                        private $f = [];

                        /**
                         * {@inheritDoc}
                         */
                        public function foo(array $f) : void {
                            $this->f = $f;
                        }
                    }',
            ],
            'allowStaticCallOnInterfaceMethod' => [
                'code' => '<?php
                    interface IFoo {
                        public static function doFoo() : void;
                    }

                    function bar(IFoo $i) : void {
                        $i::doFoo();
                    }',
            ],
            'SKIPPED-inheritSystemInterface' => [
                'code' => '<?php
                    interface I extends \RecursiveIterator {}

                    function f(I $c): void {
                        $c->current();
                    }',
            ],
            'intersectMixedTypes' => [
                'code' => '<?php
                    interface IFoo {
                        function foo() : string;
                    }

                    interface IBar {
                        function foo() : string;
                    }

                    /** @param IFoo&IBar $i */
                    function iFooFirst($i) : string {
                        return $i->foo();
                    }

                    /** @param IBar&IFoo $i */
                    function iBarFirst($i) : string {
                        return $i->foo();
                    }',
            ],
            'intersectionObjectTypes' => [
                'code' => '<?php

                    class C {}

                    interface IFoo {
                        function foo() : object;
                    }

                    interface IBar {
                        function foo() : C;
                    }

                    /** @param IFoo&IBar $i */
                    function iFooFirst($i) : C {
                        return $i->foo();
                    }

                    /** @param IBar&IFoo $i */
                    function iBarFirst($i) : C {
                        return $i->foo();
                    }',
            ],
            'noTypeCoercionWhenIntersectionMatches' => [
                'code' => '<?php
                    interface I1 {}
                    interface I2 {}
                    class A implements I1 {}

                    /** @param A|I2 $i */
                    function foo($i) : void {}

                    /** @param I1&I2 $i */
                    function bar($i) : void {
                        foo($i);
                    }',
            ],
            'intersectIterators' => [
                'code' => '<?php
                    interface A {} function takesA(A $p): void {}
                    interface B {} function takesB(B $p): void {}

                    /** @psalm-param iterable<A>&iterable<B> $i */
                    function takesIntersectionOfIterables(iterable $i): void {
                        foreach ($i as $c) {
                            takesA($c);
                            takesB($c);
                        }
                    }

                    /** @psalm-param iterable<A&B> $i */
                    function takesIterableOfIntersections(iterable $i): void {
                        foreach ($i as $c) {
                            takesA($c);
                            takesB($c);
                        }
                    }',
            ],
            'inheritDocFromObviousInterface' => [
                'code' => '<?php
                    interface I1 {
                        /**
                         * @param string $type
                         * @return bool
                         */
                        public function takesString($type);
                    }

                    interface I2 extends I1 {
                        public function takesString($type);
                    }

                    class C implements I2 {
                        public function takesString($type) {
                            return true;
                        }
                    }',
            ],
            'correctClassCasing' => [
                'code' => '<?php
                    interface F {
                        /** @return static */
                        public function m(): self;
                    }

                    abstract class G implements F {}

                    class H extends G {
                        public function m(): F {
                            return $this;
                        }
                    }

                    function f1(F $f) : void {
                        $f->m()->m();
                    }

                    function f2(G $f) : void {
                        $f->m()->m();
                    }

                    function f3(H $f) : void {
                        $f->m()->m();
                    }',
            ],
            'dontModifyAfterUnnecessaryAssertion' => [
                'code' => '<?php
                    class A {}
                    interface I {}

                    /**
                     * @param A&I $a
                     * @return A&I
                     */
                    function foo(I $a) {
                        /** @psalm-suppress RedundantConditionGivenDocblockType */
                        assert($a instanceof A);
                        return $a;
                    }',
            ],
            'interfaceAssertionOnClassInterfaceUnion' => [
                'code' => '<?php
                    class SomeClass {}

                    interface SomeInterface {
                        public function doStuff(): void;
                    }

                    function takesAorB(SomeClass|SomeInterface $some): void {
                        if ($some instanceof SomeInterface) {
                            $some->doStuff();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidInterface' => [
                'code' => '<?php
                    class C2 implements A { }',
                'error_message' => 'UndefinedClass',
            ],
            'noInterfacePropertyFetch' => [
                'code' => '<?php
                    interface A { }

                    function fooFoo(A $a): void {
                        if ($a->bar) {

                        }
                    }',
                'error_message' => 'NoInterfaceProperties',
            ],
            'noInterfacePropertyAssignment' => [
                'code' => '<?php
                    interface A { }

                    function fooFoo(A $a): void {
                        $a->bar = 5;
                    }',
                'error_message' => 'NoInterfaceProperties',
            ],
            'unimplementedInterfaceMethod' => [
                'code' => '<?php
                    interface A {
                        public function fooFoo() : void;
                    }

                    class B implements A { }',
                'error_message' => 'UnimplementedInterfaceMethod',
            ],
            'mismatchingInterfaceMethodSignature' => [
                'code' => '<?php
                    interface A {
                        public function fooFoo(int $a): void;
                    }

                    class B implements A {
                        public function fooFoo(string $a): void {

                        }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingInterfaceMethodSignatureInTrait' => [
                'code' => '<?php
                    interface A {
                        public function fooFoo(int $a, int $b): void;
                    }

                    trait T {
                        public function fooFoo(int $a): void {
                        }
                    }

                    class B implements A {
                        use T;
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingInterfaceMethodSignatureInImplementer' => [
                'code' => '<?php
                    interface A {
                        public function fooFoo(int $a, int $b): void;
                    }

                    trait T {
                        public function fooFoo(int $a, int $b): void {
                        }
                    }

                    class B implements A {
                        use T;

                        public function fooFoo(int $a): void {
                        }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingReturnTypes' => [
                'code' => '<?php
                    interface I1 {
                      public function foo(): string;
                    }
                    interface I2 {
                      public function foo(): int;
                    }
                    class A implements I1, I2 {
                      public function foo(): string {
                        return "hello";
                      }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingDocblockReturnTypes' => [
                'code' => '<?php
                    interface I1 {
                      /** @return string */
                      public function foo();
                    }
                    interface I2 {
                      /** @return int */
                      public function foo();
                    }
                    class A implements I1, I2 {
                      /** @return string */
                      public function foo() {
                        return "hello";
                      }
                    }',
                'error_message' => 'ImplementedReturnTypeMismatch',
            ],
            'abstractInterfaceImplementsButCallUndefinedMethod' => [
                'code' => '<?php
                    interface I {
                        public function foo() : void;
                    }

                    abstract class A implements I {
                        public function bar(): void {
                            $this->foo2();
                        }
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'abstractInterfaceImplementsWithSubclass' => [
                'code' => '<?php
                    interface I {
                        public function fnc() : void;
                    }

                    abstract class A implements I {}

                    class B extends A {}',
                'error_message' => 'UnimplementedInterfaceMethod',
            ],
            'lessSpecificReturnStatement' => [
                'code' => '<?php
                    interface A {}
                    interface B extends A {}

                    function foo(A $a): B {
                        return $a;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'interfaceInstanceofAndTwoReturns' => [
                'code' => '<?php
                    interface A {}
                    interface B {}

                    class C implements A, B {}

                    function foo(A $i): B {
                        if ($i instanceof B) {
                            return $i;
                        }

                        return $i;
                    }

                    foo(new C);',
                'error_message' => 'InvalidReturnStatement',
            ],
            'deprecatedInterface' => [
                'code' => '<?php
                    /** @deprecated */
                    interface Container {}

                    class A implements Container {}',
                'error_message' => 'DeprecatedInterface',
            ],
            'inheritMultipleInterfacesWithConflictingDocblocks' => [
                'code' => '<?php
                    interface I1 {
                        /** @return string */
                        public function foo();
                    }
                    interface I2 {
                        /** @return int */
                        public function foo();
                    }
                    class A implements I1, I2 {
                        public function foo() {
                            return "hello";
                        }
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'interfaceInstantiation' => [
                'code' => '<?php
                    interface myInterface{}
                    new myInterface();',
                'error_message' => 'InterfaceInstantiation',
            ],
            'nonStaticInterfaceMethod' => [
                'code' => '<?php
                    interface I {
                        public static function m(): void;
                    }
                    class C implements I {
                        public function m(): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'staticInterfaceCall' => [
                'code' => '<?php
                    interface Foo {
                        public static function doFoo();
                    }

                    Foo::doFoo();',
                'error_message' => 'UndefinedClass',
            ],
            'missingReturnType' => [
                'code' => '<?php
                    interface foo {
                        public function withoutAnyReturnType();
                    }',
                'error_message' => 'MissingReturnType',
            ],
            'missingParamType' => [
                'code' => '<?php
                    interface foo {
                        public function withoutAnyReturnType($s) : void;
                    }',
                'error_message' => 'MissingParamType',
            ],
            'missingTemplateExtendsInterface' => [
                'code' => '<?php
                    /** @template T */
                    interface A {}
                    interface B extends A {}
                ',
                'error_message' => 'MissingTemplateParam',
            ],
            'missingTemplateExtendsNativeInterface' => [
                'code' => '<?php
                    interface a extends Iterator {
                    }
                ',
                'error_message' => 'MissingTemplateParam',
            ],
            'missingTemplateExtendsNativeMultipleInterface' => [
                'code' => '<?php
                    /**
                     * @extends Iterator<mixed, mixed>
                     */
                    interface a extends Iterator, Traversable {
                    }
                ',
                'error_message' => 'MissingTemplateParam',
            ],
            'reconcileAfterClassInstanceof' => [
                'code' => '<?php
                    interface Base {}

                    class E implements Base {
                        public function bar() : void {}
                    }

                    function foobar(Base $foo) : void {
                        if ($foo instanceof E) {
                            $foo->bar();
                        }

                        $foo->bar();
                    }',
                'error_message' => 'UndefinedInterfaceMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:31',
            ],
            'reconcileAfterInterfaceInstanceof' => [
                'code' => '<?php
                    interface Base {}

                    interface E extends Base {
                        public function bar() : void;
                    }

                    function foobar(Base $foo) : void {
                        if ($foo instanceof E) {
                            $foo->bar();
                        }

                        $foo->bar();
                    }',
                'error_message' => 'UndefinedInterfaceMethod - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:31',
            ],
        ];
    }
}
