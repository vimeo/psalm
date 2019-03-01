<?php
namespace Psalm\Tests;

class ForbiddenCodeTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
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
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
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
