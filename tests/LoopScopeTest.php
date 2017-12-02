<?php
namespace Psalm\Tests;

class LoopScopeTest extends TestCase
{
    use Traits\FileCheckerInvalidCodeParseTestTrait;
    use Traits\FileCheckerValidCodeParseTestTrait;

    /**
     * @return array
     */
    public function providerFileCheckerValidCodeParse()
    {
        return [
            'switchVariableWithContinue' => [
                '<?php
                    foreach ([\'a\', \'b\', \'c\'] as $letter) {
                        switch ($letter) {
                            case \'a\':
                                $foo = 1;
                                break;
                            case \'b\':
                                $foo = 2;
                                break;
                            default:
                                continue;
                        }

                        $moo = $foo;
                    }',
            ],
            'switchVariableWithContinueAndIfs' => [
                '<?php
                    foreach ([\'a\', \'b\', \'c\'] as $letter) {
                        switch ($letter) {
                            case \'a\':
                                if (rand(0, 10) === 1) {
                                    continue;
                                }
                                $foo = 1;
                                break;
                            case \'b\':
                                if (rand(0, 10) === 1) {
                                    continue;
                                }
                                $foo = 2;
                                break;
                            default:
                                continue;
                        }

                        $moo = $foo;
                    }',
            ],
            'switchVariableWithFallthrough' => [
                '<?php
                    foreach ([\'a\', \'b\', \'c\'] as $letter) {
                        switch ($letter) {
                            case \'a\':
                            case \'b\':
                                $foo = 2;
                                break;

                            default:
                                $foo = 3;
                                break;
                        }

                        $moo = $foo;
                    }',
            ],
            'switchVariableWithFallthroughStatement' => [
                '<?php
                    foreach ([\'a\', \'b\', \'c\'] as $letter) {
                        switch ($letter) {
                            case \'a\':
                                $bar = 1;

                            case \'b\':
                                $foo = 2;
                                break;

                            default:
                                $foo = 3;
                                break;
                        }

                        $moo = $foo;
                    }',
            ],
            'whileVar' => [
                '<?php
                    $worked = false;

                    while (rand(0,100) === 10) {
                        $worked = true;
                    }',
                'assertions' => [
                    '$worked' => 'bool',
                ],
            ],
            'doWhileVar' => [
                '<?php
                    $worked = false;

                    do {
                        $worked = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$worked' => 'bool',
                ],
            ],
            'doWhileVarAndBreak' => [
                '<?php
                    /** @return void */
                    function foo(string $b) {}

                    do {
                        if (null === ($a = rand(0, 1) ? "hello" : null)) {
                            break;
                        }

                        foo($a);
                    }
                    while (rand(0,100) === 10);',
            ],
            'objectValueWithTwoTypes' => [
                '<?php
                    class B {}
                    class A {
                        /** @var A|B */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : new B();
                        }
                    }

                    function makeA() : A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a instanceof A) {
                        $a = $a->parent;
                    }',
                'assertions' => [
                    '$a' => 'B',
                ],
            ],
            'objectValueWithInstanceofProperty' => [
                '<?php
                    class B {}
                    class A {
                        /** @var A|B */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : new B();
                        }
                    }

                    function makeA() : A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a->parent instanceof A) {
                        $a = $a->parent;
                    }

                    $b = $a->parent;',
                'assertions' => [
                    '$a' => 'A',
                    '$b' => 'A|B',
                ],
            ],
            'objectValueNullable' => [
                '<?php
                    class A {
                        /** @var ?A */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : null;
                        }
                    }

                    function makeA() : A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a) {
                        $a = $a->parent;
                    }',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'objectValueWithAnd' => [
                '<?php
                    class A {
                        /** @var ?A */
                        public $parent;

                        public function __construct() {
                            $this->parent = rand(0, 1) ? new A() : null;
                        }
                    }

                    function makeA() : A {
                        return new A();
                    }

                    $a = makeA();

                    while ($a && rand(0, 10) > 5) {
                        $a = $a->parent;
                    }',
                'assertions' => [
                    '$a' => 'A|null',
                ],
            ],
            'secondLoopWithNotNullCheck' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if ($a !== null) takesInt($a);
                        $a = $i;
                    }',
            ],
            'secondLoopWithIntCheck' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) takesInt($a);
                        $a = $i;
                    }',
            ],
            'secondLoopWithIntCheckAndConditionalSet' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) takesInt($a);

                        if (rand(0, 1)) {
                            $a = $i;
                        }
                    }',
            ],
            'secondLoopWithIntCheckAndAssignmentsInIfAndElse' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) {
                            $a = 6;
                        } else {
                            $a = $i;
                        }
                    }',
            ],
            'secondLoopWithIntCheckAndLoopSet' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        if (is_int($a)) takesInt($a);

                        while (rand(0, 1)) {
                            $a = $i;
                        }
                    }',
            ],
            'secondLoopWithReturnInElseif' => [
                '<?php
                    class A {}
                    class B extends A {}
                    class C extends A {}

                    $b = null;

                    foreach ([new A, new A] as $a) {
                        if ($a instanceof B) {

                        } elseif (!$a instanceof C) {
                            return "goodbye";
                        }

                        if ($b instanceof C) {
                            return "hello";
                        }

                        $b = $a;
                    }',
            ],
            'thirdLoopWithIntCheckAndLoopSet' => [
                '<?php
                    /** @return void **/
                    function takesInt(int $i) {}

                    $a = null;
                    $b = null;

                    foreach ([1, 2, 3] as $i) {
                        if ($b !== null) {
                            takesInt($b);
                        }

                        if ($a !== null) {
                            takesInt($a);
                            $b = $a;
                        }

                        $a = $i;
                    }',
            ],
            'implicitFourthLoop' => [
                '<?php
                    function test(): int {
                      $x = 0;
                      $y = 1;
                      $z = 2;
                      for ($i = 0; $i < 3; $i++) {
                        $x = $y;
                        $y = $z;
                        $z = 5;
                      }
                      return $x;
                    }',
            ],
            'unsetInLoop' => [
                '<?php
                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                        $a = $i;
                        unset($i);
                    }',
            ],
            'assignInsideForeach' => [
                '<?php
                    $b = false;

                    foreach ([1, 2, 3, 4] as $a) {
                        if ($a === rand(0, 10)) {
                            $b = true;
                        }
                    }',
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'assignInsideForeachWithBreak' => [
                '<?php
                    $b = false;

                    foreach ([1, 2, 3, 4] as $a) {
                        if ($a === rand(0, 10)) {
                            $b = true;
                            break;
                        }
                    }',
                'assertions' => [
                    '$b' => 'bool',
                ],
            ],
            'nullCheckInsideForeachWithContinue' => [
                '<?php
                    class A {
                        /** @return array<A|null> */
                        public static function loadMultiple()
                        {
                            return [new A, null];
                        }

                        /** @return void */
                        public function barBar() {

                        }
                    }

                    foreach (A::loadMultiple() as $a) {
                        if ($a === null) {
                            continue;
                        }

                        $a->barBar();
                    }',
            ],
            'loopWithArrayKey' => [
                '<?php
                    /**
                     * @param array<array<int, array<string, string>>> $args
                     * @return array[]
                     */
                    function get_merged_dict(array $args) {
                        $merged = array();

                        foreach ($args as $group) {
                            foreach ($group as $key => $value) {
                                if (isset($merged[$key])) {
                                    $merged[$key] = array_merge($merged[$key], $value);
                                } else {
                                    $merged[$key] = $value;
                                }
                            }
                        }

                        return $merged;
                    }',
            ],
            'loopWithNoParadox' => [
                '<?php
                    $a = ["b", "c", "d"];
                    while ($a) {
                      $letter = array_pop($a);
                      if (!$a) {}
                    }',
            ],
            'loopWithIfElseNoParadox' => [
                '<?php
                    $a = [];
                    $b = rand(0, 10) > 5;

                    foreach ([1, 2, 3] as $i) {
                      if (rand(0, 5)) {
                        $a[] = 5;
                        continue;
                      }

                      if ($b) {
                        continue; // if this is removed, no failure
                      } else {} // if else is removed, no failure
                    }

                    if ($a) {}',
            ],
            'bleedVarIntoOuterContextWithEmptyLoop' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                    }',
                'assignments' => [
                    '$tag' => 'null|string',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNull' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefinedAsNullAndBreak' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                        break;
                      } elseif ($tag === "b") {
                        $tag = null;
                        break;
                      } else {
                        $tag = null;
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInElse' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'string|null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInIf' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        break;
                      } else {
                        $tag = null;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'string|null',
                ],
            ],
            'bleedVarIntoOuterContextWithBreakInElseAndIntSet' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = 5;
                      } else {
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'string|null|int',
                ],
            ],
            'bleedVarIntoOuterContextWithRedefineAndBreak' => [
                '<?php
                    $tag = null;
                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $tag = null;
                      } else {
                        $tag = null;
                        break;
                      }
                    }',
                'assignments' => [
                    '$tag' => 'null',
                ],
            ],
            'nullToNullableWithNullCheck' => [
                '<?php
                    $a = null;

                    foreach ([1, 2, 3] as $i) {
                      if ($a === null) {
                        /** @var mixed */
                        $a = "hello";
                      }
                    }',
                'assignments' => [
                    '$a' => 'mixed',
                ],
                'error_levels' => [
                    'MixedAssignment',
                ],
            ],
            'falseToBoolInBreak' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $a = true;
                        break;
                      }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInContinue' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $a = true;
                        continue;
                      }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInBreakAndContinue' => [
                '<?php
                    $a = false;

                    foreach (["a", "b", "c"] as $tag) {
                      if ($tag === "a") {
                        $a = true;
                        break;
                      }

                      if ($tag === "b") {
                        $a = true;
                        continue;
                      }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'falseToBoolInContinueAndBreak' => [
                '<?php
                    $a = false;

                    for ($i = 0; $i < 4; $i++) {
                      $j = rand(0, 10);

                      if ($j === 1) {
                        exit();
                      }

                      if ($j === 2) {
                        $a = true;
                        continue;
                      }

                      if ($j === 3) {
                        $a = true;
                        break;
                      }
                    }',
                'assignments' => [
                    '$a' => 'bool',
                ],
            ],
            'variableDefinedInForeachAndIf' => [
                '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                        } else {
                            $a = false;
                        }

                        echo $a;
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
            'possiblyUndefinedArrayInForeach' => [
                '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $array[] = "hello";
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedVariable - src/somefile.php:3 - Possibly undefined variable ' .
                    '$array, first seen on line 3',
            ],
            'possiblyUndefinedArrayInWhileAndForeach' => [
                '<?php
                    for ($i = 0; $i < 4; $i++) {
                        while (rand(0,10) === 5) {
                            $array[] = "hello";
                        }
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedVariable - src/somefile.php:4 - Possibly undefined variable ' .
                    '$array, first seen on line 4',
            ],
            'possiblyUndefinedVariableInForeach' => [
                '<?php
                    foreach ([1, 2, 3, 4] as $b) {
                        $car = "Volvo";
                    }

                    echo $car;',
                'error_message' => 'PossiblyUndefinedVariable - src/somefile.php:6 - Possibly undefined variable ' .
                    '$car, first seen on line 3',
            ],
            'possibleUndefinedVariableInForeachAndIf' => [
                '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                            break;
                        }
                    }

                    echo $a;',
                'error_message' => 'PossiblyUndefinedVariable - src/somefile.php:9 - Possibly undefined variable $a, ' .
                    'first seen on line 4',
            ],
            'possibleUndefinedVariableInForeachAndIf' => [
                '<?php
                    foreach ([1,2,3,4] as $i) {
                        if ($i === 1) {
                            $a = true;
                        }

                        echo $a;
                    }',
                'error_message' => 'PossiblyUndefinedVariable - src/somefile.php:7 - Possibly undefined variable $a, ' .
                    'first seen on line 4',
            ],
            'implicitFourthLoopWithBadReturnType' => [
                '<?php
                    function test(): int {
                      $x = 0;
                      $y = 1;
                      $z = 2;
                      foreach ([0, 1, 2] as $i) {
                        $x = $y;
                        $y = $z;
                        $z = "hello";
                      }
                      return $x;
                    }',
                'error_message' => 'InvalidReturnType',
            ],
            'possiblyNullCheckInsideForeachWithNoLeaveStatement' => [
                '<?php
                    class A {
                        /** @return array<A|null> */
                        public static function loadMultiple()
                        {
                            return [new A, null];
                        }

                        /** @return void */
                        public function barBar() {

                        }
                    }

                    foreach (A::loadMultiple() as $a) {
                        if ($a === null) {
                            // do nothing
                        }

                        $a->barBar();
                    }',
                'error_message' => 'PossiblyNullReference',
            ],
        ];
    }
}
