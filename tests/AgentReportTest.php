<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report;
use Psalm\Report\CompactReport;
use Psalm\Report\ConsoleReport;
use Psalm\Report\ReportOptions;

final class AgentReportTest extends TestCase
{
    public function testCompactMarksOnlyTheFixableIssuesForAnAgent(): void
    {
        $report = new CompactReport(
            [self::issue('MissingReturnType', 3, true), self::issue('InvalidReturnStatement', 5, false)],
            ['MissingReturnType' => 1],
            self::options(agent_output: true),
        );

        $this->assertSame(
            "ERROR a.php:3:1 MissingReturnType [*]: message\n"
            . "ERROR a.php:5:1 InvalidReturnStatement: message\n",
            $report->create(),
        );
    }

    public function testConsoleMarksTheFixableIssuesAndLeavesOutTheLinkForAnAgent(): void
    {
        $report = new ConsoleReport(
            [self::issue('MissingReturnType', 3, true), self::issue('InvalidReturnStatement', 5, false)],
            ['MissingReturnType' => 1],
            self::options(agent_output: true),
        );

        $this->assertSame(
            "ERROR: MissingReturnType [*] - a.php:3:1 - message\n\n\n"
            . "ERROR: InvalidReturnStatement - a.php:5:1 - message\n\n\n",
            $report->create(),
        );
    }

    #[DataProvider('humanReports')]
    public function testTheOutputOfAnyoneButAnAgentIsUnchanged(string $format, string $expected): void
    {
        $report = self::createReport(
            $format,
            [self::issue('MissingReturnType', 3, true)],
            self::options(agent_output: false),
        );

        $this->assertSame($expected, $report->create());
    }

    /**
     * @return array<string, array{string, string}>
     * @psalm-pure
     */
    public static function humanReports(): array
    {
        return [
            'compact' => [Report::TYPE_COMPACT, "ERROR a.php:3:1 MissingReturnType: message\n"],
            'console' => [
                Report::TYPE_CONSOLE,
                "ERROR: MissingReturnType - a.php:3:1 - message (see https://psalm.dev/050)\n\n\n",
            ],
        ];
    }

    #[DataProvider('humanReportFormats')]
    public function testNoSuggestionsLeavesOutTheMarkerForAnAgent(string $format): void
    {
        $issues = [self::issue('MissingReturnType', 3, true)];

        $with = self::createReport($format, $issues, self::options(agent_output: true));
        $without = self::createReport($format, $issues, self::options(agent_output: true, show_suggestions: false));

        $this->assertStringContainsString('[*]', $with->create());
        $this->assertStringNotContainsString('[*]', $without->create());
    }

    /**
     * @return array<string, array{string}>
     * @psalm-pure
     */
    public static function humanReportFormats(): array
    {
        return [
            'compact' => [Report::TYPE_COMPACT],
            'console' => [Report::TYPE_CONSOLE],
        ];
    }

    /**
     * @param list<IssueData> $issues
     * @psalm-pure
     */
    private static function createReport(string $format, array $issues, ReportOptions $options): Report
    {
        return $format === Report::TYPE_COMPACT
            ? new CompactReport($issues, [], $options)
            : new ConsoleReport($issues, [], $options);
    }

    /**
     * @psalm-pure
     */
    private static function options(bool $agent_output, bool $show_suggestions = true): ReportOptions
    {
        $options = new ReportOptions();
        $options->use_color = false;
        $options->show_snippet = false;
        $options->agent_output = $agent_output;
        $options->show_suggestions = $show_suggestions;

        return $options;
    }

    /**
     * @psalm-pure
     */
    private static function issue(string $type, int $line, bool $is_fixable): IssueData
    {
        return new IssueData(
            IssueData::SEVERITY_ERROR,
            $line,
            $line,
            $type,
            'message',
            'a.php',
            '/a.php',
            '',
            '',
            0,
            0,
            0,
            0,
            1,
            1,
            shortcode: 50,
            is_fixable: $is_fixable,
        );
    }
}
