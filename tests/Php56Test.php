<?php
namespace Psalm\Tests;

class Php56Test extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;
    use Traits\FileCheckerInvalidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'constArray' => [
                '<?php
                    const ARR = ["a", "b"];
                    $a = ARR[0];',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'classConstFeatures' => [
                '<?php
                    class C {
                        const ONE = 1;
                        const TWO = self::ONE * 2;
                        const THREE = self::TWO + 1;
                        const ONE_THIRD = self::ONE / self::THREE;
                        const SENTENCE = "The value of THREE is " . self::THREE;

                        /** @var int */
                        public $four = self::ONE + self::THREE;

                        /**
                         * @param  int $a
                         * @return int
                         */
                        public function f($a = self::ONE + self::THREE) {
                            return $a;
                        }
                    }

                    $c1 = C::ONE;
                    $c2 = C::TWO;
                    $c3 = C::THREE;
                    $c1_3rd = C::ONE_THIRD;
                    $c_sentence = C::SENTENCE;
                    $cf = (new C)->f();
                    $c4 = (new C)->four',
                'assertions' => [
                    '$c1' => 'int',
                    '$c2' => 'int',
                    '$c3' => 'int',
                    '$c1_3rd' => 'float|int',
                    '$c_sentence' => 'string',
                    '$cf' => 'int',
                    '$c4' => 'int',
                ],
            ],
            'constFeatures' => [
                '<?php
                    const ONE = 1;
                    const TWO = ONE * 2;

                    $one = ONE;
                    $two = TWO;',
                'assertions' => [
                    '$one' => 'int',
                    '$two' => 'int',
                ],
            ],
            'argumentUnpacking' => [
                '<?php
                    /**
                     * @return int
                     * @param int $a
                     * @param int $b
                     * @param int $c
                     */
                    function add($a, $b, $c) {
                        return $a + $b + $c;
                    }

                    $operators = [2, 3];
                    echo add(1, ...$operators);',
            ],
            'arrayPushArgumentUnpacking' => [
                '<?php
                    /**
                     * @return string[]
                     */
                    function a(): array {
                      $a = [];
                      $b = ["foo", "bar"];

                      $a[] = "foo";

                      array_push($a, ...$b);

                      return $a;
                    }',
            ],
            'arrayMergeArgumentUnpacking' => [
                '<?php
                    $a = [[1, 2]];
                    $b = array_merge([], ...$a);',
                'assertions' => [
                    '$b' => 'array{0:int, 1:int}',
                ],
            ],
            'preserveTypesWhenUnpacking' => [
                '<?php
                    /**
                     * @return array<int,array<int,string>>
                     */
                    function getData(): array
                    {
                        return [
                            ["a", "b"],
                            ["c", "d"]
                        ];
                    }

                    /**
                     * @return array<int,string>
                     */
                    function f1(): array
                    {
                        $data = getData();
                        return array_merge($data[0], $data[1]);
                    }

                    /**
                     * @return array<int,string>
                     */
                    function f2(): array
                    {
                        $data = getData();
                        return array_merge(...$data);
                    }

                    /**
                     * @return array<int,string>
                     */
                    function f3(): array
                    {
                        $data = getData();
                        return array_merge([], ...$data);
                    }',
            ],
            'exponentiation' => [
                '<?php
                    $a = 2;
                    $a **= 3;',
            ],
            'constantAliasInNamespace' => [
                '<?php
                    namespace Name\Space {
                        const FOO = 42;
                    }

                    namespace Noom\Spice {
                        use const Name\Space\FOO;

                        echo FOO . "\n";
                        echo \Name\Space\FOO;
                    }',
            ],
            'constantAliasInClass' => [
                '<?php
                    namespace Name\Space {
                        const FOO = 42;
                    }

                    namespace Noom\Spice {
                        use const Name\Space\FOO;

                        class A {
                            /** @return void */
                            public function fooFoo() {
                                echo FOO . "\n";
                                echo \Name\Space\FOO;
                            }
                        }
                    }',
            ],
            'functionAliasInNamespace' => [
                '<?php
                    namespace Name\Space {
                        /**
                         * @return void
                         */
                        function f() { echo __FUNCTION__."\n"; }
                    }

                    namespace Noom\Spice {
                        use function Name\Space\f;

                        f();
                        \Name\Space\f();
                    }',
            ],
            'functionAliasInClass' => [
                '<?php
                    namespace Name\Space {
                        /**
                         * @return void
                         */
                        function f() { echo __FUNCTION__."\n"; }
                    }

                    namespace Noom\Spice {
                        use function Name\Space\f;

                        class A {
                            /** @return void */
                            public function fooFoo() {
                                f();
                                \Name\Space\f();
                            }
                        }
                    }',
            ],
            'argumentUnpackingWithoutChangingRef' => [
                '<?php
                    function foo(int ...$is) : void {}

                    $arr = [1, 2, 3, 4];
                    foo(...$arr);
                    foo(...$arr);',
            ],
            'iterableSplat' => [
                '<?php
                    function foo(iterable $args): int {
                        return intval(...$args);
                    }

                    function foo(ArrayIterator $args): int {
                        return intval(...$args);
                    }',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'arrayPushArgumentUnpackingWithBadArg' => [
                '<?php
                    $a = [];
                    $b = "hello";

                    $a[] = "foo";

                    array_push($a, ...$b);',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
