<?php

declare(strict_types=1);

namespace Psalm\Tests\Loop;

use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;
use function sort;

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
            'postDecrementInConditionHoldsTheValueTheLoopExitsWith' => [
                'code' => '<?php
                    $i = 5;
                    do {
                        echo $i;
                    } while ($i-- > 0);',
                'assertions' => [
                    '$i===' => '-1',
                ],
            ],
            'postIncrementInConditionHoldsTheValueTheLoopExitsWith' => [
                'code' => '<?php
                    $i = 5;
                    do {
                        echo $i;
                    } while ($i++ < 10);',
                'assertions' => [
                    '$i===' => '11',
                ],
            ],
            'preIncrementInAConditionThatHoldsOnlyForTheFirstValueExitsWithTheNextOne' => [
                'code' => '<?php
                    $i = 8;
                    do {
                        echo $i;
                    } while (++$i < 10);',
                'assertions' => [
                    '$i===' => '10',
                ],
            ],
            'postDecrementOfAFloatInConditionIsNotNarrowed' => [
                'code' => '<?php
                    $f = 5.0;
                    do {
                        echo $f;
                    } while ($f-- > 0);',
                'assertions' => [
                    '$f===' => 'float',
                ],
            ],
            'doWhileNullCoalesceOfAVariableAssignedLaterInTheBody' => [
                'code' => '<?php
                    function f(): string {
                        do {
                            $s = ($s ?? "") . "x";
                        } while (rand(0, 1));

                        return $s;
                    }',
            ],
            'doWhileVariableAssignedInTheBodyIsDefinedAfterTheLoop' => [
                'code' => '<?php
                    function f(): int {
                        do {
                            $line = rand(0, 1) ? "a" : false;
                            $i = isset($i) ? $i + 1 : 0;
                        } while ($line !== false && $i < 5);

                        return $i;
                    }',
            ],
            'doWhileIntRangeShiftedInAnArrayOffsetIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $a = ["n" => 0];
                        do {
                            $a["n"] += rand(1, 3);
                        } while ($a["n"] <= 10);
                        /** @psalm-check-type-exact $a = array{n: int<11, 13>} */;
                        return $a["n"];
                    }',
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
            'SKIPPED-doWhileVarWithPossibleBreakThatSetsToTrue' => [ // #13757
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
            'doWhileVarAssignedBeforeContinueIsDefinedAfterTheLoop' => [
                'code' => '<?php
                    function f(): void {
                        do {
                            if (rand(0, 1)) { $z = 1; continue; }
                            $z = 2;
                        } while (rand(0, 1));
                        echo $z;
                    }',
            ],
            'doWhileVarAssignedBeforeContinueAndAtTheEndOfTheBody' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            $z = 1;
                            continue;
                        }
                        $z = 2;
                    } while (rand(0, 1));',
                'assertions' => [
                    '$z===' => '1|2',
                ],
            ],
            'doWhileVarAssignedBeforeContinue2OfANestedLoop' => [
                'code' => '<?php
                    do {
                        foreach ([1, 2] as $_) {
                            if (rand(0, 1)) {
                                $z = 1;
                                continue 2;
                            }
                        }
                        $z = 2;
                    } while (rand(0, 1));',
                'assertions' => [
                    '$z===' => '1|2',
                ],
            ],
            'doWhileVarAssignedBeforeContinueIsNarrowedByTheCondition' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            $z = null;
                            continue;
                        }
                        $z = 2;
                    } while ($z === null);',
                'assertions' => [
                    '$z===' => '2',
                ],
            ],
            'doWhileInALoopVarAssignedBeforeContinue' => [
                'code' => '<?php
                    function f(): int {
                        $sum = 0;
                        foreach ([1, 2] as $_) {
                            do {
                                if (rand(0, 1)) {
                                    $z = 1;
                                    continue;
                                }
                                $z = 2;
                            } while (rand(0, 1));

                            $sum += $z;
                        }

                        return $sum;
                    }',
            ],
            'doWhileVarPossiblyDefinedBeforeIsAssignedBeforeContinue' => [
                'code' => '<?php
                    function f(): int {
                        if (rand(0, 1)) {
                            $z = 0;
                        }
                        do {
                            if (rand(0, 1)) {
                                $z = 1;
                                continue;
                            }
                            $z = 2;
                        } while (rand(0, 1));

                        return $z;
                    }',
            ],
            'doWhilePropertyNotAssignedBeforeOneOfTheContinuesKeepsItsType' => [
                'code' => '<?php
                    final class C {
                        public ?int $p = null;
                    }

                    function f(C $c): void {
                        do {
                            if (rand(0, 1)) {
                                $c->p = 1;
                                continue;
                            }
                            if (rand(0, 1)) {
                                continue;
                            }
                            $c->p = 2;
                        } while (rand(0, 1));

                        if ($c->p === null) {
                            echo "null";
                        }
                    }',
            ],
            'doWhilePropertyAssignedBeforeContinueAndAtTheEndOfTheBody' => [
                'code' => '<?php
                    final class C {
                        public ?int $p = null;
                    }

                    function f(C $c): int {
                        do {
                            if (rand(0, 1)) {
                                $c->p = 1;
                                continue;
                            }
                            $c->p = 2;
                        } while (rand(0, 1));

                        return $c->p;
                    }',
            ],
        ];
    }

    private const FIRST_ITERATION_CODE = <<<'PHP'
        <?php
        function castFirst(): void {
            $x = new stdClass();
            do {
                echo (string) $x;
                $x = 'a';
            } while (rand(0, 1));
        }

        function callFirst(): void {
            $x = 5;
            do {
                echo $x();
                $x = static fn(): int => 1;
            } while (rand(0, 1));
        }

        /** @param list<string> $l */
        function offsetFirst(array $l): void {
            $i = new stdClass();
            do {
                echo $l[$i];
                $i = 0;
            } while (rand(0, 1));
        }

        function inANestedDo(): void {
            $x = new stdClass();
            do {
                do {
                    echo (string) $x;
                } while (rand(0, 1));
                $x = 'a';
            } while (rand(0, 1));
        }

        function inALoopBody(): void {
            while (rand(0, 1)) {
                $x = new stdClass();
                do {
                    echo (string) $x;
                    $x = 'a';
                } while (rand(0, 1));
            }
        }

        function readBeforeTheAssignment(): void {
            do {
                if (rand(0, 1)) {
                    echo $w;
                }
                $w = 1;
            } while (rand(0, 1));
        }

        /** @param list<string> $existing */
        function impossibleInTheFirstIteration(array $existing): string {
            $counter = 0;
            do {
                $id = rand(0, 1) . ($counter === 0 ? '' : '-' . (string) $counter);
                $counter++;
            } while (in_array($id, $existing, true));
            return $id;
        }

        function impossibleInTheFirstIterationAndInvalidLater(): void {
            $s = null;
            do {
                if ($s !== null) {
                    echo (string) $s;
                }
                $s = new stdClass();
            } while (rand(0, 1));
        }

        final class C {
            public ?C $n = null;
            public int $v = 0;
        }

        function inAChainOfFetches(C $c): void {
            $x = null;
            do {
                echo $x->n->n->v;
                $x = $c;
            } while (rand(0, 1));
        }

        function inAClosureCapturingByReference(): void {
            $x = new stdClass();
            do {
                $f = static function () use (&$x): void {
                    echo (string) $x;
                };
                $x = 'a';
                $f();
            } while (rand(0, 1));
        }

        function inAnArrowFunction(): void {
            $x = new stdClass();
            do {
                $f = static fn(): string => (string) $x;
                $x = 'a';
                if (rand(0, 1)) {
                    echo $f();
                }
            } while (rand(0, 1));
        }

        function inTheArgumentOfAnAnonymousClass(): void {
            $x = new stdClass();
            do {
                $o = new class ((string) $x) {
                    public function __construct(string $_s) {}
                };
                $x = 'a';
            } while (rand(0, 1));
        }
        PHP;

    public function testDoWhileFirstIterationIssuesAreDefinite(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, self::FIRST_ITERATION_CODE);
        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                [5, 'InvalidCast', 'stdClass cannot be cast to string'],
                [13, 'InvalidFunctionCall', 'Cannot treat type 5 as callable'],
                [
                    22,
                    'InvalidArrayOffset',
                    'Cannot access value on variable $l using a stdClass offset, expecting array-key',
                ],
                [31, 'InvalidCast', 'stdClass cannot be cast to string'],
                [41, 'InvalidCast', 'stdClass cannot be cast to string'],
                [50, 'PossiblyUndefinedVariable', 'Possibly undefined variable $w, first seen on line 52'],
                [70, 'InvalidCast', 'stdClass cannot be cast to string'],
                [84, 'NullPropertyFetch', 'Cannot get property on null variable $x'],
                [84, 'PossiblyNullPropertyFetch', 'Cannot get property on possibly null variable $x->n of type C|null'],
                [
                    84,
                    'PossiblyNullPropertyFetch',
                    'Cannot get property on possibly null variable $x->n->n of type C|null',
                ],
                [93, 'PossiblyInvalidCast', 'stdClass cannot be cast to string'],
                [103, 'InvalidCast', 'stdClass cannot be cast to string'],
                [114, 'InvalidCast', 'stdClass cannot be cast to string'],
            ],
            self::getIssues($file_path),
        );
    }

    public function testDoWhileFirstIterationIssuesAreReportedWhenPossibleIssuesAreNot(): void
    {
        $this->testConfig->throw_exception = false;

        foreach (['PossiblyInvalidCast', 'PossiblyInvalidFunctionCall', 'PossiblyInvalidArrayOffset'] as $issue_type) {
            $this->testConfig->setCustomErrorLevel($issue_type, Config::REPORT_SUPPRESS);
        }

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile($file_path, self::FIRST_ITERATION_CODE);
        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                [5, 'InvalidCast', 'stdClass cannot be cast to string'],
                [13, 'InvalidFunctionCall', 'Cannot treat type 5 as callable'],
                [
                    22,
                    'InvalidArrayOffset',
                    'Cannot access value on variable $l using a stdClass offset, expecting array-key',
                ],
                [31, 'InvalidCast', 'stdClass cannot be cast to string'],
                [41, 'InvalidCast', 'stdClass cannot be cast to string'],
                [50, 'PossiblyUndefinedVariable', 'Possibly undefined variable $w, first seen on line 52'],
                [70, 'InvalidCast', 'stdClass cannot be cast to string'],
                [84, 'NullPropertyFetch', 'Cannot get property on null variable $x'],
                [84, 'PossiblyNullPropertyFetch', 'Cannot get property on possibly null variable $x->n of type C|null'],
                [
                    84,
                    'PossiblyNullPropertyFetch',
                    'Cannot get property on possibly null variable $x->n->n of type C|null',
                ],
                [103, 'InvalidCast', 'stdClass cannot be cast to string'],
                [114, 'InvalidCast', 'stdClass cannot be cast to string'],
            ],
            self::getIssues($file_path),
        );
    }

    public function testDoWhileIssuesOfAnIncludedFileStayPossible(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $included_file_path = self::$src_dir_path . 'included.php';

        $this->addFile(
            $included_file_path,
            <<<'PHP'
                <?php
                $f = function () use (&$x): void {
                    echo (string) $x;
                };
                PHP,
        );
        $this->addFile(
            $file_path,
            <<<'PHP'
                <?php
                $x = new stdClass();
                do {
                    require __DIR__ . '/included.php';
                    $x = 'a';
                    $f();
                } while (rand(0, 1));
                PHP,
        );
        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [[3, 'PossiblyInvalidCast', 'stdClass cannot be cast to string']],
            self::getIssues($included_file_path),
        );
    }

    /**
     * @return list<array{int, string, string}>
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function getIssues(string $file_path): array
    {
        $issues = array_map(
            static fn(IssueData $issue): array => [$issue->line_from, $issue->type, $issue->message],
            IssueBuffer::getIssuesDataForFile($file_path),
        );

        sort($issues);

        return $issues;
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'doWhilePropertyFetchOnAVariableNullOnlyInTheFirstIteration' => [
                'code' => <<<'PHP'
                    <?php
                    final class A {
                        public string $p = "";
                    }

                    function f(): void {
                        $a = null;
                        do {
                            echo $a->p;
                            $a = new A();
                        } while (rand(0, 1));
                    }
                    PHP,
                'error_message' => 'NullPropertyFetch - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:14 - '
                    . 'Cannot get property on null variable $a',
            ],
            'doWhileAnonymousClassPropertyNotSetInConstructor' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        do {
                            $o = new class {
                                public int $p;
                                public function __construct() {}
                            };
                            echo $o->p;
                        } while (rand(0, 1));
                    }

                    function g(): void {
                        $a = null;
                        do {
                            echo $a?->p;
                            $a = new A();
                        } while (rand(0, 1));
                    }

                    final class A {
                        public string $p = "";
                    }
                    PHP,
                'error_message' => 'PropertyNotSetInConstructor - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:24',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'doWhileReadBeforeTheAssignmentInTheBody' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        do {
                            if (rand(0, 1)) {
                                echo $w;
                            }
                            $w = 1;
                        } while (rand(0, 1));
                    }
                    PHP,
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:18',
            ],
            'doWhileBodyIssueIsReported' => [
                'code' => '<?php
                    $i = 0;
                    do {
                        echo $undefined;
                        $i++;
                    } while ($i < 3);',
                'error_message' => 'Cannot find referenced variable $undefined in global scope',
            ],
            'doWhileBodyIssueInAFunctionIsReported' => [
                'code' => '<?php
                    function f(): void {
                        $i = 0;
                        do {
                            echo $undefined;
                            $i++;
                        } while ($i < 3);
                    }',
                'error_message' => 'Cannot find referenced variable $undefined',
            ],
            'doWhileReadBeforeAReferenceAssignment' => [
                'code' => <<<'PHP'
                    <?php
                    function f(array $arr): void {
                        do {
                            if (rand(0, 1)) {
                                echo $r;
                            }
                            $r = &$arr[rand(0, 9)];
                        } while (rand(0, 1));
                    }
                    PHP,
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:5:18',
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
            'doWhileVarWithPossibleContinueWithoutDefining' => [
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
            'doWhileVarNotAssignedBeforeContinue' => [
                'code' => '<?php
                    function f(): int {
                        do {
                            if (rand(0, 1)) {
                                continue;
                            }
                            $z = 2;
                        } while (rand(0, 1));

                        return $z;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'doWhileVarPossiblyAssignedBeforeContinue' => [
                'code' => '<?php
                    function f(): int {
                        do {
                            if (rand(0, 1)) {
                                if (rand(0, 1)) {
                                    $z = 1;
                                }
                                continue;
                            }
                            $z = 2;
                        } while (rand(0, 1));

                        return $z;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'doWhileVarNotAssignedBeforeOneOfTheContinues' => [
                'code' => '<?php
                    function f(): int {
                        do {
                            if (rand(0, 1)) {
                                $z = 1;
                                continue;
                            }
                            if (rand(0, 1)) {
                                continue;
                            }
                            $z = 2;
                        } while (rand(0, 1));

                        return $z;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'doWhileVarUnsetByFinallyAfterContinue' => [
                'code' => '<?php
                    function f(): int {
                        do {
                            try {
                                if (rand(0, 1)) {
                                    $z = 1;
                                    continue;
                                }
                                $z = 2;
                            } finally {
                                unset($z);
                            }
                        } while (rand(0, 1));

                        return $z;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'doWhileVarNotAssignedBeforeContinue2OfANestedLoop' => [
                'code' => '<?php
                    function f(): int {
                        do {
                            foreach ([1, 2] as $_) {
                                if (rand(0, 1)) {
                                    continue 2;
                                }
                            }
                            $z = 2;
                        } while (rand(0, 1));

                        return $z;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'doWhileInALoopVarNotAssignedBeforeContinue' => [
                'code' => '<?php
                    function f(): int {
                        $sum = 0;
                        foreach ([1, 2] as $_) {
                            do {
                                if (rand(0, 1)) {
                                    continue;
                                }
                                $z = 2;
                            } while (rand(0, 1));

                            $sum += $z;
                        }

                        return $sum;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'doWhileVarPossiblyDefinedBeforeIsNotAssignedBeforeContinue' => [
                'code' => '<?php
                    function f(): int {
                        if (rand(0, 1)) {
                            $z = 0;
                        }
                        do {
                            if (rand(0, 1)) {
                                continue;
                            }
                            $z = 2;
                        } while (rand(0, 1));

                        return $z;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'doWhileVarOnlyAssignedBeforeContinue' => [
                'code' => '<?php
                    function f(): int {
                        do {
                            if (rand(0, 1)) {
                                $z = 1;
                                continue;
                            }
                        } while (rand(0, 1));

                        return $z;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
        ];
    }
}
