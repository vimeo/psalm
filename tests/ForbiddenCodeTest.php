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
            'var-dump' => [
                '<?php
                    var_dump("hello");',
                'error_message' => 'ForbiddenCode'
            ],
            'exec-ticks' => [
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
