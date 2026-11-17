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

final class AssignmentInConditionalTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testAPossiblyUndefinedVariableIsSetWhereItIsNotNull(): void
    {
        $this->project_analyzer->getConfig()->throw_exception = false;

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                final class Item {
                    public function name(): string {
                        return "i";
                    }
                }

                function f(bool $first): string {
                    if ($first) {
                        $item = new Item();
                    }

                    if ($item === null) {
                        echo $item;
                        return "";
                    }

                    return $item->name();
                }',
        );

        $this->analyzeFile($file_path, new Context());

        self::assertSame(
            [
                'PossiblyUndefinedVariable 13:25',
                'PossiblyUndefinedVariable 14:30',
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
            'variableAssignedBeforeABreakInTheElseIsNotTypedByTheIf' => [
                'code' => '<?php
                    /** @return list<string> */
                    function getList(): array {
                        return [];
                    }

                    if (rand(0, 1)) {
                        $found = 1;
                    } else {
                        foreach (getList() as $item) {
                            $found = $item;
                            break;
                        }
                    }

                    $b = $found ?? null;',
                'assertions' => [
                    '$b===' => 'mixed|null',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'variableAssignedInACatchInTheElseIsNotTypedByTheIf' => [
                'code' => '<?php
                    function load(): void {
                        if (rand(0, 1) === 1) {
                            throw new RuntimeException("x");
                        }
                    }

                    if (rand(0, 1)) {
                        $searched = 1;
                    } else {
                        try {
                            load();
                        } catch (Exception $e) {
                            $searched = "";
                        }
                    }

                    $b = $searched ?? null;',
                'assertions' => [
                    '$b===' => 'mixed|null',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'variableDefinedInSomeBranchesKeepsItsTypeWhenPossiblyUndefined' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        $a = 1;
                    } elseif (rand(0, 1)) {
                        $a = "b";
                    }

                    $b = $a;',
                'assertions' => [
                    '$b===' => "'b'|1|null",
                ],
                'ignored_issues' => ['PossiblyUndefinedGlobalVariable'],
            ],
            'variableAssignedInAnElseifConditionKeepsTheValuesOfThePathsSkippingTheBranch' => [
                'code' => '<?php
                    function f(bool $skip, bool $value, bool $gate): void {
                        if ($skip) {
                        } elseif (($a = $value) && $gate) {
                        }

                        if (isset($a) && $a === false) {
                            echo "false";
                        }
                    }',
            ],
            'globalBoundToAVariableSomeBranchesDefineIsNullWhenUnset' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        $g = 1;
                    }

                    final class C {
                        public function m(): ?int {
                            global $g;
                            /** @psalm-check-type-exact $g = 1|null */;
                            return $g;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'possiblyUndefinedVariableIsNullWhereItIsFalsy' => [
                'code' => '<?php
                    function f(bool $flag): void {
                        if ($flag) {
                            $a = "s";
                        }

                        if (!$a) {
                            /** @psalm-check-type-exact $a? = null */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['PossiblyUndefinedVariable'],
            ],
            'variableAssignedInTheConditionKeepsTheValuesOfThePathsSkippingTheBranch' => [
                'code' => '<?php
                    function f(string $s): void {
                        if ($s !== "" && ($pos = strpos($s, "x"))) {
                        }

                        if (isset($pos) && $pos === 0) {
                            echo "first";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'conditionallyCreatedObjectStaysFreshWhereItIsNotNull' => [
                'code' => '<?php
                    final class Box {
                        public int $v = 0;
                    }

                    /** @psalm-pure */
                    function f(bool $flag): int {
                        if ($flag) {
                            $box = new Box();
                        }

                        /** @psalm-suppress PossiblyUndefinedVariable */
                        if ($box !== null) {
                            $box->v = 1;
                            return $box->v;
                        }

                        return 0;
                    }',
            ],
            'passingAPossiblyUndefinedVariableByReferenceDefinesIt' => [
                'code' => '<?php
                    function f(bool $flag, string $s): int {
                        if ($flag) {
                            $m = [];
                        }

                        preg_match("/x/", $s, $m);

                        return count($m);
                    }',
            ],
            'negatedAssignedVariableIsTruthyOnTheRightOfOr' => [
                'code' => '<?php
                    function takesObject(object $o): bool {
                        return true;
                    }

                    function check(?object $q): bool {
                        return !($proxy = $q) || takesObject($proxy);
                    }',
            ],
            'assignmentInTheMatchArmsOfTheValueOfAnOuterAssignmentOfTheSameVariable' => [
                'code' => '<?php
                    interface ViewFactory {
                        public function exists(string $view): bool;
                    }

                    function guess(ViewFactory $views, string $component): ?string {
                        foreach ([1, 2] as $_) {
                            try {
                                if (! is_null($guess = match (true) {
                                    $views->exists($guess = "a" . $component) => $guess,
                                    $views->exists($guess = "b" . $component) => $guess,
                                    default => null,
                                })) {
                                    return $guess;
                                }
                            } catch (InvalidArgumentException) {
                            }
                        }

                        return null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assignmentInTheOffsetOfAnIssetInATryIsDefined' => [
                'code' => '<?php
                    function keyOf(string $t): string {
                        return $t;
                    }

                    /** @param list<string> $templates */
                    function warm(array $templates): void {
                        $seen = [];

                        foreach ($templates as $t) {
                            try {
                                if (!isset($seen[$k = keyOf($t)])) {
                                    $seen[$k] = true;
                                }
                            } catch (Error) {
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assignmentInTheOffsetOfAnIssetKeepsTheTypeAfterIt' => [
                'code' => '<?php
                    function connectionName(UnitEnum|string|null $name): string {
                        return $name instanceof UnitEnum ? $name->name : (string) $name;
                    }

                    function defaultName(): string {
                        return "default";
                    }

                    /** @param array<string, int> $connections */
                    function connection(array $connections, UnitEnum|string|null $name = null): int {
                        if (isset($connections[$name = connectionName($name) ?: defaultName()])) {
                            return $connections[$name];
                        }

                        return 0;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.1',
            ],
            'assignmentInAPlainIfKeepsTheTypeAfterIt' => [
                'code' => '<?php
                    /** @return (callable(int): bool)|null */
                    function handler() {
                        return null;
                    }

                    if ($handler = handler()) {
                        if (\\is_array($handler)) {
                            echo "array";
                        }
                    }',
                'assertions' => [
                    '$handler===' => 'callable[impure](int):bool|null',
                ],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.3',
            ],
            'assignmentInTheRightOperandOfAndIsEitherTypeInTheElse' => [
                'code' => '<?php
                    function fetch(): ?string {
                        return rand(0, 1) ? "a" : null;
                    }

                    function check(bool $a): void {
                        $v = new stdClass();

                        if ($a && ($v = fetch())) {
                            echo "found";
                        } else {
                            /** @psalm-check-type-exact $v = null|stdClass|string */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentInTheRightOperandOfAndIsEitherTypeInTheElseOfAnElseif' => [
                'code' => '<?php
                    function fetch(): ?string {
                        return rand(0, 1) ? "a" : null;
                    }

                    function check(bool $a, bool $b): void {
                        $v = new stdClass();

                        if ($b) {
                            echo "b";
                        } elseif ($a && ($v = fetch())) {
                            /** @psalm-check-type-exact $v = non-falsy-string */;
                        } else {
                            /** @psalm-check-type-exact $v = null|stdClass|string */;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentInTheRightOperandOfAndInAWhileIsNarrowedInTheBody' => [
                'code' => '<?php
                    function fetch(): ?string {
                        return rand(0, 1) ? "a" : null;
                    }

                    function check(bool $a): void {
                        $v = new stdClass();

                        while ($a && ($v = fetch())) {
                            /** @psalm-check-type-exact $v = non-falsy-string */;
                            $a = false;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentInTheRightOperandOfAnAndInAGuardClauseIsSetAfterIt' => [
                'code' => '<?php
                    function fetch(): ?string {
                        return rand(0, 1) ? "a" : null;
                    }

                    function check(bool $a): void {
                        $v = new stdClass();

                        if (!$a || !($v = fetch())) {
                            return;
                        }

                        /** @psalm-check-type-exact $v = non-falsy-string */;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentComparedWithTrueIsNarrowedInTheBody' => [
                'code' => '<?php
                    function label(): string {
                        return rand(0, 1) ? "a" : "";
                    }

                    function check(): int {
                        $value = new stdClass();

                        if (($value = label()) == true) {
                            /** @psalm-check-type-exact $value = non-falsy-string */;
                            return strlen($value);
                        }

                        return 0;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentInTheRightOperandOfAndInAnIfKeepsTheEarlierType' => [
                'code' => '<?php
                    function fetch(): ?float {
                        return rand(0, 1) ? 1.5 : null;
                    }

                    function check(bool $enabled): void {
                        $value = 0;
                        if ($enabled && null !== ($value = fetch())) {
                            echo "found";
                        }

                        /** @psalm-check-type-exact $value = 0|float|null */;
                    }',
            ],
            'assignmentInTheRightOperandOfAndInAnIfIsNarrowedInTheBody' => [
                'code' => '<?php
                    function fetch(): ?float {
                        return rand(0, 1) ? 1.5 : null;
                    }

                    function check(bool $enabled): void {
                        $value = 0;
                        if ($enabled && null !== ($value = fetch())) {
                            /** @psalm-check-type-exact $value = float */;
                        }
                    }',
            ],
            'assignmentInTheRightOperandOfAnAndInsideAnOrIsNotNullInTheBody' => [
                'code' => '<?php
                    interface Argument {
                        public function name(): string;
                    }

                    final class Tagged implements Argument {
                        public function name(): string {
                            return "tagged";
                        }
                    }

                    final class Locator implements Argument {
                        public function name(): string {
                            return "locator";
                        }

                        public function getTagged(): ?Tagged {
                            return null;
                        }
                    }

                    function dump(Argument $value): string {
                        $tag = $value;

                        if ($value instanceof Tagged
                            || ($value instanceof Locator && ($tag = $value->getTagged()))
                        ) {
                            return $tag->name();
                        }

                        return "";
                    }',
            ],
            'assignmentInBothOperandsOfOrInAnIfIsEitherOfThem' => [
                'code' => '<?php
                    function getPath(): ?string {
                        return rand(0, 1) ? "a" : null;
                    }

                    function foo(?string $s): string {
                        if (($path = $s) || ($path = getPath())) {
                            /** @psalm-check-type-exact $path = non-falsy-string */
                            return $path;
                        }

                        return "b";
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentInTheRightOperandOfAndInAnIfWithAFloatPreviously' => [
                'code' => '<?php
                    class Check {
                        private bool $isTrue = false;
                        private ?float $value = 0.0;

                        public function isTrue(): bool {
                            return $this->isTrue;
                        }

                        public function getValue(): ?float {
                            return $this->value;
                        }
                    }

                    function run(Check $check): void {
                        $bar = .0;

                        if ($check->isTrue() && null === ($bar = $check->getValue())) {
                            echo "condition match";
                        }

                        if ($bar) {
                            echo "condition 2 match";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'binaryComparisonAssignedInConditional' => [
                'code' => '<?php
                    function f(string $s): void {
                        if ($b = ($s === "\xFF\xFE")) {}
                    }',
            ],
            'negatedBinaryComparisonAssignedInConditional' => [
                'code' => '<?php
                    function f(string $s): void {
                        if (!($b = ($s === "\xFF\xFE"))) {}
                    }',
            ],
            'orWithAssignment' => [
                'code' => '<?php
                    function maybeString(): ?string {
                        return rand(0, 10) > 4 ? "test" : null;
                    }

                    function test(): string {
                        $foo = maybeString();
                        ($foo !== null) || ($foo = "");

                        return $foo;
                    }',
            ],
            'andWithAssignment' => [
                'code' => '<?php
                    function maybeString(): ?string {
                        return rand(0, 10) > 4 ? "test" : null;
                    }

                    function test(): string {
                        $foo = maybeString();
                        ($foo === null) && ($foo = "");

                        return $foo;
                    }',
            ],
            'assertHardConditionalWithString' => [
                'code' => '<?php
                    interface Converter {
                        function maybeConvert(string $value): ?SomeObject;
                    }

                    interface SomeObject {
                        function isValid(): bool;
                    }

                    function exampleWithOr(Converter $converter, string $value): SomeObject {
                        if (($value = $converter->maybeConvert($value)) === null || !$value->isValid()) {
                            throw new Exception();
                        }

                        return $value; // $value is SomeObject here and cannot be a string
                    }',
            ],
            'assertOnRemainderOfArray' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedReturnStatement
                     */
                    function foo(string $file_name) : int|float|string {
                        while ($data = getData()) {
                            if (is_numeric($data[0])) {
                                for ($i = 1; $i < count($data); $i++) {
                                    return $data[$i];
                                }
                            }
                        }

                        return 5;
                    }

                    function getData() : ?array {
                        return rand(0, 1) ? ["a", "b", "c"] : null;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assertVarRedefinedInIfWithExtraIf' => [
                'code' => '<?php
                    class O {}

                    /**
                     * @param mixed $value
                     */
                    function exampleWithOr($value): O {
                        if (!is_string($value)) {
                            return new O();
                        }

                        if (($value = rand(0, 1) ? new O : null) === null) {
                            return new O();
                        }

                        return $value;
                    }',
            ],
            'SKIPPED-assertVarRedefinedInOpWithAnd' => [ // #13921
                'code' => '<?php
                    class O {
                        public function foo() : bool { return true; }
                    }

                    /** @var mixed */
                    $value = $_GET["foo"];

                    $a = is_string($value) && (($value = rand(0, 1) ? new O : null) !== null) && $value->foo();',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'assertVarRedefinedInOpWithOr' => [
                'code' => '<?php
                    class O {
                        public function foo() : bool { return true; }
                    }

                    /** @var mixed */
                    $value = $_GET["foo"];

                    $a = !is_string($value) || (($value = rand(0, 1) ? new O : null) === null) || $value->foo();',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'assertVarInOrAfterAnd' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?A $b): void {
                        $c = ($a instanceof B && $b instanceof B) || ($a instanceof C && $b instanceof C);
                    }',
            ],
            'assertAssertionsWithCreation' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function getA(A $a): ?A {
                        return rand(0, 1) ? $a : null;
                    }

                    function foo(?A $a, ?A $c): void {
                        $c = $a && ($b = getA($a)) && $c ? 1 : 0;
                    }',
            ],
            'definedInBothBranchesOfConditional' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function getA(): ?A {
                        return rand(0, 1) ? new A() : null;
                    }

                    function foo(): void {
                        $a = null;
                        if (($a = getA()) || ($a = getA())) {
                            $a->foo();
                        }
                    }',
            ],
            'definedInConditionalAndCheckedInSubbranch' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function getA(): ?A {
                        return rand(0, 1) ? new A() : null;
                    }

                    function foo(): void {
                        if (($a = getA()) || rand(0, 1)) {
                            if ($a) {
                                $a->foo();
                            }
                        }
                    }',
            ],
            'definedInRhsOfConditionalInNegation' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function getA(): ?A {
                        return rand(0, 1) ? new A() : null;
                    }

                    function foo(): void {
                        if (rand(0, 1) && ($a = getA()) !== null) {
                            $a->foo();
                        }
                    }',
            ],
            'definedInOrRHS' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function getA(): ?A {
                        return rand(0, 1) ? new A() : null;
                    }

                    function foo(bool $b): void {
                        $a = null;
                        if (!$b || !($a = getA())) {
                            return;
                        }
                        $a->foo();
                    }',
            ],
            'possiblyDefinedVarInAssertion' => [
                'code' => '<?php
                    class A {
                        public function test() : bool { return true; }
                    }

                    function getMaybeA() : ?A { return rand(0, 1) ? new A : null; }

                    function foo() : void {
                        if (rand(0, 10) && ($a = getMaybeA()) && !$a->test()) {
                            return;
                        }

                        echo isset($a);
                    }',
            ],
            'applyTruthyAssertionsToRightHandSideOfAssignment' => [
                'code' => '<?php
                    function takesAString(string $name): void {}

                    function randomReturn(): ?string {
                        return rand(1,2) === 1 ? "foo" : null;
                    }

                    $name = randomReturn();

                    if ($foo = ($name !== null)) {
                        takesAString($name);
                    }',
            ],
            'maintainTruthinessInsideAssignment' => [
                'code' => '<?php
                    class C {
                        public function foo() : void {}
                    }

                    class B {
                        public ?C $c = null;
                    }

                    function updateBackgroundClip(?B $b): void {
                        if (!$b || !($a = $b->c)) {
                            // do something
                        } else {
                            $a->foo();
                        }
                    }',
            ],
            'allowBasicOrAssignment' => [
                'code' => '<?php
                    function test(): int {
                        if (rand(0, 1) || ($a = rand(0, 10)) === 0) {
                            return 0;
                        }

                        return $a;
                    }

                    function test2(?string $comment): ?string {
                        if ($comment === null || preg_match("/.*/", $comment, $match) === 0) {
                            return null;
                        }

                        return $match[0];
                    }',
            ],
            'noParadoxicalConditionAfterTwoAssignments' => [
                'code' => '<?php
                    function foo(string $str): ?int {
                        if (rand(0, 1) || (!($pos = strpos($str, "a")) && !($pos = strpos($str, "b")))) {
                            return null;
                        }

                        return $pos;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
            ],
            'assignmentInIf' => [
                'code' => '<?php
                    if ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }',
            ],
            'negatedAssignmentInIf' => [
                'code' => '<?php
                    if (!($row = (rand(0, 10) ? [5] : null))) {
                        // do nothing
                    }
                    else {
                        echo $row[0];
                    }',
            ],
            'assignInElseIf' => [
                'code' => '<?php
                    if (rand(0, 10) > 5) {
                        echo "hello";
                    } elseif ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }',
            ],
            'ifNotEqualsFalse' => [
                'code' => '<?php
                    if (($row = rand(0,10) ? [1] : false) !== false) {
                       echo $row[0];
                    }',
            ],
            'ifNotEqualsNull' => [
                'code' => '<?php
                    if (($row = rand(0,10) ? [1] : null) !== null) {
                       echo $row[0];
                    }',
            ],
            'ifNullNotEquals' => [
                'code' => '<?php
                    if (null !== ($row = rand(0,10) ? [1] : null)) {
                       echo $row[0];
                    }',
            ],
            'ifNullEquals' => [
                'code' => '<?php
                    if (null === ($row = rand(0,10) ? [1] : null)) {

                    } else {
                        echo $row[0];
                    }',
            ],
            'passedByRefInIf' => [
                'code' => '<?php
                    if (preg_match("/bad/", "badger", $matches)) {
                        echo $matches[0];
                    }',
            ],
            'passByRefInIfCheckAfter' => [
                'code' => '<?php
                    if (!preg_match("/bad/", "badger", $matches)) {
                        exit();
                    }
                    echo $matches[0];',
            ],
            'passByRefInIfWithBoolean' => [
                'code' => '<?php
                    $a = (bool)rand(0, 1);
                    if ($a && preg_match("/bad/", "badger", $matches)) {
                        echo $matches[0];
                    }',
            ],
            'bleedElseifAssignedVarsIntoElseScope' => [
                'code' => '<?php
                    if (rand(0, 1) === 0) {
                        $foo = 0;
                    } elseif ($foo = rand(0, 10)) {}

                    echo substr("banana", $foo);',
            ],
            'repeatedSet' => [
                'code' => '<?php
                    function foo(): void {
                        if ($a = rand(0, 1) ? "1" : null) {
                            return;
                        }

                        if (rand(0, 1)) {
                            $a = rand(0, 1) ? "hello" : null;

                            if ($a) {

                            }
                        }
                    }',
            ],
            'repeatedSetInsideWhile' => [
                'code' => '<?php
                    function foo(): void {
                        if ($a = rand(0, 1) ? "1" : null) {
                            return;
                        } else {
                            while (rand(0, 1)) {
                                $a = rand(0, 1) ? "hello" : null;
                            }

                            if ($a) {

                            }
                        }
                    }',
            ],
            'propertyFetchAfterNotNullCheckInElseif' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    if (rand(0, 10) > 5) {
                    } elseif (($a = rand(0, 1) ? new A : null) && is_string($a->foo)) {}',
            ],
            'noParadoxAfterConditionalAssignment' => [
                'code' => '<?php
                    if ($a = rand(0, 5)) {
                        echo $a;
                    } elseif ($a = rand(0, 5)) {
                        echo $a;
                    }',
            ],
            'assignmentInBranchWithReference' => [
                'code' => '<?php
                    class A {}

                    function getAOrFalse(bool $b) : A|false {
                        return false;
                    }

                    function foo(A|false $a): void
                    {
                        if ($a instanceof A
                            || ($a = getAOrFalse($a))
                        ) {
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'assignmentForComparison' => [
                'code' => '<?php
                    function foo(int $b): void {
                        if ($a = $b > 1) {}
                        if ($a) {}
                    }',
            ],
            'assignmentInRightOperandOfAndOfAnElseifOrALogicalAndKeepsThePreviousValueWhereTheLeftOneIsFalse' => [
                'code' => '<?php
                    function fresh(): ?string {
                        return null;
                    }

                    function elseifBranch(bool $flag, ?string $name): void {
                        if ($flag) {
                            echo "flag";
                        } elseif (null === $name && null !== ($name = fresh())) {
                            echo "assigned";
                        } else {
                            /** @psalm-check-type-exact $name = null|string */
                            echo "else";
                        }
                    }

                    function logicalAnd(?string $name): void {
                        if (null === $name and null !== ($name = fresh())) {
                            return;
                        }

                        /** @psalm-check-type-exact $name = null|string */
                        echo "after";
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantCondition'],
                'php_version' => '8.1',
            ],
            'assignmentInRightOperandOfAndKeepsThePreviousValueWhereTheLeftOneIsFalse' => [
                'code' => '<?php
                    function fresh(): ?string {
                        return null;
                    }

                    function elseBranch(?string $name): void {
                        if (null === $name && null !== $name = fresh()) {
                            echo "assigned";
                        } else {
                            /** @psalm-check-type-exact $name = null|string */
                            echo "else";
                        }
                    }

                    function afterIf(?string $name): void {
                        if (null === $name && null !== $name = fresh()) {
                            return;
                        }

                        /** @psalm-check-type-exact $name = null|string */
                        echo "after";
                    }

                    function ternary(?string $name): string {
                        $result = null === $name && null !== ($name = fresh()) ? "assigned" : $name;
                        /** @psalm-check-type-exact $name = null|string */

                        return (string) $result;
                    }',
                'assertions' => [],
                'ignored_issues' => ['RedundantCondition'],
                'php_version' => '8.1',
            ],
            'assignmentInRightOperandOfAndForgetsWhatTheLeftOneTestedOfThePreviousValue' => [
                'code' => '<?php
                    function fresh(): ?string {
                        return null;
                    }

                    function f(?string $name): string {
                        if (null === $name && null !== $name = fresh()) {
                            return $name;
                        }

                        return "none";
                    }

                    function g(?string $name): int {
                        return $name !== null && ($name = trim($name)) !== "" ? strlen($name) : 0;
                    }

                    function other(): ?array {
                        return null;
                    }

                    /** @param array{k?: int}|null $a */
                    function h(?array $a): int {
                        return isset($a["k"]) && ($a = other()) !== null ? count($a) : 0;
                    }',
            ],
            'elseifConditionAssigningAMixedVariableInALoopDefinesItInTheElse' => [
                'code' => '<?php
                    /**
                     * @param list<bool> $l
                     * @param mixed $inc
                     */
                    function f(array $l, $inc): void {
                        foreach ($l as $a) {
                            if ($a) {
                                $x = 1;
                            } elseif (is_array($x = $inc)) {
                                $x = 2;
                            }
                            echo is_int($x) ? "int" : "";
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'elseifConditionAssigningAVariableWithTheTypeItHadDefinesItInTheElse' => [
                'code' => '<?php
                    function check(int $i): bool {
                        return $i > 0;
                    }

                    if (rand(0, 1)) {
                        $x = rand(0, 1);
                    }

                    if (rand(0, 1)) {
                        $x = rand(0, 1);
                    } elseif (check($x = rand(0, 1))) {
                        $x = rand(0, 1);
                    }

                    $y = $x;',
                'assertions' => [
                    '$y===' => 'int<0, 1>',
                ],
            ],
            'variableAssignedInCallArgumentOfSecondAndOperandIsSetInThird' => [
                'code' => '<?php
                    function isYmlFile(?string $relativePath): bool {
                        return is_string($relativePath)
                            && file_exists($absolute = "/home/user123/" . $relativePath)
                            && pathinfo($absolute, PATHINFO_EXTENSION) === "yml";
                    }',
            ],
            'variableAssignedByReferenceInSecondAndOperandIsSetInThird' => [
                'code' => '<?php
                    function f(bool $b, string $s): bool {
                        return $b && preg_match("/a/", $s, $m) && $m[0] === "a";
                    }',
            ],
            'variableAssignedInFirstAndOperandIsSetInThird' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, string $s): bool {
                        return check($x = $s) && $b && $x === "a";
                    }',
            ],
            'variableAssignedInAndOperandIsSetInEveryLaterOne' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, bool $c, string $s): bool {
                        return $b && check($x = $s) && $c && $x === "a";
                    }',
            ],
            'variableReassignedInSecondAndOperandHasItsNewTypeInThird' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b): bool {
                        $x = null;
                        return $b && check($x = "a") && strlen($x) > 0;
                    }',
            ],
            'variableAssignedInParenthesizedAndOperandIsSetInNextOne' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, bool $c, string $s): bool {
                        return $b && ($c && check($x = $s)) && $x === "a";
                    }

                    function g(bool $b, bool $c, string $s): bool {
                        return ($b && check($x = $s) && $c) && $x === "a";
                    }',
            ],
            'variableAssignedInLogicalAndOperandIsSetInNextOne' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, string $s): bool {
                        return ($b and check($x = $s)) and $x === "a";
                    }',
            ],
            'variableAssignedInAndOperandIsNarrowedByItsAssertionInNextOne' => [
                'code' => '<?php
                    function f(bool $b, int|string $s): bool {
                        return $b && is_string($x = $s) && strlen($x) > 0;
                    }

                    function g(int|string $s, bool $b): bool {
                        return is_string($x = $s) && $b && strlen($x) > 0;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
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
            'elseifConditionAssignmentIsNotMadeInTheBranchesBeforeIt' => [
                'code' => '<?php
                    function check(int $i): bool {
                        return $i > 0;
                    }

                    function f(bool $a, bool $b): void {
                        if (rand(0, 1)) {
                            $x = rand(0, 1);
                        }

                        if ($a) {
                            $x = rand(0, 1);
                        } elseif ($b) {
                        } elseif (check($x = rand(0, 1))) {
                        }

                        echo $x;
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:17:30',
            ],
            'variableOnlyAnIssetInABranchMentionsIsUndefined' => [
                'code' => '<?php
                    if (rand(0, 1)) {
                        if (isset($missing)) {}
                    }

                    $copy = $missing;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'variableAnExtractInABranchDefinesIsPossiblyUndefined' => [
                'code' => '<?php
                    function f(bool $flag): void {
                        if ($flag) {
                            extract(["a" => 1]);
                        }

                        echo $a;
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:30',
            ],
            'possiblyUndefinedVariableReadInACastIsReported' => [
                'code' => '<?php
                    function f(bool $flag): string {
                        if ($flag) {
                            $a = 1;
                        }

                        $b = (string) $a;

                        return $b;
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:39',
            ],
            'assignedVariableIsFalsyOnTheRightOfOr' => [
                'code' => '<?php
                    function takesObject(object $o): bool {
                        return true;
                    }

                    function check(?object $q): bool {
                        return ($proxy = $q) || takesObject($proxy);
                    }',
                'error_message' => 'NullArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:61',
            ],
            'assignmentInABranchOfATernaryInAnIfMayBeSkipped' => [
                'code' => '<?php
                    function label(): string {
                        return rand(0, 1) ? "a" : "";
                    }

                    function check(bool $flag, bool $other): int {
                        $value = new stdClass();

                        if ($flag ? ($value = label()) : $other) {
                            return strlen($value);
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentInATernaryComparedWithTrueMayBeSkipped' => [
                'code' => '<?php
                    function label(): string {
                        return rand(0, 1) ? "a" : "";
                    }

                    function check(bool $flag, bool $other): int {
                        $value = new stdClass();

                        if (($flag ? ($value = label()) : $other) == true) {
                            return strlen($value);
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'assignmentInAnAndInsideACastMayBeSkipped' => [
                'code' => '<?php
                    function label(): string {
                        return rand(0, 1) ? "a" : "";
                    }

                    function check(bool $flag): int {
                        $value = new stdClass();

                        if ((int) ($flag && ($value = label())) === 1) {
                            return strlen($value);
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'assignmentInTheRightOfACoalesceInsideAComparisonMayBeSkipped' => [
                'code' => '<?php
                    function label(): string {
                        return rand(0, 1) ? "a" : "";
                    }

                    function check(?string $name): int {
                        $value = new stdClass();

                        if (($name ?? ($value = label())) !== "q") {
                            return strlen($value);
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'assignmentInAMatchArmMayBeSkipped' => [
                'code' => '<?php
                    function label(): string {
                        return rand(0, 1) ? "a" : "";
                    }

                    function check(bool $flag): int {
                        $value = new stdClass();

                        if (match ($flag) { true => ($value = label()), default => false } !== false) {
                            return strlen($value);
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'assignmentInAnArgumentOfANullsafeCallMayBeSkipped' => [
                'code' => '<?php
                    final class Reader {
                        public function read(string $label): ?int {
                            return null;
                        }
                    }

                    function label(): string {
                        return rand(0, 1) ? "a" : "";
                    }

                    function check(?Reader $reader): int {
                        $value = new stdClass();

                        if ($reader?->read($value = label()) !== null) {
                            return strlen($value);
                        }

                        return 0;
                    }',
                'error_message' => 'PossiblyInvalidArgument',
                'php_version' => '8.0',
            ],
            'assignmentInTheRightOperandOfAndFollowedByAnOrMayBeNullInTheBody' => [
                'code' => '<?php
                    final class Tagged {
                        public function name(): string {
                            return "tagged";
                        }
                    }

                    function getTagged(): ?Tagged {
                        return null;
                    }

                    function dump(bool $enabled, bool $fallback): string {
                        $tag = new Tagged();

                        if (($enabled && ($tag = getTagged())) || $fallback) {
                            return $tag->name();
                        }

                        return "";
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'assignmentInBranchOfAnd' => [
                'code' => '<?php
                    function foo(string $str): ?int {
                        $pos = 5;

                        if (rand(0, 1) && !($pos = $str)) {
                            return null;
                        }

                        return $pos;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'assignmentInBranchOfOr' => [
                'code' => '<?php
                    function getPath(): string|object {
                        return rand(0, 1) ? "a" : new stdClass();
                    }

                    function foo(string $s) : string {
                        if (($path = $s) || ($path = getPath())) {
                            return $path;
                        }

                        return "b";
                    }',
                'error_message' => 'InvalidReturnStatement',
                'error_levels' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'assignmentInBranchOfAndReferencedAfterIf' => [
                'code' => '<?php
                    function bar(bool $result): bool {
                        if ($result && ($result = rand(0, 1))) {
                            return true;
                        }

                        return $result;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'assignmentInBranchOfAndReferencedInElse' => [
                'code' => '<?php
                    function bar(bool $result): bool {
                        if ($result && ($result = rand(0, 1))) {
                            return true;
                        } else {
                            return $result;
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'assignmentInBranchOfAndReferencedInElseIf' => [
                'code' => '<?php
                    function bar(bool $result): bool {
                        if ($result && ($result = rand(0, 1))) {
                            return true;
                        } elseif (rand(0, 1)) {
                            return $result;
                        } else {
                            return true;
                        }
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'variableAssignedInSecondAndOperandKeepsItsTypeInThird' => [
                'code' => '<?php
                    function check(int $i): bool {
                        return $i > 0;
                    }

                    function takesString(string $s): bool {
                        return $s === "";
                    }

                    function f(bool $b): bool {
                        return $b && check($x = 1) && takesString($x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'variableAssignedInSkippedOperandOfOrIsPossiblyUndefinedInNextAndOperand' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, string $s): bool {
                        return ($b || check($x = $s)) && $x === "a";
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInSkippedOperandOfOrInMiddleAndOperandIsPossiblyUndefinedInNextOne' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, bool $d, string $s): bool {
                        return $b && ($d || check($x = $s)) && $x === "a";
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInSkippedOperandOfOrInFirstAndOperandIsPossiblyUndefinedInThird' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, bool $d, string $s): bool {
                        return ($d || check($x = $s)) && $b && $x === "a";
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInRightAndOperandIsPossiblyUndefinedAfterIt' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function f(bool $b, string $s): bool {
                        $r = $b && check($x = $s);
                        echo $x;
                        return $r;
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInAndNestedInArgumentIsPossiblyUndefinedInNextAndOperand' => [
                'code' => '<?php
                    function check(bool $b): bool {
                        return $b;
                    }

                    function f(bool $b, string $s): bool {
                        return check($b && ($x = $s) === "b") && $x === "a";
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
            'variableAssignedInAndOperandHasTheTypeAByRefArgumentOfALaterOneGivesIt' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    /** @param-out null $r */
                    function nullRef(mixed &$r): bool {
                        $r = null;
                        return true;
                    }

                    function takesInt(int $i): bool {
                        return $i > 0;
                    }

                    function f(): bool {
                        return check($x = "a") && nullRef($x) && takesInt($x);
                    }',
                'error_message' => 'NullArgument',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'propertyAssignedInAndOperandHasTheTypeAMethodCallOfALaterOneGivesIt' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    final class A {
                        public ?string $p = null;

                        public function reset(): bool {
                            $this->p = null;
                            return true;
                        }

                        public function f(bool $a, string $s): bool {
                            return $a && check($this->p = $s) && $this->reset() && strlen($this->p) > 0;
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'arrayKeyAssignedInAndOperandIsForgottenWhenALaterOneReassignsTheArray' => [
                'code' => '<?php
                    function check(mixed $s): bool {
                        return (bool) $s;
                    }

                    /** @param array{k?: string} $a */
                    function f(array $a): bool {
                        return check($a["k"] = "s") && check($a = ["other" => 1]) && strlen($a["k"]) > 0;
                    }',
                'error_message' => 'InvalidArrayOffset',
                'error_levels' => [],
                'php_version' => '8.0',
            ],
            'variableAssignedInAndOperandKeepsItsReferenceInLaterOnes' => [
                'code' => '<?php
                    function check(string $s): bool {
                        return $s === "b";
                    }

                    function gate(): bool {
                        return true;
                    }

                    function probe(int $i, string $s): bool {
                        return $i > 0 && $s !== "";
                    }

                    function f(): bool {
                        $x = null;
                        $y =& $x;
                        return check($x = "a") && gate() && probe($y = 1, $x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'variableAssignedInNegatedAndIsPossiblyUndefinedInNextAndOperand' => [
                'code' => '<?php
                    function f(bool $b, bool $c, string $s): bool {
                        return $b && !($c && ($x = $s) === "b") && $x === "a";
                    }',
                'error_message' => 'PossiblyUndefinedVariable',
            ],
        ];
    }
}
