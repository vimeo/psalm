<?php
namespace Psalm\Tests;

class ClassTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'single-file-inheritance' => [
                '<?php
                    class A extends B {}
            
                    class B {
                        public function fooFoo() : void {
                            $a = new A();
                            $a->barBar();
                        }
            
                        protected function barBar() : void {
                            echo "hello";
                        }
                    }'
            ],
            'const-sandwich' => [
                '<?php
                    class A { const B = 42;}
                    $a = A::B;
                    class C {}'
            ],
            'deferred-reference' => [
                '<?php
                    class B {
                        const C = A;
                    }
            
                    const A = 5;
            
                    $a = B::C;',
                'assertions' => [
                    ['int' => '$a']
                ]
            ],
            'more-cyclical-references' => [
                '<?php
                    class B extends C {
                        public function d() : A {
                            return new A;
                        }
                    }
                    class C {
                        /** @var string */
                        public $p = A::class;
                        public static function e() : void {}
                    }
                    class A extends B {
                        private function f() : void {
                            self::e();
                        }
                    }'
            ],
            'reference-to-subclass-in-method' => [
                '<?php
                    class A {
                        public function b(B $b) : void {
            
                        }
            
                        public function c() : void {
            
                        }
                    }
            
                    class B extends A {
                        public function d() : void {
                            $this->c();
                        }
                    }'
            ],
            'reference-to-class-in-method' => [
                '<?php
                    class A {
                        public function b(A $b) : void {
                            $b->b(new A());
                        }
                    }'
            ],
            'override-protected-access-level-to-public' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {}
                    }
            
                    class B extends A {
                        public function fooFoo() : void {}
                    }'
            ],
            'reflected-parents' => [
                '<?php
                    $e = rand(0, 10)
                      ? new RuntimeException("m")
                      : null;
            
                    if ($e instanceof Exception) {
                      echo "good";
                    }'
            ],
            'namespaced-aliased-class-call' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Bee {
                        use Aye as A;
            
                        new A\Foo();
                    }'
            ],
            'abstract-extends-abstract' => [
                '<?php
                    abstract class A {
                        /** @return void */
                        abstract public function foo();
                    }
            
                    abstract class B extends A {
                        /** @return void */
                        public function bar() {
                            $this->foo();
                        }
                    }'
            ],
            'missing-parent-with-function' => [
                '<?php
                    class B extends C {
                        public function fooA() { }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'UndefinedClass',
                    'MissingReturnType'
                ]
            ],
            'class-traversal' => [
                '<?php
                    namespace Foo;
            
                    class A {
                        /** @var string */
                        protected $foo = C::DOPE;
            
                        /** @return string */
                        public function __get() { }
                    }
            
                    class B extends A {
                        /** @return void */
                        public function foo() {
                            echo (string)(new C)->bar;
                        }
                    }
            
                    class C extends B {
                        const DOPE = "dope";
                    }'
            ],
            'subclass-with-simpler-arg' => [
                '<?php
                    class A {}
                    class B extends A {}
            
                    class E1 {
                        /**
                         * @param A|B|null $a
                         */
                        public function __construct($a) {
                        }
                    }
            
                    class E2 extends E1 {
                        /**
                         * @param A|null $a
                         */
                        public function __construct($a) {
                            parent::__construct($a);
                        }
                    }'
            ],
            'PHP7-subclass-of-invalid-argument-exception-with-simpler-arg' => [
                '<?php
                    class A extends InvalidArgumentException {
                        /**
                         * @param string $message
                         * @param int $code
                         * @param Throwable|null $previous_exception
                         */
                        public function __construct($message, $code, $previous_exception) {
                            parent::__construct($message, $code, $previous_exception);
                        }
                    }'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'undefined-class' => [
                '<?php
                    (new Foo());',
                'error_message' => 'UndefinedClass'
            ],
            'wrong-case-class' => [
                '<?php
                    class Foo {}
                    (new foo());',
                'error_message' => 'InvalidClass'
            ],
            'invalid-this-fetch' => [
                '<?php
                    echo $this;',
                'error_message' => 'InvalidScope'
            ],
            'invalid-this-argument' => [
                '<?php
                    $this = "hello";',
                'error_message' => 'InvalidScope'
            ],
            'undefined-constant' => [
                '<?php
                    echo HELLO;',
                'error_message' => 'UndefinedConstant'
            ],
            'undefined-class-constant' => [
                '<?php
                    class A {}
                    echo A::HELLO;',
                'error_message' => 'UndefinedConstant'
            ],
            // Skipped. A bug.
            'SKIPPED-inheritance-loop-one' => [
                '<?php
                    class C extends C {}',
                'error_message' => 'InvalidParent'
            ],
            // Skipped. A bug.
            'SKIPPED-inheritance-loop-two' => [
                '<?php
                    class E extends F {}
                    class F extends E {}',
                'error_message' => 'InvalidParent'
            ],
            // Skipped. A bug.
            'SKIPPED-inheritance-loop-three' => [
                '<?php
                    class G extends H {}
                    class H extends I {}
                    class I extends G {}',
                'error_message' => 'InvalidParent'
            ],
            'invalid-deferred-reference' => [
                '<?php
                    class B {
                        const C = A;
                    }
            
                    $b = (new B);
            
                    const A = 5;',
                'error_message' => 'UndefinedConstant'
            ],
            'override-public-access-level-to-public' => [
                '<?php
                    class A {
                        public function fooFoo() : void {}
                    }
            
                    class B extends A {
                        private function fooFoo() : void {}
                    }',
                'error_message' => 'OverriddenMethodAccess'
            ],
            'override-public-access-level-to-protected' => [
                '<?php
                    class A {
                        public function fooFoo() : void {}
                    }
            
                    class B extends A {
                        protected function fooFoo() : void {}
                    }',
                'error_message' => 'OverriddenMethodAccess'
            ],
            'override-protected-access-level-to-private' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {}
                    }
            
                    class B extends A {
                        private function fooFoo() : void {}
                    }',
                'error_message' => 'OverriddenMethodAccess'
            ],
            'class-redefinition' => [
                '<?php
                    class Foo {}
                    class Foo {}',
                'error_message' => 'DuplicateClass'
            ],
            'class-redefinition-in-namespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass'
            ],
            'class-redefinition-in-separate-namespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Aye {
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass'
            ],
            'abstract-class-instantiation' => [
                '<?php
                    abstract class A {}
                    new A();',
                'error_message' => 'AbstractInstantiation'
            ],
            'missing-parent' => [
                '<?php
                    class A extends B { }',
                'error_message' => 'UndefinedClass'
            ],
            'more-specific-return-type' => [
                '<?php
                    class A {}
                    class B extends A {}
            
                    function foo(A $a) : B {
                        return $a;
                    }',
                'error_message' => 'MoreSpecificReturnType'
            ]
        ];
    }
}
