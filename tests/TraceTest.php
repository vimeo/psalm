<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

class TraceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;

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
            'traceVariables' => [
                '<?php
                    /** @psalm-trace $a $b */
                    $a = getmypid();
                    $b = getmypid();',
                'error_message' => 'Trace',
            ],
            'traceVariablesComma' => [
                '<?php
                    /** @psalm-trace $a, $b */
                    $a = getmypid();
                    $b = getmypid();',
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
