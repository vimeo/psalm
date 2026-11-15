<?php

declare(strict_types=1);

namespace Psalm\Tests\Loop;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_map;

use const DIRECTORY_SEPARATOR;

final class WhileTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testAnIssueInTheLoopDoesNotChangeTheUndefinedVariableIssues(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                final class O {
                    public ?int $p = null;
                }

                function takesInt(int $_i): void {}

                function definedLater(): void {
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            echo $a;
                        }
                        $a = 1;
                        takesInt("s");
                    }
                }

                function chain(array $arr): void {
                    foreach ($arr as $_) {
                        if (rand(0, 1)) {
                            echo $c;
                        }
                        $c = $b;
                        $b = 1;
                        takesInt("s");
                    }
                }

                function nested(): void {
                    while (rand(0, 1)) {
                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                echo $n;
                            }
                            takesInt("s");
                        }
                        $n = 1;
                    }
                }

                /** @param list<int> $arr */
                function definedBeforeContinue(array $arr): void {
                    foreach ($arr as $v) {
                        if ($v > 1) {
                            echo $x;
                        }
                        if ($v) {
                            $x = 1;
                            continue;
                        }
                        takesInt("s");
                        break;
                    }
                }

                function definedBeforeBreak(O $o): void {
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            echo $y;
                        }
                        $o->p = 1;
                        if (rand(0, 1)) {
                            $y = 1;
                            break;
                        }
                        takesInt("s");
                    }
                }

                function unsetAtTheEnd(): void {
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            echo $z;
                        }
                        $z = 1;
                        takesInt("s");
                        unset($z);
                    }
                }

                function definedBeforeContinueOnly(O $o): void {
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            echo $w;
                        }
                        if (rand(0, 1)) {
                            $w = 1;
                            continue;
                        }
                        $o->p = 1;
                        takesInt("s");
                    }
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                'PossiblyUndefinedVariable 11:34',
                'InvalidScalarArgument 14:34',
                'PossiblyUndefinedVariable 21:34',
                'PossiblyUndefinedVariable 23:30',
                'InvalidScalarArgument 25:34',
                'PossiblyUndefinedVariable 33:38',
                'InvalidScalarArgument 35:38',
                'PossiblyUndefinedVariable 45:34',
                'InvalidScalarArgument 51:34',
                'UndefinedVariable 59:34',
                'MixedArgument 59:34',
                'InvalidScalarArgument 66:34',
                'UndefinedVariable 73:34',
                'MixedArgument 73:34',
                'InvalidScalarArgument 76:34',
                'PossiblyUndefinedVariable 84:34',
                'InvalidScalarArgument 91:34',
            ],
            array_map(
                static fn(IssueData $issue): string
                    => $issue->type . ' ' . $issue->line_from . ':' . $issue->column_from,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    public function testAnIssueInTheLoopDoesNotChangeTheUnusedVariableIssues(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;
        $this->project_analyzer->getCodebase()->reportUnusedVariables();

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                function takesInt(int $_i): void {}

                function readByTheNextIteration(): void {
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;
                        $a = 1;
                        takesInt("s");
                    }
                }

                function definedInTheLoopAndReadByTheNextIteration(): void {
                    while (rand(0, 1)) {
                        if (isset($b)) {
                            echo $b;
                        }
                        $b = 1;
                        takesInt("s");
                    }
                }

                function neverRead(): void {
                    while (rand(0, 1)) {
                        $c = 1;
                        takesInt("s");
                    }
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                'InvalidScalarArgument 9:34',
                'InvalidScalarArgument 19:34',
                'InvalidScalarArgument 26:34',
                'UnusedVariable 25:25',
            ],
            array_map(
                static fn(IssueData $issue): string
                    => $issue->type . ' ' . $issue->line_from . ':' . $issue->column_from,
                IssueBuffer::getIssuesDataForFile($file_path),
            ),
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'whileTrueIntRangeShiftedByEveryIterationIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        while (true) {
                            $i += rand(1, 3);
                            if ($i > 10) {
                                break;
                            }
                        }
                        /** @psalm-check-type-exact $i = int<11, max> */;
                        return $i;
                    }',
            ],
            'whileTrueIntRangeDecrementedByEveryIterationIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        while (true) {
                            $i -= rand(1, 3);
                            if ($i < -10) {
                                break;
                            }
                        }
                        /** @psalm-check-type-exact $i = int<min, -11> */;
                        return $i;
                    }',
            ],
            'whileTrueIntRangeShiftedThroughAnotherVariableIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        while (true) {
                            $j = $i + rand(1, 3);
                            $i = $j;
                            if ($i > 10) {
                                break;
                            }
                        }
                        /** @psalm-check-type-exact $i = int<11, max> */;
                        return $i;
                    }',
            ],
            'whileTrueIntRangeShiftedBeforeAContinueIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        while (true) {
                            $i += rand(1, 3);
                            if (rand(0, 1)) {
                                continue;
                            }
                            if ($i > 10) {
                                break;
                            }
                        }
                        /** @psalm-check-type-exact $i = int<11, max> */;
                        return $i;
                    }',
            ],
            'whileTrueIntRangeShiftedInANestedLoopIsWidened' => [
                'code' => '<?php
                    function f(): int {
                        $total = 0;
                        foreach ([1, 2] as $_) {
                            $i = 0;
                            while (true) {
                                $i += rand(1, 3);
                                if ($i > 10) {
                                    break;
                                }
                            }
                            $total += $i;
                        }
                        /** @psalm-check-type-exact $total = int<11, max> */;
                        return $total;
                    }',
            ],
            'whileIntRangeAssignedOnEveryIterationIsNotWidened' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        while (rand(0, 1)) {
                            $i = rand(1, 3);
                        }
                        /** @psalm-check-type-exact $i = int<0, 3> */;
                        return $i;
                    }',
            ],
            'whileLiteralIntsAssignedInTheLoopAreNotWidened' => [
                'code' => '<?php
                    function f(): int {
                        $state = 0;
                        while (rand(0, 1)) {
                            if ($state === 0) {
                                $state = 1;
                            } elseif ($state === 1) {
                                $state = 2;
                            }
                        }
                        /** @psalm-check-type-exact $state = 0|1|2 */;
                        return $state;
                    }',
            ],
            'whileTrueVariableIsNotWhatAContinueLeaves' => [
                'code' => '<?php
                    interface T {
                        public function supports(): bool;
                    }

                    function dead(T $_t): bool {
                        return false;
                    }

                    function a(array $a): T {
                        $i = 0;
                        while (true) {
                            $x = $a[$i];
                            if (!$x->supports()) {
                                $i++;
                                continue;
                            }
                            if (!dead($x)) {
                                break;
                            }
                            $i++;
                        }
                        /** @psalm-check-type-exact $x = T */;
                        return $x;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedMethodCall', 'MixedArgument'],
            ],
            'whileTrueVariableIsWhatTheBreaksLeave' => [
                'code' => '<?php
                    function continued(): void {
                        while (true) {
                            $x = rand(0, 1) ? "s" : null;
                            if ($x === null) {
                                continue;
                            }
                            if (rand(0, 1)) {
                                break;
                            }
                        }
                        /** @psalm-check-type-exact $x = "s" */;
                    }

                    function endOfTheBody(): void {
                        while (true) {
                            $x = 1.5;
                            if (rand(0, 1)) {
                                $x = 1;
                                break;
                            }
                            $x = "s";
                        }
                        /** @psalm-check-type-exact $x = 1 */;
                    }

                    function breakOutOfASwitch(): void {
                        while (true) {
                            $x = rand(0, 1) ? "a" : 5;
                            switch ($x) {
                                case "a":
                                    break;
                                default:
                                    break 2;
                            }
                        }
                        /** @psalm-check-type-exact $x = 5 */;
                    }

                    function breakTwoOutOfAnInnerLoop(): void {
                        while (true) {
                            $x = "outer";
                            foreach ([1, 2] as $v) {
                                if ($v === 2) {
                                    $x = $v;
                                    break 2;
                                }
                            }
                            if (rand(0, 1)) {
                                continue;
                            }
                            $x = null;
                            break;
                        }
                        /** @psalm-check-type-exact $x = 2|null */;
                    }',
            ],
            'whileTrueVariableDefinedBeforeTheLoopIsWhatTheBreaksLeave' => [
                'code' => '<?php
                    function continued(): void {
                        $x = 1;
                        while (true) {
                            $x = rand(0, 1) ? "s" : null;
                            if ($x === null) {
                                continue;
                            }
                            if (rand(0, 1)) {
                                break;
                            }
                        }
                        /** @psalm-check-type-exact $x = "s" */;
                    }

                    function narrowedBeforeTheBreak(): void {
                        $x = rand(0, 1) ? null : 1;
                        while (true) {
                            if ($x !== null) {
                                break;
                            }
                            $x = rand(0, 1) ? null : 2;
                        }
                        /** @psalm-check-type-exact $x = 1|2 */;
                    }

                    function unsetAtABreak(): void {
                        $x = 1;
                        while (true) {
                            if (rand(0, 1)) {
                                unset($x);
                                break;
                            }
                            $x = 2;
                            break;
                        }
                        if (isset($x)) {
                            /** @psalm-check-type-exact $x = 2 */;
                        }
                    }',
            ],
            'whileTrueVariableIsWhatTheBreaksOfEveryIterationLeave' => [
                'code' => '<?php
                    function f(): void {
                        while (true) {
                            $x = isset($x) ? "again" : 1;
                            if (rand(0, 1)) {
                                break;
                            }
                        }
                        /** @psalm-check-type-exact $x = "again"|1 */;
                    }',
            ],
            'whileTrueBreakThroughAFinallyKeepsWhatTheFinallyAssigns' => [
                'code' => '<?php
                    function finallyInTheLoop(): void {
                        while (true) {
                            try {
                                $x = 1;
                                break;
                            } finally {
                                $x = "f";
                            }
                        }
                        /** @psalm-check-type-exact $x = "f"|1 */;
                    }

                    function finallyAroundTheLoop(): void {
                        try {
                            while (true) {
                                $x = rand(0, 1) ? "s" : null;
                                if ($x === null) {
                                    continue;
                                }
                                break;
                            }
                            /** @psalm-check-type-exact $x = "s" */;
                        } finally {
                            echo "done";
                        }
                    }',
            ],
            'whileTrueVariableWithNoValueAtTheBreaks' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        while (true) {
                            $i += rand(1, 3);
                            if ($i > 10) {
                                break;
                            }
                        }
                        return $i;
                    }',
            ],
            'mixedConditionVariableOfAWhileLoopIsNotNonEmptyAfterIt' => [
                'code' => '<?php
                    function check(mixed $w): void {
                        $j = 0;
                        while ($j < 3 && $w) {
                            $j++;
                        }

                        $after = $w;
                        /** @psalm-check-type-exact $after = mixed */
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
                'php_version' => '8.0',
            ],
            'variableDefinedBeforeAPlainBreakIsDefinedAfterTheInnerLoopOfABreakTwo' => [
                'code' => '<?php
                    while (true) {
                        while (true) {
                            if (rand(0, 1)) {
                                break 2;
                            }

                            $x = 1;
                            break;
                        }

                        echo $x;
                        break;
                    }

                    function f(): void {
                        while (true) {
                            while (true) {
                                if (rand(0, 1)) {
                                    break 2;
                                }

                                $y = 1;
                                break;
                            }

                            echo $y;
                            break;
                        }
                    }',
            ],
            'variableDefinedBeforeTheLoopAndAssignedInTheConditionHasWhatTheConditionLeaves' => [
                'code' => '<?php
                    $result = 1;
                    while ($result = random_int(0, 6)) {
                        echo "again";
                    }',
                'assertions' => [
                    '$result===' => '0',
                ],
            ],
            'variableDefinedBeforeALoopThatAlsoBreaksKeepsWhatTheBreakLeaves' => [
                'code' => '<?php
                    $result = 1;
                    while ($result = random_int(0, 6)) {
                        if (random_int(0, 1)) {
                            break;
                        }
                    }',
                'assertions' => [
                    '$result===' => 'int<0, 6>',
                ],
            ],
            'variableAssignedInACastConditionIsDefinedAfterTheLoop' => [
                'code' => '<?php
                    $a = 1;
                    while ((bool) ($a = random_int(0, 6))) {
                        echo "again";
                    }

                    while ((bool) ($b = random_int(0, 6))) {
                        echo "again";
                    }',
                'assertions' => [
                    '$a===' => '0',
                    '$b===' => '0',
                ],
            ],
            'variableAssignedInANegatedWhileConditionIsWhatTheConditionLeavesOfItAfterTheLoop' => [
                'code' => '<?php
                    /** @return Slot|false */
                    function acquire() {
                        return rand(0, 1) ? new Slot() : false;
                    }

                    final class Slot {
                        public function use(): void {}
                    }

                    function nextLine(): string|false {
                        return false;
                    }

                    while (!($slot = acquire())) {
                        usleep(10);
                    }

                    $slot->use();

                    while (($line = nextLine()) !== false) {
                        echo $line;
                    }

                    while (!($other = acquire())) {
                        if (rand(0, 1)) {
                            break;
                        }
                    }',
                'assertions' => [
                    '$slot===' => 'Slot',
                    '$line===' => 'false',
                    '$other===' => 'Slot|false',
                ],
                'ignored_issues' => ['RedundantCondition', 'MixedAssignment'],
                'php_version' => '8.2',
            ],
            'variableAssignedInTheWhileConditionIsDefinedAfterTheLoop' => [
                'code' => '<?php
                    function next_int(): int {
                        return 1;
                    }

                    function f(): int {
                        while ($value = next_int()) {
                            echo $value;
                        }

                        return $value;
                    }',
            ],
            'variableAssignedInAComparisonInTheWhileConditionIsDefinedAfterTheLoop' => [
                'code' => '<?php
                    function next_int(): int {
                        return 1;
                    }

                    function f(): ?int {
                        while (0 < $written = next_int()) {
                            if ($written > 5) {
                                return null;
                            }
                        }

                        return $written;
                    }',
            ],
            'variableAssignedInTheWhileConditionIsDefinedAfterABreak' => [
                'code' => '<?php
                    function next_int(): int {
                        return 1;
                    }

                    function f(): int {
                        while ($value = next_int()) {
                            if (rand(0, 1)) {
                                break;
                            }
                        }

                        return $value;
                    }',
            ],
            'variableAssignedInTheConditionOfAnIfThatBreaks' => [
                'code' => '<?php
                    final class Link {
                        public function getPrevious(): ?Link {
                            return null;
                        }

                        public function getClass(): string {
                            return "link";
                        }
                    }

                    function root(Link $link): Link {
                        while ($link->getClass() === "link") {
                            if (($previous = $link->getPrevious()) === null) {
                                break;
                            }

                            $link = $previous;
                        }

                        return $link;
                    }',
            ],
            'variableAssignedLaterInTheBodyHoldsWhatThePreviousIterationLeft' => [
                'code' => '<?php
                    function f(): void {
                        $whileValues = [];
                        while (rand(0, 1)) {
                            $whileValues[] = isset($previous) ? $previous : 0;
                            $previous = "x";
                        }
                        /** @psalm-check-type-exact $whileValues = list<\'x\'|0> */;

                        $forValues = [];
                        for ($i = 0; $i < 3; $i++) {
                            $forValues[] = $last ?? null;
                            $last = "y";
                        }
                        /** @psalm-check-type-exact $forValues = non-empty-list<\'y\'|null> */;
                    }',
            ],
            'variableAssignedInTheBodyOfALoopThatPreIncrementsAnInitialLiteral' => [
                'code' => '<?php
                    $try = 0;
                    while (++$try < 10) {
                        $fname = "thing";
                    }
                    echo $fname;',
                'assertions' => [
                    '$fname===' => '\'thing\'',
                ],
            ],
            'variableAssignedInTheBodyOfALoopWhoseConditionHoldsForTheInitialLiteral' => [
                'code' => '<?php
                    $a = 0;
                    while ($a < 3) {
                        $x1 = 1;
                        $a++;
                    }

                    $b = 3;
                    while ($b <= 3) {
                        $x2 = 2;
                        $b++;
                    }

                    $c = 5;
                    while ($c > 4) {
                        $x3 = 3;
                        $c--;
                    }

                    $d = 5;
                    while ($d >= 5) {
                        $x4 = 4;
                        $d--;
                    }

                    $e = 5;
                    while (--$e > 3) {
                        $x5 = 5;
                    }

                    $f = 0;
                    while (++$f <= 1) {
                        $x6 = 6;
                    }

                    $g = 1;
                    while (--$g >= 0) {
                        $x7 = 7;
                    }

                    $h = 0;
                    while (++$h < 1 + 1) {
                        $x8 = 8;
                    }

                    $all = [$x1, $x2, $x3, $x4, $x5, $x6, $x7, $x8];',
            ],
            'variableAssignedInTheBodyOfALoopComparedWithANumericStringLiteral' => [
                'code' => '<?php
                    $try = 0;
                    while (++$try < "10") {
                        $name = "thing";
                    }
                    echo $name;',
            ],
            'variableAssignedInTheBodyOfAFunctionLocalLoopOverAnInitialLiteral' => [
                'code' => '<?php
                    function f(): string {
                        $try = 0;
                        while (++$try < 10) {
                            $name = "thing";
                        }

                        return $name;
                    }',
            ],
            'variableAssignedInTheBodyOfAForLoopThatPreIncrementsAnInitialLiteral' => [
                'code' => '<?php
                    for ($i = 0; ++$i < 10;) {
                        $name = "thing";
                    }

                    echo $name;',
            ],
            'whileTrue' => [
                'code' => '<?php
                    function ret(): int {
                        do {
                            return 1;
                        } while (true);
                    }',
            ],
            'whileVar' => [
                'code' => '<?php
                    $worked = false;

                    while (rand(0,100) === 10) {
                        $worked = true;
                    }',
                'assertions' => [
                    '$worked' => 'bool',
                ],
            ],
            'objectValueWithTwoTypes' => [
                'code' => '<?php
                    class B {}
                    class A {
                        /** @var A|B */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : new B();
                        }
                    }

                    function makeA(): A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a instanceof A) {
                        $a = $a->parent;
                    }',
                'assertions' => [
                    '$a' => 'B',
                ],
            ],
            'objectValueWithInstanceofProperty' => [
                'code' => '<?php
                    class B {}
                    class A {
                        /** @var A|B */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : new B();
                        }
                    }

                    function makeA(): A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a->parent instanceof A) {
                        $a = $a->parent;
                    }

                    $b = $a->parent;',
                'assertions' => [
                    '$a' => 'A',
                    '$b' => 'A|B',
                ],
            ],
            'objectValueNullable' => [
                'code' => '<?php
                    class A {
                        /** @var ?A */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : null;
                        }
                    }

                    function makeA(): A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a) {
                        $a = $a->parent;
                    }',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'objectValueWithAnd' => [
                'code' => '<?php
                    class A {
                        /** @var ?A */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : null;
                        }
                    }

                    function makeA(): A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a && rand(0, 10) > 5) {
                        $a = $a->parent;
                    }',
                'assertions' => [
                    '$a' => 'A|null',
                ],
            ],
            'loopWithNoParadox' => [
                'code' => '<?php
                    $a = ["b", "c", "d"];
                    array_pop($a);
                    while ($a) {
                        $letter = array_pop($a);
                        if (!$a) {}
                    }',
            ],
            'noRedundantConditionInWhileAssignment' => [
                'code' => '<?php
                    class A {
                      /** @var ?int */
                      public $bar;
                    }

                    function foo(): ?A {
                      return rand(0, 1) ? new A : null;
                    }

                    while ($a = foo()) {
                      if ($a->bar !== null) {}
                    }',
            ],
            'whileTrueWithBreak' => [
                'code' => '<?php
                    while (true) {
                        $a = "hello";
                        break;
                    }
                    while (1) {
                        $b = 5;
                        break;
                    }',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'whileWithNotEmptyCheck' => [
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
                    while ($a) {
                      takesA($a);
                      $a = $a->a;
                    };',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'whileInstanceOf' => [
                'code' => '<?php
                    class A {
                        /** @var null|A */
                        public $parent;
                    }

                    class B extends A {}

                    $a = new A();

                    while ($a->parent instanceof B) {
                        $a = $a->parent;
                    }',
            ],
            'whileInstanceOfAndNotEmptyCheck' => [
                'code' => '<?php
                    class A {
                        /** @var null|A */
                        public $parent;
                    }

                    class B extends A {}

                    $a = (new A())->parent;

                    $foo = rand(0, 1) ? "hello" : null;

                    if (!$foo) {
                        while ($a instanceof B && !$foo) {
                            $a = $a->parent;
                            $foo = rand(0, 1) ? "hello" : null;
                        }
                    }',
            ],
            'noRedundantConditionAfterArrayAssignment' => [
                'code' => '<?php
                    $data = ["a" => false];
                    while (!$data["a"]) {
                        if (rand() % 2 > 0) {
                            $data = ["a" => true];
                        }
                    }',
            ],
            'additionSubtractionAssignment' => [
                'code' => '<?php
                    $a = 0;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = $a + 1;
                        } elseif ($a) {
                            $a = $a - 1;
                        }
                    }',
            ],
            'additionSubtractionInc' => [
                'code' => '<?php
                    $a = 0;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a++;
                        } elseif ($a) {
                            $a--;
                        }
                    }',
            ],
            'invalidateBothByRefAssignments' => [
                'code' => '<?php
                    function foo(?string &$i) : void {}
                    function bar(?string &$i) : void {}

                    $c = null;

                    while (rand(0, 1)) {
                        if ($c === null || $c === "" || $c === "0") {
                            foo($c);
                        } else {
                            bar($c);
                        }
                    }',
            ],
            'applyLoopConditionalAfterIf' => [
                'code' => '<?php
                    class Obj {}
                    class A extends Obj {
                        /** @var A|null */
                        public $foo;
                    }
                    class B extends Obj {}

                    function foo(Obj $node) : void {
                        while ($node instanceof A
                            || $node instanceof B
                        ) {
                            if (!$node instanceof B) {
                                $node = $node->foo;
                            }
                        }
                    }',
            ],
            'shouldBeFine' => [
                'code' => '<?php
                    class Obj {}
                    class A extends Obj {
                        /** @var A|null */
                        public $foo;
                    }
                    class B extends Obj {
                        /** @var A|null */
                        public $foo;
                    }
                    class C extends Obj {
                        /** @var A|C|null */
                        public $bar;
                    }

                    function takesA(A $a) : void {}

                    function foo(Obj $node) : void {
                        while ($node instanceof A
                            || $node instanceof B
                            || ($node instanceof C && $node->bar instanceof A)
                        ) {
                            if (!$node instanceof C) {
                                $node = $node->foo;
                            } else {
                                $node = $node->bar;
                            }
                        }
                    }',
            ],
            'comparisonAfterContinue' => [
                'code' => '<?php
                    $foo = null;
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $foo = 1;
                            continue;
                        }

                        $a = rand(0, 1);

                        if ($a === $foo) {}
                    }',
            ],
            'noRedundantConditionAfterWhile' => [
                'code' => '<?php
                    $i = 5;
                    while (--$i > 0) {}
                    echo $i === 0;',
            ],
            'noRedundantConditionOnAddedSubtractedInLoop' => [
                'code' => '<?php
                    $depth = 0;
                    $position = 0;
                    while (!$depth) {
                        if (rand(0, 1)) {
                            $depth++;
                        } elseif (rand(0, 1)) {
                            $depth--;
                        }
                        $position++;
                    }',
            ],
            'variableDefinedInWhileConditional' => [
                'code' => '<?php
                    function foo() : void {
                        $pointers = ["hi"];

                        while (rand(0, 1) && -1 < ($parent = 0)) {
                            print $pointers[$parent];
                        }
                    }',
            ],
            'assignedConditionallyReassignedToMixedInLoop' => [
                'code' => '<?php
                    function foo(array $arr): void {
                        while (rand(0, 1)) {
                            $t = true;
                            if (!empty($arr[0])) {
                                /** @psalm-suppress MixedAssignment */
                                $t = $arr[0];
                            }
                            if ($t === true) {}
                        }
                    }',
            ],
            'varChangedAfterUseInsideLoop' => [
                'code' => '<?php
                    function takesString(string $s) : void {}

                    /**
                     * @param array<string> $fields
                     */
                    function changeVarAfterUse(array $values, array $fields): void {
                        foreach ($fields as $field) {
                            if (!isset($values[$field])) {
                                continue;
                            }

                            /** @psalm-suppress MixedAssignment */
                            $value = $values[$field];

                            /** @psalm-suppress MixedArgument */
                            takesString($value);

                            $values[$field] = null;
                        }
                    }',
            ],
            'invalidateWhileAssertion' => [
                'code' => '<?php
                    function test(array $x, int $i) : void {
                        while (isset($x[$i]) && is_array($x[$i])) {
                            $i++;
                        }
                    }',
            ],
            'possiblyUndefinedInWhile' => [
                'code' => '<?php
                    function getRenderersForClass(string $a): void {
                        while ($b = getString($b ?? $a)) {
                            $c = "hello";
                        }
                    }

                    function getString(string $s) : ?string {
                        return rand(0, 1) ? $s : null;
                    }',
            ],
            'issetOfVariableUnsetInPreviousIteration' => [
                'code' => '<?php
                    class A {
                        public function noop(): void {
                            echo 1;
                        }
                    }

                    $a = new A();
                    while (true) {
                        if (!isset($a)) {
                            exit(0);
                        }
                        $a->noop();
                        unset($a);
                    }',
            ],
            'issetOfVariableUnsetAndReassignedInLoop' => [
                'code' => '<?php
                    class A {
                        public function noop(): void {
                            echo 1;
                        }
                    }

                    $a = new A();
                    while (rand(0, 1)) {
                        $a->noop();
                        unset($a);
                        $a = new A();
                    }
                    $b = $a;',
                'assertions' => ['$b' => 'A'],
            ],
            'thornyLoop' => [
                'code' => '<?php

                    function searchCode(string $content, array &$tmp) : void {
                        // separer les balises du texte
                        $tmp = [];
                        $reg = \'/(<[^>]+>)|([^<]+)+/isU\';

                        // pour chaque element trouve :
                        $str    = "";
                        $offset = 0;
                        while (preg_match($reg, $content, $parse, PREG_OFFSET_CAPTURE, $offset)) {
                            $str .= "hello";
                            unset($parse);
                        }
                    }',
            ],
            'assignToTKeyedArrayListPreserveListness' => [
                'code' => '<?php
                    /**
                     * @return non-empty-list<string>
                     */
                    function foo(string $key): array {
                        $elements = [$key];

                        while (rand(0, 1)) {
                            $elements[] = $key;
                        }

                        return $elements;
                    }',
            ],
            'reconcilePositiveInt' => [
                'code' => '<?php
                    $counter = 0;

                    while (rand(0, 1)) {
                        if ($counter > 0) {
                            $counter = $counter - 1;
                        } else {
                            $counter = $counter + 1;
                        }
                    }',
            ],
            'nonEmptyListIterationChangeVarWithContinue' => [
                'code' => '<?php
                    /** @param non-empty-list<int> $arr */
                    function foo(array $arr) : void {
                        while (array_shift($arr)) {
                            if ($arr && $arr[0] === "a") {}

                            if (rand(0, 1)) {
                                $arr = array_merge($arr, ["a"]);
                                continue;
                            }

                            echo "here";
                        }
                    }',
            ],
            'nonEmptyListIterationChangeVarWithoutContinue' => [
                'code' => '<?php
                    /** @param non-empty-list<int> $arr */
                    function foo(array $arr) : void {
                        while (array_shift($arr)) {
                            if ($arr && $arr[0] === "a") {}

                            if (rand(0, 1)) {
                                $arr = array_merge($arr, ["a"]);
                            }

                            echo "here";
                        }
                    }',
            ],
            'ifNestedInsideLoop' => [
                'code' => '<?php
                    function analyse(): int {
                        $state = 1;

                        while (rand(0, 1)) {
                            if ($state === 3) {
                                echo "here";
                            } elseif ($state === 2) {
                                if (rand(0, 1)) {
                                    $state = 3;
                                }
                            } else {
                                $state = 2;
                            }
                        }

                        return $state;
                    }',
            ],
            'ifNotNestedInsideLoop' => [
                'code' => '<?php
                    function analyse(): int {
                        $state = 1;

                        while (rand(0, 1)) {
                            if ($state === 3) {
                                echo "here";
                            } elseif ($state === 2) {
                                $state = 3;
                            } else {
                                $state = 2;
                            }
                        }

                        return $state;
                    }',
            ],
            'continueShouldAddToContext' => [
                'code' => '<?php
                    function foo() : void {
                        $link = null;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $link = "a";
                                continue;
                            }

                            if (rand(0, 1)) {
                                if ($link === null) {
                                   return;
                                }

                                continue;
                            }
                        }
                    }',
            ],
            'continue2Returns' => [
                'code' => '<?php
                    function foo(): array {
                        while (rand(0, 1)) {
                            while (rand(0, 1)) {
                                if (rand(0, 1)) {
                                    continue 2;
                                }

                                return [];
                            }
                        }

                        return [];
                    }',
            ],
            'propertyTypeUpdatedInBranch' => [
                'code' => '<?php
                    class A
                    {
                        public ?int $foo = null;

                        public function setFoo(): void
                        {
                            $this->foo = 5;
                        }
                    }

                    function bar(A $a): void {
                        $a->foo = null;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a->setFoo();
                            } elseif ($a->foo !== null) {}
                        }
                    }',
            ],
            'propertyTypeUpdatedInBranchWithBreak' => [
                'code' => '<?php
                    class A
                    {
                        public ?int $foo = null;

                        public function setFoo(): void
                        {
                            $this->foo = 5;
                        }
                    }

                    function bar(A $a): void {
                        $a->foo = null;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a->setFoo();
                            } elseif ($a->foo !== null) {
                                break;
                            }
                        }

                        if ($a->foo !== null) {}
                    }',
            ],
            'whileTrueDontHaveExitPathForReturn' => [
                'code' => '<?php
                    function getResultWithRetry(): string
                    {
                        while (new stdClass) {
                            return "";
                        }
                    }',
            ],
            'ComplexWhileTrueDontHaveExitPathForReturn' => [
                'code' => '<?php
                    class Test {
                        private int $retryAttempts = 10;

                        private function getResult(): string
                        {
                            // return string or throw exception whatever
                            throw new Exception();
                        }

                        private function getResultWithRetry(): string
                        {
                            $attempt = 1;

                            while (true) {
                                try {
                                    return $this->getResult();
                                } catch (Throwable $exception) {
                                    if ($attempt >= $this->retryAttempts) {
                                        throw $exception;
                                    }

                                    $attempt++;

                                    continue;
                                }
                            }
                        }
                    }',
            ],
            'continuingEducation' => [
                'code' => '<?php
                    function breakUpPathIntoParts(): void {
                        $b = false;

                        while (rand(0, 1)) {
                            if ($b) {
                                echo "hello";

                                continue;
                            }

                            $b = true;
                        }
                    }',
            ],
            'breakInWhileTrueIsNotInfiniteLoop' => [
                'code' => '<?php
                    /** @return Generator<array-key, mixed> */
                    function f()
                    {
                        if (rand(0,1)) {
                            throw new Exception;
                        }

                        while (true) {
                            yield 1;
                            break;
                        }
                    }',
            ],
            'whileLoopNeverEnteredKeepsThePreLoopValues' => [
                'code' => '<?php
                    $b = rand(0, 1) ? null : rand(0, 10);
                    while ($b) {
                        $b--;
                    }',
                'assertions' => [
                    '$b===' => '0|null',
                ],
            ],
            'whileLoopNeverEnteredKeepsThePreLoopFalsyStrings' => [
                'code' => '<?php
                    function getString(): ?string {
                        return rand(0, 1) ? null : (string) rand();
                    }

                    $s = getString();
                    while ($s) {
                        $s = null;
                    }',
                'assertions' => [
                    '$s===' => "''|'0'|null",
                ],
            ],
            'whileLoopWithAndConditionNeverEnteredKeepsThePreLoopValues' => [
                'code' => '<?php
                    $a = rand(0, 1) ? null : rand(-10, 10);
                    while ($a !== null && $a > 0) {
                        $a = rand(0, 1) ? null : $a - 1;
                    }',
                'assertions' => [
                    '$a===' => 'int<-10, 0>|null',
                ],
            ],
            'whileLoopNeverEnteredStillHasWhatItsConditionWrites' => [
                'code' => '<?php
                    /** @param-out int<1, 10> $a */
                    function nextValue(int &$a): bool {
                        $a = rand(1, 10);
                        return (bool) rand(0, 1);
                    }

                    $a = 0;
                    while (nextValue($a) || $a > 5) {}
                    $b = rand(0, 1) ? null : rand(0, 10);
                    while ($b && abs($b) < 20) {
                        $b--;
                    }',
                'assertions' => [
                    '$a===' => 'int<1, 5>',
                    '$b===' => 'int<0, 10>|null',
                ],
            ],
            'loopLeftWithWhatItsConditionWroteNotWhatTheBodyLeft' => [
                'code' => '<?php
                    /** @param-out int<1, 10> $a */
                    function nextValue(int &$a): bool {
                        $a = rand(1, 10);
                        return (bool) rand(0, 1);
                    }

                    /** @return int<0, 10> */
                    function g(): int {
                        return rand(0, 10);
                    }

                    final class K {
                        public int $p = 0;

                        /** @return int<0, 5> */
                        public function f(): int {
                            $this->p = 0;
                            while (($this->p = g()) > 5) {}
                            return $this->p;
                        }
                    }

                    $a = 20;
                    for (; nextValue($a), $a > 5;) {}
                    $b = 20;
                    while (nextValue($b) || $b > 5) {
                        $b = 100;
                    }',
                'assertions' => [
                    '$a===' => 'int<1, 5>',
                    '$b===' => 'int<1, 5>',
                ],
            ],
            'whileLoopAlwaysEnteredGetsTheNegatedCondition' => [
                'code' => '<?php
                    $a = null;
                    while ($a === null) {
                        $a = rand(0, 1) ? 1 : null;
                    }',
                'assertions' => [
                    '$a===' => '1',
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
            'whileTrueWidenedIntRangeKeepsTheBoundItDoesNotShift' => [
                'code' => '<?php
                    function f(): int {
                        $i = 0;
                        while (true) {
                            $i += rand(1, 3);
                            if ($i < 0) {
                                break;
                            }
                        }
                        return $i;
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:6:33 - Type int<1, max> for $i is never < 0',
            ],
            'variableDefinedOnlyBeforeAContinueIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(): void {
                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                echo $x;
                            }
                            if (rand(0, 1)) {
                                $x = 1;
                                continue;
                            }
                        }
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'whileTrueVariableUndefinedAtABreakIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(): void {
                        while (true) {
                            if (rand(0, 1)) {
                                break;
                            }
                            $x = 1;
                            if (rand(0, 1)) {
                                break;
                            }
                        }
                        echo $x;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'whileTrueVariableUnsetAtABreakIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(): void {
                        $x = 1;
                        while (true) {
                            if (rand(0, 1)) {
                                unset($x);
                                break;
                            }
                            if (rand(0, 1)) {
                                break;
                            }
                        }
                        echo $x;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'whileTrueVariableUnsetAtEveryBreakIsReported' => [
                'code' => '<?php
                    function f(): void {
                        $x = 1;
                        while (true) {
                            unset($x);
                            break;
                        }
                        echo $x;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'whileTrueKeepsAPropertyNarrowedAtOnlyOneBreakNullable' => [
                'code' => '<?php
                    final class Service {
                        public function run(): void {}
                    }

                    final class Box {
                        public ?Service $service = null;
                    }

                    function f(Box $box): void {
                        while (true) {
                            if (rand(0, 1)) {
                                if ($box->service !== null) {
                                    break;
                                }
                                continue;
                            }
                            break;
                        }
                        $box->service->run();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'whileBreakBeforeDefinition' => [
                'code' => <<<'PHP'
                    <?php
                    function f(): void {
                        while (rand()) {
                            if (rand()) {
                                $y = 1;
                                break;
                            }

                            if (rand()) {
                                break;
                            }
                        }

                        echo $y;
                    }
                    PHP,
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:14:10',
                'error_levels' => [],
                'php_version' => '8.1',
            ],
            'variableAssignedOnlyInTheSkippableOperandOfTheConditionIsPossiblyUndefined' => [
                'code' => '<?php
                    while (rand(0, 1) && ($value = rand())) {
                        echo "again";
                    }

                    echo $value;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInTheRightOperandOfAnAndInTheWhileConditionIsPossiblyUndefined' => [
                'code' => '<?php
                    function next_int(): int {
                        return 1;
                    }

                    function f(): int {
                        while (rand(0, 1) && $value = next_int()) {
                            echo 1;
                        }

                        return $value;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedOnlyInTheWhileBodyIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(): int {
                        while (rand(0, 1)) {
                            $value = 1;
                        }

                        return $value;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInALoopThatPostIncrementsAnInitialLiteralIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 0;
                    while ($try++ < 10) {
                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopWhoseFirstPreIncrementedValueFailsTheConditionIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 9;
                    while (++$try < 10) {
                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopWhoseConditionFailsForTheInitialLiteralIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 10;
                    while ($try < 10) {
                        $name = "thing";
                        $try++;
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopWhoseConditionFailsForTheInitialLiteralWithPreDecrementIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 1;
                    while (--$try > 0) {
                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopOverAnUnknownStartIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(int $try): void {
                        while (++$try < 10) {
                            $name = "thing";
                        }
                        echo $name;
                    }
                    f(0);',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInALoopOverARangeStartIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = rand(0, 5);
                    while (++$try < 10) {
                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopOverAFloatStartIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 0.0;
                    while (++$try < 10) {
                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedAfterABreakInALoopOverAnInitialLiteralIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 0;
                    while (++$try < 10) {
                        if (rand(0, 1)) {
                            break;
                        }

                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedAfterAContinueInALoopOverAnInitialLiteralIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 0;
                    while (++$try < 10) {
                        if (rand(0, 1)) {
                            continue;
                        }

                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopComparedWithANonNumericStringLiteralIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 0;
                    while ($try < "abc") {
                        $name = "thing";
                        $try++;
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopWithACompoundConditionIsPossiblyUndefined' => [
                'code' => '<?php
                    $try = 0;
                    while (++$try < 10 && rand(0, 1)) {
                        $name = "thing";
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'variableAssignedInALoopComparedWithAnUnknownValueIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(int $limit): void {
                        $try = 0;
                        while (++$try < $limit) {
                            $name = "thing";
                        }
                        echo $name;
                    }
                    f(10);',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInALoopThatChangesTheCounterBeforeTheNextCheckIsStillOnlyAssignedInTheBody' => [
                'code' => '<?php
                    $try = 0;
                    while (++$try < 10) {
                        if ($try > 3) {
                            $name = "thing";
                        }
                    }
                    echo $name;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'whileTrueNoBreak' => [
                'code' => '<?php
                    while (true) {
                        $a = "hello";
                    }

                    echo $a;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'invalidateByRefAssignmentWithRedundantCondition' => [
                'code' => '<?php
                    function foo(?string $i) : void {}
                    function bar(?string $i) : void {}

                    $c = null;

                    while (rand(0, 1)) {
                        if (!$c) {
                            foo($c);
                        } else {
                            bar($c);
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
        ];
    }
}
