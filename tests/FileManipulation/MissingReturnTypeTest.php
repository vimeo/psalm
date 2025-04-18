<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class MissingReturnTypeTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'addMissingVoidReturnType56' => [
                'input' => '<?php
                    function foo() { }',
                'output' => '<?php
                    /**
                     * @return void
                     */
                    function foo() { }',
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingVoidReturnType70' => [
                'input' => '<?php
                    function foo() { }',
                'output' => '<?php
                    /**
                     * @return void
                     */
                    function foo() { }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingVoidReturnType71' => [
                'input' => '<?php
                    function foo() { }',
                'output' => '<?php
                    function foo(): void { }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringReturnType56' => [
                'input' => '<?php
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
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringReturnType70' => [
                'input' => '<?php
                    function foo() {
                        return "hello";
                    }',
                'output' => '<?php
                    function foo(): string {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingNullableStringReturnType56' => [
                'input' => '<?php
                    function foo() {
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
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingNullableStringReturnType70' => [
                'input' => '<?php
                    function foo() {
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
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringReturnType71' => [
                'input' => '<?php
                    function foo() {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'output' => '<?php
                    function foo(): ?string {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringReturnTypeWithComment71' => [
                'input' => '<?php
                    function foo() /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'output' => '<?php
                    function foo(): ?string /** : ?string */ {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringReturnTypeWithSingleLineComment71' => [
                'input' => '<?php
                    function foo()// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'output' => '<?php
                    function foo(): ?string// cool
                    {
                        return rand(0, 1) ? "hello" : null;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringArrayReturnType56' => [
                'input' => '<?php
                    function foo() {
                        return ["hello"];
                    }',
                'output' => '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return list{\'hello\'}
                     */
                    function foo() {
                        return ["hello"];
                    }',
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringArrayReturnType70' => [
                'input' => '<?php
                    function foo() {
                        return ["hello"];
                    }',
                'output' => '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return list{\'hello\'}
                     */
                    function foo(): array {
                        return ["hello"];
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingTKeyedArrayReturnType70' => [
                'input' => '<?php
                    function foo() {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                'output' => '<?php
                    /**
                     * @return string[]
                     *
                     * @psalm-return array{a: \'goodbye\'|\'hello\', b?: \'hello again\'}
                     */
                    function foo(): array {
                        return rand(0, 1) ? ["a" => "hello"] : ["a" => "goodbye", "b" => "hello again"];
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingTKeyedArrayReturnTypeWithEmptyArray' => [
                'input' => '<?php
                    function foo() {
                        if (rand(0, 1)) {
                            return [];
                        }

                        return [
                            "a" => 1,
                            "b" => 2,
                        ];
                    }',
                'output' => '<?php
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
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingTKeyedArrayReturnTypeWithNestedArrays' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingTKeyedArrayReturnTypeSeparateStatements70' => [
                'input' => '<?php
                    function foo() {
                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        if (rand(0, 1)) {
                            return ["a" => "hello", "b" => "hello again"];
                        }

                        return ["a" => "goodbye"];
                    }',
                'output' => '<?php
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
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingStringArrayReturnTypeFromCall71' => [
                'input' => '<?php
                    /** @return string[] */
                    function foo(): array {
                        return ["hello"];
                    }

                    function bar() {
                        return foo();
                    }',
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingDocblockStringArrayReturnTypeFromCall71' => [
                'input' => '<?php
                    /** @return string[] */
                    function foo() {
                        return ["hello"];
                    }

                    function bar() {
                        return foo();
                    }',
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingNullableStringReturnType71' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingNullableStringReturnTypeWithMaybeReturn71' => [
                'input' => '<?php
                    function foo() {
                      if (rand(0, 1)) return new stdClass;
                    }',
                'output' => '<?php
                    /**
                     * @return null|stdClass
                     */
                    function foo() {
                      if (rand(0, 1)) return new stdClass;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingUnsafeNullableStringReturnType71' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'addSelfReturnType' => [
                'input' => '<?php
                    class A {
                        public function foo() {
                            return $this;
                        }
                    }',
                'output' => '<?php
                    class A {
                        /**
                         * @return static
                         */
                        public function foo(): self {
                            return $this;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'addIterableReturnType' => [
                'input' => '<?php
                    function foo() {
                        return bar();
                    }

                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                'output' => '<?php
                    function foo(): iterable {
                        return bar();
                    }

                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'addGenericIterableReturnType' => [
                'input' => '<?php
                    function foo() {
                        return bar();
                    }

                    /** @return iterable<int> */
                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                'output' => '<?php
                    /**
                     * @psalm-return iterable<mixed, int>
                     */
                    function foo(): iterable {
                        return bar();
                    }

                    /** @return iterable<int> */
                    function bar(): iterable {
                        return [1, 2, 3];
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'addMissingNullableReturnTypeInDocblockOnly71' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'addMissingVoidReturnTypeToOldArray71' => [
                'input' => '<?php
                    function foo(array $a = array()) {}
                    function bar(array $a = array() )  {}',
                'output' => '<?php
                    function foo(array $a = array()): void {}
                    function bar(array $a = array() ): void  {}',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'dontAddMissingVoidReturnType56' => [
                'input' => '<?php
                    /** @return void */
                    function foo() { }

                    function bar() {
                        return foo();
                    }',
                'output' => '<?php
                    /** @return void */
                    function foo() { }

                    function bar() {
                        return foo();
                    }',
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'dontAddMissingVoidReturnTypehintForSubclass71' => [
                'input' => '<?php
                    class A {
                        public function foo() {}
                    }

                    class B extends A {
                        public function foo() {}
                    }',
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'dontAddMissingVoidReturnTypehintForPrivateMethodInSubclass71' => [
                'input' => '<?php
                    class A {
                        private function foo() {}
                    }

                    class B extends A {
                        private function foo() {}
                    }',
                'output' => '<?php
                    class A {
                        private function foo(): void {}
                    }

                    class B extends A {
                        private function foo(): void {}
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'dontAddMissingClassReturnTypehintForSubclass71' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'dontAddMissingClassReturnTypehintForSubSubclass71' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingTemplateReturnType' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'missingReturnTypeAnonymousClass' => [
                'input' => '<?php
                    function logger() {
                        return new class {};
                    }',
                'output' => '<?php
                    function logger(): object {
                        return new class {};
                    }',
                'php_version' => '7.4',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'missingReturnTypeAnonymousClassPre72' => [
                'input' => '<?php
                    function logger() {
                        return new class {};
                    }',
                'output' => '<?php
                    /**
                     * @return object
                     */
                    function logger() {
                        return new class {};
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
            ],
            'addMissingReturnTypeWhenParentHasNone' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'dontAddMissingReturnTypeWhenChildHasNone' => [
                'input' => '<?php
                    class A {
                        public function foo() {}
                    }

                    class B extends A {
                        /** @psalm-suppress MissingReturnType */
                        public function foo() {}
                    }',
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'noEmptyArrayAnnotation' => [
                'input' => '<?php
                    function foo() {
                        return [];
                    }',
                'output' => '<?php
                    /**
                     * @psalm-return array<never, never>
                     */
                    function foo(): array {
                        return [];
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'noReturnNullButReturnTrue' => [
                'input' => '<?php
                    function a() {
                        if (rand(0,1)){
                            return true;
                        }
                    }',
                'output' => '<?php
                    /**
                     * @return null|true
                     */
                    function a() {
                        if (rand(0,1)){
                            return true;
                        }
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'returnNullAndReturnTrue' => [
                'input' => '<?php
                    function a() {
                        if (rand(0,1)){
                            return true;
                        }

                        return null;
                    }',
                'output' => '<?php
                    /**
                     * @return null|true
                     */
                    function a(): ?bool {
                        if (rand(0,1)){
                            return true;
                        }

                        return null;
                    }',
                'php_version' => '7.3',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
            ],
            'staticReturn5.6' => [
                'input' => '<?php
                    class HelloWorld
                    {
                        public function sayHello()
                        {
                            return $this;
                        }
                    }',
                'output' => '<?php
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
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'staticReturn7.0' => [
                'input' => '<?php
                    class HelloWorld
                    {
                        public function sayHello()
                        {
                            return $this;
                        }
                    }',
                'output' => '<?php
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
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'staticReturn8.0' => [
                'input' => '<?php
                    class HelloWorld
                    {
                        public function sayHello()
                        {
                            return $this;
                        }
                    }',
                'output' => '<?php
                    class HelloWorld
                    {
                        public function sayHello(): static
                        {
                            return $this;
                        }
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'arrayKeyReturn' => [
                'input' => '<?php
                    function scope(array $array) {
                        return (array_keys($array))[0] ?? null;
                    }',
                'output' => '<?php
                    /**
                     * @return (int|string)|null
                     *
                     * @psalm-return array-key|null
                     */
                    function scope(array $array) {
                        return (array_keys($array))[0] ?? null;
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'returnIntOrString' => [
                'input' => '<?php
                    function scope(int $i, string $s) {
                        return rand(0, 1) ? $i : $s;
                    }',
                'output' => '<?php
                    function scope(int $i, string $s): int|string {
                        return rand(0, 1) ? $i : $s;
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'returnIntOrString80' => [
                'input' => '<?php
                    function scope(int $i, string $s) {
                        return rand(0, 1) ? $i : $s;
                    }',
                'output' => '<?php
                    function scope(int $i, string $s): int|string {
                        return rand(0, 1) ? $i : $s;
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'returnExtendedAnonymClass' => [
                'input' => '<?php
                    class A {}

                    function f()
                    {
                        $a = new class extends A {};
                        /** @psalm-trace $a */;
                        return $a;
                    }',
                'output' => '<?php
                    class A {}

                    function f(): A
                    {
                        $a = new class extends A {};
                        /** @psalm-trace $a */;
                        return $a;
                    }',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'returnExtendedAnonymClassOld' => [
                'input' => '<?php
                    class A {}

                    function f()
                    {
                        return new class extends A {};
                    }',
                'output' => '<?php
                    class A {}

                    /**
                     * @return A
                     */
                    function f()
                    {
                        return new class extends A {};
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'never' => [
                'input' => '<?php
                    function f() {
                        exit(1);
                    }
                ',
                'output' => '<?php
                    /**
                     * @return never
                     */
                    function f() {
                        exit(1);
                    }
                ',
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => false,
                'allow_backwards_incompatible_changes' => true,
            ],
            'WithAttributes' => [
                'input' => '<?php

                    class A
                    {
                        #[Foo()]
                        public function bar()
                        {
                        }
                    }
                    ',
                'output' => '<?php

                    class A
                    {
                        #[Foo()]
                        public function bar(): void
                        {
                        }
                    }
                    ',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingReturnType'],
                'safe_types' => true,
                'allow_backwards_incompatible_changes' => true,
            ],
        ];
    }
}
