<?php

declare(strict_types=1);

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class EmptyTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'ifNotUndefinedAndEmpty' => [
                'code' => '<?php
                    $a = !empty($b) ? $b : null;',
                'assertions' => [
                    '$a' => 'mixed|null',
                ],
                'ignored_issues' => ['MixedAssignment'],
            ],
            'emptyArrayVar' => [
                'code' => '<?php
                    function a(array $in): void
                    {
                        $r = [];
                        foreach ($in as $entry) {
                            if (!empty($entry["a"])) {
                                $r[] = [];
                            }
                            if (empty($entry["a"])) {
                                $r[] = [];
                            }
                        }
                    }

                    function b(array $in): void
                    {
                        $i = 0;
                        foreach ($in as $entry) {
                            if (!empty($entry["a"])) {
                                $i--;
                            }
                            if (empty($entry["a"])) {
                                $i++;
                            }
                        }
                    }

                    function c(array $in): void
                    {
                        foreach ($in as $entry) {
                            if (!empty($entry["a"])) {}
                        }
                        foreach ($in as $entry) {
                            if (empty($entry["a"])) {}
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'removeEmptyArray' => [
                'code' => '<?php
                    $arr_or_string = [];

                    if (rand(0, 1)) {
                      $arr_or_string = "hello";
                    }

                    /** @return void **/
                    function foo(string $s) {}

                    if (!empty($arr_or_string)) {
                        foo($arr_or_string);
                    }',
            ],
            'emptyArrayReconciliationThenIf' => [
                'code' => '<?php
                    /**
                     * @param string|string[] $a
                     */
                    function foo($a): string {
                        if (is_string($a)) {
                            return $a;
                        } elseif (empty($a)) {
                            return "goodbye";
                        }

                        if (isset($a[0])) {
                            return $a[0];
                        };

                        return "not found";
                    }',
            ],
            'emptyStringReconciliationThenIf' => [
                'code' => '<?php
                    /**
                     * @param Exception|string|string[] $a
                     */
                    function foo($a): string {
                        if (is_array($a)) {
                            return "hello";
                        } elseif (empty($a)) {
                            return "goodbye";
                        }

                        if (is_string($a)) {
                            return $a;
                        };

                        return "an exception";
                    }',
            ],
            'emptyExceptionReconciliationAfterIf' => [
                'code' => '<?php
                    /**
                     * @param Exception|null $a
                     */
                    function foo($a): string {
                        if ($a && $a->getMessage() === "hello") {
                            return "hello";
                        } elseif (empty($a)) {
                            return "goodbye";
                        }

                        return $a->getMessage();
                    }',
            ],
            'noFalsyLeak' => [
                'code' => '<?php
                    function foo(string $s): void {
                      if (empty($s) || $s === "hello") {}
                    }',
            ],
            'noRedundantConditionOnMixed' => [
                'code' => '<?php
                    function testarray(array $data): void {
                        foreach ($data as $item) {
                            if (!empty($item["a"]) && !empty($item["b"]["c"])) {
                                echo "Found\n";
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'dontBleedEmptyAfterExtract' => [
                'code' => '<?php
                    function foo(array $args): void {
                      extract($args);
                      if ((empty($arr) && empty($a)) || $c === 0) {
                      } else {
                        foreach ($arr as $b) {}
                      }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MixedArgument'],
            ],
            'emptyTKeyedArray' => [
                'code' => '<?php
                    $arr = [
                        "profile" => [
                            "foo" => "bar",
                        ],
                        "groups" => [
                            "foo" => "bar",
                            "hide"  => rand(0, 5),
                        ],
                    ];

                    foreach ($arr as $item) {
                        if (empty($item["hide"]) || $item["hide"] === 3) {}
                    }',
            ],
            'alwaysBoolResult' => [
                'code' => '<?php
                    function takesBool(bool $p): void {}
                    takesBool(empty($q));',
            ],
            'noRedundantConditionAfterFalsyIntChecks' => [
                'code' => '<?php
                    function foo(int $t) : void {
                        if (!$t) {
                            foreach ([0, 1, 2] as $a) {
                                if (!$t) {
                                    $t = $a;
                                }
                            }
                        }
                    }',
            ],
            'noRedundantConditionAfterEmptyMixedChecks' => [
                'code' => '<?php
                    function foo($t) : void {
                        if (empty($t)) {
                            foreach ($GLOBALS["u"] as $a) {
                                if (empty($t)) {
                                    $t = $a;
                                }
                            }
                        }
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MissingParamType'],
            ],
            'canBeNonEmptyArray' => [
                'code' => '<?php
                    function _processScopes($scopes) : void {
                        if (!is_array($scopes) && !empty($scopes)) {
                            $scopes = explode(" ", trim($scopes));
                        } else {
                            // false is allowed here
                        }

                        if (empty($scopes)){}
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MissingParamType', 'MixedArgument'],
            ],
            'multipleEmptiesInCondition' => [
                'code' => '<?php
                    /** @param array<int, int> $o */
                    function foo(array $o) : void {
                        if (empty($o[0]) && empty($o[1])) {}
                    }',
            ],
            'multipleEmptiesInConditionWithMixedOffset' => [
                'code' => '<?php
                    /** @param array $o */
                    function foo(array $o) : void {
                        if (empty($o[0]) && empty($o[1])) {}
                    }',
            ],
            'unsetChangesArrayEmptiness' => [
                'code' => '<?php
                    function foo(array $n): void {
                        if (empty($n)) {
                            return;
                        }
                        while (!empty($n)) {
                            unset($n[rand(0, 10)]);
                        }
                    }',
            ],
            'unsetChangesComplicatedArrayEmptiness' => [
                'code' => '<?php
                    function contains(array $data, array $needle): bool {
                        if (empty($data) || empty($needle)) {
                            return false;
                        }
                        $stack = [];

                        while (!empty($needle)) {
                            $key = key($needle);
                            $val = $needle[$key];
                            unset($needle[$key]);

                            if (array_key_exists($key, $data) && is_array($val)) {
                                $next = $data[$key];
                                unset($data[$key]);

                                if (!empty($val)) {
                                    $stack[] = [$val, $next];
                                }
                            } elseif (!array_key_exists($key, $data) || $data[$key] != $val) {
                                return false;
                            }

                            if (empty($needle) && !empty($stack)) {
                                list($needle, $data) = array_pop($stack);
                            }
                        }

                        return true;
                    }',
                'assertions' => [],
                'ignored_issues' => ['MixedAssignment', 'MissingParamType', 'MixedArgument', 'MixedArrayOffset', 'MixedArrayAccess'],
            ],
            'possiblyEmptyIterable' => [
                'code' => '<?php
                    function foo(iterable $i) : void {
                        if (empty($i)) {}
                        if (!empty($i)) {}
                    }',
            ],
            'allowEmptyClassConstantOffsetCheck' => [
                'code' => '<?php
                    class Foo {
                        const BAR = "bar";
                        const ONE = 1;
                    }

                    /**
                     * @param array<string,string> $data
                     */
                    function bat(array $data) : void {
                        if (!empty($data["foo"])) {
                            if (empty($data[Foo::BAR])) {}
                        }
                    }

                    /**
                     * @param array<int,string> $data
                     */
                    function baz(array $data) : void {
                        if (!empty($data[0])) {
                            if (empty($data[Foo::ONE])) {}
                        }
                    }',
            ],
            'doubleEmptyCheckTwoArrays' => [
                'code' => '<?php
                    function foo(array $a, array $b) : void {
                        if (empty($a) && empty($b)) {}
                    }',
            ],
            'doubleEmptyCheckOnTKeyedArray' => [
                'code' => '<?php
                    /**
                     * @param array{a: array, b: array} $arr
                     */
                    function foo(array $arr) : void {
                        if (empty($arr["a"]) && empty($arr["b"])) {}
                    }',
            ],
            'doubleEmptyCheckOnTKeyedArrayVariableOffsets' => [
                'code' => '<?php
                    function foo(int $i, int $j) : void {
                        $arr = [];
                        $arr[0] = rand(0, 1);
                        $arr[1] = rand(0, 1);

                        if (empty($arr[$i]) && empty($arr[$j])) {}
                    }',
            ],
            'checkArrayEmptyUnknownRoot' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if (empty($arr[rand(0, 1)])) {
                            if ($arr) {}
                        }
                    }',
            ],
            'allowEmptyCheckOnPossiblyNullPropertyFetch' => [
                'code' => '<?php
                    class A {
                        public bool $b = false;
                    }

                    function foo(?A $a) : void {
                        if (!empty($a->b)) {}
                    }',
            ],
            'allowNumericEmpty' => [
                'code' => '<?php
                    /**
                     * @param numeric $p
                     */
                    function f($p): bool {
                        if (empty($p)) {
                            return false;
                        }
                        return true;
                    }',
            ],
            'possiblyUndefinedArrayOffset' => [
                'code' => '<?php
                    $d = [];
                    if (!rand(0,1)) {
                        $d[0] = "a";
                    }

                    if (empty($d[0])) {}',
            ],
            'reconcileNonEmptyArrayKey' => [
                'code' => '<?php
                    /**
                     * @param array{a?: string, b: string} $arr
                     */
                    function createFromString(array $arr): void
                    {
                        if (empty($arr["a"])) {
                            return;
                        }

                        echo $arr["a"];
                    }',
            ],
            'reconcileEmptyTwiceWithoutReturn' => [
                'code' => '<?php
                    function foo(array $arr): void {
                        if (!empty($arr["a"])) {
                        } else {
                            if (empty($arr["dontcare"])) {}
                        }

                        if (empty($arr["a"])) {}
                    }',
            ],
            'reconcileEmptyTwiceWithReturn' => [
                'code' => '<?php
                    function foo(array $arr): void {
                        if (!empty($arr["a"])) {
                        } else {
                            if (empty($arr["dontcare"])) {
                                return;
                            }
                        }

                        if (empty($arr["a"])) {}
                    }',
            ],
            'SKIPPED-strlenWithGreaterZero' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) > 0 ? $str : "string";
                    }',
            ],
            'SKIPPED-strlenRighthandWithGreaterZero' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return 0 < strlen($str) ? $str : "string";
                    }',
            ],
            'SKIPPED-strlenWithGreaterEqualsOne' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) >= 1 ? $str : "string";
                    }',
            ],
            'SKIPPED-strlenRighthandWithGreaterEqualsOne' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return 1 <= strlen($str) ? $str : "string";
                    }',
            ],
            'SKIPPED-strlenWithInequalZero' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) !== 0 ? $str : "string";
                    }',
            ],
            'SKIPPED-strlenRighthandWithInequalZero' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return 0 !== strlen($str) ? $str : "string";
                    }',
            ],
            'SKIPPED-strlenWithEqualOne' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) === 1 ? $str : "string";
                    }',
            ],
            'SKIPPED-strlenRighthandWithEqualOne' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return 1 === strlen($str) ? $str : "string";
                    }',
            ],
            'SKIPPED-mb_strlen' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return mb_strlen($str) === 1 ? $str : "string";
                    }',
            ],
            'SKIPPED-countWithLiteralIntVariable' => [ // #8163
                'code' => '<?php
                    $c = 1;
                    /** @var list<int> */
                    $arr = [1];
                    assert(count($arr) === $c);
                ',
                'assertions' => ['$arr===' => 'non-empty-list<int>'],
            ],
            'SKIPPED-countWithIntRange' => [ // #8163
                'code' => '<?php
                    /** @var int<1, max> */
                    $c = 1;
                    /** @var list<int> */
                    $arr = [1];
                    assert(count($arr) === $c);
                ',
                'assertions' => ['$arr===' => 'non-empty-list<int>'],
            ],
            'SKIPPED-countEmptyWithIntRange' => [ // #8163
                'code' => '<?php
                    /** @var int<0, max> */
                    $c = 1;
                    /** @var list<int> */
                    $arr = [1];
                    assert(count($arr) === $c);
                ',
                'assertions' => ['$arr===' => 'list<int>'],
            ],
            'issue-9205-1' => [
                'code' => <<<'PHP'
                    <?php
                        /** @var string $domainCandidate */;

                        $candidateLabels = explode('.', $domainCandidate);

                        $lastLabel = $candidateLabels[0];

                        if (strlen($lastLabel) === 2) {
                            exit;
                        }
                    PHP,
                'assertions' => [
                    '$lastLabel===' => 'string',
                ],
            ],
            'issue-9205-2' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var string $x */
                    if (strlen($x) > 0) {
                        exit;
                    }
                    PHP,
                'assertions' => [
                    '$x===' => 'string', // perhaps this should be improved in future
                ],
            ],
            'issue-9205-3' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var string $x */
                    if (strlen($x) === 2) {
                        exit;
                    }
                    PHP,
                'assertions' => [
                    '$x===' => 'string', // can't be improved really
                ],
            ],
            'issue-9205-4' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var string $x */
                    if (strlen($x) < 2 ) {
                        exit;
                    }
                    PHP,
                'assertions' => [
                    '$x===' => 'string', // can be improved
                ],
            ],
            'issue-9349' => [
                'code' => <<<'PHP'
                    <?php

                    $str = $argv[1] ?? '';
                    if (empty($str) || strlen($str) < 3) {
                        exit(1);
                    }

                    echo $str;
                    PHP,
                'assertions' => [
                    '$str===' => 'non-falsy-string', // can't be improved
                ],
            ],
            'issue-9349-2' => [
                'code' => <<<'PHP'
                    <?php
                    function test(string $s): void {
                        if (!$s || strlen($s) !== 9) {
                            throw new Exception();
                        }
                    }
                    PHP,
            ],
            'issue-9349-3' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var string $a */;
                    if (strlen($a) === 7) {
                        return $a;
                    } elseif (strlen($a) === 10) {
                        return $a;
                    }
                    PHP,
                'assertions' => [
                    '$a===' => 'string', // can't be improved
                ],
            ],
            'issue-9341-1' => [
                'code' => <<<'PHP'
                    <?php
                    /** @var string */
                    $GLOBALS['sql_query'] = rand(0,1) ? 'asd' : null;
                    if(!empty($GLOBALS['sql_query']) && mb_strlen($GLOBALS['sql_query']) > 2)
                    {
                        exit;
                    }
                    PHP,
                'assertions' => [
                    '$GLOBALS[\'sql_query\']===' => 'string',
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'preventImpossibleEmpty' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        if (empty($ar)) {
                            // do something
                        }
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'reconciliationForMixed' => [
                'code' => '<?php
                    function foo(array $arr) : void {
                        $a = empty($arr["a"]) ? "" : $arr["a"];

                        if ($a) {
                            if ($a) {}
                        }
                    }',
                'error_message' => 'RedundantCondition',
                'ignored_issues' => ['MixedAssignment', 'MissingParamType'],
            ],
            'preventEmptyOnBool' => [
                'code' => '<?php
                    function foo(bool $b) : void {
                        if (!empty($b)) {}
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventEmptyCreatingArray' => [
                'code' => '<?php
                    /** @return array{a:mixed} */
                    function foo(array $r) {
                        if (!empty($r["a"])) {}
                        return $r;
                    }',
                'error_message' => 'MixedReturnTypeCoercion',
            ],
            'preventEmptyEquivalentCreatingArray' => [
                'code' => '<?php
                    /** @return array{a:mixed} */
                    function foo(array $r) {
                        if (isset($r["a"]) && $r["a"]) {}
                        return $r;
                    }',
                'error_message' => 'MixedReturnTypeCoercion',
            ],
            'SKIPPED-preventStrlenGreaterMinusOne' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) > -1 ? $str : "string";
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'SKIPPED-preventRighthandStrlenGreaterMinusOne' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return -1 < strlen($str) ? $str : "string";
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'SKIPPED-preventStrlenGreaterEqualsZero' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) >= 0 ? $str : "string";
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
            'SKIPPED-preventStrlenEqualsZero' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) === 0 ? $str : "string";
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'SKIPPED-preventStrlenLessThanOne' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) < 1 ? $str : "string";
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'SKIPPED-preventStrlenLessEqualsZero' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen($str) <= 0 ? $str : "string";
                    }',
                'error_message' => 'InvalidReturnStatement',
            ],
            'SKIPPED-preventStrlenWithConcatenatedString' => [
                'code' => '<?php
                    /** @return non-empty-string */
                    function nonEmptyString(string $str): string {
                        return strlen("a" . $str . "b") > 2 ? $str : "string";
                    }',
                'error_message' => 'LessSpecificReturnStatement',
            ],
        ];
    }
}
