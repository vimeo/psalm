<?php
namespace Psalm\Tests;

class EmptyTest extends TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'empty' => [
                '<?php
                    $a = !empty($b) ? $b : null;',
                'assertions' => [
                    '$a' => 'mixed',
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
                            if (!empty($item["a"]) && !empty($item["b"]) && !empty($item["b"]["c"])) {
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
            'emptyObjectLike' => [
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
                    takesBool(empty($q));'
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
            'noReconciliationForMixed' => [
                '<?php
                    function foo(array $arr) : void {
                        $a = empty($arr["a"]) ? "" : $arr["a"];

                        if ($a) {}
                    }',
                'assertions' => [],
                'error_levels' => ['MixedAssignment', 'MissingParamType'],
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
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
        ];
    }
}
