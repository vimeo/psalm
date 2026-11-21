<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

final class TraceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
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
            'traceMessageEndingInNonWordCharacterIsMatchedInFull' => [
                'code' => '<?php
                    /** @param array<string, int> $a */
                    function f(array $a): void {
                        /** @psalm-trace $a */
                        echo 1;
                    }',
                'error_message' => '$a: array<string, int>',
            ],
            'undefinedTraceVariable' => [
                'code' => '<?php
                    /** @psalm-trace $b */
                    echo 1;',
                'error_message' => 'UndefinedTrace',
            ],
        ];
    }
}
