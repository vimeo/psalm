<?php
namespace Psalm\Tests;

class LoopScopeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'switchVariableWithContinue' => [
                '<?php
                    foreach (["a", "b", "c"] as $letter) {
                        switch ($letter) {
                            case "b":
                                $foo = 1;
                                break;
                            case "c":
                                $foo = 2;
                                break;
                            default:
                                continue 2;
                        }

                        $moo = $foo;
                    }',
            ],
            'switchVariableWithContinueAndIfs' => [
                '<?php
                    foreach (["a", "b", "c"] as $letter) {
                        switch ($letter) {
                            case "a":
                                if (rand(0, 10) === 1) {
                                    continue 2;
                                }
                                $foo = 1;
                                break;
                            case "b":
                                if (rand(0, 10) === 1) {
                                    continue 2;
                                }
                                $foo = 2;
                                break;
                            default:
                                continue 2;
                        }

                        $moo = $foo;
                    }',
            ],
            'switchVariableWithFallthrough' => [
                '<?php
                    foreach (["a", "b", "c"] as $letter) {
                        switch ($letter) {
                            case "a":
                            case "b":
                                $foo = 2;
                                break;

                            default:
                                $foo = 3;
                                break;
                        }

                        $moo = $foo;
                    }',
            ],
            'switchVariableWithFallthroughStatement' => [
                '<?php
                    foreach (["a", "b", "c"] as $letter) {
                        switch ($letter) {
                            case "a":
                                $bar = 1;

                            case "b":
                                $foo = 2;
                                break;

                            default:
                                $foo = 3;
                                break;
                        }

                        $moo = $foo;
                    }',
            ],
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
            'doWhileVar' => [
                '<?php
                    $worked = false;

                    do {
                        $worked = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$worked' => 'bool',
                ],
            ],
            'doWhileUndefinedVar' => [
                '<?php
                    do {
                        $result = rand(0,1);
                    } while (!$result);',
            ],
            'doWhileVarAndBreak' => [
                '<?php
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
            'objectValueWithTwoTypes' => [
                '<?php
                    class B {}
                    class A {
                        /** @var A|B */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A(): new B();
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
                            $this->parent = rand(0, 1) ? new A(): new B();
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
                            $this->parent = rand(0, 1) ? new A(): null;
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
                            $this->parent = rand(0, 1) ? new A(): null;
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
            'secondLoopWithNotNullCheck' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if ($a !== null) takesInt($a);
                        $a = $i;
                    }',
            ],
            'secondLoopWithIntCheck' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) takesInt($a);
                        $a = $i;
                    }',
            ],
            'secondLoopWithIntCheckAndConditionalSet' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) takesInt($a);

                        if (rand(0, 1)) {
                            $a = $i;
                        }
                    }',
            ],
            'secondLoopWithIntCheckAndAssignmentsInIfAndElse' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) {
                            $a = 6;
                        } else {
                            $a = $i;
                        }
                    }',
            ],
            'secondLoopWithIntCheckAndLoopSet' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) takesInt($a);

                        while (rand(0, 1)) {
                            $a = $i;
                        }
                    }',
            ],
            'secondLoopWithReturnInElseif' => [
                '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    $b = null;

                    foreach ([new A, new A] as $a) {
                        if ($a instanceof B) {

                        } elseif (!$a instanceof C) {
                            return "goodbye";
                        }

                        if ($b instanceof C) {
                            return "hello";
                        }

                        $b = $a;
                    }',
            ],
            'thirdLoopWithIntCheckAndLoopSet' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;
                    $b = null;

                    foreach ([1, 2, 3] as $i) {
                        if ($b !== null) {
                            takesInt($b);
                        }

                        if ($a !== null) {
                            takesInt($a);
                            $b = $a;
                        }

                        $a = $i;
                    }',
            ],
            'implicitFourthLoop' => [
                '<?php
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
            'unsetInLoop' => [
                '<?php
                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        $a = $i;
                        unset($i);
                    }',
            ],
            'assignInsideForeach' => [
                '<?php
                    $b = false;

                    foreach ([1, 2, 3, 4] as $a) {
                        if ($a === rand(0, 10)) {
                            $b = true;
                        }
                    }',
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'assignInsideForeachWithBreak' => [
                '<?php
                    $b = false;

                    foreach ([1, 2, 3, 4] as $a) {
                        if ($a === rand(0, 10)) {
                            $b = true;
                            break;
                        }
                    }',
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'nullCheckInsideForeachWithContinue' => [
                '<?php
                    class A {
                        /** @return array<A|null> */
                        public static function loadMultiple()
                        {
                            return [new A, null];
                        }

                        /** @return void */
                        public function barBar() {

                        }
                    }

                    foreach (A::loadMultiple() as $a) {
                        if ($a === null) {
                            continue;
                        }

                        $a->barBar();
                    }',
            ],
            'loopWithArrayKey' => [
                '<?php
                    /**
                     * @param array<array<int, array<string, string>>> $args
                     * @return array[]
                     */
                    function get_merged_dict(array $args) {
                        $merged = array();

                        foreach ($args as $group) {
                            foreach ($group as $key => $value) {
                                if (isset($merged[$key])) {
                                    $merged[$key] = array_merge($merged[$key], $value);
                                } else {
                                    $merged[$key] = $value;
                                }
                            }
                        }

                        return $merged;
                    }',
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
            'loopWithIfElseNoParadox' => [
                '<?php
                    $a = [];
                    $b = rand(0, 10) > 5;

                    foreach ([1, 2, 3] as $i) {
                      if (rand(0, 5)) {
                        $a[] = 5;
                        continue;
                      }

                      if ($b) {
                        continue; // if this is removed, no failure
                      } else {} // if else is removed, no failure
                    }

                    if ($a) {}',
            ],
            'bleedVarIntoOuterContextWithEmptyLoop' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                    }',
                'assignments' => [
                    '$tag' => 'string|null',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNull' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNullAndBreak' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                        break;
                      } elseif ($tag === "b") {
                        $tag = null;
                        break;
                      } else {
                        $tag = null;
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInElse' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'string|null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInIf' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        break;
                      } else {
                        $tag = null;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'string|null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInElseAndIntSet' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = 5;
                      } else {
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'string|int|null',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefineAndBreak' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'nullToMixedWithNullCheckNoContinue' => [
                '<?php
                    function getStrings(): array {
                        return ["hello", "world"];
                    }

                    $a = null;

                    foreach (getStrings() as $s) {
                      if ($a === null) {
                        $a = $s;
                      }
                    }',
                'assignments' => [
                    '$a' => 'mixed',
                ],
                'error_levels' => [
                    'MixedAssignment',
                ],
            ],
            'nullToMixedWithNullCheckAndContinue' => [
                '<?php
                    $a = null;

                    function getStrings(): array {
                        return ["hello", "world"];
                    }

                    $a = null;

                    foreach (getStrings() as $s) {
                      if ($a === null) {
                        $a = $s;
                        continue;
                      }
                    }',
                'assignments' => [
                    '$a' => 'mixed',
                ],
                'error_levels' => [
                    'MixedAssignment',
                ],
            ],
            'falseToBoolExplicitBreak' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      $a = true;
                      break;
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolExplicitContinue' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      $a = true;
                      continue;
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInBreak' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $a = true;
                        break;
                      } else {
                        $a = true;
                        break;
                      }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInContinue' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $a = true;
                        continue;
                      }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInBreakAndContinue' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $a = true;
                        break;
                      }

                      if ($tag === "b") {
                        $a = true;
                        continue;
                      }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInNestedForeach' => [
                '<?php
                    $a = false;

                    foreach (["d", "e", "f"] as $l) {
                        foreach (["a", "b", "c"] as $tag) {
                            if (!$a) {
                                if (rand(0, 10)) {
                                    $a = true;
                                    break;
                                } else {
                                    $a = true;
                                    break;
                                }
                            }
                        }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInContinueAndBreak' => [
                '<?php
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
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolAfterContinueAndBreak' => [
                '<?php
                    $a = false;
                    foreach ([1, 2, 3] as $i) {
                      if ($i > 0) {
                        $a = true;
                        continue;
                      }

                      break;
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'variableDefinedInForeachAndIf' => [
                '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                        } else {
                            $a = false;
                        }

                        echo $a;
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
            'noRedundantConditionAfterIsNumeric' => [
                '<?php
                    $ids = [];
                    foreach (explode(",", "hello,5,20") as $i) {
                      if (!is_numeric($i)) {
                        continue;
                      }

                      $ids[] = $i;
                    }',
            ],
            'mixedArrayAccessNoPossiblyUndefinedVar' => [
                '<?php
                    function foo(array $arr): void {
                      $r = [];
                      foreach ($arr as $key => $value) {
                        if ($value["foo"]) {}
                        $r[] = $key;
                      }
                    }',
                'assignments' => [],
                'error_levels' => [
                    'MixedAssignment', 'MixedArrayAccess',
                ],
            ],
            'whileTrue' => [
                '<?php
                    while (true) {
                        $a = "hello";
                        break;
                    }
                    while (1) {
                        $b = 5;
                        break;
                    }
                    for(;;) {
                        $c = true;
                        break;
                    }',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                    '$c' => 'true',
                ],
            ],
            'foreachLoopWithOKManipulation' => [
                '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      $i = 5;
                    }',
            ],
            'forLoopwithOKChange' => [
                '<?php
                    $j = 5;
                    for ($i = $j; $i < 4; $i++) {
                      $j = 9;
                    }',
            ],
            'foreachLoopDuplicateList' => [
                '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      foreach ($list as $j) {}
                    }',
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
            'doWhileWithNotEmptyCheck' => [
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
                    do {
                      takesA($a);
                      $a = $a->a;
                    } while ($a);',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'doWhileWithMethodCall' => [
                '<?php
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
                '<?php
                    do {
                        $done = rand(0, 1) > 0;
                    } while (!$done);',
            ],
            'doWhileWithIfException' => [
                '<?php
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
                '<?php
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
            'doWhileDefinedVar' => [
                '<?php
                    $value = null;
                    do {
                        $count = rand(0, 1);
                        $value = 6;
                    } while ($count);',
            ],
            'arrayKeyJustSetInLoop' => [
                '<?php
                    $a = null;
                    $arr = [];

                    foreach ([1, 2, 3] as $_) {
                        if (rand(0, 1)) {
                            $arr["a"]["c"] = "foo";
                            $a = $arr["a"]["c"];
                        } else {
                            $arr["b"]["c"] = "bar";
                            $a = $arr["b"]["c"];
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
            'additionSubtractionOps' => [
                '<?php
                    $a = 0;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a++;
                        } elseif ($a) {
                            $a--;
                        }
                    }'
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
            'invalidateBothByRefAssignmentsInDo' => [
                '<?php
                    function foo(?string &$i) : void {}
                    function bar(?string &$i) : void {}

                    $c = null;

                    do {
                        if (!$c) {
                            foo($c);
                        } else {
                            bar($c);
                        }
                    } while (rand(0, 1));',
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
            'doParentCall' => [
                '<?php
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
            'doWithContinue' => [
                '<?php
                    do {
                        if (rand(0, 1)) {
                            continue;
                        }
                    } while (rand(0, 1));',
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
            'noEmptyArrayAccessComplaintInsideDo' => [
                '<?php
                    $foo = [];
                    do {
                        if (isset($foo["bar"])) {}
                        $foo["bar"] = "bat";
                    } while (rand(0, 1));',
            ],
            'updateExistingValueAfterLoopContinue' => [
                '<?php
                    $i = false;
                    $b = (bool) rand(0, 1);
                    foreach ([$b] as $n) {
                        $i = $n;
                        if ($i) {
                            continue;
                        }
                    }
                    if ($i) {}',
            ],
            'preventNegativeZeroScrewingThingsUp' => [
                '<?php
                    function foo() : void {
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
                    }'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'switchVariableWithContinueOnce' => [
                '<?php
                    foreach (["a", "b", "c"] as $letter) {
                        switch ($letter) {
                            case "b":
                                $foo = 1;
                                break;
                            case "c":
                                $foo = 2;
                                break;
                            default:
                                continue;
                        }

                        $moo = $foo;
                    }',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'possiblyUndefinedArrayInForeach' => [
                '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $array[] = "hello";
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Possibly undefined ' .
                    'global variable $array, first seen on line 3',
            ],
            'possiblyUndefinedArrayInWhileAndForeach' => [
                '<?php
                    for ($i = 0; $i < 4; $i++) {
                        while (rand(0,10) === 5) {
                            $array[] = "hello";
                        }
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:4 - Possibly undefined ' .
                    'global variable $array, first seen on line 4',
            ],
            'possiblyUndefinedVariableInForeach' => [
                '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $car = "Volvo";
                    }

                    echo $car;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:6 - Possibly undefined ' .
                    'global variable $car, first seen on line 3',
            ],
            'possibleUndefinedVariableInForeachAndIfWithBreak' => [
                '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                            break;
                        }
                    }

                    echo $a;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:9 - Possibly undefined ' .
                    'global variable $a, first seen on line 4',
            ],
            'possibleUndefinedVariableInForeachAndIf' => [
                '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                        }

                        echo $a;
                    }',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:7 - Possibly undefined ' .
                    'global variable $a, first seen on line 4',
            ],
            'implicitFourthLoopWithBadReturnType' => [
                '<?php
                    function test(): int {
                      $x = 0;
                      $y = 1;
                      $z = 2;
                      foreach ([0, 1, 2] as $i) {
                        $x = $y;
                        $y = $z;
                        $z = "hello";
                      }
                      return $x;
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'possiblyNullCheckInsideForeachWithNoLeaveStatement' => [
                '<?php
                    class A {
                        /** @return array<A|null> */
                        public static function loadMultiple()
                        {
                            return [new A, null];
                        }

                        /** @return void */
                        public function barBar() {

                        }
                    }

                    foreach (A::loadMultiple() as $a) {
                        if ($a === null) {
                            // do nothing
                        }

                        $a->barBar();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'redundantConditionInForeachIf' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                        if (!$a) {
                            $a = true;
                            break;
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'redundantConditionInForeachWithIfElse' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                        if (!$a) {
                            if (rand(0, 1)) {
                                $a = true;
                                break;
                            } else {
                                $a = true;
                                break;
                            }
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'whileTrueNoBreak' => [
                '<?php
                    while (true) {
                        $a = "hello";
                    }

                    echo $a;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'forInfiniteNoBreak' => [
                '<?php
                    for (;;) {
                        $a = "hello";
                    }

                    echo $a;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'foreachLoopInvalidation' => [
                '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      $list = [4, 5, 6];
                    }',
                'error_message' => 'LoopInvalidation',
            ],
            'forLoopInvalidation' => [
                '<?php
                    for ($i = 0; $i < 4; $i++) {
                      foreach ([1, 2, 3] as $i) {}
                    }',
                'error_message' => 'LoopInvalidation',
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
