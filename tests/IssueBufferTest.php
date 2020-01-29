<?php

namespace Psalm\Tests;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Codebase\Analyzer;
use Psalm\IssueBuffer;
use Psalm\Report\ReportOptions;

class IssueBufferTest extends TestCase
{

    /**
     * @return void
     */
    public function testFinishDoesNotCorruptInternalState()
    {
        IssueBuffer::clear();
        IssueBuffer::addIssues([
            '/path/one.php' => [
                [
                    "severity" => "error",
                    "type" =>  "MissingPropertyType",
                    "message" => 'Message',
                    "file_name" =>"one.php",
                    "file_path" =>  "/path/one.php",
                    "snippet" => "snippet-1",
                    "selected_text" => "snippet-1",
                    "from"=> 0,
                    "to"=> 0,
                    "snippet_from" => 0,
                    "snippet_to" => 0,
                    "column_from" => 0,
                    "column_to" => 0,
                    "line_from" => 0,
                    "line_to" => 0,
                ]
            ],
            '/path/two.php' => [
                [
                    "severity" => "error",
                    "type" =>  "MissingPropertyType",
                    "message" => 'Message',
                    "file_name" =>"two.php",
                    "file_path" =>  "/path/two.php",
                    "snippet" => "snippet-2",
                    "selected_text" => "snippet-2",
                    "from"=> 0,
                    "to"=> 0,
                    "snippet_from" => 0,
                    "snippet_to" => 0,
                    "column_from" => 0,
                    "column_to" => 0,
                    "line_from" => 0,
                    "line_to" => 0,
                ]
            ]
        ]);
        $baseline = [
            'one.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-1']] ],
            'two.php' => ['MissingPropertyType' => ['o' => 1, 's' => ['snippet-2']] ],
        ];

        $analyzer = $this->createMock(Analyzer::class);
        $analyzer->method('getTotalTypeCoverage')->willReturn([0, 0]);

        $config = $this->createMock(Config::class);

        $codebase = $this->createMock(Codebase::class);
        $codebase->analyzer = $analyzer;
        $codebase->config = $config;

        $projectAnalzyer = $this->createMock(ProjectAnalyzer::class);
        $projectAnalzyer->method('getCodebase')->willReturn($codebase);

        $projectAnalzyer->stdout_report_options = new ReportOptions();
        $projectAnalzyer->generated_report_options = [];

        \ob_start();
        IssueBuffer::finish($projectAnalzyer, false, \microtime(true), false, $baseline);
        $output = \ob_get_clean();
        $this->assertStringNotContainsString("ERROR", $output, "all issues baselined");
        IssueBuffer::clear();
    }
}
