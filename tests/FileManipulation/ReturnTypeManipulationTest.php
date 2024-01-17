<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class ReturnTypeManipulationTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'addMissingClosureStringReturnType56' => [
                'input' => '<?php
                    $a = function() {
                        return "hello";
                    };',
                'output' => '<?php
                    $a = /**
                     * @return string
                     *
                     * @psalm-return \'hello\'
                     */
                    function() {
                        return "hello";
                    };',
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingClosureReturnType'],
                'safe_types' => true,
            ],
            'addMissingVoidReturnTypeClosureUse71' => [
                'input' => '<?php
                    $a = "foo";
                    $b = function() use ($a) {};',
                'output' => '<?php
                    $a = "foo";
                    $b = function() use ($a): void {};',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingClosureReturnType'],
                'safe_types' => false,
            ],
            'fixInvalidIntReturnType56' => [
                'input' => '<?php
                    /**
                     * @return int
                     */
                    function foo() {
                        return "hello";
                    }',
                'output' => '<?php
                    /**
                     * @return string
                     *
                     * @psalm-return \'hello\'
                     */
                    function foo() {
                        return "hello";
                    }',
                'php_version' => '5.6',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidIntReturnType70' => [
                'input' => '<?php
                    /**
                     * @return int
                     */
                    function foo(): int {
                        return "hello";
                    }',
                'output' => '<?php
                    /**
                     * @psalm-return \'hello\'
                     */
                    function foo(): string {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidIntReturnTypeJustInTypehint70' => [
                'input' => '<?php
                    function foo(): int {
                        return "hello";
                    }',
                'output' => '<?php
                    function foo(): string {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidStringReturnTypeThatIsNotPhpCompatible70' => [
                'input' => '<?php
                    function foo(): string {
                        return rand(0, 1) ? "hello" : false;
                    }',
                'output' => '<?php
                    /**
                     * @return false|string
                     *
                     * @psalm-return \'hello\'|false
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : false;
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['InvalidFalsableReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidIntReturnTypeThatIsNotPhpCompatible70' => [
                'input' => '<?php
                    function foo(): string {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'output' => '<?php
                    /**
                     * @return null|string
                     *
                     * @psalm-return \'hello\'|null
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['InvalidNullableReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidIntReturnTypeJustInTypehintWithComment70' => [
                'input' => '<?php
                    function foo() /** cool : beans */ : int /** cool : beans */ {
                        return "hello";
                    }',
                'output' => '<?php
                    function foo() /** cool : beans */ : string /** cool : beans */ {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidIntReturnTypeJustInTypehintWithSingleLineComment70' => [
                'input' => '<?php
                    function foo() // hello
                    : int {
                        return "hello";
                    }',
                'output' => '<?php
                    function foo() // hello
                    : string {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => true,
            ],
            'fixMismatchingDocblockReturnType70' => [
                'input' => '<?php
                    /**
                     * @return int
                     */
                    function foo(): string {
                        return "hello";
                    }',
                'output' => '<?php
                    function foo(): string {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MismatchingDocblockReturnType'],
                'safe_types' => true,
            ],
            'preserveFormat' => [
                'input' => '<?php
                    /**
                     * Here is a paragraph
                     *
                     * And another one
                     *
                     * @other is
                     *    a friend of mine
                     *       + Members
                     *          - `google`
                     * @return int
                     */
                    function foo(): int {
                      return "hello";
                    }',
                'output' => '<?php
                    /**
                     * Here is a paragraph
                     *
                     * And another one
                     *
                     * @other is
                     *    a friend of mine
                     *       + Members
                     *          - `google`
                     *
                     * @psalm-return \'hello\'
                     */
                    function foo(): string {
                      return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => true,
            ],
            'addLessSpecificArrayReturnType71' => [
                'input' => '<?php
                    namespace A\B {
                        class C {}
                    }

                    namespace C {
                        use A\B;

                        class D {
                            public function getArrayOfC(): array {
                                return [new \A\B\C];
                            }
                        }
                    }',
                'output' => '<?php
                    namespace A\B {
                        class C {}
                    }

                    namespace C {
                        use A\B;

                        class D {
                            /**
                             * @return B\C[]
                             *
                             * @psalm-return list{B\C}
                             */
                            public function getArrayOfC(): array {
                                return [new \A\B\C];
                            }
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['LessSpecificReturnType'],
                'safe_types' => true,
            ],
            'fixLessSpecificClosureReturnType' => [
                'input' => '<?php
                    function foo(string $name) : string {
                        return $name . " hello";
                    }

                    function bar() : callable {
                        return function(string $name) {
                            return foo($name);
                        };
                    }',
                'output' => '<?php
                    function foo(string $name) : string {
                        return $name . " hello";
                    }

                    /**
                     * @psalm-return Closure(string):string
                     */
                    function bar() : Closure {
                        return function(string $name) {
                            return foo($name);
                        };
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['LessSpecificReturnType'],
                'safe_types' => false,
            ],
            'fixLessSpecificReturnTypePreserveNotes' => [
                'input' => '<?php
                    namespace Foo;

                    /**
                     * @return object some description
                     */
                    function foo() {
                        return new \stdClass();
                    }',
                'output' => '<?php
                    namespace Foo;

                    /**
                     * @return \stdClass some description
                     */
                    function foo() {
                        return new \stdClass();
                    }',
                'php_version' => '5.6',
                'issues_to_fix' => ['LessSpecificReturnType'],
                'safe_types' => false,
            ],
            'fixInvalidReturnTypePreserveNotes' => [
                'input' => '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string some description
                         */
                        function foo() {
                            return new \stdClass();
                        }
                    }',
                'output' => '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return \stdClass some description
                         */
                        function foo() {
                            return new \stdClass();
                        }
                    }',
                'php_version' => '5.6',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
            ],
            'fixInvalidNullableReturnTypePreserveNotes' => [
                'input' => '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string|null some notes
                         */
                        function foo() : ?string {
                            return "hello";
                        }
                    }',
                'output' => '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string some notes
                         *
                         * @psalm-return \'hello\'
                         */
                        function foo() : string {
                            return "hello";
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['LessSpecificReturnType'],
                'safe_types' => false,
            ],
            'fixLessSpecificReturnType' => [
                'input' => '<?php
                    class A {}
                    class B extends A {}

                    class C extends B {
                        public function getB(): ?\A {
                            return new B;
                        }
                        public function getC(): ?\A {
                            return new C;
                        }
                    }',
                'output' => '<?php
                    class A {}
                    class B extends A {}

                    class C extends B {
                        public function getB(): B {
                            return new B;
                        }
                        public function getC(): self {
                            return new C;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['LessSpecificReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidIntReturnTypeJustInPhpDoc' => [
                'input' => '<?php
                    class A {
                        /**
                         * @return int
                         * @psalm-suppress InvalidReturnType
                         */
                        protected function foo() {}
                    }

                    class B extends A {
                        /**
                         * @return int
                         */
                        protected function foo() {}
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @return int
                         * @psalm-suppress InvalidReturnType
                         */
                        protected function foo() {}
                    }

                    class B extends A {
                        /**
                         * @return void
                         */
                        protected function foo() {}
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => true,
            ],
            'fixInvalidIntReturnTypeJustInPhpDocWhenDisallowingBackwardsIncompatibleChanges' => [
                'input' => '<?php
                    class A {
                        /**
                         * @return int
                         */
                        protected function foo() {}
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @return void
                         */
                        protected function foo() {}
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => false,
            ],
            'fixInvalidIntReturnTypeInFinalMethodWhenDisallowingBackwardsIncompatibleChanges' => [
                'input' => '<?php
                    class A {
                        /**
                         * @return int
                         */
                        protected final function foo() {}
                    }',
                'output' => '<?php
                    class A {
                        protected final function foo(): void {}
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => false,
            ],
            'fixInvalidIntReturnTypeInFinalClassWhenDisallowingBackwardsIncompatibleChanges' => [
                'input' => '<?php
                    final class A {
                        /**
                         * @return int
                         */
                        protected function foo() {}
                    }',
                'output' => '<?php
                    final class A {
                        protected function foo(): void {}
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => false,
            ],
            'fixInvalidIntReturnTypeInFunctionWhenDisallowingBackwardsIncompatibleChanges' => [
                'input' => '<?php
                    /**
                     * @return int
                     */
                    function foo() {}',
                'output' => '<?php
                    function foo(): void {}',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => false,
            ],
            'dontReplaceValidReturnTypePreventingBackwardsIncompatibility' => [
                'input' => '<?php
                    class A {
                        /**
                         * @return int[]|null
                         */
                        public function foo(): ?array {
                            return ["hello"];
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @return string[]
                         *
                         * @psalm-return list{\'hello\'}
                         */
                        public function foo(): ?array {
                            return ["hello"];
                        }
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => false,
            ],
            'dontReplaceValidReturnTypeAllowBackwardsIncompatibility' => [
                'input' => '<?php
                    class A {
                        /**
                         * @return int[]|null
                         */
                        public function foo(): ?array {
                            return ["hello"];
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @return string[]
                         *
                         * @psalm-return list{\'hello\'}
                         */
                        public function foo(): array {
                            return ["hello"];
                        }
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'dontAlterForLessSpecificReturnTypeWhenInheritDocPresent' => [
                'input' => '<?php
                    class A {
                        /** @return A */
                        public function getMe() {
                            return $this;
                        }
                    }

                    class B extends A {
                        /** @inheritdoc */
                        public function getMe() {
                            return $this;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @return static
                         */
                        public function getMe() {
                            return $this;
                        }
                    }

                    class B extends A {
                        /** @inheritdoc */
                        public function getMe() {
                            return $this;
                        }
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['LessSpecificReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'dontOOM' => [
                'input' => '<?php
                    class FC {
                        public function __invoke() : void {}
                    }

                    function foo(): string {
                        if (rand(0, 1)) {
                            $cb = new FC();
                        } else {
                            $cb = function() {};
                        }
                        $cb();
                    }',
                'output' => '<?php
                    class FC {
                        public function __invoke() : void {}
                    }

                    function foo(): string {
                        if (rand(0, 1)) {
                            $cb = new FC();
                        } else {
                            $cb = function() {};
                        }
                        $cb();
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['InvalidReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'tryCatchReturn' => [
                'input' => '<?php
                    function scope(){
                        try{
                            return func();
                        }
                        catch(Exception $e){
                            return null;
                        }
                    }

                    function func(): stdClass{
                        return new stdClass();
                    }',
                'output' => '<?php
                    function scope(): ?stdClass{
                        try{
                            return func();
                        }
                        catch(Exception $e){
                            return null;
                        }
                    }

                    function func(): stdClass{
                        return new stdClass();
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'switchReturn' => [
                'input' => '<?php
                    /**
                     * @param string $a
                     */
                    function get_form_fields(string $a) {
                        switch($a){
                            default:
                                return [];
                        }
                    }',
                'output' => '<?php
                    /**
                     * @param string $a
                     *
                     * @psalm-return array<never, never>
                     */
                    function get_form_fields(string $a): array {
                        switch($a){
                            default:
                                return [];
                        }
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'GenericObjectInSignature' => [
                'input' => '<?php
                    /**
                     * @template T
                     */
                    class container {
                        /**
                         * @var T
                         */
                        private $c;
                        /**
                         * @param T $c
                         */
                        public function __construct($c) { $this->c = $c; }
                    }
                    class a {
                        public function test()
                        {
                            $a = new container(1);
                            return $a;
                        }
                    }',
                'output' => '<?php
                    /**
                     * @template T
                     */
                    class container {
                        /**
                         * @var T
                         */
                        private $c;
                        /**
                         * @param T $c
                         */
                        public function __construct($c) { $this->c = $c; }
                    }
                    class a {
                        /**
                         * @psalm-return container<1>
                         */
                        public function test(): container
                        {
                            $a = new container(1);
                            return $a;
                        }
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'OrFalseInReturn' => [
                'input' => '<?php
                    function a() {
                        /** @var false|array $a */
                        $a = false;
                        return $a;
                    }',
                'output' => '<?php
                    function a(): array|false {
                        /** @var false|array $a */
                        $a = false;
                        return $a;
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'OrFalseNullInReturn' => [
                'input' => '<?php
                    function a() {
                        /** @var array|false|null $a */
                        $a = false;
                        return $a;
                    }',
                'output' => '<?php
                    function a(): array|false|null {
                        /** @var array|false|null $a */
                        $a = false;
                        return $a;
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'NullUnionReturn8' => [
                'input' => '<?php
                    function a() {
                        /** @var int|string|null $a */
                        $a = 0;
                        return $a;
                    }',
                'output' => '<?php
                    function a(): int|string|null {
                        /** @var int|string|null $a */
                        $a = 0;
                        return $a;
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'NullableReturn8' => [
                'input' => '<?php
                    function a() {
                        /** @var int|null $a */
                        $a = 0;
                        return $a;
                    }',
                'output' => '<?php
                    function a(): int|null {
                        /** @var int|null $a */
                        $a = 0;
                        return $a;
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'NullableReturn7' => [
                'input' => '<?php
                    function a() {
                        /** @var int|null $a */
                        $a = 0;
                        return $a;
                    }',
                'output' => '<?php
                    function a(): ?int {
                        /** @var int|null $a */
                        $a = 0;
                        return $a;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'ForeignStatic' => [
                'input' => '<?php
                    class a {
                        public function g(): static { return $this; }
                    }
                    class b extends a {}

                    class c {
                        public function a() { return (new a)->g(); }
                        public function b() { return (new b)->g(); }
                    }
                ',
                'output' => '<?php
                    class a {
                        public function g(): static { return $this; }
                    }
                    class b extends a {}

                    class c {
                        public function a(): a { return (new a)->g(); }
                        public function b(): b { return (new b)->g(); }
                    }
                ',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'ForeignStaticIntersection' => [
                'input' => '<?php
                    class a {
                        public function g(): static { return $this; }
                    }
                    class b extends a {}

                    class c {
                        public function a() { return (new a)->g(); }
                        public function b() { return (new b)->g(); }
                    }
                ',
                'output' => '<?php
                    class a {
                        public function g(): static { return $this; }
                    }
                    class b extends a {}

                    class c {
                        public function a(): a { return (new a)->g(); }
                        public function b(): a&b { return (new b)->g(); }
                    }
                ',
                'php_version' => '8.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'ArrowFunction' => [
                'input' => '<?php
                    fn () => 0;
                ',
                'output' => '<?php
                    fn (): int => 0;
                ',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingClosureReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'Intersection80' => [
                'input' => '<?php
                    /**
                     * @template T
                     */
                    class container1 {}
                    /**
                     * @template TT
                     * @extends container1<TT>
                     */
                    class container2 extends container1 {}

                    function ret() {
                        /** @var container1<int>&container2<int> $a */
                        $a = new container1;
                        return $a;
                    }
                ',
                'output' => '<?php
                    /**
                     * @template T
                     */
                    class container1 {}
                    /**
                     * @template TT
                     * @extends container1<TT>
                     */
                    class container2 extends container1 {}

                    /**
                     * @return container1&container2
                     *
                     * @psalm-return container1<int>&container2<int>
                     */
                    function ret(): container2 {
                        /** @var container1<int>&container2<int> $a */
                        $a = new container1;
                        return $a;
                    }
                ',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'Intersection81' => [
                'input' => '<?php
                    /**
                     * @template T
                     */
                    class container1 {}
                    /**
                     * @template TT
                     * @extends container1<TT>
                     */
                    class container2 extends container1 {}

                    function ret() {
                        /** @var container1<int>&container2<int> $a */
                        $a = new container1;
                        return $a;
                    }
                ',
                'output' => '<?php
                    /**
                     * @template T
                     */
                    class container1 {}
                    /**
                     * @template TT
                     * @extends container1<TT>
                     */
                    class container2 extends container1 {}

                    /**
                     * @psalm-return container1<int>&container2<int>
                     */
                    function ret(): container1&container2 {
                        /** @var container1<int>&container2<int> $a */
                        $a = new container1;
                        return $a;
                    }
                ',
                'php_version' => '8.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
        ];
    }
}
