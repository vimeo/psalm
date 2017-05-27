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
        ];
    }
}
