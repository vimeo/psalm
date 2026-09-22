<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class PurityFromTest extends TestCase
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
            // A pure closure passed to an `@psalm-purity-from` param keeps the call
            // at the method's own declared level, so a pure/mutation-free caller is happy.
            'pureClosureKeepsCallerPurity' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @psalm-external-mutation-free
                         * @psalm-purity-from $cb
                         * @param Closure(): void $cb
                         */
                        public function run(Closure $cb): void {
                            $cb();
                        }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param pure-Closure(): void $cb
                     */
                    function caller(Box $b, Closure $cb): void {
                        $b->run($cb);
                    }',
            ],
            // An impure closure is fine when the caller itself makes no purity promise.
            'impureClosureAllowedInImpureCaller' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @psalm-external-mutation-free
                         * @psalm-purity-from $cb
                         * @param Closure(): void $cb
                         */
                        public function run(Closure $cb): void {
                            $cb();
                        }
                    }

                    /**
                     * @param Closure(): void $cb
                     */
                    function caller(Box $b, Closure $cb): void {
                        $b->run($cb);
                    }',
            ],
            // Purity taken from a template param bound to the passed closure type.
            'purityFromTemplateParam' => [
                'code' => '<?php
                    /**
                     * @template T
                     */
                    final class Holder {
                        /** @var T */
                        public $value;

                        /**
                         * @param T $value
                         * @psalm-external-mutation-free
                         */
                        public function __construct($value) {
                            $this->value = $value;
                        }
                    }

                    class Runner {
                        /**
                         * @template T of callable(): void
                         * @psalm-external-mutation-free
                         * @psalm-purity-from-template T
                         * @param Holder<T> $holder
                         */
                        public function go(Holder $holder): void {
                            ($holder->value)();
                        }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Holder<pure-Closure(): void> $holder
                     */
                    function caller(Runner $r, Holder $holder): void {
                        $r->go($holder);
                    }',
            ],
            // Purity taken from a class-level template holding the closure.
            'purityFromClassTemplate' => [
                'code' => '<?php
                    /**
                     * @template T of callable(): void
                     */
                    class Box {
                        /** @var T */
                        private $cb;

                        /**
                         * @param T $cb
                         * @psalm-external-mutation-free
                         */
                        public function __construct($cb) {
                            $this->cb = $cb;
                        }

                        /**
                         * @psalm-external-mutation-free
                         * @psalm-purity-from-template T
                         */
                        public function run(): void {
                            ($this->cb)();
                        }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Box<pure-Closure(): void> $box
                     */
                    function caller(Box $box): void {
                        $box->run();
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
            // An impure closure passed to an `@psalm-purity-from` param makes the call
            // impure, which a mutation-free caller may not do.
            'impureClosureMakesCallImpure' => [
                'code' => '<?php
                    class Box {
                        /**
                         * @psalm-external-mutation-free
                         * @psalm-purity-from $cb
                         * @param Closure(): void $cb
                         */
                        public function run(Closure $cb): void {
                            $cb();
                        }
                    }

                    /**
                     * @psalm-external-mutation-free
                     * @param Closure(): void $cb
                     */
                    function caller(Box $b, Closure $cb): void {
                        $b->run($cb);
                    }',
                'error_message' => 'ImpureMethodCall',
            ],
        ];
    }
}
