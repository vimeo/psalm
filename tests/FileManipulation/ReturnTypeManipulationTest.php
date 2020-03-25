<?php
namespace Psalm\Tests\FileManipulation;

class ReturnTypeManipulationTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool,5?:bool}>
     */
    public function providerValidCodeParse()
    {
        return [
            'addMissingVoidReturnType56' => [
                '<?php
                    function foo() { }',
                '<?php
                    /**
                     * @return void
                     */
                    function foo() { }',
                '5.6',
                ['MissingReturnType'],
                true,
            ],
            'addMissingVoidReturnType70' => [
                '<?php
                    function foo() { }',
                '<?php
                    /**
                     * @return void
                     */
                    function foo() { }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingVoidReturnType71' => [
                '<?php
                    function foo() { }',
                '<?php
                    function foo(): void { }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringReturnType56' => [
                '<?php
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @return string
                     */
                    function foo() {
                        return "hello";
                    }',
                '5.6',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringReturnType70' => [
                '<?php
                    function foo() {
                        return "hello";
                    }',
                '<?php
                    function foo(): string {
                        return "hello";
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingClosureStringReturnType56' => [
                '<?php
                    $a = function() {
                        return "hello";
                    };',
                '<?php
                    $a = /**
                     * @return string
                     */
                    function() {
                        return "hello";
                    };',
                '5.6',
                ['MissingClosureReturnType'],
                true,
            ],
            'addMissingNullableStringReturnType56' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    /**
                     * @return null|string
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '5.6',
                ['MissingReturnType'],
                true,
            ],
            'addMissingNullableStringReturnType70' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    /**
                     * @return null|string
                     */
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringReturnType71' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo(): ?string {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringReturnTypeWithComment71' => [
                '<?php
                    function foo() /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo(): ?string /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringReturnTypeWithSingleLineComment71' => [
                '<?php
                    function foo()// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '<?php
                    function foo(): ?string// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringArrayReturnType56' => [
                '<?php
                    function foo() {
                        return ["hello"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{0: string}
                     */
                    function foo() {
                        return ["hello"];
                    }',
                '5.6',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringArrayReturnType70' => [
                '<?php
                    function foo() {
                        return ["hello"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{0: string}
                     */
                    function foo(): array {
                        return ["hello"];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingObjectLikeReturnType70' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{a: string, b?: string}
                     */
                    function foo(): array {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingObjectLikeReturnTypeWithEmptyArray' => [
                '<?php
                    function foo() {
                        if (rand(0, 1)) {
                            return [];
                        }

                        return [
                            "a" => 1,
                            "b" => 2,
                        ];
                    }',
                '<?php
                    /**
                     * @return int[]
                     *
                     * @psalm-return array{a?: int, b?: int}
                     */
                    function foo(): array {
                        if (rand(0, 1)) {
                            return [];
                        }

                        return [
                            "a" => 1,
                            "b" => 2,
                        ];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingObjectLikeReturnTypeWithNestedArrays' => [
                '<?php
                    function foo() {
                        return [
                            "a" => 1,
                            "b" => 2,
                            "c" => [
                                "a" => 1,
                                "b" => 2,
                                "c" => [
                                    "a" => 1,
                                    "b" => 2,
                                    "c" => 3,
                                ],
                            ],
                        ];
                    }',
                '<?php
                    /**
                     * @return ((int|int[])[]|int)[]
                     *
                     * @psalm-return array{a: int, b: int, c: array{a: int, b: int, c: array{a: int, b: int, c: int}}}
                     */
                    function foo(): array {
                        return [
                            "a" => 1,
                            "b" => 2,
                            "c" => [
                                "a" => 1,
                                "b" => 2,
                                "c" => [
                                    "a" => 1,
                                    "b" => 2,
                                    "c" => 3,
                                ],
                            ],
                        ];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingObjectLikeReturnTypeSeparateStatements70' => [
                '<?php
                    function foo() {
                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        return ["a" => "goodbye"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{a: string, b?: string}
                     */
                    function foo(): array {
                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        return ["a" => "goodbye"];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingStringArrayReturnTypeFromCall71' => [
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar() {
                        return foo();
                    }',
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    /**
                     * @return string[]
                     *
                     * @psalm-return array<array-key, string>
                     */
                    function bar(): array {
                        return foo();
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingDocblockStringArrayReturnTypeFromCall71' => [
                '<?php
                    /** @return string[] */
                    function foo() {
                        return ["hello"];
                    }

                    function bar() {
                        return foo();
                    }',
                '<?php
                    /** @return string[] */
                    function foo() {
                        return ["hello"];
                    }

                    /**
                     * @return string[]
                     *
                     * @psalm-return array<array-key, string>
                     */
                    function bar() {
                        return foo();
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingNullableStringReturnType71' => [
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar() {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    /**
                     * @return null|string
                     */
                    function bar() {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingNullableStringReturnTypeWithMaybeReturn71' => [
                '<?php
                    function foo() {
                      if (rand(0, 1)) return new stdClass;
                    }',
                '<?php
                    /**
                     * @return null|stdClass
                     */
                    function foo() {
                      if (rand(0, 1)) return new stdClass;
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingUnsafeNullableStringReturnType71' => [
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar() {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar(): ?string {
                        foreach (foo() as $f) {
                            return $f;
                        }
                        return null;
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addSelfReturnType' => [
                '<?php
                    class A {
                        public function foo() {
                            return $this;
                        }
                    }',
                '<?php
                    class A {
                        public function foo(): self {
                            return $this;
                        }
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addIterableReturnType' => [
                '<?php
                    function foo() {
                        return bar();
                    }

                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                '<?php
                    function foo(): iterable {
                        return bar();
                    }

                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addGenericIterableReturnType' => [
                '<?php
                    function foo() {
                        return bar();
                    }

                    /** @return iterable<int> */
                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                '<?php
                    /**
                     * @return iterable
                     *
                     * @psalm-return iterable<mixed, int>
                     */
                    function foo(): iterable {
                        return bar();
                    }

                    /** @return iterable<int> */
                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addMissingNullableReturnTypeInDocblockOnly71' => [
                '<?php
                    function foo() {
                      if (rand(0, 1)) {
                        return;
                      }

                      return "hello";
                    }

                    function bar() {
                      if (rand(0, 1)) {
                        return;
                      }

                      if (rand(0, 1)) {
                        return null;
                      }

                      return "hello";
                    }',
                '<?php
                    /**
                     * @return null|string
                     */
                    function foo() {
                      if (rand(0, 1)) {
                        return;
                      }

                      return "hello";
                    }

                    /**
                     * @return null|string
                     */
                    function bar() {
                      if (rand(0, 1)) {
                        return;
                      }

                      if (rand(0, 1)) {
                        return null;
                      }

                      return "hello";
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'addMissingVoidReturnTypeToOldArray71' => [
                '<?php
                    function foo(array $a = array()) {}
                    function bar(array $a = array() )  {}',
                '<?php
                    function foo(array $a = array()): void {}
                    function bar(array $a = array() ): void  {}',
                '7.1',
                ['MissingReturnType'],
                false,
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
            'dontAddMissingVoidReturnType56' => [
                '<?php
                    /** @return void */
                    function foo() { }

                    function bar() {
                        return foo();
                    }',
                '<?php
                    /** @return void */
                    function foo() { }

                    function bar() {
                        return foo();
                    }',
                '5.6',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingVoidReturnTypehintForSubclass71' => [
                '<?php
                    class A {
                        public function foo() {}
                    }

                    class B extends A {
                        public function foo() {}
                    }',
                '<?php
                    class A {
                        /**
                         * @return void
                         */
                        public function foo() {}
                    }

                    class B extends A {
                        /**
                         * @return void
                         */
                        public function foo() {}
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingVoidReturnTypehintForPrivateMethodInSubclass71' => [
                '<?php
                    class A {
                        private function foo() {}
                    }

                    class B extends A {
                        private function foo() {}
                    }',
                '<?php
                    class A {
                        private function foo(): void {}
                    }

                    class B extends A {
                        private function foo(): void {}
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingClassReturnTypehintForSubclass71' => [
                '<?php
                    class A {
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {
                        public function foo() {
                            return $this;
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'dontAddMissingClassReturnTypehintForSubSubclass71' => [
                '<?php
                    class A {
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {}

                    class C extends B {
                        public function foo() {
                            return $this;
                        }
                    }',
                '<?php
                    class A {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {}

                    class C extends B {
                        /**
                         * @return self
                         */
                        public function foo() {
                            return $this;
                        }
                    }',
                '7.1',
                ['MissingReturnType'],
                true,
            ],
            'addMissingTemplateReturnType' => [
                '<?php
                    /**
                     * @template T as object
                     *
                     * @param object $t Flabble
                     *
                     * @psalm-param T $t
                     */
                    function foo($t) {
                        return $t;
                    }',
                '<?php
                    /**
                     * @template T as object
                     *
                     * @param object $t Flabble
                     *
                     * @psalm-param T $t
                     *
                     * @return object
                     *
                     * @psalm-return T
                     */
                    function foo($t) {
                        return $t;
                    }',
                '7.4',
                ['MissingReturnType'],
                true
            ],
            'missingReturnTypeAnonymousClass' => [
                '<?php
                    function logger() {
                        return new class {};
                    }',
                '<?php
                    function logger(): object {
                        return new class {};
                    }',
                '7.4',
                ['MissingReturnType'],
                true
            ],
            'missingReturnTypeAnonymousClassPre72' => [
                '<?php
                    function logger() {
                        return new class {};
                    }',
                '<?php
                    /**
                     * @return object
                     */
                    function logger() {
                        return new class {};
                    }',
                '7.1',
                ['MissingReturnType'],
                true
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
                     * @return string
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
                    /**
                     * @return string
                     */
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
                     * @return string
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
                     * @return Closure
                     *
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
            'addMissingReturnTypeWhenParentHasNone' => [
                '<?php
                    class A {
                        /** @psalm-suppress MissingReturnType */
                        public function foo() {
                            return;
                        }
                    }

                    class B extends A {
                        public function foo() {
                            return;
                        }
                    }',
                '<?php
                    class A {
                        /** @psalm-suppress MissingReturnType */
                        public function foo() {
                            return;
                        }
                    }

                    class B extends A {
                        /**
                         * @return void
                         */
                        public function foo() {
                            return;
                        }
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
            ],
            'dontAddMissingReturnTypeWhenChildHasNone' => [
                '<?php
                    class A {
                        public function foo() {}
                    }

                    class B extends A {
                        /** @psalm-suppress MissingReturnType */
                        public function foo() {}
                    }',
                '<?php
                    class A {
                        /**
                         * @return void
                         */
                        public function foo() {}
                    }

                    class B extends A {
                        /** @psalm-suppress MissingReturnType */
                        public function foo() {}
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
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
                        /**
                         * @return void
                         */
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
                        /**
                         * @return void
                         */
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
                    /**
                     * @return void
                     */
                    function foo(): void {}',
                '7.3',
                ['InvalidReturnType'],
                false,
                false,
            ],
            'noEmptyArrayAnnotation' => [
                '<?php
                    function foo() {
                        return [];
                    }',
                '<?php
                    /**
                     * @return array
                     *
                     * @psalm-return array<empty, empty>
                     */
                    function foo(): array {
                        return [];
                    }',
                '7.3',
                ['MissingReturnType'],
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
                         * @psalm-return array{0: string}
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
                         * @psalm-return array{0: string}
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
                         * @return self
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
        ];
    }
}
