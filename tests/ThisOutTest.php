<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ThisOutTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'changeInterface' => [
                'code' => '<?php
                      interface Foo {
                          /**
                           * @return void
                           */
                          public function far() {
                          }
                      }
                      class Bar {
                          /**
                           * @psalm-this-out Foo
                           * @return void
                           */
                          public function baz() {
                          }
                      }
                      $bar = new Bar();
                      $bar->baz();
                      $bar->far();
                ',
            ],
            'changeTemplateArguments' => [
                'code' => '<?php
                    /**
                     * @template-covariant T as int
                     */
                    class container {
                        /** @var list<T> */
                        public array $data;
                        /**
                         * @param T $data
                         */
                        public function __construct($data) { $this->data = [$data]; }
                        /**
                         * @template NewT as int
                         * @param NewT $data
                         *
                         * @psalm-this-out self<NewT>
                         */
                        public function setData($data): void {
                            $this->data = [$data];
                        }
                        /**
                         * @template NewT as int
                         * @param NewT $data
                         *
                         * @psalm-this-out self<T|NewT>
                         */
                        public function addData($data): void {
                            $this->data []= $data;
                        }
                        /**
                         * @return list<T>
                         */
                        public function getData(): array { return $this->data; }
                    }

                    $a = new container(1);
                    $data1 = $a->getData();
                    $a->setData(2);
                    $data2 = $a->getData();
                    $a->addData(3);
                    $data3 = $a->getData();
                ',
                'assertions' => [
                    // `new container(1)` mints a type variable for T (constrainable
                    // via setData/addData); the exact `===` form reveals it here,
                    // before the subsequent writes reconcile it in $data2/$data3.
                    '$data1===' => 'list<`_0:1>',
                    '$data2===' => 'list<2>',
                    '$data3===' => 'list<2|3>',
                ],
            ],
            'propertyAssignmentFitsTheSelfOutType' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @var list<T> */
                        private array $items = [];

                        /**
                         * @template U
                         * @param U $item
                         * @psalm-self-out Box<T|U>
                         */
                        public function add(mixed $item): void {
                            $this->items[] = $item;
                        }

                        /** @return list<T> */
                        public function all(): array {
                            return $this->items;
                        }
                    }',
            ],
            'parameterConditionalTypeIsResolvedFromCallArguments' => [
                'code' => '<?php
                    class ClassNull {}
                    class ClassNonNull {}
                    class A {
                        /**
                         * @psalm-self-out ($key is null ? ClassNull : ClassNonNull)
                         */
                        public function m(?int $key = null): void {}
                    }
                    $a1 = new A();
                    $a1->m();
                    $a2 = new A();
                    $a2->m(5);
                ',
                'assertions' => [
                    '$a1===' => 'ClassNull',
                    '$a2===' => 'ClassNonNull',
                ],
            ],
            'parameterConditionalTypeWithTemplatedClassAndStaticThisBranches' => [
                'code' => '<?php
                    /**
                     * @template TKey
                     * @template TValue
                     */
                    class Coll {
                        /**
                         * @param TValue $value
                         * @phpstan-this-out ($key is null ? static<TKey|int, TValue> : $this)
                         */
                        public function prepend(mixed $value, ?int $key = null): static {
                            return $this;
                        }
                    }
                    /** @var Coll<string, bool> $c1 */
                    $c1->prepend(true);
                    /** @var Coll<string, bool> $c2 */
                    $c2->prepend(true, 0);
                ',
                'assertions' => [
                    '$c1===' => 'Coll<int|string, bool>&static',
                    '$c2===' => 'Coll<string, bool>&static',
                ],
            ],
            'parameterConditionalOnBothReturnAndSelfOutForSameParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class H {
                        /**
                         * @psalm-self-out ($key is null ? static<int> : static<string>)
                         * @return ($key is null ? int : string)
                         */
                        public function m(?int $key = null) {
                            return $key === null ? 1 : "x";
                        }
                    }
                    $h1 = new H();
                    $r1 = $h1->m();
                    $h2 = new H();
                    $r2 = $h2->m(5);
                ',
                'assertions' => [
                    '$h1===' => 'H<int>&static',
                    '$r1===' => 'int',
                    '$h2===' => 'H<string>&static',
                    '$r2===' => 'string',
                ],
            ],
            'parameterConditionalSelfOutOnConstructor' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    class K {
                        /**
                         * @psalm-self-out ($x is null ? static<int> : static<string>)
                         */
                        public function __construct(?int $x = null) {}
                    }
                    $k1 = new K();
                    $k2 = new K(5);
                ',
                'assertions' => [
                    '$k1===' => 'K<int>&static',
                    '$k2===' => 'K<string>&static',
                ],
            ],
            'selfOutInTraitResolvesToUsingClass' => [
                'code' => '<?php
                    trait TSelfOut {
                        /** @psalm-self-out self */
                        public function toSelf(): void {}
                    }
                    final class UsesSelfOutTrait {
                        use TSelfOut;
                    }
                    $x = new UsesSelfOutTrait();
                    $x->toSelf();
                ',
                'assertions' => [
                    '$x===' => 'UsesSelfOutTrait',
                ],
            ],
            'provideDefaultTypeToTypeArguments' => [
                'code' => <<<'PHP'
                <?php
                    /** @template T of 'idle'|'running' */
                    class App {
                        /** @psalm-this-out self<'idle'> */
                        public function __construct() {}

                        /**
                         * @psalm-if-this-is self<'idle'>
                         * @psalm-this-out self<'running'>
                         */
                        public function start(): void {}
                    }
                    $app = new App();
                PHP,
                'assertions' => [
                    '$app===' => "App<'idle'>",
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{code: string, error_message: string}>
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'propertyAssignmentNotFittingTheSelfOutType' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @var list<T> */
                        private array $items = [];

                        /** @psalm-self-out Box<T|string> */
                        public function add(int $item): void {
                            $this->items[] = $item;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'propertyAssignmentInAMethodWithoutSelfOut' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @var list<T> */
                        private array $items = [];

                        /**
                         * @template U
                         * @param U $item
                         */
                        public function add(mixed $item): void {
                            $this->items[] = $item;
                        }
                    }',
                'error_message' => 'InvalidPropertyAssignmentValue',
            ],
            'unparseableTypeIsReportedInsteadOfCrashing' => [
                'code' => '<?php
                    class A {
                        /** @psalm-self-out garbage<<< */
                        public function t(): void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'incompleteConditionalTypeIsReportedInsteadOfCrashing' => [
                'code' => '<?php
                    class A {
                        /** @phpstan-this-out ($key is null) */
                        public function s(?int $key): void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'undeclaredParamInConditionalTypeIsReportedInsteadOfCrashing' => [
                'code' => '<?php
                    class A {
                        /** @phpstan-this-out ($nope is null ? int : string) */
                        public function s(?int $key): void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'conditionalWithoutIsTypeIsReportedInsteadOfCrashing' => [
                'code' => '<?php
                    class A {
                        /** @psalm-self-out ($key is ? int : string) */
                        public function m(?int $key = null): void {}
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'selfOutTypeWithAnUndefinedClass' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {
                        /** @psalm-self-out self<UndefinedValue> */
                        public function fill(): void {}
                    }',
                'error_message' => 'UndefinedDocblockClass - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:29 - Docblock-defined class, interface or enum named UndefinedValue does not exist',
            ],
        ];
    }
}
