<?php
namespace Psalm\Tests;

use const DIRECTORY_SEPARATOR;

class FunctionCallTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'preg_grep' => [
                '<?php
                  /**
                   * @param array<int,string> $strings
                   * @return array<int,string>
                   */
                  function filter(array $strings): array {
                     return preg_grep("/search/", $strings, PREG_GREP_INVERT);
                  }
                '
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
                    '$c' => 'float|int|null',
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
            'byRefNewString' => [
                '<?php
                    function fooFoo(?string &$v): void {}
                    fooFoo($a);',
            ],
            'byRefVariableFunctionExistingArray' => [
                '<?php
                    $arr = [];
                    function fooFoo(array &$v): void {}
                    $function = "fooFoo";
                    $function($arr);
                    if ($arr) {}',
            ],
            'byRefProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();

                    function fooFoo(string &$v): void {}

                    fooFoo($a->foo);',
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
            'noRedundantConditionAfterArrayObjectCountCheck' => [
                '<?php
                    /** @var ArrayObject<int, int> */
                    $a = [];
                    $b = 5;
                    if (count($a)) {}',
            ],
            'noRedundantConditionAfterMixedOrEmptyArrayCountCheck' => [
                '<?php
                    function foo(string $s) : void {
                        $a = $_GET["s"] ?: [];
                        if (count($a)) {}
                        if (!count($a)) {}
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'objectLikeArrayAssignmentInConditional' => [
                '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a["a"] = 5;
                    }

                    if (count($a)) {}
                    if (!count($a)) {}',
            ],
            'noRedundantConditionAfterCheckingExplodeLength' => [
                '<?php
                    /** @var string */
                    $s = "hello";
                    $segments = explode(".", $s);
                    if (count($segments) === 1) {}',
            ],
            'arrayPopNonEmptyAfterThreeAssertions' => [
                '<?php
                    class A {}
                    class B extends A {
                        /** @var array<int, string> */
                        public $arr = [];
                    }

                    /** @var array<A> */
                    $replacement_stmts = [];

                    if (!$replacement_stmts
                        || !$replacement_stmts[0] instanceof B
                        || count($replacement_stmts[0]->arr) > 1
                    ) {
                        return null;
                    }

                    $b = $replacement_stmts[0]->arr;',
                'assertions' => [
                    '$b' => 'array<int, string>',
                ],
            ],
            'countMoreThan0CanBeInverted' => [
                '<?php
                    $a = [];

                    if (rand(0, 1)) {
                        $a[] = "hello";
                    }

                    if (count($a) > 0) {
                        exit;
                    }',
                    'assertions' => [
                        '$a' => 'array<empty, empty>',
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
            'compact' => [
                '<?php
                    /**
                     * @return array<string, mixed>
                     */
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
                    '$a' => 'array<string, string>',
                    '$b' => 'false|string',
                ],
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
                     * @return false|string
                     */
                    function bat(string $s) {
                        return file_get_contents($s);
                    }',
            ],
            'validCallables' => [
                '<?php
                    class A {
                        public static function b() : void {}
                    }

                    function c() : void {}

                    ["a", "b"]();
                    "A::b"();
                    "c"();',
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
                    }',
            ],
            'functionCallInGlobalScope' => [
                '<?php
                    $a = function() use ($argv) : void {};',
            ],
            'varExport' => [
                '<?php
                    $a = var_export(["a"], true);',
                'assertions' => [
                    '$a' => 'string',
                ],
            ],
            'varExportConstFetch' => [
                '<?php
                    class Foo {
                        const BOOL_VAR_EXPORT_RETURN = true;

                        /**
                         * @param mixed $mixed
                         */
                        public static function Baz($mixed) : string {
                            return var_export($mixed, self::BOOL_VAR_EXPORT_RETURN);
                        }
                    }',
            ],
            'explode' => [
                '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithPositiveLimit' => [
                '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, 5);',
                'assertions' => [
                    '$elements' => 'non-empty-list<string>',
                ],
            ],
            'explodeWithNegativeLimit' => [
                '<?php
                    /** @var string $string */
                    $elements = explode(" ", $string, -5);',
                'assertions' => [
                    '$elements' => 'list<string>',
                ],
            ],
            'explodeWithDynamicLimit' => [
                '<?php
                    /**
                     * @var string $string
                     * @var int $limit
                     */
                    $elements = explode(" ", $string, $limit);',
                'assertions' => [
                    '$elements' => 'list<string>',
                ],
            ],
            'explodeWithDynamicDelimiter' => [
                '<?php
                    /**
                     * @var string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string);',
                'assertions' => [
                    '$elements' => 'false|non-empty-list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndPositiveLimit' => [
                '<?php
                    /**
                     * @var string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, 5);',
                'assertions' => [
                    '$elements' => 'false|non-empty-list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndNegativeLimit' => [
                '<?php
                    /**
                     * @var string $delim
                     * @var string $string
                     */
                    $elements = explode($delim, $string, -5);',
                'assertions' => [
                    '$elements' => 'false|list<string>',
                ],
            ],
            'explodeWithDynamicDelimiterAndLimit' => [
                '<?php
                    /**
                     * @var string $delim
                     * @var string $string
                     * @var int $limit
                     */
                    $elements = explode($delim, $string, $limit);',
                'assertions' => [
                    '$elements' => 'false|list<string>',
                ],
            ],
            'explodeWithPossiblyFalse' => [
                '<?php
                    /** @return non-empty-list<string> */
                    function exploder(string $d, string $s) : array {
                        return explode($d, $s);
                    }',
            ],
            'allowPossiblyUndefinedClassInClassExists' => [
                '<?php
                    if (class_exists(Foo::class)) {}',
            ],
            'allowConstructorAfterClassExists' => [
                '<?php
                    function foo(string $s) : void {
                        if (class_exists($s)) {
                            new $s();
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedMethodCall'],
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

                    $a = iterator_to_array(generator());',
                'assertions' => [
                    '$a' => 'array<array-key, stdClass>',
                ],
            ],
            'iteratorToArrayWithGetIterator' => [
                '<?php
                    class C implements IteratorAggregate {
                        /**
                         * @return Traversable<int,string>
                         */
                        public function getIterator() {
                            yield 1 => "1";
                        }
                    }
                    $a = iterator_to_array(new C);',
                'assertions' => [
                    '$a' => 'array<int, string>',
                ],
            ],
            'iteratorToArrayWithGetIteratorReturningList' => [
                '<?php
                    class C implements IteratorAggregate {
                        /**
                         * @return Traversable<int,string>
                         */
                        public function getIterator() {
                            yield 1 => "1";
                        }
                    }
                    $a = iterator_to_array(new C, false);',
                'assertions' => [
                    '$a' => 'list<string>',
                ],
            ],
            'strtrWithPossiblyFalseFirstArg' => [
                '<?php
                    /**
                     * @param false|string $str
                     * @param array<string, string> $replace_pairs
                     * @return string
                     */
                    function strtr_wrapper($str, array $replace_pairs) {
                        /** @psalm-suppress PossiblyFalseArgument */
                        return strtr($str, $replace_pairs);
                    }',
            ],
            'versionCompare' => [
                '<?php
                    /** @return "="|"==" */
                    function getString() : string {
                        return rand(0, 1) ? "==" : "=";
                    }

                    $a = version_compare("5.0.0", "7.0.0");
                    $b = version_compare("5.0.0", "7.0.0", "==");
                    $c = version_compare("5.0.0", "7.0.0", getString());
                ',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'bool',
                    '$c' => 'bool',
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

                    function hereisanotherone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"])) {
                            return $parsed["host"];
                        }

                        return "";
                    }

                    function hereisthelastone(string $s) : string {
                        $parsed = parse_url($s);

                        if (isset($parsed["host"])) {
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
                    '$porta' => 'false|int|null',
                    '$porte' => 'false|int|null',
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
            'parseUrlTypes' => [
                '<?php
                    $url = "foo";
                    $components = parse_url($url);
                    $scheme = parse_url($url, PHP_URL_SCHEME);
                    $host = parse_url($url, PHP_URL_HOST);
                    $port = parse_url($url, PHP_URL_PORT);
                    $user = parse_url($url, PHP_URL_USER);
                    $pass = parse_url($url, PHP_URL_PASS);
                    $path = parse_url($url, PHP_URL_PATH);
                    $query = parse_url($url, PHP_URL_QUERY);
                    $fragment = parse_url($url, PHP_URL_FRAGMENT);',
                'assertions' => [
                    '$components' => 'array{fragment?: string, host?: string, pass?: string, path?: string, port?: int, query?: string, scheme?: string, user?: string}|false',
                    '$scheme' => 'false|null|string',
                    '$host' => 'false|null|string',
                    '$port' => 'false|int|null',
                    '$user' => 'false|null|string',
                    '$pass' => 'false|null|string',
                    '$path' => 'false|null|string',
                    '$query' => 'false|null|string',
                    '$fragment' => 'false|null|string',
                ],
            ],
            'triggerUserError' => [
                '<?php
                    function mightLeave() : string {
                        if (rand(0, 1)) {
                            trigger_error("bad", E_USER_ERROR);
                        } else {
                            return "here";
                        }
                    }',
            ],
            'getParentClass' => [
                '<?php
                    class A {}
                    class B extends A {}

                    $b = get_parent_class(new A());
                    if ($b === false) {}
                    $c = new $b();',
                'assertions' => [],
                'error_levels' => ['MixedMethodCall'],
            ],
            'suppressError' => [
                '<?php
                    $a = @file_get_contents("foo");',
                'assertions' => [
                    '$a' => 'false|string',
                ],
            ],
            'echo' => [
                '<?php
                echo false;',
            ],
            'printrOutput' => [
                '<?php
                    function foo(string $s) : void {
                        echo $s;
                    }

                    foo(print_r(1, true));',
            ],
            'microtime' => [
                '<?php
                    $a = microtime(true);
                    $b = microtime();
                    /** @psalm-suppress InvalidScalarArgument */
                    $c = microtime(1);
                    $d = microtime(false);',
                'assertions' => [
                    '$a' => 'float',
                    '$b' => 'string',
                    '$c' => 'float|string',
                    '$d' => 'string',
                ],
            ],
            'filterVar' => [
                '<?php
                    function filterInt(string $s) : int {
                        $filtered = filter_var($s, FILTER_VALIDATE_INT);
                        if ($filtered === false) {
                            return 0;
                        }
                        return $filtered;
                    }
                    function filterNullableInt(string $s) : ?int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => null]]);
                    }
                    function filterIntWithDefault(string $s) : int {
                        return filter_var($s, FILTER_VALIDATE_INT, ["options" => ["default" => 5]]);
                    }
                    function filterBool(string $s) : bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN);
                    }
                    function filterNullableBool(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                    }
                    function filterNullableBoolWithFlagsArray(string $s) : ?bool {
                        return filter_var($s, FILTER_VALIDATE_BOOLEAN, ["flags" => FILTER_NULL_ON_FAILURE]);
                    }
                    function filterFloat(string $s) : float {
                        $filtered = filter_var($s, FILTER_VALIDATE_FLOAT);
                        if ($filtered === false) {
                            return 0.0;
                        }
                        return $filtered;
                    }
                    function filterFloatWithDefault(string $s) : float {
                        return filter_var($s, FILTER_VALIDATE_FLOAT, ["options" => ["default" => 5.0]]);
                    }',
            ],
            'callVariableVar' => [
                '<?php
                    class Foo
                    {
                        public static function someInt(): int
                        {
                            return 1;
                        }
                    }

                    /**
                     * @return int
                     */
                    function makeInt()
                    {
                        $fooClass = Foo::class;
                        return $fooClass::someInt();
                    }',
            ],
            'expectsIterable' => [
                '<?php
                    function foo(iterable $i) : void {}
                    function bar(array $a) : void {
                        foo($a);
                    }',
            ],
            'getTypeHasValues' => [
                '<?php
                    /**
                     * @param mixed $maybe
                     */
                    function matchesTypes($maybe) : void {
                        $t = gettype($maybe);
                        if ($t === "object") {}
                    }',
            ],
            'functionResolutionInNamespace' => [
                '<?php
                    namespace Foo;
                    function sort(int $_) : void {}
                    sort(5);',
            ],
            'rangeWithIntStep' => [
                '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, 1) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithNoStep' => [
                '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithNoStepAndString' => [
                '<?php

                    function foo(string $bar) : void {}

                    foreach (range("a", "z") as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithFloatStep' => [
                '<?php

                    function foo(float $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, .3) as $x) {
                        foo($x);
                    }',
            ],
            'rangeWithFloatStart' => [
                '<?php

                    function foo(float $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1.5, 10) as $x) {
                        foo($x);
                    }',
            ],
            'duplicateNamespacedFunction' => [
                '<?php
                    namespace Bar;

                    function sort() : void {}',
            ],
            'arrayMapAfterFunctionMissingFile' => [
                '<?php
                    require_once(FOO);
                    $urls = array_map("strval", [1, 2, 3]);',
                [],
                'error_levels' => ['UndefinedConstant', 'UnresolvableInclude'],
            ],
            'noNamespaceClash' => [
                '<?php
                    namespace FunctionNamespace {
                        function foo() : void {}
                    }

                    namespace ClassNamespace {
                        class Foo {}
                    }

                    namespace {
                        use ClassNamespace\Foo;
                        use function FunctionNamespace\foo;

                        new Foo();

                        foo();
                    }',
            ],
            'hashInit70' => [
                '<?php
                    $h = hash_init("sha256");',
                [
                    '$h' => 'resource',
                ],
                [],
                '7.1',
            ],
            'hashInit71' => [
                '<?php
                    $h = hash_init("sha256");',
                [
                    '$h' => 'resource',
                ],
                [],
                '7.1',
            ],
            'hashInit72' => [
                '<?php
                    $h = hash_init("sha256");',
                [
                    '$h' => 'HashContext|false',
                ],
                [],
                '7.2',
            ],
            'hashInit73' => [
                '<?php
                    $h = hash_init("sha256");',
                [
                    '$h' => 'HashContext|false',
                ],
                [],
                '7.3',
            ],
            'nullableByRef' => [
                '<?php
                    function foo(?string &$s) : void {}

                    function bar() : void {
                        foo($bar);
                    }',
            ],
            'getClassNewInstance' => [
                '<?php
                    interface I {}
                    class C implements I {}

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class(new C);',
            ],
            'getClassVariable' => [
                '<?php
                    interface I {}
                    class C implements I {}
                    $c_instance = new C;

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class($c_instance);',
            ],
            'getClassAnonymousNewInstance' => [
                '<?php
                    interface I {}

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class(new class implements I{});',
            ],
            'getClassAnonymousVariable' => [
                '<?php
                    interface I {}
                    $anon_instance = new class implements I {};

                    class Props {
                        /** @var class-string<I>[] */
                        public $arr = [];
                    }

                    (new Props)->arr[] = get_class($anon_instance);',
            ],
            'mktime' => [
                '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    $a = mktime("foo");
                    /** @psalm-suppress MixedArgument */
                    $b = mktime($_GET["foo"]);
                    $c = mktime(1, 2, 3);',
                'assertions' => [
                    '$a' => 'false|int',
                    '$b' => 'false|int',
                    '$c' => 'int',
                ],
            ],
            'PHP73-hrtime' => [
                '<?php
                    $a = hrtime(true);
                    $b = hrtime();
                    /** @psalm-suppress InvalidScalarArgument */
                    $c = hrtime(1);
                    $d = hrtime(false);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'array{0: int, 1: int}',
                    '$c' => 'array{0: int, 1: int}|int',
                    '$d' => 'array{0: int, 1: int}',
                ],
            ],
            'PHP73-hrtimeCanBeFloat' => [
                '<?php
                    $a = hrtime(true);

                    if (is_int($a)) {}
                    if (is_float($a)) {}',
            ],
            'min' => [
                '<?php
                    $a = min(0, 1);
                    $b = min([0, 1]);
                    $c = min("a", "b");
                    $d = min(1, 2, 3, 4);
                    $e = min(1, 2, 3, 4, 5);
                    $f = min(...[1, 2, 3]);',
                'assertions' => [
                    '$a' => 'int',
                    '$b' => 'int',
                    '$c' => 'string',
                    '$d' => 'int',
                    '$e' => 'int',
                    '$f' => 'int',
                ],
            ],
            'minUnpackedArg' => [
                '<?php
                    $f = min(...[1, 2, 3]);',
                'assertions' => [
                    '$f' => 'int',
                ],
            ],
            'sscanf' => [
                '<?php
                    sscanf("10:05:03", "%d:%d:%d", $hours, $minutes, $seconds);',
                'assertions' => [
                    '$hours' => 'float|int|string',
                    '$minutes' => 'float|int|string',
                    '$seconds' => 'float|int|string',
                ],
            ],
            'noImplicitAssignmentToStringFromMixedWithDocblockTypes' => [
                '<?php
                    /** @param string $s */
                    function takesString($s) : void {}
                    function takesInt(int $i) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesString($s);
                        takesInt($s);
                    }',
            ],
            'ignoreNullableIssuesAfterMixedCoercion' => [
                '<?php
                    function takesNullableString(?string $s) : void {}
                    function takesString(string $s) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesNullableString($s);
                        takesString($s);
                    }',
            ],
            'countableSimpleXmlElement' => [
                '<?php
                    $xml = new SimpleXMLElement("<?xml version=\"1.0\"?><a><b></b><b></b></a>");
                    echo count($xml);',
            ],
            'countableCallableArray' => [
                '<?php
                    /** @param callable|false $x */
                    function example($x) : void {
                        if (is_array($x)) {
                            echo "Count is: " . count($x);
                        }
                    }'
            ],
            'refineWithTraitExists' => [
                '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
            ],
            'refineWithClassExistsOrTraitExists' => [
                '<?php
                    function foo(string $s) : void {
                        if (trait_exists($s) || class_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function bar(string $s) : void {
                        if (class_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }

                    function baz(string $s) : void {
                        if (class_exists($s) || interface_exists($s) || trait_exists($s)) {
                            new ReflectionClass($s);
                        }
                    }',
            ],
            'minSingleArg' => [
                '<?php
                    /** @psalm-suppress TooFewArguments */
                    min(0);',
            ],
            'PHP73-allowIsCountableToInformType' => [
                '<?php
                    function getObject() : iterable{
                       return [];
                    }

                    $iterableObject = getObject();

                    if (is_countable($iterableObject)) {
                       if (count($iterableObject) === 0) {}
                    }',
            ],
            'versionCompareAsCallable' => [
                '<?php
                    $a = ["1.0", "2.0"];
                    usort($a, "version_compare");',
            ],
            'coerceToObjectAfterBeingCalled' => [
                '<?php
                    class Foo {
                        public function bar() : void {}
                    }

                    function takesFoo(Foo $foo) : void {}

                    /** @param mixed $f */
                    function takesMixed($f) : void {
                        if (rand(0, 1)) {
                            $f = new Foo();
                        }
                        /** @psalm-suppress MixedArgument */
                        takesFoo($f);
                        $f->bar();
                    }',
            ],
            'functionExists' => [
                '<?php
                    if (!function_exists("in_array")) {
                        function in_array($a, $b) {
                            return true;
                        }
                    }',
            ],
            'pregMatch' => [
                '<?php
                    function takesInt(int $i) : void {}

                    takesInt(preg_match("{foo}", "foo"));',
            ],
            'pregMatchWithMatches' => [
                '<?php
                    /** @param string[] $matches */
                    function takesMatches(array $matches) : void {}

                    preg_match("{foo}", "foo", $matches);

                    takesMatches($matches);',
            ],
            'pregMatchWithOffset' => [
                '<?php
                    /** @param string[] $matches */
                    function takesMatches(array $matches) : void {}

                    preg_match("{foo}", "foo", $matches, 0, 10);

                    takesMatches($matches);',
            ],
            'pregMatchWithFlags' => [
                '<?php
                    function takesInt(int $i) : void {}

                    if (preg_match("{foo}", "this is foo", $matches, PREG_OFFSET_CAPTURE)) {
                        /**
                         * @psalm-suppress MixedArrayAccess
                         * @psalm-suppress MixedArgument
                         */
                        takesInt($matches[0][1]);
                    }',
            ],
            'pregReplaceCallback' => [
                '<?php
                    function foo(string $s) : string {
                        return preg_replace_callback(
                            \'/<files (psalm-version="[^"]+") (?:php-version="(.+)">\n)/\',
                            /** @param array<int, string> $matches */
                            function (array $matches) : string {
                                return $matches[1];
                            },
                            $s
                        );
                    }',
            ],
            'pregReplaceCallbackWithArray' => [
                '<?php
                    /**
                     * @param string[] $ids
                     * @psalm-suppress MissingClosureReturnType
                     * @psalm-suppress MixedArgumentTypeCoercion
                     */
                    function(array $ids): array {
                        return \preg_replace_callback(
                            "",
                            fn (array $matches) => $matches[4],
                            $ids
                        );
                    };',
                    'assertions' => [],
                    'error_levels' => [],
                    '7.4'
            ],
            'compactDefinedVariable' => [
                '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function foo(int $a, string $b, bool $c) : array {
                        return compact("a", "b", "c");
                    }',
            ],
            'PHP73-setCookiePhp73' => [
                '<?php
                    setcookie(
                        "name",
                        "value",
                        [
                            "path"     => "/",
                            "expires"  => 0,
                            "httponly" => true,
                            "secure"   => true,
                            "samesite" => "Lax"
                        ]
                    );',
            ],
            'printrBadArg' => [
                '<?php
                    /** @psalm-suppress InvalidScalarArgument */
                    $a = print_r([], 1);
                    echo $a;',
            ],
            'dontCoerceCallMapArgs' => [
                '<?php
                    function getStr() : ?string {
                        return rand(0,1) ? "test" : null;
                    }

                    function test() : void {
                        $g = getStr();
                        /** @psalm-suppress PossiblyNullArgument */
                        $x = strtoupper($g);
                        $c = "prefix " . (strtoupper($g ?? "") === "x" ? "xa" : "ya");
                        echo "$x, $c\n";
                    }'
            ],
            'mysqliRealConnectFunctionAllowsNullParameters' => [
                '<?php
                    $mysqli = mysqli_init();
                    mysqli_real_connect($mysqli, null, \'test\', null);',
            ],
            'callUserFunc' => [
                '<?php
                    $func = function(int $arg1, int $arg2) : int {
                        return $arg1 * $arg2;
                    };

                    $a = call_user_func($func, 2, 4);',
                [
                    '$a' => 'int',
                ]
            ],
            'callUserFuncArray' => [
                '<?php
                    $func = function(int $arg1, int $arg2) : int {
                        return $arg1 * $arg2;
                    };

                    $a = call_user_func_array($func, [2, 4]);',
                [
                    '$a' => 'int',
                ]
            ],
            'dateTest' => [
                '<?php
                    $y = date("Y");
                    $m = date("m");
                    $F = date("F");
                    $y2 = date("Y", 10000);
                    $F2 = date("F", 10000);
                    /** @psalm-suppress MixedArgument */
                    $F3 = date("F", $_GET["F3"]);',
                [
                    '$y' => 'numeric-string',
                    '$m' => 'numeric-string',
                    '$F' => 'string',
                    '$y2' => 'numeric-string',
                    '$F2' => 'string',
                    '$F3' => 'false|string',
                ]
            ],
            'sscanfReturnTypeWithTwoParameters' => [
                '<?php
                    $data = sscanf("42 psalm road", "%s %s");',
                [
                    '$data' => 'list<float|int|string>',
                ]
            ],
            'sscanfReturnTypeWithMoreThanTwoParameters' => [
                '<?php
                    $n = sscanf("42 psalm road", "%s %s", $p1, $p2);',
                [
                    '$n' => 'int',
                ]
            ],
            'writeArgsAllowed' => [
                '<?php
                    /** @return false|int */
                    function safeMatch(string $pattern, string $subject, ?array $matches = null, int $flags = 0) {
                        return \preg_match($pattern, $subject, $matches, $flags);
                    }

                    safeMatch("/a/", "b");'
            ],
            'fgetcsv' => [
                '<?php
                    $headers = fgetcsv(fopen("test.txt", "r"));
                    if (empty($headers)) {
                        throw new Exception("invalid headers");
                    }
                    print_r(array_map("strval", $headers));'
            ],
            'allowListEqualToRange' => [
                '<?php
                    /** @param array<int, int> $two */
                    function collectCommit(array $one, array $two) : void {
                        if ($one && array_values($one) === array_values($two)) {}
                    }'
            ],
            'pregMatchAll' => [
                '<?php
                    /**
                     * @return array<list<string>>
                     */
                    function extractUsernames(string $input): array {
                        preg_match_all(\'/([a-zA-Z])*/\', $input, $matches);

                        return $matches;
                    }'
            ],
            'pregMatchAllOffsetCapture' => [
                '<?php
                    function foo(string $input): array {
                        preg_match_all(\'/([a-zA-Z])*/\', $input, $matches, PREG_OFFSET_CAPTURE);

                        return $matches[0];
                    }'
            ],
            'strposAllowDictionary' => [
                '<?php
                    function sayHello(string $format): void {
                        if (strpos("abcdefghijklmno", $format)) {}
                    }',
            ],
            'pregSplit' => [
                '<?php
                    /** @return non-empty-list */
                    function foo(string $s) {
                        return preg_split("/ /", $s);
                    }'
            ],
            'mbConvertEncodingWithArray' => [
                '<?php
                    /**
                     * @param array<int, string> $str
                     * @return array<int, string>
                     */
                    function test2(array $str): array {
                        return mb_convert_encoding($str, "UTF-8", "UTF-8");
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,2?:string[],3?:bool,4?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'invalidScalarArgument' => [
                '<?php
                    function fooFoo(int $a): void {}
                    fooFoo("string");',
                'error_message' => 'InvalidScalarArgument',
            ],
            'invalidArgumentWithDeclareStrictTypes' => [
                '<?php declare(strict_types=1);
                    function fooFoo(int $a): void {}
                    fooFoo("string");',
                'error_message' => 'InvalidArgument',
            ],
            'builtinFunctioninvalidArgumentWithWeakTypes' => [
                '<?php
                    $s = substr(5, 4);',
                'error_message' => 'InvalidScalarArgument',
            ],
            'builtinFunctioninvalidArgumentWithDeclareStrictTypes' => [
                '<?php declare(strict_types=1);
                    $s = substr(5, 4);',
                'error_message' => 'InvalidArgument',
            ],
            'builtinFunctioninvalidArgumentWithDeclareStrictTypesInClass' => [
                '<?php declare(strict_types=1);
                    class A {
                        public function foo() : void {
                            $s = substr(5, 4);
                        }
                    }',
                'error_message' => 'InvalidArgument',
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
                'error_message' => 'TooManyArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - Too many arguments for fooFoo '
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
                'error_message' => 'ArgumentTypeCoercion',
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
                'error_message' => 'ArgumentTypeCoercion',
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
            'getTypeInvalidValue' => [
                '<?php
                    /**
                     * @param mixed $maybe
                     */
                    function matchesTypes($maybe) : void {
                        $t = gettype($maybe);
                        if ($t === "bool") {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'rangeWithFloatStep' => [
                '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1, 10, .3) as $x) {
                        foo($x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'rangeWithFloatStart' => [
                '<?php

                    function foo(int $bar) : string {
                        return (string) $bar;
                    }

                    foreach (range(1.4, 10) as $x) {
                        foo($x);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'duplicateFunction' => [
                '<?php
                    function f() : void {}
                    function f() : void {}',
                'error_message' => 'DuplicateFunction',
            ],
            'duplicateCoreFunction' => [
                '<?php
                    function sort() : void {}',
                'error_message' => 'DuplicateFunction',
            ],
            'functionCallOnMixed' => [
                '<?php
                    /**
                     * @var mixed $s
                     * @psalm-suppress MixedAssignment
                     */
                    $s = 1;
                    $s();',
                'error_message' => 'MixedFunctionCall',
            ],
            'iterableOfObjectCannotAcceptIterableOfInt' => [
                '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return iterable<int,int> */
                    function iterable() { yield 1; }

                    accepts(iterable());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptTraversableOfInt' => [
                '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return Traversable<int,int> */
                    function traversable() { yield 1; }

                    accepts(traversable());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptGeneratorOfInt' => [
                '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return Generator<int,int,mixed,void> */
                    function generator() { yield 1; }

                    accepts(generator());',
                'error_message' => 'InvalidArgument',
            ],
            'iterableOfObjectCannotAcceptArrayOfInt' => [
                '<?php
                    /** @param iterable<string,object> $_p */
                    function accepts(iterable $_p): void {}

                    /** @return array<int,int> */
                    function arr() { return [1]; }

                    accepts(arr());',
                'error_message' => 'InvalidArgument',
            ],
            'nonNullableByRef' => [
                '<?php
                    function foo(string &$s) : void {}

                    function bar() : void {
                        foo($bar);
                    }',
                'error_message' => 'NullReference',
            ],
            'intCastByRef' => [
                '<?php
                    function foo(int &$i) : void {}

                    $a = rand(0, 1) ? null : 5;
                    /** @psalm-suppress MixedArgument */
                    foo((int) $a);',
                'error_message' => 'InvalidPassByReference',
            ],
            'implicitAssignmentToStringFromMixed' => [
                '<?php
                    /** @param "a"|"b" $s */
                    function takesString(string $s) : void {}
                    function takesInt(int $i) : void {}

                    /**
                     * @param mixed $s
                     * @psalm-suppress MixedArgument
                     */
                    function bar($s) : void {
                        takesString($s);
                        takesInt($s);
                    }',
                'error_message' => 'InvalidScalarArgument',
            ],
            'tooFewArgsAccurateCount' => [
                '<?php
                    preg_match(\'/adsf/\');',
                'error_message' => 'TooFewArguments - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21 - Too few arguments for preg_match - expecting 2 but saw 1',
            ],
            'compactUndefinedVariable' => [
                '<?php
                    /**
                     * @return array<string, mixed>
                     */
                    function foo() : array {
                        return compact("a", "b", "c");
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'countCallableArrayShouldBeTwo' => [
                '<?php
                    /** @param callable|false $x */
                    function example($x) : void {
                        if (is_array($x)) {
                            $c = count($x);
                            if ($c !== 2) {}
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'coerceCallMapArgsInStrictMode' => [
                '<?php
                    declare(strict_types=1);

                    function getStr() : ?string {
                        return rand(0,1) ? "test" : null;
                    }

                    function test() : void {
                        $g = getStr();
                        /** @psalm-suppress PossiblyNullArgument */
                        $x = strtoupper($g);
                        $c = "prefix " . (strtoupper($g ?? "") === "x" ? "xa" : "ya");
                        echo "$x, $c\n";
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'noCrashOnEmptyArrayPush' => [
                '<?php
                    array_push();',
                'error_message' => 'TooFewArguments',
            ],
            'printOnlyString' => [
                '<?php
                    print [];',
                'error_message' => 'InvalidArgument',
            ],
            'printReturns1' => [
                '<?php
                    (print "test") === 2;',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'sodiumMemzeroNullifyString' => [
                '<?php
                    function returnsStr(): string {
                        $str = "x";
                        sodium_memzero($str);
                        return $str;
                    }',
                'error_message' => 'NullableReturnStatement'
            ],
            'noCrashWithPattern' => [
                '<?php
                    echo !\is_callable($loop_callback)
                        || (\is_array($loop_callback)
                            && !\method_exists(...$loop_callback));',
                'error_message' => 'UndefinedGlobalVariable'
            ],
            'parseUrlPossiblyUndefined' => [
                '<?php
                    function bar(string $s) : string {
                        $parsed = parse_url($s);

                        return $parsed["host"];
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'parseUrlPossiblyUndefined2' => [
                '<?php
                    function bag(string $s) : string {
                        $parsed = parse_url($s);

                        if (is_string($parsed["host"] ?? false)) {
                            return $parsed["host"];
                        }

                        return "";
                    }',
                'error_message' => 'PossiblyUndefinedArrayOffset',
            ],
            'strtolowerEmptiness' => [
                '<?php
                    /** @param non-empty-string $s */
                    function foo(string $s) : void {
                        $s = strtolower($s);

                        if ($s) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'strposNoSetFirstParam' => [
                '<?php
                    function sayHello(string $format): void {
                        if (strpos("u", $format)) {}
                    }',
                'error_message' => 'InvalidLiteralArgument',
            ],
            'pregSplitNoEmpty' => [
                '<?php
                    /** @return non-empty-list */
                    function foo(string $s) {
                        return preg_split("/ /", $s, -1, PREG_SPLIT_NO_EMPTY);
                    }',
                'error_message' => 'InvalidReturnStatement'
            ],
        ];
    }
}
