<?php
namespace Psalm\Tests\TypeReconciliation;

use const DIRECTORY_SEPARATOR;

class RedundantConditionTest extends \Psalm\Tests\TestCase
{
    use \Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;
    use \Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'ignoreIssueAndAssign' => [
                '<?php
                    function foo(): stdClass {
                        return new stdClass;
                    }

                    $b = null;

                    foreach ([0, 1] as $i) {
                        $a = foo();

                        if (!empty($a)) {
                            $b = $a;
                        }
                    }',
                'assertions' => [
                    '$b' => 'null|stdClass',
                ],
                'error_levels' => ['RedundantCondition'],
            ],
            'byrefNoRedundantCondition' => [
                '<?php
                    /**
                     * @param int $min ref
                     * @param int $other
                     */
                    function testmin(&$min, int $other): void {
                        if (is_null($min)) {
                            $min = 3;
                        } elseif (!is_int($min)) {
                            $min = 5;
                        } elseif ($min < $other) {
                            $min = $other;
                        }
                    }',
                'assertions' => [],
                'error_levels' => [
                    'RedundantConditionGivenDocblockType',
                    'DocblockTypeContradiction',
                ],
            ],
            'assignmentInIf' => [
                '<?php
                    function test(int $x = null): int {
                        if (!$x && !($x = rand(0, 10))) {
                            echo "Failed to get non-empty x\n";
                            return -1;
                        }
                        return $x;
                    }',
            ],
            'noRedundantConditionAfterAssignment' => [
                '<?php
                    /** @param int $i */
                    function foo($i): void {
                        /** @psalm-suppress RedundantConditionGivenDocblockType */
                        if ($i !== null) {
                            /** @psalm-suppress RedundantCastGivenDocblockType */
                            $i = (int) $i;

                            if ($i) {}
                        }
                    }',
                'assertions' => [],
            ],
            'noRedundantConditionAfterDocblockTypeNullCheck' => [
                '<?php
                    class A {
                        /** @var ?int */
                        public $foo;
                    }
                    class B {}

                    /**
                     * @param  A|B $i
                     */
                    function foo($i): void {
                        if (empty($i)) {
                            return;
                        }

                        switch (get_class($i)) {
                            case A::class:
                                if ($i->foo) {}
                                break;

                            default:
                                break;
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['DocblockTypeContradiction'],
            ],
            'noRedundantConditionTypeReplacementWithDocblock' => [
                '<?php
                    class A {}

                    /**
                     * @return A
                     */
                    function getA() {
                        return new A();
                    }

                    $maybe_a = rand(0, 1) ? new A : null;

                    if ($maybe_a === null) {
                        $maybe_a = getA();
                    }

                    if ($maybe_a === null) {}',
                'assertions' => [],
                'error_levels' => [
                    'DocblockTypeContradiction',
                ],
            ],
            'noRedundantConditionAfterPossiblyNullCheck' => [
                '<?php
                    if (rand(0, 1)) {
                        $a = "hello";
                    }

                    if ($a) {}',
                'assertions' => [],
                'error_levels' => ['PossiblyUndefinedGlobalVariable'],
            ],
            'noRedundantConditionAfterFromDocblockRemoval' => [
                '<?php
                    class A {
                        public function foo(): bool {
                            return (bool) rand(0, 1);
                        }
                        public function bar(): bool {
                            return (bool) rand(0, 1);
                        }
                    }

                    /** @return A */
                    function makeA() {
                        return new A;
                    }

                    $a = makeA();

                    if ($a === null) {
                        exit;
                    }

                    if ($a->foo() || $a->bar()) {}',
                'assertions' => [],
                'error_levels' => [
                    'DocblockTypeContradiction',
                ],
            ],
            'noEmptyUndefinedArrayVar' => [
                '<?php
                    if (rand(0,1)) {
                      /** @psalm-suppress UndefinedGlobalVariable */
                      $a = $b[0];
                    } else {
                      $a = null;
                    }
                    if ($a) {}',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'noComplaintWithIsNumericThenIsEmpty' => [
                '<?php
                    function takesString(string $s): void {
                      if (!is_numeric($s) || empty($s)) {}
                    }',
            ],
            'noRedundantConditionOnTryCatchVars' => [
                '<?php
                    function trycatch(): void {
                        $value = null;
                        try {
                            if (rand() % 2 > 0) {
                                throw new RuntimeException("Failed");
                            }
                            $value = new stdClass();
                            if (rand() % 2 > 0) {
                                throw new RuntimeException("Failed");
                            }
                        } catch (Exception $e) {
                            if ($value) {
                                var_export($value);
                            }
                        }

                        if ($value) {}
                    }',
            ],
            'noRedundantConditionInFalseCheck' => [
                '<?php
                    $ch = curl_init();
                    if (!$ch) {}',
            ],
            'noRedundantConditionInForCheck' => [
                '<?php
                    class Node
                    {
                        /** @var Node|null */
                        public $next;

                        public function iterate(): void
                        {
                            for ($node = $this; $node !== null; $node = $node->next) {}
                        }
                    }',
            ],
            'noRedundantConditionComparingBool' => [
                '<?php
                    function getBool(): bool {
                      return (bool)rand(0, 1);
                    }

                    function takesBool(bool $b): void {
                      if ($b === getBool()) {}
                    }',
            ],
            'evaluateElseifProperly' => [
                '<?php
                    /** @param string $str */
                    function foo($str): int {
                      if (is_null($str)) {
                        return 1;
                      } else if (strlen($str) < 1) {
                        return 2;
                      }
                      return 2;
                    }',
                'assertions' => [],
                'error_levels' => [
                    'DocblockTypeContradiction',
                    'RedundantConditionGivenDocblockType',
                ],
            ],
            'evaluateArrayCheck' => [
                '<?php
                    function array_check(): void {
                        $data = ["f" => false];
                        while (rand(0, 1) > 0 && !$data["f"]) {
                            $data = ["f" => true];
                        }
                    }',
            ],
            'mixedArrayAssignment' => [
                '<?php
                    /** @param mixed $arr */
                    function foo($arr): void {
                     if ($arr["a"] === false) {
                        /** @psalm-suppress MixedArrayAssignment */
                        $arr["a"] = (bool) rand(0, 1);
                        if ($arr["a"] === false) {}
                      }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'hardPhpTypeAssertionsOnDocblockBoolType' => [
                '<?php
                    /** @param bool|null $bar */
                    function foo($bar): void {
                        if (!is_null($bar) && !is_bool($bar)) {
                            throw new \Exception("bad");
                        }

                        if ($bar !== null) {}
                    }',
                'assertions' => [],
                'error_levels' => ['DocblockTypeContradiction'],
            ],
            'hardPhpTypeAssertionsOnDocblockStringType' => [
                '<?php
                    /** @param string|null $bar */
                    function foo($bar): void {
                        if (!is_null($bar) && !is_string($bar)) {
                            throw new \Exception("bad");
                        }

                        if ($bar !== null) {}
                    }',
                'assertions' => [],
                'error_levels' => ['DocblockTypeContradiction'],
            ],
            'isObjectAssertionOnDocblockType' => [
                '<?php
                    class A {}
                    class B {}

                    /** @param A|B $a */
                    function foo($a) : void {
                        if (!is_object($a)) {
                            return;
                        }

                        if ($a instanceof A) {

                        } elseif ($a instanceof B) {

                        } else {
                            throw new \Exception("bad");
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['RedundantConditionGivenDocblockType', 'DocblockTypeContradiction'],
            ],
            'nullToMixedWithNullCheckWithArraykey' => [
                '<?php
                    /** @return array<array-key, mixed> */
                    function getStrings(): array {
                        return ["hello", "world", 50];
                    }

                    $a = getStrings();

                    if (is_string($a[0]) && strlen($a[0]) > 3) {}',
                'assignments' => [],
                'error_levels' => [],
            ],
            'nullToMixedWithNullCheckWithIntKey' => [
                '<?php
                    /** @return array<int, mixed> */
                    function getStrings(): array {
                        return ["hello", "world", 50];
                    }

                    $a = getStrings();

                    if (is_string($a[0]) && strlen($a[0]) > 3) {}',
                'assignments' => [],
                'error_levels' => [],
            ],
            'replaceFalseTypeWithTrueConditionalOnMixedEquality' => [
                '<?php
                    function getData() {
                        return rand(0, 1) ? [1, 2, 3] : false;
                    }

                    $a = false;

                    while ($i = getData()) {
                        if (!$a && $i[0] === 2) {
                            $a = true;
                        }

                        if ($a === false) {}
                    }',
                'assignments' => [],
                'error_levels' => ['MixedAssignment', 'MissingReturnType', 'MixedArrayAccess'],
            ],
            'nullCoalescePossiblyUndefined' => [
                '<?php
                    if (rand(0,1)) {
                        $options = ["option" => true];
                    }

                    /** @psalm-suppress PossiblyUndefinedGlobalVariable */
                    $option = $options["option"] ?? false;

                    if ($option) {}',
                'assignments' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'allowIntValueCheckAfterComparisonDueToOverflow' => [
                '<?php
                    function foo(int $x) : void {
                        $x = $x + 1;

                        if (!is_int($x)) {
                            echo "Is a float.";
                        } else {
                            echo "Is an int.";
                        }
                    }

                    function bar(int $x) : void {
                        $x = $x + 1;

                        if (is_float($x)) {
                            echo "Is a float.";
                        } else {
                            echo "Is an int.";
                        }
                    }',
            ],
            'allowIntValueCheckAfterComparisonDueToOverflowInc' => [
                '<?php
                    function foo(int $x) : void {
                        $x++;

                        if (!is_int($x)) {
                            echo "Is a float.";
                        } else {
                            echo "Is an int.";
                        }
                    }

                    function bar(int $x) : void {
                        $x++;

                        if (is_float($x)) {
                            echo "Is a float.";
                        } else {
                            echo "Is an int.";
                        }
                    }',
            ],
            'allowIntValueCheckAfterComparisonDueToConditionalOverflow' => [
                '<?php
                    function foo(int $x) : void {
                        if (rand(0, 1)) {
                            $x = $x + 1;
                        }

                        if (is_float($x)) {
                            echo "Is a float.";
                        } else {
                            echo "Is an int.";
                        }
                    }',
            ],
            'changeStringValue' => [
                '<?php
                    $concat = "";
                    foreach (["x", "y"] as $v) {
                        if ($concat != "") {
                            $concat .= ", ";
                        }
                        $concat .= "($v)";
                    }',
            ],
            'arrayCanBeEmpty' => [
                '<?php
                    $x = ["key" => "value"];
                    if (rand(0, 1)) {
                        $x = [];
                    }
                    if ($x) {
                        var_export($x);
                    }',
            ],
            'noRedundantConditionStringNotFalse' => [
                '<?php
                    function foo(string $s) : void {
                        if ($s != false ) {}
                    }',
            ],
            'noRedundantConditionStringNotTrue' => [
                '<?php
                    function foo(string $s) : void {
                        if ($s != true ) {}
                    }',
            ],
            'noRedundantConditionBoolNotFalse' => [
                '<?php
                    function foo(bool $s) : void {
                        if ($s !== false ) {}
                    }',
            ],
            'noRedundantConditionBoolNotTrue' => [
                '<?php
                    function foo(bool $s) : void {
                        if ($s !== true ) {}
                    }',
            ],
            'noRedundantConditionNullableBoolIsFalseOrTrue' => [
                '<?php
                    function foo(?bool $s) : void {
                        if ($s === false ) {} elseif ($s === true) {}
                    }',
            ],
            'noRedundantConditionNullableBoolIsTrueOrFalse' => [
                '<?php
                    function foo(?bool $s) : void {
                        if ($s === true ) {} elseif ($s === false) {}
                    }',
            ],
            'noRedundantConditionAfterCheckingMixedTwice' => [
                '<?php
                    function foo($a) : void {
                        $b = $a ? 1 : 0;
                        $c = $a ? 1 : 0;
                    }',
                [],
                'error_levels' => ['MissingParamType'],
            ],
            'notAlwaysTrueBinaryOp' => [
                '<?php
                    function foo ($a) : void {
                        if (!$a) {}
                        $b = $a && rand(0, 1);
                    }',
                [],
                'error_levels' => ['MissingParamType'],
            ],
            'noRedundantConditionAfterAssertingValue' => [
                '<?php
                    function foo(string $t, bool $b) : void {
                        if (!$b && $t === "a") {
                            return;
                        }

                        if ($t === "c") {
                            if (!$b && bar($t)) {}
                        }
                    }

                    function bar(string $b) : bool {
                        return true;
                    }',
            ],
            'noRedundantConditionBleed' => [
                '<?php
                    $foo = getopt("i");
                    $i = $foo["i"];

                    /** @psalm-suppress TypeDoesNotContainNull */
                    if ($i === null) {
                        exit;
                    }

                    if ($i) {}',
            ],
            'emptyWithoutKnowingArrayType' => [
                '<?php
                    function foo(array $a) : void {
                        if (!empty($a["foo"])) {
                            foreach ($a["foo"] as $key => $_) {
                                if (rand(0, 1)) {
                                    unset($a["foo"][$key]);
                                }
                            }
                            if (empty($a["foo"])) {}
                        }
                    }',
                [],
                ['MixedAssignment', 'MixedArrayAccess', 'MixedArrayOffset'],
            ],
            'emptyKnowingArrayType' => [
                '<?php
                    /**
                     * @param array<string, array<string, int>> $a
                     */
                    function foo(array $a) : void {
                        if (!empty($a["foo"])) {
                            foreach ($a["foo"] as $key => $_) {
                                if (rand(0, 1)) {
                                    unset($a["foo"][$key]);
                                }
                            }
                            if (empty($a["foo"])) {}
                        }
                    }',
            ],
            'suppressRedundantConditionAfterAssertNonEmpty' => [
                '<?php
                    /**
                     * @param array<int> $a
                     */
                    function process(array $a): void {
                        assert(!empty($a));
                        /** @psalm-suppress RedundantConditionGivenDocblockType */
                        assert(is_int($a[0]));
                    }',
            ],
            'allowChecksOnFalsyIf' => [
                '<?php
                    function foo(?string $s) : string {
                        if ($s == null) {
                            if ($s === null) {}

                            return "hello";
                        } else {
                            return $s;
                        }
                    }',
            ],
            'updateArrayAfterUnset' => [
                '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(string $s) : void {
                        $dict = ["a" => 1];
                        unset($dict[$s]);
                        if (count($dict)) {}
                    }',
            ],
            'updateArrayAfterUnsetInLoop' => [
                '<?php
                    /**
                     * @param string[] $arr
                     */
                    function foo(array $arr) : void {
                        $dict = ["a" => 1, "b" => 2, "c" => 3];

                        foreach ($arr as $v) {
                            unset($dict[$v]);
                        }

                        if (count($dict)) {}
                    }',
            ],
            'noRedundantConditionWhenAssertingOnIntersection' => [
                '<?php
                    class A {}
                    interface I {}
                    class AChild extends A implements I {}

                    function isAChild(A $value): ?AChild {
                        if (!$value instanceof I) {
                            return null;
                        }

                        if (!$value instanceof AChild) {
                            return null;
                        }

                        return $value;
                    }',
            ],
            'noRedundantConditionWhenAssertingOnIntersectionFlipped' => [
                '<?php
                    class A {}
                    interface I {}
                    class AChild extends A implements I {}

                    /** @param I&A $value */
                    function isAChild(I $value): ?AChild {
                        if (!$value instanceof AChild) {
                            return null;
                        }

                        return $value;
                    }',
            ],
            'noRedundantConditionWhenAssertingOnIntersectionOfInterfaces' => [
                '<?php
                    interface A {}
                    interface I {}
                    class AChild implements I, A {}

                    function isAChild(A $value): ?AChild {
                        if (!$value instanceof I) {
                            return null;
                        }

                        if (!$value instanceof AChild) {
                            return null;
                        }

                        return $value;
                    }',
            ],
            'noRedundantConditionWithUnionOfInterfaces' => [
                '<?php
                    interface One {}
                    interface Two {}


                    /**
                     * @param One|Two $impl
                     */
                    function a($impl) : void {
                        if ($impl instanceof One && $impl instanceof Two) {
                            throw new \Exception();
                        } elseif ($impl instanceof One) {}
                    }

                    /**
                     * @param One|Two $impl
                     */
                    function b($impl) : void {
                        if ($impl instanceof One && $impl instanceof Two) {
                            throw new \Exception();
                        } else {
                            if ($impl instanceof One) {}
                        }
                    }'
            ],
            'invalidateAfterPostIncrement' => [
                '<?php
                    /**
                     * @param array<int, int> $tokens
                     */
                    function propertyInUse(array $tokens, int $i): bool {
                        if ($tokens[$i] !== 1) {
                            return false;
                        }
                        $i++;
                        if ($tokens[$i] !== 2) {}
                        return false;
                    }'
            ],
            'invalidateAfterAssignOp' => [
                '<?php
                    /**
                     * @param array<int, int> $tokens
                     */
                    function propertyInUse(array $tokens, int $i): bool {
                        if ($tokens[$i] !== 1) {
                            return false;
                        }
                        $i += 1;
                        if ($tokens[$i] !== 2) {}
                        return false;
                    }'
            ],
            'invalidateAfterAssign' => [
                '<?php
                    /**
                     * @param array<int, int> $tokens
                     */
                    function propertyInUse(array $tokens, int $i): bool {
                        if ($tokens[$i] !== 1) {
                            return false;
                        }
                        $i = $i + 1;
                        if ($tokens[$i] !== 2) {}
                        return false;
                    }'
            ],
            'numericNotString' => [
                '<?php
                    /** @param mixed $value */
                    function test($value) : void {
                        if (!is_numeric($value)) {
                            throw new Exception("Invalid $value");
                        }
                        if (!is_string($value)) {}
                    }'
            ],
            'checkClosedResource' => [
                '<?php
                    $fp = tmpfile();

                    if ($fp) {
                        echo "foo", "\n";
                    } else {
                        echo "bar", "\n";
                    }

                    echo var_export([$fp, is_resource($fp), !! $fp], true);

                    fclose($fp);',
                [
                    '$fp' => 'closed-resource',
                ]
            ],
            'allowCheckOnReturnTypeUnion' => [
                '<?php
                    /** @return int|string */
                    function returnsInt() {
                        return rand(0, 1) ? 1 : "hello";
                    }

                    if (is_int(returnsInt())) {}
                    if (!is_int(returnsInt())) {}',
            ],
            'noRedundantConditionInClosureForProperty' => [
                '<?php
                    class Queue {
                        private bool $closed = false;

                        public function enqueue(string $value): Closure {
                            if ($this->closed) {
                                return function() : void {
                                    if ($this->closed) {}
                                };
                            }

                            return function() : void {};
                        }
                    }'
            ],
            'noRedundantCastAfterCalculation' => [
                '<?php
                    function x(string $x): int {
                        return (int) (hexdec($x) + 1);
                    }',
            ],
            'unsetArrayWithKnownOffset' => [
                '<?php
                    function bar(string $f) : void {
                        $filter = rand(0, 1) ? explode(",", $f) : [$f];
                        unset($filter[rand(0, 1)]);
                        if ($filter) {}
                    }'
            ],
            'stringInScalar' => [
                '<?php
                    /**
                     * @template T of scalar
                     * @param T $value
                     */
                    function normalizeValue(bool|int|float|string $value): void
                    {
                        assert(is_string($value));
                    }'
            ],
            'NumericCanBeFalsy' => [
                '<?php
                    function test(string|int|float|bool $value): bool {
                        if (is_numeric($value) || $value === true) {
                            if ($value) {
                                return true;
                            }
                        }
                        return false;
                    }'
            ],
            'NumericCanBeNotIntOrNotFloat' => [
                '<?php
                    /** @param mixed $a */
                    function a($a): void{
                        if (is_numeric($a)) {
                            assert(!is_float($a));
                        }
                    }
                    /** @param mixed $a */
                    function b($a): void{
                        if (is_numeric($a)) {
                            assert(!is_int($a));
                        }
                    }'
            ],
            'alwaysTrueAssignAllowedInsideAND' => [
                '<?php
                    class A{
                        public function get(): stdClass{ return new stdClass;}
                    }
                    $a = new A();

                    if (($c = $a->get()) && rand(0,1)){


                    }
                    '
            ],
            'alwaysTrueAssignAllowedInsideOr' => [
                '<?php
                    class A{
                        public function get(): ?stdClass{ return new stdClass;}
                    }
                    $a = new A();

                    if ($a->get() || ($c = rand(0,1))){


                    }
                    '
            ],
            'countWithNeverValuesInKeyedArray' => [
                '<?php
                    /** @var non-empty-array $report_data */
                    $report_data = [];
                    if ( array_key_exists( "A", $report_data ) ) {
                    } elseif ( !empty( $report_data[0]["type"] ) && rand(0,1) ) {
                        if ( rand(0,1) ) {}

                        if ( count( $report_data ) === 1 ) {
                        }
                    }'
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'ifFalse' => [
                '<?php
                    $y = false;
                    if ($y) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'ifNotTrue' => [
                '<?php
                    $y = true;
                    if (!$y) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'ifTrue' => [
                '<?php
                    $y = true;
                    if ($y) {}',
                'error_message' => 'RedundantCondition',
            ],
            'unnecessaryInstanceof' => [
                '<?php
                    class One {
                        public function fooFoo() : void {}
                    }

                    $var = new One();

                    if ($var instanceof One) {
                        $var->fooFoo();
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'failedTypeResolution' => [
                '<?php
                    class A { }

                    /**
                     * @return void
                     */
                    function fooFoo(A $a) {
                        if ($a instanceof A) {
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'failedTypeResolutionWithDocblock' => [
                '<?php
                    class A { }

                    /**
                     * @param  A $a
                     * @return void
                     */
                    function fooFoo(A $a) {
                        if ($a instanceof A) {
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'typeResolutionFromDocblockAndInstanceof' => [
                '<?php
                    class A { }

                    /**
                     * @param  A $a
                     * @return void
                     * @psalm-suppress RedundantConditionGivenDocblockType
                     */
                    function fooFoo($a) {
                        if ($a instanceof A) {
                            if ($a instanceof A) {
                            }
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'typeResolutionRepeatingConditionWithSingleVar' => [
                '<?php
                    $a = rand(0, 10) > 5;
                    if ($a && $a) {}',
                'error_message' => 'RedundantCondition',
            ],
            'typeResolutionRepeatingConditionWithVarInMiddle' => [
                '<?php
                    $a = rand(0, 10) > 5;
                    $b = rand(0, 10) > 5;
                    if ($a && $b && $a) {}',
                'error_message' => 'RedundantCondition',
            ],
            'typeResolutionRepeatingOredConditionWithSingleVar' => [
                '<?php
                    $a = rand(0, 10) > 5;
                    if ($a || $a) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'typeResolutionRepeatingOredConditionWithVarInMiddle' => [
                '<?php
                    $a = rand(0, 10) > 5;
                    $b = rand(0, 10) > 5;
                    if ($a || $b || $a) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'typeResolutionIsIntAndIsNumeric' => [
                '<?php
                    $c = rand(0, 10) > 5 ? "hello" : 3;
                    if (is_int($c) && is_numeric($c)) {}',
                'error_message' => 'RedundantCondition',
            ],
            'typeResolutionWithInstanceOfAndNotEmpty' => [
                '<?php
                    $x = rand(0, 10) > 5 ? new stdClass : null;
                    if ($x instanceof stdClass && $x) {}',
                'error_message' => 'RedundantCondition',
            ],
            'methodWithMeaninglessCheck' => [
                '<?php
                    class One {
                        /** @return void */
                        public function fooFoo() {}
                    }

                    class B {
                        /** @return void */
                        public function barBar(One $one) {
                            if (!$one) {
                                // do nothing
                            }

                            $one->fooFoo();
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'twoVarLogicNotNestedWithElseifNegatedInIf' => [
                '<?php
                    function foo(?string $a, ?string $b): ?string {
                        if ($a) {
                            $a = null;
                        } elseif ($b) {
                            // do nothing here
                        } else {
                            return "bad";
                        }

                        if (!$a) return $b;
                        return $a;
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'refineTypeInMethodCall' => [
                '<?php
                    class A {}

                    /** @return ?A */
                    function getA() {
                      return rand(0, 1) ? new A : null;
                    }

                    function takesA(A $a): void {}

                    $a = getA();
                    if ($a instanceof A) {}
                    /** @psalm-suppress PossiblyNullArgument */
                    takesA($a);
                    if ($a instanceof A) {}',
                'error_message' => 'RedundantCondition - src' . DIRECTORY_SEPARATOR . 'somefile.php:15',
            ],
            'replaceFalseType' => [
                '<?php
                    function foo(bool $b) : void {
                      if (!$b) {
                        $b = true;
                      }

                      if ($b) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'replaceTrueType' => [
                '<?php
                    function foo(bool $b) : void {
                      if ($b) {
                        $b = false;
                      }

                      if ($b) {}
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'disallowFloatCheckAfterSettingToVar' => [
                '<?php
                    function foo(int $x) : void {
                        if (rand(0, 1)) {
                            $x = 125;
                        }

                        if (is_float($x)) {
                            echo "Is a float.";
                        } else {
                            echo "Is an int.";
                        }
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:7',
            ],
            'disallowTwoIntValueChecksDueToConditionalOverflow' => [
                '<?php
                    function foo(int $x) : void {
                        $x = $x + 1;

                        if (is_int($x)) {
                        } elseif (is_int($x)) {}
                    }',
                'error_message' => 'TypeDoesNotContainType - src' . DIRECTORY_SEPARATOR . 'somefile.php:6',
            ],
            'redundantEmptyArray' => [
                '<?php
                    $x = ["key" => "value"];
                    if ($x) {
                        var_export($x);
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'redundantConditionStringNotFalse' => [
                '<?php
                    function foo(string $s) : void {
                        if ($s !== false ) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'redundantConditionStringNotTrue' => [
                '<?php
                    function foo(string $s) : void {
                        if ($s !== true ) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'redundantConditionAfterRemovingFalse' => [
                '<?php
                    $s = rand(0, 1) ? rand(0, 5) : false;

                    if ($s !== false) {
                        if (is_int($s)) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'redundantConditionAfterRemovingTrue' => [
                '<?php
                    $s = rand(0, 1) ? rand(0, 5) : true;

                    if ($s !== true) {
                        if (is_int($s)) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'impossibleNullEquality' => [
                '<?php
                    $i = 5;
                    echo $i !== null;',
                'error_message' => 'RedundantCondition',
            ],
            'impossibleTrueEquality' => [
                '<?php
                    $i = 5;
                    echo $i !== true;',
                'error_message' => 'RedundantCondition',
            ],
            'impossibleFalseEquality' => [
                '<?php
                    $i = 5;
                    echo $i !== false;',
                'error_message' => 'RedundantCondition',
            ],
            'impossibleNumberEquality' => [
                '<?php
                    $i = 5;
                    echo $i !== 3;',
                'error_message' => 'RedundantCondition',
            ],
            'alwaysTrueBinaryOp' => [
                '<?php
                    function foo ($a) : void {
                        if (!$a) return;
                        $b = $a && rand(0, 1);
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => ['MissingParamType'],
            ],
            'negatedInstanceof' => [
                '<?php
                    class A {}
                    class B {}

                    function foo(A $a) : void {
                        if (!$a instanceof B) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'redundantInstanceof' => [
                '<?php
                    /** @param Exception $a */
                    function foo($a) : void {
                        if ($a instanceof \Exception) {}
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'preventDocblockTypesBeingIdenticalToTrue' => [
                '<?php
                    class A {}

                    /**
                     * @param  A $a
                     */
                    function foo($a, $b) : void {
                        if ($a === true) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventDocblockTypesBeingIdenticalToTrueReversed' => [
                '<?php
                    class A {}

                    /**
                     * @param  A $a
                     */
                    function foo($a, $b) : void {
                        if (true === $a) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventDocblockTypesBeingIdenticalToFalse' => [
                '<?php
                    class A {}

                    /**
                     * @param  A $a
                     */
                    function foo($a, $b) : void {
                        if ($a === false) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventDocblockTypesBeingIdenticalToFalseReversed' => [
                '<?php
                    class A {}

                    /**
                     * @param  A $a
                     */
                    function foo($a, $b) : void {
                        if (false === $a) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventDocblockTypesBeingSameAsEmptyArrayReversed' => [
                '<?php
                    class A {}

                    /**
                     * @param  A $a
                     */
                    function foo($a, $b) : void {
                        if ([] == $a) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventDocblockTypesBeingIdenticalToEmptyArrayReversed' => [
                '<?php
                    class A {}

                    /**
                     * @param  A $a
                     */
                    function foo($a, $b) : void {
                        if ([] === $a) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'preventTypesBeingIdenticalToEmptyArrayReversed' => [
                '<?php
                    class A {}

                    function foo(A $a, $b) : void {
                        if ([] === $a) {}
                    }',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'SKIPPED-secondInterfaceAssertionIsRedundant' => [
                '<?php
                    interface One {}
                    interface Two {}

                    /**
                     * @param One|Two $value
                     */
                    function isOne($value): void {
                        if ($value instanceof One) {
                            if ($value instanceof One) {}
                        }
                    }',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'errorAfterStatementThatCannotBeConvertedToAssertion' => [
                '<?php
                    function a(float $b) : void {
                        if ($b === 0.0) {
                            return;
                        }

                        $a = new stdClass();

                        if ($a) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'noLongerWarnsAboutRedundancyHere' => [
                '<?php
                    function a(bool $a, bool $b) : void {
                        if ($a || $b) {
                            if ($a) {
                            } elseif ($b) {
                            }
                        }
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'prohibitFalsyChecksOnPropertiesWithMethodCall' => [
                '<?php
                    class RequestHeaders {
                        public function has(string $s) : bool {
                            return true;
                        }
                    }

                    class Request {
                        public RequestHeaders $headers;
                        public function __construct(RequestHeaders $headers) {
                            $this->headers = $headers;
                        }
                    }

                    function lag(Request $req) : void  {
                        if ($req->headers && $req->headers->has("foo")) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'prohibitFalsyChecksOnPropertiesWithoutMethodCall' => [
                '<?php
                    class RequestHeaders {}

                    class Request {
                        public RequestHeaders $headers;
                        public function __construct(RequestHeaders $headers) {
                            $this->headers = $headers;
                        }
                    }

                    function lag(Request $req) : void  {
                        if ($req->headers) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'checkResourceTwice' => [
                '<?php
                    $fp = tmpfile();

                    if ($fp && is_resource($fp)) {
                        if (is_resource($fp)) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'preventAlwaysReturningInt' => [
                '<?php
                    function returnsInt(): int {
                        return 3;
                    }

                    if (is_int(returnsInt())) {}',
                'error_message' => 'RedundantCondition',
            ],
            'preventAlwaysReturningSpecificInt' => [
                '<?php
                    /**
                     * @return 3|4
                     */
                    function returnsInt(): int {
                        return rand(0, 1) ? 3 : 4;
                    }

                    if (is_int(returnsInt())) {}',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'preventNotAlwaysReturningInt' => [
                '<?php
                    function returnsInt(): int {
                        return 3;
                    }

                    if (!is_int(returnsInt())) {}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'classAlwaysParent' => [
                '<?php
                    class AParent {}

                    class A extends AParent {
                        public static function load() : A {
                            return new A();
                        }
                    }

                    $a = A::load();

                    if ($a instanceof AParent) {}',
                'error_message' => 'RedundantCondition',
            ],
            'staticClassIsAlwaysNull' => [
                '<?php
                    /**
                     * @psalm-consistent-constructor
                     */
                    class A {
                        /**
                         * @return ?static
                         */
                        public static function load() {
                            return rand(0, 1)
                                ? null
                                : new static();
                        }
                    }

                    $a = A::load();

                    if ($a && $a instanceof A) {}',
                'error_message' => 'RedundantConditionGivenDocblockType',
            ],
            'classStringNotEmpty' => [
                '<?php
                    function foo(object $o) : void {
                        $oc = get_class($o);
                        if ($oc) {}
                    }',
                'error_message' => 'RedundantCondition',
            ],
            'leftCannotBeTrue' => [
                '<?php
                    /** @psalm-type F = ""|"0" */
                    /**
                     * @param F $a
                     * @param F $b
                     */
                    function foo(string $a, string $b): void {
                        if ($a || $b) {}
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'rightCannotBeTrue' => [
                '<?php
                    /** @param false $a */
                    function foo(bool $a): void {
                        if (rand(0, 1) || $a) {
                            echo "a or b";
                        }
                    }',
                'error_message' => 'DocblockTypeContradiction',
            ],
            'OrTrue' => [
                '<?php
                    if(rand(0,1) || true){}',
                'error_message' => 'RedundantCondition',
            ],
            'AndTrue' => [
                '<?php
                    if(rand(0,1) && true){}',
                'error_message' => 'RedundantCondition',
            ],
            'OrFalse' => [
                '<?php
                    if(rand(0,1) || false){}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'AndFalse' => [
                '<?php
                    if(rand(0,1) && false){}',
                'error_message' => 'TypeDoesNotContainType',
            ],
            'alwaysTrueAssign' => [
                '<?php
                    class A{
                        public function get(): stdClass{ return new stdClass;}
                    }
                    $a = new A();

                    if ($c = $a->get()){


                    }
                    ',
                'error_message' => 'RedundantCondition',
            ],
        ];
    }
}
