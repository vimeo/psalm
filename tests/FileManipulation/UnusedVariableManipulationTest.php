<?php
namespace Psalm\Tests\FileManipulation;

class UnusedVariableManipulationTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse()
    {
        return [
            'removeUnusedVariableSimple' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = 5;
                            $b = "hello";
                            echo $b;
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            $b = "hello";
                            echo $b;
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVariableFunctionCall' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = substr("wonderful", 2);
                            $b = "hello";
                            echo $b;
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            substr("wonderful", 2);
                            $b = "hello";
                            echo $b;
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVariableTwoVar' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = "a";
                            $b = "b";
                            $c = "c";
                            echo $b;
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            $b = "b";
                            echo $b;
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVariableTwoVarFunctionCalls' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = substr("hello world", 4);
                            $b = "b";
                            $c = file_get_contents("foo.php");
                            echo $b;
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            substr("hello world", 4);
                            $b = "b";
                            file_get_contents("foo.php");
                            echo $b;
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVariableClassMethod' => [
                '<?php
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
                '<?php
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
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVariableFunctionCallAndStrLiteral' => [
                '<?php
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
                '<?php
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
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVariableChainAssignment' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $c = $d = $e = "hello";
                            echo $a.$b.$d.$e;
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $d = $e = "hello";
                            echo $a.$b.$d.$e;
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeTwoUnusedVariableChainAssignment' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $c = $d = $e = "hello";
                            echo $a.$d.$e;
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            $a = $d = $e = "hello";
                            echo $a.$d.$e;
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeAllUnusedVariableChainAssignment' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = $b = $c = $d = $e = "hello";
                            echo "hello";
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            echo "hello";
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedArrayAccess' => [
                '<?php
                    function foo($b) : void {
                        $a = $b[1];
                    }',
                '<?php
                    function foo($b) : void {
                        $b[1];
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeEmptyArrayAssign' => [
                '<?php
                    function foo($b) : void {
                        $a = [];
                        echo "foo";
                    }',
                '<?php
                    function foo($b) : void {
                        echo "foo";
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedArrayAssignInt' => [
                '<?php
                    function foo($b) : void {
                        $a = [5];
                        echo "foo";
                    }',
                '<?php
                    function foo($b) : void {
                        echo "foo";
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedArrayAssignCallable' => [
                '<?php
                    function foo($b) : void {
                        $a = [foo()];
                        echo "foo";
                    }',
                '<?php
                    function foo($b) : void {
                        [foo()];
                        echo "foo";
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVarShellExec' => [
                '<?php
                    function foo() : void {
                        $a = shell_exec("ls");
                    }',
                '<?php
                    function foo() : void {
                        shell_exec("ls");
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVarExit' => [
                '<?php
                    function foo() : void {
                        $a = exit(1);
                    }',
                '<?php
                    function foo() : void {
                        exit(1);
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVarTwoPass' => [
                '<?php
                    function foo() : void {
                        $a = 5;
                        $a += 1;
                    }',
                '<?php
                    function foo() : void {
                        $a = 5;
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVarAssignByRef' => [
                '<?php
                    function foo() : void {
                        $a = 5;
                        $b = &$a;
                        echo $a;
                    }',
                '<?php
                    function foo() : void {
                        $a = 5;
                        $b = &$a;
                        echo $a;
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVarAssignByRefPartial' => [
                '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $b = &$a[1];
                        print_r($a);
                    }',
                '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $b = &$a[1];
                        print_r($a);
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVarAssignByRefPartialWithSpaceAfter' => [
                '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $b = & $a[1];
                        print_r($a);
                    }',
                '<?php
                    function foo() : void {
                        $a = [1, 2, 3];
                        $b = & $a[1];
                        print_r($a);
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVarNewObject' => [
                '<?php
                    class B {}

                    function foo() : void {
                        $a = 5;
                        $b = new B();
                        echo $a;
                    }',
                '<?php
                    class B {}

                    function foo() : void {
                        $a = 5;
                        new B();
                        echo $a;
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedChainMixedAssign' => [
                '<?php
                    function foo() : void {
                        $a = 5;
                        $b = 6;
                        $c = $b += $a -= intval("4");
                        echo "foo";
                    }',
                '<?php
                    function foo() : void {
                        $a = 5;
                        $b = 6;
                        intval("4");
                        echo "foo";
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'removeUnusedVariableBinaryOp' => [
                '<?php
                    function foo() : void {
                        $a = 5;
                        $b = 6;
                        $c = $a + $b;
                        echo "foo";
                    }',
                '<?php
                    function foo() : void {
                        $a = 5;
                        $b = 6;
                        echo "foo";
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'dontremoveUnusedVariableFor' => [
                '<?php
                    function foo($b) : void {
                        for($i = 5; $j<5; $j++){
                            echo "abc";
                        }
                    }',
                '<?php
                    function foo($b) : void {
                        for($i = 5; $j<5; $j++){
                            echo "abc";
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'dontremoveUnusedVariableWhile' => [
                '<?php
                    function foo($b) : void {
                        while($i=5){
                            echo "abc";
                        }
                    }',
                '<?php
                    function foo($b) : void {
                        while($i=5){
                            echo "abc";
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'dontRemoveUnusedVariableInsideIf' => [
                '<?php
                    class A {
                        public function foo() : void {
                            $a = "hello";
                            if($b = 5) {
                                echo $a;
                            }
                        }
                    }',
                '<?php
                    class A {
                        public function foo() : void {
                            $a = "hello";
                            if($b = 5) {
                                echo $a;
                            }
                        }
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],

            'donRemoveSuppressedUnusedVariable' => [
                '<?php
                    /** @psalm-suppress UnusedVariable */
                    function foo() : void {
                        $a = 5;
                        $b = "hello";
                        echo $b;
                    }',
                '<?php
                    /** @psalm-suppress UnusedVariable */
                    function foo() : void {
                        $a = 5;
                        $b = "hello";
                        echo $b;
                    }',
                '7.1',
                ['UnusedVariable'],
                true,
            ],
        ];
    }
}
