<?php
namespace Psalm\Tests\FileManipulation;

class ParamNameMismatchTest extends FileManipulationTestCase
{
    /**
     * @return array<string,array{string,string,string,string[],bool}>
     */
    public function providerValidCodeParse(): array
    {
        return [
            'fixMismatchingParamName74' => [
                '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $string, bool $b = false) : void {
                            $str = $string;
                            $string = $string === "hello" ? "foo" : "bar";
                            echo $string;
                            echo $str;
                        }
                    }',
                '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $str, bool $b = false) : void {
                            $str_new = $str;
                            $str = $str === "hello" ? "foo" : "bar";
                            echo $str;
                            echo $str_new;
                        }
                    }',
                '7.1',
                ['ParamNameMismatch'],
                true,
            ],
            'fixIfNewNameAlreadyExists74' => [
                '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $string, bool $b = false) : void {
                            $str_new = $string;
                        }
                    }',
                '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $str, bool $b = false) : void {
                            $str_new = $str;
                        }
                    }',
                '7.1',
                ['ParamNameMismatch'],
                true,
            ],
            'noFixIfNewNameAndOldNameAlreadyExists74' => [
                '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $string, bool $b = false) : void {
                            $str = $string;
                            $str_new = $string;
                        }
                    }',
                '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $string, bool $b = false) : void {
                            $str = $string;
                            $str_new = $string;
                        }
                    }',
                '7.1',
                ['ParamNameMismatch'],
                true,
            ],
            'fixMismatchingParamNameWithDocblockType74' => [
                '<?php
                    class A {
                        /**
                         * @param string $str
                         */
                        public function foo($str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        /**
                         * @param string $string
                         */
                        public function foo($string, bool $b = false) : void {}
                    }',
                '<?php
                    class A {
                        /**
                         * @param string $str
                         */
                        public function foo($str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        /**
                         * @param string $str
                         */
                        public function foo($str, bool $b = false) : void {}
                    }',
                '7.1',
                ['ParamNameMismatch'],
                true,
            ],
        ];
    }
}
