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
                    $a = 5 + 4;'
            ],
            'differingNumericTypesAdditionInWeakMode' => [
                '<?php
                    $a = 5 + 4.1;'
            ],
            'numericAddition' => [
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
            'concatenationWithNumberInWeakMode' => [
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
            'badAddition' => [
                '<?php
                    $a = "b" + 5;',
                'error_message' => 'InvalidOperand'
            ],
            'differingNumericTypesAdditionInStrictMode' => [
                '<?php
                    $a = 5 + 4.1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true
            ],
            'concatenationWithNumberInStrictMode' => [
                '<?php
                    $a = "hi" . 5;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true
            ],
            'addArrayToNumber' => [
                '<?php
                    $a = [1] + 1;',
                'error_message' => 'InvalidOperand',
                'error_levels' => [],
                'strict_mode' => true
            ]
        ];
    }
}
