<?php
namespace Psalm\Tests;

class ClassLoadOrderTest extends TestCase
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
                        public function fooFoo(): void {
                            $a = new A();
                            $a->barBar();
                        }

                        protected function barBar(): void {
                            echo "hello";
                        }
                    }',
            ],
            'constSandwich' => [
                '<?php
                    class A { const B = 42;}
                    $a = A::B;
                    class C {}',
            ],
            'SKIPPED-deferredReference' => [
                '<?php
                    class B {
                        const C = A;
                    }

                    const A = 5;

                    $a = B::C;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'moreCyclicalReferences' => [
                '<?php
                    class B extends C {
                        public function d(): A {
                            return new A;
                        }
                    }
                    class C {
                        /** @var string */
                        public $p = A::class;
                        public static function e(): void {}
                    }
                    class A extends B {
                        private function f(): void {
                            self::e();
                        }
                    }',
            ],
            'referenceToSubclassInMethod' => [
                '<?php
                    class A {
                        public function b(B $b): void {

                        }

                        public function c(): void {

                        }
                    }

                    class B extends A {
                        public function d(): void {
                            $this->c();
                        }
                    }',
            ],
            'referenceToClassInMethod' => [
                '<?php
                    class A {
                        public function b(A $b): void {
                            $b->b(new A());
                        }
                    }',
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
            'inheritanceLoopOne' => [
                '<?php
                    class C extends C {}',
                'error_message' => 'Circular reference',
            ],
            'inheritanceLoopTwo' => [
                '<?php
                    class E extends F {}
                    class F extends E {}',
                'error_message' => 'Circular reference',
            ],
            'inheritanceLoopThree' => [
                '<?php
                    class G extends H {}
                    class H extends I {}
                    class I extends G {}',
                'error_message' => 'Circular reference',
            ],
            'SKIPPED-invalidDeferredReference' => [
                '<?php
                    class B {
                        const C = A;
                    }

                    $b = (new B);

                    const A = 5;',
                'error_message' => 'UndefinedConstant',
            ],
        ];
    }
}
