<?php

namespace Psalm\Tests\FileManipulation;

class ParamTypeManipulationTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'fixMismatchingDocblockParamType70' => [
                '<?php
                    /**
                     * @param int $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                '<?php
                    /**
                     * @param string $s
                     */
                    function foo(string $s): string {
                        return "hello";
                    }',
                '7.0',
                ['MismatchingDocblockParamType'],
                true,
            ],
            'fixNamespacedMismatchingDocblockParamsType70' => [
                '<?php
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
                '<?php
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
                '7.0',
                ['MismatchingDocblockParamType'],
                true,
            ],
            'noStringParamType' => [
                '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    (new C)->fooFoo("hello");',
                '<?php
                    class C {
                        /**
                         * @psalm-param \'hello\' $a
                         */
                        public function fooFoo(string $a): void {}
                    }

                    (new C)->fooFoo("hello");',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noStringParamTypeWithVariableCall' => [
                '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    /** @var mixed */
                    $c = null;
                    $c->fooFoo("hello");

                    (new C)->fooFoo("hello");',
                '<?php
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
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noStringParamTypeWithDocblockCall' => [
                '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    /**
                     * @param string $a
                     */
                    function callsWithString($a): void {
                        (new C)->fooFoo($a);
                    }',
                '<?php
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
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noStringParamType56' => [
                '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    (new C)->fooFoo("hello");',
                '<?php
                    class C {
                        /**
                         * @param string $a
                         *
                         * @psalm-param \'hello\' $a
                         */
                        public function fooFoo($a): void {}
                    }

                    (new C)->fooFoo("hello");',
                '5.6',
                ['MissingParamType'],
                true,
            ],
            'noBoolParamTypeWithDefault' => [
                '<?php
                    class C {
                        public function fooFoo($a = true): void {}
                    }

                    (new C)->fooFoo(false);',
                '<?php
                    class C {
                        public function fooFoo(bool $a = true): void {}
                    }

                    (new C)->fooFoo(false);',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'noStringParamTypeParent' => [
                '<?php
                    class C {
                        public function fooFoo($a): void {}
                    }

                    class D extends C {}

                    (new D)->fooFoo("hello");',
                '<?php
                    class C {
                        /**
                         * @psalm-param \'hello\' $a
                         */
                        public function fooFoo(string $a): void {}
                    }

                    class D extends C {}

                    (new D)->fooFoo("hello");',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'stringParamTypeNoOp' => [
                '<?php
                    class C {
                        public function fooFoo(string $a): void {}
                    }

                    (new C)->fooFoo("hello");',
                '<?php
                    class C {
                        public function fooFoo(string $a): void {}
                    }

                    (new C)->fooFoo("hello");',
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'addMissingByRefParamType' => [
                '<?php
                    class C {
                        public function foo(&$bar) : void {
                            $bar .= " me";
                        }
                    }

                    $a = "hello";
                    (new C)->foo($a);',
                '<?php
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
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'NamespacedParamNeeded' => [
                '<?php
                    class C {
                        public function foo($bar) : void {
                            echo $bar;
                        }
                    }

                    $a = stdClass::class;
                    (new C)->foo($a);',
                '<?php
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
                '7.1',
                ['MissingParamType'],
                true,
            ],
            'StaticParamForbidden' => [
                '<?php
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
                '<?php
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
                '8.0',
                ['MissingParamType'],
                true,
            ],
        ];
    }
}
