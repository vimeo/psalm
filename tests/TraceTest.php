<?php
namespace Psalm\Tests;

class TraceTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
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
