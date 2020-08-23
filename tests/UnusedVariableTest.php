<?php
namespace Psalm\Tests;

use function preg_quote;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;
use function strpos;

class UnusedVariableTest extends TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

    /**
     * @return void
     */
    public function setUp() : void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_analyzer = new \Psalm\Internal\Analyzer\ProjectAnalyzer(
            new TestConfig(),
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            )
        );

        $this->project_analyzer->setPhpVersion('7.3');
        $this->project_analyzer->getCodebase()->reportUnusedVariables();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $code
     * @param array<string> $error_levels
     *
     * @return void
     */
    public function testValidCode($code, array $error_levels = [])
    {
        $test_name = $this->getTestName();
        if (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code
        );

        foreach ($error_levels as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @dataProvider providerInvalidCodeParse
     *
     * @param string $code
     * @param string $error_message
     * @param array<string> $error_levels
     *
     * @return void
     */
    public function testInvalidCode($code, $error_message, $error_levels = [])
    {
        if (strpos($this->getTestName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException(\Psalm\Exception\CodeException::class);
        $this->expectExceptionMessageRegExp('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        foreach ($error_levels as $error_level) {
            $this->project_analyzer->getCodebase()->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->addFile(
            $file_path,
            $code
        );

        $this->analyzeFile($file_path, new Context());
    }

    /**
     * @return array<string, array{string,error_levels?:string[]}>
     */
    public function providerValidCodeParse()
    {
        return [
            'arrayOffset' => [
                '<?php
                    $a = 0;

                    $arr = ["hello"];

                    echo $arr[$a];',
            ],
            'unset' => [
                '<?php
                    $a = 0;

                    $arr = ["hello"];

                    unset($arr[$a]);',
            ],
            'usedVariables' => [
                '<?php
                    /** @return string */
                    function foo() {
                        $a = 5;
                        $b = [];
                        $c[] = "hello";
                        class Foo {
                            public function __construct(string $_i) {}
                        }
                        $d = "Foo";
                        $e = "arg";
                        $f = new $d($e);
                        return $a . implode(",", $b) . $c[0] . get_class($f);
                    }',
                'error_levels' => [
                    'PossiblyUndefinedVariable',
                    'MixedArrayAccess',
                    'MixedOperand',
                    'MixedAssignment',
                    'InvalidStringClass',
                ],
            ],
            'varDefinedInIfWithReference' => [
                '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $b = "hello";
                    } else {
                        $b = "goodbye";
                    }
                    echo $a . $b;',
            ],
            'varRedefinedInIfWithReference' => [
                '<?php
                    $a = (string) "fdf";

                    if (rand(0, 1)) {
                        $a = (string) "ard";
                    }

                    echo $a;',
            ],
            'byrefInForeachLoopWithReference' => [
                '<?php
                    $a = [1, 2, 3];
                    foreach ($a as &$b) {
                        $b = $b + 1;
                    }
                    echo $a[0];',
            ],
            'foreachVarSetInValue' => [
                '<?php
                    /** @param string[] $arr */
                    function foo(array $arr) : void {
                        $a = null;
                        foreach ($arr as $a) { }
                        if ($a) {}
                    }',
            ],
            'definedInSecondBranchOfCondition' => [
                '<?php
                    if (rand(0, 1) && $a = rand(0, 1)) {
                        echo $a;
                    }',
            ],
            'booleanOr' => [
                '<?php
                    function foo(int $a, int $b): bool {
                        return $a || $b;
                    }',
            ],
            'paramUsedInIf' => [
                '<?php
                    function foo(string $a): void {
                        if (rand(0, 1)) {
                            echo $a;
                        }
                    }',
            ],
            'dummyByRefVar' => [
                '<?php
                    function foo(string &$a = null, string $b = null): void {
                        if ($a) {
                            echo $a;
                        }
                        if ($b) {
                            echo $b;
                        }
                    }

                    function bar(): void {
                        foo($dummy_byref_var, "hello");
                    }

                    bar();',
            ],
            'foreachReassigned' => [
                '<?php
                    $a = false;

                    foreach ([1, 2, 3] as $b) {
                        $a = true;
                        echo $b;
                    }

                    echo $a;',
            ],
            'doWhileReassigned' => [
                '<?php
                    $a = 5;

                    do {
                        echo $a;
                        $a = $a - rand(-3, 3);
                    } while ($a > 3);',
            ],
            'loopTypeChangedInIfAndContinueWithReference' => [
                '<?php
                    $a = false;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = true;
                            continue;
                        }

                        $a = false;
                    }

                    echo $a;',
            ],
            'loopReassignedInIfAndContinueWithReferenceAfter' => [
                '<?php
                    $a = 5;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 7;
                            continue;
                        }

                        $a = 3;
                    }

                    echo $a;',
            ],
            'loopReassignedInIfAndContinueWithReferenceBeforeAndAfter' => [
                '<?php
                    $a = 5;

                    echo $a;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 7;
                            continue;
                        }

                        $a = 3;
                    }

                    echo $a;',
            ],
            'loopReassigned' => [
                '<?php
                    $a = false;

                    while(rand(0, 1)) {
                        $a = true;
                    }

                    echo $a;',
            ],
            'ifVarReassignedInBranchWithUse' => [
                '<?php
                    $a = true;

                    if (rand(0, 1)) {
                        $a = false;
                    }

                    if ($a) {
                        echo "cool";
                    }',
            ],
            'elseVarReassignedInBranchAndReference' => [
                '<?php
                    $a = false;

                    if (rand(0, 1)) {
                        // do nothing
                    } else {
                        $a = true;
                        //echo $a;
                    }

                    if ($a) {
                        echo "cool";
                    }',
            ],
            'switchVarReassignedInBranch' => [
                '<?php
                    $a = false;

                    switch (rand(0, 2)) {
                        case 0:
                            $a = true;
                    }

                    if ($a) {
                        echo "cool";
                    }',
            ],
            'switchVarDefinedInAllBranches' => [
                '<?php
                    switch (rand(0, 2)) {
                        case 0:
                            $a = true;
                            break;

                        default:
                            $a = false;
                    }

                    if ($a) {
                        echo "cool";
                    }',
            ],
            'switchVarConditionalAssignmentWithReference' => [
                '<?php
                    switch (rand(0, 4)) {
                        case 0:
                            if (rand(0, 1)) {
                                $a = 0;
                                break;
                            }

                        default:
                            $a = 1;
                    }

                    echo $a;',
            ],
            'throwWithMessageCall' => [
                '<?php
                    function dangerous(): void {
                        throw new \Exception("bad");
                    }

                    function callDangerous(): void {
                        try {
                            dangerous();
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    }',
            ],
            'throwWithMessageCallAndAssignmentAndReference' => [
                '<?php
                    function dangerous(): string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        return "hello";
                    }

                    function callDangerous(): void {
                        $s = null;

                        try {
                            $s = dangerous();
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }

                        if ($s) {}
                    }',
            ],
            'throwWithMessageCallAndAssignmentInCatchAndReference' => [
                '<?php
                    function dangerous(): string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        return "hello";
                    }

                    function callDangerous(): void {
                        $s = null;

                        try {
                            dangerous();
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            $s = "hello";
                        }

                        if ($s) {}
                    }',
            ],
            'throwWithMessageCallAndAssignmentInTryAndCatchAndReference' => [
                '<?php
                    function dangerous(): string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        return "hello";
                    }

                    function callDangerous(): void {
                        $s = null;

                        try {
                            $s = dangerous();
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            $s = "hello";
                        }

                        if ($s) {}
                    }',
            ],
            'throwWithMessageCallAndNestedAssignmentInTryAndCatchAndReference' => [
                '<?php
                    function dangerous(): string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        return "hello";
                    }

                    function callDangerous(): void {
                        $s = null;

                        if (rand(0, 1)) {
                            $s = "hello";
                        } else {
                            try {
                                $t = dangerous();
                            } catch (Exception $e) {
                                echo $e->getMessage();
                                $t = "hello";
                            }

                            if ($t) {
                                $s = $t;
                            }
                        }

                        if ($s) {}
                    }',
            ],
            'throwWithReturnInOneCatch' => [
                '<?php
                    class E1 extends Exception {}

                    function dangerous(): void {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }
                    }

                    function callDangerous(): void {
                        try {
                            dangerous();
                            $s = true;
                        } catch (E1 $e) {
                            echo $e->getMessage();
                            $s = false;
                        } catch (Exception $e) {
                            return;
                        }

                        if ($s) {}
                    }',
            ],
            'loopWithIfRedefinition' => [
                '<?php
                    $i = false;

                    foreach ([1, 2, 3] as $a) {
                        if (rand(0, 1)) {
                            $i = true;
                        }

                        echo $a;
                    }

                    if ($i) {}',
            ],
            'unknownMethodCallWithVar' => [
                '<?php
                    /** @psalm-suppress MixedMethodCall */
                    function passesByRef(object $a): void {
                        /** @psalm-suppress PossiblyUndefinedVariable */
                        $a->passedByRef($b);
                    }',
            ],
            'usedMethodCallVariable' => [
                '<?php
                    function reindex(array $arr, string $methodName): array {
                        $ret = [];

                        foreach ($arr as $element) {
                            $ret[$element->$methodName()] = true;
                        }

                        return $ret;
                    }',
                'error_levels' => [
                    'MixedAssignment',
                    'MixedMethodCall',
                    'MixedArrayOffset',
                    'MixedTypeCoercion',
                ],
            ],
            'globalVariableUsage' => [
                '<?php
                    $a = "hello";
                    function example() : void {
                        global $a;
                        echo $a;
                        $a = "hello";
                    }
                    example();',
            ],
            'staticVar' => [
                '<?php
                    function use_static() : void {
                        static $token;
                        if (!$token) {
                            $token = rand(1, 10);
                        }
                        echo "token is $token\n";
                    }',
            ],
            'staticVarUsedLater' => [
                '<?php
                    function use_static() : int {
                        static $x = null;
                        if ($x) {
                            return (int) $x;
                        }
                        $x = rand(0, 1);
                        return -1;
                    }',
            ],
            'tryCatchWithUseInIf' => [
                '<?php
                    function example_string() : string {
                        if (rand(0, 1) > 0) {
                            return "value";
                        }
                        throw new Exception("fail");
                    }

                    function main() : void {
                        try {
                            $s = example_string();
                            if (!$s) {
                                echo "Failed to get string\n";
                            }
                        } catch (Exception $e) {
                            $s = "fallback";
                        }
                        printf("s is %s\n", $s);
                    }',
            ],
            'loopTypeChangedInIfAndBreakWithReference' => [
                '<?php
                    $a = 1;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 2;
                            break;
                        }

                        $a = 3;
                    }

                    echo $a;',
            ],
            'loopReassignedInIfAndBreakWithReferenceAfter' => [
                '<?php
                    $a = 5;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 7;
                            break;
                        }

                        $a = 3;
                    }

                    echo $a;',
            ],
            'loopSetIfNullWithBreakAndReference' => [
                '<?php
                    $a = null;

                    while (rand(0, 1)) {
                        if ($a !== null) {
                            $a = 4;
                            break;
                        }

                        $a = 5;
                    }

                    echo $a;',
            ],
            'loopSetIfNullWithContinueAndReference' => [
                '<?php
                    $a = null;

                    while (rand(0, 1)) {
                        if ($a !== null) {
                            $a = 4;
                            continue;
                        }

                        $a = 5;
                    }

                    echo $a;',
            ],
            'loopAssignmentAfterReferenceSimple' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;
                        $a = 1;
                    }',
            ],
            'loopAssignmentAfterReferenceWithContinue' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;
                        $a = 1;
                        continue;
                    }',
            ],
            'loopAssignmentAfterReferenceWithConditionalAssignmentWithContinue' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;

                        if (rand(0, 1)) {
                            $a = 1;
                        }

                        continue;
                    }',
            ],
            'loopAssignmentAfterReferenceWithContinueInIf' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;

                        if (rand(0, 1)) {
                            $a = 1;
                            continue;
                        }
                    }',
            ],
            'loopAssignmentAfterReferenceWithContinueInSwitch' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        switch (rand(0, 1)) {
                            case 0:
                                $a = 1;
                                break;
                        }
                    }

                    echo $a;',
            ],
            'loopAssignmentAfterReferenceWithContinueInSwitch2' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            switch (rand(0, 1)) {
                                case 0:
                                    $a = 1;
                                    break;
                            }
                        }

                    }

                    echo $a;',
            ],
            'listVarAssignmentInIf' => [
                '<?php
                    $a = "a";
                    $b = "b";

                    if (rand(0, 1)) {
                        list($a, $b) = explode(".", "c.d");
                    }

                    echo $a;
                    echo $b;',
            ],
            'varCheckAfterNestedAssignmentAndBreak' => [
                '<?php
                    $a = false;

                    if (rand(0, 1)) {
                        while (rand(0, 1)) {
                            $a = true;
                            break;
                        }
                    }

                    if ($a) {}',
            ],
            'varCheckAfterNestedAssignmentAndBreakInIf' => [
                '<?php
                    $a = false;

                    if (rand(0, 1)) {
                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                                break;
                            }
                        }
                    }

                    if ($a) {}',
            ],
            'breakInSwitchStatementIf' => [
                '<?php
                    $a = 0;

                    while (rand(0, 1)) {
                        switch (rand(0, 1)) {
                            default:
                                echo $a;

                                if (rand(0, 1)) {
                                    $a = 5;
                                    break;
                                }
                        }
                    }',
            ],
            'breakInSwitchStatementIfWithSecondCase' => [
                '<?php
                    $a = 0;

                    while (rand(0, 1)) {
                        switch (rand(0, 1)) {
                            case 0:
                                $a = 1;
                                break;

                            default:
                                echo $a;

                                if (rand(0, 1)) {
                                    $a = 5;
                                    break;
                                }
                        }
                    }',
            ],
            'echoVarWithAdditionOp' => [
                '<?php
                    function foo(int $i) : void {
                        echo $i;
                    }
                    $a = 5;
                    foo($a += 1);',
            ],
            'echoVarWithIncrement' => [
                '<?php
                    function foo(int $i) : void {
                        echo $i;
                    }
                    $a = 5;
                    foo(++$a);',
            ],
            'afterMethodExistsCheck' => [
                '<?php
                    class A {
                        /**
                         * @param array<string, string> $options
                         */
                        public function __construct(array $options) {
                            $this->setOptions($options);
                        }

                        /**
                         * @param array<string, string> $options
                         */
                        protected function setOptions(array $options): void
                        {
                            foreach ($options as $key => $value) {
                                $normalized = ucfirst($key);
                                $method     = "set" . $normalized;

                                if (method_exists($this, $method)) {
                                    $this->$method($value);
                                }
                            }
                        }
                    }

                    new A(["bar" => "bat"]);',
            ],
            'instanceofVarUse' => [
                '<?php
                    interface Foo { }

                    function returnFoo(): Foo {
                        return new class implements Foo { };
                    }

                    $interface = Foo::class;

                    if (returnFoo() instanceof $interface) {
                        exit;
                    }',
            ],
            'usedVariableInDoWhile' => [
                '<?php
                    $i = 5;
                    do {
                        echo "hello";
                    } while (--$i > 0);
                    echo $i;',
            ],
            'callableReferencesItself' => [
                '<?php
                    /** @psalm-suppress UnusedParam */
                    function foo(callable $c) : void {}
                    $listener = function () use (&$listener) : void {
                        /** @psalm-suppress MixedArgument */
                        foo($listener);
                    };
                    foo($listener);',
            ],
            'newVariableConstructor' => [
                '<?php
                    /**
                     * @param class-string<ArrayObject> $type
                     */
                    function bar(string $type) : ArrayObject {
                        $data = [["foo"], ["bar"]];

                        /** @psalm-suppress UnsafeInstantiation */
                        return new $type($data[0]);
                    }',
            ],
            'byRefVariableUsedInAddition' => [
                '<?php
                    $i = 0;
                    $a = function () use (&$i) : void {
                        $i = 1;
                    };
                    $a();',
            ],
            'superGlobalInFunction' => [
                '<?php
                    function example1() : void {
                        $_SESSION = [];
                    }
                    function example2() : int {
                        return (int) $_SESSION["str"];
                    }',
            ],
            'usedInArray' => [
                '<?php
                    /**
                     * @psalm-suppress MixedMethodCall
                     * @psalm-suppress MissingParamType
                     */
                    function foo($a) : void {
                        $b = "b";
                        $a->bar([$b]);
                    }',
            ],
            'paramUsedInsideLoop' => [
                '<?php
                    function foo(int $counter) : void {
                        foreach ([1, 2, 3] as $_) {
                            echo ($counter = $counter + 1);
                            echo rand(0, 1) ? 1 : 0;
                        }
                    }',
            ],
            'useParamInsideIfLoop' => [
                '<?php
                    function foo() : void {
                        $a = 1;

                        if (rand(0, 1)) {
                            while (rand(0, 1)) {
                                $a = 2;
                            }
                        }

                        echo $a;
                    }',
            ],
            'useVariableInsideTry' => [
                '<?php
                    $foo = false;

                    try {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        $foo = rand(0, 1);

                        if ($foo) {}
                    } catch (Exception $e) {}

                    if ($foo) {}',
            ],
            'useTryAssignedVariableInsideFinally' => [
                '<?php
                    $var = "";
                    try {
                        if (rand(0, 1)) {
                            throw new \Exception();
                        }
                        $var = "hello";
                    } finally {
                        if ($var !== "") {
                            echo $var;
                        }
                    }',
            ],
            'useTryAssignedVariableInFinallyWhenCatchExits' => [
                '<?php
                    /**
                     * @return resource
                     */
                    function getStream() {
                        throw new \Exception();
                    }

                    $stream = null;

                    try {
                        $stream = getStream();
                        \file_put_contents("./foobar", $stream);
                    } catch (\Exception $e) {
                        throw new \Exception("Something went wrong");
                    } finally {
                        if ($stream) {
                            \fclose($stream);
                        }
                    }',
            ],
            'varUsedInloop' => [
                '<?php
                    class A {
                        public static function getA() : ?A {
                            return rand(0, 1) ? new A : null;
                        }
                    }

                    function foo(?A $a) : void {
                        while ($a) {
                            echo get_class($a);
                            $a = A::getA();
                        }
                    }',
            ],
            'varPassedByRef' => [
                '<?php
                    function foo(array $b) : void {
                        $a = &$b;
                        $a["foo"] = 5;
                    }',
            ],
            'usedAsMethodName' => [
                '<?php
                    class A {
                        public static function foo() : void {}
                    }

                    function foo() : void {
                        $method = "foo";
                        A::$method();
                    }',
            ],
            'usedAsStaticPropertyName' => [
                '<?php
                    class A {
                        private static bool $something = false;

                        public function foo() : void {
                            $var = "something";

                            if (rand(0, 1)) {
                                static::${$var} = true;
                            }
                        }
                    }'
            ],
            'setInLoopThatsAlwaysEntered' => [
                '<?php
                    /**
                     * @param non-empty-array<int> $a
                     */
                    function getLastNum(array $a): int {
                        foreach ($a as $num) {
                            $last = $num;
                        }
                        return $last;
                    }'
            ],
            'usedStrtolowerInArray' => [
                '<?php
                    /**
                     * @param array<string, int> $row
                     */
                    function foo(array $row, string $s) : array {
                        $row["a" . strtolower($s)] += 1;
                        return $row;
                    }',
            ],
            'pureWithReflectionMethodSetValue' => [
                '<?php
                    function foo(object $mock) : void {
                        $m = new \ReflectionProperty($mock, "bar");
                        $m->setValue([get_class($mock) => "hello"]);
                    }'
            ],
            'defineBeforeAssignmentInConditional' => [
                '<?php
                    $i = null;

                    if (rand(0, 1) || ($i = rand(0, 1))) {
                        echo $i;
                    }',
            ],
            'definedInFirstAssignmentInConditional' => [
                '<?php
                    if (($b = rand(0, 1)) || rand(0, 1)) {
                        echo $b;
                    }',
            ],
            'noUnusedVariableWhenUndefinedMethod' => [
                '<?php
                    class A {}

                    function foo(A $a) : void {
                        $i = 0;

                        /** @psalm-suppress UndefinedMethod */
                        $a->bar($i);
                    }',
            ],
            'noUnusedVariableAfterRedeclaredInCatch' => [
                '<?php
                    $path = "";

                    echo $path;

                    try {
                        // do nothing
                    } catch (\Exception $exception) {
                        $path = "hello";
                    }

                    echo $path;'
            ],
            'assignedInElseif' => [
                '<?php
                    function bar(): int {
                        if (rand(0, 1) === 0) {
                            $foo = 0;
                        } elseif ($foo = rand(0, 10)) {
                            return 5;
                        }

                        return $foo;
                    }',
            ],
            'refineForeachVarType' => [
                '<?php
                    function foo() : array {
                        return ["hello"];
                    }

                    /** @var string $s */
                    foreach (foo() as $s) {
                        echo $s;
                    }',
            ],
            'doWhileReassignedInConditional' =>  [
                '<?php
                    $index = 0;

                    do {
                      echo $index;
                    } while (($index = $index +  1) < 10);'
            ],
            'tryCatchInsaneRepro' => [
                '<?php
                    function maybeThrows() : string {
                        return "hello";
                    }

                    function b(bool $a): void {
                        if (!$a) {
                            return;
                        }

                        $b = "";

                        try {
                            $b = maybeThrows();
                            echo $b;
                        } catch (\Exception $e) {}

                        echo $b;
                    }'
            ],
            'tryCatchInsaneReproNoFirstBoolCheck' => [
                '<?php
                    function maybeThrows() : string {
                        return "hello";
                    }

                    function b(): void {
                        $b = "";

                        try {
                            $b = maybeThrows();
                            echo $b;
                        } catch (\Exception $e) {}

                        echo $b;
                    }'
            ],
            'tryWithWhile' => [
                '<?php
                    function foo(): void {
                        $done = false;

                        while (!$done) {
                            try {
                                $done = true;
                            } catch (\Exception $e) {
                            }
                        }
                    }',
            ],
            'tryWithWhileWithoutTry' => [
                '<?php
                    function foo(): void {
                        $done = false;

                        while (!$done) {
                            $done = true;
                        }
                    }',
            ],
            'usedInCatchAndTryWithReturnInTry' => [
                '<?php
                    function foo() : ?string {
                        $a = null;

                        try {
                            $a = "hello";
                            echo $a;
                        } catch (Exception $e) {
                            return $a;
                        }

                        return $a;
                    }

                    function dangerous() : string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }
                        return "hello";
                    }',
            ],
            'useTryAndCatchAssignedVariableInsideFinally' => [
                '<?php
                    function foo() : void {
                        try {
                            // do something dangerous
                            $a = 5;
                        } catch (Exception $e) {
                            $a = 4;
                            throw new Exception("bad");
                        } finally {
                            echo $a;
                        }
                    }'
            ],
            'usedVarInCatchAndAfter' => [
                '<?php
                    function foo() : void {
                       if (rand(0, 1)) {
                            throw new \Exception("bad");
                       }
                    }

                    $a = null;

                    try {
                        foo();
                        $a = "hello";
                    } catch (\Exception $e) {
                        echo $a;
                    }

                    echo $a;'
            ],
            'unusedForeach' => [
                '<?php
                    /**
                     * @param array<int, string> $test
                     */
                    function foo(array $test) : void {
                        foreach($test as $key => $_testValue) {
                            echo $key;
                        }
                    }'
            ],
            'usedAfterMixedVariableAssignment' => [
                '<?php
                    function foo(array $arr): array {
                        $c = "c";
                        /** @psalm-suppress MixedArrayAssignment */
                        $arr["a"]["b"][$c] = 1;
                        return $arr;
                    }',
            ],
            'binaryOpIncrementInElse' => [
                '<?php
                    function foo(int $i, string $alias) : void {
                        echo $alias ?: $i++;
                        echo $i;
                    }'
            ],
            'binaryOpIncrementInCond' => [
                '<?php
                    function foo(int $i, string $alias) : void {
                        echo $i++ ?: $alias;
                        echo $i;
                    }'
            ],
            'binaryOpIncrementInIf' => [
                '<?php
                    function foo(int $i, string $alias) : void {
                        echo rand(0, 1) ? $i++ : $alias;
                        echo $i;
                    }'
            ],
            'usedInNewCall' => [
                '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedMethodCall
                     * @psalm-suppress MissingParamType
                     * @psalm-suppress MixedArgument
                     * @psalm-suppress PossiblyNullArgument
                     */
                    function foo($a): void {
                        $m = $_GET["m"] ?? null;
                        $a->foo(new Exception($m));
                    }',
            ],
            'validMixedAnnotation' => [
                '<?php
                    function keys(): array {
                        return ["foo", "bar"];
                    }

                    /** @var mixed $k */
                    foreach (keys() as $k) {
                        echo gettype($k);
                    }'
            ],
            'byRefVariableAfterAssignment' => [
                '<?php
                    class A {
                        public string $value = "";
                        public function writeByRef(string $value): void {
                            $update =& $this->value;
                            $update = $value;
                        }
                    }'
            ],
            'createdAndUsedInCondition' => [
                '<?php
                    class A {
                        public function foo() : bool {
                            return true;
                        }
                    }

                    function getA() : ?A {
                        return rand(0, 1) ? new A() : null;
                    }

                    if (rand(0, 1)) {
                        if (!($a = getA()) || $a->foo()) {}
                        return;
                    }

                    if (!($a = getA()) || $a->foo()) {}'
            ],
            'usedInUndefinedFunction' => [
                '<?php
                    /**
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function test(): string {
                        $s = "a";
                        /** @psalm-suppress UndefinedFunction */
                        return undefined_function($s);
                    }'
            ],
            'useVariableVariable' => [
                '<?php
                    $variables = ["a" => "b", "c" => "d"];

                    foreach ($variables as $name => $value) {
                        ${$name} = $value;
                    }'
            ],
            'usedLoopVariable' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        if ($a < 20) {
                            $a = $a + 1;
                            echo "hello";
                            continue;
                        }
                        echo "goodbye";
                        break;
                    }'
            ],
            'usedForVariable' => [
                '<?php
                    $a = 0;
                    for ($i = 0; $i < 1000; $i++) {
                        if (rand(0, 1)) {
                            $a = $a + $i;
                            continue;
                        }
                        break;
                    }

                    echo $a;'
            ],
            'breakInForeachInsideSwitch' => [
                '<?php
                    function foo(string $b) : void {
                        switch ($b){
                            case "foo":
                                $a = null;
                                foreach ([1,2,3] as $f){
                                    if ($f == 2) {
                                        $a = $f;
                                        break;
                                    }
                                }
                                echo $a;
                        }
                    }'
            ],
            'passedByRefArrayOffset' => [
                '<?php
                    $a = [
                        "a" => [1],
                        "b" => [2]
                    ];

                    foreach (["a"] as $e){
                        takes_ref($a[$e]);
                    }

                    function takes_ref(array &$p): void {
                        echo implode(",", $p);
                    }'
            ],
            'doWhileWithBreak' => [
                '<?php
                    function foo(): void {
                        $f = false;

                        do {
                            if (rand(0,1)) {
                                $f = true;
                                break;
                            }
                        } while (rand(0,1));

                        if ($f) {}
                    }'
            ],
            'usedParamInWhileDirectly' => [
                '<?php
                    function foo(int $index): void {
                        while (100 >= $index = nextNumber($index)) {
                            // ...
                        }
                    }

                    function nextNumber(int $eee): int {
                        return $eee + 1;
                    }'
            ],
            'usedParamInWhileIndirectly' => [
                '<?php
                    function foo(int $i): void {
                        $index = $i;
                        while (100 >= $index = nextNumber($index)) {
                            // ...
                        }
                    }

                    function nextNumber(int $i): int {
                        return $i + 1;
                    }'
            ],
            'doArrayIncrement' => [
                '<?php
                    /**
                     * @param list<int> $keys
                     * @param int $key
                     */
                    function error2(array $keys, int $key): int
                    {
                        if ($key === 1) {}

                        do {
                            $nextKey = $keys[++$key] ?? null;
                        } while ($nextKey === null);

                        return $nextKey;
                    }'
            ],
        ];
    }

    /**
     * @return array<string,array{string,error_message:string}>
     */
    public function providerInvalidCodeParse()
    {
        return [
            'simpleUnusedVariable' => [
                '<?php
                    $a = 5;
                    $b = [];
                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithAdditionOp' => [
                '<?php
                    $a = 5;
                    $a += 1;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithConditionalAdditionOp' => [
                '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $a += 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithConditionalAddition' => [
                '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $a = $a + 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithIncrement' => [
                '<?php
                    $a = 5;
                    $a++;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithConditionalIncrement' => [
                '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $a++;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'ifInBothBranchesWithoutReference' => [
                '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $b = "hello";
                    } else {
                        $b = "goodbye";
                    }
                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'varInNestedAssignmentWithoutReference' => [
                '<?php
                    if (rand(0, 1)) {
                        $a = "foo";
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varInSecondNestedAssignmentWithoutReference' => [
                '<?php
                    if (rand(0, 1)) {
                        $a = "foo";
                        echo $a;
                    }

                    if (rand(0, 1)) {
                        $a = "foo";
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varReassignedInBothBranchesOfIf' => [
                '<?php
                    $a = "foo";

                    if (rand(0, 1)) {
                        $a = "bar";
                    } else {
                        $a = "bat";
                    }

                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'varReassignedInNestedBranchesOfIf' => [
                '<?php
                    $a = "foo";

                    if (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = "bar";
                        } else {
                            $a = "bat";
                        }
                    } else {
                        $a = "bang";
                    }

                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'ifVarReassignedInBranchWithNoUse' => [
                '<?php
                    $a = true;

                    if (rand(0, 1)) {
                        $a = false;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'elseVarReassignedInBranchAndNoReference' => [
                '<?php
                    $a = true;

                    if (rand(0, 1)) {
                        // do nothing
                    } else {
                        $a = false;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarReassignedInBranch' => [
                '<?php
                    $a = false;

                    switch (rand(0, 2)) {
                        case 0:
                            $a = true;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarReassignedInBranchWithDefault' => [
                '<?php
                    $a = false;

                    switch (rand(0, 2)) {
                        case 0:
                            $a = true;
                            break;

                        default:
                            $a = false;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarReassignedInAllBranches' => [
                '<?php
                    $a = false;

                    switch (rand(0, 2)) {
                        case 0:
                            $a = true;
                            break;

                        default:
                            $a = false;
                    }

                    if ($a) {
                        echo "cool";
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedListVar' => [
                '<?php
                    list($a, $b) = explode(" ", "hello world");
                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedPreForVar' => [
                '<?php
                    $i = 0;

                    for ($i = 0; $i < 10; $i++) {
                        echo $i;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedIfInReturnBlock' => [
                '<?php
                    $i = rand(0, 1);

                    foreach ([1, 2, 3] as $a) {
                        if ($a % 2) {
                            $i = 7;
                            return;
                        }
                    }

                    if ($i) {}',
                'error_message' => 'UnusedVariable',
            ],
            'unusedIfVarInBranch' => [
                '<?php
                    if (rand(0, 1)) {

                    } elseif (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = "foo";
                        } else {
                            $a = "bar";
                            echo $a;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'throwWithMessageCallAndAssignmentAndNoReference' => [
                '<?php
                    function dangerous(): string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        return "hello";
                    }

                    function callDangerous(): void {
                        $s = null;

                        try {
                            $s = dangerous();
                        } catch (Exception $e) {
                            echo $e->getMessage();
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'throwWithMessageCallAndAssignmentInCatchAndNoReference' => [
                '<?php
                    function dangerous(): string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        return "hello";
                    }

                    function callDangerous(): void {
                        $s = null;

                        try {
                            dangerous();
                        } catch (Exception $e) {
                            echo $e->getMessage();
                            $s = "hello";
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'throwWithMessageCallAndNestedAssignmentInTryAndCatchAndNoReference' => [
                '<?php
                    function dangerous(): string {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        return "hello";
                    }

                    function callDangerous(): void {
                        $s = null;

                        if (rand(0, 1)) {
                            $s = "hello";
                        } else {
                            try {
                                $t = dangerous();
                            } catch (Exception $e) {
                                echo $e->getMessage();
                                $t = "hello";
                            }

                            if ($t) {
                                $s = $t;
                            }
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'throwWithReturnInOneCatchAndNoReference' => [
                '<?php
                    class E1 extends Exception {}

                    function dangerous(): void {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }
                    }

                    function callDangerous(): void {
                        try {
                            dangerous();
                            $s = true;
                        } catch (E1 $e) {
                            echo $e->getMessage();
                            $s = false;
                        } catch (Exception $e) {
                            return;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopTypeChangedInIfWithoutReference' => [
                '<?php
                    $a = false;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = true;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopTypeChangedInIfAndContinueWithoutReference' => [
                '<?php
                    $a = false;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = true;
                            continue;
                        }

                        $a = false;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopReassignedInIfAndContinueWithoutReferenceAfter' => [
                '<?php
                    $a = 5;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 7;
                            continue;
                        }

                        $a = 3;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopReassignedInIfAndContinueWithoutReference' => [
                '<?php
                    $a = 3;

                    echo $a;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 5;
                            continue;
                        }

                        $a = 3;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedConditionalCode' => [
                '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                      $a = $a + 5;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varDefinedInIfWithoutReference' => [
                '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $b = "hello";
                    } else {
                        $b = "goodbye";
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'SKIPPED-byrefInForeachLoopWithoutReference' => [
                '<?php
                    $a = [1, 2, 3];
                    foreach ($a as &$b) {
                        $b = $b + 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopSetIfNullWithBreakWithoutReference' => [
                '<?php
                    $a = null;

                    while (rand(0, 1)) {
                        if ($a !== null) {
                            $a = 4;
                            break;
                        }

                        $a = 5;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopSetIfNullWithBreakWithoutReference2' => [
                '<?php
                    $a = null;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 4;
                            break;
                        }

                        $a = 5;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopSetIfNullWithContinueWithoutReference' => [
                '<?php
                    $a = null;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = 4;
                            continue;
                        }

                        $a = 5;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopAssignmentAfterReferenceWithBreak' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;
                        $a = 1;
                        break;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopAssignmentAfterReferenceWithBreakInIf' => [
                '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;

                        if (rand(0, 1)) {
                            $a = 1;
                            break;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarConditionalAssignmentWithoutReference' => [
                '<?php
                    switch (rand(0, 4)) {
                        case 0:
                            if (rand(0, 1)) {
                                $a = 0;
                                break;
                            }

                        default:
                            $a = 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchInIf' => [
                '<?php
                    $a = 0;

                    if (rand(0, 1)) {
                        switch (rand(0, 4)) {
                            case 0:
                                $a = 3;
                                break;

                            default:
                                $a = 3;
                        }
                    } else {
                        $a = 6;
                    }

                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'reusedKeyVar' => [
                '<?php
                    $key = "a";
                    echo $key;

                    $arr = ["foo" => "foo.foo"];

                    foreach ($arr as $key => $v) {
                        list($key) = explode(".", $v);
                        echo $key;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVarBeforeTryInsideForeach' => [
                '<?php
                    function foo() : void {
                        $unused = 1;

                        while (rand(0, 1)) {
                            try {} catch (\Exception $e) {}
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideIfLoop' => [
                '<?php
                    function foo() : void {
                        $a = 1;

                        if (rand(0, 1)) {
                            while (rand(0, 1)) {
                                $a = 2;
                            }
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideIfElseLoop' => [
                '<?php
                    function foo() : void {
                        $a = 1;

                        if (rand(0, 1)) {
                        } else {
                            while (rand(0, 1)) {
                                $a = 2;
                            }
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideIfElseifLoop' => [
                '<?php
                    function foo() : void {
                        $a = 1;

                        if (rand(0, 1)) {
                        } elseif (rand(0, 1)) {
                            while (rand(0, 1)) {
                                $a = 2;
                            }
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideIfLoopWithEchoInside' => [
                '<?php
                    function foo() : void {
                        $a = 1;

                        if (rand(0, 1)) {
                            while (rand(0, 1)) {
                                $a = 2;
                                echo $a;
                            }
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideLoopAfterAssignment' => [
                '<?php
                    function foo() : void {
                        foreach ([1, 2, 3] as $i) {
                            $i = $i;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideLoopAfterAssignmentWithAddition' => [
                '<?php
                    function foo() : void {
                        foreach ([1, 2, 3] as $i) {
                            $i = $i + 1;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideLoopCalledInFunction' => [
                '<?php
                    function foo(int $s) : int {
                        return $s;
                    }

                    function bar() : void {
                        foreach ([1, 2, 3] as $i) {
                            $i = foo($i);
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableReassignedInIfFollowedByTryInsideForLoop' => [
                '<?php
                    $user_id = 0;
                    $user = null;

                    if (rand(0, 1)) {
                        $user_id = rand(0, 1);
                        $user = $user_id;
                    }

                    if ($user) {
                        $a = 0;
                        for ($i = 1; $i <= 10; $i++) {
                            $a += $i;
                            try {} catch (\Exception $e) {}
                        }
                        echo $i;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableReassignedInIfFollowedByTryInsideForeachLoop' => [
                '<?php
                    $user_id = 0;
                    $user = null;

                    if (rand(0, 1)) {
                        $user_id = rand(0, 1);
                        $user = $user_id;
                    }

                    if ($user) {
                        $a = 0;
                        foreach ([1, 2, 3] as $i) {
                            $a += $i;
                            try {} catch (\Exception $e) {}
                        }
                        echo $i;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUselessArrayAssignment' => [
                '<?php
                    function foo() : void {
                        $a = [];
                        $a[0] = 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedSecondAssignmentBeforeTry' => [
                '<?php
                    $a = [1, 2, 3];
                    echo($a[0]);
                    $a = [4, 5, 6];

                    try {
                      // something
                    } catch (\Throwable $t) {
                      // something else
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectRedundancyAfterLoopWithContinue' => [
                '<?php
                    $gap = null;

                    foreach ([1, 2, 3] as $_) {
                        if (rand(0, 1)) {
                            continue;
                        }

                        $gap = "asa";
                        throw new \Exception($gap);
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'setInLoopThatsAlwaysEnteredButNotReferenced' => [
                '<?php
                    /**
                     * @param non-empty-array<int> $a
                     */
                    function getLastNum(array $a): int {
                        foreach ($a as $num) {
                            $last = $num;
                        }
                        return 4;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'defineInBothBranchesOfConditional' => [
                '<?php
                    $i = null;

                    if (($i = rand(0, 5)) || ($i = rand(0, 3))) {
                        echo $i;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'knownVarType' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string */
                    $a = foo();

                    echo $a;',
                'error_message' => 'UnnecessaryVarAnnotation',
            ],
            'knownVarTypeWithName' => [
                '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string $a */
                    $a = foo();

                    echo $a;',
                'error_message' => 'UnnecessaryVarAnnotation',
            ],
            'knownForeachVarType' => [
                '<?php
                    /** @return string[] */
                    function foo() : array {
                        return ["hello"];
                    }

                    /** @var string $s */
                    foreach (foo() as $s) {
                        echo $s;
                    }',
                'error_message' => 'UnnecessaryVarAnnotation',
            ],
        ];
    }
}
