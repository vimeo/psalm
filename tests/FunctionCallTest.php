<?php
namespace Psalm\Tests;

class FunctionCallTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'arrayFilter' => [
                '<?php
                    $d = array_filter(["a" => 5, "b" => 12, "c" => null]);
                    $e = array_filter(
                        ["a" => 5, "b" => 12, "c" => null],
                        function(?int $i): bool {
                            return true;
                        }
                    );',
                'assertions' => [
                    '$d' => 'array<string, int>',
                    '$e' => 'array<string, int|null>',
                ],
            ],
            'arrayFilterAdvanced' => [
                '<?php
                    $f = array_filter(["a" => 5, "b" => 12, "c" => null], function(?int $val, string $key): bool {
                        return true;
                    }, ARRAY_FILTER_USE_BOTH);
                    $g = array_filter(["a" => 5, "b" => 12, "c" => null], function(string $val): bool {
                        return true;
                    }, ARRAY_FILTER_USE_KEY);

                    $bar = "bar";

                    $foo = [
                        $bar => function (): string {
                            return "baz";
                        },
                    ];

                    $foo = array_filter(
                        $foo,
                        function (string $key): bool {
                            return $key === "bar";
                        },
                        ARRAY_FILTER_USE_KEY
                    );',
                'assertions' => [
                    '$f' => 'array<string, int|null>',
                    '$g' => 'array<string, int|null>',
                ],
            ],
            'arrayFilterIgnoreNullable' => [
                '<?php
                    class A {
                        /**
                         * @return array<int, self|null>
                         */
                        public function getRows() : array {
                            return [new self, null];
                        }

                        public function filter() : void {
                            $arr = array_filter(
                                static::getRows(),
                                function (self $row) : bool {
                                    return is_a($row, static::class);
                                }
                            );
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['PossiblyInvalidArgument'],
            ],
            'typedArrayWithDefault' => [
                '<?php
                    class A {}

                    /** @param array<A> $a */
                    function fooFoo(array $a = []): void {

                    }',
            ],
            'abs' => [
                '<?php
                    $a = abs(-5);
                    $b = abs(-7.5);
                    $c = $_GET["c"];
                    $c = is_numeric($c) ? abs($c) : null;',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'float',
                    '$c' => 'numeric|null',
                ],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
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
                    function fooFoo(string &$v): void {}
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
                    '$d' => 'array{0:string, 1:string, 2:string, 3:int, 4:int, 5:int}',
                ],
            ],
            'arrayReverse' => [
                '<?php
                    $d = array_reverse(["a", "b", 1]);',
                'assertions' => [
                    '$d' => 'array<int, string|int>',
                ],
            ],
            'arrayReversePreserveKey' => [
                '<?php
                    $d = array_reverse(["a", "b", 1], true);',
                'assertions' => [
                    '$d' => 'array<int, string|int>',
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
                'error_levels' => [
                    'MixedArrayAccess',
                    'MixedArgument',
                    'MissingClosureParamType',
                    'MissingClosureReturnType',
                ],
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
                    'RedundantConditionGivenDocblockType',
                ],
            ],
            'ignoreNullablePregReplace' => [
                '<?php
                    function foo(string $s): string {
                        $s = preg_replace("/hello/", "", $s);
                        if ($s === null) {
                            return "hello";
                        }
                        return $s;
                    }
                    function bar(string $s): string {
                        $s = preg_replace("/hello/", "", $s);
                        return $s;
                    }
                    function bat(string $s): ?string {
                        $s = preg_replace("/hello/", "", $s);
                        return $s;
                    }',
            ],
            'extractVarCheck' => [
                '<?php
                    function takesString(string $str): void {}

                    $foo = null;
                    $a = ["$foo" => "bar"];
                    extract($a);
                    takesString($foo);',
                'assertions' => [],
                'error_levels' => [
                    'MixedAssignment',
                    'MixedArrayAccess',
                    'MixedArgument',
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
                    '$a3' => 'array{hi:int, bye:int}',
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

                    function fooFoo(string &$v): void {}

                    fooFoo($a->foo);
                    fooFoo($b);',
            ],
            'arrayRand' => [
                '<?php
                    $vars = ["x" => "a", "y" => "b"];
                    $c = array_rand($vars);
                    $d = $vars[$c];
                    $more_vars = ["a", "b"];
                    $e = array_rand($more_vars);',

                'assertions' => [
                    '$vars' => 'array{x:string, y:string}',
                    '$c' => 'string',
                    '$d' => 'string',
                    '$more_vars' => 'array{0:string, 1:string}',
                    '$e' => 'int',
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
                    function expect_string(string $x): void {
                        echo $x;
                    }

                    function test(): void {
                        foreach (array_keys([]) as $key) {
                            expect_string($key);
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'compact' => [
                '<?php
                    function test(): array {
                        return compact(["val"]);
                    }',
            ],
            'objectLikeKeyChecksAgainstGeneric' => [
                '<?php
                    /**
                     * @param array<string, string> $b
                     */
                    function a($b): string
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
                    function a($b): string
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
            ],
            'getenv' => [
                '<?php
                    $a = getenv();
                    $b = getenv("some_key");',
                'assertions' => [
                    '$a' => 'array<mixed, string>',
                    '$b' => 'string|false',
                ],
            ],
            'arrayPopIgnoreNullable' => [
                '<?php
                    function expectsInt(int $a) : void {}

                    /**
                     * @param array<mixed, array{item:int}> $list
                     */
                    function test(array $list) : void
                    {
                        while (!empty($list)) {
                            $tmp = array_pop($list);
                            expectsInt($tmp["item"]);
                        }
                    }',
            ],
            'arrayFilterWithAssert' => [
                '<?php
                    $a = array_filter(
                        [1, "hello", 6, "goodbye"],
                        function ($s): bool {
                            return is_string($s);
                        }
                    );',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
                'error_levels' => [
                    'MissingClosureParamType',
                ],
            ],
            'arrayFilterUseKey' => [
                '<?php
                    $bar = "bar";

                    $foo = [
                        $bar => function (): string {
                            return "baz";
                        },
                    ];

                    $foo = array_filter(
                        $foo,
                        function (string $key): bool {
                            return $key === "bar";
                        },
                        ARRAY_FILTER_USE_KEY
                    );',
                'assertions' => [
                    '$foo' => 'array<string, Closure():string>',
                ],
            ],
            'ignoreFalsableCurrent' => [
                '<?php
                    /** @param string[] $arr */
                    function foo(array $arr): string {
                        return current($arr);
                    }
                    /** @param string[] $arr */
                    function bar(array $arr): string {
                        $a = current($arr);
                        if ($a === false) {
                            return "hello";
                        }
                        return $a;
                    }
                    /**
                     * @param string[] $arr
                     * @return string|false
                     */
                    function bat(array $arr) {
                        return current($arr);
                    }',
            ],
            'ignoreFalsableFileGetContents' => [
                '<?php
                    function foo(string $s): string {
                        return file_get_contents($s);
                    }
                    function bar(string $s): string {
                        $a = file_get_contents($s);
                        if ($a === false) {
                            return "hello";
                        }
                        return $a;
                    }
                    /**
                     * @return string|false
                     */
                    function bat(string $s) {
                        return file_get_contents($s);
                    }',
            ],
            'byRefString' => [
                '<?php
                    $arr = [];
                    function fooFoo(array &$v): void {}
                    $function = "fooFoo";
                    $function($arr);
                    if ($arr) {}',
            ],
            'arraySumEmpty' => [
                '<?php
                    $foo = array_sum([]) + 1;',
                'assertions' => [
                    '$foo' => 'float|int',
                ],
            ],
            'arrayMapObjectLikeAndCallable' => [
                '<?php
                    /**
                     * @psalm-return array{key1:int,key2:int}
                     */
                    function foo(): array {
                      $v = ["key1"=> 1, "key2"=> "2"];
                      $r = array_map("intval", $v);
                      return $r;
                    }',
            ],
            'arrayMapObjectLikeAndClosure' => [
                '<?php
                    /**
                     * @psalm-return array{key1:int,key2:int}
                     */
                    function foo(): array {
                      $v = ["key1"=> 1, "key2"=> "2"];
                      $r = array_map(function($i) : int { return intval($i);}, $v);
                      return $r;
                    }',
                'assertions' => [],
                'error_levels' => [
                    'MissingClosureParamType',
                    'MixedTypeCoercion',
                ],
            ],
            'arrayFilterGoodArgs' => [
                '<?php
                    function fooFoo(int $i) : bool {
                      return true;
                    }

                    class A {
                        public static function barBar(int $i) : bool {
                            return true;
                        }
                    }

                    array_filter([1, 2, 3], "fooFoo");
                    array_filter([1, 2, 3], "foofoo");
                    array_filter([1, 2, 3], "FOOFOO");
                    array_filter([1, 2, 3], "A::barBar");
                    array_filter([1, 2, 3], "A::BARBAR");
                    array_filter([1, 2, 3], "A::barbar");',
            ],
            'arrayFilterIgnoreMissingClass' => [
                '<?php
                    array_filter([1, 2, 3], "A::bar");',
                'assertions' => [],
                'error_levels' => ['UndefinedClass'],
            ],
            'arrayFilterIgnoreMissingMethod' => [
                '<?php
                    class A {
                        public static function bar(int $i) : bool {
                            return true;
                        }
                    }

                    array_filter([1, 2, 3], "A::foo");',
                'assertions' => [],
                'error_levels' => ['UndefinedMethod'],
            ],
            'validCallables' => [
                '<?php
                    class A {
                        public static function b() : void {}
                    }

                    function c() : void {}

                    ["a", "b"]();
                    "A::b"();
                    "c"();'
            ],
            'arrayMapParamDefault' => [
                '<?php
                    $arr = ["a", "b"];
                    array_map("mapdef", $arr, array_fill(0, count($arr), 1));
                    function mapdef(string $_a, int $_b = 0): string {
                        return "a";
                    }',
            ],
            'noInvalidOperandForCoreFunctions' => [
                '<?php
                    function foo(string $a, string $b) : int {
                        $aTime = strtotime($a);
                        $bTime = strtotime($b);

                        return $aTime - $bTime;
                    }',
            ],
            'strposIntSecondParam' => [
                '<?php
                    function hasZeroByteOffset(string $s) : bool {
                        return strpos($s, 0) !== false;
                    }'
            ],
            'functionCallInGlobalScope' => [
                '<?php
                    $a = function() use ($argv) : void {};',
            ],
            'implodeMultiDimensionalArray' => [
                '<?php
                    $urls = array_map("implode", [["a", "b"]]);',
            ],
            'varExport' => [
                '<?php
                    $a = var_export(["a"], true);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'key' => [
                '<?php
                    $a = ["one" => 1, "two" => 3];
                    $b = key($a);
                    $c = $a[$b];',
                'assertions' => [
                    '$b' => 'false|string',
                    '$c' => 'int',
                ],
            ],
            'explodeWithPossiblyFalse' => [
                '<?php
                    /** @return array<int, string> */
                    function exploder(string $s) : array {
                        return explode(" ", $s);
                    }',
            ],
            'allowPossiblyUndefinedClassInClassExists' => [
                '<?php
                    if (class_exists(Foo::class)) {}'
            ],
            'next' => [
                '<?php
                    $arr = ["one", "two", "three"];
                    $n = next($arr);',
                'assertions' => [
                    '$n' => 'false|string',
                ],
            ],
            'iteratorToArray' => [
                '<?php
                    /**
                     * @return Generator<stdClass>
                     */
                    function generator(): Generator {
                        yield new stdClass;
                    }

                    /**
                     * @return array<stdClass>
                     */
                    function foo(callable $filter): array {
                        return array_filter(iterator_to_array(generator()), $filter);
                    }'
            ],
            'arrayColumnInference' => [
                '<?php
                    function makeMixedArray(): array { return []; }
                    /** @return array<array<int,bool>> */
                    function makeGenericArray(): array { return []; }
                    /** @return array<array{0:string}> */
                    function makeShapeArray(): array { return []; }
                    /** @return array<array{0:string}|int> */
                    function makeUnionArray(): array { return []; }
                    $a = array_column([[1], [2], [3]], 0);
                    $b = array_column([["a" => 1], ["a" => 2], ["a" => 3]], "a");
                    $c = array_column([["k" => "a", "v" => 1], ["k" => "b", "v" => 2]], "v", "k");
                    $d = array_column([], 0);
                    $e = array_column(makeMixedArray(), 0);
                    $f = array_column(makeGenericArray(), 0);
                    $g = array_column(makeShapeArray(), 0);
                    $h = array_column(makeUnionArray(), 0);
                ',
                'assertions' => [
                    '$a' => 'array<mixed, int>',
                    '$b' => 'array<mixed, int>',
                    '$c' => 'array<string, int>',
                    '$d' => 'array<mixed, mixed>',
                    '$e' => 'array<mixed, mixed>',
                    '$f' => 'array<mixed, mixed>',
                    '$g' => 'array<mixed, string>',
                    '$h' => 'array<mixed, mixed>',
                ],
            ],
            'strstrWithPossiblyFalseFirstArg' => [
                '<?php
                    strtr(
                        file_get_contents("foobar.txt"),
                        ["foo" => "bar"]
                    );'
            ],
            'splatArrayIntersect' => [
                '<?php
                    $foo = [
                        [1, 2, 3],
                        [1, 2],
                    ];

                    $bar = array_intersect(... $foo);',
                'assertions' => [
                    '$bar' => 'array<int, int>',
                ],
            ],
            'arrayReduce' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    function multiply (int $carry, int $item) : int {
                        return $carry * $item;
                    }

                    $f2 = function (int $carry, int $item) : int {
                        return $carry * $item;
                    };

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, int $item) : int {
                            return $carry * $item;
                        },
                        1
                    );

                    $passed_closure_result = array_reduce(
                        $arr,
                        $f2,
                        1
                    );

                    $function_call_result = array_reduce(
                        $arr,
                        "multiply",
                        1
                    );',
                'assertions' => [
                    '$direct_closure_result' => 'int',
                    '$passed_closure_result' => 'int',
                    '$function_call_result' => 'int',
                ],
            ],
            'arrayReduceMixedReturn' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, int $item) {
                            return $_GET["boo"];
                        },
                        1
                    );',
                'assertions' => [],
                'error_levels' => ['MissingClosureReturnType', 'MixedAssignment'],
            ],
            'versionCompare' => [
                '<?php
                    function getString() : string {
                        return rand(0, 1) ? "===" : "==";
                    }

                    $a = version_compare("5.0.0", "7.0.0");
                    $b = version_compare("5.0.0", "7.0.0", "==");
                    $c = version_compare("5.0.0", "7.0.0", getString());
                ',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'bool',
                    '$c' => 'bool|null',
                ],
            ],
            'getTimeOfDay' => [
                '<?php
                    $a = gettimeofday(true) - gettimeofday(true);
                    $b = gettimeofday();
                    $c = gettimeofday(false);',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'array<string, int>',
                    '$c' => 'array<string, int>',
                ],
            ],
            'parseUrlArray' => [
                '<?php
                    function foo(string $s) : string {
                        return parse_url($s)["host"] ?? "";
                    }

                    function bar(string $s) : string {
                        $parsed = parse_url($s);

                        return $parsed["host"];
                    }

                    function baz(string $s) : string {
                        $parsed = parse_url($s);

                        return $parsed["host"];
                    }

                    function bag(string $s) : string {
                        $parsed = parse_url($s);

                        if (is_string($parsed["host"] ?? false)) {
                            return $parsed["host"];
                        }

                        return "";
                    }


                    function hereisanotherone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"]) && is_string($parsed["host"])) {
                            return $parsed["host"];
                        }

                        return "";
                    }

                    function hereisthelastone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"]) && is_string($parsed["host"])) {
                            return $parsed["host"];
                        }

                        return "";
                    }

                    function portisint(string $s) : int {
                        $parsed = parse_url($s);

                        if (isset($parsed["port"])) {
                            return $parsed["port"];
                        }

                        return 80;
                    }

                    function portismaybeint(string $s) : ? int {
                        $parsed = parse_url($s);

                        return $parsed["port"] ?? null;
                    }

                    $porta = parse_url("", PHP_URL_PORT);
                    $porte = parse_url("localhost:443", PHP_URL_PORT);',
                'assertions' => [
                    '$porta' => 'int|null',
                    '$porte' => 'int|null',
                ],
                'error_levels' => ['MixedReturnStatement', 'MixedInferredReturnType'],
            ],
            'parseUrlComponent' => [
                '<?php
                    function foo(string $s) : string {
                        return parse_url($s, PHP_URL_HOST) ?? "";
                    }

                    function bar(string $s) : string {
                        return parse_url($s, PHP_URL_HOST);
                    }

                    function bag(string $s) : string {
                        $host = parse_url($s, PHP_URL_HOST);

                        if (is_string($host)) {
                            return $host;
                        }

                        return "";
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
            'arrayFilterWithoutTypes' => [
                '<?php
                    $e = array_filter(
                        ["a" => 5, "b" => 12, "c" => null],
                        function(?int $i) {
                            return $_GET["a"];
                        }
                    );',
                'error_message' => 'MixedTypeCoercion',
                'error_levels' => ['MissingClosureParamType', 'MissingClosureReturnType'],
            ],
            'invalidScalarArgument' => [
                '<?php
                    function fooFoo(int $a): void {}
                    fooFoo("string");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'mixedArgument' => [
                '<?php
                    function fooFoo(int $a): void {}
                    /** @var mixed */
                    $a = "hello";
                    fooFoo($a);',
                'error_message' => 'MixedArgument',
                'error_levels' => ['MixedAssignment'],
            ],
            'nullArgument' => [
                '<?php
                    function fooFoo(int $a): void {}
                    fooFoo(null);',
                'error_message' => 'NullArgument',
            ],
            'tooFewArguments' => [
                '<?php
                    function fooFoo(int $a): void {}
                    fooFoo();',
                'error_message' => 'TooFewArguments',
            ],
            'tooManyArguments' => [
                '<?php
                    function fooFoo(int $a): void {}
                    fooFoo(5, "dfd");',
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Too many arguments for method fooFoo '
                    . '- expecting 1 but saw 2',
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

                    function fooFoo(B $b): void {}
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
                'error_levels' => ['MissingParamType'],
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
                    function fooFoo(string &$v): void {}
                    fooFoo("a");',
                'error_message' => 'InvalidPassByReference',
            ],
            'badArrayByRef' => [
                '<?php
                    function fooFoo(array &$a): void {}
                    fooFoo([1, 2, 3]);',
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
                    'RedundantConditionGivenDocblockType',
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
                    function a($b): int
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
                    function a($b): int
                    {
                      return $b["a"];
                    }

                    a(["a" => "hello"]);',
                'error_message' => 'InvalidArgument',
            ],
            'possiblyNullFunctionCall' => [
                '<?php
                    $a = rand(0, 1) ? function(): void {} : null;
                    $a();',
                'error_message' => 'PossiblyNullFunctionCall',
            ],
            'possiblyInvalidFunctionCall' => [
                '<?php
                    $a = rand(0, 1) ? function(): void {} : 23515;
                    $a();',
                'error_message' => 'PossiblyInvalidFunctionCall',
            ],
            'arrayFilterBadArgs' => [
                '<?php
                    function foo(int $i) : bool {
                      return true;
                    }

                    array_filter(["hello"], "foo");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'arrayFilterTooFewArgs' => [
                '<?php
                    function foo(int $i, string $s) : bool {
                      return true;
                    }

                    array_filter([1, 2, 3], "foo");',
                'error_message' => 'TooFewArguments',
            ],
            'arrayMapBadArgs' => [
                '<?php
                    function foo(int $i) : bool {
                      return true;
                    }

                    array_map("foo", ["hello"]);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'arrayMapTooFewArgs' => [
                '<?php
                    function foo(int $i, string $s) : bool {
                      return true;
                    }

                    array_map("foo", [1, 2, 3]);',
                'error_message' => 'TooFewArguments',
            ],
            'arrayMapTooManyArgs' => [
                '<?php
                    function foo() : bool {
                      return true;
                    }

                    array_map("foo", [1, 2, 3]);',
                'error_message' => 'TooManyArguments',
            ],
            'varExportAssignmentToVoid' => [
                '<?php
                    $a = var_export(["a"]);',
                'error_message' => 'AssignmentToVoid',
            ],
            'explodeWithEmptyString' => [
                '<?php
                    function exploder(string $s) : array {
                        return explode("", $s);
                    }',
                'error_message' => 'FalsableReturnStatement',
            ],
            'complainAboutArrayToIterable' => [
                '<?php
                    class A {}
                    class B {}
                    /**
                     * @param iterable<mixed,A> $p
                     */
                    function takesIterableOfA(iterable $p): void {}

                    takesIterableOfA([new B]); // should complain',
                'error_message' => 'InvalidArgument',
            ],
            'complainAboutArrayToIterableSingleParam' => [
                '<?php
                    class A {}
                    class B {}
                    /**
                     * @param iterable<A> $p
                     */
                    function takesIterableOfA(iterable $p): void {}

                    takesIterableOfA([new B]); // should complain',
                'error_message' => 'InvalidArgument',
            ],
            'putInvalidTypeMessagesFirst' => [
                '<?php
                    $q = rand(0,1) ? new stdClass : false;
                    strlen($q);',
                'error_message' => 'InvalidArgument',
            ],
            'arrayReduceInvalidClosureTooFewArgs' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry) : int {
                            return 5;
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['MixedTypeCoercion'],
            ],
            'arrayReduceInvalidItemType' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, stdClass $item) {
                            return $_GET["boo"];
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['MissingClosureReturnType'],
            ],
            'arrayReduceInvalidCarryType' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (stdClass $carry, int $item) {
                            return $_GET["boo"];
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
                'error_levels' => ['MissingClosureReturnType'],
            ],
            'arrayReduceInvalidCarryOutputType' => [
                '<?php
                    $arr = [2, 3, 4, 5];

                    $direct_closure_result = array_reduce(
                        $arr,
                        function (int $carry, int $item) : stdClass {
                            return new stdClass;
                        },
                        1
                    );',
                'error_message' => 'InvalidArgument',
            ],
        ];
    }
}
