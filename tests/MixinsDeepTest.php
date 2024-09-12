<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\InvalidCodeAnalysisWithIssuesTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class MixinsDeepTest extends TestCase
{
    use InvalidCodeAnalysisWithIssuesTestTrait;
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'NamedMixinsWithoutT_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    abstract class Foo {
                        abstract public function getString(): string;
                    }

                    /**
                     * @mixin Foo
                     */
                    abstract class Bar {
                        abstract public function getInt(): int;
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin Bar
                     */
                    class Baz {
                        public function __call(string $name, array $arguments) {}
                    }

                    $baz = new Baz();
                    $a = $baz->getString();
                    $b = $baz->getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'NamedMixinsWithoutT_WithStaticMethods' => [
                'code' => <<<'PHP'
                    <?php
                    abstract class Foo {
                        public static function getString(): string {}
                    }

                    /**
                     * @mixin Foo
                     */
                    abstract class Bar {
                        public static function getInt(): int {}
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin Bar
                     */
                    class Baz {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    /** @mixin Baz */
                    class Bat {
                        public static function __callStatic(string $name, array $arguments) {}
                    }
                    $a = Bat::getString();
                    $b = Bat::getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
                'ignored_issues' => ['InvalidReturnType'],
            ],
            'NamedMixinsWithoutT_WithObjectProperties' => [
                'code' => <<<'PHP'
                    <?php
                    abstract class Foo {
                        public string $propString = 'hello';
                    }

                    /**
                     * @mixin Foo
                     */
                    abstract class Bar {
                        public int $propInt = 123;
                        
                        public function __get(string $name) {}
                    }

                    /**
                     * @mixin Bar
                     */
                    class Baz {
                        public function __get(string $name) {}
                    }

                    $baz = new Baz();
                    $a = $baz->propString;
                    $b = $baz->propInt;
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'NamedMixinsWithT_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @template T
                     */
                    abstract class Foo {
                        /**
                         * @return T
                         */
                        abstract public function getString();
                    }

                    /**
                     * @template T1
                     * @template T2
                     * @mixin Foo<T1>
                     */
                    abstract class Bar {
                        /**
                         * @return T2
                         */
                        abstract public function getInt();

                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @template T1
                     * @template T2
                     * @mixin Bar<T1, T2>
                     */
                    class Baz {
                        public function __call(string $name, array $arguments) {}
                    }

                    /** @var Baz<string, int> */
                    $baz = new Baz();
                    $a = $baz->getString();
                    $b = $baz->getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'NamedMixinsWithT_WithStaticMethods' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @template T
                     */
                    abstract class Foo {
                        /**
                         * @return T
                         */
                        public static function getString() {}
                    }

                    /**
                     * @template T1
                     * @template T2
                     * @mixin Foo<T1>
                     */
                    abstract class Bar {
                        /**
                         * @return T2
                         */
                        public static function getInt() {}

                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    /**
                     * @template T1
                     * @template T2
                     * @mixin Bar<T1, T2>
                     */
                    class Baz {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    /** @mixin Baz<string, int> */
                    class Bat {
                        public static function __callStatic(string $name, array $arguments) {}
                    }
                    $a = Bat::getString();
                    $b = Bat::getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
                'ignored_issues' => ['InvalidReturnType'],
            ],
            'TemplatedMixins_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    abstract class Foo {
                        abstract public function getString(): string;
                    }

                    /**
                     * @template T
                     * @mixin T
                     */
                    abstract class Bar {
                        abstract public function getInt(): int;
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @template T
                     * @mixin T
                     */
                    class Baz {
                        public function __call(string $name, array $arguments) {}
                    }

                    /** @var Baz<Bar<Foo>> */
                    $baz = new Baz();
                    $a = $baz->getString();
                    $b = $baz->getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'CombineNamedAndTemplatedMixins_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    abstract class Foo {
                        abstract public function getString(): string;
                    }

                    /**
                     * @template T
                     * @mixin T
                     */
                    abstract class Bar {
                        abstract public function getInt(): int;
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @template T
                     * @mixin Bar<T>
                     */
                    class Baz {
                        public function __call(string $name, array $arguments) {}
                    }

                    /** @var Baz<Foo> */
                    $baz = new Baz();
                    $a = $baz->getString();
                    $b = $baz->getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'CombineTemplatedAndNamedMixinsWithoutT_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    abstract class Foo {
                        abstract public function getString(): string;
                    }

                    /**
                     * @mixin Foo
                     */
                    abstract class Bar {
                        abstract public function getInt(): int;
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @template T
                     * @mixin T
                     */
                    class Baz {
                        public function __call(string $name, array $arguments) {}
                    }

                    /** @var Baz<Bar> $baz */
                    $baz = new Baz();
                    $a = $baz->getString();
                    $b = $baz->getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'CombineTemplatedAndNamedMixinsWithT_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @template T
                     */
                    abstract class Foo {
                        /**
                         * @return T
                         */
                        abstract public function getString();
                    }

                    /**
                     * @mixin Foo<string>
                     */
                    abstract class Bar {
                        abstract public function getInt(): int;
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @template T
                     * @mixin T
                     */
                    class Baz {
                        public function __call(string $name, array $arguments) {}
                    }

                    /** @var Baz<Bar> $baz */
                    $baz = new Baz();
                    $a = $baz->getString();
                    $b = $baz->getInt();
                    PHP,
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'LowMixinCollision_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @mixin Foo
                     */
                    class Foo {
                        public function __call(string $name, array $arguments) {}
                    }

                    $foo = new Foo();
                    $a = $foo->notExistsMethod();
                    PHP,
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'DeepMixinCollision_WithObjectMethods' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @mixin Baz
                     */
                    abstract class Foo {
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin Foo
                     */
                    abstract class Bar {
                        public function __call(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin Bar
                     */
                    class Baz {
                        public function __call(string $name, array $arguments) {}
                    }

                    $baz = new Baz();
                    $a = $baz->notExistsMethod();
                    PHP,
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'LowMixinCollision_WithStaticMethods' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @mixin Foo
                     */
                    class Foo {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    $a = Foo::notExistsMethod();
                    PHP,
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'DeepMixinCollision_WithStaticMethods' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @mixin Baz
                     */
                    abstract class Foo {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin Foo
                     */
                    abstract class Bar {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    /**
                     * @mixin Bar
                     */
                    class Baz {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    $a = Baz::notExistsMethod();
                    PHP,
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'LowMixinCollision_WithProperties' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @mixin Foo
                     */
                    class Foo {
                        public function __get(string $name) {}
                    }

                    $foo = new Foo();
                    $a = $foo->notExistsProp;
                    PHP,
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'DeepMixinCollision_WithProperties' => [
                'code' => <<<'PHP'
                    <?php
                    /**
                     * @mixin Baz
                     */
                    abstract class Foo {
                        public function __get(string $name) {}
                    }

                    /**
                     * @mixin Foo
                     */
                    abstract class Bar {
                        public function __get(string $name) {}
                    }

                    /**
                     * @mixin Bar
                     */
                    class Baz {
                        public function __get(string $name) {}
                    }

                    $baz = new Baz();
                    $a = $baz->notExistsProp;
                    PHP,
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'undefinedMixinClass' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /** @mixin B */
                    class A {}
                    /** @mixin C */
                    class B {}',
                'error_message' => 'UndefinedDocblockClass',
            ],
            'undefinedMixinClassWithPropertyFetch' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /** @mixin B */
                    class A {}
                    /** @mixin C */
                    class B {}

                    (new A)->foo;',
                'error_message' => 'UndefinedPropertyFetch',
            ],
            'undefinedMixinClassWithPropertyFetch_WithMagicMethod' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /**
                     * @property string $baz
                     * @mixin B
                     */
                    class A {
                        public function __get(string $name): string {
                            return "";
                        }
                    }
                    /**
                     * @property string $bar
                     * @mixin C
                     */
                    class B {
                        public function __get(string $name): string {
                            return "";
                        }
                    }

                    (new A)->foo;',
                'error_message' => 'UndefinedMagicPropertyFetch',
            ],
            'undefinedMixinClassWithPropertyAssignment' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /** @mixin B */
                    class A {}
                    /** @mixin C */
                    class B {}

                    (new A)->foo = "bar";',
                'error_message' => 'UndefinedPropertyAssignment',
            ],
            'undefinedMixinClassWithPropertyAssignment_WithMagicMethod' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /**
                     * @property string $baz
                     * @mixin B
                     */
                    class A {
                        public function __set(string $name, string $value) {}
                    }
                    /**
                     * @property string $bar
                     * @mixin C
                     */
                    class B {
                        public function __set(string $name, string $value) {}
                    }

                    (new A)->foo = "bar";',
                'error_message' => 'UndefinedMagicPropertyAssignment',
            ],
            'undefinedMixinClassWithMethodCall' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /** @mixin B */
                    class A {}
                    /** @mixin C */
                    class B {}

                    (new A)->foo();',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedMixinClassWithMethodCall_WithMagicMethod' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /**
                     * @method baz()
                     * @mixin B
                     */
                    class A {
                        public function __call(string $name, array $arguments) {}
                    }
                    /**
                     * @method bar()
                     * @mixin C
                     */
                    class B {
                        public function __call(string $name, array $arguments) {}
                    }

                    (new A)->foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
            'undefinedMixinClassWithStaticMethodCall' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /** @mixin B */
                    class A {}
                    /** @mixin C */
                    class B {}

                    A::foo();',
                'error_message' => 'UndefinedMethod',
            ],
            'undefinedMixinClassWithStaticMethodCall_WithMagicMethod' => [
                // Similar test in MixinAnnotationTest.php
                'code' => '<?php
                    /**
                     * @method baz()
                     * @mixin B
                     */
                    class A {
                        public static function __callStatic(string $name, array $arguments) {}
                    }
                    /**
                     * @method bar()
                     * @mixin C
                     */
                    class B {
                        public static function __callStatic(string $name, array $arguments) {}
                    }

                    A::foo();',
                'error_message' => 'UndefinedMagicMethod',
            ],
        ];
    }
}
