<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class MixinsDeepTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

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
        ];
    }
}
