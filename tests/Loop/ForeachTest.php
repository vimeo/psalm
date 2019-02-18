<?php
namespace Psalm\Tests\Loop;

use Psalm\Tests\Traits;

class ForeachTest extends \Psalm\Tests\TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
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
                'assertions' => [
                    '$moo' => 'int',
                ],
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
                'assertions' => [
                    '$moo' => 'int',
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
                    '$tag' => 'string',
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
            'falseToBoolAfterContinueAndBreak' => [
                '<?php
                    $a = false;
                    foreach ([1, 2, 3] as $i) {
                      if ($i > 1) {
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
            'foreachLoopWithOKManipulation' => [
                '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      $i = 5;
                    }',
            ],
            'foreachLoopDuplicateList' => [
                '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      foreach ($list as $j) {}
                    }',
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
            'possiblyUndefinedVariableInForeach' => [
                '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $car = "Volvo";
                    }

                    echo $car;',
            ],
            'possiblyUndefinedVariableInForeachDueToBreakAfter' => [
                '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $car = "Volvo";
                        if (rand(0, 1)) {
                            break;
                        }
                    }

                    echo $car;',
            ],
            'iteratorAggregateIteration' => [
                '<?php
                    class C implements IteratorAggregate
                    {
                        public function getIterator(): Iterator
                        {
                            return new ArrayIterator([]);
                        }
                    }

                    function loopT(Traversable $coll): void
                    {
                        foreach ($coll as $item) {}
                    }

                    function loopI(IteratorAggregate $coll): void
                    {
                        foreach ($coll as $item) {}
                    }

                    loopT(new C);
                    loopI(new C);',
                'assignments' => [],
                'error_levels' => [
                    'MixedAssignment', 'UndefinedThisPropertyAssignment',
                ],
            ],
            'intersectionIterator' => [
                '<?php
                    /**
                     * @param \Traversable<int, int>&\Countable $object
                     */
                    function doSomethingUseful($object) : void {
                        echo count($object);
                        foreach ($object as $foo) {}
                    }'
            ],
            'rawIteratorIteration' => [
                '<?php
                    class Item {
                      /**
                       * @var string
                       */
                      public $prop = "var";
                    }

                    /**
                     * @return Iterator<int, Item>
                     */
                    function getIterator(): Iterator {
                        return new ArrayIterator([new Item()]);
                    }

                    foreach (getIterator() as $item) {
                        echo $item->prop;
                    }',
            ],
            'seekableIteratorIteration' => [
                '<?php
                    class Item {
                        /**
                         * @var string
                         */
                        public $prop = "var";
                    }

                    /**
                     * @return SeekableIterator<int, Item>
                     */
                    function getIterator(): \SeekableIterator {
                        return new ArrayIterator([new Item()]);
                    }

                    foreach (getIterator() as $item) {
                        echo $item->prop;
                    }',
            ],
            'arrayIteratorIteration' => [
                '<?php
                    class Item {
                        /**
                         * @var string
                         */
                        public $prop = "var";
                    }

                    /**
                     * @return ArrayIterator<int, Item>
                     */
                    function getIterator(): \SeekableIterator {
                        return new ArrayIterator([new Item()]);
                    }

                    foreach (getIterator() as $item) {
                        echo $item->prop;
                    }',
            ],
            'templatedIteratorAggregateIteration' => [
                '<?php
                    class Item {
                      /**
                       * @var string
                       */
                      public $prop = "var";
                    }

                    class Collection implements IteratorAggregate {
                      /**
                       * @var Item[]
                       */
                      private $items = [];

                      public function add(Item $item): void
                      {
                          $this->items[] = $item;
                      }

                      /**
                        * @return ArrayIterator<mixed, Item>
                        */
                      public function getIterator(): \ArrayIterator
                      {
                          return new ArrayIterator($this->items);
                      }
                    }

                    $collection = new Collection();
                    $collection->add(new Item());
                    foreach ($collection as $item) {
                        echo $item->prop;
                    }'
            ],
            'foreachIntersectionTraversable' => [
                '<?php
                    /** @var Countable&Traversable<int> */
                    $c = null;
                    foreach ($c as $i) {}',
            ],
            'iterateOverNonEmptyConstant' => [
                '<?php
                    class A {
                        const ARR = [0, 1, 2];

                        public function test() : int
                        {
                            foreach (self::ARR as $val) {
                                $max = $val;
                            }

                            return $max;
                        }
                    }'
            ],
            'ifSpecificMaybeEmptyValues' => [
                '<?php
                    foreach ([0, 1, 2, 3] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
            ],
            'ifSpecificMaybeEmptyStringValues' => [
                '<?php
                    foreach (["", "1", "2", "3"] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
            ],
            'domNodeListIterator' => [
                '<?php
                    function foo(DOMNodeList $list) : void {
                        foreach ($list as $item) {}
                    }'
            ],
            'loopOverArrayChunk' => [
                '<?php
                    /**
                    * @return array<int, array<array-key, int>>
                    */
                    function Foo(int $a, int $b, int ...$ints) : array {
                      array_unshift($ints, $a, $b);

                      return array_chunk($ints, 2);
                    }

                    foreach(Foo(1, 2, 3, 4, 5) as $ints) {
                      echo $ints[0], ", ", ($ints[1] ?? "n/a"), "\n";
                    }'
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
            'foreachLoopInvalidation' => [
                '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      $list = [4, 5, 6];
                    }',
                'error_message' => 'LoopInvalidation',
            ],
            'possiblyUndefinedVariableInForeachDueToBreakBefore' => [
                '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        if (rand(0, 1)) {
                            break;
                        }
                        $car = "Volvo";
                    }

                    echo $car;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'continueOutsideLoop' => [
                '<?php
                    continue;',
                'error_message' => 'ContinueOutsideLoop',
            ],
            'invalidIterator' => [
                '<?php
                    foreach (5 as $a) {

                    }',
                'error_message' => 'InvalidIterator',
            ],
            'rawObjectIteration' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    class B extends A {}

                    function bar(A $a): void {}

                    $arr = [];

                    if (rand(0, 10) > 5) {
                        $arr[] = new A;
                    } else {
                        $arr = new B;
                    }

                    foreach ($arr as $a) {
                        bar($a);
                    }',
                'error_message' => 'RawObjectIteration',
            ],
            'ifSpecificNonEmptyValues' => [
                '<?php
                    foreach ([1, 2, 3] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
                'error_message' => 'RedundantCondition',
            ],
            'ifSpecificNonEmptyStringValues' => [
                '<?php
                    foreach (["1", "2", "3"] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
                'error_message' => 'RedundantCondition',
            ],
        ];
    }
}
