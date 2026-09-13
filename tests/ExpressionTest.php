<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class ExpressionTest extends TestCase
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
     * @psalm-mutation-free
     */
    #[Override]
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
        yield 'autoincrementAlmostOverflow' => [
            'code' => <<<'PHP'
                <?php
                $a = [
                  9223372036854775806 => 0,
                  1, // expected key = PHP_INT_MAX
                ];
                PHP,
            'assertions' => [
                '$a===' => 'array{9223372036854775806: 0, 9223372036854775807: 1}',
            ],
        ];
        yield 'shellExecConcatInt' => [
            'code' => <<<'PHP'
                <?php
                $a = 123;
                /** @psalm-suppress ForbiddenCode */
                `ls $a`;
                PHP,
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
     * @psalm-mutation-free
     */
    #[Override]
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

        yield 'autoincrementOverflow' => [
            'code' => <<<'PHP'
                <?php
                $a = [
                  9223372036854775807 => 0,
                  1, // this is a fatal error
                ];
                PHP,
            'error_message' => 'InvalidArrayOffset',
        ];

        yield 'autoincrementOverflowWithUnpack' => [
            'code' => <<<'PHP'
                <?php
                $a = [
                  9223372036854775807 => 0,
                  ...[1], // this is a fatal error
                ];
                PHP,
            'error_message' => 'InvalidArrayOffset',
        ];
    }
}
