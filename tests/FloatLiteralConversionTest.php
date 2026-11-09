<?php

declare(strict_types=1);

namespace Psalm\Tests;

use ErrorException;
use Override;
use Psalm\Context;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function restore_error_handler;
use function set_error_handler;

use const E_ALL;

/**
 * PHP 8.5 warns of converting a NAN to string and of converting a float that is not an int's value to int, and
 * Psalm's error handler turns those warnings into fatal errors. PHPUnit only reports them as warnings, so the
 * analysis here fails on the first one explicitly. Only conversions that leave no trace in the inferred types
 * are tested here; the others have a test of their types in the test case of their analyzer.
 */
final class FloatLiteralConversionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function analyzeFile(
        string $file_path,
        Context $context,
        bool $track_unused_suppressions = true,
        bool $taint_flow_tracking = false,
    ): void {
        set_error_handler(static function (int $error_code, string $error_message): never {
            throw new ErrorException($error_message, 0, $error_code);
        }, E_ALL);

        try {
            parent::analyzeFile($file_path, $context, $track_unused_suppressions, $taint_flow_tracking);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'notANumberCoercedToString' => [
            'code' => '<?php
                const GLOBAL_NAN = NAN;

                class A {
                    public const NOT_A_NUMBER = NAN;
                }

                $n = NAN;
                $a = "$n";
                $b = (string) NAN;
                $c = strval(NAN);
                $d = (string) GLOBAL_NAN;
                $e = (string) A::NOT_A_NUMBER;',
            'assertions' => [
                '$a===' => "'NAN'",
                '$b===' => "'NAN'",
                '$c===' => "'NAN'",
                '$d===' => "'NAN'",
                '$e===' => "'NAN'",
            ],
        ];

        yield 'notANumberConcatenated' => [
            'code' => '<?php
                const GLOBAL_NAN = NAN;

                class A {
                    public const NOT_A_NUMBER = NAN;
                }

                class B {
                    public const SUFFIXED = A::NOT_A_NUMBER . "!";
                }

                $a = NAN . "";
                $b = "n=" . NAN;
                $c = GLOBAL_NAN . "!";
                $d = B::SUFFIXED;',
            'assertions' => [
                '$a===' => "'NAN'",
                '$b===' => "'n=NAN'",
                '$c===' => "'NAN!'",
                '$d===' => "'NAN!'",
            ],
            'ignored_issues' => ['InvalidOperand'],
        ];

        yield 'floatLiteralArrayOffsets' => [
            'code' => '<?php
                /** @var array<int, string> $array */
                $array = [];
                $big = $array[1.0e30];
                $notANumber = $array[NAN];
                $infinite = $array[INF];
                $negativelyInfinite = $array[-INF];',
            'assertions' => [
                '$big===' => 'string',
                '$notANumber===' => 'string',
                '$infinite===' => 'string',
                '$negativelyInfinite===' => 'string',
            ],
            'ignored_issues' => ['InvalidArrayOffset'],
        ];

        yield 'arraySpliceOffsetOfUnrepresentableFloat' => [
            'code' => '<?php
                $a = [1, 2, 3];
                array_splice($a, 1e30, 1);
                $b = [1, 2, 3];
                array_splice($b, NAN);',
            'assertions' => [],
            'ignored_issues' => ['InvalidScalarArgument'],
        ];

        yield 'filterVarSanitizesFloatLiterals' => [
            'code' => '<?php
                $notANumberInt = filter_var(NAN, FILTER_SANITIZE_NUMBER_INT);
                $notANumberFloat = filter_var(NAN, FILTER_SANITIZE_NUMBER_FLOAT);
                $negativelyInfinite = filter_var(-INF, FILTER_SANITIZE_NUMBER_INT);
                $fraction = filter_var(1.5, FILTER_SANITIZE_NUMBER_INT);',
            'assertions' => [
                '$notANumberInt===' => "''",
                '$notANumberFloat===' => "''",
                '$negativelyInfinite===' => "'-'",
                '$fraction===' => "'15'",
            ],
        ];

        yield 'looseEqualityOfNotANumberWithString' => [
            'code' => '<?php
                $value = NAN;
                if ($value == "NAN") {
                    $matched = true;
                }',
            'assertions' => [],
            'ignored_issues' => ['TypeDoesNotContainType', 'RedundantCondition'],
        ];

        yield 'looseEqualityOfUnrepresentableFloatWithInt' => [
            'code' => '<?php
                $big = 1e30;
                if ($big == 1) {
                    $matched = true;
                }

                $notANumber = NAN;
                if ($notANumber == 1) {
                    $matched = true;
                }',
            'assertions' => [],
            'ignored_issues' => ['TypeDoesNotContainType'],
        ];
    }
}
