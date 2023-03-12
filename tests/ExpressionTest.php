<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class ExpressionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<
     *     string,
     *     array{
     *         code: string,
     *         assertions?: array<string, string>,
     *         ignored_issues?: list<string>,
     *         php_version?: string,
     *     }
     * >
     */
    public function providerValidCodeParse(): iterable
    {
        yield 'maxIntegerInArrayKey' => [
            'code' => <<<'PHP'
                <?php
                // PHP_INT_MAX
                $s = ['9223372036854775807' => 1];
                $i = [9223372036854775807 => 1];

                // PHP_INT_MAX + 1
                $so = ['9223372036854775808' => 1];
                PHP,
            'assertions' => [
                '$s===' => 'array{9223372036854775807: 1}',
                '$i===' => 'array{9223372036854775807: 1}',
                '$so===' => "array{'9223372036854775808': 1}",
            ],
        ];
    }

    /**
     * @return iterable<
     *     string,
     *     array{
     *         code: string,
     *         error_message: string,
     *         ignored_issues?: list<string>,
     *         php_version?: string,
     *     }
     * >
     */
    public function providerInvalidCodeParse(): iterable
    {
        yield 'integerOverflowInArrayKey' => [
            'code' => <<<'PHP'
                <?php
                // PHP_INT_MAX + 1
                [9223372036854775808 => 1];
                PHP,
            'error_message' => 'InvalidArrayOffset',
        ];
    }
}
