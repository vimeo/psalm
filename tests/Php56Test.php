<?php
namespace Psalm\Tests;

use PhpParser\ParserFactory;
use PHPUnit_Framework_TestCase;
use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class Php56Test extends TestCase
{
    use Traits\FileCheckerValidCodeParseTestTrait;

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
                    ['string' =>'$a']
                ]
            ],
            'constFeatures' => [
                '<?php
                    const ONE = 1;
                    const TWO = ONE * 2;
            
                    class C {
                        const THREE = TWO + 1;
                        const ONE_THIRD = ONE / self::THREE;
                        const SENTENCE = "The value of THREE is " . self::THREE;
            
                        /**
                         * @param  int $a
                         * @return int
                         */
                        public function f($a = ONE + self::THREE) {
                            return $a;
                        }
                    }
            
                    $d = (new C)->f();
                    $e = C::SENTENCE;
                    $f = TWO;
                    $g = C::ONE_THIRD;',
                'assertions' => [
                    ['int' => '$d'],
                    ['string' => '$e'],
                    ['int' => '$f'],
                    ['float|int' => '$g']
                ]
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
                    echo add(1, ...$operators);'
            ],
            'exponentiation' => [
                '<?php
                    $a = 2;
                    $a **= 3;'
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
                    }'
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
                    }'
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
                    }'
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
                    }'
            ]
        ];
    }
}
