<?php
namespace Psalm\Tests;

use function class_exists;
use const DIRECTORY_SEPARATOR;

class BinaryOperationTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return void
     */
    public function testGMPOperations()
    {
        if (class_exists('GMP') === false) {
            $this->markTestSkipped('Cannot run test, base class "GMP" does not exist!');

            return;
        }

        $this->addFile(
            'somefile.php',
            '<?php
                $a = gmp_init(2);
                $b = gmp_init(4);
                $c = $a + $b;
                $d = $c + 3;
                echo $d;
                $f = $a / $b;
                $g = $a ** $b;
                $h = $a % $b;

                $i = 6 + $b;
                $j = 6 - $b;
                $k = 6 * $b;
                $l = 6 / $b;
                $m = 6 ** $b;
                $n = 6 % $b;

                $o = $a + 6;
                $p = $a - 6;
                $q = $a * 6;
                $r = $a / 6;
                $s = $a ** 6;
                $t = $a % 6;'
        );

        $assertions = [
            '$a' => 'GMP',
            '$b' => 'GMP',
            '$c' => 'GMP',
            '$d' => 'GMP',
            '$f' => 'GMP',
            '$g' => 'GMP',
            '$h' => 'GMP',
            '$i' => 'GMP',
            '$j' => 'GMP',
            '$k' => 'GMP',
            '$l' => 'GMP',
            '$m' => 'GMP',
            '$n' => 'GMP',
            '$o' => 'GMP',
            '$p' => 'GMP',
            '$q' => 'GMP',
            '$r' => 'GMP',
            '$s' => 'GMP',
            '$t' => 'GMP',
        ];

        $context = new \Psalm\Context();

        $this->analyzeFile('somefile.php', $context);

        $actual_vars = [];
        foreach ($assertions as $var => $_) {
            if (isset($context->vars_in_scope[$var])) {
                $actual_vars[$var] = (string)$context->vars_in_scope[$var];
            }
        }

        $this->assertSame($assertions, $actual_vars);
    }

    public function testStrictTrueEquivalence(): void
    {
        $config = \Psalm\Config::getInstance();
        $config->strict_binary_operands = true;

        $this->addFile(
            'somefile.php',
            '<?php
                function returnsABool(): bool {
                    return rand(1, 2) === 1;
                }

                if (returnsABool() === true) {
                    echo "hi!";
                }'
        );

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessage('RedundantIdentityWithTrue');

        $this->analyzeFile('somefile.php', new \Psalm\Context());
    }

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'regularAddition' => [
                '<?php
                    $a = 5 + 4;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'differingNumericTypesAdditionInWeakMode' => [
                '<?php
                    $a = 5 + 4.1;',
                'assertions' => [
                    '$a' => 'float',
                ],
            ],
            'modulo' => [
                '<?php
                    $a = 25 % 2;
                    $b = 25.4 % 2;
                    $c = 25 % 2.5;
                    $d = 25.5 % 2.5;',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'int',
                    '$d' => 'int',
                ],
            ],
            'numericAddition' => [
                '<?php
                    $a = "5";

                    if (is_numeric($a)) {
                        $b = $a + 4;
                    }',
            ],
            'concatenation' => [
                '<?php
                    $a = "Hey " . "Jude,";',
            ],
            'concatenationWithNumberInWeakMode' => [
                '<?php
                    $a = "hi" . 5;',
            ],
            'possiblyInvalidAdditionOnBothSides' => [
                '<?php
                    function foo(string $s) : int {
                        return strpos($s, "a") + strpos($s, "b");
                    }',
                'assertions' => [],
                'error_levels' => ['PossiblyFalseOperand'],
            ],
            'bitwiseoperations' => [
                '<?php
                    $a = 4 & 5;
                    $b = 2 | 3;
                    $c = 4 ^ 3;
                    $d = 1 << 2;
                    $e = 15 >> 2;
                    $f = "a" & "b";',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'int',
                    '$d' => 'int',
                    '$e' => 'int',
                    '$f' => 'string',
                ],
            ],
            'booleanXor' => [
                '<?php
                    $a = 4 ^ 1;
                    $b = 3 ^ 1;
                    $c = (true xor false);
                    $d = (false xor false);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'bool',
                    '$d' => 'bool',
                ],
            ],
            'ternaryAssignment' => [
                '<?php
                    rand(0, 1) ? $a = 1 : $a = 2;
                    echo $a;',
            ],
            'assignmentInRHS' => [
                '<?php
                    $name = rand(0, 1) ? "hello" : null;
                    if ($name !== null || ($name = rand(0, 1) ? "hello" : null) !== null) {}',
            ],
            'floatIncrement' => [
                '<?php
                    $a = 1.1;
                    $a++;
                    $b = 1.1;
                    $b += 1;',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'float',
                ],
            ],
            'exponent' => [
                '<?php
                    $b = 4 ** 5;',
                'assertions' => [
                    '$b' => 'int',
                ],
            ],
            'bitwiseNot' => [
                '<?php
                    $a = ~4;
                    $b = ~4.0;
                    $c = ~4.4;
                    $d = ~"a";',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'int',
                    '$d' => 'string',
                ],
            ],
            'stringIncrementSuppressed' => [
                '<?php
                    $a = "hello";
                    /** @psalm-suppress StringIncrement */
                    $a++;',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'nullCoalescingAssignment' => [
                '<?php
                    function foo(?string $s): string {
                        $s ??= "Hello";
                        return $s;
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'nullCoalescingArrayAssignment' => [
                '<?php
                    /**
                     * @param array<string> $arr
                     */
                    function foo(array $arr) : void {
                        $b = [];

                        foreach ($arr as $a) {
                            $b[0] ??= $a;
                        }
                    }',
                'assertions' => [],
                'error_levels' => [],
                '7.4',
            ],
            'addArrays' => [
                '<?php
                    /**
                     * @param array{host?:string} $opts
                     * @return array{host:string|int}
                     */
                    function a(array $opts): array {
                        return $opts + ["host" => 5];
                    }'
            ],
            'addIntToZero' => [
                '<?php
                    $tick = 0;

                    test($tick + 1);

                    $tick++;

                    test($tick);

                    /**
                     * @psalm-param positive-int $tickedTimes
                     */
                    function test(int $tickedTimes): void {}'
            ],
            'numericPlusIntegerIsIntOrFloat' => [
                '<?php
                    /** @param numeric-string $s */
                    function foo(string $s) : void {
                        $s = $s + 1;
                        if (is_int($s)) {}
                    }'
            ],
            'interpolatedStringNotEmpty' => [
                '<?php
                    /**
                     * @psalm-param non-empty-string $i
                     */
                    function func($i): string
                    {
                        return $i;
                    }

                    function foo(string $a) : void {
                        func("asdasdasd $a");
                    }'
            ],
            'spaceshipOpIsLiteralUnionType' => [
                '<?php
                    /**
                     * @psalm-param -1|0|1 $i
                     */
                     function onlyZeroOrPlusMinusOne(int $i): int {
                         return $i;
                     }

                     /**
                      * @psalm-param mixed $a
                      * @psalm-param mixed $b
                      */
                     function foo($a, $b): void {
                         onlyZeroOrPlusMinusOne($a <=> $b);
                     }'
            ]
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'badAddition' => [
                '<?php
                    $a = "b" + 5;',
                'error_message' => 'InvalidOperand',
            ],
            'differingNumericTypesAdditionInStrictMode' => [
                '<?php
                    $a = 5 + 4.1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true,
            ],
            'concatenationWithNumberInStrictMode' => [
                '<?php
                    $a = "hi" . 5;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true,
            ],
            'addArrayToNumber' => [
                '<?php
                    $a = [1] + 1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true,
            ],
            'additionWithClassInWeakMode' => [
                '<?php
                    $a = "hi" + (new stdClass);',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyInvalidOperand' => [
                '<?php
                    $b = rand(0, 1) ? [] : 4;
                    echo $b + 5;',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'possiblyInvalidConcat' => [
                '<?php
                    $b = rand(0, 1) ? [] : "hello";
                    echo $b . "goodbye";',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'invalidGMPOperation' => [
                '<?php
                    $a = gmp_init(2);
                    $b = "a" + $a;',
                'error_message' => 'InvalidOperand - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:26 - Cannot add GMP to non-numeric type',
            ],
            'stringIncrement' => [
                '<?php
                    $a = "hello";
                    $a++;',
                'error_message' => 'StringIncrement',
            ],
            'falseIncrement' => [
                '<?php
                    $a = false;
                    $a++;',
                'error_message' => 'FalseOperand',
            ],
            'trueIncrement' => [
                '<?php
                    $a = true;
                    $a++;',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyDivByZero' => [
                '<?php
                    $a = 5 / (rand(0, 1) ? 2 : null);',
                'error_message' => 'PossiblyNullOperand',
            ],
            'invalidExponent' => [
                '<?php
                    $a = "x" ^ 1;',
                'error_message' => 'InvalidOperand',
            ],
            'invalidBitwiseOr' => [
                '<?php
                    $a = "x" | new stdClass;',
                'error_message' => 'InvalidOperand',
            ],
            'invalidBitwiseNot' => [
                '<?php
                    $a = ~new stdClass;',
                'error_message' => 'InvalidOperand',
            ],
            'possiblyInvalidBitwiseNot' => [
                '<?php
                    $a = ~(rand(0, 1) ? 2 : null);',
                'error_message' => 'PossiblyInvalidOperand',
            ],
            'invalidBooleanBitwiseNot' => [
                '<?php
                    $a = ~true;',
                'error_message' => 'InvalidOperand',
            ],
            'substrImpossible' => [
                '<?php
                    class HelloWorld
                    {
                        public function sayHello(string $s): void
                        {
                            if (substr($s, 0, 6) === "abc") {}
                        }
                    }',
                    'error_message' => 'TypeDoesNotContainType',
            ],
        ];
    }
}
