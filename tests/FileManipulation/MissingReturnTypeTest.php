<?php
namespace Psalm\Tests\FileManipulation;

class MissingReturnTypeTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool,5?:bool}>
     */
    public function providerValidCodeParse(): array
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
                     *
                     * @psalm-return \'hello\'
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
            'addMissingNullableStringReturnType56' => [
                '<?php
                    function foo() {
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
                     *
                     * @psalm-return \'hello\'|null
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
                     * @psalm-return array{0: \'hello\'}
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
                     * @psalm-return array{0: \'hello\'}
                     */
                    function foo(): array {
                        return ["hello"];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingTKeyedArrayReturnType70' => [
                '<?php
                    function foo() {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{a: \'goodbye\'|\'hello\', b?: \'hello again\'}
                     */
                    function foo(): array {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                '7.0',
                ['MissingReturnType'],
                true,
            ],
            'addMissingTKeyedArrayReturnTypeWithEmptyArray' => [
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
                     * @psalm-return array{a?: 1, b?: 2}
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
            'addMissingTKeyedArrayReturnTypeWithNestedArrays' => [
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
                     * @psalm-return array{a: 1, b: 2, c: array{a: 1, b: 2, c: array{a: 1, b: 2, c: 3}}}
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
            'addMissingTKeyedArrayReturnTypeSeparateStatements70' => [
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
                     * @psalm-return array{a: \'goodbye\'|\'hello\', b?: \'hello again\'}
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
                     * @psalm-return array<string>
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
                     * @psalm-return array<string>
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
                        /**
                         * @return static
                         */
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
                     *
                     * @psalm-return \'hello\'|null
                     */
                    function foo() {
                      if (rand(0, 1)) {
                        return;
                      }

                      return "hello";
                    }

                    /**
                     * @return null|string
                     *
                     * @psalm-return \'hello\'|null
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
                         * @return static
                         */
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {
                        /**
                         * @return static
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
                         * @return static
                         */
                        public function foo() {
                            return $this;
                        }
                    }

                    class B extends A {}

                    class C extends B {
                        /**
                         * @return static
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
            'noReturnNullButReturnTrue' => [
                '<?php
                    function a() {
                        if (rand(0,1)){
                            return true;
                        }
                    }',
                '<?php
                    /**
                     * @return null|true
                     */
                    function a() {
                        if (rand(0,1)){
                            return true;
                        }
                    }',
                '7.3',
                ['MissingReturnType'],
                false,
            ],
            'returnNullAndReturnTrue' => [
                '<?php
                    function a() {
                        if (rand(0,1)){
                            return true;
                        }

                        return null;
                    }',
                '<?php
                    /**
                     * @return null|true
                     */
                    function a(): ?bool {
                        if (rand(0,1)){
                            return true;
                        }

                        return null;
                    }',
                '7.3',
                ['MissingReturnType'],
                false,
            ],
            'staticReturn5.6' => [
                '<?php
                    class HelloWorld
                    {
                        public function sayHello()
                        {
                            return $this;
                        }
                    }',
                '<?php
                    class HelloWorld
                    {
                        /**
                         * @return static
                         */
                        public function sayHello()
                        {
                            return $this;
                        }
                    }',
                '5.6',
                ['MissingReturnType'],
                false,
                true,
            ],
            'staticReturn7.0' => [
                '<?php
                    class HelloWorld
                    {
                        public function sayHello()
                        {
                            return $this;
                        }
                    }',
                '<?php
                    class HelloWorld
                    {
                        /**
                         * @return static
                         */
                        public function sayHello(): self
                        {
                            return $this;
                        }
                    }',
                '7.0',
                ['MissingReturnType'],
                false,
                true,
            ],
            'staticReturn8.0' => [
                '<?php
                    class HelloWorld
                    {
                        public function sayHello()
                        {
                            return $this;
                        }
                    }',
                '<?php
                    class HelloWorld
                    {
                        public function sayHello(): static
                        {
                            return $this;
                        }
                    }',
                '8.0',
                ['MissingReturnType'],
                false,
                true,
            ],
            'arrayKeyReturn' => [
                '<?php
                    function scope(array $array) {
                        return (array_keys($array))[0] ?? null;
                    }',
                '<?php
                    /**
                     * @return (int|string)|null
                     *
                     * @psalm-return array-key|null
                     */
                    function scope(array $array) {
                        return (array_keys($array))[0] ?? null;
                    }',
                '7.1',
                ['MissingReturnType'],
                false,
                true,
            ],
            'returnIntOrString' => [
                '<?php
                    function scope(int $i, string $s) {
                        return rand(0, 1) ? $i : $s;
                    }',
                '<?php
                    function scope(int $i, string $s): int|string {
                        return rand(0, 1) ? $i : $s;
                    }',
                '8.0',
                ['MissingReturnType'],
                false,
                true,
            ],
            'returnIntOrString80' => [
                '<?php
                    function scope(int $i, string $s) {
                        return rand(0, 1) ? $i : $s;
                    }',
                '<?php
                    function scope(int $i, string $s): int|string {
                        return rand(0, 1) ? $i : $s;
                    }',
                '8.0',
                ['MissingReturnType'],
                false,
                true,
            ],
            'returnExtendedAnonymClass' => [
                '<?php
                    class A {}

                    function f()
                    {
                        $a = new class extends A {};
                        /** @psalm-trace $a */;
                        return $a;
                    }',
                '<?php
                    class A {}

                    function f(): A
                    {
                        $a = new class extends A {};
                        /** @psalm-trace $a */;
                        return $a;
                    }',
                '8.0',
                ['MissingReturnType'],
                false,
                true,
            ],
            'returnExtendedAnonymClassOld' => [
                '<?php
                    class A {}

                    function f()
                    {
                        return new class extends A {};
                    }',
                '<?php
                    class A {}

                    /**
                     * @return A
                     */
                    function f()
                    {
                        return new class extends A {};
                    }',
                '7.0',
                ['MissingReturnType'],
                false,
                true,
            ],
            'never' => [
                '<?php
                    function f() {
                        exit(1);
                    }
                ',
                '<?php
                    /**
                     * @return never
                     */
                    function f() {
                        exit(1);
                    }
                ',
                '5.6',
                ['MissingReturnType'],
                false,
                true,
            ],
            'WithAttributes' => [
                '<?php

                    class A
                    {
                        #[Foo()]
                        public function bar()
                        {
                        }
                    }
                    ',
                '<?php

                    class A
                    {
                        #[Foo()]
                        public function bar(): void
                        {
                        }
                    }
                    ',
                '7.1',
                ['MissingReturnType'],
                true,
                true,
            ],
        ];
    }
}
