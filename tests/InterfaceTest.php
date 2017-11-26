<?php
namespace Psalm\Tests;

class InterfaceTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
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
                        public function fooFoo(int $a) : void;
                    }

                    class B implements A {
                        public function fooFoo(int $a) : void {

                        }
                    }',
            ],
            'interfaceMethodImplementedInParent' => [
                '<?php
                    interface MyInterface {
                        public function fooFoo(int $a) : void;
                    }

                    class B {
                        public function fooFoo(int $a) : void {

                        }
                    }

                    class C extends B implements MyInterface { }',
            ],
            'interfaceMethodSignatureInTrait' => [
                '<?php
                    interface A {
                        public function fooFoo(int $a, int $b) : void;
                    }

                    trait T {
                        public function fooFoo(int $a, int $b) : void {
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
                    function bar(A $a) : void {
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
                        public function bar() : void {
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

                    function foo(B $a) : A {
                        return $a;
                    }',
            ],
            'SKIPPED-interfaceInstanceof' => [
                '<?php
                    interface A {}
                    interface B {}

                    function foo(A $i) : A {
                        if ($i instanceof B) {}
                        return $i;
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'invalidInterface' => [
                '<?php
                    class C2 implements A { }',
                'error_message' => 'UndefinedClass',
            ],
            'noInterfaceProperties' => [
                '<?php
                    interface A { }

                    function fooFoo(A $a) : void {
                        if ($a->bar) {

                        }
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
                        public function fooFoo(int $a) : void;
                    }

                    class B implements A {
                        public function fooFoo(string $a) : void {

                        }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingInterfaceMethodSignatureInTrait' => [
                '<?php
                    interface A {
                        public function fooFoo(int $a, int $b) : void;
                    }

                    trait T {
                        public function fooFoo(int $a) : void {
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
                        public function fooFoo(int $a, int $b) : void;
                    }

                    trait T {
                        public function fooFoo(int $a, int $b) : void {
                        }
                    }

                    class B implements A {
                        use T;

                        public function fooFoo(int $a) : void {
                        }
                    }',
                'error_message' => 'MethodSignatureMismatch',
            ],
            'mismatchingReturnTypes' => [
                '<?php
                    interface I1 {
                      public function foo() : string;
                    }
                    interface I2 {
                      public function foo() : int;
                    }
                    class A implements I1, I2 {
                      public function foo() : string {
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
                'error_message' => 'InvalidReturnType',
            ],
            'abstractInterfaceImplementsButCallUndefinedMethod' => [
                '<?php
                    interface I {
                        public function foo();
                    }

                    abstract class A implements I {
                        public function bar() : void {
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
            'moreSpecificReturnType' => [
                '<?php
                    interface A {}
                    interface B extends A {}

                    function foo(A $a) : B {
                        return $a;
                    }',
                'error_message' => 'MoreSpecificReturnType',
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
                'error_message' => 'MixedAssignment',
                'error_levels' => [
                    'MissingReturnType',
                ],
            ],
        ];
    }
}
