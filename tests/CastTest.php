<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class CastTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
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

        yield 'strvalAndInterpolationConvertLikeAStringCast' => [
            'code' => '<?php
                /**
                 * @param int<0, 2> $range
                 * @param non-empty-lowercase-string $lower
                 */
                function f(int $int, ?int $nullable, bool $bool, int $range, string $lower): void {
                    $cast = (string) $int;
                    $strval = strval($int);
                    $interpolated = "$int";
                    /** @psalm-check-type-exact $cast = decimal-int-string */
                    /** @psalm-check-type-exact $strval = decimal-int-string */
                    /** @psalm-check-type-exact $interpolated = decimal-int-string */
                    $nullable_strval = strval($nullable);
                    $nullable_interpolated = "{$nullable}";
                    /** @psalm-check-type-exact $nullable_strval = \'\'|decimal-int-string */
                    /** @psalm-check-type-exact $nullable_interpolated = \'\'|decimal-int-string */
                    $bool_strval = strval($bool);
                    /** @psalm-check-type-exact $bool_strval = \'\'|\'1\' */
                    $range_strval = strval($range);
                    $range_interpolated = "$range";
                    /** @psalm-check-type-exact $range_strval = \'0\'|\'1\'|\'2\' */
                    /** @psalm-check-type-exact $range_interpolated = \'0\'|\'1\'|\'2\' */
                    $lower_strval = strval($lower);
                    $lower_interpolated = "$lower";
                    /** @psalm-check-type-exact $lower_strval = non-empty-lowercase-string */
                    /** @psalm-check-type-exact $lower_interpolated = non-empty-lowercase-string */
                    $five = strval(5);
                    /** @psalm-check-type-exact $five = \'5\' */
                    $concatenated = "id $int";
                    /** @psalm-check-type-exact $concatenated = non-empty-string */
                }
            ',
        ];
        yield 'floatIsCastToANonNumericStringWhenInfiniteOrNotANumber' => [
            'code' => '<?php
                /** @param numeric $numeric */
                function f(float $float, $numeric): void {
                    $cast = (string) $float;
                    $strval = strval($float);
                    $interpolated = "$float";
                    $numeric_strval = strval($numeric);
                    $literal = strval(1.5);
                    /** @psalm-check-type-exact $cast = non-empty-string */
                    /** @psalm-check-type-exact $strval = non-empty-string */
                    /** @psalm-check-type-exact $interpolated = non-empty-string */
                    /** @psalm-check-type-exact $numeric_strval = non-empty-string */
                    /** @psalm-check-type-exact $literal = \'1.5\' */
                }

                f(INF, NAN);
            ',
        ];
        yield 'strvalOfAnObjectIsWhatItsToStringReturns' => [
            'code' => '<?php
                final class Name {
                    /** @return lowercase-string */
                    public function __toString(): string {
                        return "name";
                    }
                }

                function f(Name $name): void {
                    $cast = (string) $name;
                    $strval = strval($name);
                    /** @psalm-check-type-exact $cast = lowercase-string */
                    /** @psalm-check-type-exact $strval = lowercase-string */
                }
            ',
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
