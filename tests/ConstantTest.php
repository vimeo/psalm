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
            'constant-in-function' => [
                '<?php
                    useTest();
                    const TEST = 2;
            
                    function useTest() : int {
                        return TEST;
                    }'
            ],
            'constant-in-closure' => [
                '<?php
                    const TEST = 2;
                    
                    $useTest = function() : int {
                        return TEST;
                    };
                    $useTest();'
            ],
            'constant-defined-in-function' => [
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
            'constant-defined-in-function-but-not-called' => [
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
