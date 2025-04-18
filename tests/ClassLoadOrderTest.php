<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ClassLoadOrderTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'singleFileInheritance' => [
                'code' => '<?php
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
                'code' => '<?php
                    class A { const B = 42;}
                    $a = A::B;
                    class C {}',
            ],
            'deferredReference' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class A {
                        public function b(A $b): void {
                            $b->b(new A());
                        }
                    }',
            ],
            'classTraversal' => [
                'code' => '<?php
                    namespace Foo;

                    class A {
                        /** @var string */
                        protected $foo = C::DOPE;

                        /** @return string */
                        public function __get(string $s) {
                            return "foo";
                        }
                    }

                    class B extends A {
                        /** @return void */
                        public function foo() {
                            echo (new C)->bar;
                        }
                    }

                    class C extends B {
                        const DOPE = "dope";
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'inheritanceLoopOne' => [
                'code' => '<?php
                    class C extends C {}',
                'error_message' => 'Circular reference',
            ],
            'inheritanceLoopTwo' => [
                'code' => '<?php
                    class E extends F {}
                    class F extends E {}',
                'error_message' => 'Circular reference',
            ],
            'inheritanceLoopThree' => [
                'code' => '<?php
                    class G extends H {}
                    class H extends I {}
                    class I extends G {}',
                'error_message' => 'Circular reference',
            ],
            'SKIPPED-invalidDeferredReference' => [
                'code' => '<?php
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
