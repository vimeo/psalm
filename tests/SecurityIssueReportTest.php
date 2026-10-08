<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\VersionUtils;
use Psalm\Issue\SecurityIssue;
use Psalm\Issue\TaintedInput;
use Psalm\Report\CompactReport;
use Psalm\Report\ConsoleReport;
use Psalm\Report\ReportOptions;

use function basename;
use function define;
use function defined;
use function glob;
use function is_subclass_of;

final class SecurityIssueReportTest extends TestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        if (!defined('PSALM_VERSION')) {
            define('PSALM_VERSION', VersionUtils::getPsalmVersion());
        }

        parent::setUpBeforeClass();
    }

    public function testTaintIssuesAreSecurityIssues(): void
    {
        $this->assertTrue(is_subclass_of(TaintedInput::class, SecurityIssue::class));

        $files = glob(__DIR__ . '/../src/Psalm/Issue/Tainted*.php');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $class = 'Psalm\\Issue\\' . basename($file, '.php');

            $this->assertTrue(is_subclass_of($class, SecurityIssue::class), $class);
        }
    }

    public function testConsoleReportHeader(): void
    {
        $output = $this->createReport(ConsoleReport::class);

        $this->assertStringContainsString('SECURITY: TaintedHtml - file.php:1:1', $output);
        $this->assertStringContainsString('INFO: TaintedSql - file.php:1:1', $output);
        $this->assertStringContainsString('ERROR: InvalidArgument - file.php:1:1', $output);
    }

    public function testCompactReportHeader(): void
    {
        $output = $this->createReport(CompactReport::class);

        $this->assertStringContainsString('SECURITY file.php:1:1 TaintedHtml', $output);
        $this->assertStringContainsString('INFO file.php:1:1 TaintedSql', $output);
        $this->assertStringContainsString('ERROR file.php:1:1 InvalidArgument', $output);
    }

    /**
     * @param class-string<ConsoleReport|CompactReport> $report_class
     */
    private function createReport(string $report_class): string
    {
        $options = new ReportOptions();
        $options->use_color = false;
        $options->show_snippet = false;

        $issues_data = [

                self::createIssueData(IssueData::SEVERITY_ERROR, 'TaintedHtml', true),
                // a security issue configured as info is shown as any other info
                self::createIssueData(IssueData::SEVERITY_INFO, 'TaintedSql', true),
                self::createIssueData(IssueData::SEVERITY_ERROR, 'InvalidArgument', false),
        ];

        $report = $report_class === ConsoleReport::class
            ? new ConsoleReport($issues_data, [], $options)
            : new CompactReport($issues_data, [], $options);

        return $report->create();
    }

    /**
     * @param IssueData::SEVERITY_* $severity
     * @psalm-pure
     */
    private static function createIssueData(string $severity, string $type, bool $is_security): IssueData
    {
        return new IssueData(
            $severity,
            1,
            1,
            $type,
            'message',
            'file.php',
            '/file.php',
            '',
            '',
            0,
            0,
            0,
            0,
            1,
            1,
            is_security: $is_security,
        );
    }
}
