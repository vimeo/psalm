<?php

namespace Psalm\Tests\Loop;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ForeachTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'switchVariableWithContinue' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if ($a !== null) takesInt($a);
                        $a = $i;
                    }',
            ],
            'secondLoopWithIntCheck' => [
                'code' => '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) takesInt($a);
                        $a = $i;
                    }',
            ],
            'secondLoopWithIntCheckAndConditionalSet' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        $a = $i;
                        unset($i);
                    }',
            ],
            'assignInsideForeach' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                    }',
                'assertions' => [
                    '$tag' => 'string',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNull' => [
                'code' => '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                      }
                    }',
                'assertions' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNullAndBreak' => [
                'code' => '<?php
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
                'assertions' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInElse' => [
                'code' => '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        break;
                      }
                    }',
                'assertions' => [
                    '$tag' => 'null|string',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInIf' => [
                'code' => '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        break;
                      } else {
                        $tag = null;
                      }
                    }',
                'assertions' => [
                    '$tag' => 'null|string',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInElseAndIntSet' => [
                'code' => '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = 5;
                      } else {
                        break;
                      }
                    }',
                'assertions' => [
                    '$tag' => 'int|null|string',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefineAndBreak' => [
                'code' => '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                        break;
                      }
                    }',
                'assertions' => [
                    '$tag' => 'null',
                ],
            ],
            'nullToMixedWithNullCheckNoContinue' => [
                'code' => '<?php
                    function getStrings(): array {
                        return ["hello", "world"];
                    }

                    $a = null;

                    foreach (getStrings() as $s) {
                      if ($a === null) {
                        $a = $s;
                      }
                    }',
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ],
            'noMixedAssignmentWithIfAssertion' => [
                'code' => '<?php
                    $object = new stdClass();
                    $reflection = new ReflectionClass($object);

                    foreach ($reflection->getProperties() as $property) {
                        $message = $property->getValue($reflection->newInstance());

                        if (!is_string($message)) {
                            throw new RuntimeException();
                        }
                    }',
            ],
            'noMixedAssignmentWithAssertion' => [
                'code' => '<?php
                    $object = new stdClass();
                    $reflection = new ReflectionClass($object);

                    foreach ($reflection->getProperties() as $property) {
                        $message = $property->getValue($reflection->newInstance());
                        assert(is_string($message));
                    }',
            ],
            'nullToMixedWithNullCheckAndContinue' => [
                'code' => '<?php
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
                'assertions' => [
                    '$a' => 'mixed',
                ],
                'ignored_issues' => [
                    'MixedAssignment',
                ],
            ],
            'falseToBoolExplicitBreak' => [
                'code' => '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      $a = true;
                      break;
                    }',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolExplicitContinue' => [
                'code' => '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      $a = true;
                      continue;
                    }',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInBreak' => [
                'code' => '<?php
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
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInContinue' => [
                'code' => '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $a = true;
                        continue;
                      }
                    }',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInBreakAndContinue' => [
                'code' => '<?php
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
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInNestedForeach' => [
                'code' => '<?php
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
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolAfterContinueAndBreak' => [
                'code' => '<?php
                    $a = false;
                    foreach ([1, 2, 3] as $i) {
                      if ($i > 1) {
                        $a = true;
                        continue;
                      }

                      break;
                    }',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'variableDefinedInForeachAndIf' => [
                'code' => '<?php
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
                'code' => '<?php
                    $ids = [];
                    foreach (explode(",", "hello,5,20") as $i) {
                      if (!is_numeric($i)) {
                        continue;
                      }

                      $ids[] = $i;
                    }',
            ],
            'mixedArrayAccessNoPossiblyUndefinedVar' => [
                'code' => '<?php
                    function foo(array $arr): void {
                      $r = [];
                      foreach ($arr as $key => $value) {
                        if ($value["foo"]) {}
                        $r[] = $key;
                      }
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment', 'MixedArrayAccess',
                ],
            ],
            'foreachLoopWithOKManipulation' => [
                'code' => '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      $i = 5;
                    }',
            ],
            'foreachLoopDuplicateList' => [
                'code' => '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                      foreach ($list as $j) {}
                    }',
            ],
            'arrayKeyJustSetInLoop' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $car = "Volvo";
                    }

                    echo $car;',
            ],
            'possiblyUndefinedVariableInForeachDueToBreakAfter' => [
                'code' => '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $car = "Volvo";
                        if (rand(0, 1)) {
                            break;
                        }
                    }

                    echo $car;',
            ],
            'iteratorAggregateIteration' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
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
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment', 'UndefinedThisPropertyAssignment',
                ],
            ],
            'intersectionIterator' => [
                'code' => '<?php
                    /**
                     * @param \Traversable<int, int>&\Countable $object
                     */
                    function doSomethingUseful($object) : void {
                        echo count($object);
                        foreach ($object as $foo) {}
                    }',
            ],
            'rawIteratorIteration' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    class Item {
                      /**
                       * @var string
                       */
                      public $prop = "var";
                    }

                    /**
                     * @implements IteratorAggregate<array-key, Item>
                     */
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
                        * @return ArrayIterator<array-key, Item>
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
                    }',
            ],
            'foreachIntersectionTraversable' => [
                'code' => '<?php
                    /** @var Countable&Traversable<int> */
                    $c = null;
                    foreach ($c as $i) {}',
            ],
            'iterateOverNonEmptyConstant' => [
                'code' => '<?php
                    class A {
                        const ARR = [0, 1, 2];

                        public function test() : int
                        {
                            foreach (self::ARR as $val) {
                                $max = $val;
                            }

                            return $max;
                        }
                    }',
            ],
            'ifSpecificMaybeEmptyValues' => [
                'code' => '<?php
                    foreach ([0, 1, 2, 3] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
            ],
            'ifSpecificMaybeEmptyStringValues' => [
                'code' => '<?php
                    foreach (["", "1", "2", "3"] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
            ],
            'domNodeListIterator' => [
                'code' => '<?php
                    function foo(DOMNodeList $list) : void {
                        foreach ($list as $item) {}
                    }',
            ],
            'loopOverArrayChunk' => [
                'code' => '<?php
                    /**
                    * @return array<int, array<array-key, int>>
                    */
                    function Foo(int $a, int $b, int ...$ints) : array {
                      array_unshift($ints, $a, $b);

                      return array_chunk($ints, 2);
                    }

                    foreach(Foo(1, 2, 3, 4, 5) as $ints) {
                      echo $ints[0], ", ", ($ints[1] ?? "n/a"), "\n";
                    }',
            ],
            'iteratorClassCurrent' => [
                'code' => '<?php
                    class Value {}

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class ValueCollection implements \Countable, \IteratorAggregate {
                        /**
                         * @var Value[]
                         */
                        private $items = [];

                        private function add(Value $item): void
                        {
                            $this->items[] = $item;
                        }

                        /**
                         * @return Value[]
                         */
                        public function toArray(): array
                        {
                            return $this->items;
                        }

                        public function getIterator(): ValueCollectionIterator
                        {
                            return new ValueCollectionIterator($this);
                        }

                        public function count(): int
                        {
                            return \count($this->items);
                        }

                        public function isEmpty(): bool
                        {
                            return empty($this->items);
                        }

                        public function contains(Value $item): bool
                        {
                            foreach ($this->items as $_item) {
                                if ($_item === $item) {
                                    return true;
                                }
                            }

                            return false;
                        }
                    }

                    /**
                     * @psalm-suppress MissingTemplateParam
                     */
                    class ValueCollectionIterator implements \Countable, \Iterator
                    {
                        /**
                         * @var Value[]
                         */
                        private $items;

                        /**
                         * @var int
                         */
                        private $position = 0;

                        public function __construct(ValueCollection $collection)
                        {
                            $this->items = $collection->toArray();
                        }

                        public function count(): int
                        {
                            return \iterator_count($this);
                        }

                        public function rewind(): void
                        {
                            $this->position = 0;
                        }

                        public function valid(): bool
                        {
                            return $this->position < \count($this->items);
                        }

                        public function key(): int
                        {
                            return $this->position;
                        }

                        public function current(): Value
                        {
                            return $this->items[$this->position];
                        }

                        public function next(): void
                        {
                            $this->position++;
                        }
                    }

                    function foo(ValueCollection $v) : void {
                        foreach ($v as $value) {}
                    }',
            ],
            'possibleRawObjectIterationFromIssetSuppressed' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress RawObjectIteration
                     * @psalm-suppress MixedAssignment
                     */
                    function foo(array $a) : void {
                        if (isset($a["a"]["b"])) {
                            foreach ($a["a"] as $c) {}
                        }
                    }',
            ],
            'simpleXmlIterator' => [
                'code' => '<?php
                    function f(SimpleXMLElement $elt): void {
                        foreach ($elt as $item) {
                            f($item);
                        }
                    }',
            ],
            'loopOverIteratorWithTooFewParams' => [
                'code' => '<?php
                    /**
                     * @param Iterator<string> $arr
                     */
                    function foo(Iterator $arr) : void {
                        foreach ($arr as $a) {}
                    }',
            ],
            'foreachLoopInvalidation' => [
                'code' => '<?php
                    $list = [1, 2, 3];
                    foreach ($list as $i) {
                        $list = [4, 5, 6];
                    }',
            ],
            'createNestedArrayInLoop' => [
                'code' => '<?php
                    function foo() : void {
                        $arr = [];

                        foreach ([1, 2, 3] as $i) {
                            $arr[$i]["a"] ??= 0;

                            $arr[$i]["a"] += 5;
                        }
                    }',
            ],
            'iteratorForeach' => [
                'code' => '<?php
                    /**
                     * @implements Iterator<int, string>
                     */
                    class FooIterator implements \Iterator {
                        private ?int $key = null;

                        public function current(): string
                        {
                            return "a";
                        }

                        public function next(): void
                        {
                            $this->key = $this->key === null ? 0 : $this->key + 1;
                        }

                        public function key(): int
                        {
                            if ($this->key === null) {
                                throw new \Exception();
                            }
                            return $this->key;
                        }

                        public function valid(): bool
                        {
                            return $this->key !== null && $this->key <= 3;
                        }

                        public function rewind(): void
                        {
                            $this->key = null;
                            $this->next();
                        }
                    }

                    foreach (new FooIterator() as $key => $value) {
                        echo $key . " " . $value;
                    }',
            ],
            'loopClosure' => [
                'code' => '<?php
                    /**
                     * @param list<0> $currentIndexes
                     */
                    function cartesianProduct(array $currentIndexes): void {
                        while (rand(0, 1)) {
                            array_map(
                                function ($index) { echo $index; },
                                $currentIndexes
                            );

                            /** @psalm-suppress PossiblyUndefinedArrayOffset */
                            $currentIndexes[0]++;
                        }
                    }',
            ],
            'loopCanUpdateOuterWithoutBreak' => [
                'code' => '<?php
                    /**
                     * @param array<int> $mappings
                     */
                    function foo(string $id, array $mappings) : void {
                        if ($id === "a") {
                            foreach ($mappings as $value) {
                                $id = $value;
                            }
                        }

                        if (is_int($id)) {}
                    }',
            ],
            'loopCanUpdateOuterWithBreak' => [
                'code' => '<?php
                    /**
                     * @param array<int> $mappings
                     */
                    function foo(string $id, array $mappings) : void {
                        if ($id === "a") {
                            foreach ($mappings as $value) {
                                if (rand(0, 1)) {
                                    $id = $value;
                                    break;
                                }
                            }
                        }

                        if (is_int($id)) {}
                    }',
            ],
            'loopCanUpdateOuterWithContinue' => [
                'code' => '<?php
                    /**
                     * @param array<int> $mappings
                     */
                    function foo(string $id, array $mappings) : void {
                        if ($id === "a") {
                            foreach ($mappings as $value) {
                                if (rand(0, 1)) {
                                    $id = $value;
                                    continue;
                                }
                            }
                        }

                        if (is_int($id)) {}
                    }',
            ],
            'loopVarRedefinedAtLoopStart' => [
                'code' => '<?php
                    /**
                     * @param non-empty-array<string, string> $files
                     */
                    function foo(array $files): void
                    {
                        $file = reset($files);
                        foreach ($files as $file) {
                            strlen($file);
                            $file = 0;
                        }
                    }',
            ],
            'arrayIsNotEmptyInForeachLoop' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return non-empty-array */
                    function f(array $a): array {
                        foreach ($a as $_) {
                            return $a;
                        }
                        throw new RuntimeException;
                    }
                    PHP,
            ],
            'generatorWithUnspecifiedSend' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return Generator<int,int> */
                    function gen() : Generator {
                        return yield 1;
                    }
                    $gen = gen();
                    foreach ($gen as $i) {}
                PHP,
            ],
            'generatorWithMixedSend' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return Generator<int,int, mixed, mixed> */
                    function gen() : Generator {
                        return yield 1;
                    }
                    $gen = gen();
                    foreach ($gen as $i) {}
                PHP,
            ],
            'nullableGenerator' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return Generator<int,int|null> */
                    function gen() : Generator {
                        yield null;
                        yield 1;
                    }
                    $gen = gen();
                    $a = "";
                    foreach ($gen as $i) {
                        $a = $i;
                    }
                PHP,
                'assertions' => [
                    '$a===' => "''|int|null",
                ],
            ],
            'nonNullableGenerator' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return Generator<int,int> */
                    function gen() : Generator {
                        yield 1;
                    }
                    $gen = gen();
                    $a = "";
                    foreach ($gen as $i) {
                $a = $i;
                    }
                PHP,
                'assertions' => [
                    '$a===' => "''|int",
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'switchVariableWithContinueOnce' => [
                'code' => '<?php
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
                'code' => '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $array[] = "hello";
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - Possibly undefined ' .
                    'global variable $array, first seen on line 3',
            ],
            'possibleUndefinedVariableInForeachAndIfWithBreak' => [
                'code' => '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                            break;
                        }
                    }

                    echo $a;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:9:26 - Possibly undefined ' .
                    'global variable $a, first seen on line 4',
            ],
            'possibleUndefinedVariableInForeachAndIf' => [
                'code' => '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                        }

                        echo $a;
                    }',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:7:30 - Possibly undefined ' .
                    'global variable $a, first seen on line 4',
            ],
            'implicitFourthLoopWithBadReturnType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
            'possiblyUndefinedVariableInForeachDueToBreakBefore' => [
                'code' => '<?php
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
                'code' => '<?php
                    continue;',
                'error_message' => 'ContinueOutsideLoop',
            ],
            'invalidIterator' => [
                'code' => '<?php
                    foreach (5 as $a) {

                    }',
                'error_message' => 'InvalidIterator',
            ],
            'rawObjectIteration' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    $arr = new A;

                    foreach ($arr as $a) {}',
                'error_message' => 'RawObjectIteration',
            ],
            'possibleRawObjectIteration' => [
                'code' => '<?php
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
                'error_message' => 'PossibleRawObjectIteration',
            ],
            'possibleRawObjectIterationFromIsset' => [
                'code' => '<?php
                    function foo(array $a) : void {
                        if (isset($a["a"]["b"])) {
                            foreach ($a["a"] as $c) {}
                        }
                    }',
                'error_message' => 'PossibleRawObjectIteration',
            ],
            'ifSpecificNonEmptyValues' => [
                'code' => '<?php
                    foreach ([1, 2, 3] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
                'error_message' => 'RedundantCondition',
            ],
            'ifSpecificNonEmptyStringValues' => [
                'code' => '<?php
                    foreach (["1", "2", "3"] as $i) {
                        $a = $i;
                    }

                    if ($a) {}',
                'error_message' => 'RedundantCondition',
            ],
            'arrayCanBeEmptyOutsideTheLoop' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return non-empty-array */
                    function f(array $a): array {
                        foreach ($a as $_) {
                        }
                        return $a;
                    }
                    PHP,
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'generatorWithNonNullableSend' => [
                'code' => <<<'PHP'
                    <?php
                    /** @return Generator<int,int,string,string> */
                    function gen() : Generator {
                        return yield 1;
                    }
                    $gen = gen();
                    foreach ($gen as $i) {}
                PHP,
                'error_message' => 'InvalidIterator',
            ],
        ];
    }
}
