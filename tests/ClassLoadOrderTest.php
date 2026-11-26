<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ClassLoadOrderTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
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
            'deferredReferenceNotEvaluatedYet' => [
                'code' => '<?php
                    class B {
                        const C = A;
                        const D = 1;

                        public static function m(): int {
                            return A;
                        }
                    }

                    function f(): B {
                        return new B;
                    }

                    $d = B::D;
                    $class = B::class;
                    $f = fn(): int => B::C;
                    $is_b = $d instanceof B;
                    $is_five = defined("A") && A === 5;

                    const A = 5;

                    $m = B::m();',
            ],
            'deferredReferenceFallsBackToGlobalConstant' => [
                'code' => '<?php
                    namespace Ns;

                    class B {
                        const C = PHP_EOL;
                    }

                    new B;

                    const PHP_EOL = "x";',
            ],
            'deferredReferenceDefinedWithDefine' => [
                'code' => '<?php
                    class B {
                        const C = A;
                    }

                    function init(): void {
                        define("A", 1);
                    }

                    init();
                    new B;
                    define("A", 2);',
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

                    /** @psalm-no-seal-properties */
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

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
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
            'invalidDeferredReference' => [
                'code' => '<?php
                    class B {
                        const C = A;
                    }

                    $b = (new B);

                    const A = 5;',
                'error_message' => 'UndefinedConstant - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:27 - Const A is not defined before line 8, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceInConstantFetch' => [
                'code' => '<?php
                    class B {
                        const C = A;
                    }

                    $c = B::C;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 8, where it is declared, and B::C needs it',
            ],
            'invalidDeferredReferenceInPropertyDefault' => [
                'code' => '<?php
                    class B {
                        /** @var int */
                        public $p = A;
                    }

                    new B;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 9, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceInStaticPropertyFetch' => [
                'code' => '<?php
                    class B {
                        const C = A;

                        /** @var int */
                        public static $s = 1;
                    }

                    $s = B::$s;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 11, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceInParentClass' => [
                'code' => '<?php
                    class P {
                        const C = A;
                    }

                    class B extends P {}

                    new B;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 10, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceInInterface' => [
                'code' => '<?php
                    interface I {
                        const C = A;
                    }

                    class B implements I {}

                    new B;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 10, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceInTrait' => [
                'code' => '<?php
                    trait T {
                        /** @var int */
                        public $p = A;
                    }

                    class B {
                        use T;
                    }

                    new B;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 13, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceThroughOtherConstants' => [
                'code' => '<?php
                    class O {
                        const X = [A];
                    }

                    class B {
                        const C = self::D;
                        const D = O::X;
                    }

                    $c = B::C;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 13, where it is declared, and B::C needs it',
            ],
            'invalidDeferredReferenceToClassDeclaredLater' => [
                'code' => '<?php
                    $b = new B;

                    const A = 5;

                    class B {
                        const C = A;
                    }',
                'error_message' => 'Const A is not defined before line 4, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceInBranch' => [
                'code' => '<?php
                    class B {
                        const C = A;
                    }

                    if (rand(0, 1)) {
                        new B;
                    }

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 10, where it is declared, and class B needs it',
            ],
            'invalidDeferredReferenceInNamespace' => [
                'code' => '<?php
                    namespace Ns;

                    class B {
                        const C = A;
                    }

                    new B;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 10, where it is declared, and class Ns\\B needs it',
            ],
            'invalidDeferredGlobalConstantFetch' => [
                'code' => '<?php
                    echo A;

                    const A = 5;',
                'error_message' => 'UndefinedConstant - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:26 - Const A is not defined before line 4, where it is declared',
            ],
            'invalidDeferredGlobalConstantFetchFromNamespace' => [
                'code' => '<?php
                    namespace Ns {
                        echo A;
                    }

                    namespace {
                        const A = 5;
                    }',
                'error_message' => 'Const A is not defined before line 7, where it is declared',
            ],
            'invalidDeferredNamespacedConstantFetch' => [
                'code' => '<?php
                    namespace Ns;

                    echo A;

                    const A = 5;',
                'error_message' => 'Const A is not defined before line 6, where it is declared',
            ],
        ];
    }
}
