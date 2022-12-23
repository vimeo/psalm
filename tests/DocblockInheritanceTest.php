<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class DocblockInheritanceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'inheritParentReturnDocbblock' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @return int[]
                         */
                        public function doFoo() {
                            return [1, 2, 3];
                        }
                    }

                    class Bar extends Foo {
                        public function doFoo(): array {
                            return [4, 5, 6];
                        }
                    }

                    $b = (new Bar)->doFoo();',
                'assertions' => [
                    '$b' => 'array<array-key, int>',
                ],
            ],
            'inheritedSelfAnnotation' => [
                'code' => '<?php
                    interface I {
                        /**
                         * @param self $i
                         * @return self
                         */
                        function foo(self $i) : self;
                    }

                    class C implements I {
                        public function foo(I $i) : I {
                            return $i;
                        }
                    }

                    function takeI(I $i) : I {
                        return (new C)->foo($i);
                    }',
            ],
            'inheritTwice' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @return string[]
                         */
                        public function aa() {
                            return [];
                        }
                    }

                    class Bar extends Foo {
                        public function aa() {
                            return [];
                        }
                    }

                    class Baz extends Bar {
                        public function aa() {
                            return [];
                        }
                    }',
            ],
            'inheritTwiceWithArrayType' => [
                'code' => '<?php
                    class Foo {
                        /**
                         * @return string[]
                         */
                        public function aa() : array {
                            return [];
                        }
                    }

                    class Bar extends Foo {
                        public function aa() : array {
                            return [];
                        }
                    }

                    class Baz extends Bar {
                        public function aa() : array {
                            return [];
                        }
                    }',
            ],
            'inheritCorrectReturnTypeOnInterface' => [
                'code' => '<?php
                    interface A {
                        /**
                         * @return A
                         */
                        public function map(): A;
                    }

                    interface B extends A {
                        /**
                         * @return B
                         */
                        public function map(): A;
                    }

                    function takesB(B $f) : B {
                        return $f->map();
                    }',
            ],
            'inheritCorrectReturnTypeOnClass' => [
                'code' => '<?php
                    interface A {
                        /**
                         * @return A
                         */
                        public function map(): A;
                    }

                    interface B extends A {
                        /**
                         * @return B
                         */
                        public function map(): A;
                    }

                    class F implements B {
                        public function map(): A {
                            return new F();
                        }
                    }

                    function takesF(F $f) : B {
                        return $f->map();
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'automaticInheritDoc' => [
                'code' => '<?php
                    class Y {
                        /**
                         * @param string[] $arr
                         */
                        public function boo(array $arr) : void {}
                    }

                    class X extends Y {
                        public function boo(array $arr) : void {}
                    }

                    (new X())->boo([1, 2]);',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
