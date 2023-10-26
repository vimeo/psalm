<?php

declare(strict_types=1);

namespace Psalm\Tests\Loop;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class DoTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'doWhileTrue' => [
                'code' => '<?php
                    function ret(): int {
                        do {
                            return 1;
                        } while (true);
                    }',
            ],
            'doWhileVar' => [
                'code' => '<?php
                    $worked = false;

                    do {
                        $worked = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$worked' => 'true',
                ],
            ],
            'doWhileVarWithPossibleBreak' => [
                'code' => '<?php
                    $a = false;

                    do {
                        if (rand(0, 1)) {
                            break;
                        }
                        if (rand(0, 1)) {
                            $a = true;
                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'SKIPPED-doWhileVarWithPossibleBreakThatSetsToTrue' => [
                'code' => '<?php
                    $a = false;
                    $b = false;

                    do {
                        $b = true;
                        if (rand(0, 1)) {
                            $a = true;
                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,1));',
                'assertions' => [
                    '$a' => 'true',
                    '$b' => 'true',
                ],
            ],
            'doWhileVarWithPossibleBreakThatMaybeSetsToTrue' => [
                'code' => '<?php
                    $a = false;

                    do {
                        if (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                            }

                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,1));',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'doWhileVarWithPossibleInitialisingBreakNoInitialDefinition' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            $worked = true;
                            break;
                        }
                        $worked = true;
                    }
                    while (rand(0,100) === 10);',
                'assertions' => [
                    '$worked' => 'true',
                ],
            ],
            'doWhileUndefinedVar' => [
                'code' => '<?php
                    do {
                        $result = (bool) rand(0,1);
                    } while (!$result);',
                'assertions' => [
                    '$result' => 'true',
                ],
            ],
            'doWhileVarAndBreak' => [
                'code' => '<?php
                    /** @return void */
                    function foo(string $b) {}

                    do {
                        if (null === ($a = rand(0, 1) ? "hello" : null)) {
                            break;
                        }

                        /** @psalm-suppress MixedArgument */
                        foo($a);
                    }
                    while (rand(0,100) === 10);',
            ],
            'doWhileWithNotEmptyCheck' => [
                'code' => '<?php
                    class A {
                        /** @var A|null */
                        public $a;

                        public function __construct() {
                            $this->a = rand(0, 1) ? new A : null;
                        }
                    }

                    function takesA(A $a): void {}

                    $a = new A();
                    do {
                        takesA($a);
                        $a = $a->a;
                    } while ($a);',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'doWhileWithMethodCall' => [
                'code' => '<?php
                    class A {
                        public function getParent(): ?A {
                            return rand(0, 1) ? new A() : null;
                        }
                    }

                    $a = new A();

                    do {
                        $a = $a->getParent();
                    } while ($a);',
                'assertions' => [
                    '$a' => 'null',
                ],
            ],
            'doWhileFirstGood' => [
                'code' => '<?php
                    do {
                        $done = rand(0, 1) > 0;
                    } while (!$done);',
            ],
            'doWhileWithIfException' => [
                'code' => '<?php
                    class A
                    {
                        /**
                         * @var null|A
                         */
                        public $parent;

                        public static function foo(A $a) : void
                        {
                            do {
                                if ($a->parent === null) {
                                    throw new \Exception("bad");
                                }

                                $a = $a->parent;
                            } while (rand(0,1));
                        }
                    }',
            ],
            'doWhileWithIfExceptionOutside' => [
                'code' => '<?php
                    class A
                    {
                        /**
                         * @var null|A
                         */
                        public $parent;

                        public static function foo(A $a) : void
                        {
                            if ($a->parent === null) {
                                throw new \Exception("bad");
                            }

                            do {
                                $a = $a->parent;
                            } while ($a->parent && rand(0, 1));
                        }
                    }',
            ],
            'doWhileDefinedVar' => [
                'code' => '<?php
                    $value = null;
                    do {
                        $count = rand(0, 1);
                        $value = 6;
                    } while ($count);',
            ],
            'doWhileDefinedVarWithPossibleBreak' => [
                'code' => '<?php
                    $value = null;
                    do {
                        if (rand(0, 1)) {
                            break;
                        }
                        $count = rand(0, 1);
                        $value = 6;
                    } while ($count);',
            ],
            'invalidateBothByRefAssignmentsInDo' => [
                'code' => '<?php
                    function foo(?string &$i) : void {}
                    function bar(?string &$i) : void {}

                    $c = null;

                    do {
                        if (!$c) {
                            foo($c);
                        } else {
                            bar($c);
                        }
                    } while (rand(0, 1));',
            ],
            'doParentCall' => [
                'code' => '<?php
                    class A {
                        /** @return A|false */
                        public function getParent() {
                            return rand(0, 1) ? new A : false;
                        }
                    }

                    $a = new A();

                    do {
                        $a = $a->getParent();
                    } while ($a !== false);',
            ],
            'doCallInWhile' => [
                'code' => '<?php
                    class A {
                        public function getParent() : ?A {
                            return rand(0, 1) ? new A : null;
                        }
                    }

                    $a = new A();
                    $i = 0;
                    do {
                        $i++;
                    } while ($a = $a->getParent());',
            ],
            'doWithContinue' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            continue;
                        }
                    } while (rand(0, 1));',
            ],
            'noEmptyArrayAccessComplaintInsideDo' => [
                'code' => '<?php
                    $foo = [];
                    do {
                        if (isset($foo["bar"])) {}
                        $foo["bar"] = "bat";
                    } while (rand(0, 1));',
            ],
            'noRedundantConditionAfterDoWhile' => [
                'code' => '<?php
                    $i = 5;
                    do {} while (--$i > 0);
                    echo $i === 0;',
            ],
            'doWhileNonInfinite' => [
                'code' => '<?php
                    function foo(): int {
                        do {
                            $value = mt_rand(0, 10);
                            if ($value > 5) continue;
                            break;
                        } while (true);

                        return $value;
                    }',
            ],
            'doNoRedundant' => [
                'code' => '<?php
                    class Event {}

                    function fetchEvent(): ?Event {
                        return rand(0, 1) ? new Event() : null;
                    }

                    function nextEvent(bool $c): void {
                        do {
                            $e = fetchEvent();
                        } while ($c && $e);
                    }',
            ],
            'doConditionInWhileAndIfWithSingleVar' => [
                'code' => '<?php
                    $b = !!rand(0, 1);

                    do {
                        if (!$b) {
                           $b = !rand(0, 1);
                        }
                    } while (!$b);',
                'assertions' => [
                    '$b' => 'true',
                ],
            ],
            'doConditionInWhileAndIfWithTwoVars' => [
                'code' => '<?php
                    $b = !!rand(0, 1);

                    do {
                        $s = rand(0, 1);
                        if (!$b && $s) {}
                    } while (!$b && $s);

                    if ($b) {}',
            ],
            'regularAssignmentInsideDo' => [
                'code' => '<?php
                    do {
                        $code = rand(0, 1);
                        echo "here";
                    } while ($code === 1);',
            ],
            'destructuringAssignmentInsideDo' => [
                'code' => '<?php
                    do {
                        [$code] = [rand(0, 1)];
                        echo "here";
                    } while ($code === 1);',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'doWhileVarWithPossibleBreakWithoutDefining' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            break;
                        }
                        $worked = true;
                    }
                    while (rand(0,1));

                    echo $worked;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'doWhileVarWithPossibleBreakThatMaybeSetsToTrueWithoutDefining' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            if (rand(0, 1)) {
                                $a = true;
                            }

                            break;
                        }
                        $a = true;
                    }
                    while (rand(0,1));

                    echo $a;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'SKIPPED-doWhileVarWithPossibleContinueWithoutDefining' => [
                'code' => '<?php
                    do {
                        if (rand(0, 1)) {
                            continue;
                        }
                        $worked = true;
                    }
                    while (rand(0,1));

                    echo $worked;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
            'possiblyUndefinedArrayInDo' => [
                'code' => '<?php
                    do {
                        $array[] = "hello";
                    } while (rand(0, 1));',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3:25 - Possibly undefined ' .
                    'global variable $array, first seen on line 3',
            ],
        ];
    }
}
