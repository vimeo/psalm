<?php
namespace Psalm\Tests;

class TraceTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'traceVariable' => [
                '<?php
                    /** @psalm-trace $a */
                    $a = getmypid();',
                'error_message' => 'Trace',
            ],
            'undefinedTraceVariable' => [
                '<?php
                    /** @psalm-trace $b */
                    echo 1;',
                'error_message' => 'UndefinedTrace',
                'error_levels' => [
                    'MixedAssignment',
                ],
            ]
        ];
    }
}
