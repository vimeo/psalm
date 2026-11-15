<?php

declare(strict_types=1);

namespace Psalm\Tests\Loop;

use Override;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

final class DoTest extends TestCase
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
            'operandsOfDoWhileThatIsNeverInItsFirstPassAreNotReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        $counter = 0;

                        do {
                            echo $counter !== 0 && strlen((string) $counter) > 0 ? 'next' : 'first';
                            echo $counter === 0 || (int) $counter > 0 ? 'ok' : 'no';
                            $counter++;
                        } while ($counter < 3);
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'doWhileIntRangeShiftedByEveryIterationIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        do {
                            $i = $i + rand(1, 3);
                        } while ($i <= 10);
                        /** @psalm-check-type-exact $i = int<11, 13> */;
                        return $i;
                    }',
            ],
            'doWhileTrueVariableIsWhatTheBreaksLeave' => [
                'code' => '<?php
                    function f(): void {
                        do {
                            $x = rand(0, 1) ? "s" : null;
                            if ($x === null) {
                                continue;
                            }
                            if (rand(0, 1)) {
                                break;
                            }
                        } while (true);
                        /** @psalm-check-type-exact $x = "s" */;
                    }',
            ],
            'doWhileConditionOnUnsetPropertyIsNotRedundant' => [
                'code' => '<?php
                    class Options {
                        public bool $foo = false;
                    }

                    $options = new Options();

                    if (random_int(0, 1)) {
                        $options->foo = true;
                    }

                    $i = 0;
                    do {
                        echo $i;
                        $i++;
                    } while ($options->foo && $i < 3);',
            ],
            'doWhileConditionAfterContinueBeforeAssignment' => [
                'code' => '<?php
                    do {
                        $run = false;

                        if (time() % 3 === 0) {
                            continue;
                        }

                        $run = true;
                    } while ($run);',
            ],
            'doWhileConditionAfterContinueWithSeveralVars' => [
                'code' => '<?php
                    do {
                        $a = false;
                        $b = 0;

                        if (time() % 3 === 0) {
                            continue;
                        }

                        $a = true;
                        $b = 1;
                    } while ($a || $b === 1);',
            ],
            'doWhileTrue' => [
                'code' => '<?php
                    function ret(): int {
                        do {
                            return 1;
                        } while (true);
                    }',
            ],
            'doWhileVar' => [
                'code' => '<?php
                    $worked = false;

                    do {
                        $worked = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$worked' => 'true',
                ],
            ],
            'doWhileVarWithPossibleBreak' => [
                'code' => '<?php
                    $a = false;

                    do {
                        if (rand(0, 1)) {
                            break;
                        }
                        if (rand(0, 1)) {
                            $a = true;
                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'SKIPPED-doWhileVarWithPossibleBreakThatSetsToTrue' => [
                'code' => '<?php
                    $a = false;
                    $b = false;

                    do {
                        $b = true;
                        if (rand(0, 1)) {
                            $a = true;
                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,1));',
                'assertions' => [
                    '$a' => 'true',
                    '$b' => 'true',
                ],
            ],
            'doWhileVarWithPossibleBreakThatMaybeSetsToTrue' => [
                'code' => '<?php
                    $a = false;

                    do {
                        if (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                            }

                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,1));',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'doWhileVarWithPossibleInitialisingBreakNoInitialDefinition' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            $worked = true;
                            break;
                        }
                        $worked = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$worked' => 'true',
                ],
            ],
            'doWhileUndefinedVar' => [
                'code' => '<?php
                    do {
                        $result = (bool) rand(0,1);
                    } while (!$result);',
                'assertions' => [
                    '$result' => 'true',
                ],
            ],
            'doWhileVarAndBreak' => [
                'code' => '<?php
                    /** @return void */
                    function foo(string $b) {}

                    do {
                        if (null === ($a = rand(0, 1) ? "hello" : null)) {
                            break;
                        }

                        foo($a);
                    }
                    while (rand(0,100) === 10);',
            ],
            'doWhileWithNotEmptyCheck' => [
                'code' => '<?php
                    class A {
                        /** @var A|null */
                        public $a;

                        public function __construct() {
                            $this->a = rand(0, 1) ? new A : null;
                        }
                    }

                    function takesA(A $a): void {}

                    $a = new A();
                    do {
                        takesA($a);
                        $a = $a->a;
                    } while ($a);',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'doWhileWithMethodCall' => [
                'code' => '<?php
                    class A {
                        public function getParent(): ?A {
                            return rand(0, 1) ? new A() : null;
                        }
                    }

                    $a = new A();

                    do {
                        $a = $a->getParent();
                    } while ($a);',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'doWhileFirstGood' => [
                'code' => '<?php
                    do {
                        $done = rand(0, 1) > 0;
                    } while (!$done);',
            ],
            'doWhileWithIfException' => [
                'code' => '<?php
                    class A
                    {
                        /**
                         * @var null|A
                         */
                        public $parent;

                        public static function foo(A $a) : void
                        {
                            do {
                                if ($a->parent === null) {
                                    throw new \Exception("bad");
                                }

                                $a = $a->parent;
                            } while (rand(0,1));
                        }
                    }',
            ],
            'doWhileWithIfExceptionOutside' => [
                'code' => '<?php
                    class A
                    {
                        /**
                         * @var null|A
                         */
                        public $parent;

                        public static function foo(A $a) : void
                        {
                            if ($a->parent === null) {
                                throw new \Exception("bad");
                            }

                            do {
                                $a = $a->parent;
                            } while ($a->parent && rand(0, 1));
                        }
                    }',
            ],
            'doWhileDefinedVar' => [
                'code' => '<?php
                    $value = null;
                    do {
                        $count = rand(0, 1);
                        $value = 6;
                    } while ($count);',
            ],
            'doWhileDefinedVarWithPossibleBreak' => [
                'code' => '<?php
                    $value = null;
                    do {
                        if (rand(0, 1)) {
                            break;
                        }
                        $count = rand(0, 1);
                        $value = 6;
                    } while ($count);',
            ],
            'invalidateBothByRefAssignmentsInDo' => [
                'code' => '<?php
                    function foo(?string &$i) : void {}
                    function bar(?string &$i) : void {}

                    $c = null;

                    do {
                        if ($c === null || $c === "" || $c === "0") {
                            foo($c);
                        } else {
                            bar($c);
                        }
                    } while (rand(0, 1));',
            ],
            'doParentCall' => [
                'code' => '<?php
                    class A {
                        /** @return A|false */
                        public function getParent() {
                            return rand(0, 1) ? new A : false;
                        }
                    }

                    $a = new A();

                    do {
                        $a = $a->getParent();
                    } while ($a !== false);',
            ],
            'doCallInWhile' => [
                'code' => '<?php
                    class A {
                        public function getParent() : ?A {
                            return rand(0, 1) ? new A : null;
                        }
                    }

                    $a = new A();
                    $i = 0;
                    do {
                        $i++;
                    } while ($a = $a->getParent());',
            ],
            'doWithContinue' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            continue;
                        }
                    } while (rand(0, 1));',
            ],
            'noEmptyArrayAccessComplaintInsideDo' => [
                'code' => '<?php
                    $foo = [];
                    do {
                        if (isset($foo["bar"])) {}
                        $foo["bar"] = "bat";
                    } while (rand(0, 1));',
            ],
            'noRedundantConditionAfterDoWhile' => [
                'code' => '<?php
                    $i = 5;
                    do {} while (--$i > 0);
                    echo $i === 0;',
            ],
            'doWhileNonInfinite' => [
                'code' => '<?php
                    function foo(): int {
                        do {
                            $value = mt_rand(0, 10);
                            if ($value > 5) continue;
                            break;
                        } while (true);

                        return $value;
                    }',
            ],
            'doNoRedundant' => [
                'code' => '<?php
                    class Event {}

                    function fetchEvent(): ?Event {
                        return rand(0, 1) ? new Event() : null;
                    }

                    function nextEvent(bool $c): void {
                        do {
                            $e = fetchEvent();
                        } while ($c && $e);
                    }',
            ],
            'doConditionInWhileAndIfWithSingleVar' => [
                'code' => '<?php
                    $b = !!rand(0, 1);

                    do {
                        if (!$b) {
                           $b = !rand(0, 1);
                        }
                    } while (!$b);',
                'assertions' => [
                    '$b' => 'true',
                ],
            ],
            'doConditionInWhileAndIfWithTwoVars' => [
                'code' => '<?php
                    $b = !!rand(0, 1);

                    do {
                        $s = rand(0, 1);
                        if (!$b && $s) {}
                    } while (!$b && $s);

                    if ($b) {}',
            ],
            'regularAssignmentInsideDo' => [
                'code' => '<?php
                    do {
                        $code = rand(0, 1);
                        echo "here";
                    } while ($code === 1);',
            ],
            'destructuringAssignmentInsideDo' => [
                'code' => '<?php
                    do {
                        [$code] = [rand(0, 1)];
                        echo "here";
                    } while ($code === 1);',
            ],
            'counterOfDoWhileIsNotAZeroInItsFirstPassForTheIssues' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param list<string> $existing */
                    function nextId(array $existing): string {
                        $counter = 0;

                        do {
                            $id = rand(0, 1) . ($counter === 0 ? '' : '-' . (string) $counter);
                            $counter++;
                        } while (in_array($id, $existing, true));

                        return $id;
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'castsOfDoWhileCounterAreNotNeverInItsFirstPass' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        $counter = 0;

                        do {
                            if ($counter !== 0) {
                                echo (string) $counter, (int) $counter, (float) $counter;
                            }
                            $counter++;
                        } while ($counter < 3);
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'usesOfAVariableOfDoWhileThatIsNeverInItsFirstPassAreNotReported' => [
                'code' => <<<'PHP'
                    <?php
                    final class Foo {
                        public int $foo = 1;

                        public function m(): int {
                            return 1;
                        }
                    }

                    function fetch(): void {
                        $o = 0;
                        do {
                            if ($o !== 0) {
                                $fetched = $o->foo;
                                echo $fetched, $o->m();
                            }
                            $o = new Foo();
                        } while (rand(0, 1));
                    }

                    function arrayAccess(): void {
                        $a = 0;
                        do {
                            if ($a !== 0) {
                                echo $a[0];
                            }
                            $a = ['x'];
                        } while (rand(0, 1));
                    }

                    function offset(): void {
                        $values = ['x', 'y'];
                        $i = 0;
                        do {
                            if ($i !== 0) {
                                echo $values[$i];
                            }
                            $i = 1;
                        } while (rand(0, 1));
                    }

                    function call(): void {
                        $c = 0;
                        do {
                            if ($c !== 0) {
                                echo $c();
                            }
                            $c = static fn(): int => 1;
                        } while (rand(0, 1));
                    }
                    PHP,
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'doWhileConditionAssignmentKeepsTheAssignedTypeAfterTheLoop' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    $try = 0;
                    do {
                        $try++;
                    } while ((null !== $error = fetchError()) && $try < 3);

                    $result = $error;

                    if (null !== $error) {
                        echo "failed";
                    }',
                'assertions' => [
                    '$result===' => 'null|stdClass',
                ],
            ],
            'doWhileConditionAssignmentAfterTheFirstOperandMayBeSkipped' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    $try = 0;
                    $error = null;
                    do {
                        $try++;
                    } while ($try < 3 && null !== ($error = fetchError()));

                    $result = $error;',
                'assertions' => [
                    '$result===' => 'null|stdClass',
                ],
            ],
            'doWhileConditionAssignmentIsNarrowedWhenTheLoopEndsOnIt' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    do {
                        $x = 1;
                    } while (null !== ($error = fetchError()));

                    $result = $error;',
                'assertions' => [
                    '$result===' => 'null',
                ],
            ],
            'doWhileConditionAssignmentInAnOrOperandEndsAsFalsy' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    $try = 0;
                    do {
                        $try++;
                    } while (($error = fetchError()) || $try < 3);

                    $result = $error;',
                'assertions' => [
                    '$result===' => 'null',
                ],
            ],
            'doWhileConditionAssignmentInInstanceofKeepsTheAssignedType' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    do {
                        $x = 1;
                    } while (($error = fetchError()) instanceof stdClass && rand(0, 1));

                    $result = $error;',
                'assertions' => [
                    '$result===' => 'null|stdClass',
                ],
            ],
            'doWhileConditionAssignmentInAFunctionCallKeepsTheAssignedType' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    $try = 0;
                    do {
                        $try++;
                    } while (!is_null($error = fetchError()) && $try < 3);

                    $result = $error;',
                'assertions' => [
                    '$result===' => 'null|stdClass',
                ],
            ],
            'doWhileConditionAssignmentOfAVariableAssignedInTheBody' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    $error = 5;
                    $try = 0;
                    do {
                        $error = 6;
                        $try++;
                    } while ($try < 3 && null !== ($error = fetchError()));

                    $result = $error;',
                'assertions' => [
                    '$result===' => '6|null|stdClass',
                ],
            ],
            'doWhileConditionAssignmentToAPropertyKeepsTheAssignedType' => [
                'code' => '<?php
                    function fetchError(): ?stdClass {
                        return rand(0, 1) ? new stdClass() : null;
                    }

                    $o = new stdClass();
                    $o->error = null;
                    do {
                        $x = 1;
                    } while ((null !== $o->error = fetchError()) && rand(0, 1));

                    $result = $o->error;',
                'assertions' => [
                    '$result===' => 'null|stdClass',
                ],
            ],
            'doWhileConditionOnVariableIncrementedAfterIt' => [
                'code' => '<?php
                    $v = 0;
                    do {
                        $foo = "a";
                        if ($v > 0) {
                            $foo .= sprintf("_%02d", $v);
                        }
                        $v++;
                    } while (rand(1, 10) < 5);

                    echo $foo;',
            ],
            'doWhileUsesVariableIncrementedAfterTheCondition' => [
                'code' => '<?php
                    $i = 0;
                    do {
                        if ($i > 0) {
                            echo $i;
                        }
                        $i++;
                    } while ($i < 10);',
            ],
            'doWhileBodyWithoutAssignmentRunsBeforeTheCondition' => [
                'code' => '<?php
                    function f(?int $a): void {
                        do {
                            if ($a === null) {
                                echo "null";
                            }
                        } while ($a !== null && rand(0, 1) > 5);
                    }',
            ],
            'doWhileBodyWithoutAssignmentThenNegatedCondition' => [
                'code' => '<?php
                    $a = rand(0, 1) ? null : rand();
                    do {
                        echo (string) $a;
                    } while ($a === null);
                    $b = rand(0, 1) ? null : rand(0, 10);
                    do {
                        echo (string) $b;
                    } while ($b !== null && $b > 5);',
                'assertions' => [
                    '$a===' => 'int',
                    '$b===' => 'int<0, 5>|null',
                ],
            ],
            'doWhileConditionOnMixedOffsetSaysNothingOnceNegated' => [
                'code' => '<?php
                    /** @var mixed $m */
                    $m = json_decode("[]");
                    $a = rand(0, 1) ? null : rand();
                    do {
                        echo "";
                    } while ($a !== null && $m["x"]);
                    /** @var mixed $n */
                    $n = json_decode("[]");
                    $b = rand(0, 1) ? null : rand();
                    do {
                        $b = $b;
                    } while ($b !== null && $n["x"]);',
                'assertions' => [
                    '$a===' => 'int|null',
                    '$b===' => 'int|null',
                ],
                'ignored_issues' => ['MixedArrayAccess'],
            ],
            'doWhileBodyThatAlwaysBreaksNeverEvaluatesTheCondition' => [
                'code' => '<?php
                    function f(string $s): void {
                        do {
                            $s = null;
                            break;
                        } while (strlen($s) === 2);
                    }',
            ],
            'doWhileWithContinueIsLeftWhenItsConditionIsFalse' => [
                'code' => '<?php
                    $a = rand(0, 1) ? null : rand();
                    do {
                        if (rand(0, 1)) {
                            continue;
                        }
                        echo "";
                    } while ($a === null);
                    $b = null;
                    do {
                        if (rand(0, 1)) {
                            $b = 5;
                            continue;
                        }
                        $b = null;
                    } while ($b === null);',
                'assertions' => [
                    '$a===' => 'int',
                    '$b===' => '5',
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
            'invalidCastOfTheTypeOfTheFirstIterationOfDoWhileIsReported' => [
                'code' => <<<'PHP'
                    <?php
                    function castFirst(): void {
                        $x = [];
                        do {
                            echo (string) $x;
                            $x = 'a';
                        } while (rand(0, 1));
                    }
                    PHP,
                'error_message' => 'InvalidCast',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidFunctionCallOfTheTypeOfTheFirstIterationOfDoWhileIsReported' => [
                'code' => <<<'PHP'
                    <?php
                    function callFirst(): void {
                        $x = 5;
                        do {
                            echo $x();
                            $x = static fn(): int => 1;
                        } while (rand(0, 1));
                    }
                    PHP,
                'error_message' => 'InvalidFunctionCall',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidArrayOffsetOfTheTypeOfTheFirstIterationOfDoWhileIsReported' => [
                'code' => <<<'PHP'
                    <?php
                    /** @param list<string> $l */
                    function offsetFirst(array $l): void {
                        $i = new stdClass;
                        do {
                            echo $l[$i];
                            $i = 0;
                        } while (rand(0, 1));
                    }
                    PHP,
                'error_message' => 'InvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'doWhileBreakBeforeDefinition' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        do {
                            if (rand()) {
                                break;
                            }

                            $v = 1;

                            try {
                                if (rand()) {
                                    break;
                                }
                            } finally {
                                echo "done";
                            }
                        } while (rand());

                        echo $v;
                    }
                    PHP,
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:19:10',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'doWhileConditionOnNarrowedPropertyIsRedundant' => [
                'code' => '<?php
                    class Options {
                        public bool $foo = false;
                    }

                    $options = new Options();
                    $options->foo = true;

                    $i = 0;
                    do {
                        $i++;
                    } while ($options->foo && $i < 3);',
                'error_message' => 'RedundantCondition',
            ],
            'doWhileVarWithPossibleBreakWithoutDefining' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            break;
                        }
                        $worked = true;
                    }
                    while (rand(0,1));

                    echo $worked;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'doWhileVarWithPossibleBreakThatMaybeSetsToTrueWithoutDefining' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                            }

                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,1));

                    echo $a;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'SKIPPED-doWhileVarWithPossibleContinueWithoutDefining' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            continue;
                        }
                        $worked = true;
                    }
                    while (rand(0,1));

                    echo $worked;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'possiblyUndefinedArrayInDo' => [
                'code' => '<?php
                    do {
                        $array[] = "hello";
                    } while (rand(0, 1));',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - Possibly undefined ' .
                    'global variable $array, first seen on line 3',
            ],
            'invalidCastInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(array $values): void {
                        $i = 0;
                        do {
                            echo (string) $values;
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'InvalidCast',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'mixedPropertyFetchInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(mixed $m): void {
                        $i = 0;
                        do {
                            $m->foo;
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'MixedPropertyFetch',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'mixedArrayAccessInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(mixed $m): void {
                        $i = 0;
                        do {
                            $m[0];
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'MixedArrayAccess',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'mixedMethodCallInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(mixed $m): void {
                        $i = 0;
                        do {
                            $m->m();
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'MixedMethodCall',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'mixedArgumentInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(mixed $m): void {
                        $i = 0;
                        do {
                            echo $m;
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'MixedArgument',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'mixedAssignmentInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(mixed $m): void {
                        $i = 0;
                        do {
                            $x = $m;
                            echo is_string($x) ? $x : "";
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'MixedAssignment',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidArrayOffsetInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(stdClass $o): void {
                        $values = ['x'];
                        $i = 0;
                        do {
                            echo $values[$o];
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'InvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'invalidFunctionCallInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(int $n): void {
                        $i = 0;
                        do {
                            $n();
                            $i++;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'InvalidFunctionCall',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'issueInTheBodyOfDoWhileIsStillReported' => [
                'code' => <<<'PHP'
                    <?php
                    function f(int $i): void {
                        do {
                            $i++;
                            echo $undefined;
                        } while ($i < 3);
                    }
                    PHP,
                'error_message' => 'UndefinedVariable',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
        ];
    }
}
