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
                    }'
            ],
            'constantInClosure' => [
                '<?php
                    const TEST = 2;
                    
                    $useTest = function() : int {
                        return TEST;
                    };
                    $useTest();'
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
            
                    echo CONSTANT;'
            ]
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
                'error_message' => 'UndefinedConstant'
            ]
        ];
    }
}
