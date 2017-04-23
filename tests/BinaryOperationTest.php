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
            'regular-addition' => [
                '<?php
                    $a = 5 + 4;'
            ],
            'differing-numeric-types-addition-in-weak-mode' => [
                '<?php
                    $a = 5 + 4.1;'
            ],
            'numeric-addition' => [
                '<?php
                    $a = "5";
            
                    if (is_numeric($a)) {
                        $b = $a + 4;
                    }'
            ],
            'concatenation' => [
                '<?php
                    $a = "Hey " . "Jude,";'
            ],
            'concatenation-with-number-in-weak-mode' => [
                '<?php
                    $a = "hi" . 5;'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'bad-addition' => [
                '<?php
                    $a = "b" + 5;',
                'error_message' => 'InvalidOperand'
            ],
            'differing-numeric-types-addition-in-strict-mode' => [
                '<?php
                    $a = 5 + 4.1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true
            ],
            'concatenation-with-number-in-strict-mode' => [
                '<?php
                    $a = "hi" . 5;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true
            ],
            'add-array-to-number' => [
                '<?php
                    $a = [1] + 1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true
            ]
        ];
    }
}
