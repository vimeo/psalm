<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class ParamTypeManipulationTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'fixMismatchingDocblockParamType70' => [
                'input' => '<?php
                    /**
                     * @param int $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                'output' => '<?php
                    /**
                     * @param string $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MismatchingDocblockParamType'],
                'safe_types' => true,
            ],
            'fixMismatchingDocblockWithDescriptionParamType70' => [
                'input' => '<?php
                    /**
                     * @param int $s the string
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                'output' => '<?php
                    /**
                     * @param string $s the string
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MismatchingDocblockParamType'],
                'safe_types' => true,
            ],
            'fixNamespacedMismatchingDocblockParamsType70' => [
                'input' => '<?php
                    namespace Foo\Bar {
                        class A {
                            /**
                             * @param \B $b
                             * @param \C $c
                             */
                            function foo(B $b, C $c): string {
                                return "hello";
                            }
                        }
                        class B {}
                        class C {}
                    }',
                'output' => '<?php
                    namespace Foo\Bar {
                        class A {
                            /**
                             * @param B $b
                             * @param C $c
                             */
                            function foo(B $b, C $c): string {
                                return "hello";
                            }
                        }
                        class B {}
                        class C {}
                    }',
                'php_version' => '7.0',
                'issues_to_fix' => ['MismatchingDocblockParamType'],
                'safe_types' => true,
            ],
            'noStringParamType' => [
                'input' => '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    (new C)->fooFoo("hello");',
                'output' => '<?php
                    class C {
                        /**
                         * @psalm-param \'hello\' $a
                         */
                        public function fooFoo(string $a): void {}
                    }

                    (new C)->fooFoo("hello");',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'noStringParamTypeWithVariableCall' => [
                'input' => '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    /** @var mixed */
                    $c = null;
                    $c->fooFoo("hello");

                    (new C)->fooFoo("hello");',
                'output' => '<?php
                    class C {
                        /**
                         * @param string $a
                         *
                         * @psalm-param \'hello\' $a
                         */
                        public function fooFoo($a): void {}
                    }

                    /** @var mixed */
                    $c = null;
                    $c->fooFoo("hello");

                    (new C)->fooFoo("hello");',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'noStringParamTypeWithDocblockCall' => [
                'input' => '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    /**
                     * @param string $a
                     */
                    function callsWithString($a): void {
                        (new C)->fooFoo($a);
                    }',
                'output' => '<?php
                    class C {
                        /**
                         * @param string $a
                         */
                        public function fooFoo($a): void {}
                    }

                    /**
                     * @param string $a
                     */
                    function callsWithString($a): void {
                        (new C)->fooFoo($a);
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'noStringParamTypeWithDocblockAndDescriptionCall' => [
                'input' => '<?php
                    class C {
                        /**
                         * @param $ab the string you pass in
                         */
                        public function fooFoo($ab): void {}
                    }

                    /**
                     * @param string $ab
                     */
                    function callsWithString($ab): void {
                        (new C)->fooFoo($ab);
                    }',
                'output' => '<?php
                    class C {
                        /**
                         * @param string $ab the string you pass in
                         */
                        public function fooFoo($ab): void {}
                    }

                    /**
                     * @param string $ab
                     */
                    function callsWithString($ab): void {
                        (new C)->fooFoo($ab);
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'noStringParamType56' => [
                'input' => '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    (new C)->fooFoo("hello");',
                'output' => '<?php
                    class C {
                        /**
                         * @param string $a
                         *
                         * @psalm-param \'hello\' $a
                         */
                        public function fooFoo($a): void {}
                    }

                    (new C)->fooFoo("hello");',
                'php_version' => '5.6',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'noBoolParamTypeWithDefault' => [
                'input' => '<?php
                    class C {
                        public function fooFoo($a = true): void {}
                    }

                    (new C)->fooFoo(false);',
                'output' => '<?php
                    class C {
                        public function fooFoo(bool $a = true): void {}
                    }

                    (new C)->fooFoo(false);',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'noStringParamTypeParent' => [
                'input' => '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    class D extends C {}

                    (new D)->fooFoo("hello");',
                'output' => '<?php
                    class C {
                        /**
                         * @psalm-param \'hello\' $a
                         */
                        public function fooFoo(string $a): void {}
                    }

                    class D extends C {}

                    (new D)->fooFoo("hello");',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'stringParamTypeNoOp' => [
                'input' => '<?php
                    class C {
                        public function fooFoo(string $a): void {}
                    }

                    (new C)->fooFoo("hello");',
                'output' => '<?php
                    class C {
                        public function fooFoo(string $a): void {}
                    }

                    (new C)->fooFoo("hello");',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'addMissingByRefParamType' => [
                'input' => '<?php
                    class C {
                        public function foo(&$bar) : void {
                            $bar .= " me";
                        }
                    }

                    $a = "hello";
                    (new C)->foo($a);',
                'output' => '<?php
                    class C {
                        /**
                         * @psalm-param \'hello\' $bar
                         */
                        public function foo(string &$bar) : void {
                            $bar .= " me";
                        }
                    }

                    $a = "hello";
                    (new C)->foo($a);',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'NamespacedParamNeeded' => [
                'input' => '<?php
                    class C {
                        public function foo($bar) : void {
                            echo $bar;
                        }
                    }

                    $a = stdClass::class;
                    (new C)->foo($a);',
                'output' => '<?php
                    class C {
                        /**
                         * @psalm-param stdClass::class $bar
                         */
                        public function foo(string $bar) : void {
                            echo $bar;
                        }
                    }

                    $a = stdClass::class;
                    (new C)->foo($a);',
                'php_version' => '7.1',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'StaticParamForbidden' => [
                'input' => '<?php
                    class A {
                        private function foo($bar) : void {
                        }
                        public function test(): void {
                            $this->foo($this->ret());
                        }
                        public function ret(): static {
                            return $this;
                        }
                    }
                    class B extends A {
                    }

                    (new A)->test();
                    (new A)->test();
                ',
                'output' => '<?php
                    class A {
                        /**
                         * @param static $bar
                         */
                        private function foo($bar) : void {
                        }
                        public function test(): void {
                            $this->foo($this->ret());
                        }
                        public function ret(): static {
                            return $this;
                        }
                    }
                    class B extends A {
                    }

                    (new A)->test();
                    (new A)->test();
                ',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => true,
            ],
            'ChangingTypeOfExplicitMixedParam' => [
                'input' => '<?php

                    class ConfigContainer
                    {

                        public function setValue(mixed $value): void
                        {
                        }


                    }

                    function foo(){
                        $config = new ConfigContainer();

                        $config->setValue([1,2,3,4]);

                    }
                    ',
                'output' => '<?php

                    class ConfigContainer
                    {

                        /**
                         * @param int[] $value
                         *
                         * @psalm-param list{1, 2, 3, 4} $value
                         */
                        public function setValue(array $value): void
                        {
                        }


                    }

                    function foo(){
                        $config = new ConfigContainer();

                        $config->setValue([1,2,3,4]);

                    }
                    ',
                'php_version' => '8.0',
                'issues_to_fix' => ['MissingParamType'],
                'safe_types' => false,
            ],
        ];
    }
}
