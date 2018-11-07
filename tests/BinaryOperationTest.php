<?php
namespace Psalm\Tests;

class BinaryOperationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

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

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
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
                    $a = true ^ false;
                    $b = false ^ false;
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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
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
                'error_message' => 'InvalidOperand - src/somefile.php:3 - Cannot add GMP to non-numeric type',
            ],
            'stringIncrement' => [
                '<?php
                    $a = "hello";
                    $a++;',
                'error_message' => 'InvalidOperand',
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
        ];
    }
}
