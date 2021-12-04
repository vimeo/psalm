<?php
namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class Php70Test extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'functionTypeHints' => [
                '<?php
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
                '<?php
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
                '<?php
                    /** @var int $i */
                    $i = 0;
                    $arr = ["hello", "goodbye"];
                    $a = $arr[$i] ?? null;',
                'assertions' => [
                    '$a' => 'null|string',
                ],
            ],
            'nullCoalesceWithNullableOnLeft' => [
                '<?php
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
                '<?php
                    $var = 0;
                    ($a =& $var) ?? "hello";',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'spaceship' => [
                '<?php
                    $a = 1 <=> 1;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'defineArray' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    new class {};',
            ],
            'anonymousClassTwoFunctions' => [
                '<?php
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
                '<?php
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
                '<?php
                    /** @return object */
                    function getNewAnonymousClass() {
                        return new class {};
                    }',
            ],
            'returnAnonymousClassInClass' => [
                '<?php
                    class A {
                        /** @return object */
                        public function getNewAnonymousClass() {
                            return new class {};
                        }
                    }',
            ],
            'multipleUse' => [
                '<?php
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

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'anonymousClassWithBadStatement' => [
                '<?php
                    $foo = new class {
                        public function a() {
                            new B();
                        }
                    };',
                'error_message' => 'UndefinedClass',
            ],
            'anonymousClassWithInvalidFunctionReturnType' => [
                '<?php
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
