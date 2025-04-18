<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class Php70Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'functionTypeHints' => [
                'code' => '<?php
                    function indexof(string $haystack, string $needle): int
                    {
                        $pos = strpos($haystack, $needle);

                        if ($pos === false) {
                            return -1;
                        }

                        return $pos;
                    }

                    $a = indexof("arr", "a");',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'methodTypeHints' => [
                'code' => '<?php
                    class Foo {
                        public static function indexof(string $haystack, string $needle): int
                        {
                            $pos = strpos($haystack, $needle);

                            if ($pos === false) {
                                return -1;
                            }

                            return $pos;
                        }
                    }

                    $a = Foo::indexof("arr", "a");',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'nullCoalesce' => [
                'code' => '<?php
                    /** @var int $i */
                    $i = 0;
                    $arr = ["hello", "goodbye"];
                    $a = $arr[$i] ?? null;',
                'assertions' => [
                    '$a' => 'null|string',
                ],
            ],
            'nullCoalesceWithNullableOnLeft' => [
                'code' => '<?php
                    /** @return ?string */
                    function foo() {
                        return rand(0, 10) > 5 ? "hello" : null;
                    }
                    $a = foo() ?? "goodbye";',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'nullCoalesceWithReference' => [
                'code' => '<?php
                    $var = 0;
                    ($a =& $var) ?? "hello";',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'spaceship' => [
                'code' => '<?php
                    $a = 1 <=> 1;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'defineArray' => [
                'code' => '<?php
                    define("ANIMALS", [
                        "dog",
                        "cat",
                        "bird"
                    ]);

                    $a = ANIMALS[1];',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'anonymousClassLogger' => [
                'code' => '<?php
                    interface Logger {
                        /** @return void */
                        public function log(string $msg);
                    }

                    class Application {
                        /** @var Logger|null */
                        private $logger;

                        /** @return void */
                        public function setLogger(Logger $logger) {
                             $this->logger = $logger;
                        }
                    }

                    $app = new Application;
                    $app->setLogger(new class implements Logger {
                        /** @return void */
                        public function log(string $msg) {
                            echo $msg;
                        }
                    });',
            ],
            'anonymousClassFunctionReturnType' => [
                'code' => '<?php
                    $class = new class {
                        public function f(): int {
                            return 42;
                        }
                    };

                    function g(int $i): int {
                        return $i;
                    }

                    $x = g($class->f());',
            ],
            'anonymousClassStatement' => [
                'code' => '<?php
                    new class {};',
            ],
            'anonymousClassTwoFunctions' => [
                'code' => '<?php
                    interface I {}

                    class A
                    {
                        /** @var ?I */
                        protected $i;

                        public function foo(): void
                        {
                            $this->i = new class implements I {};
                        }

                        public function foo2(): void {} // commenting this line out fixes
                    }',
            ],
            'anonymousClassExtendsWithThis' => [
                'code' => '<?php
                    class A {
                        public function foo() : void {}
                    }
                    $class = new class extends A {
                        public function f(): int {
                            $this->foo();
                            return 42;
                        }
                    };',
            ],
            'returnAnonymousClass' => [
                'code' => '<?php
                    /** @return object */
                    function getNewAnonymousClass() {
                        return new class {};
                    }',
            ],
            'returnAnonymousClassInClass' => [
                'code' => '<?php
                    class A {
                        /** @return object */
                        public function getNewAnonymousClass() {
                            return new class {};
                        }
                    }',
            ],
            'multipleUse' => [
                'code' => '<?php
                    namespace Name\Space {
                        class A {

                        }

                        class B {

                        }
                    }

                    namespace Noom\Spice {
                        use Name\Space\{
                            A,
                            B
                        };

                        new A();
                        new B();
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'anonymousClassWithBadStatement' => [
                'code' => '<?php
                    $foo = new class {
                        public function a() {
                            new B();
                        }
                    };',
                'error_message' => 'UndefinedClass',
            ],
            'anonymousClassWithInvalidFunctionReturnType' => [
                'code' => '<?php
                    $foo = new class {
                        public function a(): string {
                            return 5;
                        }
                    };',
                'error_message' => 'InvalidReturnStatement',
            ],
        ];
    }
}
