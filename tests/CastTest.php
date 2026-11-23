<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class CastTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    #[Override]
    public function providerValidCodeParse(): iterable
    {
        yield 'castOfFloatLiteralsToIntOnlyFoldsThoseInTheRangeOfAnInt' => [
            'code' => '<?php
                $tooBig = (int) 1e30;
                $notANumber = (int) NAN;
                $infinite = (int) INF;
                $negativelyInfinite = (int) -INF;
                $fraction = (int) 3.7;
                $lowest = (int) -9.2233720368547758E+18;
                $past = (int) 9.2233720368547758E+18;',
            'assertions' => [
                '$tooBig===' => 'int',
                '$notANumber===' => 'int',
                '$infinite===' => 'int',
                '$negativelyInfinite===' => 'int',
                '$fraction===' => '3',
                '$lowest===' => '-9223372036854775808',
                '$past===' => 'int',
            ],
        ];

        yield 'castOfFloatConstantsToIntOnlyFoldsThoseInTheRangeOfAnInt' => [
            'code' => '<?php
                const GLOBAL_BIG = 1.0e30;
                const GLOBAL_NAN = NAN;

                class A {
                    public const BIG = 1.0e30;
                    public const NEGATIVELY_BIG = -1.0e30;
                    public const NOT_A_NUMBER = NAN;
                    public const INFINITE = INF;
                    public const NEGATIVELY_INFINITE = -INF;
                    public const FRACTION = 3.7;
                }

                $globalBig = (int) GLOBAL_BIG;
                $globalNotANumber = (int) GLOBAL_NAN;
                $big = (int) A::BIG;
                $negativelyBig = (int) A::NEGATIVELY_BIG;
                $notANumber = (int) A::NOT_A_NUMBER;
                $infinite = (int) A::INFINITE;
                $negativelyInfinite = (int) A::NEGATIVELY_INFINITE;
                $fraction = (int) A::FRACTION;
                $negativelyBigLiteral = (int) -1e30;',
            'assertions' => [
                '$globalBig===' => 'int',
                '$globalNotANumber===' => 'int',
                '$big===' => 'int',
                '$negativelyBig===' => 'int',
                '$notANumber===' => 'int',
                '$infinite===' => 'int',
                '$negativelyInfinite===' => 'int',
                '$fraction===' => '3',
                '$negativelyBigLiteral===' => 'int',
            ],
        ];

        yield 'notANumberIsCoercedToTheStringNAN' => [
            'code' => '<?php
                const GLOBAL_NAN = NAN;

                class A {
                    public const NOT_A_NUMBER = NAN;
                }

                $n = NAN;
                $interpolated = "$n";
                $interpolatedInBraces = "{$n}!";
                $cast = (string) NAN;
                $globalCast = (string) GLOBAL_NAN;
                $constCast = (string) A::NOT_A_NUMBER;
                $constant = A::NOT_A_NUMBER;
                $constInterpolated = "$constant";',
            'assertions' => [
                '$interpolated===' => "'NAN'",
                '$interpolatedInBraces===' => "'NAN!'",
                '$cast===' => "'NAN'",
                '$globalCast===' => "'NAN'",
                '$constCast===' => "'NAN'",
                '$constInterpolated===' => "'NAN'",
            ],
        ];

        yield 'notANumberIsConcatenatedAsTheStringNAN' => [
            'code' => '<?php
                const GLOBAL_NAN = NAN;

                class A {
                    public const NOT_A_NUMBER = NAN;
                }

                class B {
                    public const SUFFIXED = A::NOT_A_NUMBER . "!";
                    public const PREFIXED = "n=" . A::NOT_A_NUMBER;
                }

                $left = NAN . "";
                $right = "n=" . NAN;
                $global = GLOBAL_NAN . "!";
                $const = "n=" . A::NOT_A_NUMBER;
                $suffixed = B::SUFFIXED;
                $prefixed = B::PREFIXED;',
            'assertions' => [
                '$left===' => "'NAN'",
                '$right===' => "'n=NAN'",
                '$global===' => "'NAN!'",
                '$const===' => "'n=NAN'",
                '$suffixed===' => "'NAN!'",
                '$prefixed===' => "'n=NAN'",
            ],
            'ignored_issues' => ['InvalidOperand'],
        ];

        yield 'infiniteAndHugeFloatsAreCoercedToStrings' => [
            'code' => '<?php
                const GLOBAL_BIG = 1.0e30;

                class A {
                    public const BIG = 1.0e30;
                    public const INFINITE = INF;
                }

                $n = -INF;
                $infinite = (string) INF;
                $negativelyInfinite = (string) -INF;
                $big = (string) 1e30;
                $negativelyBig = (string) -1e30;
                $interpolated = "$n";
                $globalBig = (string) GLOBAL_BIG;
                $constBig = (string) A::BIG;
                $constInfinite = (string) A::INFINITE;',
            'assertions' => [
                '$infinite===' => "'INF'",
                '$negativelyInfinite===' => "'-INF'",
                '$big===' => "'1.0E+30'",
                '$negativelyBig===' => "'-1.0E+30'",
                '$interpolated===' => "'-INF'",
                '$globalBig===' => "'1.0E+30'",
                '$constBig===' => "'1.0E+30'",
                '$constInfinite===' => "'INF'",
            ],
        ];

        yield 'voidCastDiscardsValueWithoutError' => [
            'code' => '<?php
                $x = strlen("hello");
                (void) $x;
                (void) strlen("world");
                echo "reachable";
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.5',
        ];
        yield 'SKIPPED-castFalseOrIntToInt' => [
            'code' => '<?php
                /** @var false|int<10, 20> */
                $intOrFalse = 10;
                $int = (int) $intOrFalse;
            ',
            'assertions' => [
                '$int===' => '0|int<10, 20>',
            ],
        ];
        yield 'SKIPPED-castTrueOrIntToInt' => [
            'code' => '<?php
                /** @var true|int<10, 20> */
                $intOrTrue = 10;
                $int = (int) $intOrTrue;
            ',
            'assertions' => [
                '$int===' => '1|int<10, 20>',
            ],
        ];
        yield 'SKIPPED-castBoolOrIntToInt' => [
            'code' => '<?php
                /** @var bool|int<10, 20> */
                $intOrBool = 10;
                $int = (int) $intOrBool;
            ',
            'assertions' => [
                '$int===' => '0|1|int<10, 20>',
            ],
        ];
        yield 'castObjectWithPropertiesToArray' => [
            'code' => '<?php
                /** @var object{a:int,b:string} $o */
                $a = (array) $o;
            ',
            'assertions' => [
                '$a===' => 'array{a: int, b: string, ...<array-key, mixed>}',
            ],
        ];
        yield 'castIntRangeToString' => [
            'code' => '<?php
                /** @var int<-5, 3> */
                $int_range = 2;
                $string = (string) $int_range;
            ',
            'assertions' => [
                '$string===' => "'-1'|'-2'|'-3'|'-4'|'-5'|'0'|'1'|'2'|'3'",
            ],
        ];
    }
}
