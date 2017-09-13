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
                    '$a' => 'int',
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
                    '$a' => 'int',
                ],
            ],
            'nullCoalesce' => [
                '<?php
                    $arr = ["hello", "goodbye"];
                    $a = $arr[rand(0, 10)] ?? null;',
                'assertions' => [
                    '$a' => 'string|null',
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

            'anonymousClassStatement' => [
                '<?php
                    new class {};',
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
            'generatorDelegation' => [
                '<?php
                    /**
                     * @return Generator<int,int>
                     * @psalm-generator-return int
                     */
                    function count_to_ten() : Generator {
                        yield 1;
                        yield 2;
                        yield from [3, 4];
                        yield from new ArrayIterator([5, 6]);
                        yield from seven_eight();
                        return yield from nine_ten();
                    }

                    /**
                     * @return Generator<int,int>
                     */
                    function seven_eight() : Generator {
                        yield 7;
                        yield from eight();
                    }

                    /**
                     * @return Generator<int,int>
                     */
                    function eight() : Generator {
                        yield 8;
                    }

                    /**
                     * @return Generator<int,int>
                     * @psalm-generator-return int
                     */
                    function nine_ten() : Generator {
                        yield 9;
                        return 10;
                    }

                    $gen = count_to_ten();
                    foreach ($gen as $num) {
                        echo "$num ";
                    }
                    $gen2 = $gen->getReturn();',
                'assertions' => [
                    '$gen' => 'Generator<int, int>',
                    '$gen2' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment'],
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
