<?php
namespace Psalm\Tests;

class InterfaceTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'extendsAndImplements' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    interface A
                    {
                        /**
                         * @return string
                         */
                        public function fooFoo();
                    }

                    interface B extends A
                    {
                        public function barBar();
                    }

                    /** @return void */
                    function mux(B $b) {
                        $b->fooFoo();
                    }',
            ],
            'correctInterfaceMethodSignature' => [
                '<?php
                    interface A {
                        public function fooFoo(int $a): void;
                    }

                    class B implements A {
                        public function fooFoo(int $a): void {

                        }
                    }',
            ],
            'interfaceMethodImplementedInParent' => [
                '<?php
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
                '<?php
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
                '<?php
                    // fails in PHP, whatcha gonna do
                    $c = new C;

                    class A { }

                    interface B { }

                    class C extends A implements B { }',
            ],
            'typeDoesNotContainType' => [
                '<?php
                    interface A { }
                    interface B {
                        function foo();
                    }
                    function bar(A $a): void {
                        if ($a instanceof B) {
                            $a->foo();
                        }
                    }',
            ],
            'abstractInterfaceImplements' => [
                '<?php
                    interface I {
                        public function fnc();
                    }

                    abstract class A implements I {}',
            ],
            'abstractInterfaceImplementsButCallMethod' => [
                '<?php
                    interface I {
                        public function foo();
                    }

                    abstract class A implements I {
                        public function bar(): void {
                            $this->foo();
                        }
                    }',
            ],
            'implementsPartialInterfaceMethods' => [
                '<?php
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
                'error_levels' => ['MissingReturnType'],
            ],
            'interfaceConstants' => [
                '<?php
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
                '<?php
                    interface A {}
                    interface B extends A {}

                    function foo(B $a): A {
                        return $a;
                    }',
            ],
            'interfaceInstanceofReturningInitial' => [
                '<?php
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
                '<?php
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
                '<?php
                    class SomeIterator extends IteratorIterator {}',
            ],
            'suppressMismatch' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    interface Collection extends Countable, IteratorAggregate, ArrayAccess {}

                    function takesCollection(Collection $c): void {
                        takesIterable($c);
                    }

                    function takesIterable(iterable $i): void {}',
            ],
            'interfaceInstanceofInterfaceOrClass' => [
                '<?php
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
                '<?php
                    interface I2 extends Iterator {}

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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    interface IFoo {
                        public static function doFoo();
                    }

                    function bar(IFoo $i) : void {
                        $i::doFoo();
                    }'
            ],
            'inheritSystemInterface' => [
                '<?php
                    interface I extends \RecursiveIterator {}

                    function f(I $c): void {
                        $c->current();
                    }'
            ],
            'intersectMixedTypes' => [
                '<?php
                    interface IFoo {
                        function foo();
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
                '<?php

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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'invalidInterface' => [
                '<?php
                    class C2 implements A { }',
                'error_message' => 'UndefinedClass',
            ],
            'noInterfacePropertyFetch' => [
                '<?php
                    interface A { }

                    function fooFoo(A $a): void {
                        if ($a->bar) {

                        }
                    }',
                'error_message' => 'NoInterfaceProperties',
            ],
            'noInterfacePropertyAssignment' => [
                '<?php
                    interface A { }

                    function fooFoo(A $a): void {
                        $a->bar = 5;
                    }',
                'error_message' => 'NoInterfaceProperties',
            ],
            'unimplementedInterfaceMethod' => [
                '<?php
                    interface A {
                        public function fooFoo();
                    }

                    class B implements A { }',
                'error_message' => 'UnimplementedInterfaceMethod',
            ],
            'mismatchingInterfaceMethodSignature' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    interface I {
                        public function foo();
                    }

                    abstract class A implements I {
                        public function bar(): void {
                            $this->foo2();
                        }
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'abstractInterfaceImplementsWithSubclass' => [
                '<?php
                    interface I {
                        public function fnc();
                    }

                    abstract class A implements I {}

                    class B extends A {}',
                'error_message' => 'UnimplementedInterfaceMethod',
            ],
            'lessSpecificReturnStatement' => [
                '<?php
                    interface A {}
                    interface B extends A {}

                    function foo(A $a): B {
                        return $a;
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'interfaceInstanceofAndTwoReturns' => [
                '<?php
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
                '<?php
                    /** @deprecated */
                    interface Container {}

                    class A implements Container {}',
                'error_message' => 'DeprecatedInterface',
            ],
            'inheritMultipleInterfacesWithConflictingDocblocks' => [
                '<?php
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
                '<?php
                    interface myInterface{}
                    new myInterface();',
                'error_message' => 'InterfaceInstantiation',
            ],
            'nonStaticInterfaceMethod' => [
                '<?php
                    interface I {
                        public static function m(): void;
                    }
                    class C implements I {
                        public function m(): void {}
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'staticInterfaceCall' => [
                '<?php
                    interface Foo {
                        public static function doFoo();
                    }

                    Foo::doFoo();',
                'error_message' => 'UndefinedClass',
            ],
        ];
    }
}
