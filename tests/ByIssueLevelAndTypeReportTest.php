<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\TestCase;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report\ByIssueLevelAndTypeReport;
use Psalm\Report\ReportOptions;

class ByIssueLevelAndTypeReportTest extends TestCase
{
    public function testItGeneratesReport(): void
    {
        $issuesData = [
            $this->issueData(2, 'SomeLevel2IssueType'),
            $this->issueData(-1, 'SomeAlwaysReportedIssueType'),
            $this->issueData(4, 'SomeLevel4IssueType'),
            $this->issueData(7, 'SomeLevel7IssueType'),
            $this->issueData(4, 'AnotherLevel4IssueType'),
            $this->issueData(4, 'SomeLevel4IssueType'), // same issue type as above, will be sorted together
            $this->issueData(1, 'SomeIssueType'),
            $this->issueData(-2, 'SomeFeatureSpecificIssueType'),
        ];

        $reportOptions = new ReportOptions();
        $reportOptions->use_color = false;

        $report = new ByIssueLevelAndTypeReport($issuesData, [], $reportOptions);

        $this->assertSame(<<<EXPECTED
        |----------------------------------------------------------------------------------------|
        |    Issues have been sorted by level and type. Feature-specific issues and the          |
        |    most serious issues that will always be reported are listed first, with             |
        |    remaining issues in level order. Issues near the top are usually the most serious.  |
        |    Reducing the errorLevel in psalm.xml will suppress output of issues further down    |
        |    this report.                                                                        |
        |                                                                                        |
        |    The level at which issue is reported as an error is given in brackets - e.g.        |
        |    `ERROR (2): MissingReturnType` indicates that MissingReturnType is only reported    |
        |    as an error when Psalm's level is set to 2 or below.                                |
        |                                                                                        |
        |    Issues are shown or hidden in this report according to current settings. For        |
        |    the most complete report set Psalm's error level to 0 or use --show-info=true       |
        |    See https://psalm.dev/docs/running_psalm/error_levels/                              |
        |----------------------------------------------------------------------------------------|

        ERROR: SomeAlwaysReportedIssueType - file.php:1:1 - message


        ERROR: SomeFeatureSpecificIssueType - file.php:1:1 - message


        ERROR (7): SomeLevel7IssueType - file.php:1:1 - message


        ERROR (4): AnotherLevel4IssueType - file.php:1:1 - message


        ERROR (4): SomeLevel4IssueType - file.php:1:1 - message


        ERROR (4): SomeLevel4IssueType - file.php:1:1 - message


        ERROR (2): SomeLevel2IssueType - file.php:1:1 - message


        ERROR (1): SomeIssueType - file.php:1:1 - message



        EXPECTED, $report->create());
    }

    private function issueData(int $errorLevel, string $type): IssueData
    {
        return new IssueData(
            IssueData::SEVERITY_ERROR,
            1,
            1,
            $type,
            'message',
            'file.php',
            '/',
            '',
            '',
            1,
            1,
            1,
            1,
            1,
            1,
            0,
            $errorLevel,
        );
    }
}
