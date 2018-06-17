<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class UnusedVariableTest extends TestCase
{
    /** @var \Psalm\Checker\ProjectChecker */
    protected $project_checker;

    /**
     * @return void
     */
    public function setUp()
    {
        FileChecker::clearCache();

        $this->file_provider = new Provider\FakeFileProvider();

        $this->project_checker = new \Psalm\Checker\ProjectChecker(
            new TestConfig(),
            $this->file_provider,
            new Provider\FakeParserCacheProvider(),
            new \Psalm\Provider\NoCache\NoFileStorageCacheProvider(),
            new \Psalm\Provider\NoCache\NoClassLikeStorageCacheProvider()
        );

        $this->project_checker->getCodebase()->reportUnusedCode();
    }

    /**
     * @dataProvider providerFileCheckerValidCodeParse
     *
     * @param string $code
     * @param array<string> $error_levels
     *
     * @return void
     */
    public function testValidCode($code, array $error_levels = [])
    {
        $test_name = $this->getName();
        if (strpos($test_name, 'PHP7-') !== false) {
            if (version_compare(PHP_VERSION, '7.0.0dev', '<')) {
                $this->markTestSkipped('Test case requires PHP 7.');

                return;
            }
        } elseif (strpos($test_name, 'SKIPPED-') !== false) {
            $this->markTestSkipped('Skipped due to a bug.');
        }

        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            $code
        );

        foreach ($error_levels as $error_level) {
            $this->project_checker->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $context = new Context();
        $context->collect_references = true;

        $this->analyzeFile($file_path, $context);

        $this->project_checker->checkClassReferences();
    }

    /**
     * @dataProvider providerFileCheckerInvalidCodeParse
     *
     * @param string $code
     * @param string $error_message
     * @param array<string> $error_levels
     *
     * @return void
     */
    public function testInvalidCode($code, $error_message, $error_levels = [])
    {
        if (strpos($this->getName(), 'SKIPPED-') !== false) {
            $this->markTestSkipped();
        }

        $this->expectException('\Psalm\Exception\CodeException');
        $this->expectExceptionMessageRegexp('/\b' . preg_quote($error_message, '/') . '\b/');

        $file_path = self::$src_dir_path . 'somefile.php';

        foreach ($error_levels as $error_level) {
            $this->project_checker->config->setCustomErrorLevel($error_level, Config::REPORT_SUPPRESS);
        }

        $this->addFile(
            $file_path,
            $code
        );

        $context = new Context();
        $context->collect_references = true;

        $this->analyzeFile($file_path, $context);

        $this->project_checker->checkClassReferences();
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
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

                    if ($a) {}

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
            'switchVarConditionalAssignment' => [
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

                    if ($a) {}'
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
                    $a = false;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = true;
                            break;
                        }

                        $a = false;
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
            'loopAssignmentAfterReferenceWithContinueInSwitch' => [
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
                    echo $b;'
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
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'simpleUnusedVariable' => [
                '<?php
                    $a = 5;
                    $b = [];
                    echo $a;',
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

                    if ($a) {}

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
            'loopSetIfNullWithBreakWithoutReference' => [
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
                        if ($a === null) {
                            $a = 4;
                            continue;
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
        ];
    }
}
