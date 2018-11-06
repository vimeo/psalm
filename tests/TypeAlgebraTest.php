<?php
namespace Psalm\Tests;

class TypeAlgebraTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'twoVarLogic' => [
                '<?php
                    function takesString(string $s): void {}

                    function foo(?string $a, ?string $b): void {
                        if ($a !== null || $b !== null) {
                            if ($a !== null) {
                                $c = $a;
                            } else {
                                $c = $b;
                            }

                            takesString($c);
                        }
                    }',
            ],
            'threeVarLogic' => [
                '<?php
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
            'twoVarLogicNotNested' => [
                '<?php
                    function foo(?string $a, ?string $b): string {
                        if (!$a && !$b) return "bad";
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'twoVarLogicNotNestedWithAllPathsReturning' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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

                        if (!$a && !$b) return $c;
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'threeVarLogicNotNestedWithNoRedefinitionsWithStrings' => [
                '<?php
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
                '<?php
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
                '<?php
                    function foo(?string $a, ?string $b): string {
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
                '<?php
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
                '<?php
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
                '<?php
                    function foo(string $a): void {
                        if ($a === "foo") {
                            // do something
                        } elseif ($a === "bar") {
                            // can never get here
                        }
                    }',
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
            'byRefAssignment' => [
                '<?php
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
                '<?php
                    function foo(string $a, string $b): void {
                        if ($a && $b) {
                            echo "a";
                        } elseif ($a || $b) {
                            echo "b";
                        }
                    }',
            ],
            'issetOnOneStringAfterAnother' => [
                '<?php
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
                '<?php
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
                '<?php
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
                'error_levels' => ['MixedArrayOffset'],
            ],
            'moreConvolutedNestedArrayCreation' => [
                '<?php
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
                'error_levels' => ['MixedArrayOffset'],
            ],
            'noParadoxInLoop' => [
                '<?php
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
                '<?php
                    function foo(string $a): void {
                        if (!$a) {
                            list($a) = explode(":", "a:b");

                            if ($a) { }
                        }
                    }',
            ],
            'noParadoxAfterAssignment' => [
                '<?php
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
                '<?php
                    /** @return array|false */
                    function array_append2(array $errors) {
                        if ($errors) {
                            return $errors;
                        }
                        $errors[] = "deterministic";
                        if ($errors) {
                            return false;
                        }
                        return $errors;
                    }

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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class A {}
                    class B extends A {
                      public function foo(): void {}
                    }

                    function takesA(A $a): void {
                      if (get_class($a) === "B") {
                        $a->foo();
                      }
                    }',
            ],
            'ifNotEqualsGetClass' => [
                '<?php
                    class A {}
                    class B extends A {
                      public function foo(): void {}
                    }

                    function takesA(A $a): void {
                      if (get_class($a) !== "B") {
                        // do nothing
                      } else {
                        $a->foo();
                      }
                    }',
            ],
            'nestedCheckWithSingleVarPerLevel' => [
                '<?php
                    function foo(?stdClass $a, ?stdClass $b): void {
                        if ($a) {
                            if ($b) {}
                        }
                    }',
            ],
            'nestedCheckWithTwoVarsPerLevel' => [
                '<?php
                    function foo(?stdClass $a, ?stdClass $b, ?stdClass $c, ?stdClass $d): void {
                        if ($a && $b) {
                            if ($c && $d) {}
                        }
                    }',
            ],
            'nestedCheckWithReturn' => [
                '<?php
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
                '<?php
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
            'propertyFetchAfterNotNullCheckInElseif' => [
                '<?php
                    class A {
                        /** @var ?string */
                        public $foo;
                    }

                    if (rand(0, 10) > 5) {
                    } elseif (($a = new A) && $a->foo) {}',
            ],
            'noParadoxForGetopt' => [
                '<?php
                    $options = getopt("t:");

                    try {
                        if (!isset($options["t"])) {
                            throw new Exception("bad");
                        }
                    } catch (Exception $e) {}',
            ],
            'instanceofInOr' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'noParadoxAfterConditionalAssignment' => [
                '<?php
                    if ($a = rand(0, 5)) {
                        echo $a;
                    } elseif ($a = rand(0, 5)) {
                        echo $a;
                    }',
            ],
            'callWithNonNullInTernary' => [
                '<?php
                    function sayHello(?int $a, ?int $b): void {
                        if ($a === null && $b === null) {
                            throw new \LogicException();
                        }

                        takesInt($a !== null ? $a : $b);
                    }

                    function takesInt(int $c) : void {}',
            ],
            'callWithNonNullInIf' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
            'explicitValuesInOr' => [
                '<?php
                    $s = rand(0, 1) ? "a" : "b";

                    if (($s === "a" && rand(0, 1)) || ($s === "b" && rand(0, 1))) {}

                    $a = (($s === "a" && rand(0, 1)) || ($s === "b" && rand(0, 1))) ? 1 : 0;',
            ],
            'boolComparison' => [
                '<?php
                    $a = (bool) rand(0, 1);

                    if (rand(0, 1)) {
                        $a = null;
                    }

                    if ($a !== (bool) rand(0, 1)) {
                        echo $a === false ? "a" : "b";
                    }',
            ],
            'stringConcatenationTrackedValid' => [
                '<?php
                    $x = "a";
                    $x = "_" . $x;
                    $array = [$x => 2];
                    echo $array["_a"];',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'threeVarLogicWithChange' => [
                '<?php
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
                '<?php
                    function takesString(string $s): void {}

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

                            takesString($d);
                        }
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'invertedTwoVarLogicNotNestedWithVarChange' => [
                '<?php
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
                '<?php
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
                '<?php
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
                'error_message' => 'ParadoxicalCondition',
            ],
            'twoVarLogicNotNestedWithElseifIncorrectlyReinforcedInIf' => [
                '<?php
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
                '<?php
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
                '<?php
                    function foo(?string $a): void {
                        if ($a) {
                            // do something
                        } elseif ($a) {
                            // can never get here
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'repeatedAndConditional' => [
                '<?php
                    function foo(string $a, string $b): void {
                        if ($a && $b) {
                            echo "a";
                        } elseif ($a && $b) {
                            echo "b";
                        }
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'andConditionalAfterOrConditional' => [
                '<?php
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
                '<?php
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
                '<?php
                    $a = "hello";
                    $b = 5;
                    if ($a !== $b) {}',
                'error_message' => 'RedundantCondition',
            ],
            'stringConcatenationTrackedInvalid' => [
                '<?php
                    $x = "a";
                    $x = "_" . $x;
                    $array = [$x => 2];
                    echo $array["other"];',
                'error_message' => 'InvalidArrayOffset',
            ],
        ];
    }
}
