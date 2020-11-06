<?php
namespace Psalm\Tests\TypeReconciliation;

class AssignmentInConditionalTest extends \Psalm\Tests\TestCase
{
    use \Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
    use \Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'orWithAssignment' => [
                '<?php
                    function maybeString(): ?string {
                        return rand(0, 10) > 4 ? "test" : null;
                    }

                    function test(): string {
                        $foo = maybeString();
                        ($foo !== null) || ($foo = "");

                        return $foo;
                    }'
            ],
            'andWithAssignment' => [
                '<?php
                    function maybeString(): ?string {
                        return rand(0, 10) > 4 ? "test" : null;
                    }

                    function test(): string {
                        $foo = maybeString();
                        ($foo === null) && ($foo = "");

                        return $foo;
                    }'
            ],
            'assertHardConditionalWithString' => [
                '<?php
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
                    }'
            ],
            'assertOnRemainderOfArray' => [
                '<?php
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
                '<?php
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
                    }'
            ],
            'SKIPPED-assertVarRedefinedInOpWithAnd' => [
                '<?php
                    class O {
                        public function foo() : bool { return true; }
                    }

                    /** @var mixed */
                    $value = $_GET["foo"];

                    $a = is_string($value) && (($value = rand(0, 1) ? new O : null) !== null) && $value->foo();',
                [
                    '$a' => 'bool',
                ]
            ],
            'assertVarRedefinedInOpWithOr' => [
                '<?php
                    class O {
                        public function foo() : bool { return true; }
                    }

                    /** @var mixed */
                    $value = $_GET["foo"];

                    $a = !is_string($value) || (($value = rand(0, 1) ? new O : null) === null) || $value->foo();',
                [
                    '$a' => 'bool',
                ]
            ],
            'assertVarInOrAfterAnd' => [
                '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?A $b): void {
                        $c = ($a instanceof B && $b instanceof B) || ($a instanceof C && $b instanceof C);
                    }'
            ],
            'assertAssertionsWithCreation' => [
                '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function getA(A $a): ?A {
                        return rand(0, 1) ? $a : null;
                    }

                    function foo(?A $a, ?A $c): void {
                        $c = $a && ($b = getA($a)) && $c ? 1 : 0;
                    }'
            ],
            'definedInBothBranchesOfConditional' => [
                '<?php
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
                    }'
            ],
            'definedInConditionalAndCheckedInSubbranch' => [
                '<?php
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
                    }'
            ],
            'definedInRhsOfConditionalInNegation' => [
                '<?php
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
                    }'
            ],
            'definedInOrRHS' => [
                '<?php
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
                    }'
            ],
            'possiblyDefinedVarInAssertion' => [
                '<?php
                    class A {
                        public function test() : bool { return true; }
                    }

                    function getMaybeA() : ?A { return rand(0, 1) ? new A : null; }

                    function foo() : void {
                        if (rand(0, 10) && ($a = getMaybeA()) && !$a->test()) {
                            return;
                        }

                        echo isset($a);
                    }'
            ],
            'applyTruthyAssertionsToRightHandSideOfAssignment' => [
                '<?php
                    function takesAString(string $name): void {}

                    function randomReturn(): ?string {
                        return rand(1,2) === 1 ? "foo" : null;
                    }

                    $name = randomReturn();

                    if ($foo = ($name !== null)) {
                        takesAString($name);
                    }'
            ],
            'maintainTruthinessInsideAssignment' => [
                '<?php
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
                    }'
            ],
            'allowBasicOrAssignment' => [
                '<?php
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
                    }'
            ],
            'assertWithAssignmentInOr' => [
                'function test(int $x = null): int {
                    \assert($x || ($x = rand(0, 10)));
                    return $x;
                }'
            ],
            'noParadoxicalConditionAfterTwoAssignments' => [
                '<?php
                    function foo(string $str): ?int {
                        if (rand(0, 1) || (!($pos = strpos($str, "a")) && !($pos = strpos($str, "b")))) {
                            return null;
                        }

                        return $pos;
                    }'
            ],
            'assignmentInIf' => [
                '<?php
                    if ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }',
            ],
            'negatedAssignmentInIf' => [
                '<?php
                    if (!($row = (rand(0, 10) ? [5] : null))) {
                        // do nothing
                    }
                    else {
                        echo $row[0];
                    }',
            ],
            'assignInElseIf' => [
                '<?php
                    if (rand(0, 10) > 5) {
                        echo "hello";
                    } elseif ($row = (rand(0, 10) ? [5] : null)) {
                        echo $row[0];
                    }',
            ],
            'ifNotEqualsFalse' => [
                '<?php
                    if (($row = rand(0,10) ? [1] : false) !== false) {
                       echo $row[0];
                    }',
            ],
            'ifNotEqualsNull' => [
                '<?php
                    if (($row = rand(0,10) ? [1] : null) !== null) {
                       echo $row[0];
                    }',
            ],
            'ifNullNotEquals' => [
                '<?php
                    if (null !== ($row = rand(0,10) ? [1] : null)) {
                       echo $row[0];
                    }',
            ],
            'ifNullEquals' => [
                '<?php
                    if (null === ($row = rand(0,10) ? [1] : null)) {

                    } else {
                        echo $row[0];
                    }',
            ],
            'passedByRefInIf' => [
                '<?php
                    if (preg_match("/bad/", "badger", $matches)) {
                        echo (string)$matches[0];
                    }',
            ],
            'passByRefInIfCheckAfter' => [
                '<?php
                    if (!preg_match("/bad/", "badger", $matches)) {
                        exit();
                    }
                    echo (string)$matches[0];',
            ],
            'passByRefInIfWithBoolean' => [
                '<?php
                    $a = (bool)rand(0, 1);
                    if ($a && preg_match("/bad/", "badger", $matches)) {
                        echo (string)$matches[0];
                    }',
            ],
            'bleedElseifAssignedVarsIntoElseScope' => [
                '<?php
                    if (rand(0, 1) === 0) {
                        $foo = 0;
                    } elseif ($foo = rand(0, 10)) {}

                    echo substr("banana", $foo);',
            ],
            'repeatedSet' => [
                '<?php
                    function foo(): void {
                        if ($a = rand(0, 1) ? "" : null) {
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
                '<?php
                    function foo(): void {
                        if ($a = rand(0, 1) ? "" : null) {
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
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    if (rand(0, 10) > 5) {
                    } elseif (($a = rand(0, 1) ? new A : null) && $a->foo) {}',
            ],
            'noParadoxAfterConditionalAssignment' => [
                '<?php
                    if ($a = rand(0, 5)) {
                        echo $a;
                    } elseif ($a = rand(0, 5)) {
                        echo $a;
                    }',
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'assignmentInBranchOfAnd' => [
                '<?php
                    function foo(string $str): ?int {
                        $pos = 5;

                        if (rand(0, 1) && !($pos = $str)) {
                            return null;
                        }

                        return $pos;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
