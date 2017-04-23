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
            'two-var-logic' => [
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
                    }'
            ],
            'three-var-logic' => [
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
                    }'
            ],
            'two-var-logic-not-nested' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
                        if (!$a && !$b) return "bad";
                        if (!$a) return $b;
                        return $a;
                    }'
            ],
            'two-var-logic-not-nested-with-all-paths-returning' => [
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
                    }'
            ],
            'two-var-logic-not-nested-with-assignment-before-return' => [
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
                    }'
            ],
            'inverted-two-var-logic-not-nested' => [
                '<?php
                    function foo(?string $a, ?string $b) : string {
                        if ($a || $b) {
                            // do nothing
                        } else {
                            return "bad";
                        }
            
                        if (!$a) return $b;
                        return $a;
                    }'
            ],
            'inverted-two-var-logic-not-nested-with-assignment-before-return' => [
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
                    }'
            ],
            'two-var-logic-not-nested-with-elseif' => [
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
                    }'
            ],
            'three-var-logic-not-nested' => [
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
                    }'
            ],
            'three-var-logic-not-nested-and-or' => [
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
                    }'
            ],
            'two-var-logic-not-nested-with-elseif-correctly-negated-in-else-if' => [
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
                    }'
            ],
            'nested-reassignment' => [
                '<?php
                    function foo(?string $a) : void {
                        if ($a === null) {
                            $a = "blah-blah";
                        } else {
                            $a = rand(0, 1) ? "blah" : null;
            
                            if ($a === null) {
            
                            }
                        }
                    }'
            ],
            'two-var-logic-not-nested-with-elseif-correctly-reinforced-in-if' => [
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
                    }'
            ],
            'different-value-checks' => [
                '<?php
                    function foo(string $a) : void {
                        if ($a === "foo") {
                            // do something
                        } elseif ($a === "bar") {
                            // can never get here
                        }
                    }'
            ],
            'repeated-set' => [
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
                    }'
            ],
            'repeated-set-inside-while' => [
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
                    }'
            ],
            'by-ref-assignment' => [
                '<?php
                    function foo() : void {
                        preg_match("/hello/", "hello molly", $matches);
            
                        if (!$matches) {
                            return;
                        }
            
                        preg_match("/hello/", "hello dolly", $matches);
            
                        if (!$matches) {
            
                        }
                    }'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'three-var-logic-with-change' => [
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
                'error_message' => 'NullArgument'
            ],
            'three-var-logic-with-exception' => [
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
                'error_message' => 'NullArgument'
            ],
            'inverted-two-var-logic-not-nested-with-var-change' => [
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
                'error_message' => 'InvalidReturnType'
            ],
            'inverted-two-var-logic-not-nested-with-elseif' => [
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
                'error_message' => 'InvalidReturnType'
            ],
            'three-var-logic-with-elseif-and-and' => [
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
                'error_message' => 'InvalidReturnType'
            ],
            'two-var-logic-not-nested-with-elseif-negated-in-if' => [
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
                'error_message' => 'InvalidReturnType'
            ],
            'two-var-logic-not-nested-with-elseif-incorrectly-reinforced-in-if' => [
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
                'error_message' => 'InvalidReturnType'
            ],
            'repeated-if-statements' => [
                '<?php
                    /** @return string|null */
                    function foo(?string $a) {
                        if ($a) {
                            return $a;
                        }
            
                        if ($a) {
            
                        }
                    }',
                'error_message' => 'ParadoxicalCondition'
            ],
            'repeated-conditionals' => [
                '<?php
                    function foo(?string $a) : void {
                        if ($a) {
                            // do something
                        } elseif ($a) {
                            // can never get here
                        }
                    }',
                'error_message' => 'ParadoxicalCondition'
            ]
        ];
    }
}
