<?php

declare(strict_types=1);

namespace Psalm\Tests\FileManipulation;

class ParamNameMismatchTest extends FileManipulationTestCase
{
    public function providerValidCodeParse(): array
    {
        return [
            'fixMismatchingParamName74' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['ParamNameMismatch'],
                'safe_types' => true,
            ],
            'fixIfNewNameAlreadyExists74' => [
                'input' => '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $string, bool $b = false) : void {
                            $str_new = $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $str, bool $b = false) : void {
                            $str_new = $str;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['ParamNameMismatch'],
                'safe_types' => true,
            ],
            'noFixIfNewNameAndOldNameAlreadyExists74' => [
                'input' => '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $string, bool $b = false) : void {
                            $str = $string;
                            $str_new = $string;
                        }
                    }',
                'output' => '<?php
                    class A {
                        public function foo(string $str, bool $b = false) : void {}
                    }

                    class AChild extends A {
                        public function foo(string $string, bool $b = false) : void {
                            $str = $string;
                            $str_new = $string;
                        }
                    }',
                'php_version' => '7.1',
                'issues_to_fix' => ['ParamNameMismatch'],
                'safe_types' => true,
            ],
            'fixMismatchingParamNameWithDocblockType74' => [
                'input' => '<?php
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
                'output' => '<?php
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
                'php_version' => '7.1',
                'issues_to_fix' => ['ParamNameMismatch'],
                'safe_types' => true,
            ],
        ];
    }
}
