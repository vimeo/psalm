<?php
namespace Psalm\Tests\FileManipulation;

class ParamTypeManipulationTest extends FileManipulationTest
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse()
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
                         * @param string $a
                         */
                        public function fooFoo($a): void {}
                    }

                    (new C)->fooFoo("hello");',
                '7.1',
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
                        /**
                         * @param bool $a
                         */
                        public function fooFoo($a = true): void {}
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
                         * @param string $a
                         */
                        public function fooFoo($a): void {}
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
        ];
    }
}
