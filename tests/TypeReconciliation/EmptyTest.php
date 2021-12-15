<?php

namespace Psalm\Tests\TypeReconciliation;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class EmptyTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'ifNotUndefinedAndEmpty' => [
                '<?php
                    $a = !empty($b) ? $b : null;',
                'assertions' => [
                    '$a' => 'mixed|null',
                ],
                'error_levels' => ['MixedAssignment'],
            ],
            'emptyArrayVar' => [
                '<?php
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
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'removeEmptyArray' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    function foo(string $s): void {
                      if (empty($s) || $s === "hello") {}
                    }',
            ],
            'noRedundantConditionOnMixed' => [
                '<?php
                    function testarray(array $data): void {
                        foreach ($data as $item) {
                            if (!empty($item["a"]) && !empty($item["b"]["c"])) {
                                echo "Found\n";
                            }
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArrayAccess'],
            ],
            'dontBleedEmptyAfterExtract' => [
                '<?php
                    function foo(array $args): void {
                      extract($args);
                      if ((empty($arr) && empty($a)) || $c === 0) {
                      } else {
                        foreach ($arr as $b) {}
                      }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MixedArgument'],
            ],
            'emptyTKeyedArray' => [
                '<?php
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
                '<?php
                    function takesBool(bool $p): void {}
                    takesBool(empty($q));',
            ],
            'noRedundantConditionAfterFalsyIntChecks' => [
                '<?php
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
                '<?php
                    function foo($t) : void {
                        if (empty($t)) {
                            foreach ($_GET["u"] as $a) {
                                if (empty($t)) {
                                    $t = $a;
                                }
                            }
                        }
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MissingParamType'],
            ],
            'canBeNonEmptyArray' => [
                '<?php
                    function _processScopes($scopes) : void {
                        if (!is_array($scopes) && !empty($scopes)) {
                            $scopes = explode(" ", trim($scopes));
                        } else {
                            // false is allowed here
                        }

                        if (empty($scopes)){}
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MissingParamType', 'MixedArgument'],
            ],
            'multipleEmptiesInCondition' => [
                '<?php
                    /** @param array<int, int> $o */
                    function foo(array $o) : void {
                        if (empty($o[0]) && empty($o[1])) {}
                    }',
            ],
            'multipleEmptiesInConditionWithMixedOffset' => [
                '<?php
                    /** @param array $o */
                    function foo(array $o) : void {
                        if (empty($o[0]) && empty($o[1])) {}
                    }',
            ],
            'unsetChangesArrayEmptiness' => [
                '<?php
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
                '<?php
                    function contains(array $data, array $needle): bool {
                        if (empty($data) || empty($needle)) {
                            return false;
                        }
                        $stack = [];

                        while (!empty($needle)) {
                            $key = key($needle);
                            if ($key === null) continue;
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
                'error_levels' => ['MixedAssignment', 'MissingParamType', 'MixedArgument', 'MixedArrayOffset', 'MixedArrayAccess'],
            ],
            'possiblyEmptyIterable' => [
                '<?php
                    function foo(iterable $i) : void {
                        if (empty($i)) {}
                        if (!empty($i)) {}
                    }',
            ],
            'allowEmptyClassConstantOffsetCheck' => [
                '<?php
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
                '<?php
                    function foo(array $a, array $b) : void {
                        if (empty($a) && empty($b)) {}
                    }',
            ],
            'doubleEmptyCheckOnTKeyedArray' => [
                '<?php
                    /**
                     * @param array{a: array, b: array} $arr
                     */
                    function foo(array $arr) : void {
                        if (empty($arr["a"]) && empty($arr["b"])) {}
                    }',
            ],
            'doubleEmptyCheckOnTKeyedArrayVariableOffsets' => [
                '<?php
                    function foo(int $i, int $j) : void {
                        $arr = [];
                        $arr[0] = rand(0, 1);
                        $arr[1] = rand(0, 1);

                        if (empty($arr[$i]) && empty($arr[$j])) {}
                    }',
            ],
            'checkArrayEmptyUnknownRoot' => [
                '<?php
                    function foo(array $arr) : void {
                        if (empty($arr[rand(0, 1)])) {
                            if ($arr) {}
                        }
                    }',
            ],
            'allowEmptyCheckOnPossiblyNullPropertyFetch' => [
                '<?php
                    class A {
                        public bool $b = false;
                    }

                    function foo(?A $a) : void {
                        if (!empty($a->b)) {}
                    }',
            ],
            'allowNumericEmpty' => [
                '<?php
                    /**
                     * @param numeric $p
                     */
                    function f($p): bool {
                        if (empty($p)) {
                            return false;
                        }
                        return true;
                    }'
            ],
            'possiblyUndefinedArrayOffset' => [
                '<?php
                    $d = [];
                    if (!rand(0,1)) {
                        $d[0] = "a";
                    }

                    if (empty($d[0])) {}'
            ],
            'reconcileNonEmptyArrayKey' => [
                '<?php
                    /**
                     * @param array{a?: string, b: string} $arr
                     */
                    function createFromString(array $arr): void
                    {
                        if (empty($arr["a"])) {
                            return;
                        }

                        echo $arr["a"];
                    }'
            ],
            'reconcileEmptyTwiceWithoutReturn' => [
                '<?php
                    function foo(array $arr): void {
                        if (!empty($arr["a"])) {
                        } else {
                            if (empty($arr["dontcare"])) {}
                        }

                        if (empty($arr["a"])) {}
                    }'
            ],
            'reconcileEmptyTwiceWithReturn' => [
                '<?php
                    function foo(array $arr): void {
                        if (!empty($arr["a"])) {
                        } else {
                            if (empty($arr["dontcare"])) {
                                return;
                            }
                        }

                        if (empty($arr["a"])) {}
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
            'preventImpossibleEmpty' => [
                '<?php
                    function foo(array $arr) : void {
                        if (empty($ar)) {
                            // do something
                        }
                    }',
                'error_message' => 'UndefinedVariable',
            ],
            'reconciliationForMixed' => [
                '<?php
                    function foo(array $arr) : void {
                        $a = empty($arr["a"]) ? "" : $arr["a"];

                        if ($a) {
                            if ($a) {}
                        }
                    }',
                'error_message' => 'RedundantCondition',
                'error_levels' => ['MixedAssignment', 'MissingParamType'],
            ],
            'preventEmptyOnBool' => [
                '<?php
                    function foo(bool $b) : void {
                        if (!empty($b)) {}
                    }',
                'error_message' => 'InvalidArgument',
            ],
            'preventEmptyCreatingArray' => [
                '<?php
                    /** @return array{a:mixed} */
                    function foo(array $r) {
                        if (!empty($r["a"])) {}
                        return $r;
                    }',
                'error_message' => 'MixedReturnTypeCoercion'
            ],
            'preventEmptyEquivalentCreatingArray' => [
                '<?php
                    /** @return array{a:mixed} */
                    function foo(array $r) {
                        if (isset($r["a"]) && $r["a"]) {}
                        return $r;
                    }',
                'error_message' => 'MixedReturnTypeCoercion'
            ],
            'secondEmptyTwice' => [
                '<?php
                    /**
                     * @param array{a?:int,b?:string} $p
                     */
                    function f(array $p) : void {
                        if (empty($p)) {
                            throw new RuntimeException("");
                        }
                        assert(!empty($p));
                    }',
                'error_message' => 'RedundantCondition'
            ],
        ];
    }
}
