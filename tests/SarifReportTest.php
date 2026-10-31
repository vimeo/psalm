<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\VersionUtils;
use Psalm\Report\ReportOptions;
use Psalm\Report\SarifReport;

use function define;
use function defined;
use function json_decode;

final class SarifReportTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        }

        parent::setUpBeforeClass();
    }

    public function testClampsRegionToSchemaMinimum(): void
    {
        // An issue reported at line 0 (e.g. UnusedBaselineEntry from a stale baseline)
        // must not emit startLine 0, which the SARIF schema rejects (minimum 1).
        $issue_data = new IssueData(
            IssueData::SEVERITY_ERROR,
            0,
            0,
            'UnusedBaselineEntry',
            'message',
            'file.php',
            '/',
            '',
            '',
            0,
            0,
            0,
            0,
            0,
            0,
        );

        $report = new SarifReport([$issue_data], [], new ReportOptions());

        $this->assertStringContainsString(
            '"region":{"startLine":1,"endLine":1,"startColumn":1,"endColumn":1}',
            $report->create(),
        );
    }

    /**
     * @return iterable<string, array{string, int, ?string}>
     * @psalm-pure
     */
    public static function ruleProperties(): iterable
    {
        yield 'a critical sink' => ['TaintedShell', 246, '9.8'];
        yield 'a high one' => ['TaintedFile', 100, '7.5'];
        yield 'a medium one' => ['TaintedHeader', 101, '6.1'];
        yield 'a security issue without a score of its own' => ['TaintedCustom', 102, '7.0'];
        yield 'an issue that is not about security' => ['MissingReturnType', 103, null];
    }

    #[DataProvider('ruleProperties')]
    public function testTheRulesOfSecurityIssuesCarryASecuritySeverity(
        string $type,
        int $shortcode,
        ?string $expected_severity,
    ): void {
        $issue_data = new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            $type,
            'message',
            'file.php',
            '/',
            '',
            '',
            0,
            0,
            0,
            0,
            1,
            1,
            $shortcode,
        );

        /** @var array{runs: list<array{tool: array{driver: array{rules: list<array{id: string, properties: array<string, mixed>}>}}}>} $report */
        $report = json_decode((new SarifReport([$issue_data], [], new ReportOptions()))->create(), true);

        $rule = $report['runs'][0]['tool']['driver']['rules'][0];
        $properties = $rule['properties'];

        self::assertSame((string) $shortcode, $rule['id']);

        if ($expected_severity === null) {
            self::assertSame(['tags' => ['maintainability']], $properties);

            return;
        }

        self::assertSame(['tags' => ['security'], 'security-severity' => $expected_severity], $properties);
    }
}
