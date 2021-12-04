<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class PureCallableTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'varCallableParamReturnType' => [
                '<?php
                    $add_one =
                        /**
                         * @psalm-pure
                         */
                        function(int $a): int {
                            return $a + 1;
                        };

                    /**
                     * @param pure-callable(int): int $c
                     */
                    function bar(callable $c) : int {
                        return $c(1);
                    }

                    bar($add_one);',
            ],
            'callableToClosure' => [
                '<?php
                    /**
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
                '<?php
                    /**
                     * @return pure-callable
                     */
                    function foo() {
                        return
                            /**
                             * @psalm-pure
                             */
                            fn(string $a): string => $a . "blah";
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'callableWithNonInvokable' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function asd(): void {}
                    class B {}

                    /**
                     * @param pure-callable|B $p
                     */
                    function passes($p): void {}

                    passes("asd");',
            ],
            'callableWithInvokableUnion' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function asd(): void {}
                    class A {public function __invoke(): void {} }

                    /**
                     * @param pure-callable|A $p
                     */
                    function fails($p): void {}

                    fails("asd");',
            ],
            'callableWithInvokable' => [
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function asd(): void {}
                    class A {
                        /**
                         * @psalm-pure
                         */
                        public function __invoke(): void {}
                    }

                    /**
                     * @param pure-callable $p
                     */
                    function fails($p): void {}

                    fails(new A());
                    fails("asd");',
            ],
            'allowVoidCallable' => [
                '<?php
                    /**
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
                '<?php
                    class C {
                        /** @psalm-var pure-callable():bool */
                        private $callable;

                        /**
                         * @psalm-param pure-callable():bool $callable
                         */
                        public function __construct(callable $callable) {
                            $this->callable = $callable;
                        }

                        public function callTheCallableDirectly(): bool {
                            return ($this->callable)();
                        }

                        public function callTheCallableIndirectly(): bool {
                            $r = $this->callable;
                            return $r();
                        }
                    }',
            ],
            'nullableReturnTypeShorthand' => [
                '<?php
                    class A {}
                    /** @param pure-callable(mixed):?A $a */
                    function foo(callable $a): void {}',
            ],
            'callablesCanBeObjects' => [
                '<?php
                    /**
                     * @param pure-callable $c
                     */
                    function foo(callable $c) : void {
                        if (is_object($c)) {
                            $c();
                        }
                    }',
            ],
            'goodCallableArgs' => [
                '<?php
                    /**
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
                '<?php
                    /**
                     * @param pure-callable(string, string) : int $p
                     */
                    function f(callable $p): void {}',
            ],
            'varCallableInNamespace' => [
                '<?php
                    namespace Foo;

                    /**
                     * @param pure-callable $c
                     */
                    function bar(callable $c) : callable {
                        return $c;
                    }',
            ],
            'pureCallableArgument' => [
                '<?php
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
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'impureCallableArgument' => [
                '<?php
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
                'error_message' => 'InvalidArgument',
            ],
            'impureCallableReturn' => [
                '<?php
                    /**
                     * @psalm-pure
                     * @return pure-callable():int
                     */
                    function foo(): callable {
                        return function() {
                            echo "bar";
                            return 1;
                        };
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
