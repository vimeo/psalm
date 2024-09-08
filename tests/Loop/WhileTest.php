<?php

namespace Psalm\Tests\Loop;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class WhileTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
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
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
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
