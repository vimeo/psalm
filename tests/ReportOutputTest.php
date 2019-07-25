<?php
namespace Psalm\Tests;

use function file_get_contents;
use function json_decode;
use function ob_end_clean;
use function ob_start;
use function preg_replace;
use Psalm\Context;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\IssueBuffer;
use Psalm\Report;
use Psalm\Tests\Internal\Provider;
use function unlink;

class ReportOutputTest extends TestCase
{
    /**
     * @return void
     */
    public function setUp() : void
    {
        // `TestCase::setUp()` creates its own ProjectAnalyzer and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        FileAnalyzer::clearCache();
        $this->file_provider = new Provider\FakeFileProvider();

        $config = new TestConfig();
        $config->throw_exception = false;
        $config->setCustomErrorLevel('PossiblyUndefinedGlobalVariable', \Psalm\Config::REPORT_INFO);

        $json_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json']);

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new \Psalm\Internal\Provider\Providers(
                $this->file_provider,
                new Provider\FakeParserCacheProvider()
            ),
            new Report\ReportOptions(),
            $json_report_options
        );
    }

    /**
     * @return void
     */
    public function testReportFormatValid()
    {
        $config = new TestConfig();
        $config->throw_exception = false;

        // No exception
        foreach (['.xml', '.txt', '.json', '.emacs'] as $extension) {
            ProjectAnalyzer::getFileReportOptions(['/tmp/report' . $extension]);
        }
    }

    /**
     * @return void
     */
    public function testReportFormatException()
    {
        $this->expectException(\UnexpectedValueException::class);
        $config = new TestConfig();
        $config->throw_exception = false;

        ProjectAnalyzer::getFileReportOptions(['/tmp/report.log']);
    }

    public function analyzeFileForReport() : void
    {
        $file_contents = '<?php
function psalmCanVerify(int $your_code): ?string {
  return $as_you . "type";
}

// and it supports PHP 5.4 - 7.1
echo CHANGE_ME;

if (rand(0, 100) > 10) {
  $a = 5;
} else {
  //$a = 2;
}

echo $a;';

        $this->addFile(
            'somefile.php',
            $file_contents
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    /**
     * @return void
     */
    public function testJsonReport()
    {
        $this->analyzeFileForReport();

        $issue_data = [
            [
                'severity' => 'error',
                'line_from' => 3,
                'line_to' => 3,
                'type' => 'UndefinedVariable',
                'message' => 'Cannot find referenced variable $as_you',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => '  return $as_you . "type";',
                'selected_text' => '$as_you',
                'from' => 66,
                'to' => 73,
                'snippet_from' => 57,
                'snippet_to' => 83,
                'column_from' => 10,
                'column_to' => 17,
            ],
            [
                'severity' => 'error',
                'line_from' => 2,
                'line_to' => 2,
                'type' => 'MixedInferredReturnType',
                'message' => 'Could not verify return type \'string|null\' for psalmCanVerify',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'function psalmCanVerify(int $your_code): ?string {',
                'selected_text' => '?string',
                'from' => 47,
                'to' => 54,
                'snippet_from' => 6,
                'snippet_to' => 56,
                'column_from' => 42,
                'column_to' => 49,
            ],
            [
                'severity' => 'error',
                'line_from' => 7,
                'line_to' => 7,
                'type' => 'UndefinedConstant',
                'message' => 'Const CHANGE_ME is not defined',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo CHANGE_ME;',
                'selected_text' => 'CHANGE_ME',
                'from' => 125,
                'to' => 134,
                'snippet_from' => 120,
                'snippet_to' => 135,
                'column_from' => 6,
                'column_to' => 15,
            ],
            [
                'severity' => 'info',
                'line_from' => 15,
                'line_to' => 15,
                'type' => 'PossiblyUndefinedGlobalVariable',
                'message' => 'Possibly undefined global variable $a, first seen on line 10',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo $a',
                'selected_text' => '$a',
                'from' => 201,
                'to' => 203,
                'snippet_from' => 196,
                'snippet_to' => 203,
                'column_from' => 6,
                'column_to' => 8,
            ],
        ];

        $json_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0];

        $this->assertSame(
            $issue_data,
            json_decode(IssueBuffer::getOutput($json_report_options), true)
        );
    }

    /**
     * @return void
     */
    public function testSonarqubeReport()
    {
        $this->analyzeFileForReport();

        $issue_data = [
            'issues' => [
                [
                    'engineId' => 'Psalm',
                    'ruleId' => 'UndefinedVariable',
                    'primaryLocation' => [
                        'message' => 'Cannot find referenced variable $as_you',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 3,
                            'endLine' => 3,
                            'startColumn' => 9,
                            'endColumn' => 16,
                        ],
                    ],
                    'type' => 'CODE_SMELL',
                    'severity' => 'CRITICAL',
                ],
                [
                    'engineId' => 'Psalm',
                    'ruleId' => 'MixedInferredReturnType',
                    'primaryLocation' => [
                        'message' => 'Could not verify return type \'string|null\' for psalmCanVerify',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 2,
                            'endLine' => 2,
                            'startColumn' => 41,
                            'endColumn' => 48,
                        ],
                    ],
                    'type' => 'CODE_SMELL',
                    'severity' => 'CRITICAL',
                ],
                [
                    'engineId' => 'Psalm',
                    'ruleId' => 'UndefinedConstant',
                    'primaryLocation' => [
                        'message' => 'Const CHANGE_ME is not defined',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 7,
                            'endLine' => 7,
                            'startColumn' => 5,
                            'endColumn' => 14,
                        ],
                    ],
                    'type' => 'CODE_SMELL',
                    'severity' => 'CRITICAL',
                ],
                [
                    'engineId' => 'Psalm',
                    'ruleId' => 'PossiblyUndefinedGlobalVariable',
                    'primaryLocation' => [
                        'message' => 'Possibly undefined global variable $a, first seen on line 10',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 15,
                            'endLine' => 15,
                            'startColumn' => 5,
                            'endColumn' => 7,
                        ],
                    ],
                    'type' => 'CODE_SMELL',
                    'severity' => 'MINOR',
                ],
            ],
        ];

        $sonarqube_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-sonarqube.json'])[0];
        $sonarqube_report_options->format = 'sonarqube';

        $this->assertSame(
            $issue_data,
            json_decode(IssueBuffer::getOutput($sonarqube_report_options), true)
        );
    }

    /**
     * @return void
     */
    public function testEmacsReport()
    {
        $this->analyzeFileForReport();

        $emacs_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.emacs'])[0];

        $this->assertSame(
            'somefile.php:3:10:error - Cannot find referenced variable $as_you
somefile.php:2:42:error - Could not verify return type \'string|null\' for psalmCanVerify
somefile.php:7:6:error - Const CHANGE_ME is not defined
somefile.php:15:6:warning - Possibly undefined global variable $a, first seen on line 10
',
            IssueBuffer::getOutput($emacs_report_options)
        );
    }

    /**
     * @return void
     */
    public function testPylintReport()
    {
        $this->analyzeFileForReport();

        $pylint_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.pylint'])[0];

        $this->assertSame(
            'somefile.php:3: [E0001] UndefinedVariable: Cannot find referenced variable $as_you (column 10)
somefile.php:2: [E0001] MixedInferredReturnType: Could not verify return type \'string|null\' for psalmCanVerify (column 42)
somefile.php:7: [E0001] UndefinedConstant: Const CHANGE_ME is not defined (column 6)
somefile.php:15: [W0001] PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 10 (column 6)
',
            IssueBuffer::getOutput($pylint_report_options)
        );
    }

    /**
     * @return void
     */
    public function testConsoleReport()
    {
        $this->analyzeFileForReport();

        $console_report_options = new Report\ReportOptions();
        $console_report_options->use_color = false;

        $this->assertSame(
            'ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you
  return $as_you . "type";

ERROR: MixedInferredReturnType - somefile.php:2:42 - Could not verify return type \'string|null\' for psalmCanVerify
function psalmCanVerify(int $your_code): ?string {

ERROR: UndefinedConstant - somefile.php:7:6 - Const CHANGE_ME is not defined
echo CHANGE_ME;

INFO: PossiblyUndefinedGlobalVariable - somefile.php:15:6 - Possibly undefined global variable $a, first seen on line 10
echo $a

',
            IssueBuffer::getOutput($console_report_options)
        );
    }

    /**
     * @return void
     */
    public function testConsoleReportNoInfo()
    {
        $this->analyzeFileForReport();

        $console_report_options = new Report\ReportOptions();
        $console_report_options->use_color = false;
        $console_report_options->show_info = false;

        $this->assertSame(
            'ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you
  return $as_you . "type";

ERROR: MixedInferredReturnType - somefile.php:2:42 - Could not verify return type \'string|null\' for psalmCanVerify
function psalmCanVerify(int $your_code): ?string {

ERROR: UndefinedConstant - somefile.php:7:6 - Const CHANGE_ME is not defined
echo CHANGE_ME;

',
            IssueBuffer::getOutput($console_report_options)
        );
    }

    /**
     * @return void
     */
    public function testConsoleReportNoSnippet()
    {
        $this->analyzeFileForReport();

        $console_report_options = new Report\ReportOptions();
        $console_report_options->show_snippet = false;
        $console_report_options->use_color = false;

        $this->assertSame(
            'ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you


ERROR: MixedInferredReturnType - somefile.php:2:42 - Could not verify return type \'string|null\' for psalmCanVerify


ERROR: UndefinedConstant - somefile.php:7:6 - Const CHANGE_ME is not defined


INFO: PossiblyUndefinedGlobalVariable - somefile.php:15:6 - Possibly undefined global variable $a, first seen on line 10


',
            IssueBuffer::getOutput($console_report_options)
        );
    }

    /**
     * @return void
     */
    public function testCompactReport()
    {
        $this->analyzeFileForReport();

        $compact_report_options = new Report\ReportOptions();
        $compact_report_options->format = Report::TYPE_COMPACT;
        $compact_report_options->use_color = false;

        $this->assertSame(
            'FILE: somefile.php' . "\n" .
            "\n" .
            '+----------+------+---------------------------------+---------------------------------------------------------------+' . "\n" .
            '| SEVERITY | LINE | ISSUE                           | DESCRIPTION                                                   |' . "\n" .
            '+----------+------+---------------------------------+---------------------------------------------------------------+' . "\n" .
            '| ERROR    | 3    | UndefinedVariable               | Cannot find referenced variable $as_you                       |' . "\n" .
            '| ERROR    | 2    | MixedInferredReturnType         | Could not verify return type \'string|null\' for psalmCanVerify |' . "\n" .
            '| ERROR    | 7    | UndefinedConstant               | Const CHANGE_ME is not defined                                |' . "\n" .
            '| INFO     | 15   | PossiblyUndefinedGlobalVariable | Possibly undefined global variable $a, first seen on line 10  |' . "\n" .
            '+----------+------+---------------------------------+---------------------------------------------------------------+' . "\n",
            $this->toUnixLineEndings(IssueBuffer::getOutput($compact_report_options))
        );
    }
    /**
     * @return void
     */
    public function testCheckstyleReport()
    {
        $this->analyzeFileForReport();

        $checkstyle_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.checkstyle.xml'])[0];

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
<file name="somefile.php">
 <error line="3" column="10" severity="error" message="UndefinedVariable: Cannot find referenced variable $as_you"/>
</file>
<file name="somefile.php">
 <error line="2" column="42" severity="error" message="MixedInferredReturnType: Could not verify return type \'string|null\' for psalmCanVerify"/>
</file>
<file name="somefile.php">
 <error line="7" column="6" severity="error" message="UndefinedConstant: Const CHANGE_ME is not defined"/>
</file>
<file name="somefile.php">
 <error line="15" column="6" severity="info" message="PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 10"/>
</file>
</checkstyle>
',
            IssueBuffer::getOutput($checkstyle_report_options)
        );

        // FIXME: The XML parser only return strings, all int value are casted, so the assertSame failed
        //$this->assertSame(
        //    ['report' => ['item' => $issue_data]],
        //    XML2Array::createArray(IssueBuffer::getOutput(ProjectAnalyzer::TYPE_XML, false), LIBXML_NOCDATA)
        //);
    }

    /**
     * @return void
     */
    public function testEmptyReportIfNotError()
    {
        $this->addFile(
            'somefile.php',
            '<?php ?>'
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(
            '[]
',
            IssueBuffer::getOutput(ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0])
        );
        $this->assertSame(
            '',
            IssueBuffer::getOutput(ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.emacs'])[0])
        );
        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<report>
  <item/>
</report>
',
            IssueBuffer::getOutput(ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.xml'])[0])
        );

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
</checkstyle>
',
            IssueBuffer::getOutput(ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.checkstyle.xml'])[0])
        );

        ob_start();
        IssueBuffer::finish($this->project_analyzer, true, 0);
        ob_end_clean();
        $this->assertFileExists(__DIR__ . '/test-report.json');
        $this->assertSame('[]
', file_get_contents(__DIR__ . '/test-report.json'));
        unlink(__DIR__ . '/test-report.json');
    }

    /**
     * Needed when running on Windows
     */
    private function toUnixLineEndings(string $output): string
    {
        return preg_replace('~\r\n?~', "\n", $output);
    }
}
