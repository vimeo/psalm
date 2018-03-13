<?php
namespace Psalm\Tests;

class ForbiddenCodeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'varDump' => [
                '<?php
                    var_dump("hello");',
                'error_message' => 'ForbiddenCode',
            ],
            'execTicks' => [
                '<?php
                    `rm -rf`;',
                'error_message' => 'ForbiddenCode',
            ],
            'exec' => [
                '<?php
                    shell_exec("rm -rf");',
                'error_message' => 'ForbiddenCode',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'execWithSuppression' => [
                '<?php
                    @exec("pwd 2>&1", $output, $returnValue);
                    if ($returnValue === 0) {
                        echo "success";
                    }',
            ],
            'execWithoutSuppression' => [
                '<?php
                    exec("pwd 2>&1", $output, $returnValue);
                    if ($returnValue === 0) {
                        echo "success";
                    }',
            ],
        ];
    }
}
