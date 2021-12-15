<?php

namespace Psalm\Tests\FileManipulation;

class PureAnnotationAdditionTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'addPureAnnotationToFunction' => [
                '<?php
                    function foo(string $s): string {
                        return $s;
                    }',
                '<?php
                    /**
                     * @psalm-pure
                     */
                    function foo(string $s): string {
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'addPureAnnotationToFunctionWithExistingDocblock' => [
                '<?php
                    /**
                     * @return string
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                '<?php
                    /**
                     * @return string
                     *
                     * @psalm-pure
                     */
                    function foo(string $s) {
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToImpureFunction' => [
                '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                '<?php
                    function foo(string $s): string {
                        echo $s;
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToMutationFreeMethod' => [
                '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                '<?php
                    class A {
                        public string $foo = "hello";

                        public function getFoo() : string {
                            return $this->foo;
                        }
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureCall' => [
                '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                '<?php
                    function foo(string $s): string {
                        if (file_exists($s)) {
                            return "";
                        }
                        return $s;
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddPureAnnotationToFunctionWithImpureClosure' => [
                '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                '<?php
                    /** @param list<string> $arr */
                    function foo(array $arr): array {
                        return array_map($arr, function ($s) { echo $s; return $s;});
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddWhenReferencingThis' => [
                '<?php
                    abstract class A {
                        public int $a = 5;

                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                '<?php
                    abstract class A {
                        public int $a = 5;

                        public function foo() : self {
                            return $this;
                        }
                    }

                    class B extends A {}',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'dontAddInChildMethod' => [
                '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function foo(string $s) : string {
                            return $string;
                        }
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
            'doAddInOtherMethod' => [
                '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                '<?php
                    class A {
                        public int $a = 5;

                        public function foo(string $s) : string {
                            return $string . $this->a;
                        }
                    }

                    class B extends A {
                        /**
                         * @psalm-pure
                         */
                        public function bar(string $s) : string {
                            return $string;
                        }
                    }',
                '7.4',
                ['MissingPureAnnotation'],
                true,
            ],
        ];
    }
}
