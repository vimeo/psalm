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
        ];
    }
}
