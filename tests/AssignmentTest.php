<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\IssueBuffer;
use Psalm\Tests\Traits\InvalidCodeAnalysisTestTrait;
use Psalm\Tests\Traits\ValidCodeAnalysisTestTrait;

use function array_filter;
use function array_map;
use function array_values;

final class AssignmentTest extends TestCase
{
    use InvalidCodeAnalysisTestTrait;
    use ValidCodeAnalysisTestTrait;

    public function testReassignedVariableKeepsTheMixedAssignmentTheIfDoesNotCheck(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param list $items */
                function reassigned(array $items): string {
                    $a = $items[0];
                    echo (string) $a;
                    $a = $items[1];
                    if (!is_string($a)) { $a = ""; }
                    return $a;
                }',
        );

        $this->analyzeFile($file_path, new Context());

        $mixed_assignment_lines = [];

        foreach (IssueBuffer::getIssuesDataForFile($file_path) as $issue) {
            if ($issue->type === 'MixedAssignment') {
                $mixed_assignment_lines[] = $issue->line_from;
            }
        }

        // the assignment the if checks is not reported, the one used as mixed before it is
        self::assertSame([4], $mixed_assignment_lines);
    }

    public function testMixedAssignmentTypedOnEveryWayThroughTheFollowingIfIsNotReported(): void
    {
        $this->testConfig->throw_exception = false;
        $file_path = self::$src_dir_path . 'somefile.php';

        $this->addFile(
            $file_path,
            '<?php
                /** @param non-empty-list $items */
                function firstName(array $items): string {
                    $name = $items[0];

                    if (!is_string($name)) {
                        $name = "";
                    }

                    return $name;
                }

                function setOption(string $name): void {}

                /** @param mixed $choice */
                function chosen($choice): void {
                    $option = $choice;

                    if ($option !== "none") {
                        setOption($option);
                    }
                }

                /** @param non-empty-list $items */
                function firstTitle(array $items): string {
                    $title = $items[0];

                    if (rand(0, 1)) {
                        $title = "";
                    }

                    return (string) $title;
                }',
        );

        $this->analyzeFile($file_path, new Context());

        $lines = array_map(
            static fn(IssueData $issue): int => $issue->line_from,
            array_values(array_filter(
                IssueBuffer::getIssuesDataForFile($file_path),
                static fn(IssueData $issue): bool => $issue->type === 'MixedAssignment',
            )),
        );

        // $option is only typed by its use as an argument, and $title stays mixed on one way through its if
        self::assertSame([17, 26], $lines);
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
            'mixedAssignmentLeftMixedByTheFollowingIf' => [
                'code' => '<?php
                    /** @param non-empty-list $items */
                    function firstName(array $items): string {
                        $name = $items[0];

                        if (rand(0, 1)) {
                            $name = "";
                        }

                        return (string) $name;
                    }',
                'error_message' => 'MixedAssignment',
            ],
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
