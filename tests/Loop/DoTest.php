<?php
namespace Psalm\Tests\Loop;

use Psalm\Tests\Traits;

class DoTest extends \Psalm\Tests\TestCase
{
    use Traits\InvalidCodeAnalysisTestTrait;
    use Traits\ValidCodeAnalysisTestTrait;

    /**
     * @return array
     */
    public function providerValidCodeParse()
    {
        return [
            'doWhileVar' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    do {
                        $result = (bool) rand(0,1);
                    } while (!$result);',
                'assertions' => [
                    '$result' => 'true',
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
            'doWhileWithNotEmptyCheck' => [
                '<?php
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
                '<?php
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
                '<?php
                    do {
                        $done = rand(0, 1) > 0;
                    } while (!$done);',
            ],
            'doWhileWithIfException' => [
                '<?php
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
                '<?php
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
                '<?php
                    $value = null;
                    do {
                        $count = rand(0, 1);
                        $value = 6;
                    } while ($count);',
            ],
            'doWhileDefinedVarWithPossibleBreak' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    class A {
                        public function getParent() : ?A {
                            return rand(0, 1) ? new A : null;
                        }
                    }

                    $a = new A();
                    $i = 0;
                    do {
                        $i++;
                    } while ($a = $a->getParent());'
            ],
            'doWithContinue' => [
                '<?php
                    do {
                        if (rand(0, 1)) {
                            continue;
                        }
                    } while (rand(0, 1));',
            ],
            'noEmptyArrayAccessComplaintInsideDo' => [
                '<?php
                    $foo = [];
                    do {
                        if (isset($foo["bar"])) {}
                        $foo["bar"] = "bat";
                    } while (rand(0, 1));',
            ],
            'noRedundantConditionAfterDoWhile' => [
                '<?php
                    $i = 5;
                    do {} while (--$i > 0);
                    echo $i === 0;',
            ],
        ];
    }

    /**
     * @return array
     */
    public function providerInvalidCodeParse()
    {
        return [
            'doWhileVarWithPossibleBreakWithoutDefining' => [
                '<?php
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
                '<?php
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
                '<?php
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
                '<?php
                    do {
                        $array[] = "hello";
                    } while (rand(0, 1));

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:3 - Possibly undefined ' .
                    'global variable $array, first seen on line 3',
            ],
        ];
    }
}
