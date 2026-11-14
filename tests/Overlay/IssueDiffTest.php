<?php

declare(strict_types=1);

namespace Psalm\Tests\Overlay;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Overlay\IssueDiff;

use function array_map;
use function str_repeat;
use function substr;

/**
 * @psalm-import-type IssueArray from IssueDiff
 */
final class IssueDiffTest extends TestCase
{
    private const FILE = '/project/src/A.php';

    /**
     * @param list<IssueArray> $base
     * @param list<IssueArray> $overlay
     * @param array{string, string} $contents the overlaid file without and with the overlay
     * @param array<string, array<string, array{o: int, s: list<string>}>> $baseline
     * @param list<string> $expected "severity type message @offset" of the new issues
     */
    #[DataProvider('provideCases')]
    #[DataProvider('provideMessageCases')]
    public function testNewIssues(array $base, array $overlay, array $contents, array $baseline, array $expected): void
    {
        $new_issues = IssueDiff::getNewIssues(
            $base,
            $overlay,
            [self::FILE => $contents],
            $baseline,
            static fn(): bool => true,
        );

        $this->assertSame(
            $expected,
            array_map(
                static fn(array $issue): string => $issue['severity'] . ' ' . $issue['type'] . ' '
                    . $issue['message'] . ' @' . $issue['from'],
                $new_issues,
            ),
        );
    }

    /**
     * @return iterable<string, array{list<IssueArray>, list<IssueArray>, array{string, string},
     *     array<string, array<string, array{o: int, s: list<string>}>>, list<string>}>
     * @psalm-pure
     */
    public static function provideCases(): iterable
    {
        $old = str_repeat('x', 100);

        yield 'an issue the lines moved is not new' => [
            [self::issue('InvalidReturnStatement', 'bad', 50)],
            [self::issue('InvalidReturnStatement', 'bad', 60)],
            [$old, str_repeat('y', 10) . $old],
            [],
            [],
        ];

        yield 'an issue the edit rewrote is not new' => [
            [self::issue('InvalidReturnStatement', 'bad', 50, 'a')],
            [self::issue('InvalidReturnStatement', 'bad', 50, 'b')],
            [$old, substr($old, 0, 45) . 'zzzzzzzzzz' . substr($old, 55)],
            [],
            [],
        ];

        yield 'an edit inside a baselined snippet introduces nothing' => [
            [self::issue('InvalidReturnStatement', 'bad', 50, 'return "a";')],
            [self::issue('InvalidReturnStatement', 'bad', 50, 'return "b";')],
            [$old, substr($old, 0, 45) . 'zzzzzzzzzz' . substr($old, 55)],
            ['src/A.php' => ['InvalidReturnStatement' => ['o' => 1, 's' => ['return "a";']]]],
            [],
        ];

        yield 'one more occurrence is new, the one that was not there' => [
            [self::issue('InvalidReturnStatement', 'bad', 10)],
            [self::issue('InvalidReturnStatement', 'bad', 10), self::issue('InvalidReturnStatement', 'bad', 80)],
            [$old, substr($old, 0, 70) . 'zz' . substr($old, 72)],
            [],
            ['error InvalidReturnStatement bad @80'],
        ];

        yield 'an issue that moved inside the edit is not new' => [
            [self::issue('InvalidReturnStatement', 'bad', 20)],
            [self::issue('InvalidReturnStatement', 'bad', 40)],
            [$old, substr($old, 0, 10) . str_repeat('z', 50) . substr($old, 60)],
            [],
            [],
        ];

        yield 'the baseline covers what was there first' => [
            [self::issue('InvalidReturnStatement', 'old', 10, 'a')],
            [self::issue('InvalidReturnStatement', 'old', 10, 'a'), self::issue('InvalidReturnStatement', 'new', 80, 'b')],
            [$old, $old],
            ['src/A.php' => ['InvalidReturnStatement' => ['o' => 1, 's' => ['a', 'b']]]],
            // the count of the entry is spent on the old issue: the snippet list no longer matches
            ['error InvalidReturnStatement new @80'],
        ];

        yield 'a new issue takes a spare baseline entry' => [
            [self::issue('InvalidReturnStatement', 'old', 10, 'a')],
            [self::issue('InvalidReturnStatement', 'old', 10, 'a'), self::issue('InvalidReturnStatement', 'new', 80, 'b')],
            [$old, $old],
            ['src/A.php' => ['InvalidReturnStatement' => ['o' => 2, 's' => ['a', 'b']]]],
            ['info InvalidReturnStatement new @80'],
        ];

        yield 'a new issue never takes the entry of an old one' => [
            [self::issue('InvalidReturnStatement', 'old', 90, 'a')],
            [self::issue('InvalidReturnStatement', 'new', 10, 'a'), self::issue('InvalidReturnStatement', 'old', 90, 'a')],
            [$old, $old],
            ['src/A.php' => ['InvalidReturnStatement' => ['o' => 1, 's' => ['a']]]],
            ['error InvalidReturnStatement new @10'],
        ];

        yield 'bookkeeping issues are never new' => [
            [],
            [self::issue('UnusedPsalmSuppress', 'unused', 10), self::issue('UnusedBaselineEntry', 'unused', 0)],
            [$old, $old],
            [],
            [],
        ];
    }

    /**
     * Positions a message quotes, and what only looks like one
     *
     * @return iterable<string, array{list<IssueArray>, list<IssueArray>, array{string, string},
     *     array<string, array<string, array{o: int, s: list<string>}>>, list<string>}>
     * @psalm-pure
     */
    public static function provideMessageCases(): iterable
    {
        $old = str_repeat('x', 100);

        yield 'a line quoted in the message does not make it new' => [
            [self::issue('PossiblyUndefinedVariable', 'Possibly undefined variable $a, first seen on line 3', 50)],
            [self::issue('PossiblyUndefinedVariable', 'Possibly undefined variable $a, first seen on line 5', 60)],
            [$old, "\n\n" . str_repeat('y', 8) . $old],
            [],
            [],
        ];

        yield 'another variable is new' => [
            [self::issue('PossiblyUndefinedVariable', 'Possibly undefined variable $a, first seen on line 3', 50)],
            [self::issue('PossiblyUndefinedVariable', 'Possibly undefined variable $b, first seen on line 3', 50)],
            [$old, $old],
            [],
            ['error PossiblyUndefinedVariable Possibly undefined variable $b, first seen on line 3 @50'],
        ];

        yield 'a line quoted for a global variable does not make it new' => [
            [self::issue('PossiblyUndefinedGlobalVariable', 'Possibly undefined global variable $a, first seen on line 3', 50)],
            [self::issue('PossiblyUndefinedGlobalVariable', 'Possibly undefined global variable $a, first seen on line 4', 60)],
            [$old, "\n" . str_repeat('y', 9) . $old],
            [],
            [],
        ];

        yield 'a literal that reads like a line is not a position' => [
            [self::issue('InvalidArgument', "Argument 1 of need expects 'expected', but 'first seen on line 3' provided", 50)],
            [self::issue('InvalidArgument', "Argument 1 of need expects 'expected', but 'first seen on line 4' provided", 50)],
            [$old, $old],
            [],
            ["error InvalidArgument Argument 1 of need expects 'expected', but 'first seen on line 4' provided @50"],
        ];

        yield 'a parse error the lines moved is not new' => [
            [self::issue('ParseError', "Syntax error, unexpected '}' on line 6", 50)],
            [self::issue('ParseError', "Syntax error, unexpected '}' on line 7", 60)],
            [$old, "\n" . str_repeat('y', 9) . $old],
            [],
            [],
        ];

        yield 'an anonymous class the lines moved is not new' => [
            [self::issue('UndefinedMethod', 'Method App\_project_src_A_php_3_40::f does not exist', 50)],
            [self::issue('UndefinedMethod', 'Method App\_project_src_A_php_4_50::f does not exist', 60)],
            [$old, "\n" . str_repeat('y', 9) . $old],
            [],
            [],
        ];

        yield 'a class named like an anonymous class is new' => [
            [self::issue('UndefinedClass', 'Class App\foo_php_1_2 does not exist', 50)],
            [self::issue('UndefinedClass', 'Class App\foo_php_1_3 does not exist', 50)],
            [$old, $old],
            [],
            ['error UndefinedClass Class App\foo_php_1_3 does not exist @50'],
        ];
    }

    #[DataProvider('provideMessages')]
    public function testMessageNormalization(string $type, string $message, string $expected): void
    {
        $this->assertSame(
            $expected,
            IssueDiff::normalizeMessage($type, $message, IssueDiff::getGeneratedNames([self::FILE])),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     * @psalm-pure
     */
    public static function provideMessages(): iterable
    {
        yield 'first seen on line' => [
            'PossiblyUndefinedVariable',
            'Possibly undefined variable $a, first seen on line 12',
            'Possibly undefined variable $a, first seen on line #',
        ];

        yield 'closure id' => [
            'MissingClosureReturnType',
            'Closure /project/src/a.php:12:345:-:closure has no return type',
            'Closure /project/src/a.php:#:#:-:closure has no return type',
        ];

        yield 'closure of a file no issue is in' => [
            'MissingClosureReturnType',
            'Closure /project/src/b.php:12:345:-:closure has no return type',
            'Closure /project/src/b.php:12:345:-:closure has no return type',
        ];

        yield 'anonymous class' => [
            'UnusedClass',
            'Class App\_project_src_A_php_12_345 is never used',
            'Class App\_project_src_A_php_#_# is never used',
        ];

        yield 'anonymous class in lower case' => [
            'UndefinedMethod',
            'Method app\_project_src_a_php_12_345::f does not exist',
            'Method app\_project_src_a_php_#_#::f does not exist',
        ];

        yield 'class named like an anonymous class' => [
            'UndefinedClass',
            'Class App\foo_php_1_2 does not exist',
            'Class App\foo_php_1_2 does not exist',
        ];

        yield 'line of a parse error' => [
            'ParseError',
            "Syntax error, unexpected '}' on line 6",
            "Syntax error, unexpected '}' on line #",
        ];

        yield 'unknown line of a parse error' => [
            'ParseError',
            'Syntax error, unexpected EOF on unknown line',
            'Syntax error, unexpected EOF on line #',
        ];

        yield 'a line in another message stays' => [
            'InvalidArgument',
            'Argument 1 of f expects "a on line 6", but "b on line 6" provided',
            'Argument 1 of f expects "a on line 6", but "b on line 6" provided',
        ];

        yield 'other numbers stay' => [
            'InvalidArgument',
            'Argument 1 of f expects int<0, 12>, but 13 provided',
            'Argument 1 of f expects int<0, 12>, but 13 provided',
        ];
    }

    /**
     * @return IssueArray
     * @psalm-pure
     */
    private static function issue(string $type, string $message, int $from, string $selected_text = 'x'): array
    {
        return [
            'severity' => 'error',
            'type' => $type,
            'message' => $message,
            'file_name' => 'src/A.php',
            'file_path' => self::FILE,
            'selected_text' => $selected_text,
            'from' => $from,
            'line_from' => 1,
        ];
    }
}
