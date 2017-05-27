<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class InterfaceTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @expectedException        \Psalm\Exception\CodeException
     * @expectedExceptionMessage UndefinedClass
     *
     * @return                   void
     */
    public function testInvalidImplements()
    {
        $this->project_checker->registerFile(
            'somefile.php',
            '<?php
        class C2 implements A { }
        '
        );
        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
    }

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
                        public function fooFoo()
                        {
                            return "hello";
                        }
            
                        public function barBar()
                        {
                            return "goodbye";
                        }
            
                        public function baz()
                        {
                            return "hello again";
                        }
                    }
            
                    $cee = (new D())->baz();
                    $dee = (new D())->fooFoo();',
                'assertions' => [
                    ['string' => '$cee'],
                    ['string' => '$dee'],
                ],
            ],
            'isExtendedInterface' => [
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
                        /**
                         * @return string
                         */
                        public function baz();
                    }
            
                    class C implements B
                    {
                        public function fooFoo()
                        {
                            return "hello";
                        }
            
                        public function baz()
                        {
                            return "goodbye";
                        }
                    }
            
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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
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
        ];
    }
}
