<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class UnusedVariableManipulationTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'removeUnusedVariableSimple' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = 5;
                            $b = "hello";
                            echo $b;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            $b = "hello";
                            echo $b;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUndefined' => [
                'input' => '<?php
                    function foo(bool $b): void {
                        if ($b) {
                            $v = "hi";
                        }

                        echo $v;
                    }',
                'output' => '<?php
                    function foo(bool $b): void {
                        if ($b) {
                            $v = "hi";
                        }

                        echo $v;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
            'removeUnusedVariableFromTry' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            try {
                                $c = false;
                                $d = null;
                            } catch (Exception $e) {}
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            try {
                            } catch (Exception $e) {}
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
            'removeUnusedVariableAndFunctionCall' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = substr("wonderful", 2);
                            $b = "hello";
                            echo $b;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            $b = "hello";
                            echo $b;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVariableTwoVar' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = "a";
                            $b = "b";
                            $c = "c";
                            echo $b;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            $b = "b";
                            echo $b;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVariableTwoVarFunctionCalls' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = substr("hello world", 4);
                            $b = "b";
                            $c = file_get_contents("foo.php");
                            echo $b;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            $b = "b";
                            file_get_contents("foo.php");
                            echo $b;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVariableClassMethod' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a =bar();
                            $b = "b";
                            echo $b;
                        }

                        public function bar() : void {
                            ;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            bar();
                            $b = "b";
                            echo $b;
                        }

                        public function bar() : void {
                            ;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVariableFunctionCallAndStrLiteral' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = "hello".bar();
                            $b = "world";
                            echo $b;
                        }

                        public function bar() : string {
                            return "bar";
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            "hello".bar();
                            $b = "world";
                            echo $b;
                        }

                        public function bar() : string {
                            return "bar";
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVariableChainAssignment' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $c = $d = $e = "";
                            echo $a.$b.$d.$e;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $d = $e = "";
                            echo $a.$b.$d.$e;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeTwoUnusedVariableChainAssignment' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $c = $d = $e = "hello";
                            echo $a.$d.$e;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            $a = $d = $e = "hello";
                            echo $a.$d.$e;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeAllUnusedVariableChainAssignment' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $c = $d = $e = "hello";
                            echo "hello";
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            echo "hello";
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedArrayAccess' => [
                'input' => '<?php
                    function foo($b) : void {
                        $a = $b[1];
                    }',
                'output' => '<?php
                    function foo($b) : void {
                        $b[1];
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeEmptyArrayAssign' => [
                'input' => '<?php
                    function foo($b) : void {
                        $a = [];
                        echo "foo";
                    }',
                'output' => '<?php
                    function foo($b) : void {
                        echo "foo";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedArrayAssignInt' => [
                'input' => '<?php
                    function foo($b) : void {
                        $a = [5];
                        echo "foo";
                    }',
                'output' => '<?php
                    function foo($b) : void {
                        echo "foo";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedArrayAssignCallable' => [
                'input' => '<?php
                    function foo($b) : void {
                        $a = [foo()];
                        echo "foo";
                    }',
                'output' => '<?php
                    function foo($b) : void {
                        [foo()];
                        echo "foo";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVarShellExec' => [
                'input' => '<?php
                    function foo() : void {
                        $a = shell_exec("ls");
                    }',
                'output' => '<?php
                    function foo() : void {
                        shell_exec("ls");
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVarExit' => [
                'input' => '<?php
                    function foo() : void {
                        $a = exit(1);
                    }',
                'output' => '<?php
                    function foo() : void {
                        exit(1);
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVarTwoPass' => [
                'input' => '<?php
                    function foo() : void {
                        $a = 5;
                        $a += 1;
                    }',
                'output' => '<?php
                    function foo() : void {
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'SKIPPED-removeUnusedVarAssignByRefToSubsequentlyUsedVariable' => [
                'input' => '<?php
                    function foo() : void {
                        $a = 5;
                        $b = &$a;
                        echo $a;
                    }',
                'output' => '<?php
                    function foo() : void {
                        $a = 5;
                        echo $a;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVarAssignByRef' => [
                'input' => '<?php
                    function foo() : void {
                        $a = 5;
                        $b = &$a;
                    }',
                'output' => '<?php
                    function foo() : void {
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVarAssignByRefPartial' => [
                'input' => '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $b = &$a[1];
                        print_r($a);
                    }',
                'output' => '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $a[1];
                        print_r($a);
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVarAssignByRefPartialWithSpaceAfter' => [
                'input' => '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $b = & $a[1];
                        print_r($a);
                    }',
                'output' => '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $a[1];
                        print_r($a);
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedVarNewObject' => [
                'input' => '<?php
                    class B {}

                    function foo() : void {
                        $a = 5;
                        $b = new B();
                        echo $a;
                    }',
                'output' => '<?php
                    class B {}

                    function foo() : void {
                        $a = 5;
                        new B();
                        echo $a;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeUnusedChainMixedAssign' => [
                'input' => '<?php
                    function foo() : void {
                        $a = 5;
                        $b = 6;
                        $c = $b += $a -= intval("4");
                        echo "foo";
                    }',
                'output' => '<?php
                    function foo() : void {
                        echo "foo";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
            'removeUnusedUnchainedAssign' => [
                'input' => '<?php
                    function foo() : void {
                        $a = 5;
                        $b = 6;
                        $a -= intval("4");
                        $b += $a;
                        $c = $b;
                        echo "foo";
                    }',
                'output' => '<?php
                    function foo() : void {
                        echo "foo";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
            'removeUnusedVariableBinaryOp' => [
                'input' => '<?php
                    function foo() : void {
                        $a = 5;
                        $b = 6;
                        $c = $a + $b;
                        echo "foo";
                    }',
                'output' => '<?php
                    function foo() : void {
                        echo "foo";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'dontremoveUnusedVariableFor' => [
                'input' => '<?php
                    function foo($b) : void {
                        for($i = 5; $j<5; $j++){
                            echo "abc";
                        }
                    }',
                'output' => '<?php
                    function foo($b) : void {
                        for($i = 5; $j<5; $j++){
                            echo "abc";
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'dontremoveUnusedVariableWhile' => [
                'input' => '<?php
                    function foo($b) : void {
                        while($i=5){
                            echo "abc";
                        }
                    }',
                'output' => '<?php
                    function foo($b) : void {
                        while($i=5){
                            echo "abc";
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'dontRemoveUnusedVariableInsideIf' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {
                            $a = "hello";
                            if($b = 5) {
                                echo $a;
                            }
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {
                            $a = "hello";
                            if($b = 5) {
                                echo $a;
                            }
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'donRemoveSuppressedUnusedVariable' => [
                'input' => '<?php
                    /** @psalm-suppress UnusedVariable */
                    function foo() : void {
                        $a = 5;
                        $b = "hello";
                        echo $b;
                    }',
                'output' => '<?php
                    /** @psalm-suppress UnusedVariable */
                    function foo() : void {
                        $a = 5;
                        $b = "hello";
                        echo $b;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],

            'removeLongUnusedAssignment' => [
                'input' => '<?php
                    /**
                     * @psalm-external-mutation-free
                     */
                    class A {
                        private string $foo;

                        public function __construct(string $foo) {
                            $this->foo = $foo;
                        }

                        public function getFoo() : void {
                            return "abular" . $this->foo;
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function makeA(string $s) : A {
                        return new A($s);
                    }

                    function foo() : void {
                        $a = makeA("hello")->getFoo();
                    }',
                'output' => '<?php
                    /**
                     * @psalm-external-mutation-free
                     */
                    class A {
                        private string $foo;

                        public function __construct(string $foo) {
                            $this->foo = $foo;
                        }

                        public function getFoo() : void {
                            return "abular" . $this->foo;
                        }
                    }

                    /**
                     * @psalm-pure
                     */
                    function makeA(string $s) : A {
                        return new A($s);
                    }

                    function foo() : void {
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
            'dontRemoveUsedToStringCall' => [
                'input' => '<?php
                    class S {
                        /**
                         * @throws Exception
                         */
                        public function __toString() {
                            if(rand(0,1)){
                                throw new exception();
                            }
                            return "";
                        }
                    }

                    function foo(S $a) {
                        try {
                            $b = (string) $a;
                        } catch(Exception $e){
                            // this class is not stringable
                        }
                    }',
                'output' => '<?php
                    class S {
                        /**
                         * @throws Exception
                         */
                        public function __toString() {
                            if(rand(0,1)){
                                throw new exception();
                            }
                            return "";
                        }
                    }

                    function foo(S $a) {
                        try {
                            (string) $a;
                        } catch(Exception $e){
                            // this class is not stringable
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
            'dontRemoveUnusedClosureUse' => [
                'input' => '<?php
                    $b = 5;
                    echo $b;
                    $a = function() use ($b) : void {
                        echo 4;
                    };
                    $a();',
                'output' => '<?php
                    $b = 5;
                    echo $b;
                    $a = function() use ($b) : void {
                        echo 4;
                    };
                    $a();',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedVariable'],
                'safe_types' => true,
            ],
        ];
    }
}
