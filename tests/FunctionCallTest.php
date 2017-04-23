<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Context;

class FunctionCallTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return void
     */
    public function testArrayFilter()
    {
        $stmts = self::$parser->parse('<?php
        $d = array_filter(["a" => 5, "b" => 12, "c" => null]);
        $e = array_filter(["a" => 5, "b" => 12, "c" => null], function(?int $i) : bool { return true; });
        ');

        $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->assertEquals('array<string, int>', (string) $context->vars_in_scope['$d']);
        $this->assertEquals('array<string, null|int>', (string) $context->vars_in_scope['$e']);

        if (version_compare((string)phpversion(), '5.6.0', '>=')) {
            $stmts = self::$parser->parse('<?php
            $f = array_filter(["a" => 5, "b" => 12, "c" => null], function(?int $val, string $key) : bool { 
                return true; 
            }, ARRAY_FILTER_USE_BOTH);
            $g = array_filter(["a" => 5, "b" => 12, "c" => null], function(string $val) : bool { 
                return true; 
            }, ARRAY_FILTER_USE_KEY);

            $bar = "bar";

            $foo = [
                $bar => function () : string {
                    return "baz";
                },
            ];

            $foo = array_filter(
                $foo,
                function (string $key) : bool {
                    return $key === "bar";
                },
                ARRAY_FILTER_USE_KEY
            );
            ');

            $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
            $context = new Context();
            $file_checker->visitAndAnalyzeMethods($context);
            $this->assertEquals('array<string, null|int>', (string) $context->vars_in_scope['$f']);
            $this->assertEquals('array<string, null|int>', (string) $context->vars_in_scope['$g']);
        }
    }

    /**
     * @return void
     */
    public function testArrayFilterUseKey()
    {
        if (version_compare((string)phpversion(), '5.6.0', '>=')) {
            $stmts = self::$parser->parse('<?php
            $bar = "bar";

            $foo = [
                $bar => function () : string {
                    return "baz";
                },
            ];

            $foo = array_filter(
                $foo,
                function (string $key) : bool {
                    return $key === "bar";
                },
                ARRAY_FILTER_USE_KEY
            );
            ');

            $file_checker = new FileChecker('somefile.php', $this->project_checker, $stmts);
            $context = new Context();
            $file_checker->visitAndAnalyzeMethods($context);
        }
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'typed-array-with-default' => [
                '<?php
                    class A {}
            
                    /** @param array<A> $a */
                    function fooFoo(array $a = []) : void {
            
                    }'
            ],
            'valid-docblock-param-default' => [
                '<?php
                    /**
                     * @param  int|false $p
                     * @return void
                     */
                    function f($p = false) {}'
            ],
            'by-ref' => [
                '<?php
                    function fooFoo(string &$v) : void {}
                    fooFoo($a);'
            ],
            'namespaced' => [
                '<?php
                    namespace A;
            
                    /** @return void */
                    function f(int $p) {}
                    f(5);'
            ],
            'namespaced-root-function-call' => [
                '<?php
                    namespace {
                        /** @return void */
                        function foo() { }
                    }
                    namespace A\B\C {
                        foo();
                    }'
            ],
            'namespaced-aliased-function-call' => [
                '<?php
                    namespace Aye {
                        /** @return void */
                        function foo() { }
                    }
                    namespace Bee {
                        use Aye as A;
            
                        A\foo();
                    }'
            ],
            'array-keys' => [
                '<?php
                    $a = array_keys(["a" => 1, "b" => 2]);',
                'assertions' => [
                    ['array<int, string>' => '$a']
                ]
            ],
            'array-keys-mixed' => [
                '<?php
                    /** @var array */
                    $b = ["a" => 5];
                    $a = array_keys($b);',
                'assertions' => [
                    ['array<int, mixed>' => '$a']
                ],
                'error_levels' => ['MixedArgument']
            ],
            'array-values' => [
                '<?php
                    $b = array_values(["a" => 1, "b" => 2]);',
                'assertions' => [
                    ['array<int, int>' => '$b']
                ]
            ],
            'array-combine' => [
                '<?php
                    $c = array_combine(["a", "b", "c"], [1, 2, 3]);',
                'assertions' => [
                    ['array<string, int>' => '$c']
                ]
            ],
            'array-merge' => [
                '<?php
                    $d = array_merge(["a", "b", "c"], [1, 2, 3]);',
                'assertions' => [
                    ['array<int, int|string>' => '$d']
                ]
            ],
            'array-diff' => [
                '<?php
                    $d = array_diff(["a" => 5, "b" => 12], [5]);',
                'assertions' => [
                    ['array<string, int>' => '$d']
                ]
            ],
            'by-ref-after-callable' => [
                '<?php
                    /**
                     * @param callable $callback
                     * @return void
                     */
                    function route($callback) {
                      if (!is_callable($callback)) {  }
                      $a = preg_match("", "", $b);
                      if ($b[0]) {}
                    }',
                'assertions' => [],
                'error_levels' => [
                    'MixedAssignment',
                    'MixedArrayAccess'
                ]
            ],
            'extract-var-check' => [
                '<?php
                    function takesString(string $str) : void {}
            
                    $foo = null;
                    $a = ["$foo" => "bar"];
                    extract($a);
                    takesString($foo);',
                'assertions' => [],
                'error_levels' => [
                    'MixedAssignment',
                    'MixedArrayAccess'
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'invalid-scalar-argument' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo("string");',
                'error_message' => 'InvalidScalarArgument'
            ],
            'mixed-argument' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    /** @var mixed */
                    $a = "hello";
                    fooFoo($a);',
                'error_message' => 'MixedArgument',
                'error_levels' => ['MixedAssignment']
            ],
            'null-argument' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo(null);',
                'error_message' => 'NullArgument'
            ],
            'too-few-arguments' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo();',
                'error_message' => 'TooFewArguments'
            ],
            'too-many-arguments' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments'
            ],
            'type-coercion' => [
                '<?php
                    class A {}
                    class B extends A{}
            
                    function fooFoo(B $b) : void {}
                    fooFoo(new A());',
                'error_message' => 'TypeCoercion'
            ],
            'array-type-coercion' => [
                '<?php
                    class A {}
                    class B extends A{}
            
                    /**
                     * @param  B[]  $b
                     * @return void
                     */
                    function fooFoo(array $b) {}
                    fooFoo([new A()]);',
                'error_message' => 'TypeCoercion'
            ],
            'duplicate-param' => [
                '<?php
                    function f($p, $p) {}',
                'error_message' => 'DuplicateParam'
            ],
            'invalid-param-default' => [
                '<?php
                    function f(int $p = false) {}',
                'error_message' => 'InvalidParamDefault'
            ],
            'invalid-docblock-param-default' => [
                '<?php
                    /**
                     * @param  int $p
                     * @return void
                     */
                    function f($p = false) {}',
                'error_message' => 'InvalidParamDefault'
            ],
            // Skipped. Does not throw an error.
            'SKIPPED-bad-by-ref' => [
                '<?php
                    function fooFoo(string &$v) : void {}
                    fooFoo("a");',
                'error_message' => 'InvalidPassByReference'
            ],
            'invalid-arg-after-callable' => [
                '<?php
                    /**
                     * @param callable $callback
                     * @return void
                     */
                    function route($callback) {
                      if (!is_callable($callback)) {  }
                      takes_int("string");
                    }
            
                    function takes_int(int $i) {}',
                'error_message' => 'InvalidScalarArgument',
                'error_levels' => [
                    'MixedAssignment',
                    'MixedArrayAccess'
                ]
            ]
        ];
    }
}
