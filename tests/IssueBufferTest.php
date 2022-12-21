<?php

namespace Psalm\Tests;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\Internal\EventDispatcher;
use Psalm\IssueBuffer;
use Psalm\Report\ReportOptions;

use function microtime;
use function ob_get_clean;
use function ob_start;

class IssueBufferTest extends TestCase
{

    public function testFinishDoesNotCorruptInternalState(): void
    {
        IssueBuffer::clear();
        IssueBuffer::addIssues([
            '/path/one.php' => [
                new IssueData(
                    "error",
                    0,
                    0,
                    "MissingPropertyType",
                    'Message',
                    "one.php",
                    "/path/one.php",
                    "snippet-1",
                    "snippet-1",
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ),
            ],
            '/path/two.php' => [
                new IssueData(
                    "error",
                    0,
                    0,
                    "MissingPropertyType",
                    'Message',
                    "two.php",
                    "/path/two.php",
                    "snippet-2",
                    "snippet-2",
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ),
            ],
            '/path/three.php' => [
                new IssueData(
                    "error",
                    0,
                    0,
                    "MissingPropertyType",
                    'Message',
                    "three.php",
                    "/path/three.php",
                    "snippet-3-has-carriage-return\r",
                    "snippet-3-has-carriage-return\r",
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ),
            ],
        ]);
        $baseline = [
            'one.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-1']] ],
            'two.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-2']] ],
            'three.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-3-has-carriage-return']] ],
        ];

        $analyzer = $this->createMock(Analyzer::class);
        $analyzer->method('getTotalTypeCoverage')->willReturn([0, 0]);

        $eventDispatcher = $this->createMock(EventDispatcher::class);

        $config = $this->createMock(Config::class);
        $config->eventDispatcher = $eventDispatcher;

        $codebase = $this->createMock(Codebase::class);
        $codebase->analyzer = $analyzer;
        $codebase->config = $config;

        $projectAnalzyer = $this->createMock(ProjectAnalyzer::class);
        $projectAnalzyer->method('getCodebase')->willReturn($codebase);

        $projectAnalzyer->stdout_report_options = new ReportOptions();
        $projectAnalzyer->generated_report_options = [];

        ob_start();
        IssueBuffer::finish($projectAnalzyer, false, microtime(true), false, $baseline);
        $output = ob_get_clean();
        $this->assertStringNotContainsString("ERROR", $output, "all issues baselined");
        IssueBuffer::clear();
    }

    public function testPrintSuccessMessageWorks(): void
    {
        $project_analyzer = $this->createMock(ProjectAnalyzer::class);
        $project_analyzer->stdout_report_options = new ReportOptions;
        ob_start();
        IssueBuffer::printSuccessMessage($project_analyzer);
        $output = ob_get_clean();

        $this->assertStringContainsString('No errors found!', $output);
    }
}
