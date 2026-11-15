<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_column;

use const DIRECTORY_SEPARATOR;

final class TypeAlgebraTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testOffsetOfAPropertyAssignedInTheIfIsKnownAfterIt(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                final class Channels {
                    /** @var array<string, object> */
                    protected array $channels = [];

                    /** @param mixed $channel */
                    public function get($channel): object {
                        if (!isset($this->channels[$channel])) {
                            $this->channels[$channel] = new stdClass();
                        }

                        return $this->channels[$channel];
                    }
                }',
        );

        $this->analyzeFile($file_path, new Context(), false);

        // the offset is mixed where it is tested and assigned, not again where it is read after the if
        self::assertSame(
            ['MixedArrayOffset', 'MixedArrayOffset', 'MixedPropertyTypeCoercion'],
            array_column(IssueBuffer::getIssuesDataForFile($file_path), 'type'),
        );
    }

    public function testIssuesShowAComparisonWithoutWhatItComparesWith(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile(
            $file_path,
            '<?php
                /** @param list<int> $ids */
                function f(?string $id, array $ids): void {
                    if (!in_array($id, $ids, true)) {
                        echo "not in";
                    }
                }

                /**
                 * @param mixed $x
                 * @psalm-assert "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-one" $x
                 */
                function assertOne($x): void {}

                function g(int $x): void {
                    assertOne($x);
                }',
        );

        $this->analyzeFile($file_path, new Context(), false);

        self::assertSame(
            [
                'Operand of type true is always truthy',
                'Type null|string for $id is never in-array-int',
                'Type int for $x is never '
                    . "'https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarte...'",
            ],
            array_column(IssueBuffer::getIssuesDataForFile($file_path), 'message'),
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'isAAndItsNegationAreDifferentConditions' => [
                'code' => '<?php
                    final class A {}
                    function f(object $a, ?int $b): void {
                        if (!is_a($a, A::class) && $b === null) {
                            return;
                        }
                        if (is_a($a, A::class)) {
                            /** @psalm-check-type-exact $b = int|null */
                            echo "is_a";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'isAIsStillTheNegationOfNotIsA' => [
                'code' => '<?php
                    final class A {}
                    function f(object $a, ?int $b): void {
                        if (is_a($a, A::class) && $b === null) {
                            return;
                        }
                        if (is_a($a, A::class)) {
                            /** @psalm-check-type-exact $b = int */
                            echo "is_a";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'definedAndItsNegationAreDifferentConditions' => [
                'code' => '<?php
                    function f(?int $b): void {
                        if (!defined("FOO") && $b === null) {
                            return;
                        }
                        if (defined("FOO")) {
                            /** @psalm-check-type-exact $b = int|null */
                            echo "defined";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'definedIsStillTheNegationOfNotDefined' => [
                'code' => '<?php
                    function f(?int $b): void {
                        if (defined("FOO") && $b === null) {
                            return;
                        }
                        if (defined("FOO")) {
                            /** @psalm-check-type-exact $b = int */
                            echo "defined";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notIssetIsNotItsOwnNegation' => [
                'code' => '<?php
                    /** @param array<string, int> $arr */
                    function f(array $arr, bool $x, bool $y): void {
                        if ((!isset($arr["k"]) || $x) && (!isset($arr["k"]) || $y) && ($x || $y)) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $arr = array<string, int> */
                            echo "else";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notIssetIsTheNegationOfIsset' => [
                'code' => '<?php
                    /** @param array<string, int> $arr */
                    function f(array $arr, bool $x): void {
                        if ((isset($arr["k"]) || $x) && (!isset($arr["k"]) || $x)) {
                            /** @psalm-check-type-exact $x = true */
                            echo "if";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inArrayOfAnotherArrayOfTheSameTypeIsADifferentCondition' => [
                'code' => '<?php
                    /**
                     * @param list<string> $allowed
                     * @param list<string> $other
                     */
                    function f(string $a, array $allowed, array $other, ?int $b): void {
                        if (in_array($a, $allowed, true) && $b === null) {
                            return;
                        }
                        if (in_array($a, $other, true)) {
                            /** @psalm-check-type-exact $b = int|null */
                            echo "in";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inArrayOfTwoCallsIsADifferentCondition' => [
                'code' => '<?php
                    /** @return list<string> */
                    function getList(): array {
                        return [];
                    }
                    function f(string $a, ?int $b): void {
                        if (in_array($a, getList(), true) && $b === null) {
                            return;
                        }
                        if (in_array($a, getList(), true)) {
                            /** @psalm-check-type-exact $b = int|null */
                            echo "in";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'inArrayOfTheSameArrayIsTheSameCondition' => [
                'code' => '<?php
                    /** @param list<string> $allowed */
                    function f(string $a, array $allowed, ?int $b): void {
                        if (in_array($a, $allowed, true) && $b === null) {
                            return;
                        }
                        if (in_array($a, $allowed, true)) {
                            /** @psalm-check-type-exact $b = int */
                            echo "in";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'identicalToOneOfTwoArraysOfDifferentShapesKeepsBoth' => [
                'code' => '<?php
                    /**
                     * @param array{id: int} $row
                     * @param array{error: string} $failure
                     */
                    function f(array $x, array $row, array $failure): void {
                        if ($x === $row || $x === $failure) {
                            /** @psalm-check-type-exact $x = array{error?: string, id?: int} */
                            echo "identical";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notIdenticalToAnotherStringIsNotTheNegationOfIdentical' => [
                'code' => '<?php
                    function f(string $a, string $b, string $c, ?int $n): void {
                        if ($a === $b && ($a !== $c || $n !== null)) {
                            /** @psalm-check-type-exact $n = int|null */
                            echo "identical";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notIdenticalToAnotherCallIsNotTheNegationOfIdentical' => [
                'code' => '<?php
                    function getString(): string {
                        return "";
                    }
                    function f(string $a, ?int $n): void {
                        if ($a === getString() && ($a !== getString() || $n !== null)) {
                            /** @psalm-check-type-exact $n = int|null */
                            echo "identical";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notLooselyEqualToAnotherObjectIsNotTheNegationOfLooselyEqual' => [
                'code' => '<?php
                    final class A {}
                    function f(A $a, A $b, A $c, ?int $n): void {
                        if ($a == $b && ($a != $c || $n !== null)) {
                            /** @psalm-check-type-exact $n = int|null */
                            echo "equal";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notIdenticalToTheSameStringIsTheNegationOfIdentical' => [
                'code' => '<?php
                    function f(string $a, string $b, ?int $n): void {
                        if ($a === $b && ($a !== $b || $n !== null)) {
                            /** @psalm-check-type-exact $n = int */
                            echo "identical";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'notLooselyEqualToTheSameObjectIsTheNegationOfLooselyEqual' => [
                'code' => '<?php
                    final class A {}
                    function f(A $a, A $b, ?int $n): void {
                        if ($a == $b && ($a != $b || $n !== null)) {
                            /** @psalm-check-type-exact $n = int */
                            echo "equal";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'longStringLiteralsSharingTheirStartAreNotEachOthersNegation' => [
                'code' => '<?php
                    const ONE = "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-one";
                    const TWO = "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-two";
                    function f(string $x, bool $y): void {
                        if (($x === ONE || $x !== TWO) && $y) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $y = bool */
                            echo "else";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'aLongStringLiteralIsTheNegationOfItself' => [
                'code' => '<?php
                    const ONE = "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-one";
                    function f(string $x, bool $y): void {
                        if (($x === ONE || $x !== ONE) && $y) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $y = false */
                            echo "else";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assertedLongStringLiteralsSharingTheirStartAreNotEachOthersNegation' => [
                'code' => '<?php
                    /** @psalm-assert-if-true "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-one" $x */
                    function isOne(string $x): bool {
                        return $x === "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-one";
                    }
                    /** @psalm-assert-if-true "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-two" $x */
                    function isTwo(string $x): bool {
                        return $x === "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-two";
                    }
                    function f(string $x, ?int $b): void {
                        if (isOne($x) || $b !== null) {
                            if (!isTwo($x)) {
                                /** @psalm-check-type-exact $b = int|null */
                                echo "not two";
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'anAssertedLongStringLiteralIsTheNegationOfItself' => [
                'code' => '<?php
                    /** @psalm-assert-if-true "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-one" $x */
                    function isOne(string $x): bool {
                        return $x === "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-one";
                    }
                    /** @psalm-assert-if-true "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-two" $x */
                    function isTwo(string $x): bool {
                        return $x === "https://example.org/api/v2/organisations/acme/projects/psalm/reports/2026/quarterly/summary-two";
                    }
                    function f(string $x, ?int $b): void {
                        if (isOne($x) || $b !== null) {
                            if (!isOne($x)) {
                                /** @psalm-check-type-exact $b = int */
                                echo "not one";
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assignedTypeChecksOfAnOrNarrowToEitherType' => [
                'code' => '<?php
                    function f(mixed $x): void {
                        if (($ok = is_int($x)) || ($ok = is_string($x))) {
                            /** @psalm-check-type-exact $x = int|string */
                            echo "yes";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'anAssignedTypeCheckOrAnotherConditionDoesNotNarrow' => [
                'code' => '<?php
                    function f(mixed $x, bool $y): void {
                        if (($ok = is_int($x)) || $y) {
                            /** @psalm-check-type-exact $x = mixed */
                            echo $ok ? "yes" : "no";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'theElseOfAnAndWithAnOperandThatAssertsNothingWhenFalseDoesNotNarrowTheOtherOperand' => [
                'code' => '<?php
                    function f(?int $userId, ?string $filter, string $name): void {
                        if ($userId !== null && ($filter === null || !str_contains($name, $filter))) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $userId = int|null */
                            if ($userId !== null) {
                                /** @psalm-check-type-exact $filter = string */
                                echo "reached";
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'anEarlyReturnOnAnOrWithAnOperandThatAssertsNothingWhenFalseStillNarrows' => [
                'code' => '<?php
                    function f(?string $filter, string $name): void {
                        if ($filter === null || !str_contains($name, $filter)) {
                            return;
                        }
                        /** @psalm-check-type-exact $filter = string */
                        echo $filter;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'theElseOfAConditionComparedToFalseDoesNotAssertWhatItsNegationLeftOut' => [
                'code' => '<?php
                    function f(?int $a, string $s, string $t): void {
                        if (($a === null || !($s === $t)) === false) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $a = int|null */
                            echo "else";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'pastAnEarlyReturnOnAnOrComparedToFalseItsNonLiteralEqualityIsKept' => [
                'code' => '<?php
                    function f(?int $a, string $b, string $c): void {
                        if (($a === null || $b === $c) === false) {
                            return;
                        }
                        /** @psalm-check-type-exact $a = int|null */
                        echo "past";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'theElseOfAnOrWithANonLiteralEqualityStillNarrowsTheOtherOperand' => [
                'code' => '<?php
                    function f(?int $a, string $b, string $c): void {
                        if ($a === null || $b === $c) {
                            /** @psalm-check-type-exact $a = int|null */
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $a = int */
                            echo "else";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'theNegationOfAConjunctionDoesNotDependOnTheOrderOfItsOperands' => [
                'code' => '<?php
                    function f(int|string|null $a, bool $x): void {
                        if ((is_int($a) || is_string($a)) && (!is_int($a) || $x)) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $a = int|null */
                            echo "else";
                        }
                    }

                    function swapped(int|string|null $a, bool $x): void {
                        if ((!is_int($a) || $x) && (is_int($a) || is_string($a))) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $a = int|null */
                            echo "else";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'anOrAssigningAVariableOnTheRightDoesNotAssertItsNewValueWhereTheLeftIsTrue' => [
                'code' => '<?php
                    function load(?int $x): ?int {
                        return $x;
                    }

                    function f(?int $a, ?int $p): void {
                        if ($a === null || ($a = load($p)) !== null) {
                            /** @psalm-check-type-exact $a = int|null */
                            echo "if";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'pastAnEarlyReturnOnAnOrAssigningAVariableOnTheRightItsNewValueIsNarrowed' => [
                'code' => '<?php
                    function load(?int $x): ?int {
                        return $x;
                    }

                    function f(?int $a, ?int $p): void {
                        if ($a === null || ($a = load($p)) === null) {
                            return;
                        }
                        /** @psalm-check-type-exact $a = int */
                        echo $a;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'theElseOfAnAndWithAnOrTooComplexToCombineDoesNotNarrowTheOtherOperand' => [
                'code' => '<?php
                    function g(): bool {
                        return rand(0, 1) === 1;
                    }

                    function f(?int $y): void {
                        if ($y !== null
                            && (
                                (g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g())
                                || (g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g())
                                || (g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g())
                                || (g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g() && g())
                                || g()
                            )
                        ) {
                            /** @psalm-check-type-exact $y = int */
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $y = int|null */
                            echo "else";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'aConditionWithAnAlternativeThatCanNotBeNegatedIsNoParadox' => [
                'code' => '<?php
                    final class A {}

                    function f(?A $a, ?A $b, string $s, string $t): void {
                        if ($a && $b) {
                            return;
                        }
                        if (($s !== $t || $a) && ($s !== $t || $b)) {
                            echo "reached";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'aDoWhileConditionWithTooManyPartlyNegatableClausesIsAnalyzed' => [
                'code' => '<?php
                    function f(
                        ?int $q,
                        bool $a,
                        bool $b,
                        bool $c,
                        bool $d,
                        bool $e,
                        bool $f,
                        bool $g,
                        bool $h,
                        string $name
                    ): void {
                        do {
                            echo "body";
                        } while ($q !== null
                            && (($a && $b && $c && $d) || ($e && $f && $g && $h) || !trait_exists($name, false))
                        );
                        /** @psalm-check-type-exact $q = int|null */
                        echo "after";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'aDoWhileConditionTooComplexToNegateIsAnalyzed' => [
                'code' => '<?php
                    function f(?int $q, bool $a, bool $b, bool $c, bool $d, bool $e, bool $f, bool $g, bool $h): void {
                        do {
                            echo "body";
                        } while ($q !== null
                            && (($a && $b && $c && $d)
                                || ($e && $f && $g && $h)
                                || ($a && $c && $e && $g)
                                || ($b && $d && $f && $h))
                        );
                        echo "after";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'aFalsyAssertionOnAConditionTooComplexToNegateIsAnalyzed' => [
                'code' => '<?php
                    /** @psalm-assert falsy $value */
                    function assertFalsy(mixed $value): void {
                        if ($value) {
                            throw new Exception();
                        }
                    }

                    function f(bool $a, bool $b, bool $c, bool $d, bool $e, bool $f, bool $g, bool $h): void {
                        assertFalsy(($a && $b && $c && $d)
                            || ($e && $f && $g && $h)
                            || ($a && $c && $e && $g)
                            || ($b && $d && $f && $h));
                        echo "after";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'anIfConditionAssigningAnAndKeepsTheNarrowingOfItsOperands' => [
                'code' => '<?php
                    function takesInt(int $x): void {}

                    function f(?int $min, int $w): bool {
                        if ($wrapped = $min && $w > 3) {
                            takesInt($min);
                        }

                        return $wrapped;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'anIfConditionAssigningAComparisonKeepsTheNarrowingOfItsOperand' => [
                'code' => '<?php
                    function f(?float $a): void {
                        if ($r = $a !== null && $a > 1.0) {
                            /** @psalm-check-type-exact $a = float */
                            echo $a - 1.0;
                        }
                    }',
            ],
            'anIfConditionReassigningAVariableKeepsOnlyTheClausesOfItsNewValue' => [
                'code' => '<?php
                    function f(?int $min, ?int $other): void {
                        $r = $other !== null;

                        if ($r = $min !== null) {
                            /** @psalm-check-type-exact $min = int */
                            echo $min;
                            /** @psalm-check-type-exact $other = int|null */
                            echo (int) $other;
                        }
                    }',
            ],
            'anElseifConditionAssigningAnAndKeepsTheNarrowingOfItsOperands' => [
                'code' => '<?php
                    function f(?int $min, int $w, bool $b): void {
                        if ($b) {
                            echo 1;
                        } elseif ($r = $min !== null && $w > 3) {
                            /** @psalm-check-type-exact $min = int */
                            echo $min;
                        }
                    }',
            ],
            'anElseifConditionReassigningAVariableKeepsOnlyTheClausesOfItsNewValue' => [
                'code' => '<?php
                    function f(?int $min, ?int $other, bool $b): void {
                        $r = $other !== null;

                        if ($b) {
                            echo 1;
                        } elseif ($r = $min !== null) {
                            /** @psalm-check-type-exact $min = int */
                            echo $min;
                            /** @psalm-check-type-exact $other = int|null */
                            echo (int) $other;
                        }
                    }',
            ],
            'anIfConditionRepeatingAnAssignedConditionKeepsTheNarrowingOfItsOperands' => [
                'code' => '<?php
                    function f(?int $min, int $w): void {
                        if ($wrapped = $min !== null && $w > 3) {
                            echo $min;
                        }

                        if ($wrapped = $min !== null && $w > 3) {
                            /** @psalm-check-type-exact $min = int */
                            echo $min;
                        }
                    }',
            ],
            'anElseifConditionRepeatingAnAssignedConditionKeepsTheNarrowingOfItsOperands' => [
                'code' => '<?php
                    function f(?int $min, int $w, bool $b): void {
                        if ($wrapped = $min !== null && $w > 3) {
                            echo $min;
                        }

                        if ($b) {
                            echo 1;
                        } elseif ($wrapped = $min !== null && $w > 3) {
                            /** @psalm-check-type-exact $min = int */
                            echo $min;
                        }
                    }',
            ],
            'anIfConditionAssigningAVariableOnTheRightOfAnAndKeepsOnlyTheClausesOfItsNewValue' => [
                'code' => '<?php
                    function f(?int $min, ?int $other): void {
                        $r = $other !== null;

                        if (rand(0, 1) && ($r = $min !== null)) {
                            /** @psalm-check-type-exact $other = int|null */
                            echo (int) $other;
                        }
                    }',
            ],
            'theElseOfAnElseifAssigningOnTheRightOfAnOrHasNoClausesOfTheOldValue' => [
                'code' => '<?php
                    function load(?int $x): ?int {
                        return $x;
                    }

                    function f(?int $a, ?int $p, bool $z): void {
                        if ($z) {
                        } elseif ($a === null || ($a = load($p))) {
                        } else {
                            /** @psalm-check-type-exact $a = 0|null */
                            echo 1;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'aWhileLoopAssigningOnTheRightOfAnOrIsLeftWithoutClausesOfTheOldValue' => [
                'code' => '<?php
                    function load(?int $x): ?int {
                        return $x;
                    }

                    function f(?int $a, ?int $p): void {
                        while ($a === null || ($a = load($p))) {
                            return;
                        }

                        /** @psalm-check-type-exact $a = 0|null */
                        echo 1;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'aWhileLoopAssigningOnTheRightOfAnAndIsLeftWhereEitherSideIsFalse' => [
                'code' => '<?php
                    function nextOrNull(int $v): ?int {
                        return $v > 5 ? null : $v + 1;
                    }

                    function f(?int $a): void {
                        while ($a !== null && ($a = nextOrNull($a)) !== null) {
                            echo 1;
                        }

                        /** @psalm-check-type-exact $a = null */
                        echo 1;
                    }',
            ],
            'aDoWhileLoopAssigningOnTheRightOfAnAndIsLeftWhereTheLeftSideIsFalse' => [
                'code' => '<?php
                    function nextOrNull(int $v): ?int {
                        return $v > 5 ? null : $v + 1;
                    }

                    function f(?int $a): void {
                        do {
                            echo 1;
                        } while ($a !== null && ($a = nextOrNull($a)) === null);

                        /** @psalm-check-type-exact $a = int|null */
                        echo 1;
                    }',
            ],
            'theDefaultOfASwitchTrueCaseAssigningOnTheRightOfAnAndIsWhereTheLeftSideIsFalse' => [
                'code' => '<?php
                    function nextOrNull(int $v): ?int {
                        return $v > 5 ? null : $v + 1;
                    }

                    function f(?int $a): void {
                        switch (true) {
                            case $a !== null && ($a = nextOrNull($a)) === null:
                                break;
                            default:
                                /** @psalm-check-type-exact $a = int|null */
                                echo 1;
                        }
                    }',
            ],
            'aNegatedAndAssigningOnItsRightIsFalseWhereTheRightSideIsTrue' => [
                'code' => '<?php
                    function load(?int $x): ?int {
                        return $x;
                    }

                    function f(?int $a, ?int $p): void {
                        if (!($a === null && ($a = load($p)))) {
                            return;
                        }

                        /** @psalm-check-type-exact $a = int */
                        echo 1;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'aNegatedOrAssigningOnItsRightIsFalseWhereEitherSideIsTrue' => [
                'code' => '<?php
                    function load(int $x): ?int {
                        return $x > 5 ? null : $x;
                    }

                    function f(?int $a): void {
                        if (!($a === null || ($a = load(0)) === 0)) {
                        } else {
                            /** @psalm-check-type-exact $a = 0|null */
                            echo 1;
                        }
                    }',
            ],
            'anAndAssigningOnItsRightIdenticalToFalseIsWhereTheLeftSideIsFalse' => [
                'code' => '<?php
                    function nextOrNull(int $v): ?int {
                        return $v > 5 ? null : $v + 1;
                    }

                    function f(?int $a): void {
                        if (($a !== null && ($a = nextOrNull($a)) === null) === false) {
                            /** @psalm-check-type-exact $a = int|null */
                            echo 1;
                        }
                    }',
            ],
            'theRightOperandOfAnOrAfterAnOrAssigningOnItsRightHasNoClausesOfTheOldValue' => [
                'code' => '<?php
                    function load(?int $x): ?int {
                        return $x;
                    }

                    function f(?int $a, ?int $p): void {
                        if (($a === null || ($a = load($p))) || $a === null) {
                            echo 1;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'aFalsyAssertionOfAnAndAssigningOnItsRightHoldsWhereTheLeftSideIsFalse' => [
                'code' => '<?php
                    function nextOrNull(int $v): ?int {
                        return $v > 5 ? null : $v + 1;
                    }

                    /**
                     * @param mixed $v
                     * @psalm-assert falsy $v
                     */
                    function assertFalsy($v): void {}

                    function f(?int $a): void {
                        assertFalsy($a !== null && ($a = nextOrNull($a)) === null);

                        /** @psalm-check-type-exact $a = int|null */
                        echo 1;
                    }',
            ],
            'theElseAfterAnElseifReassigningAVariableHasNoNegatedClausesOfItsOldValue' => [
                'code' => '<?php
                    function load(): ?int {
                        return null;
                    }

                    function f(?int $a): void {
                        if ($a === null) {
                        } elseif ($a = load()) {
                        } else {
                            /** @psalm-check-type-exact $a = 0|null */
                            echo 1;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'aVariablePassedByReferenceOnTheRightOfAnAndLosesWhatTheLeftSideTestedOfIt' => [
                'code' => '<?php
                    function clear(?int &$v): bool {
                        $v = null;
                        return true;
                    }

                    final class A {
                        public function clear(?int &$v): bool {
                            $v = null;
                            return true;
                        }

                        public static function staticClear(?int &$v): bool {
                            $v = null;
                            return true;
                        }
                    }

                    function f(?int $x, A $a): void {
                        if ($x !== null && clear($x) && $x === null) {
                            echo 1;
                        }

                        if ($x !== null && $a->clear($x) && $x === null) {
                            echo 1;
                        }

                        if ($x !== null && A::staticClear($x) && $x === null) {
                            echo 1;
                        }
                    }',
            ],
            'aPropertyOrItemPassedByReferenceOnTheRightOfAnAndLosesWhatTheLeftSideTestedOfIt' => [
                'code' => '<?php
                    function clear(?int &$v): bool {
                        $v = null;
                        return true;
                    }

                    final class A {
                        public ?int $p = null;

                        public function f(): void {
                            if ($this->p !== null && clear($this->p) && $this->p === null) {
                                echo 1;
                            }
                        }
                    }

                    /** @param array{k: ?int} $a */
                    function f(array $a): void {
                        if ($a["k"] !== null && clear($a["k"]) && $a["k"] === null) {
                            echo 1;
                        }
                    }',
            ],
            'anArgumentPassedByValueOnTheRightOfAnAndKeepsWhatTheLeftSideTestedOfIt' => [
                'code' => '<?php
                    function positive(int $v): bool {
                        return $v > 0;
                    }

                    function f(?int $x, array $a): void {
                        if ($x !== null && positive($x)) {
                            /** @psalm-check-type-exact $x = int */
                            echo 1;
                        }

                        if ($a !== [] && count($a) > 1) {
                            /** @psalm-check-type-exact $a = non-empty-array<array-key, mixed> */
                            echo 1;
                        }
                    }',
            ],
            'aBuiltinTakingAnArgumentByReferenceOnTheRightOfAnAndLosesWhatTheLeftSideTestedOfIt' => [
                'code' => '<?php
                    function f(array $a): void {
                        if ($a !== [] && array_pop($a) !== null && $a === []) {
                            echo 1;
                        }
                    }',
            ],
            'theNegationOfAConditionPassingAVariableByReferenceOnTheRightKeepsBothValues' => [
                'code' => '<?php
                    function refill(?int &$v): bool {
                        $v = rand(0, 1) ? null : 1;
                        return (bool) rand(0, 1);
                    }

                    function loop(?int $x): void {
                        do {
                            echo 1;
                        } while ($x === null || refill($x));

                        /** @psalm-check-type-exact $x = int|null */
                        echo 1;
                    }

                    function negatedAnd(?int $x): void {
                        if (!($x !== null && refill($x))) {
                            return;
                        }

                        /** @psalm-check-type-exact $x = int|null */
                        echo 1;
                    }

                    function ternary(?int $x): void {
                        $r = ($x === null || refill($x)) ? 1 : $x;

                        /** @psalm-check-type-exact $r = int|null */
                        echo 1;
                    }',
            ],
            'aSwitchCasePassingAVariableByReferenceHasNoNegatedClausesOfItsOldValue' => [
                'code' => '<?php
                    function refill(?int &$v): bool {
                        $v = rand(0, 1) ? null : 1;
                        return true;
                    }

                    function f(?int $x): int {
                        switch (true) {
                            case $x === null:
                                return 0;
                            case refill($x) && $x === null:
                                return 1;
                        }

                        return 2;
                    }',
            ],
            'anAssertFunctionReassigningAParameterAssertsNothingOfItsArgumentFromThere' => [
                'code' => '<?php
                    function load(?int $x): ?int {
                        return $x;
                    }

                    function assertLoaded(?int $a, ?int $p): void {
                        if ($a === null && ($a = load($p)) === null) {
                            throw new Exception("null");
                        }
                    }

                    function assertBoth(?int $a, ?int $p): void {
                        if ($a === null) {
                            throw new Exception("null");
                        }

                        if (($a = load($p)) === null) {
                            throw new Exception("null");
                        }
                    }

                    function f(?int $a, ?int $b, ?int $p): void {
                        assertLoaded($a, $p);
                        /** @psalm-check-type-exact $a = int|null */
                        echo 1;

                        assertBoth($b, $p);
                        /** @psalm-check-type-exact $b = int */
                        echo 1;
                    }',
            ],
            'anAssertMethodAssigningAPropertyOfThisAssertsItForTheCaller' => [
                'code' => '<?php
                    final class Holder {
                        public ?int $value = null;

                        private function load(): ?int {
                            return rand(0, 1) ? null : 1;
                        }

                        public function assertLoaded(): void {
                            if (($this->value = $this->load()) === null) {
                                throw new Exception("null");
                            }
                        }
                    }

                    function f(Holder $h): int {
                        $h->assertLoaded();

                        return $h->value;
                    }',
            ],
            'aDoubleNegationIsTheConditionItself' => [
                'code' => '<?php
                    function f(?int $a): void {
                        if (!!$a) {
                            /** @psalm-check-type-exact $a = int */
                            echo 1;
                        } else {
                            /** @psalm-check-type-exact $a = 0|null */
                            echo 1;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'aLoopRetryingUntilTheVariableItAssignsIsNotNullIsLeftWithItNotNull' => [
                'code' => '<?php
                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a): int {
                        while ($a === null || ($a = load()) === null) {
                            echo 1;
                        }

                        return $a;
                    }

                    function g(?int $a): int {
                        do {
                            echo 1;
                        } while ($a === null || ($a = load()) === null);

                        return $a;
                    }',
            ],
            'aVariableAssignedOnTheRightOfAnOrIsDefinedWhereTheOrIsFalse' => [
                'code' => '<?php
                    function get(int $a): ?string {
                        return $a > 1 ? "x" : null;
                    }

                    /**
                     * @param mixed $v
                     * @psalm-assert falsy $v
                     */
                    function assertFalsy($v): void {}

                    function f(?int $a): string {
                        do {
                            echo 1;
                        } while (!$a || !($b = get($a)));

                        return $b;
                    }

                    function g(?int $a): string {
                        switch (true) {
                            case !$a || !($b = get($a)):
                                return "";
                            default:
                                return $b;
                        }
                    }

                    function h(?int $a): string {
                        assertFalsy(!$a || !($b = get($a)));

                        return $b;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison', 'MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'aStringThatIsNoTraitOrAnyOtherLoadedSymbolIsLeftByAConditionWithAnUnknownCall' => [
                'code' => '<?php
                    final class Registry {
                        public function has(string $name): bool {
                            return true;
                        }
                    }

                    function f(Registry $registry, string $name): void {
                        if ($registry->has($name)
                            || (!class_exists($name, false)
                                && !interface_exists($name, false)
                                && !enum_exists($name, false)
                                && !trait_exists($name, false))
                        ) {
                            return;
                        }

                        new ReflectionClass($name);
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'aStringThatIsNoClassAndNoTraitIsLeftByAnEarlyReturn' => [
                'code' => '<?php
                    function f(string $s): void {
                        if (!class_exists($s, false) && !trait_exists($s, false)) {
                            return;
                        }

                        /** @psalm-check-type-exact $s = class-string|trait-string */
                        echo $s;
                    }',
            ],
            'aFailedTraitExistsCheckWithoutAutoloadKeepsATraitString' => [
                'code' => '<?php
                    /** @param trait-string $t */
                    function a(string $t): void {
                        if (!trait_exists($t, false)) {
                            /** @psalm-check-type-exact $t = trait-string */
                            echo $t;
                        }
                    }',
            ],
            'aTraitExistsCheckWithoutAutoloadKeepsATraitStringAfterAnEarlyReturn' => [
                'code' => '<?php
                    /** @param trait-string $t */
                    function b(string $t): void {
                        if (trait_exists($t, false)) {
                            return;
                        }

                        /** @psalm-check-type-exact $t = trait-string */
                        echo $t;
                    }',
            ],
            'aCaseOfASwitchTrueAssigningAVariableHasNoClausesOfItsOldValue' => [
                'code' => '<?php
                    function f(string $s): int {
                        switch (true) {
                            case ($pos = strpos($s, "a")) !== false:
                                return $pos;
                            case ($pos = strpos($s, "b")) !== false:
                                return $pos;
                        }

                        return -1;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'anIfConditionAssigningAVariableHasNoClausesOfItsOldValueFromAnEnclosingIf' => [
                'code' => '<?php
                    final class A {}

                    function loadNext(): ?A {
                        return null;
                    }

                    function f(?A $a, ?A $b, bool $c): void {
                        if ($a || $b) {
                            if ($c && !($a = loadNext())) {
                                /** @psalm-check-type-exact $b = A|null */
                                echo get_debug_type($b);
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'aFunctionExistsCheckOfANewerNativeFunctionDoesNotNarrowThePhpVersion' => [
                'code' => '<?php
                    function f(): void {
                        if (function_exists("array_first")) {
                            if (PHP_VERSION_ID < 8_05_00) {
                                echo "polyfill";
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'aFunctionExistsCheckOfANewerNativeFunctionDoesNotContradictAVersionCheckOfTheSameCondition' => [
                'code' => '<?php
                    function f(): void {
                        if (PHP_VERSION_ID < 8_05_00 && function_exists("array_first")) {
                            echo "polyfill";
                        }

                        if (function_exists("array_first") && PHP_VERSION_ID < 8_05_00) {
                            echo "polyfill";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.4',
            ],
            'twoClausesThatAreNotReconcilableAreBothKeptOfOneCondition' => [
                'code' => '<?php
                    function f(?string $a, ?string $b, string $haystack): void {
                        if (($a === null || !str_contains($haystack, $a))
                            && ($b === null || !str_contains($haystack, $b))
                        ) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $b = null|string */
                            echo get_debug_type($b);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'simplifyingThreeClausesDoesNotMakeAUnitClauseOfAReducedOne' => [
                'code' => '<?php
                    function f(mixed $a, mixed $x, mixed $y): void {
                        if ((is_int($a) || is_int($x) || is_int($y))
                            && (!is_int($a) || is_int($x) || is_int($y))
                            && (is_int($a) || !is_int($x) || is_int($y))
                        ) {
                            /** @psalm-check-type-exact $y = mixed */
                            echo get_debug_type($y);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'aClauseIsNotTheResolventOfTwoClausesThatClashOnTwoVariables' => [
                'code' => '<?php
                    function f(mixed $a, mixed $b, mixed $x, mixed $y): void {
                        if ((is_int($a) || is_int($b) || is_int($x))
                            && (!is_int($a) || !is_int($b) || is_int($y))
                            && (is_int($x) || is_int($y))
                        ) {
                            echo "if";
                        } elseif (is_int($a)) {
                            /** @psalm-check-type-exact $b = mixed */
                            echo get_debug_type($b);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'twoClausesAreNotRedundantBecauseOfEachOther' => [
                'code' => '<?php
                    function f(?int $p, ?int $u, ?int $v): void {
                        if (($v !== null || $p !== null)
                            && ($v === null || $u !== null)
                            && ($p !== null || $u !== null)
                            && ($u === null || $v !== null)
                        ) {
                            echo "if";
                        } else {
                            if ($u === null) {
                                /** @psalm-check-type-exact $v = int|null */
                                echo get_debug_type($v);
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'aContradictionOfAConditionDoesNotNarrowTheOtherConjunctsInTheElse' => [
                'code' => '<?php
                    function f(?int $a, bool $b): void {
                        /** @psalm-suppress TypeDoesNotContainType */
                        if ($a === null && $a !== null && $b) {
                            echo "unreachable";
                        } else {
                            /** @psalm-check-type-exact $b = bool */
                            echo var_export($b, true);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'aContradictionAfterTheOtherConjunctsDoesNotNarrowThemInTheElse' => [
                'code' => '<?php
                    function f(?int $a, bool $b): void {
                        /** @psalm-suppress TypeDoesNotContainNull */
                        if ($b && $a !== null && $a === null) {
                            echo "unreachable";
                        } elseif ($a === null) {
                            /** @psalm-check-type-exact $b = bool */
                            echo var_export($b, true);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'aClauseThatIsNotReconcilableIsNotAParentOfAResolvent' => [
                'code' => '<?php
                    function f(?int $a, ?int $x, ?int $y, string $s, string $t): void {
                        if (($a !== null || $x !== null || !($s === $t))
                            && ($a === null || $y !== null)
                            && ($x !== null || $y !== null)
                        ) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $a = int|null */
                            echo get_debug_type($a);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'twoClausesAreNotRedundantBecauseOfEachOtherWhenTheLaterOneIsReduced' => [
                'code' => '<?php
                    function f(?int $p, ?int $u, ?int $v): void {
                        if (($u === null || $v !== null)
                            && ($v === null || $u !== null)
                            && ($p !== null || $u !== null)
                            && ($v !== null || $p !== null)
                        ) {
                            echo "if";
                        } elseif ($u === null) {
                            /** @psalm-check-type-exact $v = int|null */
                            echo get_debug_type($v);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'aClauseThatIsNotReconcilableIsNotTheLaterParentOfAResolvent' => [
                'code' => '<?php
                    function f(?int $a, ?int $x, ?int $y, string $s, string $t): void {
                        if (($a === null || $y !== null)
                            && ($a !== null || $x !== null || !($s === $t))
                            && ($x !== null || $y !== null)
                        ) {
                            echo "if";
                        } else {
                            /** @psalm-check-type-exact $a = int|null */
                            echo get_debug_type($a);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'coalesceAssignmentInConditionNarrowsTheVariable' => [
                'code' => <<<'PHP'
                    <?php
                    function make(): ?object { return null; }

                    function negated(?object $s): object {
                        if (!($s ??= make())) {
                            exit;
                        }

                        return $s;
                    }

                    function branches(?object $s): void {
                        if ($s ??= make()) {
                            /** @psalm-check-type-exact $s = object */;
                        } else {
                            /** @psalm-check-type-exact $s = null */;
                        }
                    }

                    /** @param array{k?: object|null} $arr */
                    function arrayItem(array $arr): object {
                        if (!($arr['k'] ??= make())) {
                            exit;
                        }

                        return $arr['k'];
                    }

                    final class Holder {
                        private ?object $item = null;
                        public ?self $child = null;

                        public function item(): object {
                            if (!($this->item ??= make())) {
                                throw new Exception();
                            }

                            return $this->item;
                        }

                        public function untracked(): void {
                            if ($this->self()->child ??= null) {
                                echo 1;
                            }
                        }

                        public function self(): self {
                            return $this;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'coalesceAssignmentOnTheRightOfAndKeepsTheAssignmentAfterTheIf' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {}

                    function make(): ?A {
                        return rand(0, 1) ? new A() : null;
                    }

                    /** @param list<string> $names */
                    function export(array $names): void {
                        $class = null;

                        foreach ($names as $name) {
                            if ($name === 'self' && $class ??= make()) {
                                echo get_class($class);
                            }
                        }

                        /** @psalm-check-type-exact $class = A|null */;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'coalesceAssignmentAssignedToAVariableOnTheLeftOfOr' => [
                'code' => <<<'PHP'
                    <?php
                    interface I {}
                    final class Lazy implements I {}
                    final class Real implements I {}

                    function make(): ?I {
                        return rand(0, 1) ? new Lazy() : null;
                    }

                    final class Builder {
                        private ?I $instantiator = null;

                        public function isReal(): bool {
                            return !($proxy = $this->instantiator ??= make()) || $proxy instanceof Real;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'instanceofStoredInVariableNarrowsInIfBody' => [
                'code' => '<?php
                    interface Foo {}
                    interface Bar {
                        public function bar(): void;
                    }

                    function f(Foo $obj): void {
                        $isBar = $obj instanceof Bar;

                        if ($isBar) {
                            $obj->bar();
                        }
                    }',
            ],
            'instanceofStoredInVariableNarrowsAfterEarlyReturn' => [
                'code' => '<?php
                    interface Foo {}
                    interface Bar {
                        public function bar(): void;
                    }

                    function f(Foo $obj): void {
                        $isBar = $obj instanceof Bar;

                        if (!$isBar) {
                            return;
                        }

                        $obj->bar();
                    }',
            ],
            'nullsafeCallFalsyResultDoesNotNarrowReceiver' => [
                'code' => <<<'PHP'
                    <?php
                    final class Box
                    {
                        public function get(): ?string { return null; }
                        public function name(): string { return ''; }
                    }

                    function makeBox(): ?Box { return null; }

                    function plain(?Box $box): void {
                        if ($box?->get()) {
                            /** @psalm-check-type-exact $box = Box */;
                        } else {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        /** @psalm-check-type-exact $box = Box|null */;
                    }

                    function negated(?Box $box): void {
                        if (!$box?->get()) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        } else {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                    }

                    function assigned(?Box $box): void {
                        if ($value = $box?->get()) {
                            echo $value;
                            /** @psalm-check-type-exact $box = Box */;
                        } else {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        /** @psalm-check-type-exact $box = Box|null */;
                    }

                    function elvis(?Box $box): string {
                        return $box?->get() ?: $box?->name() ?? "";
                    }

                    function conjunction(?Box $box): void {
                        if ($box?->get() && $box->name()) {
                            echo 1;
                        }
                    }

                    function untracked(): void {
                        if (makeBox()?->get()) {
                            echo 1;
                        } else {
                            echo 2;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.1',
            ],
            'nullsafeCallComparedToBoolLiteral' => [
                'code' => <<<'PHP'
                    <?php
                    final class Box
                    {
                        public function check(): bool { return (bool) rand(0, 1); }
                        public function other(): bool { return (bool) rand(0, 1); }
                    }
                    function identicalFalse(?Box $box): void {
                        if ($box?->check() === false) {
                            /** @psalm-check-type-exact $box = Box */;
                        } else {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                    }
                    function identicalFalseLiteralOnLeft(?Box $box): void {
                        if (false === $box?->check()) {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                    }
                    function identicalTrue(?Box $box): void {
                        if ($box?->check() === true) {
                            /** @psalm-check-type-exact $box = Box */;
                        } else {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                    }
                    function notIdenticalFalse(?Box $box): void {
                        if ($box?->check() !== false) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        } else {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                    }
                    function notIdenticalTrue(?Box $box): void {
                        if ($box?->check() !== true) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        } else {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                    }
                    PHP,
                'assertions' => [],
                'php_version' => '8.1',
            ],
            'nullsafeCallWhoseArgumentsMayReassignTheReceiver' => [
                'code' => <<<'PHP'
                    <?php
                    final class Box
                    {
                        public function check(?Box &$arg): bool { $arg = null; return false; }
                        public function take(mixed $_v): bool { return (bool) rand(0, 1); }
                        public function run(callable $f): bool { $f(); return (bool) rand(0, 1); }
                        public ?Box $inner = null;
                    }
                    final class Holder
                    {
                        public ?Box $box = null;
                    }
                    function byRef(?Box $box): void {
                        if ($box?->check($box) === false) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->check($box) === true) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->check($box) !== true) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        } else {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->check($box)) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($r = $box?->check($box)) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                    }
                    function makeBox(): ?Box { return rand(0, 1) ? new Box() : null; }
                    function assignedInArgument(?Box $box): void {
                        if ($box?->take($box = makeBox()) === true) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->take([$box = makeBox()])) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                    }
                    function capturedByReference(?Box $box): void {
                        if ($box?->run(function () use (&$box): void { $box = null; })) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                    }
                    function holderPassedByReference(Holder $h): void {
                        if ($h->box?->check($h->box) === false) {
                            /** @psalm-check-type-exact $h->box = Box|null */;
                        }
                    }
                    function otherArgument(?Box $box, ?Box $other): void {
                        if ($box?->check($other) === false) {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                        if ($box?->check($other)) {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                    }
                    function nullify(?Box &$b): bool { $b = null; return false; }
                    function passedByReferenceToNestedCall(?Box $box, ?Box $o): void {
                        if ($box?->take(nullify($box)) === false) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->take($o?->take(nullify($box))) === false) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->take(nullify($box))) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                    }
                    function destructuredInArgument(?Box $box): void {
                        if ($box?->take([$box] = [makeBox()]) === false) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->take(list(, $box) = [1, makeBox()]) === false) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                        if ($box?->take(['k' => [$box]] = ['k' => [makeBox()]]) === false) {
                            /** @psalm-check-type-exact $box = Box|null */;
                        }
                    }
                    function closureLocalVariable(?Box $box): void {
                        if ($box?->run(function (): void { $box = null; echo (string) $box; })) {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                        if ($box?->run(fn() => $box = null) === false) {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                        if ($box?->take(static function (?Box $box): void { $box = null; echo (string) $box; }) === false) {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                    }
                    function propertyOfReceiverPassed(?Box $box): void {
                        if ($box?->check($box->inner) === false) {
                            /** @psalm-check-type-exact $box = Box */;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['UnusedVariable'],
                'php_version' => '8.1',
            ],
            'variableReassignedOnTheRightOfAndKeepsItsNewNarrowing' => [
                'code' => <<<'PHP'
                    <?php
                    final class A
                    {
                        public function foo(): int { return rand(0, 1); }
                        public function ofoo(): ?int { return rand(0, 1) ? 1 : null; }
                    }
                    function call(A $a): void {
                        $r = $a->ofoo();
                        if ($r !== null && ($r = $a->ofoo()) !== null) {
                            /** @psalm-check-type-exact $r = int */;
                        }
                    }
                    function truthyCall(A $a, ?int $s): void {
                        $r = $s;
                        if ($r && ($r = $a->ofoo())) {
                            /** @psalm-check-type-exact $r = int */;
                        }
                    }
                    function nullsafeCall(?A $a, ?int $s): void {
                        $r = $s;
                        if ($r && ($r = $a?->foo())) {
                            /** @psalm-check-type-exact $r = int */;
                        }
                    }
                    function nestedAnd(?A $a, ?int $s): void {
                        $r = $s;
                        if ($r && $a && ($r = $a->ofoo())) {
                            /** @psalm-check-type-exact $r = int */;
                        }
                    }
                    function otherVariableStaysNarrowed(A $a, ?int $s): void {
                        if ($s && ($r = $a->ofoo())) {
                            /** @psalm-check-type-exact $s = int */;
                            /** @psalm-check-type-exact $r = int */;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.1',
            ],
            'argumentCoercedOnTheRightOfAndIsNotReassigned' => [
                'code' => <<<'PHP'
                    <?php
                    declare(strict_types=1);

                    /** @param list<string> $argv */
                    function f(array $argv): void {
                        for ($i = 0, $iMax = count($argv); $i < $iMax; ++$i) {
                            $input_path = $argv[$i];
                            if ($input_path[0] === '-' && strlen($input_path) === 2) {
                                continue;
                            }
                            if (strlen($input_path) > 2 && $input_path[0] === '-' && $input_path[2] === '=') {
                                continue;
                            }
                        }
                    }
                    PHP,
            ],
            'boolVarIdenticalToFalseNarrowsItsConjuncts' => [
                'code' => <<<'PHP'
                    <?php
                    final class U {
                        public int $id = 1;
                    }

                    function f(?U $v, ?U $e): int {
                        $c = $v !== null && $e === $v;
                        if ($c === false) {
                            return 0;
                        }
                        return $v->id;
                    }
                    PHP,
            ],
            'boolVarIdenticalToTrueNarrowsItsConjuncts' => [
                'code' => <<<'PHP'
                    <?php
                    final class U {
                        public int $id = 1;
                    }

                    function f(?U $v, ?U $e): int {
                        $c = $v !== null && $e === $v;
                        if ($c === true) {
                            return $v->id;
                        }
                        return 0;
                    }
                    PHP,
            ],
            'boolVarNotIdenticalToFalseNarrowsItsConjuncts' => [
                'code' => <<<'PHP'
                    <?php
                    final class U {
                        public int $id = 1;
                    }

                    function f(?U $v, ?U $e): int {
                        $c = $v !== null && $e === $v;
                        if ($c !== false) {
                            return $v->id;
                        }
                        return 0;
                    }
                    PHP,
            ],
            'boolVarNotIdenticalToTrueNarrowsItsConjuncts' => [
                'code' => <<<'PHP'
                    <?php
                    final class U {
                        public int $id = 1;
                    }

                    function f(?U $v, ?U $e): int {
                        $c = $v !== null && $e === $v;
                        if ($c !== true) {
                            return 0;
                        }
                        return $v->id;
                    }
                    PHP,
            ],
            'identityWithFalseOfNonBoolVarStillNarrows' => [
                'code' => <<<'PHP'
                    <?php
                    function f(int|false $x): int {
                        if ($x === false) {
                            return 0;
                        }
                        return $x;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'booleanVariableNarrowingSurvivesOrWithItAsLeftOperand' => [
                'code' => <<<'PHP'
                    <?php
                    final class Bag {
                        public function get(): int {
                            return 1;
                        }
                    }

                    function maybeBag(): ?Bag {
                        return null;
                    }

                    function assigned(?Bag $b, bool $c): int {
                        $has = $b !== null;
                        $z = $has || $c;
                        if ($has) {
                            echo $b->get();
                        }

                        return $z ? 1 : 0;
                    }

                    function inCondition(?Bag $b, bool $c): void {
                        $has = $b !== null;
                        if ($has || $c) {
                            echo 1;
                        }

                        if ($has) {
                            echo $b->get();
                        }
                    }

                    function assignmentInOperand(): void {
                        if (!($x = maybeBag()) || $x->get()) {
                            echo 1;
                        }

                        if (!($x = maybeBag()) || $x->get()) {
                            echo 2;
                        }
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'inArrayWithAValueAnEarlierConditionExcludedIsNotParadoxical' => [
                'code' => '<?php
                    const A = "a";
                    const ALL = [A, "b", "c"];

                    function test(string $value): int {
                        if ($value === A) {
                            return 1;
                        }

                        if (in_array($value, ALL, true)) {
                            return 2;
                        }

                        return 3;
                    }',
                'assertions' => [],
            ],
            'orOfAValueAnEarlierConditionExcludedAndAnotherIsNotParadoxical' => [
                'code' => '<?php
                    function test(string $value): int {
                        if ($value === "a") {
                            return 1;
                        }

                        if ($value === "a" || $value === "b") {
                            return 2;
                        }

                        return 3;
                    }',
                'assertions' => [],
            ],
            'orOfAnExcludedValueAndAPossibleOneOfTheSameVariableIsNotReported' => [
                'code' => '<?php
                    final class Model {
                        public const A = "MODEL_A";
                        public const B = "MODEL_B";
                    }

                    function pick(string $m, bool $flag): int {
                        if ($m !== Model::A) {
                            if ($flag || $m === Model::B) {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'assertions' => [],
            ],
            'orOperandOfAVariableAssignedInTheOrIsNotParadoxical' => [
                'code' => '<?php
                    function h(): string {
                        return rand(0, 1) ? "a" : "b";
                    }

                    function pick(string $m, bool $flag): int {
                        if ($m !== "a") {
                            if ($flag || ($m = h()) === "a") {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'assertions' => [],
            ],
            'orOperandAfterAnOperandAssigningItsVariableIsNotParadoxical' => [
                'code' => '<?php
                    function h(): string {
                        return rand(0, 1) ? "a" : "b";
                    }

                    function pick(string $m, bool $flag): int {
                        if ($m !== "a") {
                            if ($flag && ($m = h()) !== "" || $m === "a") {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'assertions' => [],
            ],
            'orOperandOfAnIncrementedVariableIsNotParadoxical' => [
                'code' => '<?php
                    function pick(int $i, bool $flag): int {
                        if ($i !== 0) {
                            if ($flag || ++$i === 0) {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'assertions' => [],
            ],
            'ternaryOrOperandOfAVariableAssignedInTheOrIsNotParadoxical' => [
                'code' => '<?php
                    function h(): string {
                        return rand(0, 1) ? "a" : "b";
                    }

                    function pick(string $m, bool $flag): string {
                        if ($m !== "a") {
                            return $flag || ($m = h()) === "a" ? $m : "";
                        }

                        return "";
                    }',
                'assertions' => [],
            ],
            'twoVarLogicSimple' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a !== null || $b !== null) {
                            if ($a !== null) {
                                return $a;
                            } else {
                                return $b;
                            }
                        }

                        return "foo";
                    }',
            ],
            'threeVarLogic' => [
                'code' => '<?php
                    function takesString(string $s): void {}

                    function foo(?string $a, ?string $b, ?string $c): void {
                        if ($a !== null || $b !== null || $c !== null) {
                            if ($a !== null) {
                                $d = $a;
                            } elseif ($b !== null) {
                                $d = $b;
                            } else {
                                $d = $c;
                            }

                            takesString($d);
                        }
                    }',
            ],
            'twoVarLogicNotNestedSimple' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!$a && !$b) return "bad";
                        if (!$a) return $b;
                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'twoVarLogicNotNestedWithAllPathsReturning' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!$a && !$b) {
                            return "bad";
                        } else {
                            if (!$a) {
                                return $b;
                            } else {
                                return $a;
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'twoVarLogicNotNestedWithAssignmentBeforeReturn' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!$a && !$b) {
                            $a = 5;
                            return "bad";
                        }

                        if (!$a) {
                            $a = 7;
                            return $b;
                        }

                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'invertedTwoVarLogicNotNested' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a || $b) {
                            // do nothing
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'invertedTwoVarLogicNotNestedWithAssignmentBeforeReturn' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a || $b) {
                            // do nothing
                        } else {
                            $a = 5;
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'twoVarLogicNotNestedWithElseifAndNoNegations' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'threeVarLogicNotNestedWithNoRedefinitionsWithClasses' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b, ?stdClass $c): stdClass {
                        if ($a) {
                            // do nothing
                        } elseif ($b) {
                            // do nothing here
                        } elseif ($c) {
                            // do nothing here
                        } else {
                            return new stdClass;
                        }

                        if (!$a && !$b) {
                            return $c;
                        }
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'threeVarLogicNotNestedWithNoRedefinitionsWithStrings' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b) {
                            // do nothing here
                        } elseif ($c) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a && !$b) return $c;
                        if (!$a) return $b;
                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'threeVarLogicNotNestedAndOrWithNoRedefinitions' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b || $c) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a && !$b) return $c;
                        if (!$a) return $b;
                        return $a;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'twoVarLogicNotNestedWithElseifCorrectlyNegatedInElseIf' => [
                'code' => '<?php
                    function foo(string $a, string $b): string {
                        if ($a) {
                            // do nothing here
                        } elseif ($b) {
                            $a = null;
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'nestedReassignment' => [
                'code' => '<?php
                    function foo(?string $a): void {
                        if ($a === null) {
                            $a = "blah-blah";
                        } else {
                            $a = rand(0, 1) ? "blah" : null;

                            if ($a === null) {

                            }
                        }
                    }',
            ],
            'twoVarLogicNotNestedWithElseifCorrectlyReinforcedInIf' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(?A $a, ?A $b): A {
                        if ($a) {
                            $a = new B;
                        } elseif ($b) {
                            // do nothing
                        } else {
                            return new A;
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'differentValueChecks' => [
                'code' => '<?php
                    function foo(string $a): void {
                        if ($a === "foo") {
                            // do something
                        } elseif ($a === "bar") {
                            // can never get here
                        }
                    }',
            ],
            'byRefAssignment' => [
                'code' => '<?php
                    function foo(): void {
                        preg_match("/hello/", "hello molly", $matches);

                        if (!$matches) {
                            return;
                        }

                        preg_match("/hello/", "hello dolly", $matches);

                        if (!$matches) {

                        }
                    }',
            ],
            'orConditionalAfterAndConditional' => [
                'code' => '<?php
                    function foo(string $a, string $b): void {
                        if ($a && $b) {
                            echo "a";
                        } elseif ($a || $b) {
                            echo "b";
                        }
                    }',
            ],
            'issetOnOneStringAfterAnother' => [
                'code' => '<?php
                    /** @param string[] $arr */
                    function foo(array $arr): void {
                        $a = "a";

                        if (!isset($arr[$a])) {
                            return;
                        }

                        foreach ([0, 1, 2, 3] as $i) {
                            if (!isset($arr[$a . $i])) {
                                echo "a";
                            }

                            $a = "hello";
                        }
                    }',
            ],
            'issetArrayCreation' => [
                'code' => '<?php
                    $arr = [];

                    foreach ([0, 1, 2, 3] as $i) {
                        $a = (int) (rand(0, 1) ? 5 : "010");

                        if (!isset($arr[$a])) {
                            $arr[$a] = 5;
                        } else {
                            $arr[$a] += 4;
                        }
                    }',
            ],
            'moreConvolutedArrayCreation' => [
                'code' => '<?php
                    function fetchRow() : array {
                        return ["c" => "UK"];
                    }

                    $arr = [];

                    foreach ([1, 2, 3] as $i) {
                        $row = fetchRow();

                        if (!isset($arr[$row["c"]])) {
                            $arr[$row["c"]] = 0;
                        }

                        $arr[$row["c"]] = 1;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayOffset'],
            ],
            'moreConvolutedNestedArrayCreation' => [
                'code' => '<?php
                    function fetchRow() : array {
                        return ["c" => "UK"];
                    }

                    $arr = [];

                    foreach ([1, 2, 3] as $i) {
                        $row = fetchRow();

                        if (!isset($arr[$row["c"]]["foo"])) {
                            $arr[$row["c"]]["foo"] = 0;
                        }

                        $arr[$row["c"]]["foo"] = 1;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayOffset'],
            ],
            'noParadoxInLoop' => [
                'code' => '<?php
                    function paradox2(): void {
                        $condition = rand() % 2 > 0;

                        if (!$condition) {
                            foreach ([1, 2] as $value) {
                                if ($condition) { }
                                $condition = true;
                            }
                        }
                    }',
            ],
            'noParadoxInListAssignment' => [
                'code' => '<?php
                    function foo(string $a): void {
                        if (!$a) {
                            list($a) = explode(":", "a:b");

                            if ($a) { }
                        }
                    }',
            ],
            'noParadoxAfterAssignment' => [
                'code' => '<?php
                    function get_bool(): bool {
                        return rand() % 2 > 0;
                    }

                    function leftover(): bool {
                        $res = get_bool();
                        if ($res === false) {
                            return true;
                        }
                        $res = get_bool();
                        if ($res === false) {
                            return false;
                        }
                        return true;
                    }',
            ],
            'noParadoxAfterArrayAppending' => [
                'code' => '<?php
                    /** @return array|false */
                    function array_append(array $errors) {
                        if ($errors) {
                            return $errors;
                        }
                        if (rand() % 2 > 0) {
                            $errors[] = "unlucky";
                        }
                        if ($errors) {
                            return false;
                        }
                        return $errors;
                    }',
            ],
            'noParadoxInCatch' => [
                'code' => '<?php
                    function maybe_returns_array(): ?array {
                        if (rand() % 2 > 0) {
                            return ["key" => "value"];
                        }
                        if (rand() % 3 > 0) {
                            throw new Exception("An exception occurred");
                        }
                        return null;
                    }

                    function try_catch_check(): array {
                        $arr = null;
                        try {
                            $arr = maybe_returns_array();
                            if (!$arr) { return [];  }
                        } catch (Exception $e) {
                            if (!$arr) { return []; }
                        }
                        return $arr;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'lotsaTruthyStatements' => [
                'code' => '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      if (($obj->a !== null) == true) {
                        return $obj->a; // definitely not null
                      } elseif (!is_null($obj->b) == true) {
                        return $obj->b;
                      } else {
                        throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
                    }',
            ],
            'lotsaFalsyStatements' => [
                'code' => '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      if (($obj->a === null) == false) {
                        return $obj->a; // definitely not null
                      } elseif (is_null($obj->b) == false) {
                        return $obj->b;
                      } else {
                        throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
                    }',
            ],
            'ifGetClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                      public function foo(): void {}
                    }

                    function takesA(A $a): void {
                      if (get_class($a) === B::class) {
                        $a->foo();
                      }
                    }',
            ],
            'ifNotEqualsGetClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                      public function foo(): void {}
                    }

                    function takesA(A $a): void {
                      if (get_class($a) !== B::class) {
                        // do nothing
                      } else {
                        $a->foo();
                      }
                    }',
            ],
            'nestedCheckWithSingleVarPerLevel' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b): void {
                        if ($a) {
                            if ($b) {}
                        }
                    }',
            ],
            'nestedCheckWithTwoVarsPerLevel' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b, ?stdClass $c, ?stdClass $d): void {
                        if ($a && $b) {
                            if ($c && $d) {}
                        }
                    }',
            ],
            'nestedCheckWithReturn' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b): void {
                        if ($a === null) {
                            return;
                        }

                        if ($b) {
                            echo "hello";
                        }
                    }',
            ],
            'propertyFetchAfterNotNullCheck' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    $a = new A;

                    if ($a->foo === null) {
                        $a->foo = "hello";
                        exit;
                    }

                    if ($a->foo === "somestring") {}',
            ],
            'noParadoxForGetopt' => [
                'code' => '<?php
                    $options = getopt("t:");

                    try {
                        if (!isset($options["t"])) {
                            throw new Exception("bad");
                        }
                    } catch (Exception $e) {}',
            ],
            'instanceofInOr' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a): void {
                        if ($a instanceof B
                            || ($a instanceof C && rand(0, 1))
                        ) {
                            takesA($a);
                        }
                    }',
            ],
            'instanceofInOrNegated' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?A $b, ?A $c): void {
                        if (!$a || ($b && $c)) {
                            return;
                        }

                        takesA($a);
                    }',
            ],
            'instanceofInBothOrs' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a): void {
                        if (($a instanceof B && rand(0, 1))
                            || ($a instanceof C && rand(0, 1))
                        ) {
                            takesA($a);
                        }
                    }',
            ],
            'instanceofInBothOrsWithSecondVar' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?A $b): void {
                        if (($a instanceof B && $b instanceof B)
                            || ($a instanceof C && $b instanceof C)
                        ) {
                            takesA($a);
                            takesA($b);
                        }
                    }',
            ],
            'explosionOfCNF' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;

                        /** @var ?string */
                        public $bar;
                    }

                    $a1 = rand(0, 1) ? new A() : null;
                    $a4 = rand(0, 1) ? new A() : null;
                    $a5 = rand(0, 1) ? new A() : null;
                    $a7 = rand(0, 1) ? new A() : null;
                    $a8 = rand(0, 1) ? new A() : null;

                    if ($a1 || (($a4 && $a5) || ($a7 && $a8))) {}',
            ],
            'instanceofInCNFOr' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a): void {
                        $c = rand(0, 1);
                        if (($a instanceof B || $a instanceof C)
                            && ($a instanceof B || $c)
                        ) {
                            takesA($a);
                        }
                    }',
            ],
            'reconcileNestedOrsInElse' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?B $b): void {
                        if ($a === null || $b === null || rand(0, 1)) {
                            // do nothing
                        } else {
                            takesA($a);
                        }
                    }',
            ],
            'getClassComparison' => [
                'code' => '<?php
                    class Foo {
                        public function bar() : void {}
                    }
                    class Bar extends Foo{
                        public function bar() : void {}
                    }

                    class Baz {
                        public function test(Foo $foo) : void {
                            if (get_class($foo) !== Foo::class) {
                                // do nothing
                            } else {
                                $foo->bar();
                            }
                        }
                    }',
            ],
            'callWithNonNullInTernary' => [
                'code' => '<?php
                    function sayHello(?int $a, ?int $b): void {
                        if ($a === null && $b === null) {
                            throw new \LogicException();
                        }

                        takesInt($a !== null ? $a : $b);
                    }

                    function takesInt(int $c) : void {}',
            ],
            'callWithNonNullInIf' => [
                'code' => '<?php
                    function sayHello(?int $a, ?int $b): void {
                        if ($a === null && $b === null) {
                            throw new \LogicException();
                        }

                        if ($a !== null) {
                            takesInt($a);
                        } else {
                            takesInt($b);
                        }
                    }

                    function takesInt(int $c) : void {}',
            ],
            'callWithNonNullInIfWithCallInElseif' => [
                'code' => '<?php
                    function sayHello(?int $a, ?int $b): void {
                        if ($a === null && $b === null) {
                            throw new \LogicException();
                        }

                        if ($a !== null) {
                            takesInt($a);
                        } elseif (rand(0, 1)) {
                            takesInt($b);
                        }
                    }

                    function takesInt(int $c) : void {}',
            ],
            'typeSimplification' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(A $a, A $b) : ?B {
                        if (($a instanceof B || !$b instanceof B) && $a instanceof B && $b instanceof B) {
                            return $a;
                        }

                        return null;
                    }',
            ],
            'instanceofNoRedundant' => [
                'code' => '<?php
                    function logic(Foo $a, Foo $b) : void {
                        if ((!$a instanceof Bat || !$b instanceof Bat)
                            && (!$a instanceof Bat || !$b instanceof Bar)
                            && (!$a instanceof Bar || !$b instanceof Bat)
                            && (!$a instanceof Bar || !$b instanceof Bar)
                        ) {

                        } else {
                            if ($b instanceof Bat) {}
                        }
                    }

                    class Foo {}
                    class Bar extends Foo {}
                    class Bat extends Foo {}',
            ],
            'explicitValuesInOrIf' => [
                'code' => '<?php
                    $s = rand(0, 1) ? "a" : "b";

                    if (($s === "a" && rand(0, 1)) || ($s === "b" && rand(0, 1))) {}',
            ],
            'explicitValuesInOrTernary' => [
                'code' => '<?php
                    $s = rand(0, 1) ? "a" : "b";

                    $a = (($s === "a" && rand(0, 1)) || ($s === "b" && rand(0, 1))) ? 1 : 0;',
            ],
            'boolComparison' => [
                'code' => '<?php
                    $a = (bool) rand(0, 1);

                    if (rand(0, 1)) {
                        $a = null;
                    }

                    if ($a !== (bool) rand(0, 1)) {
                        echo $a === false ? "a" : "b";
                    }',
            ],
            'stringConcatenationTrackedValid' => [
                'code' => '<?php
                    $x = "a";
                    $x = "_" . $x;
                    $array = [$x => 2];
                    echo $array["_a"];',
            ],
            'noMemoryIssueWithLongConditional' => [
                'code' => '<?php

                    function foo(int $c) : string {
                        if (!($c >= 0x5be && $c <= 0x10b7f)) {
                            return "LTR";
                        }

                        if ($c <= 0x85e) {
                            if ($c === 0x5be ||
                                $c === 0x5c0 ||
                                $c === 0x5c3 ||
                                $c === 0x5c6 ||
                                ($c >= 0x5d0 && $c <= 0x5ea) ||
                                ($c >= 0x5f0 && $c <= 0x5f4) ||
                                $c === 0x608 ||
                                ($c >= 0x712 && $c <= 0x72f) ||
                                ($c >= 0x74d && $c <= 0x7a5) ||
                                $c === 0x7b1 ||
                                ($c >= 0x7c0 && $c <= 0x7ea) ||
                                ($c >= 0x7f4 && $c <= 0x7f5) ||
                                $c === 0x7fa ||
                                ($c >= 0x800 && $c <= 0x815) ||
                                $c === 0x81a ||
                                $c === 0x824 ||
                                $c === 0x828 ||
                                ($c >= 0x830 && $c <= 0x83e) ||
                                ($c >= 0x840 && $c <= 0x858) ||
                                $c === 0x85e
                            ) {
                                return "RTL";
                            }
                        } elseif ($c === 0x200f) {
                            return "RTL";
                        } elseif ($c >= 0xfb1d) {
                            if ($c === 0xfb1d ||
                                ($c >= 0xfb1f && $c <= 0xfb28) ||
                                ($c >= 0xfb2a && $c <= 0xfb36) ||
                                ($c >= 0xfb38 && $c <= 0xfb3c) ||
                                $c === 0xfb3e ||
                                ($c >= 0x10a10 && $c <= 0x10a13) ||
                                ($c >= 0x10a15 && $c <= 0x10a17) ||
                                ($c >= 0x10a19 && $c <= 0x10a33) ||
                                ($c >= 0x10a40 && $c <= 0x10a47) ||
                                ($c >= 0x10a50 && $c <= 0x10a58) ||
                                ($c >= 0x10a60 && $c <= 0x10a7f) ||
                                ($c >= 0x10b00 && $c <= 0x10b35) ||
                                ($c >= 0x10b40 && $c <= 0x10b55) ||
                                ($c >= 0x10b58 && $c <= 0x10b72) ||
                                $c >= 0x10b78
                            ) {
                                return "RTL";
                            }
                        }

                        return "LTR";
                    }',
                'assertions' => [],
            ],
            'grandParentInstanceofConfusion' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends B {}

                    function bad(A $x) : void {
                        if (($x instanceof C && rand(0, 1)) || rand(0, 1)) {
                            return;
                        }

                        if ($x instanceof B) {
                            if ($x instanceof C) {}
                        }
                    }',
            ],
            'invertEquation' => [
                'code' => '<?php
                    /**
                     * @param mixed $width
                     * @param mixed $height
                     *
                     * @throws RuntimeException
                     */
                    function Foo($width, $height) : void {
                        if (!(is_int($width) || is_float($width)) || !(is_int($height) || is_float($height))) {
                            throw new RuntimeException("bad");
                        }

                        echo sprintf("padding-top:%s%%;", 100 * ($height/$width));
                    }',
            ],
            'invertLogic' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(?A $a) : A {
                        if (!$a || !($a instanceof B && rand(0, 1))) {
                            throw new Exception();
                        }

                        return $a;
                    }',
            ],
            'allowAssertionInElseif' => [
                'code' => '<?php
                class X {
                    public bool $a = false;
                    public bool $b = false;
                    public bool $c = false;
                }

                function foo(X $x) : void {
                    $a = false;
                    if ($x->b && $x->a) {
                    } elseif ($x->c) {
                        $a = true;
                    }

                    if ($x->c) {}
                    if ($a) {}
                }',
            ],
            'twoVarChangeInElseOnly' => [
                'code' => '<?php
                    class A {
                        public function takesA(A $a) : void {}

                        public function foo() : void {}
                    }

                    function formatRange(?A $from, ?A $to): void {
                        if (!$to && !$from) {
                            $to = new A();
                            $from = new A();
                        } elseif (!$from) {
                            $from = new A();
                            $from->takesA($to);
                        } else {
                            if (!$to) {
                                $to = new A();
                                $to->takesA($from);
                            }
                        }

                        $from->foo();
                        $to->foo();
                    }',
            ],
            'twoVarChangeInElseif' => [
                'code' => '<?php
                    class A {
                        public function takesA(A $a) : void {}

                        public function foo() : void {}
                    }

                    function formatRange(?A $from, ?A $to): void {
                        if (!$to && !$from) {
                            $to = new A();
                            $from = new A();
                        } elseif (!$from) {
                            $from = new A();
                            $from->takesA($to);
                        } elseif (!$to) {
                            $to = new A();
                            $to->takesA($from);
                        }

                        $from->foo();
                        $to->foo();
                    }',
            ],
            'testSimplishThing' => [
                'code' => '<?php
                    function foo(
                        bool $a,
                        bool $b,
                        bool $c,
                        bool $d,
                        bool $e,
                        bool $f,
                        bool $g,
                        bool $h,
                        bool $i,
                        bool $j
                    ): bool {
                        return ($a && $b)
                            || ($c && $d)
                            || ($e && $f)
                            || ($g && $h)
                            || ($i && $j);
                    }',
            ],
            'fineCheck' => [
                'code' => '<?php
                    function foo(bool $b, bool $c) : void {
                        if ((!$b || rand(0, 1)) && (!$c || rand(0, 1))) {}
                    }',
            ],
            'noParadoxInTernary' => [
                'code' => '<?php
                    function foo(?bool $b) : string {
                        return $b ? "a" : ($b === null ? "foo" : "b");
                    }',
            ],
            'cancelOutSameStatement' => [
                'code' => '<?php
                    function edit(?string $a, ?string $b): string {
                        if ((!$a && !$b) || ($a && !$b)) {
                            return "";
                        }

                        return $b;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'cancelOutDifferentStatement' => [
                'code' => '<?php
                    function edit(?string $a, ?string $b): string {
                        if (!$a && !$b) {
                            return "";
                        }

                        if ($a && !$b) {
                            return "";
                        }

                        return $b;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'moreChecks' => [
                'code' => '<?php
                    class B {}
                    class C {}

                    function foo(?B $b, ?C $c): B|C {
                        if (!$b && !$c) {
                            throw new Exception("bad");
                        }

                        if ($b && $c) {
                            return rand(0, 1) ? $b : $c;
                        }

                        if ($b) {
                            return $b;
                        }

                        return $c;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'dependentType' => [
                'code' => '<?php
                    class A {
                        public function isValid() : bool {
                            return (bool) rand(0, 1);
                        }

                        public function foo() : void {}
                    }

                    function takesA(?A $a) : void {
                        $is_valid_a = $a && $a->isValid();

                        if ($is_valid_a) {
                            $a->foo();
                        }
                    }',
            ],
            'assignSameName' => [
                'code' => '<?php
                    function foo(string $value): string {
                        $value = "yes" === $value;
                        return !$value ? "foo" : "bar";
                    }',
            ],
            'dependentTypeUsedAfterCall' => [
                'code' => '<?php
                    function a(string $_b): void {}

                    function foo(?string $c): string {
                        $iftrue = $c !== null;

                        if ($c !== null) {
                            a($c);
                        }

                        if ($iftrue) {
                            return $c;
                        }

                        return "";
                    }',
            ],
            'notNullAfterSuccessfulNullsafeMethodCall' => [
                'code' => '<?php
                    interface X {
                        public function a(): bool;
                        public function b(): string;
                    }

                    function foo(?X $x): void {
                        if ($x?->a()) {
                            echo $x->b();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'narrowedTypeAfterIdenticalCheckWithOtherType' => [
                'code' => '<?php
                    function a(int $a, ?int $b = null): void
                    {
                        if ($a === $b) {
                            throw new InvalidArgumentException(sprintf("a can not be the same as b (b: %s).", $b));
                        }
                    }',
            ],
            'ThrowableInstanceOfThrowableMayBeFalse' => [
                'code' => '<?php

                    final class Handler
                    {
                        /**
                         * @var class-string<Throwable>[]
                         */
                        private array $dontReport = [];

                        /**
                         * @param class-string<Throwable> $throwable
                         */
                        public function dontReport(string $throwable): void
                        {
                            $this->dontReport[] = $throwable;
                        }

                        public function shouldReport(Throwable $t): bool
                        {
                            foreach ($this->dontReport as $tc) {
                                if ($t instanceof $tc) {
                                    return false;
                                }
                            }

                            return true;
                        }
                    }

                    $h = new Handler();
                    $h->dontReport(RuntimeException::class);

                    $h->shouldReport(new Exception());
                    $h->shouldReport(new RuntimeException());',
            ],
            'ThrowableInstanceOfThrowableMayBeTrue' => [
                'code' => '<?php

                    class Mapper
                    {
                        /** @param class-string<Throwable> $class */
                        final public function map(Throwable $throwable, string $class): ?Throwable
                        {
                            if (! $throwable instanceof $class) {
                                return null;
                            }

                            return $throwable;
                        }
                    }',
            ],
            'combineTwoOrredClausesWithUnnecessaryTerm' => [
                'code' => '<?php
                    function foo(bool $a, bool $b, bool $c): void {
                        if (($a && $b) || (!$a && $c)) {
                            //
                        } else {
                            if ($c) {}
                        }
                    }',
            ],
            'combineTwoOrredClausesWithMoreComplexUnnecessaryTerm' => [
                'code' => '<?php
                    function foo(bool $a, bool $b, bool $c): void {
                        if ((!$a && !$b) || ($a && $b) || ($a && $c)) {
                            throw new \Exception();
                        }

                        if ($a) {}
                    }',
            ],
            'literalIntAssertionsInsideAndOrOr' => [
                'code' => '<?php
                    final class Collection {
                        /** @psalm-assert-if-true 0 $this->count() */
                        public function isEmpty(): bool {
                            return $this->count() === 0;
                        }

                        public function count(): int {
                            return 0;
                        }
                    }

                    function foo(Collection $a, Collection $b): void {
                        if ($a->isEmpty() && $b->isEmpty()) {
                            return;
                        }

                        if ($a->isEmpty() || $a->isEmpty()) {
                            return;
                        }

                        if (!$a->isEmpty() || !$b->isEmpty()) {
                            echo $a->count();
                        }
                    }',
            ],
            'compareToIntInsideIfDNF' => [
                'code' => '<?php
                    function foo(?int $foo): void {
                        if (($foo && $foo !== 5) || (!$foo && rand(0,1))) {
                            return;
                        }

                        if ($foo === null) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'compareToIntInsideIfCNF' => [
                'code' => '<?php
                    function baz(?int $foo): void {
                        if (
                            (!$foo || $foo !== 5) && ($foo || rand(0,1)) && ($foo !== 5 || rand(0, 1))
                        ) {
                            return;
                        }

                        if ($foo === null) {}
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'ternaryAssertionOnBool' => [
                'code' => '<?php
                    function test(string|object $s, bool $b) : string {
                        if (!$b || is_string($s)) {
                            return $b ? $s : "";
                        }
                        return "";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'subclassAfterNegation' => [
                'code' => '<?php
                    abstract class Base {}
                    class A extends Base {}
                    class AChild extends A {}
                    class B extends Base {
                        public string $s = "";
                    }

                    function foo(Base $base): void {
                        if (!$base instanceof A || $base instanceof AChild) {
                            if ($base instanceof B && rand(0, 1)) {
                                echo $base->s;
                            }
                        }
                    }',
            ],
            'subclassAfterElseifNegation' => [
                'code' => '<?php
                    abstract class Base {}
                    class A extends Base {}
                    class AChild extends A {}
                    class B extends Base {
                        public string $s = "";
                    }

                    function foo(Base $base): void {
                        if ($base instanceof A && !($base instanceof AChild)) {
                            // do nothing
                        } elseif ($base instanceof B && rand(0, 1)) {
                            echo $base->s;
                        }
                    }',
            ],
            'staticPropertyNarrowedAfterEarlyReturn' => [
                'code' => '<?php
                    final class A {
                        public static ?int $x = null;
                    }

                    function f(): int {
                        if (A::$x === null) {
                            return 0;
                        }

                        return A::$x;
                    }',
            ],
            'staticPropertyArrayOffsetNarrowedAfterEarlyReturn' => [
                'code' => '<?php
                    final class A {
                        /** @var array{a?: int}|null */
                        public static ?array $x = null;
                    }

                    function f(): int {
                        if (A::$x === null || !isset(A::$x["a"])) {
                            return 0;
                        }

                        return A::$x["a"];
                    }',
            ],
            'staticPropertyPropertyNarrowedAfterEarlyReturn' => [
                'code' => '<?php
                    final class A {
                        public static ?A $x = null;
                        public ?int $y = null;
                    }

                    function f(): int {
                        if (A::$x === null || A::$x->y === null) {
                            return 0;
                        }

                        return A::$x->y;
                    }',
            ],
            'offsetKnownFromTheConditionIsForgottenWhenTheArrayIsReassignedAndThenAnOffsetIsWritten' => [
                'code' => '<?php
                    $a = ["test" => rand(0, 1) ? null : "x"];

                    if ($a["test"] === null) {
                        $a = ["test" => ""];
                        $a["other"] = 1;
                    }

                    $var = $a["test"];',
                'assertions' => [
                    '$var===' => "''|'x'",
                ],
            ],
            'offsetKnownFromTheConditionIsForgottenWhenTheArrayIsReassignedAndThenUnioned' => [
                'code' => '<?php
                    $a = ["test" => rand(0, 1) ? null : "x"];

                    if ($a["test"] === null) {
                        $a = ["test" => ""];
                        $a += ["other" => 1];
                    }

                    $var = $a["test"];',
                'assertions' => [
                    '$var===' => "''|'x'",
                ],
            ],
            'offsetKnownFromTheConditionIsKeptWhenTheArrayIsOnlyUnioned' => [
                'code' => '<?php
                    $a = ["test" => rand(0, 1) ? null : "x"];

                    if ($a["test"] === null) {
                        $a += ["other" => 1];
                    }

                    $var = $a["test"];',
                'assertions' => [
                    '$var===' => "'x'|null",
                ],
            ],
            'offsetKnownBeforeAnArrayUnionAssignmentIsKeptAfterTheIf' => [
                'code' => '<?php
                    /** @param mixed $extra */
                    function widget(bool $a, bool $b, $extra): string {
                        $variables = ["style" => "s", "uri" => "public://x"];
                        $key = $variables["uri"] . ".dims";

                        if ($a) {
                            $variables["width"] = 1;
                        } elseif ($b) {
                            $variables += $extra;
                        } else {
                            $variables["width"] = null;
                        }

                        $uri = $variables["uri"];
                        /** @psalm-check-type-exact $uri = "public://x" */

                        return $uri . $key;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedOperand'],
                'php_version' => '8.0',
            ],
            'offsetKnownFromTheConditionIsForgottenWhenTheArrayIsReassigned' => [
                'code' => '<?php
                    $dummy = ["test" => ""];
                    $a = ["test" => rand(0, 1) ? null : "x"];

                    if ($a["test"] === null) {
                        $a = $dummy;
                    }

                    $var = $a["test"];',
                'assertions' => [
                    '$var===' => "''|'x'",
                ],
            ],
            'offsetKnownFromTheConditionIsKeptWhenTheArrayIsNotReassigned' => [
                'code' => '<?php
                    $a = ["test" => rand(0, 1) ? null : "x"];

                    if ($a["test"] === null) {
                        $a["other"] = 1;
                    }

                    $var = $a["test"];',
                'assertions' => [
                    '$var===' => "'x'|null",
                ],
            ],
            'intTruthinessIsNotParadoxicalAfterReassignment' => [
                'code' => '<?php
                    function f(int $n): void {
                        if ($n) {
                            $n = $n - 1;

                            if (!$n) {
                                echo "zero";
                            }
                        }
                    }',
            ],
            'clauseIsKeptByANestedScopeAfterAnEarlierReassignment' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c): void {
                        $a = load();

                        if ($a === null && $b === null) {
                            return;
                        }

                        if ($c) {
                            echo "c";
                        }

                        while ($c) {
                            $c = rand(0, 1) === 1;
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
            ],
            'clauseIsKeptWhenANestedScopeAssignsAnotherVar' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c, bool $d): ?int {
                        $z = null;

                        if ($a === null && $b === null) {
                            return null;
                        }

                        while ($c) {
                            $z = load();
                            $c = rand(0, 1) === 1;
                        }

                        $y = $d ? ($z = load()) : 1;

                        if ($a === null) {
                            takesInt($b);
                        }

                        return $y ?? $z;
                    }',
            ],
            'clauseIsKeptWhenAMethodCallWritesAnotherProperty' => [
                'code' => '<?php
                    final class C {
                        public ?int $foo = 1;
                        public ?int $other = 1;

                        public function resetOther(): void {
                            $this->other = null;
                        }
                    }

                    function takesInt(int $_i): void {}

                    function f(C $o, ?int $b): void {
                        if ($o->foo === null && $b === null) {
                            return;
                        }

                        $o->resetOther();

                        if ($o->foo === null) {
                            takesInt($b);
                        }
                    }',
            ],
            'clauseIsKeptWhenAReferenceToAnotherVarIsAssigned' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function f(?int $a, ?int $b, ?int $c): ?int {
                        $r = &$c;

                        if ($a === null && $b === null) {
                            return null;
                        }

                        $r = null;

                        if ($a === null) {
                            takesInt($b);
                        }

                        return $c;
                    }',
            ],
            'clauseIsKeptWhenAReferenceToTheVarIsUnset' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b): void {
                        $r = &$a;

                        if ($a === null && $b === null) {
                            return;
                        }

                        unset($r);

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
            ],
            'clauseIsKeptWhenOnlyAThrowingTernaryArmReassignsTheVar' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        $v = $c ? throw new RuntimeException((string) ($a = load())) : 0;
                        echo $v;

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'clauseIsKeptWhenAMethodCallInANestedScopeNarrowsTheVar' => [
                'code' => '<?php
                    final class A {
                        public function foo(): bool {
                            return true;
                        }
                    }

                    final class B {
                        public function foo(): bool {
                            return true;
                        }
                    }

                    function takesInt(int $_i): void {}

                    /** @param A|B $o */
                    function f($o, ?int $b, bool $c): void {
                        if ($o instanceof B && $b === null) {
                            return;
                        }

                        $x = $c ? $o->foo() : false;
                        echo (int) $x;

                        if ($o instanceof B) {
                            takesInt($b);
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
            'onlyTheOperandOfAnOrContradictedByAnEarlierConditionIsAParadox' => [
                'code' => '<?php
                    final class A {}

                    function f(?A $a, ?A $b, string $s, string $t): void {
                        if ($a && $b) {
                            return;
                        }
                        if ($s === $t || ($a && $b)) {
                            echo "reached";
                        }
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:43',
                'error_levels' => ['TypeDoesNotContainType'],
                'php_version' => '8.0',
            ],
            'aConditionThatContradictsItselfIsReported' => [
                'code' => '<?php
                    function f(?int $a, bool $b): void {
                        if ($a === null && $a !== null && $b) {
                            echo "unreachable";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'anIfConditionTestingAVariableBeforeAssigningItIsCheckedAgainstTheEnclosingIf' => [
                'code' => '<?php
                    function f(string $m): void {
                        if ($m !== "a") {
                            if (($m === "a") || (($m = strtolower($m)) && rand(0, 1))) {
                                echo 1;
                            }
                        }
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:34',
            ],
            'instanceofStoredInVariableIsForgottenWhenTheVariableIsReassigned' => [
                'code' => '<?php
                    interface Foo {}
                    interface Bar {
                        public function bar(): void;
                    }
                    class Baz implements Foo {}

                    function f(Foo $obj): void {
                        $isBar = $obj instanceof Bar;
                        $obj = new Baz();

                        if ($isBar) {
                            $obj->bar();
                        }
                    }',
                'error_message' => 'UndefinedMethod',
            ],
            'instanceofStoredInVariableDoesNotNarrowTheElseBranch' => [
                'code' => '<?php
                    interface Foo {}
                    interface Bar {
                        public function bar(): void;
                    }

                    function f(Foo $obj): void {
                        $isBar = $obj instanceof Bar;

                        if ($isBar) {
                            echo "bar";
                        } else {
                            $obj->bar();
                        }
                    }',
                'error_message' => 'UndefinedInterfaceMethod',
            ],
            'booleanVariableNarrowingNotKeptInElseOfOr' => [
                'code' => <<<'PHP'
                    <?php
                    final class Bag {
                        public function get(): int {
                            return 1;
                        }
                    }

                    function f(?Bag $b, bool $c): void {
                        $has = $b !== null;
                        if ($has || $c) {
                            echo 1;
                        } else {
                            echo $b->get();
                        }
                    }
                    PHP,
                'error_message' => 'PossiblyNullReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'narrowingOfOrLeftOperandNotKeptAfterIt' => [
                'code' => <<<'PHP'
                    <?php
                    final class Bag {
                        public function get(): int {
                            return 1;
                        }
                    }

                    function f(?Bag $b, bool $c): void {
                        if ($b !== null || $c) {
                            echo 1;
                        }

                        echo $b->get();
                    }
                    PHP,
                'error_message' => 'PossiblyNullReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'deadOperandOnTheRightOfAnOrIsParadoxical' => [
                'code' => '<?php
                    final class Model {
                        public const A = "MODEL_A";
                        public const B = "MODEL_B";
                    }

                    function pick(string $m, bool $flag): int {
                        if ($m !== Model::A && $m !== Model::B) {
                            if ($flag || $m === Model::A) {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:42',
            ],
            'deadOperandOnTheLeftOfAnOrIsParadoxical' => [
                'code' => '<?php
                    final class Model {
                        public const A = "MODEL_A";
                        public const B = "MODEL_B";
                    }

                    function pick(string $m, bool $flag): int {
                        if ($m !== Model::A && $m !== Model::B) {
                            if ($m === Model::A || $flag) {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:33',
            ],
            'deadOperandInAChainOfOrsIsParadoxical' => [
                'code' => '<?php
                    final class Model {
                        public const A = "MODEL_A";
                        public const B = "MODEL_B";
                    }

                    function pick(string $m, bool $flag, bool $other): int {
                        if ($m !== Model::A && $m !== Model::B) {
                            if ($flag || $other || $m === Model::B) {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:52',
            ],
            'deadOperandNextToAnAssignmentOfAnotherVariableIsParadoxical' => [
                'code' => '<?php
                    function h(): string {
                        return rand(0, 1) ? "a" : "b";
                    }

                    function pick(string $m, bool $flag): string {
                        if ($m !== "a") {
                            if ($flag || ($n = h()) === "b" || $m === "a") {
                                return $n ?? "";
                            }
                        }

                        return "";
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:64',
            ],
            'deadOperandBeforeAnOperandAssigningItsVariableIsParadoxical' => [
                'code' => '<?php
                    function h(): string {
                        return rand(0, 1) ? "a" : "b";
                    }

                    function pick(string $m, bool $flag): int {
                        if ($m !== "a") {
                            if ($flag || $m === "a" || ($m = h()) === "b") {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:42',
            ],
            'deadOperandNextToAnArrowFunctionAssigningItsVariableIsParadoxical' => [
                'code' => '<?php
                    function accepts(callable $c): bool {
                        return (bool) $c();
                    }

                    function pick(string $m, bool $flag): int {
                        if ($m !== "a") {
                            if ($flag || accepts(static fn(): string => $m = "a") || $m === "a") {
                                return 1;
                            }
                        }

                        return 0;
                    }',
                'error_message' => 'ParadoxicalCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:86',
            ],
            'conditionOfOnlyValuesAnEarlierConditionExcludedIsParadoxical' => [
                'code' => '<?php
                    function test(string $value): int {
                        if ($value === "a" || $value === "b") {
                            return 1;
                        }

                        if ($value === "a" || $value === "b") {
                            return 2;
                        }

                        return 3;
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'staticPropertyNarrowedToDeclaredTypeAfterEarlyReturn' => [
                'code' => '<?php
                    final class A {
                        public static ?int $x = null;
                    }

                    function f(): string {
                        if (A::$x === null) {
                            return "";
                        }

                        return A::$x;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'threeVarLogicWithChange' => [
                'code' => '<?php
                    function takesString(string $s): void {}

                    function foo(?string $a, ?string $b, ?string $c): void {
                        if ($a !== null || $b !== null || $c !== null) {
                            $c = null;

                            if ($a !== null) {
                                $d = $a;
                            } elseif ($b !== null) {
                                $d = $b;
                            } else {
                                $d = $c;
                            }

                            takesString($d);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'threeVarLogicWithException' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): void {
                        if ($a !== null || $b !== null || $c !== null) {
                            if ($c !== null) {
                                throw new \Exception("bad");
                            }

                            if ($a !== null) {
                                $d = $a;
                            } elseif ($b !== null) {
                                $d = $b;
                            } else {
                                $d = $c;
                            }
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'invertedTwoVarLogicNotNestedWithVarChange' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a !== null || $b !== null) {
                            $b = null;
                        } else {
                            return "bad";
                        }

                        if ($a !== null) return $b;
                        return $a;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'invertedTwoVarLogicNotNestedWithElseif' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (rand(0, 1)) {
                            // do nothing
                        } elseif ($a || $b) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'NullableReturnStatement',
                'error_levels' => ['RiskyTruthyFalsyComparison'],
            ],
            'threeVarLogicWithElseifAndAnd' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b && $c) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a && !$b) return $c;
                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => ['RiskyTruthyFalsyComparison'],
            ],
            'twoVarLogicNotNestedWithElseifIncorrectlyReinforcedInIf' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a) {
                            $a = "";
                        } elseif ($b) {
                            // do nothing
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => ['RiskyTruthyFalsyComparison'],
            ],
            'repeatedIfStatements' => [
                'code' => '<?php
                    /** @return string|null */
                    function foo(?string $a) {
                        if ($a) {
                            return $a;
                        }

                        if ($a) {

                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
                'error_levels' => ['RiskyTruthyFalsyComparison'],
            ],
            'repeatedConditionals' => [
                'code' => '<?php
                    function foo(?object $a): void {
                        if ($a) {
                            // do something
                        } elseif ($a) {
                            // can never get here
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'repeatedAndConditional' => [
                'code' => '<?php
                    class C {}
                    function foo(?C $a, ?C $b): void {
                        if ($a && $b) {
                            echo "a";
                        } elseif ($a && $b) {
                            echo "b";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'andConditionalAfterOrConditional' => [
                'code' => '<?php
                    function foo(string $a, string $b): void {
                        if ($a || $b) {
                            echo "a";
                        } elseif ($a && $b) {
                            echo "b";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'repeatedVarFromOrConditional' => [
                'code' => '<?php
                    function foo(string $a, string $b): void {
                        if ($a || $b) {
                            echo "a";
                        } elseif ($a) {
                            echo "b";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'typeDoesntEqualType' => [
                'code' => '<?php
                    $a = "hello";
                    $b = 5;
                    if ($a !== $b) {}',
                'error_message' => 'RedundantCondition',
            ],
            'stringConcatenationTrackedInvalid' => [
                'code' => '<?php
                    $x = "a";
                    $x = "_" . $x;
                    $array = [$x => 2];
                    echo $array["other"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'redundantTwoVarInElseif' => [
                'code' => '<?php
                    class A {}

                    $from = rand(0, 1) ? new A() : null;
                    $to = rand(0, 1) ? new A() : null;

                    if ($from === null && $to === null) {
                    } elseif ($from !== null) {
                    } elseif ($to !== null) {}',
                'error_message' => 'RedundantCondition',
            ],
            'paradoxInTernary' => [
                'code' => '<?php
                    function foo(string $input) : string {
                        return $input === "a" ? "bar" : ($input === "a" ? "foo" : "b");
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'mismatchingChecks' => [
                'code' => '<?php
                    function doesntFindBug(?string $old, ?string $new): void {
                        if (empty($old) && empty($new)) {
                            return;
                        }

                        if (($old && empty($new)) || ($new && empty($old))) {
                            return;
                        }
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => ['RiskyTruthyFalsyComparison'],
            ],
            'dependentTypeInvalidated' => [
                'code' => '<?php
                    class A {
                        public function isValid() : bool {
                            return (bool) rand(0, 1);
                        }

                        public function foo() : void {}
                    }

                    function takesA(?A $a) : void {
                        $is_valid_a = $a && $a->isValid();

                        if (rand(0, 1)) {
                            $is_valid_a = false;
                        }

                        if ($is_valid_a) {
                            $a->foo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'possiblyNullAfterFalsyNullsafeMethodCall' => [
                'code' => '<?php
                    interface X {
                        public function a(): bool;
                        public function b(): string;
                    }

                    function foo(?X $x): void {
                        if (!($x?->a())) {
                            echo $x->b();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'arrayShapeListCanBeEmpty' => [
                'code' => '<?php
                    /** @param non-empty-list<mixed> $_list */
                    function foobar(array $_list): void {}

                    $list = random_int(0, 1) ? [] : ["foobar"];

                    foobar($list);
                ',
                'error_message' => 'InvalidArgument',
            ],
            'clauseOnVarReassignedInWhileIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        while ($n-- > 0) {
                            $a = load();
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInDoWhileIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        do {
                            $a = load();
                        } while ($n-- > 0);

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInForIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        for ($i = 0; $i < $n; $i++) {
                            $a = load();
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInForeachIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        foreach ($list as $_) {
                            $a = load();
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInTernaryIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        $x = $c ? ($a = load()) : 1;
                        echo (int) $x;

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInCoalesceIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        $x = $list[0] ?? ($a = load());
                        echo (int) $x;

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInAndOperandIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        $x = $c && ($a = load()) !== 2;
                        echo (int) $x;

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInOrOperandIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        $x = $c || ($a = load()) !== 2;
                        echo (int) $x;

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInSwitchCaseIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        switch ($n) {
                            case 1:
                                $a = load();
                                break;
                            default:
                                echo 1;
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInMatchArmIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        $x = match ($n) {
                            1 => ($a = load()),
                            default => 2,
                        };
                        echo (int) $x;

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'clauseOnVarReassignedInCatchIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function mayThrow(): void {
                        if (rand(0, 1)) {
                            throw new Exception();
                        }
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        try {
                            mayThrow();
                        } catch (Exception) {
                            $a = load();
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'clauseOnVarReassignedInFinallyIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function mayThrow(): void {
                        if (rand(0, 1)) {
                            throw new Exception();
                        }
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, int $n, bool $c, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        try {
                            mayThrow();
                        } finally {
                            $a = load();
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnIntTruthinessIsDroppedOnReassignment' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function zero(): int {
                        return 0;
                    }

                    function f(int $a, ?int $b): void {
                        if (!$a && $b === null) {
                            return;
                        }

                        $a = zero();

                        if (!$a) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnFloatTruthinessIsDroppedOnReassignment' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function zero(): float {
                        return 0.0;
                    }

                    function f(float $a, ?int $b): void {
                        if (!$a && $b === null) {
                            return;
                        }

                        $a = zero();

                        if (!$a) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnInArrayIsDroppedOnReassignment' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): int {
                        return rand(0, 5);
                    }

                    /** @param list<int> $list */
                    function f(int $a, ?int $b, array $list): void {
                        if (!in_array($a, $list, true) && $b === null) {
                            return;
                        }

                        $a = load();

                        if (!in_array($a, $list, true)) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOfElseifConditionIsDroppedWhenItsBodyReassignsTheVar' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function f(?int $x, bool $a): void {
                        if ($a) {
                            echo "a";
                        } elseif ($x !== null) {
                            $x = null;
                        } else {
                            return;
                        }

                        if (!$a) {
                            takesInt($x);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnPropertyIsDroppedWhenAMethodCallWritesIt' => [
                'code' => '<?php
                    final class C {
                        public ?int $foo = 1;

                        public function reset(): void {
                            $this->foo = null;
                        }
                    }

                    function takesInt(int $_i): void {}

                    function f(C $o, ?int $b): void {
                        if ($o->foo === null && $b === null) {
                            return;
                        }

                        $o->reset();

                        if ($o->foo === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarIsDroppedWhenAssignedThroughAReference' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b): void {
                        $r = &$a;

                        if ($a === null && $b === null) {
                            return;
                        }

                        $r = load();

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnReferenceIsDroppedWhenTheVarItIsBoundToIsAssigned' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b): void {
                        $r = &$a;

                        if ($r === null && $b === null) {
                            return;
                        }

                        $a = load();

                        if ($r === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarIsDroppedWhenAssignedThroughAReferenceInANestedScope' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c): void {
                        $r = &$a;

                        if ($a === null && $b === null) {
                            return;
                        }

                        if ($c) {
                            $r = load();
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInTheRightOfAnAndInAnIfConditionIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        if ($c && ($a = load()) !== 2) {
                            echo "c";
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInTheRightOfAnOrInAnIfConditionIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        if ($c || ($a = load()) !== 2) {
                            echo "c";
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInATernaryInAnIfConditionIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        if (($c ? ($a = load()) : 1) !== 3) {
                            echo "c";
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInAnElseifConditionIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        if ($c) {
                            echo "c";
                        } elseif (($a = load()) !== 2) {
                            echo "a";
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInACatchThatContinuesIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    /** @param list<int> $list */
                    function f(?int $a, ?int $b, array $list): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        foreach ($list as $_) {
                            try {
                                if (rand(0, 1)) {
                                    throw new Exception();
                                }
                            } catch (Exception $e) {
                                $a = load();
                                continue;
                            }
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarIsDroppedWhenAnElseifBodyAssignsItThroughAReference' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c, bool $d): void {
                        $r = &$a;

                        if ($a === null && $b === null) {
                            return;
                        }

                        if ($c) {
                            echo "c";
                        } elseif ($d) {
                            $r = load();
                        } else {
                            return;
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'clauseOnVarReassignedInTheRightOfAnAndInAnElseifConditionIsDropped' => [
                'code' => '<?php
                    function takesInt(int $_i): void {}

                    function load(): ?int {
                        return rand(0, 1) ? null : 1;
                    }

                    function f(?int $a, ?int $b, bool $c, bool $d): void {
                        if ($a === null && $b === null) {
                            return;
                        }

                        if ($d) {
                            echo "d";
                        } elseif ($c && ($a = load()) !== 5) {
                            echo "c";
                        }

                        if ($a === null) {
                            takesInt($b);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
        ];
    }
}
