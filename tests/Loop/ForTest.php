<?php

declare(strict_types=1);

namespace Psalm\Tests\Loop;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class ForTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'forIntRangeShiftedByEveryIterationIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $total = 0;
                        for ($i = 0; $i < 100; $i++) {
                            $total += rand(1, 3);
                        }
                        /** @psalm-check-type-exact $total = int<1, max> */;
                        return $total;
                    }',
            ],
            'forIntRangeShiftedInAnArrayOffsetIsWidened' => [
                'code' => '<?php
                    function f(stdClass $o): int {
                        $a = ["n" => 0, "o" => $o];
                        for ($i = 0; $i < 100; $i++) {
                            $a["n"] -= rand(1, 3);
                        }
                        /** @psalm-check-type-exact $a = array{n: int<min, -1>, o: stdClass} */;
                        return $a["n"];
                    }',
            ],
            'forIntRangeComputedFromTheCounterIsNotWidened' => [
                'code' => '<?php
                    function f(): int {
                        $x = 0;
                        for ($i = 0; $i < 10; $i++) {
                            $x = $i * 2;
                        }
                        /** @psalm-check-type-exact $x = int<0, 18> */;
                        return $x;
                    }',
            ],
            'forIntRangeShiftedByThePostExpressionOfABodyEndingInAReturnIsWidened' => [
                'code' => '<?php
                    function f(): void {
                        for ($i = 0; ; $i += rand(1, 3)) {
                            if ($i > 10) {
                                echo "reachable";
                            }
                            if (rand(0, 1)) {
                                continue;
                            }
                            return;
                        }
                    }',
            ],
            'forIntRangeShiftedByThePostExpressionBeforeAReturnIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        for ($i = 0; ; $i += rand(1, 3)) {
                            if ($i < 10) {
                                continue;
                            }
                            /** @psalm-check-type-exact $i = int<10, max> */;
                            return $i;
                        }
                    }',
            ],
            'forEverVariableIsWhatTheBreaksLeave' => [
                'code' => '<?php
                    function f(): void {
                        for (;;) {
                            $x = rand(0, 1) ? "s" : null;
                            if ($x === null) {
                                continue;
                            }
                            break;
                        }
                        /** @psalm-check-type-exact $x = "s" */;
                    }',
            ],
            'mixedConditionVariableOfAForLoopIsNotNonEmptyAfterIt' => [
                'code' => '<?php
                    function check(mixed $m): void {
                        for ($i = 0; $i < 3 && $m; $i++) {
                        }

                        $after = $m;
                        /** @psalm-check-type-exact $after = mixed */
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'variableNarrowedAndReassignedByAnIfKeepsTheTypesOfBothBranches' => [
                'code' => '<?php
                    function normalize(array|string|int $raw): array {
                        if (!is_array($raw)) {
                            $raw = [];
                        }

                        /** @psalm-check-type-exact $raw = array<array-key, mixed> */
                        return $raw;
                    }

                    function toggle(): void {
                        $flag = true;

                        while (rand(0, 1)) {
                            if ($flag) {
                                $flag = false;
                            } else {
                                $flag = true;
                            }
                        }

                        /** @psalm-check-type-exact $flag = bool */
                        echo 1;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'flagToggledByAnIfAndAnElseifKeepsBothValuesAfterTheLoop' => [
                'code' => '<?php
                    function toggle(): void {
                        $flag = false;

                        for ($i = 0; $i < 10; $i++) {
                            if ($flag) {
                                $flag = false;
                            } elseif ($i > 8) {
                                $flag = true;
                            }
                        }

                        /** @psalm-check-type-exact $flag = bool */;

                        if ($flag) {
                            echo "Flag set";
                        }
                    }

                    class Config {}

                    /** @param mixed $raw */
                    function defaulted(?Config $config, $raw): void {
                        if ($config === null) {
                            $config = new Config();
                        }

                        /** @psalm-check-type-exact $config = Config */;

                        if (!is_array($raw)) {
                            $raw = [];
                        }

                        /** @psalm-check-type-exact $raw = array<array-key, mixed> */;
                    }

                    function pick(bool $flag, int $i): void {
                        if ($flag) {
                            $flag = false;
                        } elseif ($i > 8) {
                            $flag = true;
                        }

                        /** @psalm-check-type-exact $flag = bool */;
                    }',
            ],
            'variableAssignedSeveralTimesInTheForConditionHasTheLastAssignedType' => [
                'code' => '<?php
                    for (; $value = 42, $value = rand(0, 1) === 1;) {
                        break;
                    }',
                'assertions' => [
                    '$value===' => 'bool',
                ],
                'ignored_issues' => [],
                'php_version' => '8.2',
            ],
            'variableDefinedBeforeAForAndAssignedSeveralTimesInItsConditionHasWhatTheLastConditionLeaves' => [
                'code' => '<?php
                    $value = "a";
                    for (; $value = 42, $value = rand(0, 1) === 1;) {
                        echo "again";
                    }',
                'assertions' => [
                    '$value===' => 'false',
                ],
            ],
            'variableDefinedBeforeAForAndAssignedInALaterConditionHasWhatTheLastConditionLeaves' => [
                'code' => '<?php
                    $value = "a";
                    for (; print("x"), $value = random_int(0, 3);) {
                        echo "again";
                    }',
                'assertions' => [
                    '$value===' => '0',
                ],
            ],
            'variableAssignedInALaterCommaSeparatedForConditionIsDefinedAfterTheLoop' => [
                'code' => '<?php
                    function next_int(): int {
                        return 1;
                    }

                    function f(): int {
                        for ($i = 0; $first = next_int(), $second = next_int(); ++$i) {
                            echo $first;
                        }

                        return $second;
                    }',
            ],
            'variableAssignedInTheForConditionIsDefinedAfterTheLoop' => [
                'code' => '<?php
                    function next_int(): int {
                        return 1;
                    }

                    function f(): int {
                        for (; $value = next_int(); ) {
                            echo $value;
                        }

                        return $value;
                    }',
            ],
            'postIncrementInConditionHoldsTheValueTheLoopExitsWith' => [
                'code' => '<?php
                    for ($i = 0; $i++ < 3;) {
                        echo $i;
                    }',
                'assertions' => [
                    '$i===' => 'int<4, 4>',
                ],
            ],
            'preDecrementInConditionOfALoopAlwaysEnteredHoldsTheValueTheLoopExitsWith' => [
                'code' => '<?php
                    for ($i = 5; --$i > 0;) {
                        echo $i;
                    }',
                'assertions' => [
                    '$i===' => 'int<0, 0>',
                ],
            ],
            'variableAssignedAfterAnInnerLoopThatOnlyBreaksItself' => [
                'code' => '<?php
                    function f(): int {
                        $foo = 0;

                        for ($i = 0; $i < 10; ++$i) {
                            for ($j = 0; $j < 10; ++$j) {
                                break;
                            }

                            $foo = 1;
                        }

                        return $foo;
                    }',
            ],
            'narrowedPropertyOfLoopVariableIsKeptWhenTheVariableIsReassigned' => [
                'code' => '<?php
                    final class Node {
                        public ?Node $next = null;
                    }

                    function f(Node $node): void {
                        if ($node->next !== null) {
                            for ($i = 0; $i < 2; $i++) {
                                $node = $node->next ?? new Node();
                            }
                            if ($node->next === null) {
                                echo "end";
                            }
                        }
                    }',
            ],
            'forTrue' => [
                'code' => '<?php
                    function ret(): int {
                        for (;;) {
                            return 1;
                        }
                    }',
            ],
            'implicitFourthLoop' => [
                'code' => '<?php
                    function test(): int {
                      $x = 0;
                      $y = 1;
                      $z = 2;
                      for ($i = 0; $i < 3; $i++) {
                        $x = $y;
                        $y = $z;
                        $z = 5;
                      }
                      return $x;
                    }',
            ],
            'falseToBoolInContinueAndBreak' => [
                'code' => '<?php
                    $a = false;

                    for ($i = 0; $i < 4; $i++) {
                      $j = rand(0, 10);

                      if ($j === 2) {
                        $a = true;
                        continue;
                      }

                      if ($j === 3) {
                        $a = true;
                        break;
                      }
                    }',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'forLoopwithOKChange' => [
                'code' => '<?php
                    $j = 5;
                    for ($i = $j; $i < 4; $i++) {
                      $j = 9;
                    }',
            ],
            'preventNegativeZeroScrewingThingsUp' => [
                'code' => '<?php
                    function foo() : void {
                      /** @var array<int, int> $v */
                      $v = [1 => 0];
                      for ($d = 0; $d <= 10; $d++) {
                        for ($k = -$d; $k <= $d; $k += 2) {
                          if ($k === -$d || ($k !== $d && $v[$k-1] < $v[$k+1])) {
                            $x = $v[$k+1];
                          } else {
                            $x = $v[$k-1] + 1;
                          }

                          $v[$k] = $x;
                        }
                      }
                    }',
            ],
            'whileTrueWithBreak' => [
                'code' => '<?php
                    for (;;) {
                        $a = "hello";
                        break;
                    }
                    for (;;) {
                        $b = 5;
                        break;
                    }',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'continueOutsideLoop' => [
                'code' => '<?php
                    class Node {
                        /** @var Node|null */
                        public $next;
                    }

                    /** @return void */
                    function test(Node $head) {
                        for ($node = $head; $node; $node = $next) {
                            $next = $node->next;
                            $node->next = null;
                        }
                    }',
            ],
            'echoAfterFor' => [
                'code' => '<?php
                    for ($i = 0; $i < 5; $i++);
                    echo $i;',
            ],
            'nestedEchoAfterFor' => [
                'code' => '<?php
                    for ($i = 1; $i < 2; $i++) {
                        for ($j = 1; $j < 2; $j++) {}
                    }

                    echo $i * $j;',
            ],
            'reconcileOuterVars' => [
                'code' => '<?php
                    for ($i = 0; $i < 2; $i++) {
                        if ($i === 0) {
                            continue;
                        }
                    }',
            ],
            'noException' => [
                'code' => '<?php
                    /**
                     * @param list<int> $arr
                     */
                    function cartesianProduct(array $arr) : void {
                        for ($i = 20; $i > 0 && $arr[$i] === 5 ; $i--) {}
                    }',
            ],
            'noCrashOnLongThing' => [
                'code' => '<?php
                    /**
                     * @param list<array{a: array{int, int}}> $data
                     */
                    function makeData(array $data) : array {
                        while (rand(0, 1)) {
                            while (rand(0, 1)) {
                                while (rand(0, 1)) {
                                    if (rand(0, 1)) {
                                        continue;
                                    }

                                    /** @psalm-suppress PossiblyUndefinedArrayOffset */
                                    $data[0]["a"] = array_merge($data[0]["a"], $data[0]["a"]);
                                }
                            }
                        }

                        return $data;
                    }',
            ],
            'InfiniteForLoop' => [
                'code' => '<?php
                    /**
                     * @return int
                     */
                    function g() {
                        for (;;) {
                            return 1;
                        }
                    }

                    /**
                     * @return int
                     */
                    function h() {
                        for (;1;) {
                            return 1;
                        }
                    }',
            ],
            'forLoopWithAlwaysTruthyLastConditionIsInfinite' => [
                'code' => '<?php
                    function g(): int {
                        for ($i = 0; $i < 5, true; $i++) {
                            if (rand(0, 1)) {
                                return $i;
                            }
                        }
                    }',
            ],
            'forLoopBodyOnlyGetsTheLastCondition' => [
                'code' => '<?php
                    function f(?int $a, int $n): void {
                        for ($i = 0; $a !== null, $i < $n; $i++) {
                            if ($a === null) {
                                echo "null";
                            }
                        }
                    }',
            ],
            'forLoopIsOnlyLeftByTheLastCondition' => [
                'code' => '<?php
                    $a = rand(0, 1) ? null : rand();
                    for (; rand(0, 1), $a !== null;) {
                        echo "";
                    }
                    $n = rand();
                    for ($i = 0, $j = 0; $i < 10, $j < $n; $i++, $j++) {
                        echo "";
                    }',
                'assertions' => [
                    '$a===' => 'null',
                    '$i===' => 'int<0, max>',
                ],
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
            'forBreakBeforeDefinitionAndBreakAfter' => [
                'code' => '<?php
                    for ($i = 0; $i < 4; $i++) {
                        if (rand(0, 1)) {
                            break;
                        }

                        $car = "Volvo";

                        if (rand(0, 1)) {
                            break;
                        }
                    }

                    echo $car;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:26',
            ],
            'flagFalseInEveryBranchThatChangesItIsStillAlwaysFalsy' => [
                'code' => '<?php
                    function check(bool $flag, int $i): void {
                        if ($flag) {
                            $flag = false;
                        } elseif ($i > 8) {
                            $flag = false;
                        }

                        if ($flag) {
                            echo 1;
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'variableAssignedAfterAnInnerLoopBreakingTheOuterOneIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(): void {
                        for ($i = 0; $i < 10; ++$i) {
                            for ($j = 0; $j < 10; ++$j) {
                                break 2;
                            }

                            $foo = 1;
                        }

                        echo $foo;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedAfterASwitchAndALoopBreakingTheOuterLoopIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(): void {
                        for ($i = 0; $i < 10; ++$i) {
                            switch (true) {
                                case true:
                                    for ($j = 0; $j < 10; ++$j) {
                                        break 3;
                                    }
                            }

                            $foo = 1;
                        }

                        echo $foo;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'possiblyUndefinedArrayInWhileAndForeach' => [
                'code' => '<?php
                    for ($i = 0; $i < 4; $i++) {
                        while (rand(0,10) === 5) {
                            $array[] = "hello";
                        }
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:29 - Possibly undefined ' .
                    'global variable $array, first seen on line 4',
            ],
            'forLoopInvalidation' => [
                'code' => '<?php
                    for ($i = 0; $i < 4; $i++) {
                      foreach ([1, 2, 3] as $i) {}
                    }',
                'error_message' => 'LoopInvalidation',
            ],
            'forInfiniteNoBreak' => [
                'code' => '<?php
                    for (;;) {
                        $a = "hello";
                    }

                    echo $a;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'nestedEchoAfterFor' => [
                'code' => '<?php
                    for ($i = 1; $i < 2; $i++) {
                        if (rand(0, 1)) break;
                        for ($j = 1; $j < 2; $j++) {}
                    }

                    echo $i * $j;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
        ];
    }
}
