<?php

namespace Psalm\Tests;

use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

class AssignmentTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return iterable<string,array{string,assertions?:array<string,string>,error_levels?:string[]}>
     */
    public function providerValidCodeParse(): iterable
    {
        return [
            'nestedAssignment' => [
                '<?php
                    $a = $b = $c = 5;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'assignmentInByRefParams' => [
                '<?php
                    function foo(?string $s, ?string $t): void {}
                    foo($s = null, $t = null);
                    echo $s;
                    echo $t;

                    function foo2(?string &$u, ?string &$v): void {}
                    foo2($u = null, $v = null);
                    echo $u;
                    echo $v;

                    $read = [fopen(\'php://stdin\', \'rb\')];
                    $return = stream_select($read, $w = null, $e = null, 0);
                    echo $w;
                    echo $e;',
            ],
            'bitwiseAssignment' => [
                '<?php
                    $x = 0;
                    $x |= (int) (rand(0, 2) !== 2);
                    $x |= 1;
                    /** @psalm-suppress RedundantCondition Psalm now knows this is always truthy */
                    if ($x) {
                        echo $x;
                    }',
            ],
            'ifAssignment' => [
                '<?php
                    if ($foo = rand(0, 1)) {
                        echo $foo;
                    }',
            ],
            'explicitlyTypedMixedAssignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    /** @var mixed */
                    $b = $a;',
            ],
            'referenceAssignmentArray' => [
                '<?php
                    $matrix = [
                      [1, 0],
                      [0, 1],
                    ];
                    $row =& $matrix[0];
                    echo $row[0];',
            ],
            'referenceAssignmentLhs' => [
                '<?php
                    $a = 1;
                    $b =& $a;
                    echo $b;',
            ],
            'referenceAssignmentRhs' => [
                '<?php
                    $a = 1;
                    $b =& $a;
                    echo $a;',
            ],
            'chainedAssignmentUncomplicated' => [
                '<?php
                    $a = $b = $c = $d = $e = $f = $g = $h = $i = $j = $k = $l = $m
                       = $n = $o = $p = $q = $r = $s = $t = $u = $v = $w = $x = $y
                       = $z = $A = $B = 0;',
                [
                    '$a' => 'int',
                    '$B' => 'int',
                ]
            ],
        ];
    }

    /**
     * @return iterable<string,array{string,error_message:string,1?:string[],2?:bool,3?:string}>
     */
    public function providerInvalidCodeParse(): iterable
    {
        return [
            'mixedAssignment' => [
                '<?php
                    /** @var mixed */
                    $a = 5;
                    $b = $a;',
                'error_message' => 'MixedAssignment',
            ],
        ];
    }
}
