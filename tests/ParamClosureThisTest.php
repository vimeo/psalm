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

    /**
     * @psalm-pure
     */
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
            'variadicClosureParamBindsEachClosure' => [
                'code' => '<?php
                    class Bound { public int $p = 99; }

                    /** @param-closure-this Bound ...$cbs */
                    function eachCallback(Closure ...$cbs): void {
                        foreach ($cbs as $cb) {
                            $cb->call(new Bound());
                        }
                    }

                    eachCallback(function (): int {
                        return $this->p;
                    });
                ',
            ],
            'namedClosureParameterTakesPreVariadicAnnotation' => [
                'code' => '<?php
                    class Bound {
                        public int $value = 99;
                    }

                    /**
                     * @param-closure-this Bound $callback
                     */
                    function withBound(Closure $callback, mixed ...$rest): void {
                    }

                    withBound(callback: function (): int {
                        return $this->value;
                    });
                ',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'classGenericTemplateBindsClosureThis' => [
                'code' => '<?php
                    class Container { public int $value = 42; }

                    /**
                     * @template T of object
                     */
                    class Tap {
                        /** @var T */
                        private object $obj;

                        /** @param T $obj */
                        public function __construct(object $obj) {
                            $this->obj = $obj;
                        }

                        /**
                         * @param-closure-this T $cb
                         */
                        public function with(Closure $cb): void {
                            $cb->call($this->obj);
                        }
                    }

                    $tap = new Tap(new Container());
                    $tap->with(function (): int {
                        return $this->value;
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
            'traitSelfResolvesToUsingClass' => [
                'code' => '<?php
                    trait RunsCallback {
                        /** @param-closure-this self $callback */
                        public function run(Closure $callback): void {
                            $callback->call($this);
                        }
                    }

                    class Host {
                        use RunsCallback;

                        public int $value = 1;
                    }

                    (new Host())->run(function (): int {
                        return $this->value;
                    });',
            ],
            'boundParentConstantUsesBoundScope' => [
                'code' => '<?php
                    class Base {
                        protected const VALUE = 1;
                    }

                    class Bound extends Base {}

                    /** @param-closure-this Bound $callback */
                    function bind(Closure $callback): void {}

                    bind(function (): int {
                        return parent::VALUE;
                    });
                ',
            ],
            'genericBoundReceiverPropertyUsesBoundType' => [
                'code' => '<?php
                    /** @template T */
                    class Box {
                        /** @var T */
                        public $value;

                        /** @param T $value */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    /** @param-closure-this Box<int> $callback */
                    function bind(Closure $callback): void {}

                    bind(function (): int {
                        return $this->value;
                    });
                ',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'sameClosureNodeIsClearedForUnboundUnionReceiver' => [
                'code' => '<?php
                    class Bound {
                        public int $value = 1;
                    }

                    class ABoundReceiver {
                        /** @param-closure-this Bound $callback */
                        public function run(Closure $callback): void {}
                    }

                    class ZUnboundReceiver {}

                    $receiver = rand(0, 1)
                        ? new ABoundReceiver()
                        : new ZUnboundReceiver();

                    $receiver->run(function (): int {
                        return $this->value;
                    });
                ',
                'error_message' => 'InvalidScope',
                'ignored_issues' => ['PossiblyUndefinedMethod'],
            ],
            'selfOnInheritedMethodStaysAtDeclaringClass' => [
                'code' => '<?php
                    class Base {
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
                        return $this->only_on_child;
                    });
                ',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
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
            'staticClosureIsNotBound' => [
                'code' => '<?php
                    class Bound {
                        public function go(): int { return 1; }
                    }

                    /**
                     * @param-closure-this Bound $cb
                     */
                    function withBound(Closure $cb): void {}

                    withBound(static function (): int {
                        return $this->go();
                    });
                ',
                'error_message' => 'InvalidScope',
            ],
            'unionClosureThisIsNotBound' => [
                'code' => '<?php
                    class A { public int $x = 1; }
                    class B { public int $x = 2; }

                    /**
                     * @param-closure-this A|B $cb
                     */
                    function withBound(Closure $cb): void {}

                    withBound(function (): int {
                        return $this->x;
                    });
                ',
                'error_message' => 'InvalidScope',
            ],
            'unknownClosureThisClassIsNotBound' => [
                'code' => '<?php
                    /**
                     * @param-closure-this NoSuchClass $cb
                     */
                    function withBound(Closure $cb): void {}

                    withBound(function (): int {
                        return $this->whatever();
                    });
                ',
                'error_message' => 'InvalidScope',
                'ignored_issues' => ['UndefinedDocblockClass'],
            ],
            'traitSelfOnInheritedMethodStaysAtUsingClass' => [
                'code' => '<?php
                    trait RunsCallback {
                        /** @param-closure-this self $callback */
                        public function run(Closure $callback): void {
                            $callback->call($this);
                        }
                    }

                    class Host {
                        use RunsCallback;
                    }

                    class Child extends Host {
                        public int $child_only = 1;
                    }

                    (new Child())->run(function (): int {
                        return $this->child_only;
                    });',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
            'traitParentOnInheritedMethodUsesConsumerParent' => [
                'code' => '<?php
                    class ParentClass {
                        public int $parent_only = 1;
                    }

                    trait RunsCallback {
                        /** @param-closure-this parent $callback */
                        public function run(Closure $callback): void {
                            $callback->call($this);
                        }
                    }

                    class Host extends ParentClass {
                        use RunsCallback;

                        public int $host_only = 1;
                    }

                    class Child extends Host {}

                    (new Child())->run(function (): int {
                        return $this->host_only;
                    });',
                'error_message' => 'UndefinedThisPropertyFetch',
            ],
        ];
    }
}
