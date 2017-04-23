<?php
namespace Psalm\Tests;

class ClosureTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'by-ref-use-var' => [
                '<?php
                    /** @return void */
                    function run_function(\Closure $fnc) {
                        $fnc();
                    }
            
                    // here we have to make sure $data exists as a side-effect of calling `run_function`
                    // because it could exist depending on how run_function is implemented
                    /**
                     * @return void
                     * @psalm-suppress MixedArgument
                     */
                    function fn() {
                        run_function(
                            /**
                             * @return void
                             */
                            function() use(&$data) {
                                $data = 1;
                            }
                        );
                        echo $data;
                    }
            
                    fn();'
            ],
            'inferred-arg' => [
                '<?php
                    $bar = ["foo", "bar"];
            
                    $bam = array_map(
                        /**
                         * @psalm-suppress MissingClosureReturnType
                         */
                        function(string $a) {
                            return $a . "blah";
                        },
                        $bar
                    );'
            ],
            'var-return-type' => [
                '<?php
                    $add_one = function(int $a) : int {
                        return $a + 1;
                    };
            
                    $a = $add_one(1);',
                'assertions' => [
                    ['int' => '$a']
                ]
            ],
            'callable-to-closure' => [
                '<?php
                    /**
                     * @return callable
                     */
                    function foo() {
                        return function(string $a) : string {
                            return $a . "blah";
                        };
                    }'
            ],
            'callable' => [
                '<?php
                    function foo(callable $c) : void {
                        echo (string)$c();
                    }'
            ],
            'callable-class' => [
                '<?php
                    class C {
                        public function __invoke() : string {
                            return "You ran?";
                        }
                    }
            
                    function foo(callable $c) : void {
                        echo (string)$c();
                    }
            
                    foo(new C());
            
                    $c2 = new C();
                    $c2();'
            ],
            'correct-param-type' => [
                '<?php
                    $take_string = function(string $s) : string { return $s; };
                    $take_string("string");'
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'wrong-arg' => [
                '<?php
                    $bar = ["foo", "bar"];
            
                    $bam = array_map(
                        function(int $a) : int {
                            return $a + 1;
                        },
                        $bar
                    );',
                'error_message' => 'InvalidScalarArgument'
            ],
            'no-return' => [
                '<?php
                    $bar = ["foo", "bar"];
            
                    $bam = array_map(
                        function(string $a) : string {
                        },
                        $bar
                    );',
                'error_message' => 'InvalidReturnType'
            ],
            'undefined-callable-class' => [
                '<?php
                    class A {
                        public function getFoo() : Foo
                        {
                            return new Foo([]);
                        }
            
                        public function bar($argOne, $argTwo)
                        {
                            $this->getFoo()($argOne, $argTwo);
                        }
                    }',
                'error_message' => 'InvalidFunctionCall',
                'error_levels' => ['UndefinedClass']
            ],
            'possibly-null-function-call' => [
                '<?php
                    /**
                     * @var Closure|null $foo
                     */
                    $foo = null;
            
                    $foo = function ($bar) use (&$foo) : string
                    {
                        if (is_array($bar)) {
                            return $foo($bar);
                        }
            
                        return $bar;
                    };',
                'error_message' => 'PossiblyNullFunctionCall'
            ],
            'string-function-call' => [
                '<?php
                    $bad_one = "hello";
                    $a = $bad_one(1);',
                'error_message' => 'InvalidFunctionCall'
            ],
            'wrong-param-type' => [
                '<?php
                    $take_string = function(string $s) : string { return $s; };
                    $take_string(42);',
                'error_message' => 'InvalidScalarArgument'
            ]
        ];
    }
}
