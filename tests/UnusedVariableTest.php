<?php
namespace Psalm\Tests;

use function preg_quote;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider;
use function strpos;

class UnusedVariableTest extends TestCase
{
    /** @var \Psalm\Internal\Analyzer\ProjectAnalyzer */
    protected $project_analyzer;

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

        $this->project_analyzer->setPhpVersion('7.4');
        $this->project_analyzer->getCodebase()->reportUnusedVariables();
    }

    /**
     * @dataProvider providerValidCodeParse
     *
     * @param string $code
     * @param array<string> $error_levels
     *
     */
    public function testValidCode($code, array $error_levels = []): void
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
     */
    public function testInvalidCode($code, $error_message, $error_levels = []): void
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
    public function providerValidCodeParse(): array
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
                    /**
                     * @param list<int> $arr
                     */
                    function foo(array $arr) : void {
                        $a = false;

                        foreach ($arr as $b) {
                            $a = true;
                            echo $b;
                        }

                        echo $a;
                    }',
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
            'arrayVarAssignmentInFunctionAndReturned' => [
                '<?php
                    /**
                     * @param array{string} $arr
                     */
                    function far(array $arr): string {
                        [$a] = $arr;

                        return $a;
                    }',
            ],
            'arrayUnpackInForeach' => [
                '<?php
                    /**
                     * @param list<array{string, string}> $arr
                     */
                    function far(array $arr): void {
                        foreach ($arr as [$a, $b]) {
                            echo $a;
                            echo $b;
                        }
                    }',
            ],
            'arrayAssignmentInFunctionCoerced' => [
                '<?php
                    class A {
                        public int $a = 0;
                        public int $b = 1;

                        function setPhpVersion(string $version): void {
                            [$a, $b] = explode(".", $version);

                            $this->a = (int) $a;
                            $this->b = (int) $b;
                        }
                    }
                    '
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
                    $a = 5;

                    while (rand(0, 1)) {
                        echo($a += 1);
                    }',
            ],
            'echoVarWithIncrement' => [
                '<?php
                    function foo(int $i) : void {
                        echo $i;
                    }

                    $a = 5;

                    while (rand(0, 1)) {
                        foo(++$a);
                    }',
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
                    $a();
                    echo $i;',
            ],
            'regularVariableClosureUseInAddition' => [
                '<?php
                    $i = 0;
                    $a = function () use ($i) : int {
                        return $i + 1;
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
                    function foo(array $returned) : array {
                        $ancillary = &$returned;
                        $ancillary["foo"] = 5;
                        return $returned;
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
                            /** @psalm-suppress PossiblyUndefinedVariable */
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
                     * @psalm-suppress MixedMethodCall
                     * @psalm-suppress MixedArgument
                     * @psalm-suppress PossiblyNullArgument
                     * @param mixed $mixed
                     * @param mixed|null $mixed_or_null
                     */
                    function foo($mixed, $mixed_or_null): void {
                        $mixed->foo(new Exception($mixed_or_null));
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
            'byRefVariableAfterAssignmentToArray' => [
                '<?php
                    $a = [1, 2, 3];
                    $b = &$a[1];
                    $b = 5;
                    print_r($a);'
            ],
            'byRefVariableAfterAssignmentToProperty' => [
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
                            $a = $a + 1;
                            continue;
                        }
                        break;
                    }

                    echo $a;'
            ],
            'usedForVariableMinusString' => [
                '<?php
                    function foo(string $limit) : void {
                        /**
                         * @psalm-suppress InvalidOperand
                         */
                        for ($i = $limit; $i > 0; $i--) {
                            echo $i . "\n";
                        }
                    }'
            ],
            'usedForVariablePlusString' => [
                '<?php
                    function foo(string $limit) : void {
                        /**
                         * @psalm-suppress InvalidOperand
                         */
                        for ($i = $limit; $i < 50; $i++) {
                            echo $i . "\n";
                        }
                    }'
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
            'passedByRefSimpleUndefinedBefore' => [
                '<?php
                    takes_ref($a);

                    function takes_ref(?array &$p): void {
                        $p = [0];
                    }'
            ],
            'passedByRefSimpleDefinedBefore' => [
                '<?php
                    $a = [];
                    takes_ref($a);

                    function takes_ref(?array &$p): void {
                        $p = [0];
                    }'
            ],
            'passedByRefSimpleDefinedBeforeWithExtract' => [
                '<?php
                    function foo(array $arr) : void {
                        while (rand(0, 1)) {
                            /** @psalm-suppress MixedArgument */
                            extract($arr);
                            $a = [];
                            takes_ref($a);
                        }
                    }

                    /** @param mixed $p */
                    function takes_ref(&$p): void {}'
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
            'usedParamInWhileAddition' => [
                '<?php
                    function foo(int $index): void {
                        while ($index++ <= 100) {
                            //
                        }
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
            'variableUsedIndirectly' => [
                '<?php
                    $a = 0;

                    while (rand(0,1)){
                        $b = $a + 1;
                        echo $b;
                        $a = $b;
                    }',
            ],
            'arrayMapClosureWithParamType' => [
                '<?php
                    $a = [1, 2, 3];

                    $b = array_map(
                        function(int $i) {
                            return $i * 3;
                        },
                        $a
                    );

                    foreach ($b as $c) {
                        echo $c;
                    }',
            ],
            'arrayMapClosureWithoutParamType' => [
                '<?php
                    $a = [1, 2, 3];

                    $b = array_map(
                        function($i) {
                            return $i * 3;
                        },
                        $a
                    );

                    foreach ($b as $c) {
                        echo $c;
                    }',
            ],
            'unusedArrayAdditionWithArrayChecked' => [
                '<?php
                    $a = [];

                    while (rand(0,1)) {
                        $a[] = 1;
                    }

                    if ($a) {}',
            ],
            'usedArrayRecursiveAddition' => [
                '<?php
                    $a = [];

                    while (rand(0,1)) {
                        $a[] = $a;
                    }

                    print_r($a);',
            ],
            'usedImmutableProperty' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class Clause {
                        /**
                         * @var array<int, int>
                         */
                        public $b = [];
                    }

                    function foo(Clause $c, int $var): void {
                        $new_b = $c->b;

                        if (isset($c->b[0])) {
                            $new_b[$var] = 0;
                        }

                        if ($new_b) {}
                    }',
            ],
            'arrayAssignOpAdditionInsideLoop' => [
                '<?php
                    /**
                     * @param array<string, string> $arr0
                     * @param array<string, string> $arr1
                     * @param array<string, string> $arr2
                     * @return void
                     */
                    function parp(array $arr0, array $arr1, array $arr2) {
                        $arr3 = $arr0;

                        foreach ($arr1 as $a) {
                            echo $a;
                            $arr3 += $arr2;
                        }

                        if ($arr3) {}
                    }',
            ],
            'arrayAdditionInsideLoop' => [
                '<?php
                    /**
                     * @param array<string, string> $arr0
                     * @param array<string, string> $arr1
                     * @param array<string, string> $arr2
                     * @return void
                     */
                    function parp(array $arr0, array $arr1, array $arr2) {
                        $arr3 = $arr0;

                        foreach ($arr1 as $a) {
                            echo $a;
                            $arr3 = $arr3 + $arr2;
                        }

                        if ($arr3) {}
                    }',
            ],
            'checkValueBeforeAdding' => [
                '<?php
                    class T {
                        public bool $b = false;
                    }

                    function foo(
                        ?T $t
                    ): void {
                        if (!$t) {
                            $t = new T();
                        } elseif (rand(0, 1)) {
                            //
                        }

                        if ($t->b) {}
                    }'
            ],
            'loopOverUnknown' => [
                '<?php
                    /** @psalm-suppress MixedAssignment */
                    function foo(Traversable $t) : void {
                        foreach ($t as $u) {
                            if ($u instanceof stdClass) {}
                        }
                    }'
            ],
            'loopWithRequire' => [
                '<?php
                    /**
                     * @psalm-suppress UnresolvableInclude
                     */
                    function foo(string $delta_file) : void {
                        while (rand(0, 1)) {
                            /**
                             * @var array<string, mixed>
                             */
                            $diff_call_map = require($delta_file);

                            foreach ($diff_call_map as $key => $_) {
                                $cased_key = strtolower($key);
                                echo $cased_key;
                            }
                        }
                    }',
            ],
            'loopAgain' => [
                '<?php
                    /** @param non-empty-list<string> $lines */
                    function parse(array $lines) : array {
                        $last = 0;
                        foreach ($lines as $k => $line) {
                            if (rand(0, 1)) {
                                $last = $k;
                            } elseif (rand(0, 1)) {
                                $last = 0;
                            } elseif ($last !== 0) {
                                $lines[$last] .= $line;
                            }
                        }

                        return $lines;
                    }'
            ],
            'necessaryVarAnnotation' => [
                '<?php
                    function foo(array $arr) : void {
                        /** @var int $key */
                        foreach ($arr as $key => $_) {
                            echo $key;
                        }
                    }'
            ],
            'continuingEducation' => [
                '<?php
                    function breakUpPathIntoParts(): void {
                        $b = false;

                        while (rand(0, 1)) {
                            if ($b) {
                                if (rand(0, 1)) {
                                    $b = 0;
                                }

                                echo "hello";

                                continue;
                            }

                            $b = true;
                        }
                    }'
            ],
            'usedInBinaryOp' => [
                '<?php
                    function foo(int $a, int $b) : int {
                        $a |= $b;
                        return $a;
                    }'
            ],
            'reassignedInFinally' => [
                '<?php
                    function getRows(int $s) : void {
                        try {}
                        finally {
                            $s = $s + 3;
                        }

                        echo $s;
                    }'
            ],
            'divAssignOp' => [
                '<?php
                    function hslToRgb(float $hue): float {
                        $hue /= 360;

                        return $hue;
                    }'
            ],
            'concatAssignOp' => [
                '<?php
                    function hslToRgb(string $hue): string {
                        $hue .= "hello";

                        return $hue;
                    }'
            ],
            'possiblyUndefinedVariableUsed' => [
                '<?php
                    function foo(string $a): void {
                        if ($a === "a") {
                            $hue = "hello";
                        } elseif ($a === "b") {
                            $hue = "goodbye";
                        }

                        /** @psalm-suppress PossiblyUndefinedVariable */
                        echo $hue;
                    }'
            ],
            'possiblyUndefinedVariableUsedInUnknownMethod' => [
                '<?php
                    function foo(string $a, object $b): void {
                        if ($a === "a") {
                            $hue = "hello";
                        } elseif ($a === "b") {
                            $hue = "goodbye";
                        }

                        /**
                         * @psalm-suppress PossiblyUndefinedVariable
                         * @psalm-suppress MixedMethodCall
                         */
                        $b->foo($hue);
                    }'
            ],
            'usedAsArrayKey' => [
                '<?php
                    function hslToRgb(string $hue, string $lightness): array {
                        $arr = [$hue => $lightness];
                        return $arr;
                    }'
            ],
            'assignToGlobalVar' => [
                '<?php
                    /** @psalm-suppress MixedAssignment */
                    function foo(array $args) : void {
                        foreach ($args as $key => $value) {
                            $_GET[$key] = $value;
                        }
                    }'
            ],
            'assignToArrayTwice' => [
                '<?php
                    function foo(string $c): void {
                        $arr = [$c];
                        $arr[] = 1;

                        foreach ($arr as $e) {
                            echo $e;
                        }
                    }'
            ],
            'classPropertyThing' => [
                '<?php
                    function foo(): string {
                        $notice  = "i";
                        $notice .= "j";
                        $notice .= "k";
                        $notice .= "l";
                        $notice .= "m";
                        $notice .= "n";
                        $notice .= "o";
                        $notice .= "p";
                        $notice .= "q";
                        $notice .= "r";
                        $notice .= "s";

                        return $notice;
                    }'
            ],
            'usedInIsset' => [
                '<?php
                    function foo(int $i): void {
                        if ($i === 0) {
                            $j = "hello";
                        } elseif ($i === 1) {
                            $j = "goodbye";
                        }

                        if (isset($j)) {
                            echo $j;
                        }
                    }'
            ],
            'byRefNestedArrayParam' => [
                '<?php
                    function foo(array &$arr): void {
                        $b = 5;
                        $arr[0] = $b;
                    }'
            ],
            'byRefNestedArrayInForeach' => [
                '<?php
                    function foo(array $arr): array {
                        /**
                         * @psalm-suppress MixedAssignment
                         * @psalm-suppress MixedArrayAssignment
                         */
                        foreach ($arr as &$element) {
                            $b = 5;
                            $element[0] = $b;
                        }

                        return $arr;
                    }'
            ],
            'instantArrayAssignment' => [
                '<?php
                    function foo(string $b) : array {
                        /** @psalm-suppress PossiblyUndefinedVariable */
                        $arr["foo"] = $b;

                        return $arr;
                    }',
            ],
            'explodeSource' => [
                '<?php
                    $start = microtime();
                    $start = explode(" ", $start);
                    /**
                     * @psalm-suppress InvalidOperand
                     */
                    $start = $start[1] + $start[0];
                    echo $start;'
            ],
            'csvByRefForeach' => [
                '<?php
                    function foo(string $value) : array {
                        $arr = str_getcsv($value);

                        foreach ($arr as &$element) {
                            $element = $element ?: "foo";
                        }

                        return $arr;
                    }'
            ],
            'memoryFree' => [
                '<?php
                    function verifyLoad(string $free) : void {
                        $free = explode("\n", $free);

                        $parts_mem = preg_split("/\s+/", $free[1]);

                        $free_mem = $parts_mem[3];
                        $total_mem = $parts_mem[1];

                        /** @psalm-suppress InvalidOperand */
                        $used_mem  = ($total_mem - $free_mem) / $total_mem;

                        echo $used_mem;
                    }'
            ],
            'returnNotBool' => [
                '<?php
                    function verifyLoad(bool $b) : bool {
                        $c = !$b;
                        return $c;
                    }'
            ],
            'sourcemaps' => [
                '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @param iterable<mixed, int> $keys
                     */
                    function foo(iterable $keys, int $colno) : void {
                        $i = 0;
                        $key = 0;
                        $index = 0;

                        foreach ($keys as $index => $key) {
                            if ($key === $colno) {
                                $i = $index;
                                break;
                            } elseif ($key > $colno) {
                                $i = $index;
                                break;
                            }
                        }

                        echo $i;
                        echo $index;
                        echo $key;
                    }'
            ],
            'whileLoopVarUpdatedInWhileLoop' => [
                '<?php
                    /** @param non-empty-list<int> $arr */
                    function foo(array $arr) : void {
                        while ($a = array_pop($arr)) {
                            if ($a === 4) {
                                $arr = array_merge($arr, ["a", "b", "c"]);
                                continue;
                            }

                            echo "here";
                        }
                    }'
            ],
            'usedThroughParamByRef' => [
                '<?php
                    $arr = [];

                    $populator = function(array &$arr): void {
                        $arr[] = 5;
                    };

                    $populator($arr);

                    print_r($arr);'
            ],
            'maybeUndefinedCheckedWithEmpty' => [
                '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        if (empty($maybe_undefined)) {
                            $maybe_undefined = [0];
                        }

                        print_r($maybe_undefined);
                    }'
            ],
            'maybeUndefinedCheckedWithEmptyOrRand' => [
                '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        if (empty($maybe_undefined) || rand(0, 1)) {
                            $maybe_undefined = [0];
                        }

                        print_r($maybe_undefined);
                    }'
            ],
            'maybeUndefinedCheckedWithNotIsset' => [
                '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        if (!isset($maybe_undefined)) {
                            $maybe_undefined = [0];
                        }

                        print_r($maybe_undefined);
                    }'
            ],
            'maybeUndefinedCheckedWithImplicitIsset' => [
                '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        /** @psalm-suppress MixedAssignment */
                        $maybe_undefined = $maybe_undefined ?? [0];

                        print_r($maybe_undefined);
                    }'
            ],
            'usedInGlobalAfterAssignOp' => [
                '<?php
                    $total = 0;
                    $foo = &$total;

                    $total = 5;

                    echo $foo;'
            ],
            'takesByRefThing' => [
                '<?php
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $c = 5;
                        }

                        takesByRef($c);
                        echo $c;
                    }

                    /**
                     * @param-out int $c
                     */
                    function takesByRef(?int &$c) : void {
                        $c = 7;
                    }'
            ],
            'clips' => [
                '<?php declare(strict_types=1);
                    function foo(array $clips) : void {
                        /** @psalm-suppress MixedAssignment */
                        foreach ($clips as &$clip) {
                            /** @psalm-suppress MixedArgument */
                            if (!empty($clip)) {
                                $legs = explode("/", $clip);
                                $clip_id = $clip = $legs[1];

                                if ((is_numeric($clip_id) || $clip = (new \Exception($clip_id)))) {}

                                print_r($clips);
                            }
                        }
                    }'
            ],
            'validator' => [
                '<?php
                    /**
                     * @param bool $b
                     */
                    function validate($b, string $source) : void {
                        /**
                         * @psalm-suppress DocblockTypeContradiction
                         * @psalm-suppress MixedAssignment
                         */
                        if (!is_bool($b)) {
                            $source = $b;
                        }

                        print_r($source);
                    }'
            ],
            'implicitSpread' => [
                '<?php
                    function validate(bool $b, bool $c) : void {
                        $d = [$b, $c];
                        print_r(...$d);
                    }'
            ],
            'funcGetArgs' => [
                '<?php
                    function validate(bool $b, bool $c) : void {
                        /** @psalm-suppress MixedArgument */
                        print_r(...func_get_args());
                    }'
            ],
            'nullCoalesce' => [
                '<?php
                    function foo (?bool $b, int $c): void {
                        $b ??= $c;

                        echo $b;
                    }'
            ],
            'arrowFunctionImplicitlyUsedVar' => [
                '<?php
                    function test(Exception $e): callable {
                        return fn() => $e->getMessage();
                    }'
            ],
            'useImmutableGetIteratorInForeach' => [
                '<?php
                    /**
                     * @psalm-immutable
                     */
                    class A implements IteratorAggregate
                    {
                        /**
                         * @return Iterator<int>
                         */
                        public function getIterator() {
                            yield from [1, 2, 3];
                        }
                    }

                    $a = new A();

                    foreach ($a as $v) {
                        echo $v;
                    }'
            ],
        ];
    }

    /**
     * @return array<string,array{string,error_message:string}>
     */
    public function providerInvalidCodeParse(): array
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
            'arrowFunctionUnusedVariable' => [
                '<?php
                    function f(callable $c): void {
                        $c(22);
                    }

                    f(
                        fn(int $p)
                            =>
                            ++$p
                    );',
                'error_message' => 'UnusedVariable',
            ],
            'arrowFunctionUnusedParam' => [
                '<?php
                    function f(callable $c): void {
                        $c(22);
                    }

                    f(
                        fn(int $p)
                            =>
                            0
                    );',
                'error_message' => 'UnusedClosureParam',
            ],
            'unusedFunctionParamWithDefault' => [
                '<?php
                    function foo(bool $b = false) : void {}',
                'error_message' => 'UnusedParam',
            ],
            'arrayMapClosureWithParamTypeNoUse' => [
                '<?php
                    $a = [1, 2, 3];

                    $b = array_map(
                        function(int $i) {
                            return rand(0, 5);
                        },
                        $a
                    );

                    foreach ($b as $c) {
                        echo $c;
                    }',
                'error_message' => 'UnusedClosureParam',
            ],
            'noUseOfInstantArrayAssignment' => [
                '<?php
                    function foo() : void {
                        /** @psalm-suppress PossiblyUndefinedVariable */
                        $arr["foo"] = 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'expectsNonNullAndPassedPossiblyNull' => [
                '<?php
                    /**
                     * @param mixed|null $mixed_or_null
                     */
                    function foo($mixed_or_null): Exception {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        return new Exception($mixed_or_null);
                    }',
                'error_message' => 'PossiblyNullArgument'
            ],
        ];
    }
}
