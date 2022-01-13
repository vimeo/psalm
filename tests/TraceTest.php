<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

class TraceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:array<string>,strict_mode?:bool,php_version?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'traceVariable' => [
                'code' => '<?php
                    /** @psalm-trace $a */
                    $a = getmypid();',
                'error_message' => 'Trace',
            ],
            'traceVariables' => [
                'code' => '<?php
                    /** @psalm-trace $a $b */
                    $a = getmypid();
                    $b = getmypid();',
                'error_message' => 'Trace',
            ],
            'traceVariablesComma' => [
                'code' => '<?php
                    /** @psalm-trace $a, $b */
                    $a = getmypid();
                    $b = getmypid();',
                'error_message' => 'Trace',
            ],
            'undefinedTraceVariable' => [
                'code' => '<?php
                    /** @psalm-trace $b */
                    echo 1;',
                'error_message' => 'UndefinedTrace',
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ]
        ];
    }
}
