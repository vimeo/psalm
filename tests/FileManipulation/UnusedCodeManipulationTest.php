<?php
namespace Psalm\Tests\FileManipulation;

class UnusedCodeManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'removePossiblyUnusedMethod' => [
                '<?php
                    class A {
                        public function foo() : void {}

                        public function bar() : void {}
                    }

                    (new A)->foo();',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'removePossiblyUnusedMethodInMiddle' => [
                '<?php
                    class A {
                        public function foo() : void {}

                        public function bar() : void {}

                        public function bat() : void {}
                    }

                    (new A)->foo();
                    (new A)->bat();',
                '<?php
                    class A {
                        public function foo() : void {}

                        public function bat() : void {}
                    }

                    (new A)->foo();
                    (new A)->bat();',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'removeAllPossiblyUnusedMethods' => [
                '<?php
                    class A {
                        public function foo() : void {}

                        public function bar() : void {}

                        public function bat() : void {}
                    }

                    new A();',
                '<?php
                    class A {

                    }

                    new A();',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'dontRemovePossiblyUnusedMethodWithMixedUse' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo($a) {
                        $a->foo();
                    }',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo($a) {
                        $a->foo();
                    }',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'dontRemovePossiblyUnusedMethodWithSuppression' => [
                '<?php
                    class A {
                        public function foo() : void {}

                        /** @psalm-suppress PossiblyUnusedMethod */
                        public function bar() : void {}
                    }

                    (new A)->foo();',
                '<?php
                    class A {
                        public function foo() : void {}

                        /** @psalm-suppress PossiblyUnusedMethod */
                        public function bar() : void {}
                    }

                    (new A)->foo();',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'removeUnusedMethod' => [
                '<?php
                    class A {
                        public function foo() : void {}

                        private function bar() : void {}
                    }

                    (new A)->foo();',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                '7.1',
                ['UnusedMethod'],
                true,
            ],
            'removeUnusedMethodAtBeginning' => [
                '<?php
                    class A {
                        private function foo() : void {}

                        public function bar() : void {}
                    }

                    (new A)->bar();',
                '<?php
                    class A {


                        public function bar() : void {}
                    }

                    (new A)->bar();',
                '7.1',
                ['UnusedMethod'],
                true,
            ],
            'removePossiblyUnusedMethodWithDocblock' => [
                '<?php
                    class A {
                        public function foo() : void {}

                        /** @return void */
                        public function bar() : void {}
                    }

                    (new A)->foo();',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'removeUnusedMethodWithDocblock' => [
                '<?php
                    class A {
                        public function foo() : void {}

                        /** @return void */
                        private function bar() : void {}
                    }

                    (new A)->foo();',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    (new A)->foo();',
                '7.1',
                ['UnusedMethod'],
                true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCall' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCallableCall' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function takeCallable(callable $c) : void {}

                    function foo(A $a, string $var) {
                        takeCallable([$a, $var]);
                    }',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function takeCallable(callable $c) : void {}

                    function foo(A $a, string $var) {
                        takeCallable([$a, $var]);
                    }',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'dontRemovePossiblyUnusedMethodWithCallUserFuncCall' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        call_user_func([$a, $var]);
                    }',
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        call_user_func([$a, $var]);
                    }',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCallableLhsCall' => [
                '<?php
                    class A {
                        public function foo() : void {}
                        public function bar() : void {}
                    }

                    function takeCallable(callable $c) : void {}

                    function foo($a) {
                        takeCallable([$a, "foo"]);
                    }

                    foo(new A);',
                '<?php
                    class A {
                        public function foo() : void {}

                    }

                    function takeCallable(callable $c) : void {}

                    function foo($a) {
                        takeCallable([$a, "foo"]);
                    }

                    foo(new A);',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'dontRemovePossiblyUnusedMethodWithVariableCallOnParent' => [
                '<?php
                    class A { }

                    class B extends A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }

                    foo(new B);',
                '<?php
                    class A { }

                    class B extends A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var();
                    }

                    foo(new B);',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'removePossiblyUnusedMethodWithVariableCall' => [
                '<?php
                    class A {
                        public function foo() : void {}
                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-method */
                        echo $a->$var();
                    }',
                '<?php
                    class A {

                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-method */
                        echo $a->$var();
                    }',
                '7.1',
                ['PossiblyUnusedMethod'],
                true,
            ],
            'removePossiblyUnusedMethodAndMissingReturnType' => [
                '<?php
                    class A {
                        public function foo() {}
                    }

                    new A();',
                '<?php
                    class A {

                    }

                    new A();',
                '7.1',
                ['PossiblyUnusedMethod', 'MissingReturnType'],
                true,
            ],
            'removePossiblyUnusedPropertyWithDocblock' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";

                        /** @var string */
                        public $bar = "hello";
                    }

                    echo (new A)->foo;',
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    echo (new A)->foo;',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithMixedUse' => [
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo($a) {
                        echo $a->foo;
                    }',
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo($a) {
                        echo $a->foo;
                    }',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithSuppression' => [
                '<?php
                    /** @psalm-suppress PossiblyUnusedProperty */
                    class A {
                        public $foo = "hello";
                    }',
                '<?php
                    /** @psalm-suppress PossiblyUnusedProperty */
                    class A {
                        public $foo = "hello";
                    }',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableFetch' => [
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }',
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'removePossiblyUnusedPropertyWithVariableFetch' => [
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-property */
                        echo $a->$var;
                    }',
                '<?php
                    class A {

                    }

                    function foo(A $a, string $var) {
                        /** @psalm-ignore-variable-property */
                        echo $a->$var;
                    }',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableFetchInParent' => [
                '<?php
                    class A {
                        public function __set(string $k, $v) {
                            $this->$k = $v;
                        }
                    }

                    class B extends A {
                        public $foo = "hello";
                    }

                    (new B())->__set("foo", "bar");',
                '<?php
                    class A {
                        public function __set(string $k, $v) {
                            $this->$k = $v;
                        }
                    }

                    class B extends A {
                        public $foo = "hello";
                    }

                    (new B())->__set("foo", "bar");',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableOnParent' => [
                '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }

                    foo(new A(), "foo");',
                '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        echo $a->$var;
                    }

                    foo(new A(), "foo");',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableFetchImplementedInterface' => [
                '<?php
                    interface I {}

                    class A implements I {
                        public $foo = "hello";
                    }

                    function foo(I $i, string $var) {
                        echo $i->$var;
                    }

                    foo(new A(), "foo");',
                '<?php
                    interface I {}

                    class A implements I {
                        public $foo = "hello";
                    }

                    function foo(I $i, string $var) {
                        echo $i->$var;
                    }

                    foo(new A(), "foo");',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithStaticVariableFetch' => [
                '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        echo A::$$var;
                    }',
                '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        echo A::$$var;
                    }',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableAssignment' => [
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }',
                '<?php
                    class A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithVariableAssignmentOnParent' => [
                '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }

                    foo(new B);',
                '<?php
                    class A {}

                    class B extends A {
                        public $foo = "hello";
                    }

                    function foo(A $a, string $var) {
                        $a->$var = "hello";
                    }

                    foo(new B);',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'dontRemovePossiblyUnusedPropertyWithStaticVariableAssignment' => [
                '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        A::$$var = "hello";
                    }',
                '<?php
                    class A {
                        public static $foo = "hello";
                    }

                    function foo(string $var) {
                        A::$$var = "hello";
                    }',
                '7.1',
                ['PossiblyUnusedProperty'],
                true,
            ],
            'removeUnusedPropertyWithDocblock' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";

                        /** @var string */
                        private $bar = "hello";
                    }

                    echo (new A)->foo;',
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    echo (new A)->foo;',
                '7.1',
                ['UnusedProperty'],
                true,
            ],
        ];
    }
}
