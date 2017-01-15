<?php
namespace Psalm\Tests;

class Php70Test extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'functionTypeHints' => [
                '<?php
                    function indexof(string $haystack, string $needle) : int
                    {
                        $pos = strpos($haystack, $needle);

                        if ($pos === false) {
                            return -1;
                        }

                        return $pos;
                    }

                    $a = indexof("arr", "a");',
                'assertions' => [
                    ['int' => '$a'],
                ],
            ],
            'methodTypeHints' => [
                '<?php
                    class Foo {
                        public static function indexof(string $haystack, string $needle) : int
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
                    ['int' => '$a'],
                ],
            ],
            'nullCoalesce' => [
                '<?php
                    $arr = ["hello", "goodbye"];
                    $a = $arr[rand(0, 10)] ?? null;',
                'assertions' => [
                    ['string|null' => '$a'],
                ],
            ],
            'nullCoalesceWithReference' => [
                '<?php
                    $var = 0;
                    ($a =& $var) ?? "hello";',
                'assertions' => [
                    ['int' => '$a'],
                ],
            ],
            'spaceship' => [
                '<?php
                    $a = 1 <=> 1;',
                'assertions' => [
                    ['int' => '$a'],
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
                    ['string' => '$a'],
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
                        public function log(string $msg) {
                            echo $msg;
                        }
                    });',
            ],
            'anonymousClassFunctionReturnType' => [
                '<?php
                    $class = new class {
                        public function f() : int {
                            return 42;
                        }
                    };

                    function g(int $i) : int {
                        return $i;
                    }

                    $x = g($class->f());',
            ],
            'generatorWithReturn' => [
                '<?php
                    /**
                     * @return Generator<int,int>
                     * @psalm-generator-return string
                     */
                    function fooFoo(int $i) : Generator {
                        if ($i === 1) {
                            return "bash";
                        }

                        yield 1;
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
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
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
                        public function a() : string {
                            return 5;
                        }
                    };',
                'error_message' => 'InvalidReturnType',
            ],
        ];
    }
}
