<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ReferenceTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{code:string,assertions?:array<string,string>,ignored_issues?:list<string>,php_version?:string}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
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
            ],
        ];
    }

    /**
     * @return iterable<string,array{code:string,error_message:string,ignored_issues?:list<string>,php_version?:string}>
     */
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
        ];
    }
}
