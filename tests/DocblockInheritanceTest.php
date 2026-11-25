<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class DocblockInheritanceTest extends TestCase
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
            'inheritCorrectParamOnTypeChange' => [
                'code' => '<?php
                    class A
                    {
                        /** @param array<int, int>|int $className */
                        public function a(array|int $className): int
                        {
                            return 0;
                        }
                    }

                    class B extends A
                    {
                        /** @param array<int, int>|int|bool $className */
                        public function a(array|int|bool $className): int
                        {
                            return 0;
                        }
                    }

                    print_r((new A)->a(1));
                    print_r((new B)->a(true));
                    ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inheritParentTemplateParamNextToOverrideTemplates' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param int $b
                         * @return T
                         */
                        public function f($a, $b) { return $a; }
                    }

                    final class C extends P {
                        /**
                         * @template U of int
                         * @param U $b
                         */
                        public function f($a, $b) { return $a; }
                    }

                    $r = (new C)->f("s", 1);',
                'assertions' => [
                    '$r===' => '\'s\'',
                ],
            ],
            'returnUsingTemplateOfRedocumentedParamNotInherited' => [
                'code' => '<?php
                    abstract class Container {
                        /**
                         * @template T of object
                         * @param class-string<T> $id
                         * @return T
                         */
                        abstract public function make(string $id): object;
                    }

                    final class MyContainer extends Container {
                        /** @param string $id service id or class name */
                        public function make(string $id): object { return new stdClass(); }
                    }

                    $x = (new MyContainer)->make(stdClass::class);',
                'assertions' => [
                    '$x===' => 'object',
                ],
                'ignored_issues' => ['ImplementedReturnTypeMismatch'],
            ],
            'inheritedReturnTemplateUsedByNoParamIsResolved' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param positive-int $n
                         * @return list<T>
                         */
                        public function empty(int $n): array { return []; }
                    }

                    final class C extends P {
                        /** @param int $n */
                        public function empty(int $n): array { return []; }
                    }

                    $c = (new C)->empty(1);',
                'assertions' => [
                    '$c===' => 'array<never, never>',
                ],
            ],
            'inheritParentTemplateParamsWithoutOwnTemplates' => [
                'code' => '<?php
                    class P {
                        /**
                         * @template T
                         * @param T $a
                         * @param list<T> $b
                         * @return T
                         */
                        public function f($a, array $b, int $c) { return $a; }
                    }

                    final class C extends P {
                        /** @param int $c */
                        public function f($a, array $b, int $c) { return $a; }
                    }

                    $r = (new C)->f(1, [2], 3);',
                'assertions' => [
                    '$r===' => '1|2',
                ],
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
            'codeAfterCallReturningTemplateOfRedocumentedParamIsAnalyzed' => [
                'code' => '<?php
                    abstract class Container {
                        /**
                         * @template T of object
                         * @param class-string<T> $id
                         * @return T
                         */
                        abstract public function make(string $id): object;
                    }

                    final class MyContainer extends Container {
                        /** @param string $id service id or class name */
                        public function make(string $id): object { return new stdClass(); }
                    }

                    function test(MyContainer $c): void {
                        $c->make(stdClass::class);
                        echo strlen([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:18:37',
                'error_levels' => ['ImplementedReturnTypeMismatch'],
            ],
            'codeAfterCallsReturningTemplateOfRedocumentedParamIsAnalyzedWithoutNativeReturn' => [
                'code' => '<?php
                    abstract class Container {
                        /**
                         * @template T of object
                         * @param class-string<T> $id
                         * @param array<string, mixed> $options
                         * @return T
                         */
                        abstract public function make($id, array $options = []);

                        /**
                         * @template T
                         * @param callable(): T $factory
                         * @param positive-int $n
                         * @return T
                         */
                        abstract public function call(callable $factory, int $n);
                    }

                    final class MyContainer extends Container {
                        /** @param string $id service id or class name */
                        public function make($id, array $options = []) { throw new RuntimeException(); }

                        /** @param callable $factory */
                        public function call(callable $factory, int $n) { throw new RuntimeException(); }
                    }

                    function test(MyContainer $c): void {
                        $c->make(stdClass::class);
                        $c->call(fn() => 1, 1);
                        echo strlen([]);
                    }',
                'error_message' => 'InvalidArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:31:37',
            ],
        ];
    }
}
