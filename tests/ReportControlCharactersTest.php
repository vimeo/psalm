<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report;
use Psalm\Report\ByIssueLevelAndTypeReport;
use Psalm\Report\CompactReport;
use Psalm\Report\ConsoleReport;
use Psalm\Report\EmacsReport;
use Psalm\Report\GithubActionsReport;
use Psalm\Report\JsonReport;
use Psalm\Report\ReportOptions;
use Psalm\Report\TextReport;

use function json_decode;
use function preg_match;

final class ReportControlCharactersTest extends TestCase
{
    private const DANGEROUS = "\e[2K\r\x7F\xC2\x9B\xE2\x80\xAEgnirts\xE2\x80\xAC\x00";

    private const FORBIDDEN = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|\xC2[\x80-\x9F]|\xE2\x80[\x8E\x8F\xAA-\xAE]/';

    /**
     * @return iterable<string, array{Closure(list<IssueData>, ReportOptions): Report}>
     * @psalm-pure
     */
    public static function humanReports(): iterable
    {
        yield 'console' => [/** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new ConsoleReport($i, [], $o)];
        yield 'compact' => [/** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new CompactReport($i, [], $o)];
        yield 'text' => [/** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new TextReport($i, [], $o)];
        yield 'emacs' => [/** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new EmacsReport($i, [], $o)];
        yield 'github' => [/** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new GithubActionsReport($i, [], $o)];
        yield 'by-issue-level' => [
            /** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new ByIssueLevelAndTypeReport($i, [], $o),
        ];
    }

    /**
     * @param Closure(list<IssueData>, ReportOptions): Report $create_report
     */
    #[DataProvider('humanReports')]
    public function testHumanReadableReportsEscapeWhatTheCodeHolds(Closure $create_report): void
    {
        $options = new ReportOptions();
        $options->use_color = false;
        $options->show_snippet = true;

        $output = $create_report([$this->issue()], $options)->create();

        self::assertSame(0, preg_match(self::FORBIDDEN, $output));
        self::assertStringContainsString('\u{001B}[2K\u{000D}\u{007F}\u{009B}\u{202E}gnirts\u{202C}\u{0000}', $output);
    }

    /**
     * @return iterable<string, array{Closure(list<IssueData>, ReportOptions): Report, bool, string}>
     * @psalm-pure
     */
    public static function plainOutputs(): iterable
    {
        $link = ' (see https://psalm.dev/012)';

        yield 'console' => [
            /** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new ConsoleReport($i, [], $o),
            false,
            "ERROR: SomeIssue - src/file.php:3:5 - a message$link\nfoo(bar);\n\n",
        ];
        yield 'console in color' => [
            /** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new ConsoleReport($i, [], $o),
            true,
            "\e[0;31mERROR\e[0m: SomeIssue - src/\e[1;31mfile.php:3:5\e[0m - a message$link\n"
                . "foo(\e[97;41mbar\e[0m);\n\n\n",
        ];
        yield 'compact' => [
            /** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new CompactReport($i, [], $o),
            false,
            "ERROR src/file.php:3:5 SomeIssue: a message\n",
        ];
        yield 'text' => [
            /** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new TextReport($i, [], $o),
            false,
            "/p/src/file.php:3:5:error - SomeIssue: a message\n",
        ];
        yield 'emacs' => [
            /** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new EmacsReport($i, [], $o),
            false,
            "/p/src/file.php:3:5:error - SomeIssue: a message$link\n",
        ];
        yield 'github' => [
            /** @param array<int, IssueData> $i */ static fn(array $i, ReportOptions $o): Report => new GithubActionsReport($i, [], $o),
            false,
            "::error file=src/file.php,line=3,col=5,title=SomeIssue::src/file.php:3:5: SomeIssue: a message$link\n",
        ];
    }

    /**
     * @param Closure(list<IssueData>, ReportOptions): Report $create_report
     */
    #[DataProvider('plainOutputs')]
    public function testTheFormatOfAnIssueWithoutControlCharactersIsUnchanged(
        Closure $create_report,
        bool $use_color,
        string $expected,
    ): void {
        $options = new ReportOptions();
        $options->use_color = $use_color;
        $options->show_snippet = true;
        $options->in_ci = true;

        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            3,
            3,
            'SomeIssue',
            'a message',
            'src/file.php',
            '/p/src/file.php',
            'foo(bar);',
            'bar',
            4,
            7,
            0,
            9,
            5,
            8,
            12,
            2,
        );

        self::assertSame($expected . $expected, $create_report([$issue, $issue], $options)->create());
    }

    public function testTheByIssueLevelReportListsEveryIssueInTheFormatOfTheConsoleReport(): void
    {
        $options = new ReportOptions();
        $options->use_color = false;
        $options->show_snippet = true;

        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            3,
            3,
            'SomeIssue',
            'a message',
            'src/file.php',
            '/p/src/file.php',
            'foo(bar);',
            'bar',
            4,
            7,
            0,
            9,
            5,
            8,
            12,
            2,
        );

        $block = "ERROR (2): SomeIssue - src/file.php:3:5 - a message (see https://psalm.dev/012)\nfoo(bar);\n\n";

        self::assertStringEndsWith(
            "\n\n" . $block . $block,
            (new ByIssueLevelAndTypeReport([$issue, $issue], [], $options))->create(),
        );
    }

    public function testTheTaintTraceOfTheConsoleReportIsEscapedAndKeepsItsFormat(): void
    {
        $node = new DataFlowNodeData(
            '$a' . self::DANGEROUS,
            3,
            3,
            'src/file.php',
            '/p/src/file.php',
            'foo(bar);',
            4,
            7,
            0,
            5,
            8,
        );
        $issue = new IssueData(
            IssueData::SEVERITY_ERROR,
            3,
            3,
            'TaintedHtml',
            'a message',
            'src/file.php',
            '/p/src/file.php',
            'foo(bar);',
            'bar',
            4,
            7,
            0,
            9,
            5,
            8,
            12,
            2,
            [$node, ['label' => '$b' . self::DANGEROUS, 'entry_path_type' => 'x'], $node],
            [$node],
            null,
            null,
            true,
        );

        $options = new ReportOptions();
        $options->use_color = false;
        $options->show_snippet = true;

        $escaped = '\u{001B}[2K\u{000D}\u{007F}\u{009B}\u{202E}gnirts\u{202C}\u{0000}';

        self::assertSame(
            "SECURITY: TaintedHtml - src/file.php:3:5 - a message (see https://psalm.dev/012)\n"
                . "  \$a$escaped - src/file.php:3:5\nfoo(bar);\n\n"
                . "  \$b$escaped\n    <no known location>\n\n"
                . "  \$a$escaped - src/file.php:3:5\nfoo(bar);\n\n"
                . "\n  \$a$escaped - src/file.php:3:5\nfoo(bar);\n\n\n\n",
            (new ConsoleReport([$issue], [], $options))->create(),
        );
    }

    public function testALoneCrIsEscapedButACrLfIsKept(): void
    {
        $options = new ReportOptions();
        $options->use_color = false;

        $output = (new TextReport([$this->issue("a\r\nb\tc\rd")], [], $options))->create();

        self::assertStringContainsString("a\r\nb\tc" . '\u{000D}' . 'd', $output);
    }

    public function testTheOwnHighlightingOfTheConsoleReportIsKept(): void
    {
        $options = new ReportOptions();
        $options->use_color = true;
        $options->show_snippet = true;
        $options->in_ci = true;

        $output = (new ConsoleReport([$this->issue()], [], $options))->create();

        self::assertStringContainsString("\e[97;41m", $output);
        self::assertStringNotContainsString("\e[2K", $output);
    }

    public function testTheJsonReportKeepsTheOriginalCharacters(): void
    {
        $options = new ReportOptions();

        $output = (new JsonReport([$this->issue()], [], $options))->create();

        /** @var list<array<string, mixed>> $decoded */
        $decoded = json_decode($output, true);

        self::assertSame(self::DANGEROUS, $decoded[0]['message']);
    }

    /**
     * @psalm-pure
     */
    private function issue(string $message = self::DANGEROUS): IssueData
    {
        return new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            'SomeIssue',
            $message,
            'src/' . self::DANGEROUS . '.php',
            '/src/' . self::DANGEROUS . '.php',
            self::DANGEROUS,
            self::DANGEROUS,
            0,
            1,
            0,
            1,
            1,
            1,
            1,
            1,
        );
    }
}
