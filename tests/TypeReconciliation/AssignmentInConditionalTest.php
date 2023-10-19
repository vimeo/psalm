<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class AssignmentInConditionalTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
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
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function foo(string $file_name) : int {
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
            'SKIPPED-assertVarRedefinedInOpWithAnd' => [
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
                            /** @psalm-suppress MixedMethodCall */
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
                    } elseif (($a = rand(0, 1) ? new A : null) && $a->foo) {}',
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
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
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
                'ignored_issues' => [],
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
        ];
    }
}
