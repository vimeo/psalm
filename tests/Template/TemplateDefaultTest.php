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
            'inferredNeverPreservedOverDefault' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @param list<T> $items
                     * @return list<T>
                     */
                    function passThrough(array $items): array {
                        return $items;
                    }

                    /** @var list<never> $empty */
                    $empty = [];
                    $result = passThrough($empty);',
                'assertions' => [
                    '$result===' => 'list<never>',
                ],
            ],
            'classTemplateDefaultEqualsBound' => [
                'code' => '<?php
                    /**
                     * @template T of stdClass = stdClass
                     */
                    class Foo {
                        /** @return T */
                        public function get(): stdClass {
                            throw new \RuntimeException();
                        }
                    }',
            ],
            'classTemplateDefaultReferencingClassNotYetLoaded' => [
                'code' => '<?php
                    /**
                     * @template T of \DateTimeInterface = \DateTimeImmutable
                     */
                    class Foo {
                        /** @return T */
                        public function get(): \DateTimeInterface {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): \DateTimeInterface {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultWithTemplateBound' => [
                'code' => '<?php
                    /**
                     * @template TKey of array-key
                     * @template T of TKey = string
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultWithNestedTemplateInBound' => [
                'code' => '<?php
                    /**
                     * @template TKey of string
                     * @template T of array<TKey, mixed> = array<string, mixed>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of Z = Child
                     */
                    class Foo {
                        /** @return T */
                        public function get(): Z {
                            throw new \RuntimeException();
                        }
                    }

                    /** @param Foo $foo */
                    function test(Foo $foo): Z {
                        return $foo->get();
                    }',
            ],
            'classTemplateDefaultArrayWithTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of array<int, Z> = array<int, Child>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultClassStringWithTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of class-string<Z> = class-string<Child>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultIterableWithTransitiveInheritance' => [
                'code' => '<?php
                    class Z {}
                    class A extends Z {}
                    class Child extends A {}

                    /**
                     * @template T of iterable<Z> = array<Child>
                     */
                    class Foo {}',
            ],
            'classTemplateDefaultAppliedThroughFunctionReturn' => [
                'code' => '<?php
                    /**
                     * @template T of string = "hello"
                     */
                    class Foo {
                        /** @return T */
                        public function get(): string { throw new \RuntimeException(); }
                    }

                    /** @return Foo */
                    function makeFoo(): Foo { throw new \RuntimeException(); }

                    $r = makeFoo()->get();',
                'assertions' => [
                    "\$r===" => "'hello'",
                ],
            ],
            'classDefaultReferencingAnotherDefaultResolves' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = T
                     */
                    class Pair {}

                    $p = new Pair();',
                'assertions' => [
                    '$p===' => 'Pair<string, string>',
                ],
            ],
            'classChainedDefaultsAppliedThroughFunctionReturn' => [
                'code' => '<?php
                    /**
                     * @template T = string
                     * @template U = T
                     */
                    class Pair {}

                    /** @return Pair */
                    function makePair(): Pair { throw new \RuntimeException(); }

                    $p = makePair();',
                'assertions' => [
                    '$p===' => 'Pair<string, string>',
                ],
            ],
            'classCyclicDefaultsDoNotInfiniteLoop' => [
                'code' => '<?php
                    /**
                     * @template T = U
                     * @template U = T
                     */
                    class Cycle {}

                    new Cycle();',
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
            'classTemplateDefaultViolatesBound' => [
                'code' => '<?php
                    /**
                     * @template T of object = int
                     */
                    class Foo {}',
                'error_message' => 'is not within bound',
            ],
            'classTemplateDefaultViolatesAsBound' => [
                'code' => '<?php
                    /**
                     * @template T as string = 42
                     */
                    class Foo {}',
                'error_message' => 'is not within bound',
            ],
            'functionTemplateDefaultViolatesBound' => [
                'code' => '<?php
                    /**
                     * @template T of object = int
                     * @return T
                     */
                    function foo() {
                        throw new \RuntimeException();
                    }',
                'error_message' => 'is not within bound',
            ],
            'classTemplateDefaultScalarViolatesNamedClassBound' => [
                'code' => '<?php
                    /**
                     * @template T of \DateTimeInterface = int
                     */
                    class Foo {}',
                'error_message' => 'is not within bound',
            ],
        ];
    }
}
