<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class UnusedVariableTest extends TestCase
{
    use ValidCodeAnalysisTestTrait;
    use InvalidCodeAnalysisTestTrait;

    public function setUp(): void
    {
        parent::setUp();
        $this->project_analyzer->getCodebase()->reportUnusedVariables();
    }

    public function providerValidCodeParse(): array
    {
        return [
            'arrayOffset' => [
                'code' => '<?php
                    $a = 0;

                    $arr = ["hello"];

                    echo $arr[$a];',
            ],
            'unset' => [
                'code' => '<?php
                    $a = 0;

                    $arr = ["hello"];

                    unset($arr[$a]);',
            ],
            'eval' => [
                'code' => '<?php
                    if (rand()) {
                        $v = "";
                        eval($v);
                    }',
            ],
            'usedVariables' => [
                'code' => '<?php
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
                'assertions' => [],
                'ignored_issues' => [
                    'PossiblyUndefinedVariable',
                    'MixedArrayAccess',
                    'MixedOperand',
                    'MixedAssignment',
                    'InvalidStringClass',
                ],
            ],
            'varDefinedInIfWithReference' => [
                'code' => '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $b = "hello";
                    } else {
                        $b = "goodbye";
                    }
                    echo $a . $b;',
            ],
            'varRedefinedInIfWithReference' => [
                'code' => '<?php
                    $a = (string) 5;

                    if (rand(0, 1)) {
                        $a = (string) 6;
                    }

                    echo $a;',
            ],
            'byrefInForeachLoopWithReference' => [
                'code' => '<?php
                    $a = [1, 2, 3];
                    foreach ($a as &$b) {
                        $b = $b + 1;
                    }
                    echo $a[0];',
            ],
            'foreachVarSetInValue' => [
                'code' => '<?php
                    /** @param string[] $arr */
                    function foo(array $arr) : void {
                        $a = null;
                        foreach ($arr as $a) { }
                        if ($a !== null) {}
                    }',
            ],
            'definedInSecondBranchOfCondition' => [
                'code' => '<?php
                    if (rand(0, 1) && $a = rand(0, 1)) {
                        echo $a;
                    }',
            ],
            'booleanOr' => [
                'code' => '<?php
                    function foo(int $a, int $b): bool {
                        return $a || $b;
                    }',
            ],
            'paramUsedInIf' => [
                'code' => '<?php
                    function foo(string $a): void {
                        if (rand(0, 1)) {
                            echo $a;
                        }
                    }',
            ],
            'dummyByRefVar' => [
                'code' => '<?php
                    function foo(string &$a = null, string $b = null): void {
                        if ($a !== null) {
                            echo $a;
                        }
                        if ($b !== null) {
                            echo $b;
                        }
                    }

                    function bar(): void {
                        foo($dummy_byref_var, "hello");
                    }

                    bar();',
            ],
            'foreachReassigned' => [
                'code' => '<?php
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
                'code' => '<?php
                    $a = 5;

                    do {
                        echo $a;
                        $a = $a - rand(-3, 3);
                    } while ($a > 3);',
            ],
            'loopTypeChangedInIfAndContinueWithReference' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = false;

                    while(rand(0, 1)) {
                        $a = true;
                    }

                    echo $a;',
            ],
            'ifVarReassignedInBranchWithUse' => [
                'code' => '<?php
                    $a = true;

                    if (rand(0, 1)) {
                        $a = false;
                    }

                    if ($a) {
                        echo "cool";
                    }',
            ],
            'elseVarReassignedInBranchAndReference' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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

                        if ($s !== null) {}
                    }',
            ],
            'throwWithMessageCallAndAssignmentInCatchAndReference' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /** @psalm-suppress MixedMethodCall */
                    function passesByRef(object $a): void {
                        /** @psalm-suppress PossiblyUndefinedVariable */
                        $a->passedByRef($b);
                    }',
            ],
            'usedMethodCallVariable' => [
                'code' => '<?php
                    function reindex(array $arr, string $methodName): array {
                        $ret = [];

                        foreach ($arr as $element) {
                            $ret[$element->$methodName()] = true;
                        }

                        return $ret;
                    }',
                'assertions' => [],
                'ignored_issues' => [
                    'MixedAssignment',
                    'MixedMethodCall',
                    'MixedArrayOffset',
                ],
            ],
            'globalVariableUsage' => [
                'code' => '<?php
                    $a = "hello";
                    function example() : void {
                        global $a;
                        echo $a;
                        $a = "hello";
                    }
                    example();',
            ],
            'staticVar' => [
                'code' => '<?php
                    function use_static() : void {
                        static $token;
                        if (!$token) {
                            $token = rand(1, 10);
                        }
                        echo "token is $token\n";
                    }',
            ],
            'staticVarUsedLater' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;
                        $a = 1;
                    }',
            ],
            'loopAssignmentAfterReferenceWithContinue' => [
                'code' => '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;
                        $a = 1;
                        continue;
                    }',
            ],
            'loopAssignmentAfterReferenceWithConditionalAssignmentWithContinue' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = "a";
                    $b = "b";

                    if (rand(0, 1)) {
                        list($a, $b) = explode(".", "c.d");
                    }

                    echo $a;
                    echo $b;',
            ],
            'arrayVarAssignmentInFunctionAndReturned' => [
                'code' => '<?php
                    /**
                     * @param array{string} $arr
                     */
                    function far(array $arr): string {
                        [$a] = $arr;

                        return $a;
                    }',
            ],
            'arrayUnpackInForeach' => [
                'code' => '<?php
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
            'arraySubAppend' => [
                'code' => '<?php
                    $rules = [0, 1, 2];
                    $report = ["runs" => []];
                    foreach ($rules as $rule) {
                        $report["runs"][] = $rule;
                    }
                    echo(count($report));',
            ],
            'arrayAssignmentInFunctionCoerced' => [
                'code' => '<?php
                    class A {
                        public int $a = 0;
                        public int $b = 1;

                        function setPhpVersion(string $version): void {
                            [$a, $b] = explode(".", $version);

                            $this->a = (int) $a;
                            $this->b = (int) $b;
                        }
                    }
                    ',
            ],
            'varCheckAfterNestedAssignmentAndBreak' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = 5;

                    while (rand(0, 1)) {
                        echo($a += 1);
                    }',
            ],
            'echoVarWithIncrement' => [
                'code' => '<?php
                    function foo(int $i) : void {
                        echo $i;
                    }

                    $a = 5;

                    while (rand(0, 1)) {
                        foo(++$a);
                    }',
            ],
            'afterMethodExistsCheck' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $i = 5;
                    do {
                        echo "hello";
                    } while (--$i > 0);
                    echo $i;',
            ],
            'callableReferencesItself' => [
                'code' => '<?php
                    /** @psalm-suppress UnusedParam */
                    function foo(callable $c) : void {}
                    $listener = function () use (&$listener) : void {
                        foo($listener);
                    };
                    foo($listener);',
            ],
            'newVariableConstructor' => [
                'code' => '<?php
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
                'code' => '<?php
                    $i = 0;
                    $a = function () use (&$i) : void {
                        $i = 1;
                    };
                    $a();
                    echo $i;',
            ],
            'regularVariableClosureUseInAddition' => [
                'code' => '<?php
                    $i = 0;
                    $a = function () use ($i) : int {
                        return $i + 1;
                    };
                    $a();',
            ],
            'superGlobalInFunction' => [
                'code' => '<?php
                    function example1() : void {
                        $_SESSION = [];
                    }
                    function example2() : int {
                        return (int) $_SESSION["str"];
                    }',
            ],
            'usedInArray' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo(int $counter) : void {
                        foreach ([1, 2, 3] as $_) {
                            echo ($counter = $counter + 1);
                            echo rand(0, 1) ? 1 : 0;
                        }
                    }',
            ],
            'useParamInsideIfLoop' => [
                'code' => '<?php
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
                'code' => '<?php
                    $foo = false;

                    try {
                        if (rand(0, 1)) {
                            throw new \Exception("bad");
                        }

                        $foo = rand(0, 1);

                        if ($foo) {}
                    } catch (Exception $e) {}

                    if ($foo !== false && $foo !== 0) {}',
            ],
            'useTryAssignedVariableInsideFinally' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(array $returned) : array {
                        $ancillary = &$returned;
                        $ancillary["foo"] = 5;
                        return $returned;
                    }',
            ],
            'usedAsMethodName' => [
                'code' => '<?php
                    class A {
                        public static function foo() : void {}
                    }

                    function foo() : void {
                        $method = "foo";
                        A::$method();
                    }',
            ],
            'usedAsClassConstFetch' => [
                'code' => '<?php
                    class A {
                        const bool something = false;

                        public function foo() : void {
                            $var = "something";

                            if (rand(0, 1)) {
                                static::{$var};
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'usedAsEnumFetch' => [
                'code' => '<?php
                    enum E {
                        case C;
                    }

                    class A {
                        public function foo() : void {
                            $var = "C";

                            if (rand(0, 1)) {
                                E::{$var};
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => [],
                'php_version' => '8.3',
            ],
            'usedAsStaticPropertyAssign' => [
                'code' => '<?php
                    class A {
                        private static bool $something = false;

                        public function foo() : void {
                            $var = "something";

                            if (rand(0, 1)) {
                                static::${$var} = true;
                            }
                        }
                    }',
            ],
            'usedAsStaticPropertyFetch' => [
                'code' => '<?php
                    class A {
                        private static bool $something = false;

                        public function foo() : void {
                            $var = "something";

                            if (rand(0, 1)) {
                                static::${$var};
                            }
                        }
                    }',
            ],
            'setInLoopThatsAlwaysEntered' => [
                'code' => '<?php
                    /**
                     * @param non-empty-array<int> $a
                     */
                    function getLastNum(array $a): int {
                        foreach ($a as $num) {
                            $last = $num;
                        }
                        return $last;
                    }',
            ],
            'usedStrtolowerInArray' => [
                'code' => '<?php
                    /**
                     * @param array<string, int> $row
                     */
                    function foo(array $row, string $s) : array {
                        $row["a" . strtolower($s)] += 1;
                        return $row;
                    }',
            ],
            'pureWithReflectionMethodSetValue' => [
                'code' => '<?php
                    function foo(object $mock) : void {
                        $m = new \ReflectionProperty($mock, "bar");
                        $m->setValue([get_class($mock) => "hello"]);
                    }',
            ],
            'defineBeforeAssignmentInConditional' => [
                'code' => '<?php
                    $i = null;

                    if (rand(0, 1) || ($i = rand(0, 1))) {
                        echo $i;
                    }',
            ],
            'definedInFirstAssignmentInConditional' => [
                'code' => '<?php
                    if (($b = rand(0, 1)) || rand(0, 1)) {
                        echo $b;
                    }',
            ],
            'noUnusedVariableWhenUndefinedMethod' => [
                'code' => '<?php
                    class A {}

                    function foo(A $a) : void {
                        $i = 0;

                        /** @psalm-suppress UndefinedMethod */
                        $a->bar($i);
                    }',
            ],
            'noUnusedVariableAfterRedeclaredInCatch' => [
                'code' => '<?php
                    $path = "";

                    echo $path;

                    try {
                        // do nothing
                    } catch (\Exception $exception) {
                        $path = "hello";
                    }

                    echo $path;',
            ],
            'assignedInElseif' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo() : array {
                        return ["hello"];
                    }

                    /** @var string $s */
                    foreach (foo() as $s) {
                        echo $s;
                    }',
            ],
            'doWhileReassignedInConditional' =>  [
                'code' => '<?php
                    $index = 0;

                    do {
                      echo $index;
                    } while (($index = $index +  1) < 10);',
            ],
            'tryCatchInsaneRepro' => [
                'code' => '<?php
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
                    }',
            ],
            'tryCatchInsaneReproNoFirstBoolCheck' => [
                'code' => '<?php
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
                    }',
            ],
            'tryWithWhile' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo(): void {
                        $done = false;

                        while (!$done) {
                            $done = true;
                        }
                    }',
            ],
            'usedInCatchAndTryWithReturnInTry' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'usedVarInCatchAndAfter' => [
                'code' => '<?php
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

                    echo $a;',
            ],
            'unusedForeach' => [
                'code' => '<?php
                    /**
                     * @param array<int, string> $test
                     */
                    function foo(array $test) : void {
                        foreach($test as $key => $_testValue) {
                            echo $key;
                        }
                    }',
            ],
            'usedAfterMixedVariableAssignment' => [
                'code' => '<?php
                    function foo(array $arr): array {
                        $c = "c";
                        /** @psalm-suppress MixedArrayAssignment */
                        $arr["a"]["b"][$c] = 1;
                        return $arr;
                    }',
            ],
            'binaryOpIncrementInElse' => [
                'code' => '<?php
                    function foo(int $i, string $alias) : void {
                        echo $alias ?: $i++;
                        echo $i;
                    }',
            ],
            'binaryOpIncrementInCond' => [
                'code' => '<?php
                    function foo(int $i, string $alias) : void {
                        echo $i++ ?: $alias;
                        echo $i;
                    }',
            ],
            'binaryOpIncrementInIf' => [
                'code' => '<?php
                    function foo(int $i, string $alias) : void {
                        echo rand(0, 1) ? $i++ : $alias;
                        echo $i;
                    }',
            ],
            'usedInNewCall' => [
                'code' => '<?php
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
                'code' => '<?php
                    function keys(): array {
                        return ["foo", "bar"];
                    }

                    /** @var mixed $k */
                    foreach (keys() as $k) {
                        echo gettype($k);
                    }',
            ],
            'byRefVariableAfterAssignmentToArray' => [
                'code' => '<?php
                    $a = [1, 2, 3];
                    $b = &$a[1];
                    $b = 5;
                    print_r($a);',
            ],
            'byRefVariableAfterAssignmentToProperty' => [
                'code' => '<?php
                    class A {
                        public string $value = "";
                        public function writeByRef(string $value): void {
                            $update =& $this->value;
                            $update = $value;
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['UnsupportedPropertyReferenceUsage'],
            ],
            'createdAndUsedInCondition' => [
                'code' => '<?php
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

                    if (!($a = getA()) || $a->foo()) {}',
            ],
            'usedInUndefinedFunction' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedInferredReturnType
                     * @psalm-suppress MixedReturnStatement
                     */
                    function test(): string {
                        $s = "a";
                        /** @psalm-suppress UndefinedFunction */
                        return undefined_function($s);
                    }',
            ],
            'useVariableVariable' => [
                'code' => '<?php
                    $variables = ["a" => "b", "c" => "d"];

                    foreach ($variables as $name => $value) {
                        ${$name} = $value;
                    }',
            ],
            'usedLoopVariable' => [
                'code' => '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        if ($a < 20) {
                            $a = $a + 1;
                            echo "hello";
                            continue;
                        }
                        echo "goodbye";
                        break;
                    }',
            ],
            'usedForVariable' => [
                'code' => '<?php
                    $a = 0;
                    for ($i = 0; $i < 1000; $i++) {
                        if (rand(0, 1)) {
                            $a = $a + 1;
                            continue;
                        }
                        break;
                    }

                    echo $a;',
            ],
            'usedForVariableMinusString' => [
                'code' => '<?php
                    function foo(string $limit) : void {
                        /**
                         * @psalm-suppress InvalidOperand
                         */
                        for ($i = $limit; $i > 0; $i--) {
                            echo $i . "\n";
                        }
                    }',
            ],
            'usedForVariablePlusString' => [
                'code' => '<?php
                    function foo(string $limit) : void {
                        /**
                         * @psalm-suppress InvalidOperand
                         */
                        for ($i = $limit; $i < 50; $i++) {
                            echo $i . "\n";
                        }
                    }',
            ],
            'breakInForeachInsideSwitch' => [
                'code' => '<?php
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
                    }',
            ],
            'passedByRefSimpleUndefinedBefore' => [
                'code' => '<?php
                    takes_ref($a);

                    function takes_ref(?array &$p): void {
                        $p = [0];
                    }',
            ],
            'passedByRefSimpleDefinedBefore' => [
                'code' => '<?php
                    $a = [];
                    takes_ref($a);

                    function takes_ref(?array &$p): void {
                        $p = [0];
                    }',
            ],
            'passedByRefSimpleDefinedBeforeWithExtract' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        while (rand(0, 1)) {
                            /** @psalm-suppress MixedArgument */
                            extract($arr);
                            $a = [];
                            takes_ref($a);
                        }
                    }

                    /**
                     * @param mixed $p
                     * @psalm-suppress UnusedParam
                     */
                    function takes_ref(&$p): void {}',
            ],
            'passedByRefArrayOffset' => [
                'code' => '<?php
                    $a = [
                        "a" => [1],
                        "b" => [2]
                    ];

                    foreach (["a"] as $e){
                        takes_ref($a[$e]);
                    }

                    /** @param array<string|int> $p */
                    function takes_ref(array &$p): void {
                        echo implode(",", $p);
                    }',
            ],
            'doWhileWithBreak' => [
                'code' => '<?php
                    function foo(): void {
                        $f = false;

                        do {
                            if (rand(0,1)) {
                                $f = true;
                                break;
                            }
                        } while (rand(0,1));

                        if ($f) {}
                    }',
            ],
            'usedParamInWhileAddition' => [
                'code' => '<?php
                    function foo(int $index): void {
                        while ($index++ <= 100) {
                            //
                        }
                    }',
            ],
            'usedParamInWhileDirectly' => [
                'code' => '<?php
                    function foo(int $index): void {
                        while (100 >= $index = nextNumber($index)) {
                            // ...
                        }
                    }

                    function nextNumber(int $eee): int {
                        return $eee + 1;
                    }',
            ],
            'usedParamInWhileIndirectly' => [
                'code' => '<?php
                    function foo(int $i): void {
                        $index = $i;
                        while (100 >= $index = nextNumber($index)) {
                            // ...
                        }
                    }

                    function nextNumber(int $i): int {
                        return $i + 1;
                    }',
            ],
            'doArrayIncrement' => [
                'code' => '<?php
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
                    }',
            ],
            'variableUsedIndirectly' => [
                'code' => '<?php
                    $a = 0;

                    while (rand(0,1)){
                        $b = $a + 1;
                        echo $b;
                        $a = $b;
                    }',
            ],
            'arrayMapClosureWithParamType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = [];

                    while (rand(0,1)) {
                        $a[] = 1;
                    }

                    if ($a) {}',
            ],
            'usedArrayRecursiveAddition' => [
                'code' => '<?php
                    $a = [];

                    while (rand(0,1)) {
                        $a[] = $a;
                    }

                    print_r($a);',
            ],
            'usedImmutableProperty' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'loopOverUnknown' => [
                'code' => '<?php
                    /** @psalm-suppress MixedAssignment */
                    function foo(Traversable $t) : void {
                        foreach ($t as $u) {
                            if ($u instanceof stdClass) {}
                        }
                    }',
            ],
            'loopWithRequire' => [
                'code' => '<?php
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
                'code' => '<?php
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
                    }',
            ],
            'necessaryVarAnnotation' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        /** @var int $key */
                        foreach ($arr as $key => $_) {
                            echo $key;
                        }
                    }',
            ],
            'continuingEducation' => [
                'code' => '<?php
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
                    }',
            ],
            'usedInBinaryOp' => [
                'code' => '<?php
                    function foo(int $a, int $b) : int {
                        $a |= $b;
                        return $a;
                    }',
            ],
            'reassignedInFinally' => [
                'code' => '<?php
                    function getRows(int $s) : void {
                        try {}
                        finally {
                            $s = $s + 3;
                        }

                        echo $s;
                    }',
            ],
            'divAssignOp' => [
                'code' => '<?php
                    function hslToRgb(float $hue): float {
                        $hue /= 360;

                        return $hue;
                    }',
            ],
            'concatAssignOp' => [
                'code' => '<?php
                    function hslToRgb(string $hue): string {
                        $hue .= "hello";

                        return $hue;
                    }',
            ],
            'possiblyUndefinedVariableUsed' => [
                'code' => '<?php
                    function foo(string $a): void {
                        if ($a === "a") {
                            $hue = "hello";
                        } elseif ($a === "b") {
                            $hue = "goodbye";
                        }

                        /**
                         * @psalm-suppress PossiblyUndefinedVariable
                         * @psalm-suppress MixedArgument
                         */
                        echo $hue;
                    }',
            ],
            'possiblyUndefinedVariableUsedInUnknownMethod' => [
                'code' => '<?php
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
                    }',
            ],
            'usedAsArrayKey' => [
                'code' => '<?php
                    function hslToRgb(string $hue, string $lightness): array {
                        $arr = [$hue => $lightness];
                        return $arr;
                    }',
            ],
            'assignToGlobalVar' => [
                'code' => '<?php
                    /** @psalm-suppress MixedAssignment */
                    function foo(array $args) : void {
                        foreach ($args as $key => $value) {
                            $_GET[$key] = $value;
                        }
                    }',
            ],
            'assignToArrayTwice' => [
                'code' => '<?php
                    function foo(string $c): void {
                        $arr = [$c];
                        $arr[] = 1;

                        foreach ($arr as $e) {
                            echo $e;
                        }
                    }',
            ],
            'classPropertyThing' => [
                'code' => '<?php
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
                    }',
            ],
            'usedInIsset' => [
                'code' => '<?php
                    function foo(int $i): void {
                        if ($i === 0) {
                            $j = "hello";
                        } elseif ($i === 1) {
                            $j = "goodbye";
                        }

                        if (isset($j)) {
                            /** @psalm-suppress MixedArgument */
                            echo $j;
                        }
                    }',
            ],
            'byRefNestedArrayParam' => [
                'code' => '<?php
                    function foo(array &$arr): void {
                        $b = 5;
                        $arr[0] = $b;
                    }',
            ],
            'byRefDeeplyNestedArrayParam' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<non-empty-list<int>> $arr
                     * @param-out non-empty-list<non-empty-list<int>> $arr
                     */
                    function foo(array &$arr): void {
                        $b = 5;
                        $arr[0][0] = $b;
                    }',
            ],
            'nestedReferencesToByRefParam' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<non-empty-list<int>> $arr
                     * @param-out non-empty-list<non-empty-list<int>> $arr
                     */
                    function foo(array &$arr): void {
                        $a = &$arr[0];
                        $b = &$a[0];
                        $b = 5;
                    }',
            ],
            'byRefNestedArrayInForeach' => [
                'code' => '<?php
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
                    }',
            ],
            'instantArrayAssignment' => [
                'code' => '<?php
                    function foo(string $b) : array {
                        /** @psalm-suppress PossiblyUndefinedVariable */
                        $arr["foo"] = $b;

                        return $arr;
                    }',
            ],
            'explodeSource' => [
                'code' => '<?php
                    $start = microtime();
                    $start = explode(" ", $start);
                    /**
                     * @psalm-suppress InvalidOperand
                     */
                    $start = $start[1] + $start[0];
                    echo $start;',
            ],
            'csvByRefForeach' => [
                'code' => '<?php
                    function foo(string $value) : array {
                        $arr = str_getcsv($value);

                        foreach ($arr as &$element) {
                            $element = $element !== null ?: "foo";
                        }

                        return $arr;
                    }',
            ],
            'memoryFree' => [
                'code' => '<?php
                    function verifyLoad(string $free) : void {
                        $free = explode("\n", $free);

                        $parts_mem = preg_split("/\s+/", $free[1]);

                        $free_mem = $parts_mem[3];
                        $total_mem = $parts_mem[1];

                        /** @psalm-suppress InvalidOperand */
                        $used_mem  = ($total_mem - $free_mem) / $total_mem;

                        echo $used_mem;
                    }',
            ],
            'returnNotBool' => [
                'code' => '<?php
                    function verifyLoad(bool $b) : bool {
                        $c = !$b;
                        return $c;
                    }',
            ],
            'sourcemaps' => [
                'code' => '<?php
                    /**
                     * @psalm-suppress MixedAssignment
                     * @psalm-suppress MixedArgument
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
                    }',
            ],
            'whileLoopVarUpdatedInWhileLoop' => [
                'code' => '<?php
                    /** @param non-empty-list<int> $arr */
                    function foo(array $arr) : void {
                        while ($a = array_pop($arr)) {
                            if ($a === 4) {
                                $arr = array_merge($arr, ["a", "b", "c"]);
                                continue;
                            }

                            echo "here";
                        }
                    }',
            ],
            'usedThroughParamByRef' => [
                'code' => '<?php
                    $arr = [];

                    $populator = function(array &$arr): void {
                        $arr[] = 5;
                    };

                    $populator($arr);

                    print_r($arr);',
            ],
            'maybeUndefinedCheckedWithEmpty' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        if (empty($maybe_undefined)) {
                            $maybe_undefined = [0];
                        }

                        print_r($maybe_undefined);
                    }',
            ],
            'maybeUndefinedCheckedWithEmptyOrRand' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        if (empty($maybe_undefined) || rand(0, 1)) {
                            $maybe_undefined = [0];
                        }

                        print_r($maybe_undefined);
                    }',
            ],
            'maybeUndefinedCheckedWithNotIsset' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        if (!isset($maybe_undefined)) {
                            $maybe_undefined = [0];
                        }

                        print_r($maybe_undefined);
                    }',
            ],
            'maybeUndefinedCheckedWithImplicitIsset' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if (rand(0, 1)) {
                            $maybe_undefined = $arr;
                        }

                        /** @psalm-suppress MixedAssignment */
                        $maybe_undefined = $maybe_undefined ?? [0];

                        print_r($maybe_undefined);
                    }',
            ],
            'usedInGlobalAfterAssignOp' => [
                'code' => '<?php
                    $total = 0;
                    $foo = &$total;

                    $total = 5;

                    echo $foo;',
            ],
            'takesByRefThing' => [
                'code' => '<?php
                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $c = 5;
                        }

                        takesByRef($c);
                        echo $c;
                    }

                    /**
                     * @psalm-param-out int $c
                     */
                    function takesByRef(?int &$c) : void {
                        $c = 7;
                    }',
            ],
            'clips' => [
                'code' => '<?php declare(strict_types=1);
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
                    }',
            ],
            'validator' => [
                'code' => '<?php
                    /**
                     * @param bool $b
                     */
                    function validate($b, string $source) : void {
                        /** @var bool|string $b */
                        if (!is_bool($b)) {
                            $source = $b;
                            $b = false;
                        }

                        /**
                         * test to ensure $b is only type bool and not bool|string anymore
                         * after we set $b = false; inside the condition above
                         * @psalm-suppress TypeDoesNotContainType
                         */
                        if (!is_bool($b)) {
                            echo "this should not happen";
                        }

                        print_r($source);
                    }',
            ],
            'implicitSpread' => [
                'code' => '<?php
                    function validate(bool $b, bool $c) : void {
                        $d = [$b, $c];
                        print_r(...$d);
                    }',
            ],
            'explicitSpread' => [
                'code' => '<?php
                    function f(): array {
                        $s = [1, 2, 3];
                        $b = ["a", "b", "c"];

                        $r = [...$s, ...$b];
                        return $r;
                    }',
            ],
            'funcGetArgs' => [
                'code' => '<?php
                    function validate(bool $b, bool $c) : void {
                        /** @psalm-suppress MixedArgument */
                        print_r(...func_get_args());
                    }',
            ],
            'nullCoalesce' => [
                'code' => '<?php
                    function foo (?bool $b, int $c): void {
                        $b ??= $c;

                        echo $b;
                    }',
            ],
            'arrowFunctionImplicitlyUsedVar' => [
                'code' => '<?php
                    function test(Exception $e): callable {
                        return fn() => $e->getMessage();
                    }',
            ],
            'useImmutableGetIteratorInForeach' => [
                'code' => '<?php
                    /**
                     * @psalm-immutable
                     * @psalm-suppress MissingTemplateParam
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
                    }',
            ],
            'castToBoolAndDouble' => [
                'code' => '<?php
                    function string_to_bool(string $a): bool {
                        $b = (bool)$a;
                        return $b;
                    }

                    function string_to_float(string $a): float {
                        $b = (float)$a;
                        return $b;
                    }',
            ],
            'allowUseByRef' => [
                'code' => '<?php
                    function foo(array $data) : array {
                        $output = [];

                        array_map(
                            function (array $row) use (&$output) {
                                $output = $row;
                            },
                            $data
                        );

                        return $output;
                    }',
            ],
            'allowedUseByRefArrayAssignment' => [
                'code' => '<?php
                    $output_rows = [];

                    $a = function() use (&$output_rows) : void {
                        $output_row = 5;
                        $output_rows[] = $output_row;
                    };
                    $a();

                    print_r($output_rows);',
            ],
            'usedInAssignOpToByRef' => [
                'code' => '<?php
                    function foo(int &$d): void  {
                        $l = 4;
                        $d += $l;
                    }',
            ],
            'mixedArrayAccessMightBeObject' => [
                'code' => '<?php
                    function takesResults(array $arr) : void {
                        /**
                         * @psalm-suppress MixedAssignment
                         */
                        foreach ($arr as $item) {
                            /**
                             * @psalm-suppress MixedArrayAccess
                             * @psalm-suppress MixedArrayAssignment
                             */
                            $item[0] = $item[1];
                        }
                    }',
            ],
            'usedThrow' => [
                'code' => '<?php
                    function f(Exception $e): void {
                        throw $e;
                    }
                ',
            ],
            'usedThrowInReturnedCallable' => [
                'code' => '<?php
                    function createFailingFunction(RuntimeException $exception): Closure
                    {
                        return static function () use ($exception): void {
                            throw $exception;
                        };
                    }
                ',
            ],
            'usedInIntCastInAssignment' => [
                'code' => '<?php
                    /** @return mixed */
                    function f() {
                        $a = random_int(0, 10) >= 5 ? true : false;

                        $b = (int) $a;

                        return $b;
                    }
                ',
            ],
            'promotedPropertiesAreNeverMarkedAsUnusedParams' => [
                'code' => '<?php
                    class Container {
                        private function __construct(
                            public float $value
                        ) {}

                        public static function fromValue(float $value): self {
                            return new self($value);
                        }
                    }',
            ],
            'noUnusedVariableDefinedInBranchOfIf' => [
                'code' => '<?php
                    abstract class Foo {
                        abstract function validate(): bool|string;
                        abstract function save(): bool|string;

                        function bar(): int {
                            if (($result = $this->validate()) && ($result = $this->save())) {
                                return 0;
                            } elseif (is_string($result)) {
                                return 1;
                            } else {
                                return 2;
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['RiskyTruthyFalsyComparison'],
                'php_version' => '8.0',
            ],
            'concatWithUnknownProperty' => [
                'code' => '<?php
                    /** @param array<string> $key */
                    function foo(object $a, string $k) : string {
                        $sortA = "";

                        /** @psalm-suppress MixedOperand */
                        $sortA .= $a->$k;

                        return $sortA;
                    }',
            ],
            'varDocblockVariableIsUsedByRef' => [
                'code' => '<?php
                    /** @param array<string|int> $arr */
                    function foo(array $arr) : string {
                        /** @var string $val */
                        foreach ($arr as &$val) {
                            $val = urlencode($val);
                        }
                        return implode("/", $arr);
                    }',
            ],
            'initVariableInOffset'  => [
                'code' => '<?php
                    $a = [
                        $b = "b" => $b,
                    ];

                    foreach ($a as $key => $value) {
                        echo $key . " " . $value;
                    }',
            ],
            'intAndBitwiseNotOperator' => [
                'code' => '<?php
                    function foo() : int
                    {
                        $bitmask = 0x1;
                        $bytes = 2;
                        $ret = $bytes | ~$bitmask;
                        return $ret;
                    }',
            ],
            'stringAndBitwiseAndOperator' => [
                'code' => '<?php
                    function randomBits() : string
                    {
                        $bitmask = \chr(0xFF >> 1);

                        $randomBytes    = random_bytes(1);
                        $randomBytes[0] = $randomBytes[0] & $bitmask;

                        return $randomBytes;
                    }',
            ],
            'globalChangeValue' => [
                'code' => '<?php
                    function setProxySettingsFromEnv(): void {
                        global $a;

                        $a = false;
                    }',
            ],
            'usedInCatchIsAlwaysUsedInTry' => [
                'code' => '<?php
                    $step = 0;
                    try {
                        $step = 1;
                        $step = 2;
                    } catch (Throwable $_) {
                        echo $step;
                    }
                ',
            ],
            'usedInFinallyIsAlwaysUsedInTry' => [
                'code' => '<?php
                    $step = 0;
                    try {
                        $step = 1;
                        $step = 2;
                    } finally {
                        echo $step;
                    }
                ',
            ],
            'usedInFinallyIsAlwaysUsedInTryWithNestedTry' => [
                'code' => '<?php
                    $step = 0;
                    try {
                        try {
                            $step = 1;
                        } finally {
                        }
                        $step = 2;
                        $step = 3;
                    } finally {
                        echo $step;
                    }
                ',
            ],
            'referenceUseUsesReferencedVariable' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    echo $b;
                ',
            ],
            'referenceAssignmentToNonReferenceCountsAsUse' => [
                'code' => '<?php
                    $b = &$a;
                    $b = 2;
                    echo $a;
                ',
            ],
            'referenceUsedAfterVariableReassignment' => [
                'code' => '<?php
                    $b = &$a;
                    $a = 2;
                    echo $a;
                    $b = 3;
                    echo $a;
                ',
            ],
            'referenceUsedInForeach' => [
                'code' => '<?php
                    foreach ([1, 2, 3] as &$var) {
                        $var += 1;
                    }
                ',
            ],
            'SKIPPED-referenceUsedInDestructuredForeach' => [
                'code' => '<?php
                    foreach ([[1, 2], [3, 4]] as [&$a, $_]) {
                        $a += 1;
                    }
                ',
            ],
            'arrayWithReferenceIsUsed' => [
                'code' => '<?php
                    /** @var non-empty-list<int> */
                    $arr = [1];
                    $arr[1] = &$arr[0];

                    takesArray($arr);

                    function takesArray(array $_arr): void {}
                ',
            ],
            'arrayWithVariableOffsetAssignedToReferenceUsesVariableOffset' => [
                'code' => '<?php
                    /** @var non-empty-list<int> */
                    $arr = [1];
                    $int = 1;
                    $arr[$int] = &$arr[0];

                    takesArray($arr);

                    function takesArray(array $_arr): void {}
                ',
            ],
            'usedPlusInAddition' => [
                'code' => '<?php
                    function takesAnInt(): void {
                        $i = 0;

                        while (rand(0, 1)) {
                            if (($i = $i + 1) > 10) {
                                break;
                            } else {}
                        }
                    }',
            ],
            'usedPlusInUnaryAddition' => [
                'code' => '<?php
                    function takesAnInt(): void {
                        $i = 0;

                        while (rand(0, 1)) {
                            if (++$i > 10) {
                                break;
                            } else {}
                        }
                    }',
            ],
            'referenceInPropertyIsNotUnused' => [
                'code' => '<?php
                    class Foo
                    {
                        /** @var int|null */
                        public $bar = null;

                        public function setBarRef(int $ref): void
                        {
                            $this->bar = &$ref;
                        }
                    }
                ',
            ],
            'requiredClosureArgumentMustNotGetReported' => [
                'code' => '<?php

                /** @param callable(string,int): void $callable */
                function takesCallable(callable $callable): void
                {
                    $callable("foo", 0);
                }

                takesCallable(
                    static function (string $foo, int $bar) {
                        if ($bar === 0) {
                            throw new RuntimeException();
                        }
                    }
                );',
            ],
        ];
    }

    public function providerInvalidCodeParse(): array
    {
        return [
            'simpleUnusedVariable' => [
                'code' => '<?php
                    $a = 5;
                    $b = [];
                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithAdditionOp' => [
                'code' => '<?php
                    $a = 5;
                    $a += 1;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithConditionalAdditionOp' => [
                'code' => '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $a += 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithConditionalAddition' => [
                'code' => '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $a = $a + 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithIncrement' => [
                'code' => '<?php
                    $a = 5;
                    $a++;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedVarWithConditionalIncrement' => [
                'code' => '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $a++;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'ifInBothBranchesWithoutReference' => [
                'code' => '<?php
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
                'code' => '<?php
                    if (rand(0, 1)) {
                        $a = "foo";
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varInSecondNestedAssignmentWithoutReference' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = true;

                    if (rand(0, 1)) {
                        $a = false;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'elseVarReassignedInBranchAndNoReference' => [
                'code' => '<?php
                    $a = true;

                    if (rand(0, 1)) {
                        // do nothing
                    } else {
                        $a = false;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarReassignedInBranch' => [
                'code' => '<?php
                    $a = false;

                    switch (rand(0, 2)) {
                        case 0:
                            $a = true;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'switchVarReassignedInBranchWithDefault' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    list($a, $b) = explode(" ", "hello world");
                    echo $a;',
                'error_message' => 'UnusedVariable',
            ],
            'unusedPreForVar' => [
                'code' => '<?php
                    $i = 0;

                    for ($i = 0; $i < 10; $i++) {
                        echo $i;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'unusedIfInReturnBlock' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = false;

                    while (rand(0, 1)) {
                        if (rand(0, 1)) {
                            $a = true;
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopTypeChangedInIfAndContinueWithoutReference' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                      $a = $a + 5;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'varDefinedInIfWithoutReference' => [
                'code' => '<?php
                    $a = 5;
                    if (rand(0, 1)) {
                        $b = "hello";
                    } else {
                        $b = "goodbye";
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'SKIPPED-byrefInForeachLoopWithoutReference' => [
                'code' => '<?php
                    $a = [1, 2, 3];
                    foreach ($a as &$b) {
                        $b = $b + 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopSetIfNullWithBreakWithoutReference' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    $a = 0;
                    while (rand(0, 1)) {
                        echo $a;
                        $a = 1;
                        break;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'loopAssignmentAfterReferenceWithBreakInIf' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo() : void {
                        $unused = 1;

                        while (rand(0, 1)) {
                            try {} catch (\Exception $e) {}
                        }
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedVariableInsideIfLoop' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo() : void {
                        foreach ([1, 2, 3] as $i) {
                            $i = $i;
                        }
                    }',
                'error_message' => 'UnusedForeachValue',
            ],
            'detectUnusedVariableInsideLoopAfterAssignmentWithAddition' => [
                'code' => '<?php
                    function foo() : void {
                        foreach ([1, 2, 3] as $i) {
                            $i = $i + 1;
                        }
                    }',
                'error_message' => 'UnusedForeachValue',
            ],
            'detectUnusedVariableInsideLoopCalledInFunction' => [
                'code' => '<?php
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
                'code' => '<?php
                    $user_id = 0;
                    $user = null;

                    if (rand(0, 1)) {
                        $user_id = rand(0, 1);
                        $user = $user_id;
                    }

                    if ($user !== null && $user !== 0) {
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
                'code' => '<?php
                    $user_id = 0;
                    $user = null;

                    if (rand(0, 1)) {
                        $user_id = rand(0, 1);
                        $user = $user_id;
                    }

                    if ($user !== null && $user !== 0) {
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
                'code' => '<?php
                    function foo() : void {
                        $a = [];
                        $a[0] = 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'detectUnusedSecondAssignmentBeforeTry' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    /**
                     * @param non-empty-array<int> $a
                     */
                    function getLastNum(array $a): int {
                        foreach ($a as $num) {
                            $last = $num;
                        }
                        return 4;
                    }',
                'error_message' => 'UnusedForeachValue',
            ],
            'conditionalForeachWithUnusedValue' => [
                'code' => '<?php
                    if (rand(0, 1) > 0) {
                        foreach ([1, 2, 3] as $val) {}
                    }
                ',
                'error_message' => 'UnusedForeachValue',
            ],
            'doubleForeachWithInnerUnusedValue' => [
                'code' => '<?php
                    /**
                     * @param non-empty-list<list<int>> $arr
                     * @return list<int>
                     */
                    function f(array $arr): array {
                        foreach ($arr as $elt) {
                            foreach ($elt as $subelt) {}
                        }
                        return $elt;
                    }
                ',
                'error_message' => 'UnusedForeachValue',
            ],
            'defineInBothBranchesOfConditional' => [
                'code' => '<?php
                    $i = null;

                    if (($i = rand(0, 5)) || ($i = rand(0, 3))) {
                        echo $i;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'knownVarType' => [
                'code' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string */
                    $a = foo();

                    echo $a;',
                'error_message' => 'UnnecessaryVarAnnotation',
            ],
            'knownVarTypeWithName' => [
                'code' => '<?php
                    function foo() : string {
                        return "hello";
                    }

                    /** @var string $a */
                    $a = foo();

                    echo $a;',
                'error_message' => 'UnnecessaryVarAnnotation',
            ],
            'knownForeachVarType' => [
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
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
                'code' => '<?php
                    function foo(bool $b = false) : void {}',
                'error_message' => 'UnusedParam',
            ],
            'arrayMapClosureWithParamTypeNoUse' => [
                'code' => '<?php
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
                'code' => '<?php
                    function foo() : void {
                        /** @psalm-suppress PossiblyUndefinedVariable */
                        $arr["foo"] = 1;
                    }',
                'error_message' => 'UnusedVariable',
            ],
            'expectsNonNullAndPassedPossiblyNull' => [
                'code' => '<?php
                    /**
                     * @param mixed|null $mixed_or_null
                     */
                    function foo($mixed_or_null): Exception {
                        /**
                         * @psalm-suppress MixedArgument
                         */
                        return new Exception($mixed_or_null);
                    }',
                'error_message' => 'PossiblyNullArgument',
            ],
            'useArrayAssignmentNeverUsed' => [
                'code' => '<?php
                    $data = [];

                    return function () use ($data) {
                        $data[] = 1;
                    };',
                'error_message' => 'UnusedVariable',
            ],
            'warnAboutOriginalBadArray' => [
                'code' => '<?php
                    function takesArray(array $arr) : void {
                        foreach ($arr as $a) {}
                    }',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:42 - Unable to determine the type that $a is being assigned to. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:2:47',
            ],
            'warnAboutOriginalBadFunctionCall' => [
                'code' => '<?php
                    function makeArray() : array {
                        return ["hello"];
                    }

                    $arr = makeArray();

                    foreach ($arr as $a) {
                        echo $a;
                    }',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:8:38 - Unable to determine the type that $a is being assigned to. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:2:44',
            ],
            'warnAboutOriginalBadStaticCall' => [
                'code' => '<?php
                    class A {
                        public static function makeArray() : array {
                            return ["hello"];
                        }
                    }

                    $arr = A::makeArray();

                    foreach ($arr as $a) {
                        echo $a;
                    }',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:38 - Unable to determine the type that $a is being assigned to. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:3:62',
            ],
            'warnAboutOriginalBadInstanceCall' => [
                'code' => '<?php
                    class A {
                        public function makeArray() : array {
                            return ["hello"];
                        }
                    }

                    $arr = (new A)->makeArray();

                    foreach ($arr as $a) {
                        echo $a;
                    }',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:38 - Unable to determine the type that $a is being assigned to. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:3:55',
            ],
            'warnAboutDocblockReturnType' => [
                'code' => '<?php
                    /** @return array[] */
                    function makeArray() : array {
                        return [["hello"]];
                    }

                    $arr = makeArray();

                    foreach ($arr as $some_arr) {
                        foreach ($some_arr as $a) {
                            echo $a;
                        }
                    }',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:47 - Unable to determine the type that $a is being assigned to. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:2:33',
            ],
            'warnAboutMixedArgument' => [
                'code' => '<?php
                    function makeArray() : array {
                        return ["hello"];
                    }

                    $arr = makeArray();

                    /** @psalm-suppress MixedAssignment */
                    foreach ($arr as $a) {
                        echo $a;
                    }',
                'error_message' => 'MixedArgument - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:30 - Argument 1 of echo cannot be mixed, expecting string. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:2:44',
            ],
            'warnAboutMixedMethodCall' => [
                'code' => '<?php
                    function makeArray() : array {
                        return ["hello"];
                    }

                    $arr = makeArray();

                    /** @psalm-suppress MixedAssignment */
                    foreach ($arr as $a) {
                        $a->foo();
                    }',
                'error_message' => 'MixedMethodCall - src' . DIRECTORY_SEPARATOR . 'somefile.php:10:29 - Cannot determine the type of $a when calling method foo. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:2:44',
            ],
            'warnAboutMixedReturnStatement' => [
                'code' => '<?php
                    function makeArray() : array {
                        return ["hello"];
                    }

                    function foo() : string {
                        $arr = makeArray();

                        /** @psalm-suppress MixedAssignment */
                        foreach ($arr as $a) {
                            return $a;
                        }

                        return "";
                    }',
                'error_message' => 'MixedReturnStatement - src' . DIRECTORY_SEPARATOR . 'somefile.php:11:36 - Could not infer a return type. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:2:44',
            ],
            'warnAboutIterableKeySource' => [
                'code' => '<?php
                    function foo(iterable $arr) : void {
                        foreach ($arr as $key => $_) {}
                    }',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:42 - Unable to determine the type that $key is being assigned to. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:2:43',
            ],
            'warnAboutMixedKeySource' => [
                'code' => '<?php
                    /** @param mixed $arr */
                    function foo($arr) : void {
                        foreach ($arr as $key => $_) {}
                    }',
                'error_message' => 'MixedAssignment - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:42 - Unable to determine the type that $key is being assigned to. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:3:34',
            ],
            'warnAboutMixedArgumentTypeCoercionSource' => [
                'code' => '<?php
                    /** @param array<string> $arr */
                    function takesArrayOfString(array $arr) : void {
                        foreach ($arr as $a) {
                            echo $a;
                        }
                    }

                    /** @param mixed $a */
                    function takesArray($a) : void {
                        $arr = [$a];
                        takesArrayOfString($arr);
                    }',
                'error_message' => 'MixedArgumentTypeCoercion - src' . DIRECTORY_SEPARATOR . 'somefile.php:12:44 - Argument 1 of takesArrayOfString expects array<array-key, string>, but parent type list{mixed} provided. Consider improving the type at src' . DIRECTORY_SEPARATOR . 'somefile.php:10:41',
            ],
            'warnAboutUnusedVariableInTryReassignedInCatch' => [
                'code' => '<?php
                    $step = 0;
                    try {
                        $step = 1;
                        $step = 2;
                    } catch (Throwable $_) {
                        $step = 3;
                        echo $step;
                    }
                ',
                'error_message' => 'UnusedVariable',
            ],
            'warnAboutUnusedVariableInTryReassignedInFinally' => [
                'code' => '<?php
                    $step = 0;
                    try {
                        $step = 1;
                        $step = 2;
                    } finally {
                        $step = 3;
                        echo $step;
                    }
                ',
                'error_message' => 'UnusedVariable',
            ],
            'SKIPPED-warnAboutVariableUsedInNestedTryNotUsedInOuterTry' => [
                'code' => '<?php
                    $step = 0;
                    try {
                        $step = 1; // Unused
                        $step = 2;
                        try {
                            $step = 3;
                            $step = 4;
                        } finally {
                            echo $step;
                        }
                    } finally {
                    }
                ',
                'error_message' => 'UnusedVariable',
            ],
            'referenceReassignmentUnusedVariable' => [
                'code' => '<?php
                    $a = $b = 1;
                    $c = &$a;
                    $c = &$b;
                    $c = 2;

                    echo $a + $b + $c;
                ',
                'error_message' => 'UnusedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - $c',
            ],
            'referenceAssignmentIsNotUsed' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                ',
                'error_message' => 'UnusedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21 - $a',
            ],
            'unusedReferenceToPreviouslyUsedVariable' => [
                'code' => '<?php
                    $a = 1;
                    echo $a;
                    $b = &$a;
                ',
                'error_message' => 'UnusedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:21 - $b',
            ],
            'SKIPPED-unusedReferenceToSubsequentlyUsedVariable' => [ // Not easy to do the way it's currently set up
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    echo $a;
                ',
                'error_message' => 'UnusedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:21 - $b',
            ],
            'unusedReferenceInForeach' => [
                'code' => '<?php
                    foreach ([1, 2, 3] as &$var) {
                    }
                ',
                'error_message' => 'UnusedForeachValue',
            ],
            'SKIPPED-unusedReferenceInDestructuredForeach' => [
                'code' => '<?php
                    foreach ([[1, 2], [3, 4]] as [&$var, $_]) {
                    }
                ',
                'error_message' => 'UnusedForeachValue',
            ],
            'unusedReturnByReference' => [
                'code' => '<?php
                    function &foo(): int
                    {
                        /** @var ?int */
                        static $i;
                        if ($i === null) {
                            $i = 0;
                        }
                        return $i;
                    }

                    $bar = foo();
                ',
                'error_message' => 'UnusedVariable',
            ],
            'unusedPassByReference' => [
                'code' => '<?php
                    function foo(int &$arg): int
                    {
                        return 0;
                    }
                ',
                'error_message' => 'UnusedParam',
            ],
            'SKIPPED-unusedGlobalVariable' => [
                'code' => '<?php
                    $a = 0;
                    function foo(): void
                    {
                        global $a;
                    }
                ',
                'error_message' => 'UnusedVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:2:21 - $a',
            ],
            'unusedUndeclaredGlobalVariable' => [
                'code' => '<?php
                    function foo(): void
                    {
                        global $a;
                    }
                ',
                'error_message' => 'UnusedVariable',
            ],
            'reportWillReportFloatAsItIsAfterRequiredParameterAndUnused' => [
                'code' => '<?php

                /** @param callable(string,int,bool,mixed,float): void $callable */
                function takesCallable(callable $callable): void
                {
                    /** @var mixed $mixed */
                    $mixed = null;
                    $callable("foo", 0, true, $mixed, 0.0);
                }

                takesCallable(
                    static function (string $foo, int $bar, $float) {
                        if ($bar === 0) {
                            throw new RuntimeException();
                        }
                    }
                );',
                'error_message' => 'Param float is never referenced in this method',
            ],
        ];
    }
}
