<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class TypeAlgebraTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'twoVarLogicSimple' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a !== null || $b !== null) {
                            if ($a !== null) {
                                return $a;
                            } else {
                                return $b;
                            }
                        }

                        return "foo";
                    }',
            ],
            'threeVarLogic' => [
                'code' => '<?php
                    function takesString(string $s): void {}

                    function foo(?string $a, ?string $b, ?string $c): void {
                        if ($a !== null || $b !== null || $c !== null) {
                            if ($a !== null) {
                                $d = $a;
                            } elseif ($b !== null) {
                                $d = $b;
                            } else {
                                $d = $c;
                            }

                            takesString($d);
                        }
                    }',
            ],
            'twoVarLogicNotNestedSimple' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!$a && !$b) return "bad";
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'twoVarLogicNotNestedWithAllPathsReturning' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!$a && !$b) {
                            return "bad";
                        } else {
                            if (!$a) {
                                return $b;
                            } else {
                                return $a;
                            }
                        }
                    }',
            ],
            'twoVarLogicNotNestedWithAssignmentBeforeReturn' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!$a && !$b) {
                            $a = 5;
                            return "bad";
                        }

                        if (!$a) {
                            $a = 7;
                            return $b;
                        }

                        return $a;
                    }',
            ],
            'invertedTwoVarLogicNotNested' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a || $b) {
                            // do nothing
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'invertedTwoVarLogicNotNestedWithAssignmentBeforeReturn' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a || $b) {
                            // do nothing
                        } else {
                            $a = 5;
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'twoVarLogicNotNestedWithElseifAndNoNegations' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'threeVarLogicNotNestedWithNoRedefinitionsWithClasses' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b, ?stdClass $c): stdClass {
                        if ($a) {
                            // do nothing
                        } elseif ($b) {
                            // do nothing here
                        } elseif ($c) {
                            // do nothing here
                        } else {
                            return new stdClass;
                        }

                        if (!$a && !$b) {
                            return $c;
                        }
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'threeVarLogicNotNestedWithNoRedefinitionsWithStrings' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b) {
                            // do nothing here
                        } elseif ($c) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a && !$b) return $c;
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'threeVarLogicNotNestedAndOrWithNoRedefinitions' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b || $c) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a && !$b) return $c;
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'twoVarLogicNotNestedWithElseifCorrectlyNegatedInElseIf' => [
                'code' => '<?php
                    function foo(string $a, string $b): string {
                        if ($a) {
                            // do nothing here
                        } elseif ($b) {
                            $a = null;
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'nestedReassignment' => [
                'code' => '<?php
                    function foo(?string $a): void {
                        if ($a === null) {
                            $a = "blah-blah";
                        } else {
                            $a = rand(0, 1) ? "blah" : null;

                            if ($a === null) {

                            }
                        }
                    }',
            ],
            'twoVarLogicNotNestedWithElseifCorrectlyReinforcedInIf' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(?A $a, ?A $b): A {
                        if ($a) {
                            $a = new B;
                        } elseif ($b) {
                            // do nothing
                        } else {
                            return new A;
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'differentValueChecks' => [
                'code' => '<?php
                    function foo(string $a): void {
                        if ($a === "foo") {
                            // do something
                        } elseif ($a === "bar") {
                            // can never get here
                        }
                    }',
            ],
            'byRefAssignment' => [
                'code' => '<?php
                    function foo(): void {
                        preg_match("/hello/", "hello molly", $matches);

                        if (!$matches) {
                            return;
                        }

                        preg_match("/hello/", "hello dolly", $matches);

                        if (!$matches) {

                        }
                    }',
            ],
            'orConditionalAfterAndConditional' => [
                'code' => '<?php
                    function foo(string $a, string $b): void {
                        if ($a && $b) {
                            echo "a";
                        } elseif ($a || $b) {
                            echo "b";
                        }
                    }',
            ],
            'issetOnOneStringAfterAnother' => [
                'code' => '<?php
                    /** @param string[] $arr */
                    function foo(array $arr): void {
                        $a = "a";

                        if (!isset($arr[$a])) {
                            return;
                        }

                        foreach ([0, 1, 2, 3] as $i) {
                            if (!isset($arr[$a . $i])) {
                                echo "a";
                            }

                            $a = "hello";
                        }
                    }',
            ],
            'issetArrayCreation' => [
                'code' => '<?php
                    $arr = [];

                    foreach ([0, 1, 2, 3] as $i) {
                        $a = rand(0, 1) ? 5 : "010";

                        if (!isset($arr[(int) $a])) {
                            $arr[(int) $a] = 5;
                        } else {
                            $arr[(int) $a] += 4;
                        }
                    }',
            ],
            'moreConvolutedArrayCreation' => [
                'code' => '<?php
                    function fetchRow() : array {
                        return ["c" => "UK"];
                    }

                    $arr = [];

                    foreach ([1, 2, 3] as $i) {
                        $row = fetchRow();

                        if (!isset($arr[$row["c"]])) {
                            $arr[$row["c"]] = 0;
                        }

                        $arr[$row["c"]] = 1;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayOffset'],
            ],
            'moreConvolutedNestedArrayCreation' => [
                'code' => '<?php
                    function fetchRow() : array {
                        return ["c" => "UK"];
                    }

                    $arr = [];

                    foreach ([1, 2, 3] as $i) {
                        $row = fetchRow();

                        if (!isset($arr[$row["c"]]["foo"])) {
                            $arr[$row["c"]]["foo"] = 0;
                        }

                        $arr[$row["c"]]["foo"] = 1;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayOffset'],
            ],
            'noParadoxInLoop' => [
                'code' => '<?php
                    function paradox2(): void {
                        $condition = rand() % 2 > 0;

                        if (!$condition) {
                            foreach ([1, 2] as $value) {
                                if ($condition) { }
                                $condition = true;
                            }
                        }
                    }',
            ],
            'noParadoxInListAssignment' => [
                'code' => '<?php
                    function foo(string $a): void {
                        if (!$a) {
                            list($a) = explode(":", "a:b");

                            if ($a) { }
                        }
                    }',
            ],
            'noParadoxAfterAssignment' => [
                'code' => '<?php
                    function get_bool(): bool {
                        return rand() % 2 > 0;
                    }

                    function leftover(): bool {
                        $res = get_bool();
                        if ($res === false) {
                            return true;
                        }
                        $res = get_bool();
                        if ($res === false) {
                            return false;
                        }
                        return true;
                    }',
            ],
            'noParadoxAfterArrayAppending' => [
                'code' => '<?php
                    /** @return array|false */
                    function array_append(array $errors) {
                        if ($errors) {
                            return $errors;
                        }
                        if (rand() % 2 > 0) {
                            $errors[] = "unlucky";
                        }
                        if ($errors) {
                            return false;
                        }
                        return $errors;
                    }',
            ],
            'noParadoxInCatch' => [
                'code' => '<?php
                    function maybe_returns_array(): ?array {
                        if (rand() % 2 > 0) {
                            return ["key" => "value"];
                        }
                        if (rand() % 3 > 0) {
                            throw new Exception("An exception occurred");
                        }
                        return null;
                    }

                    function try_catch_check(): array {
                        $arr = null;
                        try {
                            $arr = maybe_returns_array();
                            if (!$arr) { return [];  }
                        } catch (Exception $e) {
                            if (!$arr) { return []; }
                        }
                        return $arr;
                    }',
            ],
            'lotsaTruthyStatements' => [
                'code' => '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      if (($obj->a !== null) == true) {
                        return $obj->a; // definitely not null
                      } elseif (!is_null($obj->b) == true) {
                        return $obj->b;
                      } else {
                        throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
                    }',
            ],
            'lotsaFalsyStatements' => [
                'code' => '<?php
                    class A {
                       /**
                        * @var ?string
                        */
                       public $a = null;
                       /**
                        * @var ?string
                        */
                       public $b = null;
                    }
                    function f(A $obj): string {
                      if (($obj->a === null) == false) {
                        return $obj->a; // definitely not null
                      } elseif (is_null($obj->b) == false) {
                        return $obj->b;
                      } else {
                        throw new \InvalidArgumentException("$obj->a or $obj->b must be set");
                      }
                    }',
            ],
            'ifGetClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                      public function foo(): void {}
                    }

                    function takesA(A $a): void {
                      if (get_class($a) === B::class) {
                        $a->foo();
                      }
                    }',
            ],
            'ifNotEqualsGetClass' => [
                'code' => '<?php
                    class A {}
                    class B extends A {
                      public function foo(): void {}
                    }

                    function takesA(A $a): void {
                      if (get_class($a) !== B::class) {
                        // do nothing
                      } else {
                        $a->foo();
                      }
                    }',
            ],
            'nestedCheckWithSingleVarPerLevel' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b): void {
                        if ($a) {
                            if ($b) {}
                        }
                    }',
            ],
            'nestedCheckWithTwoVarsPerLevel' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b, ?stdClass $c, ?stdClass $d): void {
                        if ($a && $b) {
                            if ($c && $d) {}
                        }
                    }',
            ],
            'nestedCheckWithReturn' => [
                'code' => '<?php
                    function foo(?stdClass $a, ?stdClass $b): void {
                        if ($a === null) {
                            return;
                        }

                        if ($b) {
                            echo "hello";
                        }
                    }',
            ],
            'propertyFetchAfterNotNullCheck' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    $a = new A;

                    if ($a->foo === null) {
                        $a->foo = "hello";
                        exit;
                    }

                    if ($a->foo === "somestring") {}',
            ],
            'noParadoxForGetopt' => [
                'code' => '<?php
                    $options = getopt("t:");

                    try {
                        if (!isset($options["t"])) {
                            throw new Exception("bad");
                        }
                    } catch (Exception $e) {}',
            ],
            'instanceofInOr' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a): void {
                        if ($a instanceof B
                            || ($a instanceof C && rand(0, 1))
                        ) {
                            takesA($a);
                        }
                    }',
            ],
            'instanceofInOrNegated' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?A $b, ?A $c): void {
                        if (!$a || ($b && $c)) {
                            return;
                        }

                        takesA($a);
                    }',
            ],
            'instanceofInBothOrs' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a): void {
                        if (($a instanceof B && rand(0, 1))
                            || ($a instanceof C && rand(0, 1))
                        ) {
                            takesA($a);
                        }
                    }',
            ],
            'instanceofInBothOrsWithSecondVar' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?A $b): void {
                        if (($a instanceof B && $b instanceof B)
                            || ($a instanceof C && $b instanceof C)
                        ) {
                            takesA($a);
                            takesA($b);
                        }
                    }',
            ],
            'explosionOfCNF' => [
                'code' => '<?php
                    class A {
                        /** @var ?string */
                        public $foo;

                        /** @var ?string */
                        public $bar;
                    }

                    $a1 = rand(0, 1) ? new A() : null;
                    $a4 = rand(0, 1) ? new A() : null;
                    $a5 = rand(0, 1) ? new A() : null;
                    $a7 = rand(0, 1) ? new A() : null;
                    $a8 = rand(0, 1) ? new A() : null;

                    if ($a1 || (($a4 && $a5) || ($a7 && $a8))) {}',
            ],
            'instanceofInCNFOr' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    function takesA(A $a): void {}

                    function foo(?A $a): void {
                        $c = rand(0, 1);
                        if (($a instanceof B || $a instanceof C)
                            && ($a instanceof B || $c)
                        ) {
                            takesA($a);
                        }
                    }',
            ],
            'reconcileNestedOrsInElse' => [
                'code' => '<?php
                    class A {}
                    class B {}

                    function takesA(A $a): void {}

                    function foo(?A $a, ?B $b): void {
                        if ($a === null || $b === null || rand(0, 1)) {
                            // do nothing
                        } else {
                            takesA($a);
                        }
                    }',
            ],
            'getClassComparison' => [
                'code' => '<?php
                    class Foo {
                        public function bar() : void {}
                    }
                    class Bar extends Foo{
                        public function bar() : void {}
                    }

                    class Baz {
                        public function test(Foo $foo) : void {
                            if (get_class($foo) !== Foo::class) {
                                // do nothing
                            } else {
                                $foo->bar();
                            }
                        }
                    }',
            ],
            'callWithNonNullInTernary' => [
                'code' => '<?php
                    function sayHello(?int $a, ?int $b): void {
                        if ($a === null && $b === null) {
                            throw new \LogicException();
                        }

                        takesInt($a !== null ? $a : $b);
                    }

                    function takesInt(int $c) : void {}',
            ],
            'callWithNonNullInIf' => [
                'code' => '<?php
                    function sayHello(?int $a, ?int $b): void {
                        if ($a === null && $b === null) {
                            throw new \LogicException();
                        }

                        if ($a !== null) {
                            takesInt($a);
                        } else {
                            takesInt($b);
                        }
                    }

                    function takesInt(int $c) : void {}',
            ],
            'callWithNonNullInIfWithCallInElseif' => [
                'code' => '<?php
                    function sayHello(?int $a, ?int $b): void {
                        if ($a === null && $b === null) {
                            throw new \LogicException();
                        }

                        if ($a !== null) {
                            takesInt($a);
                        } elseif (rand(0, 1)) {
                            takesInt($b);
                        }
                    }

                    function takesInt(int $c) : void {}',
            ],
            'typeSimplification' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(A $a, A $b) : ?B {
                        if (($a instanceof B || !$b instanceof B) && $a instanceof B && $b instanceof B) {
                            return $a;
                        }

                        return null;
                    }',
            ],
            'instanceofNoRedundant' => [
                'code' => '<?php
                    function logic(Foo $a, Foo $b) : void {
                        if ((!$a instanceof Bat || !$b instanceof Bat)
                            && (!$a instanceof Bat || !$b instanceof Bar)
                            && (!$a instanceof Bar || !$b instanceof Bat)
                            && (!$a instanceof Bar || !$b instanceof Bar)
                        ) {

                        } else {
                            if ($b instanceof Bat) {}
                        }
                    }

                    class Foo {}
                    class Bar extends Foo {}
                    class Bat extends Foo {}',
            ],
            'explicitValuesInOrIf' => [
                'code' => '<?php
                    $s = rand(0, 1) ? "a" : "b";

                    if (($s === "a" && rand(0, 1)) || ($s === "b" && rand(0, 1))) {}',
            ],
            'explicitValuesInOrTernary' => [
                'code' => '<?php
                    $s = rand(0, 1) ? "a" : "b";

                    $a = (($s === "a" && rand(0, 1)) || ($s === "b" && rand(0, 1))) ? 1 : 0;',
            ],
            'boolComparison' => [
                'code' => '<?php
                    $a = (bool) rand(0, 1);

                    if (rand(0, 1)) {
                        $a = null;
                    }

                    if ($a !== (bool) rand(0, 1)) {
                        echo $a === false ? "a" : "b";
                    }',
            ],
            'stringConcatenationTrackedValid' => [
                'code' => '<?php
                    $x = "a";
                    $x = "_" . $x;
                    $array = [$x => 2];
                    echo $array["_a"];',
            ],
            'noMemoryIssueWithLongConditional' => [
                'code' => '<?php

                    function foo(int $c) : string {
                        if (!($c >= 0x5be && $c <= 0x10b7f)) {
                            return "LTR";
                        }

                        if ($c <= 0x85e) {
                            if ($c === 0x5be ||
                                $c === 0x5c0 ||
                                $c === 0x5c3 ||
                                $c === 0x5c6 ||
                                ($c >= 0x5d0 && $c <= 0x5ea) ||
                                ($c >= 0x5f0 && $c <= 0x5f4) ||
                                $c === 0x608 ||
                                ($c >= 0x712 && $c <= 0x72f) ||
                                ($c >= 0x74d && $c <= 0x7a5) ||
                                $c === 0x7b1 ||
                                ($c >= 0x7c0 && $c <= 0x7ea) ||
                                ($c >= 0x7f4 && $c <= 0x7f5) ||
                                $c === 0x7fa ||
                                ($c >= 0x800 && $c <= 0x815) ||
                                $c === 0x81a ||
                                $c === 0x824 ||
                                $c === 0x828 ||
                                ($c >= 0x830 && $c <= 0x83e) ||
                                ($c >= 0x840 && $c <= 0x858) ||
                                $c === 0x85e
                            ) {
                                return "RTL";
                            }
                        } elseif ($c === 0x200f) {
                            return "RTL";
                        } elseif ($c >= 0xfb1d) {
                            if ($c === 0xfb1d ||
                                ($c >= 0xfb1f && $c <= 0xfb28) ||
                                ($c >= 0xfb2a && $c <= 0xfb36) ||
                                ($c >= 0xfb38 && $c <= 0xfb3c) ||
                                $c === 0xfb3e ||
                                ($c >= 0x10a10 && $c <= 0x10a13) ||
                                ($c >= 0x10a15 && $c <= 0x10a17) ||
                                ($c >= 0x10a19 && $c <= 0x10a33) ||
                                ($c >= 0x10a40 && $c <= 0x10a47) ||
                                ($c >= 0x10a50 && $c <= 0x10a58) ||
                                ($c >= 0x10a60 && $c <= 0x10a7f) ||
                                ($c >= 0x10b00 && $c <= 0x10b35) ||
                                ($c >= 0x10b40 && $c <= 0x10b55) ||
                                ($c >= 0x10b58 && $c <= 0x10b72) ||
                                ($c >= 0x10b78 && $c <= 0x10b7f)
                            ) {
                                return "RTL";
                            }
                        }

                        return "LTR";
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedInferredReturnType'],
            ],
            'grandParentInstanceofConfusion' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}
                    class C extends B {}

                    function bad(A $x) : void {
                        if (($x instanceof C && rand(0, 1)) || rand(0, 1)) {
                            return;
                        }

                        if ($x instanceof B) {
                            if ($x instanceof C) {}
                        }
                    }',
            ],
            'invertEquation' => [
                'code' => '<?php
                    /**
                     * @param mixed $width
                     * @param mixed $height
                     *
                     * @throws RuntimeException
                     */
                    function Foo($width, $height) : void {
                        if (!(is_int($width) || is_float($width)) || !(is_int($height) || is_float($height))) {
                            throw new RuntimeException("bad");
                        }

                        echo sprintf("padding-top:%s%%;", 100 * ($height/$width));
                    }',
            ],
            'invertLogic' => [
                'code' => '<?php
                    class A {}
                    class B extends A {}

                    function foo(?A $a) : A {
                        if (!$a || !($a instanceof B && rand(0, 1))) {
                            throw new Exception();
                        }

                        return $a;
                    }',
            ],
            'allowAssertionInElseif' => [
                'code' => '<?php
                class X {
                    public bool $a = false;
                    public bool $b = false;
                    public bool $c = false;
                }

                function foo(X $x) : void {
                    $a = false;
                    if ($x->b && $x->a) {
                    } elseif ($x->c) {
                        $a = true;
                    }

                    if ($x->c) {}
                    if ($a) {}
                }',
            ],
            'twoVarChangeInElseOnly' => [
                'code' => '<?php
                    class A {
                        public function takesA(A $a) : void {}

                        public function foo() : void {}
                    }

                    function formatRange(?A $from, ?A $to): void {
                        if (!$to && !$from) {
                            $to = new A();
                            $from = new A();
                        } elseif (!$from) {
                            $from = new A();
                            $from->takesA($to);
                        } else {
                            if (!$to) {
                                $to = new A();
                                $to->takesA($from);
                            }
                        }

                        $from->foo();
                        $to->foo();
                    }',
            ],
            'twoVarChangeInElseif' => [
                'code' => '<?php
                    class A {
                        public function takesA(A $a) : void {}

                        public function foo() : void {}
                    }

                    function formatRange(?A $from, ?A $to): void {
                        if (!$to && !$from) {
                            $to = new A();
                            $from = new A();
                        } elseif (!$from) {
                            $from = new A();
                            $from->takesA($to);
                        } elseif (!$to) {
                            $to = new A();
                            $to->takesA($from);
                        }

                        $from->foo();
                        $to->foo();
                    }',
            ],
            'testSimplishThing' => [
                'code' => '<?php
                    function foo(
                        bool $a,
                        bool $b,
                        bool $c,
                        bool $d,
                        bool $e,
                        bool $f,
                        bool $g,
                        bool $h,
                        bool $i,
                        bool $j
                    ): bool {
                        return ($a && $b)
                            || ($c && $d)
                            || ($e && $f)
                            || ($g && $h)
                            || ($i && $j);
                    }',
            ],
            'fineCheck' => [
                'code' => '<?php
                    function foo(bool $b, bool $c) : void {
                        if ((!$b || rand(0, 1)) && (!$c || rand(0, 1))) {}
                    }',
            ],
            'noParadoxInTernary' => [
                'code' => '<?php
                    function foo(?bool $b) : string {
                        return $b ? "a" : ($b === null ? "foo" : "b");
                    }',
            ],
            'cancelOutSameStatement' => [
                'code' => '<?php
                    function edit(?string $a, ?string $b): string {
                        if ((!$a && !$b) || ($a && !$b)) {
                            return "";
                        }

                        return $b;
                    }',
            ],
            'cancelOutDifferentStatement' => [
                'code' => '<?php
                    function edit(?string $a, ?string $b): string {
                        if (!$a && !$b) {
                            return "";
                        }

                        if ($a && !$b) {
                            return "";
                        }

                        return $b;
                    }',
            ],
            'moreChecks' => [
                'code' => '<?php
                    class B {}
                    class C {}

                    function foo(?B $b, ?C $c): B|C {
                        if (!$b && !$c) {
                            throw new Exception("bad");
                        }

                        if ($b && $c) {
                            return rand(0, 1) ? $b : $c;
                        }

                        if ($b) {
                            return $b;
                        }

                        return $c;
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'dependentType' => [
                'code' => '<?php
                    class A {
                        public function isValid() : bool {
                            return (bool) rand(0, 1);
                        }

                        public function foo() : void {}
                    }

                    function takesA(?A $a) : void {
                        $is_valid_a = $a && $a->isValid();

                        if ($is_valid_a) {
                            $a->foo();
                        }
                    }',
            ],
            'assignSameName' => [
                'code' => '<?php
                    function foo(string $value): string {
                        $value = "yes" === $value;
                        return !$value ? "foo" : "bar";
                    }',
            ],
            'dependentTypeUsedAfterCall' => [
                'code' => '<?php
                    function a(string $_b): void {}

                    function foo(?string $c): string {
                        $iftrue = $c !== null;

                        if ($c !== null) {
                            a($c);
                        }

                        if ($iftrue) {
                            return $c;
                        }

                        return "";
                    }',
            ],
            'notNullAfterSuccessfulNullsafeMethodCall' => [
                'code' => '<?php
                    interface X {
                        public function a(): bool;
                        public function b(): string;
                    }

                    function foo(?X $x): void {
                        if ($x?->a()) {
                            echo $x->b();
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'narrowedTypeAfterIdenticalCheckWithOtherType' => [
                'code' => '<?php
                    function a(int $a, ?int $b = null): void
                    {
                        if ($a === $b) {
                            throw new InvalidArgumentException(sprintf("a can not be the same as b (b: %s).", $b));
                        }
                    }',
            ],
            'ThrowableInstanceOfThrowableMayBeFalse' => [
                'code' => '<?php

                    final class Handler
                    {
                        /**
                         * @var class-string<Throwable>[]
                         */
                        private array $dontReport = [];

                        /**
                         * @param class-string<Throwable> $throwable
                         */
                        public function dontReport(string $throwable): void
                        {
                            $this->dontReport[] = $throwable;
                        }

                        public function shouldReport(Throwable $t): bool
                        {
                            foreach ($this->dontReport as $tc) {
                                if ($t instanceof $tc) {
                                    return false;
                                }
                            }

                            return true;
                        }
                    }

                    $h = new Handler();
                    $h->dontReport(RuntimeException::class);

                    $h->shouldReport(new Exception());
                    $h->shouldReport(new RuntimeException());',
            ],
            'ThrowableInstanceOfThrowableMayBeTrue' => [
                'code' => '<?php

                    class Mapper
                    {
                        /** @param class-string<Throwable> $class */
                        final public function map(Throwable $throwable, string $class): ?Throwable
                        {
                            if (! $throwable instanceof $class) {
                                return null;
                            }

                            return $throwable;
                        }
                    }',
            ],
            'combineTwoOrredClausesWithUnnecessaryTerm' => [
                'code' => '<?php
                    function foo(bool $a, bool $b, bool $c): void {
                        if (($a && $b) || (!$a && $c)) {
                            //
                        } else {
                            if ($c) {}
                        }
                    }',
            ],
            'combineTwoOrredClausesWithMoreComplexUnnecessaryTerm' => [
                'code' => '<?php
                    function foo(bool $a, bool $b, bool $c): void {
                        if ((!$a && !$b) || ($a && $b) || ($a && $c)) {
                            throw new \Exception();
                        }

                        if ($a) {}
                    }',
            ],
            'compareToIntInsideIfDNF' => [
                'code' => '<?php
                    function foo(?int $foo): void {
                        if (($foo && $foo !== 5) || (!$foo && rand(0,1))) {
                            return;
                        }

                        if ($foo === null) {}
                    }',
            ],
            'compareToIntInsideIfCNF' => [
                'code' => '<?php
                    function baz(?int $foo): void {
                        if (
                            (!$foo || $foo !== 5) && ($foo || rand(0,1)) && ($foo !== 5 || rand(0, 1))
                        ) {
                            return;
                        }

                        if ($foo === null) {}
                    }',
            ],
            'ternaryAssertionOnBool' => [
                'code' => '<?php
                    function test(string|object $s, bool $b) : string {
                        if (!$b || is_string($s)) {
                            return $b ? $s : "";
                        }
                        return "";
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.0',
            ],
            'subclassAfterNegation' => [
                'code' => '<?php
                    abstract class Base {}
                    class A extends Base {}
                    class AChild extends A {}
                    class B extends Base {
                        public string $s = "";
                    }

                    function foo(Base $base): void {
                        if (!$base instanceof A || $base instanceof AChild) {
                            if ($base instanceof B && rand(0, 1)) {
                                echo $base->s;
                            }
                        }
                    }',
            ],
            'subclassAfterElseifNegation' => [
                'code' => '<?php
                    abstract class Base {}
                    class A extends Base {}
                    class AChild extends A {}
                    class B extends Base {
                        public string $s = "";
                    }

                    function foo(Base $base): void {
                        if ($base instanceof A && !($base instanceof AChild)) {
                            // do nothing
                        } elseif ($base instanceof B && rand(0, 1)) {
                            echo $base->s;
                        }
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'threeVarLogicWithChange' => [
                'code' => '<?php
                    function takesString(string $s): void {}

                    function foo(?string $a, ?string $b, ?string $c): void {
                        if ($a !== null || $b !== null || $c !== null) {
                            $c = null;

                            if ($a !== null) {
                                $d = $a;
                            } elseif ($b !== null) {
                                $d = $b;
                            } else {
                                $d = $c;
                            }

                            takesString($d);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'threeVarLogicWithException' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): void {
                        if ($a !== null || $b !== null || $c !== null) {
                            if ($c !== null) {
                                throw new \Exception("bad");
                            }

                            if ($a !== null) {
                                $d = $a;
                            } elseif ($b !== null) {
                                $d = $b;
                            } else {
                                $d = $c;
                            }
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'invertedTwoVarLogicNotNestedWithVarChange' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a !== null || $b !== null) {
                            $b = null;
                        } else {
                            return "bad";
                        }

                        if ($a !== null) return $b;
                        return $a;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'invertedTwoVarLogicNotNestedWithElseif' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if (rand(0, 1)) {
                            // do nothing
                        } elseif ($a || $b) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'NullableReturnStatement',
            ],
            'threeVarLogicWithElseifAndAnd' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b, ?string $c): string {
                        if ($a) {
                            // do nothing
                        } elseif ($b && $c) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a && !$b) return $c;
                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'twoVarLogicNotNestedWithElseifIncorrectlyReinforcedInIf' => [
                'code' => '<?php
                    function foo(?string $a, ?string $b): string {
                        if ($a) {
                            $a = "";
                        } elseif ($b) {
                            // do nothing
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'repeatedIfStatements' => [
                'code' => '<?php
                    /** @return string|null */
                    function foo(?string $a) {
                        if ($a) {
                            return $a;
                        }

                        if ($a) {

                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'repeatedConditionals' => [
                'code' => '<?php
                    function foo(?object $a): void {
                        if ($a) {
                            // do something
                        } elseif ($a) {
                            // can never get here
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'repeatedAndConditional' => [
                'code' => '<?php
                    class C {}
                    function foo(?C $a, ?C $b): void {
                        if ($a && $b) {
                            echo "a";
                        } elseif ($a && $b) {
                            echo "b";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'andConditionalAfterOrConditional' => [
                'code' => '<?php
                    function foo(string $a, string $b): void {
                        if ($a || $b) {
                            echo "a";
                        } elseif ($a && $b) {
                            echo "b";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'repeatedVarFromOrConditional' => [
                'code' => '<?php
                    function foo(string $a, string $b): void {
                        if ($a || $b) {
                            echo "a";
                        } elseif ($a) {
                            echo "b";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'typeDoesntEqualType' => [
                'code' => '<?php
                    $a = "hello";
                    $b = 5;
                    if ($a !== $b) {}',
                'error_message' => 'RedundantCondition',
            ],
            'stringConcatenationTrackedInvalid' => [
                'code' => '<?php
                    $x = "a";
                    $x = "_" . $x;
                    $array = [$x => 2];
                    echo $array["other"];',
                'error_message' => 'InvalidArrayOffset',
            ],
            'redundantTwoVarInElseif' => [
                'code' => '<?php
                    class A {}

                    $from = rand(0, 1) ? new A() : null;
                    $to = rand(0, 1) ? new A() : null;

                    if ($from === null && $to === null) {
                    } elseif ($from !== null) {
                    } elseif ($to !== null) {}',
                'error_message' => 'RedundantCondition',
            ],
            'paradoxInTernary' => [
                'code' => '<?php
                    function foo(string $input) : string {
                        return $input === "a" ? "bar" : ($input === "a" ? "foo" : "b");
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'mismatchingChecks' => [
                'code' => '<?php
                    function doesntFindBug(?string $old, ?string $new): void {
                        if (empty($old) && empty($new)) {
                            return;
                        }

                        if (($old && empty($new)) || ($new && empty($old))) {
                            return;
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'dependentTypeInvalidated' => [
                'code' => '<?php
                    class A {
                        public function isValid() : bool {
                            return (bool) rand(0, 1);
                        }

                        public function foo() : void {}
                    }

                    function takesA(?A $a) : void {
                        $is_valid_a = $a && $a->isValid();

                        if (rand(0, 1)) {
                            $is_valid_a = false;
                        }

                        if ($is_valid_a) {
                            $a->foo();
                        }
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
            'stillNullAfterNullsafeMethodCall' => [
                'code' => '<?php
                    interface X {
                        public function a(): bool;
                        public function b(): string;
                    }

                    function foo(?X $x): void {
                        if (!($x?->a())) {
                            echo $x->b();
                        }
                    }',
                'error_message' => 'NullReference',
                'ignored_issues' => [],
                'php_version' => '8.1',
            ],
            'arrayShapeListCanBeEmpty' => [
                'code' => '<?php
                    /** @param non-empty-list<mixed> $_list */
                    function foobar(array $_list): void {}

                    $list = random_int(0, 1) ? [] : ["foobar"];

                    foobar($list);
                ',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
