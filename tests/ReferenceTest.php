<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ReferenceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'referenceAssignmentToNonReferenceCountsAsUse' => [
                'code' => '<?php
                    $b = &$a;
                    $b = 2;
                    echo $a;
                ',
                'assertions' => [
                    '$b===' => '2',
                    '$a===' => '2',
                ],
            ],
            'updateReferencedTypes' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $b = 2;
                    $c = 3;
                    $b = &$c;
                    $b = 4;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '4',
                    '$c===' => '4',
                ],
            ],
            'changingReferencedVariableChangesReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $a = 2;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '2',
                ],
            ],
            'unsetReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $b = 2;
                    unset($b);
                    $b = 3;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '3',
                ],
            ],
            'recursiveReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $a = &$b;
                    $b = 2;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '2',
                ],
            ],
            'SKIPPED-referenceToArrayItemChangesArrayType' => [
                'code' => '<?php
                    /** @var list<int> */
                    $arr = [];

                    assert(isset($arr[0]));
                    $int = &$arr[0];
                    $int = (string) $int;
                ',
                'assertions' => [
                    '$arr' => 'list<int|string>',
                ],
            ],
            'referenceToReference' => [
                'code' => '<?php
                    $a = 1;
                    $b = &$a;
                    $c = &$b;
                    $c = 2;
                    $d = 3;
                    $b = &$d;
                ',
                'assertions' => [
                    '$a===' => '2',
                    '$b===' => '3',
                    '$c===' => '2',
                    '$d===' => '3',
                ],
            ],
            'referenceToSubsequentCatch' => [
                'code' => '<?php
                    $a = null;
                    $b = &$a;

                    try {
                        throw new \Exception();
                    } catch (\Exception $a) {
                        takesException($b);
                    }
                    function takesException(\Exception $e): void {}
                ',
            ],
            'referenceAsSubsequentCatch' => [
                'code' => '<?php
                    $a = null;
                    $b = &$a;

                    try {
                        throw new \Exception();
                    } catch (\Exception $b) {
                        takesException($a);
                    }
                    function takesException(\Exception $e): void {}
                ',
            ],
            'referenceToNewVariableInitializesNull' => [
                'code' => '<?php
                    $b = &$a;
                ',
                'assertions' => [
                    '$a===' => 'null',
                    '$b===' => 'null',
                ],
            ],
            'referenceShadowedByGlobal' => [
                'code' => '<?php
                    /** @var string */
                    $a = 0;
                    function foo(): void
                    {
                        $b = 1;
                        $a = &$b;
                        global $a;
                        takesString($a);
                    }

                    function takesString(string $str): void {}
                ',
            ],
            'unsetPreventsReferenceConfusion' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }
                    unset($i);

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }
                ',
            ],
            'assignmentAsReferencePreventsReferenceConfusion' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }

                    $i = &$foo;

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }
                ',
            ],
            'assignmentAsReferenceInForeachPreventsReferenceConfusion' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }
                    foreach ($arr as &$i) {
                        ++$i;
                    }
                ',
            ],
            'referenceToProperty' => [
                'code' => '<?php
                    class Foo
                    {
                        public string $bar = "";
                    }

                    $foo = new Foo();
                    $bar = &$foo->bar;

                    $foo->bar = "bar";
                ',
                'assertions' => [
                    '$bar===' => "'bar'",
                ],
                'ignored_issues' => ['UnsupportedPropertyReferenceUsage'],
            ],
            'referenceReassignedInLoop' => [
                'code' => '<?php
                    /** @psalm-param list<string> $keys */
                    function &ensure_array(array &$what, array $keys): array
                    {
                        $arr = & $what;
                        while ($key = array_shift($keys)) {
                            if (!isset($arr[$key]) || !is_array($arr[$key])) {
                                $arr[$key] = array();
                            }
                            $arr = & $arr[$key];
                        }
                        return $arr;
                    }
                ',
            ],
            'dontCrashOnReferenceToMixedVariableArrayOffset' => [
                'code' => '<?php
                    function func(&$a): void
                    {
                        $_ = &$a["f"];
                    }
                ',
                'assertions' => [],
                'ignored_issues' => ['MixedArrayAccess', 'MissingParamType'],
            ],
            'dontCrashOnReferenceToArrayUnknownOffset' => [
                'code' => '<?php
                    function func(array &$a): void
                    {
                        $_ = &$a["f"];
                    }
                ',
                'assertions' => [],
            ],
            'dontCrashOnReferenceToArrayMixedOffset' => [
                'code' => '<?php
                    /** @param array{f: mixed} $a */
                    function func(array &$a): void
                    {
                        $_ = &$a["f"];
                    }
                ',
                'assertions' => [],
            ],
            'allowDocblockTypingOtherVariable' => [
                'code' => '<?php
                    $a = 1;
                    /** @var string $a */
                    $b = &$a;
                ',
                'assertions' => [
                    '$b' => 'string',
                ],
            ],
            'referenceToArrayVariableOffsetDoesntCrashWhenOffsetVariableChangesDueToReconciliation' => [
                'code' => '<?php
                    $a = "a";
                    $b = false;
                    $doesNotMatter = ["a" => ["id" => 1]];
                    $reference = &$doesNotMatter[$a];
                    /** @psalm-suppress TypeDoesNotContainType */
                    $result = ($a === "not-a" && ($b || false));
                ',
                'assertions' => [
                    '$reference===' => 'array{id: 1}',
                ],
            ],
            'multipleReferencesToArrayVariableOffsetThatChangesDueToReconciliation' => [
                'code' => '<?php
                    $a = "a";
                    $b = false;
                    $doesNotMatter = ["a" => ["id" => 1]];
                    $reference1 = &$doesNotMatter[$a];
                    $reference2 = &$doesNotMatter[$a];
                    /** @psalm-suppress TypeDoesNotContainType */
                    $result = ($a === "not-a" && ($b || false));
                    $reference1["id"] = 2;
                ',
                'assertions' => [
                    '$reference1===' => 'array{id: 2}',
                    '$reference2===' => 'array{id: 2}',
                ],
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'referenceReuseForeachValue' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    foreach ($arr as &$var) {
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInForeach' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    foreach ($arr as $val) {
                        $var = &$val;
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInFor' => [
                'code' => '<?php
                    /** @var list<int> */
                    $arr = [];

                    for ($i = 0; $i < count($arr); ++$i) {
                        $var = &$arr[$i];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInIf' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    if (isset($arr[0])) {
                        $var = &$arr[0];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInElseif' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    if (random_int(0, 1)) {
                    } elseif (isset($arr[0])) {
                        $var = &$arr[0];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeclaredInElse' => [
                'code' => '<?php
                    /** @var array<int> */
                    $arr = [];

                    if (!isset($arr[0])) {
                    } else {
                        $var = &$arr[0];
                        $var += 1;
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referenceReuseDeeplyNested' => [
                'code' => '<?php
                    /** @var list<list<list<int>>> */
                    $arr = [];

                    for ($i = 0; $i < count($arr); ++$i) {
                        foreach ($arr[$i] as $inner_arr) {
                            if (isset($inner_arr[0])) {
                                $var = &$inner_arr[0];
                                $var += 1;
                            }
                        }
                    }

                    $var = "foo";
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'referencesIgnoreVarAnnotation' => [
                'code' => '<?php
                    $a = 1;
                    /** @var int */
                    $b = &$a;
                ',
                'error_message' => 'InvalidDocblock - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:21 - Docblock type cannot be used for reference assignment',
            ],
            'unsetOnlyPreventsReferenceConfusionAfterCall' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }

                    unset($i);
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'assignmentAsReferenceOnlyPreventsReferenceConfusionAfterAssignment' => [
                'code' => '<?php
                    $arr = [1, 2, 3];
                    foreach ($arr as &$i) {
                        ++$i;
                    }

                    for ($i = 0; $i < 10; ++$i) {
                        echo $i;
                    }

                    $i = &$foo;
                ',
                'error_message' => 'ReferenceReusedFromConfusingScope',
            ],
            'unsupportedReferenceUsageWithReferenceToArrayOffsetOfArrayOffset' => [
                'code' => '<?php
                    /** @var array<string, string> */
                    $arr = [];

                    /** @var non-empty-list<string> */
                    $foo = ["foo"];

                    $bar = &$arr[$foo[0]];
                ',
                'error_message' => 'UnsupportedReferenceUsage',
            ],
            'unsupportedReferenceUsageContinuesAnalysis' => [
                'code' => '<?php
                    /** @var array<string, string> */
                    $arr = [];

                    /** @var non-empty-list<string> */
                    $foo = ["foo"];

                    /** @psalm-suppress UnsupportedReferenceUsage */
                    $bar = &$arr[$foo[0]];

                    /** @psalm-trace $bar */;
                ',
                'error_message' => ' - $bar: string',
            ],
        ];
    }
}
