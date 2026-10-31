<?php

declare(strict_types=1);

namespace Psalm\Tests;

use ErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Context;

use function restore_error_handler;
use function set_error_handler;

use const E_ALL;

/**
 * PHP 8.5 warns of converting a NAN to string and of converting a float that is not an int's value to int, and
 * Psalm's error handler turns those warnings into fatal errors. PHPUnit only reports them as warnings, so these
 * tests fail on the first one explicitly.
 */
final class FloatLiteralConversionTest extends TestCase
{
    /**
     * @param list<string> $ignored_issues
     */
    #[DataProvider('providerCode')]
    public function testAnalysisRaisesNoPhpDiagnostic(string $code, array $ignored_issues = []): void
    {
        foreach ($ignored_issues as $issue_name) {
            Config::getInstance()->setCustomErrorLevel($issue_name, Config::REPORT_SUPPRESS);
        }

        $codebase = $this->project_analyzer->getCodebase();
        $codebase->enterServerMode();
        $codebase->config->visitPreloadedStubFiles($codebase);

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, $code);

        set_error_handler(static function (int $error_code, string $error_message): never {
            throw new ErrorException($error_message, 0, $error_code);
        }, E_ALL);

        try {
            $this->analyzeFile($file_path, new Context());
        } finally {
            restore_error_handler();
        }

        $this->addToAssertionCount(1);
    }

    /**
     * @return iterable<string, array{code: string, ignored_issues?: list<string>}>
     */
    public static function providerCode(): iterable
    {
        yield 'castOfUnrepresentableFloatsToInt' => [
            'code' => '<?php
                const BIG = 1.0e30;

                class A {
                    public const NOT_A_NUMBER = NAN;
                }

                $a = (int) INF;
                $b = (int) -INF;
                $c = (int) NAN;
                $d = (int) 1e30;
                $e = (int) -1e30;
                $f = (int) BIG;
                $g = (int) A::NOT_A_NUMBER;',
        ];

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
            'ignored_issues' => ['InvalidOperand'],
        ];

        yield 'floatLiteralArrayKeys' => [
            'code' => '<?php
                $a = [1.0e30 => 1];
                $b = [NAN => 2];
                $c = [INF => 3, -INF => 4];',
            'ignored_issues' => ['InvalidArrayOffset'],
        ];

        yield 'floatLiteralArrayOffsets' => [
            'code' => '<?php
                /** @param array<int, string> $array */
                function f(array $array): void {
                    echo $array[1.0e30], $array[NAN], $array[INF], $array[-INF];
                }',
            'ignored_issues' => ['InvalidArrayOffset'],
        ];

        yield 'bitwiseNotOfUnrepresentableFloats' => [
            'code' => '<?php
                $a = ~NAN;
                $b = ~1e30;
                $c = ~-INF;',
        ];

        yield 'integerOperatorsOnUnrepresentableFloats' => [
            'code' => '<?php
                $a = NAN % 3;
                $b = 3 % INF;
                $c = 1e30 | 1;
                $d = INF & 1;
                $e = 1 ^ NAN;
                $f = 1 << 1e30;
                $g = -1e30 >> 1;',
        ];

        yield 'bitwiseOperatorsOnUnrepresentableFloatConstants' => [
            'code' => '<?php
                class A {
                    public const BIG = 1e30;
                    public const NOT_A_NUMBER = NAN;
                }

                class B {
                    public const OR = A::BIG | 1;
                    public const AND = A::NOT_A_NUMBER & 1;
                    public const XOR = A::BIG ^ A::NOT_A_NUMBER;
                }

                $a = B::OR;
                $b = B::AND;
                $c = B::XOR;',
        ];

        yield 'arraySpliceLengthOfUnrepresentableFloat' => [
            'code' => '<?php
                $a = [1, 2, 3];
                array_splice($a, 0, 1e30);
                $b = [1, 2, 3];
                array_splice($b, 0, NAN);',
            'ignored_issues' => ['InvalidScalarArgument'],
        ];

        yield 'filterVarWithUnrepresentableFloats' => [
            'code' => '<?php
                $a = filter_var(1e30, FILTER_VALIDATE_INT);
                $b = filter_var(NAN, FILTER_VALIDATE_INT);
                $c = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1e30]]);
                $d = filter_var(5, FILTER_VALIDATE_INT, ["options" => ["max_range" => NAN]]);',
        ];

        yield 'looseEqualityOfUnrepresentableFloatWithInt' => [
            'code' => '<?php
                function f(): void {
                    $big = 1e30;
                    if ($big == 1) {
                        $matched = true;
                    }

                    $notANumber = NAN;
                    if ($notANumber == 1) {
                        $matched = true;
                    }
                }',
            'ignored_issues' => ['TypeDoesNotContainType'],
        ];
    }
}
