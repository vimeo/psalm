<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

final class AssignmentTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    /**
     * @return list<int>
     */
    private function getMixedAssignmentLines(string $code): array
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';
        $this->addFile($file_path, $code);

        $this->analyzeFile($file_path, new Context());

        $lines = [];

        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'MixedAssignment') {
                $lines[] = $issue->line_from;
            }
        }

        return $lines;
    }

    public function testMixedAssignmentIsNotReportedInASecondLoopOfAVariableAssertedInTheFirst(): void
    {
        self::assertSame(
            [],
            $this->getMixedAssignmentLines('<?php
                /** @var array<mixed> $data */
                $data = [];

                foreach ($data as $value) {
                    assert(is_array($value));
                }

                foreach ($data as $value) {
                    assert(is_array($value));
                }'),
        );
    }

    public function testMixedAssignmentIsNotReportedForAReassignedVariableAssertedAfterTheFirstAssignment(): void
    {
        self::assertSame(
            [],
            $this->getMixedAssignmentLines('<?php
                /** @var mixed $first */
                $first = 1;
                /** @var mixed $second */
                $second = 2;

                $value = $first;
                $value = $second;

                assert(is_int($value));'),
        );
    }

    public function testMixedAssignmentIsStillReportedWhenTheVariableIsNotAsserted(): void
    {
        self::assertSame(
            [9],
            $this->getMixedAssignmentLines('<?php
                /** @var array<mixed> $data */
                $data = [];

                foreach ($data as $value) {
                    assert(is_array($value));
                }

                foreach ($data as $value) {
                    echo "x";
                }'),
        );
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerValidCodeParse(): iterable
    {
        return [
            'nestedAssignment' => [
                'code' => '<?php
                    $a = $b = $c = 5;',
                'assertions' => [
                    '$a' => 'int',
                ],
            ],
            'assignmentInByRefParams' => [
                'code' => '<?php
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
                'code' => '<?php
                    $x = 0;
                    $x |= (int) (rand(0, 2) !== 2);
                    $x |= 1;
                    /** @psalm-suppress RedundantCondition Psalm now knows this is always truthy */
                    if ($x) {
                        echo $x;
                    }',
            ],
            'ifAssignment' => [
                'code' => '<?php
                    if ($foo = rand(0, 1)) {
                        echo $foo;
                    }',
            ],
            'explicitlyTypedMixedAssignment' => [
                'code' => '<?php
                    /** @var mixed */
                    $a = 5;
                    /** @var mixed */
                    $b = $a;',
            ],
            'referenceAssignmentArray' => [
                'code' => '<?php
                    $matrix = [
                      [1, 0],
                      [0, 1],
                    ];
                    $row =& $matrix[0];
                    echo $row[0];',
            ],
            'referenceAssignmentLhs' => [
                'code' => '<?php
                    $a = 1;
                    $b =& $a;
                    echo $b;',
            ],
            'referenceAssignmentRhs' => [
                'code' => '<?php
                    $a = 1;
                    $b =& $a;
                    echo $a;',
            ],
            'chainedAssignmentUncomplicated' => [
                'code' => '<?php
                    $a = $b = $c = $d = $e = $f = $g = $h = $i = $j = $k = $l = $m
                       = $n = $o = $p = $q = $r = $s = $t = $u = $v = $w = $x = $y
                       = $z = $A = $B = 0;',
                'assertions' => [
                    '$a' => 'int',
                    '$B' => 'int',
                ],
            ],
        ];
    }

    /**
     * @psalm-pure
     */
    #[Override]
    public static function providerInvalidCodeParse(): iterable
    {
        return [
            'mixedAssignment' => [
                'code' => '<?php
                    /** @var mixed */
                    $a = 5;
                    $b = $a;',
                'error_message' => 'MixedAssignment',
            ],
        ];
    }
}
