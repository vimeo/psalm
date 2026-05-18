<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ParamClosureThisTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'bindThisToExplicitClass' => [
                'code' => '<?php
                    class Bound {
                        public int $x = 1;
                        public function add(): int { return $this->x + 1; }
                    }

                    /**
                     * @param-closure-this Bound $callback
                     */
                    function withBound(Closure $callback): void {
                        $callback->call(new Bound());
                    }

                    withBound(function (): int {
                        return $this->add() + $this->x;
                    });
                ',
            ],
            'bindThisInArrowFunction' => [
                'code' => '<?php
                    class Bound {
                        public int $x = 5;
                    }

                    /**
                     * @param-closure-this Bound $callback
                     */
                    function withBound(Closure $callback): void {
                        $callback->call(new Bound());
                    }

                    withBound(fn (): int => $this->x);
                ',
            ],
            'bindStaticOnSelfReferencingMacroable' => [
                'code' => '<?php
                    class Holder {
                        public int $value = 42;

                        /**
                         * @param-closure-this static $macro
                         */
                        public static function macro(Closure $macro): void {
                        }
                    }

                    Holder::macro(function (): int {
                        return $this->value;
                    });
                ',
            ],
            'bindThisAsLiteralThisToken' => [
                'code' => '<?php
                    class Manager {
                        public string $name = "m";

                        /**
                         * @param-closure-this $this $callback
                         */
                        public function extend(Closure $callback): void {
                            $callback->call($this);
                        }
                    }

                    $m = new Manager();
                    $m->extend(function (): string {
                        return $this->name;
                    });
                ',
            ],
            'phpstanAliasParseSameAsPsalm' => [
                'code' => '<?php
                    class Bound {
                        public int $x = 1;
                    }

                    /**
                     * @phpstan-param-closure-this Bound $cb
                     */
                    function withBound(Closure $cb): void {
                        $cb->call(new Bound());
                    }

                    withBound(function (): int {
                        return $this->x;
                    });
                ',
            ],
            'psalmAliasParseSameAsPlain' => [
                'code' => '<?php
                    class Bound {
                        public int $x = 1;
                    }

                    /**
                     * @psalm-param-closure-this Bound $cb
                     */
                    function withBound(Closure $cb): void {
                        $cb->call(new Bound());
                    }

                    withBound(function (): int {
                        return $this->x;
                    });
                ',
            ],
            'parentResolvesToParentClass' => [
                'code' => '<?php
                    class A {
                        public int $a_prop = 1;
                    }

                    class B extends A {
                        /**
                         * @param-closure-this parent $cb
                         */
                        public static function run(Closure $cb): void {
                        }
                    }

                    B::run(function (): int {
                        return $this->a_prop;
                    });
                ',
            ],
            'selfWithClassConstantInDocblockResolves' => [
                'code' => '<?php
                    class Holder {
                        public int $value = 7;

                        /**
                         * @param-closure-this self $cb
                         */
                        public static function run(Closure $cb): void {
                        }
                    }

                    Holder::run(function (): int {
                        return $this->value;
                    });
                ',
            ],
            'selfResolvesToDeclaringClassOnInheritedStatic' => [
                'code' => '<?php
                    class Base {
                        public int $only_on_base = 100;

                        /**
                         * @param-closure-this self $cb
                         */
                        public static function run(Closure $cb): void {
                        }
                    }

                    class Child extends Base {
                        public int $only_on_child = 200;
                    }

                    Child::run(function (): int {
                        return $this->only_on_base;
                    });
                ',
            ],
            'staticResolvesToCalledClassOnInheritedStatic' => [
                'code' => '<?php
                    class Base {
                        /**
                         * @param-closure-this static $cb
                         */
                        public static function run(Closure $cb): void {
                        }
                    }

                    class Child extends Base {
                        public int $only_on_child = 200;
                    }

                    Child::run(function (): int {
                        return $this->only_on_child;
                    });
                ',
            ],
            'bindInsideMethodOverridesCallerThis' => [
                'code' => '<?php
                    class Bound {
                        public int $bound_prop = 7;
                    }

                    class Caller {
                        public int $caller_prop = 1;

                        public function go(): void {
                            $this->run(function (): int {
                                return $this->bound_prop;
                            });
                        }

                        /**
                         * @param-closure-this Bound $cb
                         */
                        private function run(Closure $cb): void {
                            $cb->call(new Bound());
                        }
                    }
                ',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'callerPropertyNotVisibleInsideBoundClosure' => [
                'code' => '<?php
                    class Bound {
                        public int $bound_prop = 0;
                    }

                    class Caller {
                        public int $caller_prop = 1;

                        public function go(): void {
                            $this->run(function (): int {
                                return $this->caller_prop;
                            });
                        }

                        /**
                         * @param-closure-this Bound $cb
                         */
                        private function run(Closure $cb): void {
                            $cb->call(new Bound());
                        }
                    }
                ',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'thisMethodNotVisibleOutsideBoundClass' => [
                'code' => '<?php
                    class Bound {
                        public int $x = 0;
                    }

                    /**
                     * @param-closure-this Bound $cb
                     */
                    function withBound(Closure $cb): void {
                        $cb->call(new Bound());
                    }

                    withBound(function (): int {
                        return $this->doesNotExist();
                    });
                ',
                'error_message' => 'UndefinedMethod',
            ],
        ];
    }
}
