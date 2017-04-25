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
            'singleFileInheritance' => [
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
            'constSandwich' => [
                '<?php
                    class A { const B = 42;}
                    $a = A::B;
                    class C {}'
            ],
            'deferredReference' => [
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
            'moreCyclicalReferences' => [
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
            'referenceToSubclassInMethod' => [
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
            'referenceToClassInMethod' => [
                '<?php
                    class A {
                        public function b(A $b) : void {
                            $b->b(new A());
                        }
                    }'
            ],
            'overrideProtectedAccessLevelToPublic' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {}
                    }
            
                    class B extends A {
                        public function fooFoo() : void {}
                    }'
            ],
            'reflectedParents' => [
                '<?php
                    $e = rand(0, 10)
                      ? new RuntimeException("m")
                      : null;
            
                    if ($e instanceof Exception) {
                      echo "good";
                    }'
            ],
            'namespacedAliasedClassCall' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Bee {
                        use Aye as A;
            
                        new A\Foo();
                    }'
            ],
            'abstractExtendsAbstract' => [
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
            'missingParentWithFunction' => [
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
            'classTraversal' => [
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
            'subclassWithSimplerArg' => [
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
            'PHP7-subclassOfInvalidArgumentExceptionWithSimplerArg' => [
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
            'undefinedClass' => [
                '<?php
                    (new Foo());',
                'error_message' => 'UndefinedClass'
            ],
            'wrongCaseClass' => [
                '<?php
                    class Foo {}
                    (new foo());',
                'error_message' => 'InvalidClass'
            ],
            'invalidThisFetch' => [
                '<?php
                    echo $this;',
                'error_message' => 'InvalidScope'
            ],
            'invalidThisArgument' => [
                '<?php
                    $this = "hello";',
                'error_message' => 'InvalidScope'
            ],
            'undefinedConstant' => [
                '<?php
                    echo HELLO;',
                'error_message' => 'UndefinedConstant'
            ],
            'undefinedClassConstant' => [
                '<?php
                    class A {}
                    echo A::HELLO;',
                'error_message' => 'UndefinedConstant'
            ],
            // Skipped. A bug.
            'SKIPPED-inheritanceLoopOne' => [
                '<?php
                    class C extends C {}',
                'error_message' => 'InvalidParent'
            ],
            // Skipped. A bug.
            'SKIPPED-inheritanceLoopTwo' => [
                '<?php
                    class E extends F {}
                    class F extends E {}',
                'error_message' => 'InvalidParent'
            ],
            // Skipped. A bug.
            'SKIPPED-inheritanceLoopThree' => [
                '<?php
                    class G extends H {}
                    class H extends I {}
                    class I extends G {}',
                'error_message' => 'InvalidParent'
            ],
            'invalidDeferredReference' => [
                '<?php
                    class B {
                        const C = A;
                    }
            
                    $b = (new B);
            
                    const A = 5;',
                'error_message' => 'UndefinedConstant'
            ],
            'overridePublicAccessLevelToPublic' => [
                '<?php
                    class A {
                        public function fooFoo() : void {}
                    }
            
                    class B extends A {
                        private function fooFoo() : void {}
                    }',
                'error_message' => 'OverriddenMethodAccess'
            ],
            'overridePublicAccessLevelToProtected' => [
                '<?php
                    class A {
                        public function fooFoo() : void {}
                    }
            
                    class B extends A {
                        protected function fooFoo() : void {}
                    }',
                'error_message' => 'OverriddenMethodAccess'
            ],
            'overrideProtectedAccessLevelToPrivate' => [
                '<?php
                    class A {
                        protected function fooFoo() : void {}
                    }
            
                    class B extends A {
                        private function fooFoo() : void {}
                    }',
                'error_message' => 'OverriddenMethodAccess'
            ],
            'classRedefinition' => [
                '<?php
                    class Foo {}
                    class Foo {}',
                'error_message' => 'DuplicateClass'
            ],
            'classRedefinitionInNamespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass'
            ],
            'classRedefinitionInSeparateNamespace' => [
                '<?php
                    namespace Aye {
                        class Foo {}
                    }
                    namespace Aye {
                        class Foo {}
                    }',
                'error_message' => 'DuplicateClass'
            ],
            'abstractClassInstantiation' => [
                '<?php
                    abstract class A {}
                    new A();',
                'error_message' => 'AbstractInstantiation'
            ],
            'missingParent' => [
                '<?php
                    class A extends B { }',
                'error_message' => 'UndefinedClass'
            ],
            'moreSpecificReturnType' => [
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
