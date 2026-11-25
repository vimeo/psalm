<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;

use const DIRECTORY_SEPARATOR;

/**
 * The assertions of `===`, `==`, `!==` and `!=`, which are built for both polarities of each kind of comparison
 */
final class ComparisonAssertionTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function testANullComparisonOfANonNullableValueIsReportedOnce(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function f(int $i): void {
                    if ($i === null) {}
                }',
        );
        $this->analyzeFile($file_path, new Context(), false);

        $this->assertSame(
            ['TypeDoesNotContainNull: int does not contain null'],
            array_map(
                static fn(IssueData $issue): string => $issue->type . ': ' . $issue->message,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    public function testALooseComparisonOfNumericStringsIsNotAnIdentityCheck(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function f(): void {
                    $a = "1";
                    $b = "01";
                    if ($a == $b) {}
                    if ($a != $b) {}
                }',
        );
        $this->analyzeFile($file_path, new Context(), false);

        // "1" == "01" is true; issues of the reconciler about it are out of scope here
        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            $this->assertStringNotContainsString('cannot be identical', $issue->message);
            $this->assertStringNotContainsString('can never contain', $issue->message);
        }
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'looseComparisonOfNumericStringLiteralsIsByTheirNumbers' => [
                'code' => '<?php
                    $one = "1";
                    $padded = "01";
                    $ten = "10";
                    $exponent = "1e1";
                    $spaced = " 1";

                    $matches = 0;

                    if ($one == $padded) {
                        $matches++;
                    }

                    if ($ten == $exponent) {
                        $matches++;
                    }

                    if ($one == $spaced) {
                        $matches++;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'looseEqualityToAScalarKeepsTheLooselyEqualBoolsAndNull' => [
                'code' => '<?php
                    /** @return int|false */
                    function intOrFalse() { return 1; }

                    /** @return int|bool */
                    function intOrBool() { return 1; }

                    /** @return string|null */
                    function stringOrNull() { return null; }

                    /** @return string|false */
                    function stringOrFalse() { return false; }

                    /** @return float|null */
                    function floatOrNull() { return null; }

                    /** @return 0|false */
                    function zeroOrFalse() { return 0; }

                    $a = intOrFalse();
                    $zeroOrFalse = $a == 0 ? $a : null;
                    $elseBranch = $a != 0 ? null : $a;

                    $b = intOrBool();
                    $zeroOrFalseFromBool = $b == 0 ? $b : null;
                    $fiveOrTrue = $b == 5 ? $b : null;

                    $c = stringOrNull();
                    $emptyOrNull = $c == "" ? $c : 1;
                    $zeroStringWithoutNull = $c == "0" ? $c : 1;

                    $d = stringOrFalse();
                    $zeroStringOrFalse = $d == "0" ? $d : null;
                    $nonFalsyString = $d == "a" ? $d : null;

                    $e = floatOrNull();
                    $zeroFloatOrNull = $e == 0.0 ? $e : 1;

                    $f = zeroOrFalse();
                    $unchanged = $f == 0 ? $f : null;',
                'assertions' => [
                    '$zeroOrFalse===' => '0|false|null',
                    '$elseBranch===' => '0|false|null',
                    '$zeroOrFalseFromBool===' => '0|false|null',
                    '$fiveOrTrue===' => '5|null|true',
                    '$emptyOrNull===' => "''|1|null",
                    '$zeroStringWithoutNull===' => "'0'|1",
                    '$zeroStringOrFalse===' => "'0'|false|null",
                    '$nonFalsyString===' => "'a'|null",
                    '$zeroFloatOrNull===' => '1|float(0)|null',
                    '$unchanged===' => '0|false|null',
                ],
                'ignored_issues' => ['LessSpecificReturnType'],
            ],
            'looseEqualityToAnIntKeepsTheLooselyEqualFloatsBoolsAndNull' => [
                'code' => '<?php
                    /** @return int|float|bool|null */
                    function scalarOrNull() { return 1; }

                    function anInt(): int { return 1; }

                    $a = scalarOrNull();
                    $i = anInt();
                    $equal = $a == $i ? $a : "no";',
                'assertions' => [
                    '$equal===' => "'no'|bool|float|int|null",
                ],
                'ignored_issues' => ['LessSpecificReturnType'],
            ],
            'looseEqualityLeavingATemplateWithABoolUnchangedKeepsTheOtherTypes' => [
                'code' => '<?php
                    /**
                     * @template T of array-key|true
                     * @param T|Stringable $x
                     */
                    function f($x): ?Stringable {
                        if ($x == "1" && is_object($x)) {
                            return $x;
                        }
                        return null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'looseEqualityKeepsEveryNumericallyEqualLiteral' => [
                'code' => '<?php
                    /** @return \'1\'|\'01\' */
                    function both(): string { return "1"; }

                    /** @return \'1\'|\'2\' */
                    function oneOrTwo(): string { return "1"; }

                    /** @return \'1\'|int */
                    function oneOrInt(): string|int { return 1; }

                    /** @return \'01\'|\'1\'|\'2\' */
                    function three(): string { return "1"; }

                    $a = both();
                    $sameNumber = $a == "01" ? $a : null;

                    $b = oneOrTwo();
                    $onlyOne = $b == "01" ? $b : null;
                    $notOne = $b != "01" ? $b : null;

                    $c = oneOrInt();
                    $withInt = $c == "01" ? $c : null;

                    $d = three();
                    $elseBranch = $d != "1" ? null : $d;',
                'assertions' => [
                    '$sameNumber===' => "'01'|'1'|null",
                    '$onlyOne===' => "'1'|null",
                    '$notOne===' => "'2'|null",
                    '$withInt===' => "'1'|int|null",
                    '$elseBranch===' => "'01'|'1'|null",
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'looseEqualityOfNumericStringsWithATrailingWhitespaceIsByTheirNumbersAsOfPhp8' => [
                'code' => '<?php
                    $one = "1";
                    $spaced = "1 ";

                    $matches = 0;

                    if ($one == $spaced) {
                        $matches++;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'identicalToNull' => [
                'code' => '<?php
                    function f(?int $n): void {
                        if ($n === null) {
                            /** @psalm-check-type-exact $n = null */;
                        } else {
                            /** @psalm-check-type-exact $n = int */;
                        }
                    }',
            ],
            'notIdenticalToNull' => [
                'code' => '<?php
                    function f(?int $n): void {
                        if ($n !== null) {
                            /** @psalm-check-type-exact $n = int */;
                        } else {
                            /** @psalm-check-type-exact $n = null */;
                        }
                    }',
            ],
            'looselyEqualToNull' => [
                'code' => '<?php
                    function f(?int $n): void {
                        if ($n == null) {
                            /** @psalm-check-type-exact $n = 0|null */;
                        } else {
                            /** @psalm-check-type-exact $n = int */;
                        }
                    }',
            ],
            'notLooselyEqualToNull' => [
                'code' => '<?php
                    function f(?int $n): void {
                        if ($n != null) {
                            /** @psalm-check-type-exact $n = int */;
                        } else {
                            /** @psalm-check-type-exact $n = 0|null */;
                        }
                    }',
            ],
            'nullsafePropertyNotIdenticalToNullNarrowsTheReceiver' => [
                'code' => '<?php
                    final class A { public ?int $p = null; }

                    function f(?A $a): void {
                        if ($a?->p !== null) {
                            /** @psalm-check-type-exact $a = A */;
                        } else {
                            /** @psalm-check-type-exact $a = A|null */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyIdenticalToNullDoesNotNarrowTheReceiver' => [
                'code' => '<?php
                    final class A { public ?int $p = null; }

                    function f(?A $a): void {
                        if ($a?->p === null) {
                            /** @psalm-check-type-exact $a = A|null */;
                        } else {
                            /** @psalm-check-type-exact $a = A */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'nullsafePropertyNotIdenticalToAValueNarrowsTheReceiverWhereItIsIdentical' => [
                'code' => '<?php
                    final class Item {
                        public bool $flag = true;

                        public function ok(): bool {
                            return true;
                        }
                    }

                    function f(?Item $item): bool {
                        if ($item?->flag !== true) {
                            /** @psalm-check-type-exact $item = Item|null */;
                            return false;
                        }

                        /** @psalm-check-type-exact $item = Item */;
                        return $item->flag;
                    }

                    function g(?Item $item, ?bool $maybe): bool {
                        if ($item?->flag !== $maybe) {
                            return false;
                        }

                        /** @psalm-check-type-exact $item = Item|null */;
                        return true;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'identicalToTrue' => [
                'code' => '<?php
                    /** @param int|bool $b */
                    function f($b): void {
                        if ($b === true) {
                            /** @psalm-check-type-exact $b = true */;
                        } else {
                            /** @psalm-check-type-exact $b = false|int */;
                        }
                    }',
            ],
            'notIdenticalToTrue' => [
                'code' => '<?php
                    /** @param int|bool $b */
                    function f($b): void {
                        if ($b !== true) {
                            /** @psalm-check-type-exact $b = false|int */;
                        } else {
                            /** @psalm-check-type-exact $b = true */;
                        }
                    }',
            ],
            'identicalToFalse' => [
                'code' => '<?php
                    /** @param int|bool $b */
                    function f($b): void {
                        if ($b === false) {
                            /** @psalm-check-type-exact $b = false */;
                        } else {
                            /** @psalm-check-type-exact $b = int|true */;
                        }
                    }',
            ],
            'notIdenticalToFalse' => [
                'code' => '<?php
                    /** @param int|bool $b */
                    function f($b): void {
                        if ($b !== false) {
                            /** @psalm-check-type-exact $b = int|true */;
                        } else {
                            /** @psalm-check-type-exact $b = false */;
                        }
                    }',
            ],
            'functionCallComparedWithTrueOrFalse' => [
                'code' => '<?php
                    /** @param mixed $m */
                    function f($m): void {
                        if (is_int($m) === true) {
                            /** @psalm-check-type-exact $m = int */;
                        }
                        if (is_int($m) !== true) {
                        } else {
                            /** @psalm-check-type-exact $m = int */;
                        }
                        if (is_int($m) === false) {
                        } else {
                            /** @psalm-check-type-exact $m = int */;
                        }
                        if (is_int($m) !== false) {
                            /** @psalm-check-type-exact $m = int */;
                        }
                    }',
            ],
            'templateOfBoolComparedWithTrueOrFalseIsNotRedundant' => [
                'code' => '<?php
                    /**
                     * @template T of bool
                     * @param T $t
                     */
                    function f($t): void {
                        if ($t === true) {}
                        if ($t !== true) {}
                        if ($t === false) {}
                        if ($t !== false) {}
                    }',
            ],
            'templateOfArrayComparedWithAnEmptyArrayIsNotRedundant' => [
                'code' => '<?php
                    /**
                     * @template T of array
                     * @param T $t
                     */
                    function f($t): void {
                        if ($t === []) {}
                        if ($t !== []) {}
                    }',
            ],
            'identicalToAnEmptyArray' => [
                'code' => '<?php
                    function f(array $a): void {
                        if ($a === []) {
                            /** @psalm-check-type-exact $a = array<never, never> */;
                        } else {
                            /** @psalm-check-type-exact $a = non-empty-array<array-key, mixed> */;
                        }
                    }',
            ],
            'notIdenticalToAnEmptyArray' => [
                'code' => '<?php
                    function f(array $a): void {
                        if ($a !== []) {
                            /** @psalm-check-type-exact $a = non-empty-array<array-key, mixed> */;
                        } else {
                            /** @psalm-check-type-exact $a = array<never, never> */;
                        }
                    }',
            ],
            'getDebugTypeIdentical' => [
                'code' => '<?php
                    final class A {}

                    function f(mixed $m): void {
                        if (get_debug_type($m) === "int") {
                            /** @psalm-check-type-exact $m = int */;
                        }
                        if (get_debug_type($m) === A::class) {
                            /** @psalm-check-type-exact $m = A */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getDebugTypeNotIdentical' => [
                'code' => '<?php
                    final class A {}

                    function f(mixed $m): void {
                        if (get_debug_type($m) !== "int") {
                        } else {
                            /** @psalm-check-type-exact $m = int */;
                        }
                        if (get_debug_type($m) !== A::class) {
                        } else {
                            /** @psalm-check-type-exact $m = A */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getDebugTypeComparedWithAStringThatIsNotATypeName' => [
                'code' => '<?php
                    function f(mixed $m): void {
                        if (get_debug_type($m) === "1") {}
                        if (get_debug_type($m) !== "1") {}
                        if (get_debug_type($m) === "") {}
                        if (get_debug_type($m) !== "") {}
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'getClassIdentical' => [
                'code' => '<?php
                    class A {}
                    final class B extends A {}

                    function f(A $a): void {
                        if (get_class($a) === B::class) {
                            /** @psalm-check-type-exact $a = B */;
                        } else {
                            /** @psalm-check-type-exact $a = A */;
                        }
                    }',
            ],
            'getClassNotIdentical' => [
                'code' => '<?php
                    class A {}
                    final class B extends A {}

                    function f(A $a): void {
                        if (get_class($a) !== B::class) {
                            /** @psalm-check-type-exact $a = A */;
                        } else {
                            /** @psalm-check-type-exact $a = B */;
                        }
                    }',
            ],
            'typedValueIdentical' => [
                'code' => '<?php
                    /** @param "a"|"b" $s */
                    function f(string $s): void {
                        if ($s === "a") {
                            /** @psalm-check-type-exact $s = "a" */;
                        } else {
                            /** @psalm-check-type-exact $s = "b" */;
                        }
                        if ($s == "a") {
                            /** @psalm-check-type-exact $s = "a" */;
                        } else {
                            /** @psalm-check-type-exact $s = "b" */;
                        }
                    }',
            ],
            'typedValueNotIdentical' => [
                'code' => '<?php
                    /** @param "a"|"b" $s */
                    function f(string $s): void {
                        if ($s !== "a") {
                            /** @psalm-check-type-exact $s = "b" */;
                        } else {
                            /** @psalm-check-type-exact $s = "a" */;
                        }
                        if ($s != "a") {
                            /** @psalm-check-type-exact $s = "b" */;
                        } else {
                            /** @psalm-check-type-exact $s = "a" */;
                        }
                    }',
            ],
            'countIdentical' => [
                'code' => '<?php
                    function f(array $a): void {
                        if (count($a) === 2) {
                            /** @psalm-check-type-exact $a = non-empty-array<array-key, mixed> */;
                        }
                        if (count($a) === 0) {
                            /** @psalm-check-type-exact $a = array<never, never> */;
                        } else {
                            /** @psalm-check-type-exact $a = non-empty-array<array-key, mixed> */;
                        }
                    }',
            ],
            'countNotIdentical' => [
                'code' => '<?php
                    function f(array $a): void {
                        if (count($a) !== 2) {
                        } else {
                            /** @psalm-check-type-exact $a = non-empty-array<array-key, mixed> */;
                        }
                        if (count($a) !== 0) {
                            /** @psalm-check-type-exact $a = non-empty-array<array-key, mixed> */;
                        } else {
                            /** @psalm-check-type-exact $a = array<never, never> */;
                        }
                    }',
            ],
            'identicalToAUnionOfClassStringsKeepsEachOfThem' => [
                'code' => '<?php
                    final class A {}
                    final class B {}

                    /** @param class-string<A>|class-string<B> $y */
                    function f(string $x, string $y): void {
                        if ($x === $y) {
                            /** @psalm-check-type-exact $x = class-string<A>|class-string<B> */;
                        }
                        if ($x !== $y) {
                        } else {
                            /** @psalm-check-type-exact $x = class-string<A>|class-string<B> */;
                        }
                        if ($x === $y && $x === A::class) {}
                    }',
            ],
            'identicalToAUnionOfGenericObjectsKeepsEachOfThem' => [
                'code' => '<?php
                    /** @template T */
                    final class Box {}

                    /** @param Box<int>|Box<string> $y */
                    function f(object $x, object $y): void {
                        if ($x === $y) {
                            /** @psalm-check-type-exact $x = Box<int>|Box<string> */;
                        }
                        if ($x !== $y) {
                        } else {
                            /** @psalm-check-type-exact $x = Box<int>|Box<string> */;
                        }
                    }',
            ],
            'intLiteralOnEitherSideOfAnOrderComparison' => [
                'code' => '<?php
                    function f(int $i): void {
                        if ($i > 5) {
                            /** @psalm-check-type-exact $i = int<6, max> */;
                        }
                        if (-5 > $i) {
                            /** @psalm-check-type-exact $i = int<min, -6> */;
                        }
                        if ($i < +3) {
                            /** @psalm-check-type-exact $i = int<min, 2> */;
                        }
                        if (3 <= $i) {
                            /** @psalm-check-type-exact $i = int<3, max> */;
                        }
                    }',
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'nullsafePropertyNeverIdenticalToAValueStaysARedundantCondition' => [
                'code' => '<?php
                    final class Item {
                        public bool $flag = true;
                    }

                    function f(?Item $item): bool {
                        if ($item?->flag !== "x") {
                            return false;
                        }

                        return true;
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'looseComparisonOfDifferentNonNumericStringLiteralsIsNeverTrue' => [
                'code' => '<?php
                    $first = "abc";
                    $second = "abd";

                    if ($first == $second) {
                        echo 1;
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'looseInequalityOfNumericStringLiteralsWithTheSameNumberIsNeverTrue' => [
                'code' => '<?php
                    $one = "1";
                    $padded = "01";

                    if ($one != $padded) {
                        echo 1;
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.2',
            ],
            'looseEqualityOfNumericStringsWithATrailingWhitespaceIsNotByTheirNumbersBeforePhp8' => [
                'code' => '<?php
                    $one = "1";
                    $spaced = "1 ";

                    if ($one == $spaced) {
                        echo 1;
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '7.4',
            ],
            'nullNeverIdenticalToANonNullableValue' => [
                'code' => '<?php
                    function f(string $s): void {
                        if ($s === null) {}
                    }',
                'error_message' => 'TypeDoesNotContainNull - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:29 - string does not contain null',
            ],
            'nullNeverNotIdenticalToANonNullableValue' => [
                'code' => '<?php
                    function f(string $s): void {
                        if ($s !== null) {}
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:29 - string can never be null',
            ],
            'trueNeverIdenticalToAnInt' => [
                'code' => '<?php
                    function f(int $i): void {
                        if ($i === true) {}
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:29 - int does not contain true',
            ],
            'trueNeverNotIdenticalToAnInt' => [
                'code' => '<?php
                    function f(int $i): void {
                        if ($i !== true) {}
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:29 - int can never be true',
            ],
            'falseNeverIdenticalToADocblockInt' => [
                'code' => '<?php
                    /** @param int $i */
                    function f($i): void {
                        if ($i === false) {}
                    }',
                'error_message' => 'DocblockTypeContradiction - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:29 - int does not contain false',
            ],
            'falseNeverNotIdenticalToADocblockInt' => [
                'code' => '<?php
                    /** @param int $i */
                    function f($i): void {
                        if ($i !== false) {}
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:29 - Docblock-defined type int can never be false',
            ],
            'emptyArrayNeverIdenticalToAString' => [
                'code' => '<?php
                    function f(string $s): void {
                        if ($s === []) {}
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:29 - string does not contain an empty array',
            ],
            'emptyArrayNeverNotIdenticalToAString' => [
                'code' => '<?php
                    function f(string $s): void {
                        if ($s !== []) {}
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:29 - string can never be an empty array',
            ],
            'gettypeIdenticalToAValueItCannotReturn' => [
                'code' => '<?php
                    /** @param mixed $m */
                    function f($m): void {
                        if (gettype($m) === "int") {}
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
            'gettypeNotIdenticalToAValueItCannotReturn' => [
                'code' => '<?php
                    /** @param mixed $m */
                    function f($m): void {
                        if (gettype($m) !== "int") {}
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
            'getClassIdenticalToAnUndefinedClass' => [
                'code' => '<?php
                    function f(object $o): void {
                        if (get_class($o) === Missing::class) {}
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'getClassNotIdenticalToAnUndefinedClass' => [
                'code' => '<?php
                    function f(object $o): void {
                        if (get_class($o) !== Missing::class) {}
                    }',
                'error_message' => 'UndefinedClass',
            ],
            'firstClassCallableComparedWithTrueOrFalse' => [
                'code' => '<?php
                    function f(): void {
                        if (strlen(...) !== false) {}
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'firstClassCallableIdenticalToFalse' => [
                'code' => '<?php
                    function f(): void {
                        if (strlen(...) === false) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'docblockValuesThatCannotBeIdenticalComparedNotIdentical' => [
                'code' => '<?php
                    /**
                     * @param 1 $a
                     * @param 2 $b
                     */
                    function f($a, $b): void {
                        if ($a !== $b) {}
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:29 - Docblock-defined type 2 can never contain 1',
            ],
            'looseEqualityOfATemplateToZeroKeepsFalse' => [
                'code' => '<?php
                    /**
                     * @template T of int|false
                     * @param T $x
                     */
                    function f($x): int {
                        if ($x == 0) {
                            return $x;
                        }
                        return 1;
                    }',
                'error_message' => 'FalsableReturnStatement',
            ],
            'getClassIdenticalToEitherOfTwoTemplatedClassStrings' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @template U of object
                     * @param class-string<T> $t
                     * @param class-string<U> $u
                     * @return T
                     */
                    function f(object $o, string $t, string $u): object {
                        if (get_class($o) === $t || get_class($o) === $u) {
                            return $o;
                        }
                        throw new Exception();
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:36 - The inferred type \'(T:fn-f as object)|(U:fn-f as object)\' does not match',
            ],
            'getClassLooselyEqualToEitherOfTwoTemplatedClassStrings' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @template U of object
                     * @param class-string<T> $t
                     * @param class-string<U> $u
                     * @return T
                     */
                    function f(object $o, string $t, string $u): object {
                        if (get_class($o) == $t || get_class($o) == $u) {
                            return $o;
                        }
                        throw new Exception();
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:36 - The inferred type \'(T:fn-f as object)|(U:fn-f as object)\' does not match',
            ],
            'getClassIdenticalToEitherOfTwoTemplatedClassStringsInANegatedCondition' => [
                'code' => '<?php
                    /**
                     * @template T of object
                     * @template U of object
                     * @param class-string<T> $t
                     * @param class-string<U> $u
                     * @return T
                     */
                    function f(object $o, string $t, string $u): object {
                        if (!(get_class($o) === $t || get_class($o) === $u)) {
                            throw new Exception();
                        }
                        return $o;
                    }',
                'error_message' => 'InvalidReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:13:32 - The inferred type \'(T:fn-f as object)|(U:fn-f as object)\' does not match',
            ],
            'looseEqualityOfTrueToZeroIsImpossible' => [
                'code' => '<?php
                    /** @param true|resource $x */
                    function f($x): void {
                        if ($x == 0) {}
                    }',
                'error_message' => 'DocblockTypeContradiction - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:29',
            ],
            'looseEqualityOfIntsThatCannotBeIdentical' => [
                'code' => '<?php
                    function f(): void {
                        $one = 1;
                        $two = 2;
                        if ($one == $two) {}
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:29 - 2 cannot be identical to 1',
            ],
            'looseInequalityOfIntsThatCannotBeIdentical' => [
                'code' => '<?php
                    function f(): void {
                        $one = 1;
                        $two = 2;
                        if ($one != $two) {}
                    }',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:29 - 2 can never contain 1',
            ],
        ];
    }
}
