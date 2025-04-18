<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class Php56Test extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'constArray' => [
                'code' => '<?php
                    const ARR = ["a", "b"];
                    $a = ARR[0];',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'classConstFeatures' => [
                'code' => '<?php
                    class C {
                        const ONE = 1;
                        const TWO = self::ONE * 2;
                        const THREE = self::TWO + 1;
                        const ONE_THIRD = self::ONE / self::THREE;
                        const SENTENCE = "The value of THREE is " . self::THREE;
                        const SHIFT = self::ONE >> 2;
                        const SHIFT2 = self::ONE << 1;
                        const BITAND = 1 & 1;
                        const BITOR = 1 | 1;
                        const BITXOR = 1 ^ 1;

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
                    $c4 = (new C)->four;
                    $shift = C::SHIFT;
                    $shift2 = C::SHIFT2;
                    $bitand = C::BITAND;
                    $bitor = C::BITOR;
                    $bitxor = C::BITXOR;',
                'assertions' => [
                    '$c1' => 'int',
                    '$c2===' => '2',
                    '$c3===' => '3',
                    '$c1_3rd' => 'float|int',
                    '$c_sentence' => 'string',
                    '$cf' => 'int',
                    '$c4' => 'int',
                    '$shift' => 'int',
                    '$shift2' => 'int',
                    '$bitand' => 'int',
                    '$bitor' => 'int',
                    '$bitxor' => 'int',
                ],
            ],
            'constFeatures' => [
                'code' => '<?php
                    const ONE = 1;
                    const TWO = ONE * 2;
                    const BITWISE = ONE & 2;
                    const SHIFT = ONE << 2;
                    const SHIFT2 = PHP_INT_MAX << 1;

                    $one = ONE;
                    $two = TWO;
                    $bitwise = BITWISE;
                    $shift = SHIFT;
                    $shift2 = SHIFT2;',
                'assertions' => [
                    '$one' => 'int',
                    '$two' => 'int',
                    '$bitwise' => 'int',
                    '$shift' => 'int',
                    '$shift2' => 'int',
                ],
            ],
            'exponentiation' => [
                'code' => '<?php
                    $a = 2;
                    $a **= 3;',
            ],
            'constantAliasInNamespace' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'yieldReturn' => [
                'code' => '<?php
                    function foo() : Traversable {
                        if (rand(0, 1)) {
                            yield "hello";
                            return;
                        }

                        yield "goodbye";
                    }',
            ],
        ];
    }
}
