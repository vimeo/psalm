<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

/**
 * `@psalm-purity-template` / `@psalm-purity-from-template`: function-likes whose purity depends
 * on the closures they are given, and classes whose purity depends on their subclass or on
 * what they were constructed with.
 */
final class PurityTemplateTest extends TestCase
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
            'pureClosureKeepsFunctionPure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(int): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f(1);
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(fn(int $x): int => $x + 1);
                    }',
            ],
            'closureParamsKeepTheirTypes' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(string): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f("a");
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(fn(string $s): int => strlen($s));
                    }',
            ],
            'impureClosureAllowedInImpureCaller' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    function useImpure(): int {
                        return apply(function (): void { echo "hi"; });
                    }',
            ],
            'staticMethodAndConstructor' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure<P>(): void $f
                         * @psalm-purity-from-template P
                         */
                        public static function run(Closure $f): int {
                            $f();
                            return 1;
                        }

                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure<P>(): void $f
                         * @psalm-purity-from-template P
                         */
                        public function __construct(Closure $f) {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        new Holder(function (): void {});
                        return Holder::run(function (): void {});
                    }',
            ],
            'firstClassCallableOfPureFunction' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    /** @psalm-pure */
                    function one(): int {
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(one(...));
                    }',
            ],
            'omittedAndNullClosureRequireNothing' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param ?Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(?Closure $f = null): int {
                        if ($f) {
                            $f();
                        }
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply() + apply(null) + apply(function (): void {});
                    }',
            ],
            'nestedClosureMayCallTheTemplatedClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $inner = function () use ($f): void {
                            $f();
                        };
                        $inner();
                        return 1;
                    }',
            ],
            'ownCapabilitiesPlusClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-capabilities write-props */
                    function useWriteProps(): int {
                        return apply(function (): void {});
                    }',
            ],
            'twoPurityTemplates' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @psalm-purity-template Q
                     * @param Closure<P>(): void $f
                     * @param Closure<Q>(): void $g
                     * @psalm-purity-from-template P, Q
                     */
                    function both(Closure $f, Closure $g): int {
                        $f();
                        $g();
                        return 1;
                    }

                    /** @psalm-capabilities io */
                    function useIo(): int {
                        return both(function (): void {}, function (): void { echo "x"; });
                    }',
            ],
            'classPurityTemplateBoundBySubclass' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function run(): int {
                            return $this->doWork();
                        }
                    }

                    /** @extends Doer<pure> */
                    final class PureDoer extends Doer {
                        /** @psalm-pure */
                        public function doWork(): int {
                            return 1;
                        }
                    }

                    /** @extends Doer<io> */
                    final class IoDoer extends Doer {
                        /** @psalm-capabilities io */
                        public function doWork(): int {
                            echo "x";
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(PureDoer $d): int {
                        return $d->run();
                    }

                    /** @psalm-capabilities io */
                    function useIo(IoDoer $d): int {
                        return $d->run();
                    }',
            ],
            'functionDependingOnClassPurityTemplate' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @extends Doer<pure> */
                    final class PureDoer extends Doer {
                        /** @psalm-pure */
                        public function doWork(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Doer<P> $d
                     * @psalm-purity-from-template P
                     */
                    function useAny(Doer $d): int {
                        return $d->doWork();
                    }

                    /** @psalm-pure */
                    function usePure(PureDoer $d): int {
                        return useAny($d);
                    }',
            ],
            'purityTemplateIsCovariant' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {}

                    /** @extends Doer<pure> */
                    final class PureDoer extends Doer {}

                    /** @param Doer<io> $d */
                    function takesIoDoer(Doer $d): void {}

                    function test(PureDoer $d): void {
                        takesIoDoer($d);
                    }',
            ],
            'classPurityTemplateBoundByConstructor' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure<C>(): void $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function fire(): int {
                            ($this->cb)();
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        $b = new Box(function (): void {});
                        return $b->fire();
                    }',
            ],
            'typeTemplateBoundToClosure' => [
                'code' => '<?php
                    /**
                     * @template T of callable(): void
                     */
                    final class Deferred {
                        /** @var T */
                        private $callback;

                        /**
                         * @param T $callback
                         * @psalm-external-mutation-free
                         */
                        public function __construct($callback) {
                            $this->callback = $callback;
                        }

                        /**
                         * @psalm-external-mutation-free
                         * @psalm-purity-from-template T
                         */
                        public function run(): void {
                            ($this->callback)();
                        }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Deferred<pure-Closure(): void> $deferred
                     */
                    function runPure(Deferred $deferred): void {
                        $deferred->run();
                    }',
            ],
            'purityTemplateWithoutParams' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P> $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(fn(): int => 1);
                    }',
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
            'impureClosureMakesCallImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                    }

                    /** @psalm-pure */
                    function usePure(): void {
                        apply(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureClosureParamMakesCallImpure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                    }

                    /**
                     * @psalm-pure
                     * @param Closure(): void $f
                     */
                    function usePure(Closure $f): void {
                        apply($f);
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'impureClosureMakesStaticCallImpure' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure<P>(): void $f
                         * @psalm-purity-from-template P
                         */
                        public static function run(Closure $f): void {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): void {
                        Holder::run(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureClosureMakesConstructorImpure' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure<P>(): void $f
                         * @psalm-purity-from-template P
                         */
                        public function __construct(Closure $f) {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): Holder {
                        return new Holder(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'impureClosureMakesMethodCallImpure' => [
                'code' => '<?php
                    final class Holder {
                        /**
                         * @psalm-pure
                         * @psalm-purity-template P
                         * @param Closure<P>(): void $f
                         * @psalm-purity-from-template P
                         */
                        public function run(Closure $f): void {
                            $f();
                        }
                    }

                    /** @psalm-pure */
                    function usePure(Holder $h): void {
                        $h->run(function (): void { echo "hi"; });
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'firstClassCallableOfImpureFunction' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                    }

                    function impure(): void {
                        echo "hi";
                    }

                    /** @psalm-pure */
                    function usePure(): void {
                        apply(impure(...));
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'bodyMayOnlyUseItsOwnCapabilities' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): void {
                        $f();
                        echo "done";
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'templatedClosureNotInheritedFrom' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     */
                    function apply(Closure $f): void {
                        $f();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'reassignedClosureIsNoLongerTemplated' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @param Closure(): void $g
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f, Closure $g): void {
                        $f = $g;
                        $f();
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'ownCapabilitiesAreStillRequired' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        return apply(function (): void {});
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'closureCapabilitiesAreAddedToOwn' => [
                'code' => '<?php
                    /**
                     * @psalm-capabilities write-props
                     * @psalm-purity-template P
                     * @param Closure<P>(): void $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        $f();
                        return 1;
                    }

                    /** @psalm-capabilities write-props */
                    function useWriteProps(): int {
                        return apply(function (): void { echo "x"; });
                    }',
                'error_message' => 'ImpureFunctionCall',
            ],
            'classPurityTemplateBoundToImpure' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @extends Doer<io> */
                    final class IoDoer extends Doer {
                        /** @psalm-capabilities io */
                        public function doWork(): int {
                            echo "x";
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(IoDoer $d): int {
                        return $d->doWork();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'unboundClassPurityTemplateIsImpure' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @psalm-pure */
                    function usePure(Doer $d): int {
                        return $d->doWork();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'purityTemplateCovarianceViolation' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {}

                    /** @extends Doer<io> */
                    final class IoDoer extends Doer {}

                    /** @param Doer<pure> $d */
                    function takesPureDoer(Doer $d): void {}

                    function test(IoDoer $d): void {
                        takesPureDoer($d);
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'constructorBindsClassPurityTemplate' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    final class Box {
                        /**
                         * @param Closure<C>(): void $cb
                         * @psalm-pure
                         */
                        public function __construct(private Closure $cb) {}

                        /**
                         * @psalm-mutation-free
                         * @psalm-purity-from-template C
                         */
                        public function fire(): int {
                            ($this->cb)();
                            return 1;
                        }
                    }

                    /** @psalm-pure */
                    function usePure(): int {
                        $b = new Box(function (): void { echo "x"; });
                        return $b->fire();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'typeTemplateBoundToImpureClosure' => [
                'code' => '<?php
                    /**
                     * @template T of callable(): void
                     */
                    final class Deferred {
                        /** @var T */
                        private $callback;

                        /**
                         * @param T $callback
                         * @psalm-external-mutation-free
                         */
                        public function __construct($callback) {
                            $this->callback = $callback;
                        }

                        /**
                         * @psalm-external-mutation-free
                         * @psalm-purity-from-template T
                         */
                        public function run(): void {
                            ($this->callback)();
                        }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Deferred<Closure(): void> $deferred
                     */
                    function runImpure(Deferred $deferred): void {
                        $deferred->run();
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
            'purityFromUnknownTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @psalm-purity-from-template P
                     */
                    function apply(): void {}',
                'error_message' => 'InvalidDocblock',
            ],
            'purityFromNonPurityTemplate' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @template T
                     * @param T $t
                     * @psalm-purity-from-template T
                     * @return T
                     */
                    function apply($t) {
                        return $t;
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'closurePurityMustBeAPurityType' => [
                'code' => '<?php
                    /**
                     * @param Closure<int>(): void $f
                     */
                    function apply(Closure $f): void {
                        $f();
                    }',
                'error_message' => 'InvalidDocblock',
            ],
            'overrideOfTemplatedMethodMayNotRequireMore' => [
                'code' => '<?php
                    /** @psalm-purity-template C */
                    abstract class Doer {
                        /**
                         * @psalm-pure
                         * @psalm-purity-from-template C
                         */
                        abstract public function doWork(): int;
                    }

                    /** @extends Doer<pure> */
                    final class PureDoer extends Doer {
                        /** @psalm-pure */
                        public function doWork(): int {
                            return 1;
                        }
                    }

                    /**
                     * @psalm-pure
                     * @psalm-purity-template P
                     * @param Closure<P>(): int $f
                     * @psalm-purity-from-template P
                     */
                    function apply(Closure $f): int {
                        return $f();
                    }

                    interface Applier {
                        /** @psalm-pure */
                        public function get(): int;
                    }

                    final class Impl implements Applier {
                        public function get(): int {
                            return apply(function (): int { echo "x"; return 1; });
                        }
                    }',
                'error_message' => 'ImmutableDependency',
            ],
        ];
    }
}
