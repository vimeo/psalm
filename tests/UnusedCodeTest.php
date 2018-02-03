<?php
namespace Psalm\Tests;

use Psalm\Checker\FileChecker;
use Psalm\Config;
use Psalm\Context;

class UnusedCodeTest extends TestCase
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
            new Provider\FakeParserCacheProvider()
        );

        $this->project_checker->getCodebase()->collectReferences();
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
        $this->project_checker->getCodebase()->classlikes->checkClassReferences();
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
        $this->project_checker->getCodebase()->classlikes->checkClassReferences();
    }

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'arrayOffset' => [
                '<?php
                    /** @return void */
                    function foo() {
                        $a = 0;

                        $arr = ["hello"];

                        echo $arr[$a];
                    }',
            ],
            'unset' => [
                '<?php
                    /** @return void */
                    function foo() {
                        $a = 0;

                        $arr = ["hello"];

                        unset($arr[$a]);
                    }',
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
            'ifInFunctionWithReference' => [
                '<?php
                    /** @return string */
                    function foo() {
                        $a = 5;
                        if (rand(0, 1)) {
                            $b = "hello";
                        } else {
                            $b = "goodbye";
                        }
                        return $a . $b;
                    }',
            ],
            'byrefInForeachLoop' => [
                '<?php
                    function foo(): void {
                        $a = [1, 2, 3];
                        foreach ($a as &$b) {
                            $b = $b + 1;
                        }
                    }',
            ],
            'definedInSecondBranchOfCondition' => [
                '<?php
                    function foo(): void {
                        if (rand(0, 1) && $a = rand(0, 1)) {
                            echo $a;
                        }
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
            'magicCall' => [
                '<?php
                    class A {
                        /** @var string */
                        private $value = "default";

                        /** @param string[] $args */
                        public function __call(string $name, array $args) {
                            if (count($args) == 1) {
                                $this->modify($name, $args[0]);
                            }
                        }

                        private function modify(string $name, string $value): void {
                            call_user_func(array($this, "modify_" . $name), $value);
                        }

                        public function modifyFoo(string $value): void {
                            $this->value = $value;
                        }

                        public function getFoo() : string {
                            return $this->value;
                        }
                    }

                    $m = new A();
                    $m->foo("value");
                    $m->modifyFoo("value2");
                    echo $m->getFoo();',
            ],
            'usedTraitMethod' => [
                '<?php
                    class A {
                        public function foo(): void {
                            echo "parent method";
                        }
                    }

                    trait T {
                        public function foo(): void {
                            echo "trait method";
                        }
                    }

                    class B extends A {
                        use T;
                    }

                    (new A)->foo();
                    (new B)->foo();',
            ],
            'usedInterfaceMethod' => [
                '<?php
                    interface I {
                        public function foo(): void;
                    }

                    class A implements I {
                        public function foo(): void {}
                    }

                    (new A)->foo();',
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
                    function foo(): void {
                        $a = false;

                        foreach ([1, 2, 3] as $b) {
                            $a = true;
                            echo $b;
                        }

                        echo $a;
                    }',
            ],
            'doWhileReassigned' => [
                '<?php
                    function foo(): void {
                        $a = 5;

                        do {
                            echo $a;
                            $a = $a - rand(-3, 3);
                        } while ($a > 3);
                    }',
            ],
            'whileTypeChangedInIfAndContinueWithReference' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                                continue;
                            }

                            $a = false;
                        }

                        echo $a;
                    }',
            ],
            'whileReassignedInIfAndContinueWithReferenceAfter' => [
                '<?php
                    function foo(): void {
                        $a = 5;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = 7;
                                continue;
                            }

                            $a = 3;
                        }

                        echo $a;
                    }',
            ],
            'whileReassignedInIfAndContinueWithReferenceBeforeAndAfter' => [
                '<?php
                    function foo(): void {
                        $a = 5;

                        if ($a) {}

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = 7;
                                continue;
                            }

                            $a = 3;
                        }

                        echo $a;
                    }',
            ],
            'whileReassigned' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        while(rand(0, 1)) {
                            $a = true;
                        }

                        echo $a;
                    }',
            ],
            'ifVarReassignedInBranch' => [
                '<?php
                    function foo(): void {
                        $a = true;

                        if (rand(0, 1)) {
                            $a = false;
                        }

                        if ($a) {
                            echo "cool";
                        }
                    }',
            ],
            'elseVarReassignedInBranchAndReference' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        if (rand(0, 1)) {
                            // do nothing
                        } else {
                            $a = true;
                            //echo $a;
                        }

                        if ($a) {
                            echo "cool";
                        }
                    }',
            ],
            'switchVarReassignedInBranch' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        switch (rand(0, 2)) {
                            case 0:
                                $a = true;
                        }

                        if ($a) {
                            echo "cool";
                        }
                    }',
            ],
            'switchVarReassignedInBranchWithDefault' => [
                '<?php
                    function foo(): void {
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
                        }
                    }',
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
            'ifInReturnBlock' => [
                '<?php
                    function foo(): void {
                        $i = false;

                        foreach ([1, 2, 3] as $a) {
                            if (rand(0, 1)) {
                                $i = true;
                            }

                            echo $a;
                        }

                        if ($i) {}
                    }',
            ],
            'unknownMethodCallWithVar' => [
                '<?php
                    /** @psalm-suppress MixedMethodCall */
                    function passesByRef(object $a): void {
                        $a->passedByRef($b);
                    }',
            ],
            'constructorIsUsed' => [
                '<?php
                    class A {
                        public function __construct() {
                            $this->foo();
                        }
                        private function foo() : void {}
                    }
                    $a = new A();
                    echo (bool) $a;',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerFileCheckerInvalidCodeParse()
    {
        return [
            'function' => [
                '<?php
                    /** @return int */
                    function foo() {
                        $a = 5;
                        $b = [];
                        return $a;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'ifInFunctionWithoutReference' => [
                '<?php
                    /** @return int */
                    function foo() {
                        $a = 5;
                        if (rand(0, 1)) {
                            $b = "hello";
                        } else {
                            $b = "goodbye";
                        }
                        return $a;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varInNestedAssignmentWithoutReference' => [
                '<?php
                    function foo(): void {
                        if (rand(0, 1)) {
                            $a = "foo";
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varInSecondNestedAssignmentWithoutReference' => [
                '<?php
                    function foo(): void {
                        if (rand(0, 1)) {
                            $a = "foo";
                            echo $a;
                        }

                        if (rand(0, 1)) {
                            $a = "foo";
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varReassignedInBothBranchesOfIf' => [
                '<?php
                    function foo(): void {
                        $a = "foo";

                        if (rand(0, 1)) {
                            $a = "bar";
                        } else {
                            $a = "bat";
                        }

                        echo $a;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varReassignedInNestedBranchesOfIf' => [
                '<?php
                    function foo(): void {
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

                        echo $a;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'ifVarReassignedInBranch' => [
                '<?php
                    function foo(): void {
                        $a = true;

                        if (rand(0, 1)) {
                            $a = false;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'elseVarReassignedInBranchAndNoReference' => [
                '<?php
                    function foo(): void {
                        $a = true;

                        if (rand(0, 1)) {
                            // do nothing
                        } else {
                            $a = false;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarReassignedInBranch' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        switch (rand(0, 2)) {
                            case 0:
                                $a = true;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarReassignedInBranchWithDefault' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        switch (rand(0, 2)) {
                            case 0:
                                $a = true;
                                break;

                            default:
                                $a = false;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedListVar' => [
                '<?php
                    function foo(): void {
                        list($a, $b) = explode(" ", "hello world");
                        echo $a;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedPreForVar' => [
                '<?php
                    function foo(): void {
                        $i = 0;

                        for ($i = 0; $i < 10; $i++) {
                            echo $i;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedIfInReturnBlock' => [
                '<?php
                    function foo(): void {
                        $i = rand(0, 1);

                        foreach ([1, 2, 3] as $a) {
                            if ($a % 2) {
                                $i = 7;
                                return;
                            }
                        }

                        if ($i) {}
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedIfVarInBranch' => [
                '<?php
                    function foo(): void {
                        if (rand(0, 1)) {

                        } elseif (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = "foo";
                            } else {
                                $a = "bar";
                                echo $a;
                            }
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
            'whileTypeChangedInIfWithoutReference' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                            }
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'whileTypeChangedInIfAndContinueWithoutReference' => [
                '<?php
                    function foo(): void {
                        $a = false;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                                continue;
                            }

                            $a = false;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'whileReassignedInIfAndContinueWithoutReferenceAfter' => [
                '<?php
                    function foo(): void {
                        $a = 5;

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = 7;
                                continue;
                            }

                            $a = 3;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'whileReassignedInIfAndContinueWithoutReference' => [
                '<?php
                    function foo(): void {
                        $a = 3;

                        if ($a) {}

                        while (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = 5;
                                continue;
                            }

                            $a = 3;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedClass' => [
                '<?php
                    class A { }',
                'error_message' => 'UnusedClass',
            ],
            'publicUnusedMethod' => [
                '<?php
                    class A {
                        /** @return void */
                        public function foo() {}
                    }

                    new A();',
                'error_message' => 'PossiblyUnusedMethod',
            ],
            'possiblyUnusedParam' => [
                '<?php
                    class A {
                        /** @return void */
                        public function foo(int $i) {}
                    }

                    (new A)->foo(4);',
                'error_message' => 'PossiblyUnusedParam',
            ],
            'unusedParam' => [
                '<?php
                    function foo(int $i) {}

                    foo(4);',
                'error_message' => 'UnusedParam',
            ],
            'possiblyUnusedProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        public $foo = "hello";
                    }

                    $a = new A();',
                'error_message' => 'PossiblyUnusedProperty',
                'error_levels' => ['UnusedVariable'],
            ],
            'unusedProperty' => [
                '<?php
                    class A {
                        /** @var string */
                        private $foo = "hello";
                    }

                    $a = new A();',
                'error_message' => 'UnusedProperty',
                'error_levels' => ['UnusedVariable'],
            ],
            'privateUnusedMethod' => [
                '<?php
                    class A {
                        /** @return void */
                        private function foo() {}
                    }

                    new A();',
                'error_message' => 'UnusedMethod',
            ],
            'unevaluatedCode' => [
                '<?php
                    function foo(): void {
                        return;
                        $a = "foo";
                    }',
                'error_message' => 'UnevaluatedCode',
            ],
        ];
    }
}
