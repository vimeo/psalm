<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

class TraceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;

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
            ],
        ];
    }
}
