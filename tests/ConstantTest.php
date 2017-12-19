<?php
namespace Psalm\Tests;

class ConstantTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'constantInFunction' => [
                '<?php
                    useTest();
                    const TEST = 2;

                    function useTest() : int {
                        return TEST;
                    }',
            ],
            'constantInClosure' => [
                '<?php
                    const TEST = 2;

                    $useTest = function() : int {
                        return TEST;
                    };
                    $useTest();',
            ],
            'constantDefinedInFunction' => [
                '<?php
                    /**
                     * @return void
                     */
                    function defineConstant() {
                        define("CONSTANT", 1);
                    }

                    defineConstant();

                    echo CONSTANT;',
            ],
            'magicConstant' => [
                '<?php
                    $a = __LINE__;
                    $b = __file__;',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'string',
                ],
            ],
            'getClassConstantValue' => [
                '<?php
                    class A {
                        const B = [0, 1, 2];
                    }

                    $a = A::B[1];',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'constantDefinedInFunctionButNotCalled' => [
                '<?php
                    /**
                     * @return void
                     */
                    function defineConstant() {
                        define("CONSTANT", 1);
                    }

                    echo CONSTANT;',
                'error_message' => 'UndefinedConstant',
            ],
        ];
    }
}
