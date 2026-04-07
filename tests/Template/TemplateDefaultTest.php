<?php

declare(strict_types=1);

namespace Psalm\Tests\Template;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class TemplateDefaultTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'classTemplateDefaultBasic' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): string {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultWithBound' => [
                'code' => '<?php
                    /**
                     * @template T of string = "hello"
                     */
                    class Foo {
                        /** @return T */
                        public function get(): string {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): string {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultExplicitOverride' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo<int> $foo */
                    function test(Foo $foo): int {
                        return $foo->get();
                    }',
            ],
            'methodTemplateDefaultReferencingClassTemplate' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /**
                         * @template TResult = T
                         * @param (callable(T): TResult)|null $a
                         * @return I<TResult>
                         */
                        public function work(?callable $a = null): self;
                    }

                    /**
                     * @param I<string> $i
                     * @return I<string>
                     */
                    function test(I $i): I {
                        return $i->work(null);
                    }',
            ],
            'methodTemplateDefaultWithCallable' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface I {
                        /**
                         * @template TResult = T
                         * @param (callable(T): TResult)|null $a
                         * @return I<TResult>
                         */
                        public function work(?callable $a = null): self;
                    }

                    /**
                     * @param I<string> $i
                     */
                    function test(I $i): void {
                        /** @var I<int> */
                        $result = $i->work(
                            /** @param string $s @return int */
                            function (string $s): int { return 1; },
                        );
                    }',
            ],
            'multipleTemplateDefaults' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = int
                     */
                    class Pair {
                        /** @return T */
                        public function first() {
                            throw new \RuntimeException();
                        }
                        /** @return U */
                        public function second() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Pair $p */
                    function testFirst(Pair $p): string {
                        return $p->first();
                    }

                    /** @param Pair $p */
                    function testSecond(Pair $p): int {
                        return $p->second();
                    }',
            ],
            'templateDefaultNever' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    interface Promise {
                        /**
                         * @template TResult1 = T
                         * @template TResult2 = never
                         * @param (callable(T): TResult1)|null $onFulfilled
                         * @param (callable(mixed): TResult2)|null $onRejected
                         * @return Promise<TResult1|TResult2>
                         */
                        public function then(
                            ?callable $onFulfilled = null,
                            ?callable $onRejected = null
                        ): self;
                    }

                    /**
                     * @param Promise<int> $promise
                     * @return Promise<int>
                     */
                    function testNulls(Promise $promise): Promise {
                        return $promise->then(null, null);
                    }',
            ],
            'classTemplateDefaultCovariant' => [
                'code' => '<?php
                    /**
                     * @template-covariant T = string
                     */
                    class Box {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Box $b */
                    function test(Box $b): string {
                        return $b->get();
                    }',
            ],
            'classTemplateDefaultOnNew' => [
                'code' => '<?php
                    /**
                     * @template T = int
                     */
                    class Container {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    $c = new Container(42);',
                'assertions' => [
                    '$c===' => 'Container<42>',
                ],
            ],
            'phpstanTemplateSyntax' => [
                'code' => '<?php
                    /**
                     * @phpstan-template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): string {
                        return $foo->get();
                    }',
            ],
            'functionTemplateDefault' => [
                'code' => '<?php
                    /**
                     * @template TResult = string
                     * @param (callable(): TResult)|null $callback
                     * @return TResult
                     */
                    function resolve(?callable $callback = null) {
                        throw new \RuntimeException();
                    }

                    function test(): string {
                        return resolve(null);
                    }',
            ],
            'templateDefaultWithAsKeyword' => [
                'code' => '<?php
                    /**
                     * @template T as object = stdClass
                     */
                    class Foo {
                        /** @return T */
                        public function get(): object {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): object {
                        return $foo->get();
                    }',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'classTemplateDefaultMismatch' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     */
                    class Foo {
                        /** @return T */
                        public function get() {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): int {
                        return $foo->get();
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
