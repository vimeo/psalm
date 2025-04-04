<?php

declare(strict_types=1);

namespace Psalm\Tests;

use DOMDocument;
use Override;
use Psalm\Config;
use Psalm\Context;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Report;
use Psalm\Report\JsonReport;
use Psalm\Report\ReportOptions;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use UnexpectedValueException;

use function file_get_contents;
use function json_decode;
use function ob_end_clean;
use function ob_start;
use function preg_replace;
use function unlink;

use const JSON_THROW_ON_ERROR;

final class ReportOutputTest extends TestCase
{
    #[Override]
    public function setUp(): void
    {
        // `TestCase::setUp()` creates its own ProjectAnalyzer and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        RuntimeCaches::clearAll();
        $this->file_provider = new FakeFileProvider();

        $config = new TestConfig();
        $config->throw_exception = false;
        $config->setCustomErrorLevel('PossiblyUndefinedGlobalVariable', Config::REPORT_INFO);

        $json_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json']);

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            new Providers(
                $this->file_provider,
                new FakeParserCacheProvider(),
            ),
            new ReportOptions(),
            $json_report_options,
        );
    }

    public function testReportFormatValid(): void
    {
        $config = new TestConfig();
        $config->throw_exception = false;

        // No exception
        foreach (['.xml', '.txt', '.json', '.emacs'] as $extension) {
            ProjectAnalyzer::getFileReportOptions(['/tmp/report' . $extension]);
        }
    }

    public function testReportFormatException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $config = new TestConfig();
        $config->throw_exception = false;

        ProjectAnalyzer::getFileReportOptions(['/tmp/report.log']);
    }

    public function analyzeTaintFlowFilesForReport(): void
    {
        $vulnerable_file_contents = <<<'EOF'
        <?php

        function addPrefixToInput(string $prefix, string $input): string {
            return $prefix . $input;
        }

        $prefixedData = addPrefixToInput('myprefix', (string) ($_POST['cmd'] ?? ''));

        shell_exec($prefixedData);

        echo "Successfully executed the command: " . $prefixedData;
        EOF;

        $this->addFile(
            'taintflow-test/vulnerable.php',
            $vulnerable_file_contents,
        );

        $this->analyzeFile('taintflow-test/vulnerable.php', new Context(), true, true);
    }

    public function testSarifReport(): void
    {
        $this->analyzeTaintFlowFilesForReport();

        $issue_data = json_decode(file_get_contents(__DIR__.'/sarif.json'), true, flags: JSON_THROW_ON_ERROR);

        $sarif_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.sarif'])[0];

        //file_put_contents(__DIR__.'/sarif.json', json_encode(json_decode(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $sarif_report_options), true, 512, JSON_THROW_ON_ERROR), flags: JSON_PRETTY_PRINT));
        $this->assertSame(
            $issue_data,
            json_decode(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $sarif_report_options), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function analyzeFileForReport(): void
    {
        $file_contents = <<<'EOF'
        <?php
        function psalmCanVerify(int $your_code): ?string {
          return $as_you_____type;
        }

        // and it supports PHP 5.4 - 7.1
        /** @psalm-suppress MixedArgument */
        echo CHANGE_ME;

        if (rand(0, 100) > 10) {
          $a = 5;
        } else {
          //$a = 2;
        }

        /** @psalm-suppress MixedArgument */
        echo $a;
        EOF;

        $this->addFile(
            'somefile.php',
            $file_contents,
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testJsonReport(): void
    {
        $this->analyzeFileForReport();

        $issue_data = [
            [
                'link' => 'https://psalm.dev/024',
                'severity' => 'error',
                'line_from' => 3,
                'line_to' => 3,
                'type' => 'UndefinedVariable',
                'message' => 'Cannot find referenced variable $as_you_____type',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => '  return $as_you_____type;',
                'selected_text' => '$as_you_____type',
                'from' => 66,
                'to' => 82,
                'snippet_from' => 57,
                'snippet_to' => 83,
                'column_from' => 10,
                'column_to' => 26,
                'shortcode' => 24,
                'error_level' => -1,
                'taint_trace' => null,
                'other_references' => null,
            ],
            [
                'link' => 'https://psalm.dev/138',
                'severity' => 'error',
                'line_from' => 3,
                'line_to' => 3,
                'type' => 'MixedReturnStatement',
                'message' => 'Could not infer a return type',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => '  return $as_you_____type;',
                'selected_text' => '$as_you_____type',
                'from' => 66,
                'to' => 82,
                'snippet_from' => 57,
                'snippet_to' => 83,
                'column_from' => 10,
                'column_to' => 26,
                'shortcode' => 138,
                'error_level' => 1,
                'taint_trace' => null,
                'other_references' => null,
            ],
            [
                'link' => 'https://psalm.dev/020',
                'severity' => 'error',
                'line_from' => 8,
                'line_to' => 8,
                'type' => 'UndefinedConstant',
                'message' => 'Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo CHANGE_ME;',
                'selected_text' => 'CHANGE_ME',
                'from' => 162,
                'to' => 171,
                'snippet_from' => 157,
                'snippet_to' => 172,
                'column_from' => 6,
                'column_to' => 15,
                'shortcode' => 20,
                'error_level' => -1,
                'taint_trace' => null,
                'other_references' => null,
            ],
            [
                'link' => 'https://psalm.dev/126',
                'severity' => 'info',
                'line_from' => 17,
                'line_to' => 17,
                'type' => 'PossiblyUndefinedGlobalVariable',
                'message' => 'Possibly undefined global variable $a, first seen on line 11',
                'file_name' => 'somefile.php',
                'file_path' => 'somefile.php',
                'snippet' => 'echo $a',
                'selected_text' => '$a',
                'from' => 275,
                'to' => 277,
                'snippet_from' => 270,
                'snippet_to' => 277,
                'column_from' => 6,
                'column_to' => 8,
                'shortcode' => 126,
                'error_level' => 3,
                'taint_trace' => null,
                'other_references' => null,
            ],
        ];

        $json_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0];

        $this->assertSame(
            $issue_data,
            json_decode(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $json_report_options), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testFilteredJsonReportIsStillArray(): void
    {
        $issues_data = [
            22 => new IssueData(
                IssueData::SEVERITY_INFO,
                15,
                15,
                'PossiblyUndefinedGlobalVariable',
                'Possibly undefined global variable $a, first seen on line 11',
                'somefile.php',
                'somefile.php',
                'echo $a',
                '$a',
                201,
                203,
                196,
                203,
                6,
                8,
            ),
        ];

        $report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0];
        $fixable_issue_counts = [];

        $report = new JsonReport(
            $issues_data,
            $fixable_issue_counts,
            $report_options,
        );
        $this->assertIsArray(json_decode($report->create(), null, 512, JSON_THROW_ON_ERROR));
    }

    public function testSonarqubeReport(): void
    {
        $this->analyzeFileForReport();

        $issue_data = [
            'issues' => [
                [
                    'engineId' => 'Psalm',
                    'ruleId' => 'UndefinedVariable',
                    'primaryLocation' => [
                        'message' => 'Cannot find referenced variable $as_you_____type',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 3,
                            'endLine' => 3,
                            'startColumn' => 9,
                            'endColumn' => 25,
                        ],
                    ],
                    'type' => 'CODE_SMELL',
                    'severity' => 'CRITICAL',
                ],
                [
                    'engineId' => 'Psalm',
                    'ruleId' => 'MixedReturnStatement',
                    'primaryLocation' => [
                        'message' => 'Could not infer a return type',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 3,
                            'endLine' => 3,
                            'startColumn' => 9,
                            'endColumn' => 25,
                        ],
                    ],
                    'type' => 'CODE_SMELL',
                    'severity' => 'CRITICAL',
                ],
                [
                    'engineId' => 'Psalm',
                    'ruleId' => 'UndefinedConstant',
                    'primaryLocation' => [
                        'message' => 'Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 8,
                            'endLine' => 8,
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
                        'message' => 'Possibly undefined global variable $a, first seen on line 11',
                        'filePath' => 'somefile.php',
                        'textRange' => [
                            'startLine' => 17,
                            'endLine' => 17,
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
            json_decode(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $sonarqube_report_options), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testEmacsReport(): void
    {
        $this->analyzeFileForReport();

        $emacs_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.emacs'])[0];

        $this->assertSame(
            <<<'EOF'
            somefile.php:3:10:error - UndefinedVariable: Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)
            somefile.php:3:10:error - MixedReturnStatement: Could not infer a return type (see https://psalm.dev/138)
            somefile.php:8:6:error - UndefinedConstant: Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases (see https://psalm.dev/020)
            somefile.php:17:6:warning - PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 11 (see https://psalm.dev/126)

            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $emacs_report_options),
        );
    }

    public function testPylintReport(): void
    {
        $this->analyzeFileForReport();

        $pylint_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.pylint'])[0];

        $this->assertSame(
            <<<'EOF'
            somefile.php:3: [E0001] UndefinedVariable: Cannot find referenced variable $as_you_____type (column 10)
            somefile.php:3: [E0001] MixedReturnStatement: Could not infer a return type (column 10)
            somefile.php:8: [E0001] UndefinedConstant: Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases (column 6)
            somefile.php:17: [W0001] PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 11 (column 6)

            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $pylint_report_options),
        );
    }

    public function testConsoleReport(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new ReportOptions();
        $console_report_options->use_color = false;

        $this->assertSame(
            <<<'EOF'
            ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)
              return $as_you_____type;

            ERROR: MixedReturnStatement - somefile.php:3:10 - Could not infer a return type (see https://psalm.dev/138)
              return $as_you_____type;

            ERROR: UndefinedConstant - somefile.php:8:6 - Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases (see https://psalm.dev/020)
            echo CHANGE_ME;

            INFO: PossiblyUndefinedGlobalVariable - somefile.php:17:6 - Possibly undefined global variable $a, first seen on line 11 (see https://psalm.dev/126)
            echo $a


            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options),
        );
    }

    public function testConsoleReportNoInfo(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new ReportOptions();
        $console_report_options->use_color = false;
        $console_report_options->show_info = false;

        $this->assertSame(
            <<<'EOF'
            ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)
              return $as_you_____type;

            ERROR: MixedReturnStatement - somefile.php:3:10 - Could not infer a return type (see https://psalm.dev/138)
              return $as_you_____type;

            ERROR: UndefinedConstant - somefile.php:8:6 - Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases (see https://psalm.dev/020)
            echo CHANGE_ME;


            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options),
        );
    }

    public function testConsoleReportNoSnippet(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new ReportOptions();
        $console_report_options->show_snippet = false;
        $console_report_options->use_color = false;

        $this->assertSame(
            <<<'EOF'
            ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)


            ERROR: MixedReturnStatement - somefile.php:3:10 - Could not infer a return type (see https://psalm.dev/138)


            ERROR: UndefinedConstant - somefile.php:8:6 - Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases (see https://psalm.dev/020)


            INFO: PossiblyUndefinedGlobalVariable - somefile.php:17:6 - Possibly undefined global variable $a, first seen on line 11 (see https://psalm.dev/126)



            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options),
        );
    }

    public function testConsoleReportWithLinks(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new ReportOptions();
        $console_report_options->show_snippet = false;
        $console_report_options->use_color = true;
        $console_report_options->in_ci = false; // we don't output links in CI

        $output  = IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options);

        $this->assertStringContainsString(
            "\033]8;;file://somefile.php#L3\033\\\033[1;31msomefile.php:3:10\033[0m\033]8;;\033\\",
            $output,
        );
    }

    public function testConsoleReportLinksAreDisabledInCI(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new ReportOptions();
        $console_report_options->show_snippet = false;
        $console_report_options->use_color = true;
        $console_report_options->in_ci = true;

        $output  = IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options);

        $this->assertStringNotContainsString(
            "\033]8;;file://somefile.php#L3\033\\",
            $output,
        );
    }

    public function testCompactReport(): void
    {
        $this->analyzeFileForReport();

        $compact_report_options = new ReportOptions();
        $compact_report_options->format = Report::TYPE_COMPACT;
        $compact_report_options->use_color = false;

        $this->assertSame(
            <<<'EOF'
            FILE: somefile.php

            +----------+------+---------------------------------+------------------------------------------------------------------------+
            | SEVERITY | LINE | ISSUE                           | DESCRIPTION                                                            |
            +----------+------+---------------------------------+------------------------------------------------------------------------+
            | ERROR    | 3    | UndefinedVariable               | Cannot find referenced variable $as_you_____type                       |
            | ERROR    | 3    | MixedReturnStatement            | Could not infer a return type                                          |
            | ERROR    | 8    | UndefinedConstant               | Const CHANGE_ME is not defined, consider enabling the allConstantsGlob |
            |          |      |                                 | al config option if scanning legacy codebases                          |
            | INFO     | 17   | PossiblyUndefinedGlobalVariable | Possibly undefined global variable $a, first seen on line 11           |
            +----------+------+---------------------------------+------------------------------------------------------------------------+

            EOF,
            $this->toUnixLineEndings(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $compact_report_options)),
        );
    }

    public function testCheckstyleReport(): void
    {
        $this->analyzeFileForReport();

        $checkstyle_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.checkstyle.xml'])[0];

        $this->assertSame(
            <<<'EOF'
            <?xml version="1.0" encoding="UTF-8"?>
            <checkstyle>
            <file name="somefile.php">
             <error line="3" column="10" severity="error" message="UndefinedVariable: Cannot find referenced variable $as_you_____type"/>
            </file>
            <file name="somefile.php">
             <error line="3" column="10" severity="error" message="MixedReturnStatement: Could not infer a return type"/>
            </file>
            <file name="somefile.php">
             <error line="8" column="6" severity="error" message="UndefinedConstant: Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases"/>
            </file>
            <file name="somefile.php">
             <error line="17" column="6" severity="info" message="PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 11"/>
            </file>
            </checkstyle>

            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $checkstyle_report_options),
        );

        // FIXME: The XML parser only return strings, all int value are casted, so the assertSame failed
        //$this->assertSame(
        //    ['report' => ['item' => $issue_data]],
        //    XML2Array::createArray(IssueBuffer::getOutput(ProjectAnalyzer::TYPE_XML, false), LIBXML_NOCDATA)
        //);
    }

    public function testJunitReport(): void
    {
        $this->analyzeFileForReport();

        $checkstyle_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.junit.xml'])[0];

        $xml = IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $checkstyle_report_options);

        $this->assertSame(
            <<<'EOF'
            <?xml version="1.0" encoding="UTF-8"?>
            <testsuites failures="3" errors="0" name="psalm" tests="4" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd">
              <testsuite name="somefile.php" failures="3" errors="0" tests="4">
                <testcase name="somefile.php:3" classname="UndefinedVariable" assertions="1">
                  <failure type="UndefinedVariable">message: Cannot find referenced variable $as_you_____type
            type: UndefinedVariable
            snippet: return $as_you_____type;
            selected_text: $as_you_____type
            line: 3
            column_from: 10
            column_to: 26
            </failure>
                </testcase>
                <testcase name="somefile.php:3" classname="MixedReturnStatement" assertions="1">
                  <failure type="MixedReturnStatement">message: Could not infer a return type
            type: MixedReturnStatement
            snippet: return $as_you_____type;
            selected_text: $as_you_____type
            line: 3
            column_from: 10
            column_to: 26
            </failure>
                </testcase>
                <testcase name="somefile.php:8" classname="UndefinedConstant" assertions="1">
                  <failure type="UndefinedConstant">message: Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases
            type: UndefinedConstant
            snippet: echo CHANGE_ME;
            selected_text: CHANGE_ME
            line: 8
            column_from: 6
            column_to: 15
            </failure>
                </testcase>
                <testcase name="somefile.php:17" classname="PossiblyUndefinedGlobalVariable" assertions="1">
                  <skipped>message: Possibly undefined global variable $a, first seen on line 11
            type: PossiblyUndefinedGlobalVariable
            snippet: echo $a
            selected_text: $a
            line: 17
            column_from: 6
            column_to: 8
            </skipped>
                </testcase>
              </testsuite>
            </testsuites>

            EOF,
            $xml,
        );

        // Validate against junit xsd
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($xml);

        // Validate against xsd
        $valid = $dom->schemaValidate(__DIR__ . '/junit.xsd');
        $this->assertTrue($valid, 'Output did not validate against XSD');

        // FIXME: The XML parser only return strings, all int value are casted, so the assertSame failed
        //$this->assertSame(
        //    ['report' => ['item' => $issue_data]],
        //    XML2Array::createArray(IssueBuffer::getOutput(ProjectAnalyzer::TYPE_XML, false), LIBXML_NOCDATA)
        //);
    }

    public function testGithubActionsOutput(): void
    {
        $this->analyzeFileForReport();

        $github_report_options = new ReportOptions();
        $github_report_options->format = Report::TYPE_GITHUB_ACTIONS;
        $expected_output = <<<'EOF'
        ::error file=somefile.php,line=3,col=10,title=UndefinedVariable::somefile.php:3:10: UndefinedVariable: Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)
        ::error file=somefile.php,line=3,col=10,title=MixedReturnStatement::somefile.php:3:10: MixedReturnStatement: Could not infer a return type (see https://psalm.dev/138)
        ::error file=somefile.php,line=8,col=6,title=UndefinedConstant::somefile.php:8:6: UndefinedConstant: Const CHANGE_ME is not defined, consider enabling the allConstantsGlobal config option if scanning legacy codebases (see https://psalm.dev/020)
        ::warning file=somefile.php,line=17,col=6,title=PossiblyUndefinedGlobalVariable::somefile.php:17:6: PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 11 (see https://psalm.dev/126)

        EOF;
        $this->assertSame(
            $expected_output,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $github_report_options),
        );
    }

    public function testCountOutput(): void
    {
        $this->analyzeFileForReport();

        $report_options = new ReportOptions();
        $report_options->format = Report::TYPE_COUNT;
        $expected_output = <<<'EOF'
        MixedReturnStatement: 1
        PossiblyUndefinedGlobalVariable: 1
        UndefinedConstant: 1
        UndefinedVariable: 1

        EOF;
        $this->assertSame(
            $expected_output,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $report_options),
        );
    }

    public function testEmptyReportIfNotError(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php ?>',
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(
            "[]\n",
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0]),
        );
        $this->assertSame(
            '',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.emacs'])[0]),
        );
        $this->assertSame(
            <<<'EOF'
            <?xml version="1.0" encoding="UTF-8"?>
            <report>
              <item/>
            </report>

            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.xml'])[0]),
        );

        $this->assertSame(
            <<<'EOF'
            <?xml version="1.0" encoding="UTF-8"?>
            <checkstyle>
            </checkstyle>

            EOF,
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.checkstyle.xml'])[0]),
        );

        ob_start();
        IssueBuffer::finish($this->project_analyzer, true, 0);
        ob_end_clean();
        $this->assertFileExists(__DIR__ . '/test-report.json');
        $this->assertSame(
            "[]\n",
            file_get_contents(__DIR__ . '/test-report.json'),
        );
        unlink(__DIR__ . '/test-report.json');
    }

    /**
     * Needed when running on Windows
     *
     * @psalm-pure
     */
    private function toUnixLineEndings(string $output): string
    {
        return (string) preg_replace('~\r\n?~', "\n", $output);
    }
}
