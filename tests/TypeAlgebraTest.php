<?php
namespace Psalm\Tests;

class TypeAlgebraTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'twoVarLogic' => [
                '<?php
                    function takesString(string $s) : void {}

                    function foo(?string $a, ?string $b) : void {
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
                    function takesString(string $s) : void {}

                    function foo(?string $a, ?string $b, ?string $c) : void {
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
                    function foo(?string $a, ?string $b) : string {
                        if (!$a && !$b) return "bad";
                        if (!$a) return $b;
                        return $a;
                    }',
            ],
            'twoVarLogicNotNestedWithAllPathsReturning' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
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
                    function foo(?string $a, ?string $b) : string {
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
                    function foo(?string $a, ?string $b) : string {
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
                    function foo(?string $a, ?string $b) : string {
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
            'twoVarLogicNotNestedWithElseif' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
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
            'threeVarLogicNotNested' => [
                '<?php
                    function foo(?string $a, ?string $b, ?string $c) : string {
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
            'threeVarLogicNotNestedAndOr' => [
                '<?php
                    function foo(?string $a, ?string $b, ?string $c) : string {
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
                    function foo(?string $a, ?string $b) : string {
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
                    function foo(?string $a) : void {
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

                    function foo(?A $a, ?A $b) : A {
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
                    function foo(string $a) : void {
                        if ($a === "foo") {
                            // do something
                        } elseif ($a === "bar") {
                            // can never get here
                        }
                    }',
            ],
            'repeatedSet' => [
                '<?php
                    function foo() : void {
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
                    function foo() : void {
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
                    function foo() : void {
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
                    function foo(string $a, string $b) : void {
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
                    function foo(array $arr) : void {
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
            'noParadoxInLoop' => [
                '<?php
                    function paradox2() : void {
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
                    function foo(string $a) : void {
                        if (!$a) {
                            list($a) = explode(":", "a:b");

                            if ($a) { }
                        }
                    }',
            ],
            'noParadoxAfterAssignment' => [
                '<?php
                    function get_bool() : bool {
                        return rand() % 2 > 0;
                    }

                    function leftover() : bool {
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
                    function maybe_returns_array() : ?array {
                        if (rand() % 2 > 0) {
                            return ["key" => "value"];
                        }
                        if (rand() % 3 > 0) {
                            throw new Exception("An exception occurred");
                        }
                        return null;
                    }

                    function try_catch_check() : array {
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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'threeVarLogicWithChange' => [
                '<?php
                    function takesString(string $s) : void {}

                    function foo(?string $a, ?string $b, ?string $c) : void {
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
                'error_message' => 'NullArgument',
            ],
            'threeVarLogicWithException' => [
                '<?php
                    function takesString(string $s) : void {}

                    function foo(?string $a, ?string $b, ?string $c) : void {
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
                'error_message' => 'NullArgument',
            ],
            'invertedTwoVarLogicNotNestedWithVarChange' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
                        if ($a || $b) {
                            $b = null;
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'invertedTwoVarLogicNotNestedWithElseif' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
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
                'error_message' => 'InvalidReturnType',
            ],
            'threeVarLogicWithElseifAndAnd' => [
                '<?php
                    function foo(?string $a, ?string $b, ?string $c) : string {
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
                'error_message' => 'InvalidReturnType',
            ],
            'twoVarLogicNotNestedWithElseifNegatedInIf' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
                        if ($a) {
                            $a = null;
                        } elseif ($b) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'twoVarLogicNotNestedWithElseifIncorrectlyReinforcedInIf' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
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
                'error_message' => 'InvalidReturnType',
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
                'error_message' => 'ParadoxicalCondition',
            ],
            'repeatedConditionals' => [
                '<?php
                    function foo(?string $a) : void {
                        if ($a) {
                            // do something
                        } elseif ($a) {
                            // can never get here
                        }
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'repeatedAndConditional' => [
                '<?php
                    function foo(string $a, string $b) : void {
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
                    function foo(string $a, string $b) : void {
                        if ($a || $b) {
                            echo "a";
                        } elseif ($a && $b) {
                            echo "b";
                        }
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
            'repeatedVarFromOrConditional' => [
                '<?php
                    function foo(string $a, string $b) : void {
                        if ($a || $b) {
                            echo "a";
                        } elseif ($a) {
                            echo "b";
                        }
                    }',
                'error_message' => 'ParadoxicalCondition',
            ],
        ];
    }
}
