<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class UnusedCodeManipulationTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'removePossiblyUnusedMethod' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}

                        public function bar() : void {}
                    }

                    (new A)->foo();',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'removePossiblyUnusedMethodInMiddle' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}

                        public function bar() : void {}

                        public function bat() : void {}
                    }

                    (new A)->foo();
                    (new A)->bat();',
                'output' => '<?php
                    class A {
                        public function foo() : void {}

                        public function bat() : void {}
                    }

                    (new A)->foo();
                    (new A)->bat();',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'removeAllPossiblyUnusedMethods' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}

                        public function bar() : void {}

                        public function bat() : void {}
                    }

                    new A();',
                'output' => '<?php
                    class A {

                    }

                    new A();',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedMethodWithMixedUse' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo($a) {
                        $a->foo();
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo($a) {
                        $a->foo();
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedMethodWithSuppression' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}

                        /** @psalm-suppress PossiblyUnusedMethod */
                        public function bar() : void {}
                    }

                    (new A)->foo();',
                'output' => '<?php
                    class A {
                        public function foo() : void {}

                        /** @psalm-suppress PossiblyUnusedMethod */
                        public function bar() : void {}
                    }

                    (new A)->foo();',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'removeUnusedMethod' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}

                        private function bar() : void {}
                    }

                    (new A)->foo();',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedMethod'],
                'safe_types' => true,
            ],
            'removeUnusedMethodAtBeginning' => [
                'input' => '<?php
                    class A {
                        private function foo() : void {}

                        public function bar() : void {}
                    }

                    (new A)->bar();',
                'output' => '<?php
                    class A {


                        public function bar() : void {}
                    }

                    (new A)->bar();',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedMethod'],
                'safe_types' => true,
            ],
            'removePossiblyUnusedMethodWithDocblock' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}

                        /** @return void */
                        public function bar() : void {}
                    }

                    (new A)->foo();',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'removeUnusedMethodWithDocblock' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}

                        /** @return void */
                        private function bar() : void {}
                    }

                    (new A)->foo();',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedMethod'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCall' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCallableCall' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function takeCallable(callable $c) : void {}

                    function foo(A $a, string $var) {
                        takeCallable([$a, $var]);
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function takeCallable(callable $c) : void {}

                    function foo(A $a, string $var) {
                        takeCallable([$a, $var]);
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedMethodWithCallUserFuncCall' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        call_user_func([$a, $var]);
                    }',
                'output' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        call_user_func([$a, $var]);
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCallableLhsCall' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}
                        public function bar() : void {}
                    }

                    function takeCallable(callable $c) : void {}

                    function foo($a) {
                        takeCallable([$a, "foo"]);
                    }

                    foo(new A);',
                'output' => '<?php
                    class A {
                        public function foo() : void {}

                    }

                    function takeCallable(callable $c) : void {}

                    function foo($a) {
                        takeCallable([$a, "foo"]);
                    }

                    foo(new A);',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCallOnParent' => [
                'input' => '<?php
                    class A { }

                    class B extends A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }

                    foo(new B);',
                'output' => '<?php
                    class A { }

                    class B extends A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }

                    foo(new B);',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'removePossiblyUnusedMethodWithVariableCall' => [
                'input' => '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-method */
                        echo $a->$var();
                    }',
                'output' => '<?php
                    class A {

                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-method */
                        echo $a->$var();
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod'],
                'safe_types' => true,
            ],
            'removePossiblyUnusedMethodAndMissingReturnType' => [
                'input' => '<?php
                    class A {
                        public function foo() {}
                    }

                    new A();',
                'output' => '<?php
                    class A {

                    }

                    new A();',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedMethod', 'MissingReturnType'],
                'safe_types' => true,
            ],
            'removePossiblyUnusedPropertyWithDocblock' => [
                'input' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";

                        /** @var string */
                        public $bar = "hello";
                    }

                    echo (new A)->foo;',
                'output' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    echo (new A)->foo;',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithMixedUse' => [
                'input' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo($a) {
                        echo $a->foo;
                    }',
                'output' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo($a) {
                        echo $a->foo;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithSuppression' => [
                'input' => '<?php
                    /** @psalm-suppress PossiblyUnusedProperty */
                    class A {
                        public $foo = "hello";
                    }',
                'output' => '<?php
                    /** @psalm-suppress PossiblyUnusedProperty */
                    class A {
                        public $foo = "hello";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableFetch' => [
                'input' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }',
                'output' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'removePossiblyUnusedPropertyWithVariableFetch' => [
                'input' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-property */
                        echo $a->$var;
                    }',
                'output' => '<?php
                    class A {

                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-property */
                        echo $a->$var;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableFetchInParent' => [
                'input' => '<?php
                    class A {
                        public function __set(string $k, $v) {
                            $this->$k = $v;
                        }
                    }

                    class B extends A {
                        public $foo = "hello";
                    }

                    (new B())->__set("foo", "bar");',
                'output' => '<?php
                    class A {
                        public function __set(string $k, $v) {
                            $this->$k = $v;
                        }
                    }

                    class B extends A {
                        public $foo = "hello";
                    }

                    (new B())->__set("foo", "bar");',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableOnParent' => [
                'input' => '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }

                    foo(new A(), "foo");',
                'output' => '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }

                    foo(new A(), "foo");',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableFetchImplementedInterface' => [
                'input' => '<?php
                    interface I {}

                    class A implements I {
                        public $foo = "hello";
                    }

                    function foo(I $i, string $var) {
                        echo $i->$var;
                    }

                    foo(new A(), "foo");',
                'output' => '<?php
                    interface I {}

                    class A implements I {
                        public $foo = "hello";
                    }

                    function foo(I $i, string $var) {
                        echo $i->$var;
                    }

                    foo(new A(), "foo");',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithStaticVariableFetch' => [
                'input' => '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        echo A::$$var;
                    }',
                'output' => '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        echo A::$$var;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableAssignment' => [
                'input' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }',
                'output' => '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableAssignmentOnParent' => [
                'input' => '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }

                    foo(new B);',
                'output' => '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }

                    foo(new B);',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'dontRemovePossiblyUnusedPropertyWithStaticVariableAssignment' => [
                'input' => '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        A::$$var = "hello";
                    }',
                'output' => '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        A::$$var = "hello";
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['PossiblyUnusedProperty'],
                'safe_types' => true,
            ],
            'removeUnusedPropertyWithDocblock' => [
                'input' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";

                        /** @var string */
                        private $bar = "hello";
                    }

                    echo (new A)->foo;',
                'output' => '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    echo (new A)->foo;',
                'php_version' => '7.1',
                'issues_to_fix' => ['UnusedProperty'],
                'safe_types' => true,
            ],
        ];
    }
}
