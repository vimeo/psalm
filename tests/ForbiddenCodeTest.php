<?php
namespace Psalm\Tests;

class ForbiddenCodeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'varDump' => [
                '<?php
                    var_dump("hello");',
                'error_message' => 'ForbiddenCode'
            ],
            'execTicks' => [
                '<?php
                    `rm -rf`;',
                'error_message' => 'ForbiddenCode'
            ],
            'exec' => [
                '<?php
                    shell_exec("rm -rf");',
                'error_message' => 'ForbiddenCode'
            ]
        ];
    }
}
