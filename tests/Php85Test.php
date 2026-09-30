<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class Php85Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        return [
            'intCastOfUnrepresentableFloat' => [
                'code' => '<?php
                    $a = (int) 1.0e30;
                    $b = (int) -1.0e30;
                    $c = (int) INF;
                    $d = (int) NAN;
                    $e = (int) 1.5;',
                'assertions' => [
                    '$a===' => 'int',
                    '$b===' => 'int',
                    '$c===' => 'int',
                    '$d===' => 'int',
                    '$e===' => '1',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'intCastOfUnrepresentableFloatConstant' => [
                'code' => '<?php
                    const BIG = 1.0e30;

                    function h(): int {
                        return (int) BIG;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'operatorsOnUnrepresentableFloat' => [
                'code' => '<?php
                    $f = 1.0e30 % 3;
                    $g = 1.0e30 | 1;
                    $h = 1.0e30 << 1;
                    $i = ~1.0e30;',
                'assertions' => [
                    '$f===' => 'int',
                    '$g===' => 'int',
                    '$h===' => 'int',
                    '$i===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'operatorsOnFractionalFloat' => [
                'code' => '<?php
                    $j = 1.5 | 1;
                    $k = ~1.5;',
                'assertions' => [
                    '$j===' => '1',
                    '$k===' => '-2',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'classConstantFoldingOfUnrepresentableFloat' => [
                'code' => '<?php
                    final class A {
                        const F = 1.0e30;
                        const G = self::F | 1;
                    }

                    $l = A::G;',
                'assertions' => [
                    '$l===' => 'int',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'arrayKeyOfUnrepresentableFloat' => [
                'code' => '<?php
                    function makeArray(): array {
                        return [1.0e30 => "x"];
                    }',
                'assertions' => [],
                'ignored_issues' => ['InvalidArrayOffset'],
                'php_version' => '8.5',
            ],
            'arrayOffsetFetchOfUnrepresentableFloat' => [
                'code' => '<?php
                    /** @param list<string> $arr */
                    function fetch(array $arr): ?string {
                        return $arr[1.0e30] ?? null;
                    }',
                'assertions' => [],
                'ignored_issues' => ['InvalidArrayOffset'],
                'php_version' => '8.5',
            ],
            'filterVarIntOfUnrepresentableFloat' => [
                'code' => '<?php
                    function check(): mixed {
                        return filter_var(1.0e30, FILTER_VALIDATE_INT);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'arraySpliceWithUnrepresentableFloatLength' => [
                'code' => '<?php
                    /** @param list<string> $list */
                    function splice(array $list): array {
                        return array_splice($list, 0, 1.0e30);
                    }',
                'assertions' => [],
                'ignored_issues' => ['InvalidScalarArgument'],
                'php_version' => '8.5',
            ],
            'looseComparisonOfUnrepresentableFloat' => [
                'code' => '<?php
                    function compare(): bool {
                        $x = 1.0e30;
                        if ($x == 5) {
                            return true;
                        }
                        return false;
                    }',
                'assertions' => [],
                'ignored_issues' => ['TypeDoesNotContainType'],
                'php_version' => '8.5',
            ],
            'nanCoercedToString' => [
                'code' => '<?php
                    $n = NAN;
                    $a = "$n";
                    $b = (string) NAN;
                    $c = NAN . \'\';
                    $d = \'a\' . NAN;',
                'assertions' => [
                    '$a' => 'string',
                    '$b===' => "'NAN'",
                    '$c===' => "'NAN'",
                    '$d===' => "'aNAN'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'infCoercedToString' => [
                'code' => '<?php
                    $e = (string) INF;
                    $f = -INF . \'\';',
                'assertions' => [
                    '$e===' => "'INF'",
                    '$f===' => "'-INF'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'nanInConstantConcatenation' => [
                'code' => '<?php
                    final class A {
                        public const N = NAN;
                    }
                    final class K {
                        public const S = A::N . \'x\';
                    }

                    $g = K::S;',
                'assertions' => [
                    '$g===' => "'NANx'",
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
            'nanGlobalConstant' => [
                'code' => '<?php
                    const N = NAN;
                    $h = N;',
                'assertions' => [
                    '$h' => 'float',
                ],
                'ignored_issues' => [],
                'php_version' => '8.5',
            ],
        ];
    }

    #[Override]
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'moduloByZeroOfUnrepresentableFloat' => [
                'code' => '<?php
                    $m = 1.0e30 % 0;',
                'error_message' => 'NoValue',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
            'moduloByZeroOfNan' => [
                'code' => '<?php
                    $n = NAN % 0;',
                'error_message' => 'NoValue',
                'error_levels' => [],
                'php_version' => '8.5',
            ],
        ];
    }
}
