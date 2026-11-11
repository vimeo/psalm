<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class CastTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        yield 'castOfAnIntersectionToStringIsWhatTheNarrowestToStringReturns' => [
            'code' => '<?php
                interface PlainString {
                    public function __toString(): string;
                }

                interface NumericString {
                    /** @return numeric-string */
                    public function __toString(): string;
                }

                /** @var PlainString&NumericString $a */
                $a = null;
                $fromPlainFirst = (string) $a;

                /** @var NumericString&PlainString $b */
                $b = null;
                $fromNumericFirst = (string) $b;',
            'assertions' => [
                '$fromPlainFirst===' => 'numeric-string',
                '$fromNumericFirst===' => 'numeric-string',
            ],
        ];

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
                $strval = strval(NAN);
                $globalCast = (string) GLOBAL_NAN;
                $constCast = (string) A::NOT_A_NUMBER;
                $constant = A::NOT_A_NUMBER;
                $constInterpolated = "$constant";',
            'assertions' => [
                '$interpolated===' => "'NAN'",
                '$interpolatedInBraces===' => "'NAN!'",
                '$cast===' => "'NAN'",
                '$strval===' => "'NAN'",
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
                $constInfinite = (string) A::INFINITE;
                $strval = strval(1e30);',
            'assertions' => [
                '$infinite===' => "'INF'",
                '$negativelyInfinite===' => "'-INF'",
                '$big===' => "'1.0E+30'",
                '$negativelyBig===' => "'-1.0E+30'",
                '$interpolated===' => "'-INF'",
                '$globalBig===' => "'1.0E+30'",
                '$constBig===' => "'1.0E+30'",
                '$constInfinite===' => "'INF'",
                '$strval===' => "'1.0E+30'",
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
                    /** @psalm-check-type-exact $concatenated = non-falsy-string */
                }
            ',
        ];
        yield 'closureCastToArrayIsAListOfTheClosure' => [
            'code' => '<?php
                /**
                 * @param Closure(string): void|non-empty-list<Closure(string): void> $writers
                 */
                function write_to(mixed $writers, string $message): void {
                    $writers = (array) $writers;
                    $writers[0]($message);
                }

                $closure = static fn(): int => 1;
                $cast = (array) $closure;
            ',
            'assertions' => [
                '$cast===' => 'list{Closure[pure]():1}',
            ],
            'ignored_issues' => [],
            'php_version' => '8.1',
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
        yield 'voidCastDiscardsValueWithoutErrorOnPhp86' => [
            'code' => '<?php
                $x = strlen("hello");
                (void) $x;
                (void) strlen("world");
                echo "reachable";
            ',
            'assertions' => [],
            'ignored_issues' => [],
            'php_version' => '8.6',
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
        yield 'arrayCastToStringIsTheArrayLiteral' => [
            'code' => '<?php
                /**
                 * @param array<string> $arr
                 * @param list{1, 2} $tuple
                 * @param non-empty-array<int, string> $non_empty
                 * @param list<int> $list
                 */
                function f(array $arr, array $tuple, array $non_empty, array $list): void {
                    $cast = (string) $arr;
                    $interpolated = "$arr";
                    $prefixed = "A: $arr";
                    /** @psalm-check-type-exact $cast = \'Array\' */
                    /** @psalm-check-type-exact $interpolated = \'Array\' */
                    /** @psalm-check-type-exact $prefixed = \'A: Array\' */
                    $tuple_cast = (string) $tuple;
                    $tuple_interpolated = "$tuple";
                    /** @psalm-check-type-exact $tuple_cast = \'Array\' */
                    /** @psalm-check-type-exact $tuple_interpolated = \'Array\' */
                    $non_empty_cast = (string) $non_empty;
                    $list_interpolated = "$list";
                    /** @psalm-check-type-exact $non_empty_cast = \'Array\' */
                    /** @psalm-check-type-exact $list_interpolated = \'Array\' */
                }
            ',
            'assertions' => [],
            'ignored_issues' => ['InvalidCast'],
        ];
        yield 'arrayAmongOtherTypesAddsTheArrayLiteralToTheCast' => [
            'code' => '<?php
                /**
                 * @param array<string>|false $array_or_false
                 * @param array<string>|int $array_or_int
                 */
                function f($array_or_false, $array_or_int): void {
                    $interpolated = "$array_or_false";
                    $cast = (string) $array_or_int;
                    /** @psalm-check-type-exact $interpolated = \'\'|\'Array\' */
                    /** @psalm-check-type-exact $cast = \'Array\'|decimal-int-string */
                }
            ',
            'assertions' => [],
            'ignored_issues' => ['PossiblyInvalidCast'],
        ];
        yield 'arrayBoundOfTemplateIsCastToTheArrayLiteral' => [
            'code' => '<?php
                /**
                 * @template T of array<string>
                 * @param T $array
                 */
                function f(array $array): void {
                    $cast = (string) $array;
                    $interpolated = "x$array";
                    /** @psalm-check-type-exact $cast = \'Array\' */
                    /** @psalm-check-type-exact $interpolated = \'xArray\' */
                }
            ',
            'assertions' => [],
            'ignored_issues' => ['InvalidCast'],
        ];
        yield 'interpolationOfEmptyStringsIsTheEmptyString' => [
            'code' => '<?php
                $empty1 = "";
                $empty2 = "";
                $both = "$empty1$empty2";
                $one = "{$empty1}";
            ',
            'assertions' => [
                '$both===' => "''",
                '$one===' => "''",
            ],
        ];
        yield 'interpolationWithANonEmptyPartIsNonEmpty' => [
            'code' => '<?php
                /**
                 * @param non-empty-string $non_empty
                 * @param int|null $nullable
                 */
                function f(string $non_empty, string $string, int $int, ?int $nullable): void {
                    $first = "$non_empty$string";
                    $last = "$string$non_empty";
                    $int_part = "$string$int";
                    $single = "$non_empty";
                    /** @psalm-check-type-exact $first = non-empty-string */
                    /** @psalm-check-type-exact $last = non-empty-string */
                    /** @psalm-check-type-exact $int_part = non-empty-string */
                    /** @psalm-check-type-exact $single = non-empty-string */
                    $maybe_empty = "$string$string";
                    $nullable_part = "$string$nullable";
                    /** @psalm-check-type-exact $maybe_empty = string */
                    /** @psalm-check-type-exact $nullable_part = string */
                }
            ',
        ];
        yield 'interpolationOfAToStringReturningATemplate' => [
            'code' => '<?php
                /** @template T of string */
                final class Wrapper {
                    /** @param T $value */
                    public function __construct(private string $value) {}

                    /** @return T */
                    public function __toString(): string {
                        return $this->value;
                    }
                }

                /** @param Wrapper<string> $wrapper */
                function f(Wrapper $wrapper): void {
                    $wrapped = "[{$wrapper}]";
                    $twice = "{$wrapper}{$wrapper}";
                    /** @psalm-check-type-exact $wrapped = non-falsy-string */
                    /** @psalm-check-type-exact $twice = string */
                }
            ',
        ];
        yield 'interpolationWithClassStringUnionStaysNonEmpty' => [
            'code' => '<?php
                /**
                 * @template T of object
                 * @param class-string<T>|\'foo\'|\'bar\' $class
                 */
                function f(string $class): void {
                    $prefixed = "x$class";
                    /** @psalm-check-type-exact $prefixed = non-falsy-string */
                }
            ',
        ];
        yield 'arrayOrObjectCastToStringStaysAString' => [
            'code' => '<?php
                /** @param array<array-key, mixed>|object $value */
                function f($value): void {
                    $cast = (string) $value;
                    $interpolated = "$value";
                    $prefixed = "x$value";
                    /** @psalm-check-type-exact $cast = string */
                    /** @psalm-check-type-exact $interpolated = string */
                    /** @psalm-check-type-exact $prefixed = non-falsy-string */
                }
            ',
            'assertions' => [],
            'ignored_issues' => ['InvalidCast'],
        ];
        yield 'objectAmongOtherTypesIsCastToAString' => [
            'code' => '<?php
                /**
                 * @param object|int $object_or_int
                 * @param array<array-key, mixed>|object|int $anything
                 */
                function f($object_or_int, $anything, string $suffix): void {
                    $cast = (string) $object_or_int;
                    $anything_cast = (string) $anything;
                    $interpolated = "{$object_or_int}{$suffix}";
                    $anything_interpolated = "{$anything}{$suffix}";
                    $prefixed = "x{$object_or_int}";
                    /** @psalm-check-type-exact $cast = string */
                    /** @psalm-check-type-exact $anything_cast = string */
                    /** @psalm-check-type-exact $interpolated = string */
                    /** @psalm-check-type-exact $anything_interpolated = string */
                    /** @psalm-check-type-exact $prefixed = non-falsy-string */
                }
            ',
            'assertions' => [],
            'ignored_issues' => ['InvalidCast', 'PossiblyInvalidCast'],
        ];
        yield 'interpolationWithANeverPartIsNever' => [
            'code' => '<?php
                final class NeverString {
                    /** @return never */
                    public function __toString(): string {
                        throw new Exception();
                    }
                }

                function single(NeverString $v): never {
                    return "$v";
                }

                function prefixed(NeverString $v): never {
                    return "x$v";
                }

                function suffixed(NeverString $v, string $s): never {
                    return "$s{$v}y";
                }
            ',
            'assertions' => [],
            'ignored_issues' => ['NoValue'],
            'php_version' => '8.1',
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        yield 'unsetCastIsRemovedInPhpEight' => [
            'code' => '<?php
                $foo = 1;
                (unset) $foo;',
            'error_message' => 'UnrecognizedExpression - src' . DIRECTORY_SEPARATOR
                . 'somefile.php:3:17 - The (unset) cast was removed in PHP 8.0',
            'error_levels' => [],
            'php_version' => '8.0',
        ];
        yield 'arrayCastToStringIsInvalid' => [
            'code' => '<?php
                /** @param array<string> $arr */
                function f(array $arr): string {
                    return (string) $arr;
                }',
            'error_message' => 'InvalidCast - src' . DIRECTORY_SEPARATOR
                . 'somefile.php:4:37 - array<array-key, string> cannot be cast to string',
        ];
        yield 'arrayOrIntCastToStringIsPossiblyInvalid' => [
            'code' => '<?php
                /** @param array<string>|int $arr */
                function f($arr): string {
                    return "$arr";
                }',
            'error_message' => 'PossiblyInvalidCast - src' . DIRECTORY_SEPARATOR
                . 'somefile.php:4:29 - array<array-key, string> cannot be cast to string',
        ];
        yield 'arrayInterpolatedWithAPrefixIsInvalid' => [
            'code' => '<?php
                /** @param list{1, 2} $tuple */
                function f(array $tuple): string {
                    return "A: $tuple";
                }',
            'error_message' => 'InvalidCast - src' . DIRECTORY_SEPARATOR
                . 'somefile.php:4:32 - list{1, 2} cannot be cast to string',
        ];
    }
}
