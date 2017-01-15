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
        $this->markTestSkipped('PHP 7.1 syntax');
        $this->addFile(
            'somefile.php',
            '<?php
                $d = array_filter(["a" => 5, "b" => 12, "c" => null]);
                $e = array_filter(["a" => 5, "b" => 12, "c" => null],
                   /** @param ?int $i */
                   function($i) : bool { return true; });'
        );

        $file_checker = new FileChecker('somefile.php', $this->project_checker);
        $context = new Context();
        $file_checker->visitAndAnalyzeMethods($context);
        $this->project_checker->checkClassReferences();

        $this->assertSame('array<string, int>', (string) $context->vars_in_scope['$d']);
        $this->assertSame('array<string, null|int>', (string) $context->vars_in_scope['$e']);
    }

    /**
     * @return void
     */
    public function testArrayFilterAdvanced()
    {
        $this->markTestSkipped('PHP 7.1 syntax');
        if (version_compare((string)PHP_VERSION, '5.6.0', '>=')) {
            $this->addFile(
                'somefile.php',
                '<?php
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
                    );'
            );

            $file_checker = new FileChecker('somefile.php', $this->project_checker);
            $context = new Context();
            $file_checker->visitAndAnalyzeMethods($context);
            $this->project_checker->checkClassReferences();

            $this->assertSame('array<string, null|int>', (string) $context->vars_in_scope['$f']);
            $this->assertSame('array<string, null|int>', (string) $context->vars_in_scope['$g']);
        }
    }

    /**
     * @return void
     */
    public function testArrayFilterUseKey()
    {
        if (version_compare((string)PHP_VERSION, '5.6.0', '>=')) {
            $this->addFile(
                getcwd() . '/src/somefile.php',
                '<?php
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
                    );'
            );

            $file_checker = new FileChecker(getcwd() . '/src/somefile.php', $this->project_checker);
            $context = new Context();
            $file_checker->visitAndAnalyzeMethods($context);
            $this->project_checker->checkClassReferences();
        }
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'typedArrayWithDefault' => [
                '<?php
                    class A {}

                    /** @param array<A> $a */
                    function fooFoo(array $a = []) : void {

                    }',
            ],
            'typedArrayWithDefault' => [
                '<?php
                    $a = abs(-5);
                    $b = abs(-7.5);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                ],
            ],
            'validDocblockParamDefault' => [
                '<?php
                    /**
                     * @param  int|false $p
                     * @return void
                     */
                    function f($p = false) {}',
            ],
            'byRef' => [
                '<?php
                    function fooFoo(string &$v) : void {}
                    fooFoo($a);',
            ],
            'namespaced' => [
                '<?php
                    namespace A;

                    /** @return void */
                    function f(int $p) {}
                    f(5);',
            ],
            'namespacedRootFunctionCall' => [
                '<?php
                    namespace {
                        /** @return void */
                        function foo() { }
                    }
                    namespace A\B\C {
                        foo();
                    }',
            ],
            'namespacedAliasedFunctionCall' => [
                '<?php
                    namespace Aye {
                        /** @return void */
                        function foo() { }
                    }
                    namespace Bee {
                        use Aye as A;

                        A\foo();
                    }',
            ],
            'arrayKeys' => [
                '<?php
                    $a = array_keys(["a" => 1, "b" => 2]);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
            ],
            'arrayKeysMixed' => [
                '<?php
                    /** @var array */
                    $b = ["a" => 5];
                    $a = array_keys($b);',
                'assertions' => [
                    '$a' => 'array<int, mixed>',
                ],
                'error_levels' => ['MixedArgument'],
            ],
            'arrayValues' => [
                '<?php
                    $b = array_values(["a" => 1, "b" => 2]);',
                'assertions' => [
                    '$b' => 'array<int, int>',
                ],
            ],
            'arrayCombine' => [
                '<?php
                    $c = array_combine(["a", "b", "c"], [1, 2, 3]);',
                'assertions' => [
                    '$c' => 'array<string, int>',
                ],
            ],
            'arrayMerge' => [
                '<?php
                    $d = array_merge(["a", "b", "c"], [1, 2, 3]);',
                'assertions' => [
                    '$d' => 'array<int, int|string>',
                ],
            ],
            'arrayDiff' => [
                '<?php
                    $d = array_diff(["a" => 5, "b" => 12], [5]);',
                'assertions' => [
                    '$d' => 'array<string, int>',
                ],
            ],
            'arrayPopMixed' => [
                '<?php
                    /** @var mixed */
                    $b = ["a" => 5, "c" => 6];
                    $a = array_pop($b);',
                'assertions' => [
                    '$a' => 'mixed',
                    '$b' => 'mixed',
                ],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'uasort' => [
                '<?php
                    uasort(
                      $manifest,
                      function ($a, $b) {
                        return strcmp($a["parent"],$b["parent"]);
                      }
                    );',
                'assertions' => [],
                'error_levels' => ['MixedArrayAccess', 'MixedArgument', 'UntypedParam', 'MissingClosureReturnType'],
            ],
            'byRefAfterCallable' => [
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
                    'MixedArrayAccess',
                ],
            ],
            'extractVarCheck' => [
                '<?php
                    function takesString(string $str) : void {}

                    $foo = null;
                    $a = ["$foo" => "bar"];
                    extract($a);
                    takesString($foo);',
                'assertions' => [],
                'error_levels' => [
                    'MixedAssignment',
                    'MixedArrayAccess',
                ],
            ],
            'arrayMergeObjectLike' => [
                '<?php
                  /**
                   * @param array<string, int> $a
                   * @return array<string, int>
                   */
                  function foo($a)
                  {
                    return $a;
                  }

                  $a1 = ["hi" => 3];
                  $a2 = ["bye" => 5];
                  $a3 = array_merge($a1, $a2);

                  foo($a3);',
                'assertions' => [
                    '$a3' => 'array{bye:int, hi:int}',
                ],
            ],
            'goodByRef' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();
                    $b = "goodbye";

                    function fooFoo(string &$v) : void {}

                    fooFoo($a->foo);
                    fooFoo($b);',
            ],
            'arrayRand' => [
                '<?php
                    $vars = ["x" => "a", "y" => "b"];
                    $c = array_rand($vars);
                    $d = $vars[$c];',

                'assertions' => [
                    '$vars' => 'array{x:string, y:string}',
                    '$c' => 'string',
                    '$d' => 'string',
                ],
            ],
            'arrayRandMultiple' => [
                '<?php
                    $vars = ["x" => "a", "y" => "b"];
                    $b = 3;
                    $c = array_rand($vars, 1);
                    $d = array_rand($vars, 2);
                    $e = array_rand($vars, 3);
                    $f = array_rand($vars, $b);',

                'assertions' => [
                    '$vars' => 'array{x:string, y:string}',
                    '$c' => 'string',
                    '$e' => 'array<int, string>',
                    '$f' => 'array<int, string>|string',
                ],
            ],
            'arrayKeysNoEmpty' => [
                '<?php
                    function expect_string(string $x) : void {
                        echo $x;
                    }

                    function test() : void {
                        foreach (array_keys([]) as $key) {
                            expect_string($key);
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'compact' => [
                '<?php
                    function test() : array {
                        return compact(["val"]);
                    }',
            ],
            'objectLikeKeyChecksAgainstGeneric' => [
                '<?php
                    /**
                     * @param array<string, string> $b
                     */
                    function a($b) : string
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
            ],
            'objectLikeKeyChecksAgainstObjectLike' => [
                '<?php
                    /**
                     * @param array{a: string} $b
                     */
                    function a($b) : string
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'invalidScalarArgument' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo("string");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'mixedArgument' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    /** @var mixed */
                    $a = "hello";
                    fooFoo($a);',
                'error_message' => 'MixedArgument',
                'error_levels' => ['MixedAssignment'],
            ],
            'nullArgument' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo(null);',
                'error_message' => 'NullArgument',
            ],
            'tooFewArguments' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'tooManyArguments' => [
                '<?php
                    function fooFoo(int $a) : void {}
                    fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments',
            ],
            'tooManyArgumentsForConstructor' => [
                '<?php
                  class A { }
                  new A("hello");',
                'error_message' => 'TooManyArguments',
            ],
            'typeCoercion' => [
                '<?php
                    class A {}
                    class B extends A{}

                    function fooFoo(B $b) : void {}
                    fooFoo(new A());',
                'error_message' => 'TypeCoercion',
            ],
            'arrayTypeCoercion' => [
                '<?php
                    class A {}
                    class B extends A{}

                    /**
                     * @param  B[]  $b
                     * @return void
                     */
                    function fooFoo(array $b) {}
                    fooFoo([new A()]);',
                'error_message' => 'TypeCoercion',
            ],
            'duplicateParam' => [
                '<?php
                    /**
                     * @return void
                     */
                    function f($p, $p) {}',
                'error_message' => 'DuplicateParam',
                'error_levels' => ['UntypedParam'],
            ],
            'invalidParamDefault' => [
                '<?php
                    function f(int $p = false) {}',
                'error_message' => 'InvalidParamDefault',
            ],
            'invalidDocblockParamDefault' => [
                '<?php
                    /**
                     * @param  int $p
                     * @return void
                     */
                    function f($p = false) {}',
                'error_message' => 'InvalidParamDefault',
            ],
            'badByRef' => [
                '<?php
                    function fooFoo(string &$v) : void {}
                    fooFoo("a");',
                'error_message' => 'InvalidPassByReference',
            ],
            'invalidArgAfterCallable' => [
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
                    'MixedArrayAccess',
                ],
            ],
            'undefinedFunctionInArrayMap' => [
                '<?php
                    array_map(
                        "undefined_function",
                        [1, 2, 3]
                    );',
                'error_message' => 'UndefinedFunction',
            ],
            'objectLikeKeyChecksAgainstDifferentGeneric' => [
                '<?php
                    /**
                     * @param array<string, int> $b
                     */
                    function a($b) : int
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'objectLikeKeyChecksAgainstDifferentObjectLike' => [
                '<?php
                    /**
                     * @param array{a: int} $b
                     */
                    function a($b) : int
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
        ];
    }
}
