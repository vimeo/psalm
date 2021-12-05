<?php
namespace Psalm\Tests\Loop;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class WhileTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'whileVar' => [
                '<?php
                    $worked = false;

                    while (rand(0,100) === 10) {
                        $worked = true;
                    }',
                'assertions' => [
                    '$worked' => 'bool',
                ],
            ],
            'objectValueWithTwoTypes' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    $a = ["b", "c", "d"];
                    array_pop($a);
                    while ($a) {
                        $letter = array_pop($a);
                        if (!$a) {}
                    }',
            ],
            'noRedundantConditionInWhileAssignment' => [
                '<?php
                    class A {
                      /** @var ?int */
                      public $bar;
                    }

                    function foo(): ?A {
                      return rand(0, 1) ? new A : null;
                    }

                    while ($a = foo()) {
                      if ($a->bar) {}
                    }',
            ],
            'whileTrueWithBreak' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    $data = ["a" => false];
                    while (!$data["a"]) {
                        if (rand() % 2 > 0) {
                            $data = ["a" => true];
                        }
                    }',
            ],
            'additionSubtractionAssignment' => [
                '<?php
                    $a = 0;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = $a + 1;
                        } elseif ($a) {
                            $a = $a - 1;
                        }
                    }'
            ],
            'additionSubtractionInc' => [
                '<?php
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
                '<?php
                    function foo(?string &$i) : void {}
                    function bar(?string &$i) : void {}

                    $c = null;

                    while (rand(0, 1)) {
                        if (!$c) {
                            foo($c);
                        } else {
                            bar($c);
                        }
                    }',
            ],
            'applyLoopConditionalAfterIf' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    $i = 5;
                    while (--$i > 0) {}
                    echo $i === 0;',
            ],
            'noRedundantConditionOnAddedSubtractedInLoop' => [
                '<?php
                    $depth = 0;
                    $position = 0;
                    while (!$depth) {
                        if (rand(0, 1)) {
                            $depth++;
                        } elseif (rand(0, 1)) {
                            $depth--;
                        }
                        $position++;
                    }'
            ],
            'variableDefinedInWhileConditional' => [
                '<?php
                    function foo() : void {
                        $pointers = ["hi"];

                        while (rand(0, 1) && -1 < ($parent = 0)) {
                            print $pointers[$parent];
                        }
                    }'
            ],
            'assingnedConditionallyReassignedToMixedInLoop' => [
                '<?php
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
                '<?php
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
                '<?php
                    function test(array $x, int $i) : void {
                        while (isset($x[$i]) && is_array($x[$i])) {
                            $i++;
                        }
                    }'
            ],
            'possiblyUndefinedInWhile' => [
                '<?php
                    function getRenderersForClass(string $a): void {
                        while ($b = getString($b ?? $a)) {
                            $c = "hello";
                        }
                    }

                    function getString(string $s) : ?string {
                        return rand(0, 1) ? $s : null;
                    }'
            ],
            'thornyLoop' => [
                '<?php

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
                    }'
            ],
            'assignToTKeyedArrayListPreserveListness' => [
                '<?php
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
                '<?php
                    $counter = 0;

                    while (rand(0, 1)) {
                        if ($counter > 0) {
                            $counter = $counter - 1;
                        } else {
                            $counter = $counter + 1;
                        }
                    }'
            ],
            'nonEmptyListIterationChangeVarWithContinue' => [
                '<?php
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
                    }'
            ],
            'nonEmptyListIterationChangeVarWithoutContinue' => [
                '<?php
                    /** @param non-empty-list<int> $arr */
                    function foo(array $arr) : void {
                        while (array_shift($arr)) {
                            if ($arr && $arr[0] === "a") {}

                            if (rand(0, 1)) {
                                $arr = array_merge($arr, ["a"]);
                            }

                            echo "here";
                        }
                    }'
            ],
            'propertyAssertionInsideWhile' => [
                '<?php
                    class Foo {
                        public array $a = [];
                        public array $b = [];
                        public array $c = [];

                        public function one(): bool {
                            $has_changes = false;

                            while ($this->a) {
                                $has_changes = true;
                                $this->alter();
                            }

                            return $has_changes;
                        }

                        public function two(): bool {
                            $has_changes = false;

                            while ($this->a || $this->b) {
                                $has_changes = true;
                                $this->alter();
                            }

                            return $has_changes;
                        }

                        public function three(): bool {
                            $has_changes = false;

                            while ($this->a || $this->b || $this->c) {
                                $has_changes = true;
                                $this->alter();
                            }

                            return $has_changes;
                        }

                        public function four(): bool {
                            $has_changes = false;

                            while (($this->a && $this->b) || $this->c) {
                                $has_changes = true;
                                $this->alter();
                            }

                            return $has_changes;
                        }

                        public function alter() : void {
                            if (rand(0, 1)) {
                                array_pop($this->a);
                            } elseif (rand(0, 1)) {
                                array_pop($this->a);
                            } else {
                                array_pop($this->c);
                            }
                        }
                    }'
            ],
            'propertyAssertionInsideWhileNested' => [
                '<?php
                    class Foo {
                        public array $a = [];
                        public array $b = [];
                        public array $c = [];

                        public function five(): bool {
                            $has_changes = false;

                            while ($this->a || ($this->b && $this->c)) {
                                $has_changes = true;
                                $this->alter();
                            }

                            return $has_changes;
                        }

                        public function alter() : void {
                            if (rand(0, 1)) {
                                array_pop($this->a);
                            } elseif (rand(0, 1)) {
                                array_pop($this->a);
                            } else {
                                array_pop($this->c);
                            }
                        }
                    }'
            ],
            'ifNestedInsideLoop' => [
                '<?php
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
                    }'
            ],
            'ifNotNestedInsideLoop' => [
                '<?php
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
                    }'
            ],
            'continueShouldAddToContext' => [
                '<?php
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
                    }'
            ],
            'continue2Returns' => [
                '<?php
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
                    }'
            ],
            'propertyTypeUpdatedInBranch' => [
                '<?php
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
                    }'
            ],
            'propertyTypeUpdatedInBranchWithBreak' => [
                '<?php
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
                    }'
            ],
            'whileTrueDontHaveExitPathForReturn' => [
                '<?php
                    function getResultWithRetry(): string
                    {
                        while (new stdClass) {
                            return "";
                        }
                    }'
            ],
            'ComplexWhileTrueDontHaveExitPathForReturn' => [
                '<?php
                    class Test {
                        private int $retryAttempts = 10;

                        private function getResult(): string
                        {
                            // return tring or throw exception whatever
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
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'whileTrueNoBreak' => [
                '<?php
                    while (true) {
                        $a = "hello";
                    }

                    echo $a;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'invalidateByRefAssignmentWithRedundantCondition' => [
                '<?php
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
