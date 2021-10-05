<?php
namespace Psalm\Tests\FileManipulation;

class ReturnTypeManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool,5?:bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'addMissingClosureStringReturnType56' => [
                '<?php
                    $a = function() {
                        return "hello";
                    };',
                '<?php
                    $a = /**
                     * @return string
                     *
                     * @psalm-return \'hello\'
                     */
                    function() {
                        return "hello";
                    };',
                '5.6',
                ['MissingClosureReturnType'],
                true,
            ],
            'addMissingVoidReturnTypeClosureUse71' => [
                '<?php
                    $a = "foo";
                    $b = function() use ($a) {};',
                '<?php
                    $a = "foo";
                    $b = function() use ($a): void {};',
                '7.1',
                ['MissingClosureReturnType'],
                false,
            ],
            'fixInvalidIntReturnType56' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     *
                     * @psalm-return \'hello\'
                     */
                    function foo() {
                        return "hello";
                    }',
                '5.6',
                ['InvalidReturnType'],
                true,
            ],
            'fixInvalidIntReturnType70' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo(): int {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @psalm-return \'hello\'
                     */
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'fixInvalidIntReturnTypeJustInTypehint70' => [
                '<?php
                    function foo(): int {
                        return "hello";
                    }',
                '<?php
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'fixInvalidStringReturnTypeThatIsNotPhpCompatible70' => [
                '<?php
                    function foo(): string {
                        return rand(0, 1) ? "hello" : false;
                    }',
                '<?php
                    /**
                     * @return false|string
                     *
                     * @psalm-return \'hello\'|false
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : false;
                    }',
                '7.0',
                ['InvalidFalsableReturnType'],
                true,
            ],
            'fixInvalidIntReturnTypeThatIsNotPhpCompatible70' => [
                '<?php
                    function foo(): string {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    /**
                     * @return null|string
                     *
                     * @psalm-return \'hello\'|null
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.0',
                ['InvalidNullableReturnType'],
                true,
            ],
            'fixInvalidIntReturnTypeJustInTypehintWithComment70' => [
                '<?php
                    function foo() /** cool : beans */ : int /** cool : beans */ {
                        return "hello";
                    }',
                '<?php
                    function foo() /** cool : beans */ : string /** cool : beans */ {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'fixInvalidIntReturnTypeJustInTypehintWithSingleLineComment70' => [
                '<?php
                    function foo() // hello
                    : int {
                        return "hello";
                    }',
                '<?php
                    function foo() // hello
                    : string {
                        return "hello";
                    }',
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'fixMismatchingDocblockReturnType70' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo(): string {
                        return "hello";
                    }',
                '<?php
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['MismatchingDocblockReturnType'],
                true,
            ],
            'preserveFormat' => [
                '<?php
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
                '<?php
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
                '7.0',
                ['InvalidReturnType'],
                true,
            ],
            'addLessSpecificArrayReturnType71' => [
                '<?php
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
                '<?php
                    namespace A\B {
                        class C {}
                    }

                    namespace C {
                        use A\B;

                        class D {
                            /**
                             * @return B\C[]
                             *
                             * @psalm-return array{0: B\C}
                             */
                            public function getArrayOfC(): array {
                                return [new \A\B\C];
                            }
                        }
                    }',
                '7.1',
                ['LessSpecificReturnType'],
                true,
            ],
            'fixLessSpecificClosureReturnType' => [
                '<?php
                    function foo(string $name) : string {
                        return $name . " hello";
                    }

                    function bar() : callable {
                        return function(string $name) {
                            return foo($name);
                        };
                    }',
                '<?php
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
                '7.1',
                ['LessSpecificReturnType'],
                false,
            ],
            'fixLessSpecificReturnTypePreserveNotes' => [
                '<?php
                    namespace Foo;

                    /**
                     * @return object some description
                     */
                    function foo() {
                        return new \stdClass();
                    }',
                '<?php
                    namespace Foo;

                    /**
                     * @return \stdClass some description
                     */
                    function foo() {
                        return new \stdClass();
                    }',
                '5.6',
                ['LessSpecificReturnType'],
                false,
            ],
            'fixInvalidReturnTypePreserveNotes' => [
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string some description
                         */
                        function foo() {
                            return new \stdClass();
                        }
                    }',
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return \stdClass some description
                         */
                        function foo() {
                            return new \stdClass();
                        }
                    }',
                '5.6',
                ['InvalidReturnType'],
                false,
            ],
            'fixInvalidNullableReturnTypePreserveNotes' => [
                '<?php
                    namespace Foo;

                    class A {
                        /**
                         * @return string|null some notes
                         */
                        function foo() : ?string {
                            return "hello";
                        }
                    }',
                '<?php
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
                '7.1',
                ['LessSpecificReturnType'],
                false,
            ],
            'fixLessSpecificReturnType' => [
                '<?php
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
                '<?php
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
                '7.1',
                ['LessSpecificReturnType'],
                true,
            ],
            'fixInvalidIntReturnTypeJustInPhpDoc' => [
                '<?php
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
                '<?php
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
                '7.3',
                ['InvalidReturnType'],
                false,
            ],
            'fixInvalidIntReturnTypeJustInPhpDocWhenDisallowingBackwardsIncompatibleChanges' => [
                '<?php
                    class A {
                        /**
                         * @return int
                         */
                        protected function foo() {}
                    }',
                '<?php
                    class A {
                        /**
                         * @return void
                         */
                        protected function foo() {}
                    }',
                '7.3',
                ['InvalidReturnType'],
                false,
                false,
            ],
            'fixInvalidIntReturnTypeInFinalMethodWhenDisallowingBackwardsIncompatibleChanges' => [
                '<?php
                    class A {
                        /**
                         * @return int
                         */
                        protected final function foo() {}
                    }',
                '<?php
                    class A {
                        protected final function foo(): void {}
                    }',
                '7.3',
                ['InvalidReturnType'],
                false,
                false,
            ],
            'fixInvalidIntReturnTypeInFinalClassWhenDisallowingBackwardsIncompatibleChanges' => [
                '<?php
                    final class A {
                        /**
                         * @return int
                         */
                        protected function foo() {}
                    }',
                '<?php
                    final class A {
                        protected function foo(): void {}
                    }',
                '7.3',
                ['InvalidReturnType'],
                false,
                false,
            ],
            'fixInvalidIntReturnTypeInFunctionWhenDisallowingBackwardsIncompatibleChanges' => [
                '<?php
                    /**
                     * @return int
                     */
                    function foo() {}',
                '<?php
                    function foo(): void {}',
                '7.3',
                ['InvalidReturnType'],
                false,
                false,
            ],
            'dontReplaceValidReturnTypePreventingBackwardsIncompatibility' => [
                '<?php
                    class A {
                        /**
                         * @return int[]|null
                         */
                        public function foo(): ?array {
                            return ["hello"];
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @return string[]
                         *
                         * @psalm-return array{0: \'hello\'}
                         */
                        public function foo(): ?array {
                            return ["hello"];
                        }
                    }',
                '7.3',
                ['InvalidReturnType'],
                false,
                false,
            ],
            'dontReplaceValidReturnTypeAllowBackwardsIncompatibility' => [
                '<?php
                    class A {
                        /**
                         * @return int[]|null
                         */
                        public function foo(): ?array {
                            return ["hello"];
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @return string[]
                         *
                         * @psalm-return array{0: \'hello\'}
                         */
                        public function foo(): array {
                            return ["hello"];
                        }
                    }',
                '7.3',
                ['InvalidReturnType'],
                false,
                true,
            ],
            'dontAlterForLessSpecificReturnTypeWhenInheritDocPresent' => [
                '<?php
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
                '<?php
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
                '7.3',
                ['LessSpecificReturnType'],
                false,
                true,
            ],
            'dontOOM' => [
                '<?php
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
                '<?php
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
                '7.3',
                ['InvalidReturnType'],
                false,
                true,
            ],
            'tryCatchReturn' => [
                '<?php
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
                '<?php
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
                '7.3',
                ['MissingReturnType'],
                false,
                true,
            ],
            'switchReturn' => [
                '<?php
                    /**
                     * @param string $a
                     */
                    function get_form_fields(string $a) {
                        switch($a){
                            default:
                                return [];
                        }
                    }',
                '<?php
                    /**
                     * @param string $a
                     *
                     * @psalm-return array<empty, empty>
                     */
                    function get_form_fields(string $a): array {
                        switch($a){
                            default:
                                return [];
                        }
                    }',
                '7.3',
                ['MissingReturnType'],
                false,
                true,
            ],
            'GenericObjectInSignature' => [
                '<?php
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
                '<?php
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
                         * @return container
                         *
                         * @psalm-return container<1>
                         */
                        public function test(): container
                        {
                            $a = new container(1);
                            return $a;
                        }
                    }',
                '7.3',
                ['MissingReturnType'],
                false,
                true,
            ]
        ];
    }
}
