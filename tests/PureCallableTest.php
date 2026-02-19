<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class PureCallableTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'varCallableParamReturnType' => [
                'code' => '<?php
                    $add_one =
                        /**
                         * @psalm-pure
                         */
                        function(int $a): int {
                            return $a + 1;
                        };

                    /**
                     * @psalm-pure
                     * @param pure-callable(int): int $c
                     */
                    function bar(callable $c) : int {
                        return $c(1);
                    }

                    bar($add_one);',
            ],
            'callableToClosure' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * 
                     * @return pure-callable
                     */
                    function foo() {
                        return
                            /**
                             * @psalm-pure
                             */
                            function(string $a): string {
                                return $a . "blah";
                            };
                    }',
            ],
            'callableToClosureArrow' => [
                'code' => '<?php
                    /**
                     * @return pure-callable
                     * @psalm-pure
                     */
                    function foo() {
                        return
                            /**
                             * @psalm-pure
                             */
                            fn(string $a): string => $a . "blah";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '7.4',
            ],
            'callableWithNonInvokable' => [
                'code' => '<?php
                    /** @psalm-mutation-free */
                    function asd(): void {}
                    class B {}

                    /**
                     * @psalm-mutation-free
                     * 
                     * @param pure-callable|B $p
                     */
                    function passes($p): void {}

                    passes("asd");',
            ],
            'callableWithInvokableUnion' => [
                'code' => '<?php
                    /** @psalm-mutation-free */
                    function asd(): void {}
                    class A {
                        /**
                         * @psalm-mutation-free
                         */
                        public function __invoke(): void {}
                    }

                    /**
                     * @psalm-mutation-free
                     * 
                     * @param pure-callable|A $p
                     */
                    function fails($p): void {}

                    fails("asd");',
            ],
            'callableWithInvokable' => [
                'code' => '<?php
                    /** @psalm-pure */
                    function asd(int $a): int { return $a; }
                    class A {
                        /**
                         * @psalm-pure
                         */
                        public function __invoke(int $a): int { return $a; }
                    }

                    /**
                     * @psalm-mutation-free
                     * 
                     * @param pure-callable $p
                     */
                    function fails($p): void {
                        $p();
                    }

                    fails(new A());
                    fails("asd");',
            ],
            'allowVoidCallable' => [
                'code' => '<?php
                    /**
                     * @psalm-mutation-free
                     * 
                     * @param pure-callable():void $p
                     */
                    function doSomething($p): void {}
                    doSomething(
                        /**
                         * @psalm-pure
                         */
                        function(): bool { return false; }
                    );',
            ],
            'callableProperties' => [
                'code' => '<?php
                    class C {
                        /** @psalm-var pure-callable():bool */
                        private $callable;

                        /**
                         * @psalm-mutation-free
                         * 
                         * @psalm-param pure-callable():bool $callable
                         */
                        public function __construct(callable $callable) {
                            $this->callable = $callable;
                        }

                        /** @psalm-mutation-free */
                        public function callTheCallableDirectly(): bool {
                            return ($this->callable)();
                        }

                        /** @psalm-mutation-free */
                        public function callTheCallableIndirectly(): bool {
                            $r = $this->callable;
                            return $r();
                        }
                    }',
            ],
            'nullableReturnTypeShorthand' => [
                'code' => '<?php
                    class A {}
                    /**
                     * @psalm-mutation-free
                     * 
                     * @param pure-callable(mixed):?A $a
                     */
                    function foo(callable $a): void {}',
            ],
            'callablesCanBeObjects' => [
                'code' => '<?php
                    /**
                     * Not pure because callable return type is not used.
                     * 
                     * @psalm-mutation-free
                     * 
                     * @param pure-callable $c
                     */
                    function foo(callable $c) : void {
                        if (is_object($c)) {
                            $c();
                        }
                    }',
            ],
            'goodCallableArgs' => [
                'code' => '<?php
                    /**
                     * @psalm-mutation-free
                     * 
                     * @param pure-callable(string,string):int $_p
                     */
                    function f(callable $_p): void {}

                    class C {
                        /**
                         * @psalm-pure
                         */
                        public static function m(string $a, string $b): int { return $a <=> $b; }
                    }

                    f("strcmp");
                    f([new C, "m"]);
                    f([C::class, "m"]);',
            ],
            'callableWithSpaces' => [
                'code' => '<?php
                    /**
                     * @psalm-mutation-free
                     * @param pure-callable(string, string) : int $p
                     */
                    function f(callable $p): void {}',
            ],
            'varCallableInNamespace' => [
                'code' => '<?php
                    namespace Foo;

                    /**
                     * @psalm-pure
                     * 
                     * @param pure-callable $c
                     */
                    function bar(callable $c) : callable {
                        return $c;
                    }',
            ],
            'pureCallableArgument' => [
                'code' => '<?php
                    /**
                     * @psalm-param array<int, int> $values
                     * @psalm-param pure-callable(int):int $num_func
                     *
                     * @psalm-pure
                     */
                    function max_by(array $values, callable $num_func) : ?int {
                        $max = null;
                        $max_num = null;
                        foreach ($values as $value) {
                            $value_num = $num_func($value);
                            if (null === $max_num || $value_num >= $max_num) {
                                $max = $value;
                                $max_num = $value_num;
                            }
                        }

                        return $max;
                    }

                    $c = max_by([1, 2, 3], function(int $a): int {
                        return $a + 5;
                    });

                    echo $c;',
            ],
            'impureCallableReturnOk' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return impure-callable(int):int
                     */
                    function foo(): callable {
                        /** @psalm-suppress ImpureFunctionCall */
                        return function(int $a): int {
                            return $a + mt_rand(0, $a);
                        };
                    }',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'impureCallableArgument' => [
                'code' => '<?php
                    /**
                     * @psalm-param array<int, int> $values
                     * @psalm-param pure-callable(int):int $num_func
                     *
                     * @psalm-pure
                     */
                    function max_by(array $values, callable $num_func) : ?int {
                        $max = null;
                        $max_num = null;
                        foreach ($values as $value) {
                            $value_num = $num_func($value);
                            if (null === $max_num || $value_num >= $max_num) {
                                $max = $value;
                                $max_num = $value_num;
                            }
                        }

                        return $max;
                    }

                    $c = max_by([1, 2, 3], function(int $a): int {
                        return $a + mt_rand(0, $a);
                    });

                    echo $c;',
                'error_message' => 'ArgumentTypeCoercion',
            ],
            'impureCallableReturn' => [
                'code' => '<?php
                    /**
                     * @psalm-pure
                     * @return pure-callable():int
                     */
                    function foo(): callable {
                        /** @psalm-suppress ImpureFunctionCall */
                        return function() {
                            echo "bar";
                            return 1;
                        };
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
        ];
    }
}
