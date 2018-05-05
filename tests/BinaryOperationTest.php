<?php
namespace Psalm\Tests;

class BinaryOperationTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'regularAddition' => [
                '<?php
                    $a = 5 + 4;',
            ],
            'differingNumericTypesAdditionInWeakMode' => [
                '<?php
                    $a = 5 + 4.1;',
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
            'gmpOperations' => [
                '<?php
                    $a = gmp_init(2);
                    $b = gmp_init(4);
                    $c = $a + $b;
                    $d = $c + 3;
                    echo $d;',
                'assertions' => [
                    '$a' => 'GMP',
                    '$b' => 'GMP',
                    '$c' => 'GMP',
                    '$d' => 'GMP',
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
        ];
    }
}
