<?php

declare(strict_types=1);

namespace Psalm\Tests\Loop;

use Psalm\Tests\TestCase;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use const DIRECTORY_SEPARATOR;

class ForTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function providerValidCodeParse(): iterable
    {
        return [
            'forTrue' => [
                'code' => '<?php
                    function ret(): int {
                        for (;;) {
                            return 1;
                        }
                    }',
            ],
            'implicitFourthLoop' => [
                'code' => '<?php
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
            'falseToBoolInContinueAndBreak' => [
                'code' => '<?php
                    $a = false;

                    for ($i = 0; $i < 4; $i++) {
                      $j = rand(0, 10);

                      if ($j === 2) {
                        $a = true;
                        continue;
                      }

                      if ($j === 3) {
                        $a = true;
                        break;
                      }
                    }',
                'assertions' => [
                    '$a' => 'bool',
                ],
            ],
            'forLoopwithOKChange' => [
                'code' => '<?php
                    $j = 5;
                    for ($i = $j; $i < 4; $i++) {
                      $j = 9;
                    }',
            ],
            'preventNegativeZeroScrewingThingsUp' => [
                'code' => '<?php
                    function foo() : void {
                      /** @var array<int, int> $v */
                      $v = [1 => 0];
                      for ($d = 0; $d <= 10; $d++) {
                        for ($k = -$d; $k <= $d; $k += 2) {
                          if ($k === -$d || ($k !== $d && $v[$k-1] < $v[$k+1])) {
                            $x = $v[$k+1];
                          } else {
                            $x = $v[$k-1] + 1;
                          }

                          $v[$k] = $x;
                        }
                      }
                    }',
            ],
            'whileTrueWithBreak' => [
                'code' => '<?php
                    for (;;) {
                        $a = "hello";
                        break;
                    }
                    for (;;) {
                        $b = 5;
                        break;
                    }',
                'assertions' => [
                    '$a' => 'string',
                    '$b' => 'int',
                ],
            ],
            'continueOutsideLoop' => [
                'code' => '<?php
                    class Node {
                        /** @var Node|null */
                        public $next;
                    }

                    /** @return void */
                    function test(Node $head) {
                        for ($node = $head; $node; $node = $next) {
                            $next = $node->next;
                            $node->next = null;
                        }
                    }',
            ],
            'echoAfterFor' => [
                'code' => '<?php
                    for ($i = 0; $i < 5; $i++);
                    echo $i;',
            ],
            'nestedEchoAfterFor' => [
                'code' => '<?php
                    for ($i = 1; $i < 2; $i++) {
                        for ($j = 1; $j < 2; $j++) {}
                    }

                    echo $i * $j;',
            ],
            'reconcileOuterVars' => [
                'code' => '<?php
                    for ($i = 0; $i < 2; $i++) {
                        if ($i === 0) {
                            continue;
                        }
                    }',
            ],
            'noException' => [
                'code' => '<?php
                    /**
                     * @param list<int> $arr
                     */
                    function cartesianProduct(array $arr) : void {
                        for ($i = 20; $arr[$i] === 5 && $i > 0; $i--) {}
                    }',
            ],
            'noCrashOnLongThing' => [
                'code' => '<?php
                    /**
                     * @param list<array{a: array{int, int}}> $data
                     */
                    function makeData(array $data) : array {
                        while (rand(0, 1)) {
                            while (rand(0, 1)) {
                                while (rand(0, 1)) {
                                    if (rand(0, 1)) {
                                        continue;
                                    }

                                    /** @psalm-suppress PossiblyUndefinedArrayOffset */
                                    $data[0]["a"] = array_merge($data[0]["a"], $data[0]["a"]);
                                }
                            }
                        }

                        return $data;
                    }',
            ],
            'InfiniteForLoop' => [
                'code' => '<?php
                    /**
                     * @return int
                     */
                    function g() {
                        for (;;) {
                            return 1;
                        }
                    }

                    /**
                     * @return int
                     */
                    function h() {
                        for (;1;) {
                            return 1;
                        }
                    }',
            ],
        ];
    }

    public function providerInvalidCodeParse(): iterable
    {
        return [
            'possiblyUndefinedArrayInWhileAndForeach' => [
                'code' => '<?php
                    for ($i = 0; $i < 4; $i++) {
                        while (rand(0,10) === 5) {
                            $array[] = "hello";
                        }
                    }

                    echo $array;',
                'error_message' => 'PossiblyUndefinedGlobalVariable - src' . DIRECTORY_SEPARATOR . 'somefile.php:4:29 - Possibly undefined ' .
                    'global variable $array, first seen on line 4',
            ],
            'forLoopInvalidation' => [
                'code' => '<?php
                    for ($i = 0; $i < 4; $i++) {
                      foreach ([1, 2, 3] as $i) {}
                    }',
                'error_message' => 'LoopInvalidation',
            ],
            'forInfiniteNoBreak' => [
                'code' => '<?php
                    for (;;) {
                        $a = "hello";
                    }

                    echo $a;',
                'error_message' => 'UndefinedGlobalVariable',
            ],
            'nestedEchoAfterFor' => [
                'code' => '<?php
                    for ($i = 1; $i < 2; $i++) {
                        if (rand(0, 1)) break;
                        for ($j = 1; $j < 2; $j++) {}
                    }

                    echo $i * $j;',
                'error_message' => 'PossiblyUndefinedGlobalVariable',
            ],
        ];
    }
}
