<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\VersionUtils;
use Psalm\Report\ReportOptions;
use Psalm\Report\SarifReport;

use function define;
use function defined;

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
}
