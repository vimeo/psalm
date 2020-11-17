<?php

namespace Psalm\Tests;

use DOMDocument;
use Psalm\Context;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Report;
use Psalm\Report\JsonReport;
use Psalm\Tests\Internal\Provider;

use function file_get_contents;
use function json_decode;
use function ob_end_clean;
use function ob_start;
use function preg_replace;
use function array_values;
use function unlink;

class ReportOutputTest extends TestCase
{
    public function setUp() : void
    {
        // `TestCase::setUp()` creates its own ProjectAnalyzer and Config instance, but we don't want to do that in this
        // case, so don't run a `parent::setUp()` call here.
        RuntimeCaches::clearAll();
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
        $this->expectException(\UnexpectedValueException::class);
        $config = new TestConfig();
        $config->throw_exception = false;

        ProjectAnalyzer::getFileReportOptions(['/tmp/report.log']);
    }

    public function analyzeTaintFlowFilesForReport() : void
    {
        $vulnerable_file_contents = '<?php
 
function addPrefixToInput($prefix, $input): string {
    return $prefix . $input;
}

$prefixedData = addPrefixToInput(\'myprefix\', $_POST[\'cmd\']);
        
shell_exec($prefixedData);

echo "Successfully executed the command: " . $prefixedData;';

        $this->addFile(
            'taintflow-test/vulnerable.php',
            $vulnerable_file_contents
        );

        $this->analyzeFile('taintflow-test/vulnerable.php', new Context(), true, true);
    }

    public function testSarifReport(): void
    {
        $this->analyzeTaintFlowFilesForReport();

        $issue_data = [
            'version' => '2.1.0',
            'runs' => [
                [
                    'tool' => [
                        'driver' => [
                            'name' => 'Psalm',
                            'version' => '2.0.0',
                            'rules' => [
                                [
                                    'id' => '246',
                                    'name' => 'TaintedShell',
                                    'shortDescription' => [
                                        'text' => 'TaintedShell'
                                    ],
                                    'properties' => [
                                        'tags' => [
                                            'security'
                                        ],
                                    ],
                                    'help' => [
                                        'markdown' => file_get_contents(__DIR__ . '/../docs/running_psalm/issues/TaintedShell.md'),
                                        'text' => file_get_contents(__DIR__ . '/../docs/running_psalm/issues/TaintedShell.md'),
                                    ],
                                ],
                                [
                                    'id' => '245',
                                    'name' => 'TaintedHtml',
                                    'shortDescription' => [
                                        'text' => 'TaintedHtml'
                                    ],
                                    'properties' => [
                                        'tags' => [
                                            'security'
                                        ],
                                    ],
                                    'help' => [
                                        'markdown' => file_get_contents(__DIR__ . '/../docs/running_psalm/issues/TaintedHtml.md'),
                                        'text' => file_get_contents(__DIR__ . '/../docs/running_psalm/issues/TaintedHtml.md'),
                                    ],
                                ],
                            ]
                        ]
                    ],
                    'results' => [
                        [
                            'ruleId' => '246',
                            'message' => [
                                'text' => 'Detected tainted shell code'
                            ],
                            'level' => 'error',
                            'locations' => [
                                [
                                    'physicalLocation' => [
                                        'artifactLocation' => [
                                            'uri' => 'taintflow-test/vulnerable.php'
                                        ],
                                        'region' => [
                                            'startLine' => 9,
                                            'endLine' => 9,
                                            'startColumn' => 12,
                                            'endColumn' => 25
                                        ]
                                    ]
                                ]
                            ],
                            'codeFlows' => [
                                [
                                    'message' => [
                                        'text' => 'Tracing the path from user input to insecure usage'
                                    ],
                                    'threadFlows' => [
                                        [
                                            'locations' => [
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 7,
                                                                'endLine' => 7,
                                                                'startColumn' => 46,
                                                                'endColumn' => 52
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 7,
                                                                'endLine' => 7,
                                                                'startColumn' => 46,
                                                                'endColumn' => 59
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 3,
                                                                'endLine' => 3,
                                                                'startColumn' => 36,
                                                                'endColumn' => 42
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 3,
                                                                'endLine' => 3,
                                                                'startColumn' => 36,
                                                                'endColumn' => 42
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 4,
                                                                'endLine' => 4,
                                                                'startColumn' => 12,
                                                                'endColumn' => 28
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 3,
                                                                'endLine' => 3,
                                                                'startColumn' => 45,
                                                                'endColumn' => 51
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 7,
                                                                'endLine' => 7,
                                                                'startColumn' => 1,
                                                                'endColumn' => 14
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 9,
                                                                'endLine' => 9,
                                                                'startColumn' => 12,
                                                                'endColumn' => 25
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        [
                            'ruleId' => '245',
                            'message' => [
                                'text' => 'Detected tainted HTML'
                            ],
                            'level' => 'error',
                            'locations' => [
                                [
                                    'physicalLocation' => [
                                        'artifactLocation' => [
                                            'uri' => 'taintflow-test/vulnerable.php'
                                        ],
                                        'region' => [
                                            'startLine' => 11,
                                            'endLine' => 11,
                                            'startColumn' => 6,
                                            'endColumn' => 59
                                        ]
                                    ]
                                ]
                            ],
                            'codeFlows' => [
                                [
                                    'message' => [
                                        'text' => 'Tracing the path from user input to insecure usage'
                                    ],
                                    'threadFlows' => [
                                        [
                                            'locations' => [
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 7,
                                                                'endLine' => 7,
                                                                'startColumn' => 46,
                                                                'endColumn' => 52
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 7,
                                                                'endLine' => 7,
                                                                'startColumn' => 46,
                                                                'endColumn' => 59
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 3,
                                                                'endLine' => 3,
                                                                'startColumn' => 36,
                                                                'endColumn' => 42
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 3,
                                                                'endLine' => 3,
                                                                'startColumn' => 36,
                                                                'endColumn' => 42
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 4,
                                                                'endLine' => 4,
                                                                'startColumn' => 12,
                                                                'endColumn' => 28
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 3,
                                                                'endLine' => 3,
                                                                'startColumn' => 45,
                                                                'endColumn' => 51
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 7,
                                                                'endLine' => 7,
                                                                'startColumn' => 1,
                                                                'endColumn' => 14
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 11,
                                                                'endLine' => 11,
                                                                'startColumn' => 6,
                                                                'endColumn' => 59
                                                            ]
                                                        ]
                                                    ]
                                                ],
                                                [
                                                    'location' => [
                                                        'physicalLocation' => [
                                                            'artifactLocation' => [
                                                                'uri' => 'taintflow-test/vulnerable.php'
                                                            ],
                                                            'region' => [
                                                                'startLine' => 11,
                                                                'endLine' => 11,
                                                                'startColumn' => 6,
                                                                'endColumn' => 59
                                                            ]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $sarif_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.sarif'])[0];

        $this->assertSame(
            $issue_data,
            json_decode(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $sarif_report_options), true)
        );
    }

    public function analyzeFileForReport() : void
    {
        $file_contents = '<?php
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
echo $a;';

        $this->addFile(
            'somefile.php',
            $file_contents
        );

        $this->analyzeFile('somefile.php', new Context());
    }

    public function testJsonReport(): void
    {
        $this->analyzeFileForReport();

        $issue_data = [
            [
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
                'error_level' => -1,
                'shortcode' => 24,
                'link' => 'https://psalm.dev/024',
                'taint_trace' => null
            ],
            [
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
                'error_level' => 1,
                'shortcode' => 138,
                'link' => 'https://psalm.dev/138',
                'taint_trace' => null
            ],
            [
                'severity' => 'error',
                'line_from' => 2,
                'line_to' => 2,
                'type' => 'MixedInferredReturnType',
                'message' => 'Could not verify return type \'null|string\' for psalmCanVerify',
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
                'error_level' => 1,
                'shortcode' => 47,
                'link' => 'https://psalm.dev/047',
                'taint_trace' => null
            ],
            [
                'severity' => 'error',
                'line_from' => 8,
                'line_to' => 8,
                'type' => 'UndefinedConstant',
                'message' => 'Const CHANGE_ME is not defined',
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
                'error_level' => -1,
                'shortcode' => 20,
                'link' => 'https://psalm.dev/020',
                'taint_trace' => null
            ],
            [
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
                'error_level' => 3,
                'shortcode' => 126,
                'link' => 'https://psalm.dev/126',
                'taint_trace' => null
            ],
        ];

        $json_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0];

        $this->assertSame(
            array_values($issue_data),
            json_decode(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $json_report_options), true)
        );
    }

    public function testFilteredJsonReportIsStillArray(): void
    {
        $issues_data = [
            22 => new \Psalm\Internal\Analyzer\IssueData(
                'info',
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
                8
            ),
        ];

        $report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0];
        $fixable_issue_counts = ['MixedInferredReturnType' => 1];

        $report = new JsonReport(
            $issues_data,
            $fixable_issue_counts,
            $report_options
        );
        $this->assertIsArray(json_decode($report->create()));
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
                    'ruleId' => 'MixedInferredReturnType',
                    'primaryLocation' => [
                        'message' => 'Could not verify return type \'null|string\' for psalmCanVerify',
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
            json_decode(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $sonarqube_report_options), true)
        );
    }

    public function testEmacsReport(): void
    {
        $this->analyzeFileForReport();

        $emacs_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.emacs'])[0];

        $this->assertSame(
            'somefile.php:3:10:error - Cannot find referenced variable $as_you_____type
somefile.php:3:10:error - Could not infer a return type
somefile.php:2:42:error - Could not verify return type \'null|string\' for psalmCanVerify
somefile.php:8:6:error - Const CHANGE_ME is not defined
somefile.php:17:6:warning - Possibly undefined global variable $a, first seen on line 11
',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $emacs_report_options)
        );
    }

    public function testPylintReport(): void
    {
        $this->analyzeFileForReport();

        $pylint_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.pylint'])[0];

        $this->assertSame(
            'somefile.php:3: [E0001] UndefinedVariable: Cannot find referenced variable $as_you_____type (column 10)
somefile.php:3: [E0001] MixedReturnStatement: Could not infer a return type (column 10)
somefile.php:2: [E0001] MixedInferredReturnType: Could not verify return type \'null|string\' for psalmCanVerify (column 42)
somefile.php:8: [E0001] UndefinedConstant: Const CHANGE_ME is not defined (column 6)
somefile.php:17: [W0001] PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 11 (column 6)
',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $pylint_report_options)
        );
    }

    public function testConsoleReport(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new Report\ReportOptions();
        $console_report_options->use_color = false;

        $this->assertSame(
            'ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)
  return $as_you_____type;

ERROR: MixedReturnStatement - somefile.php:3:10 - Could not infer a return type (see https://psalm.dev/138)
  return $as_you_____type;

ERROR: MixedInferredReturnType - somefile.php:2:42 - Could not verify return type \'null|string\' for psalmCanVerify (see https://psalm.dev/047)
function psalmCanVerify(int $your_code): ?string {

ERROR: UndefinedConstant - somefile.php:8:6 - Const CHANGE_ME is not defined (see https://psalm.dev/020)
echo CHANGE_ME;

INFO: PossiblyUndefinedGlobalVariable - somefile.php:17:6 - Possibly undefined global variable $a, first seen on line 11 (see https://psalm.dev/126)
echo $a

',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options)
        );
    }

    public function testConsoleReportNoInfo(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new Report\ReportOptions();
        $console_report_options->use_color = false;
        $console_report_options->show_info = false;

        $this->assertSame(
            'ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)
  return $as_you_____type;

ERROR: MixedReturnStatement - somefile.php:3:10 - Could not infer a return type (see https://psalm.dev/138)
  return $as_you_____type;

ERROR: MixedInferredReturnType - somefile.php:2:42 - Could not verify return type \'null|string\' for psalmCanVerify (see https://psalm.dev/047)
function psalmCanVerify(int $your_code): ?string {

ERROR: UndefinedConstant - somefile.php:8:6 - Const CHANGE_ME is not defined (see https://psalm.dev/020)
echo CHANGE_ME;

',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options)
        );
    }

    public function testConsoleReportNoSnippet(): void
    {
        $this->analyzeFileForReport();

        $console_report_options = new Report\ReportOptions();
        $console_report_options->show_snippet = false;
        $console_report_options->use_color = false;

        $this->assertSame(
            'ERROR: UndefinedVariable - somefile.php:3:10 - Cannot find referenced variable $as_you_____type (see https://psalm.dev/024)


ERROR: MixedReturnStatement - somefile.php:3:10 - Could not infer a return type (see https://psalm.dev/138)


ERROR: MixedInferredReturnType - somefile.php:2:42 - Could not verify return type \'null|string\' for psalmCanVerify (see https://psalm.dev/047)


ERROR: UndefinedConstant - somefile.php:8:6 - Const CHANGE_ME is not defined (see https://psalm.dev/020)


INFO: PossiblyUndefinedGlobalVariable - somefile.php:17:6 - Possibly undefined global variable $a, first seen on line 11 (see https://psalm.dev/126)


',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $console_report_options)
        );
    }

    public function testCompactReport(): void
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
            '| ERROR    | 3    | UndefinedVariable               | Cannot find referenced variable $as_you_____type              |' . "\n" .
            '| ERROR    | 3    | MixedReturnStatement            | Could not infer a return type                                 |' . "\n" .
            '| ERROR    | 2    | MixedInferredReturnType         | Could not verify return type \'null|string\' for psalmCanVerify |' . "\n" .
            '| ERROR    | 8    | UndefinedConstant               | Const CHANGE_ME is not defined                                |' . "\n" .
            '| INFO     | 17   | PossiblyUndefinedGlobalVariable | Possibly undefined global variable $a, first seen on line 11  |' . "\n" .
            '+----------+------+---------------------------------+---------------------------------------------------------------+' . "\n",
            $this->toUnixLineEndings(IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $compact_report_options))
        );
    }

    public function testCheckstyleReport(): void
    {
        $this->analyzeFileForReport();

        $checkstyle_report_options = ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.checkstyle.xml'])[0];

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
<file name="somefile.php">
 <error line="3" column="10" severity="error" message="UndefinedVariable: Cannot find referenced variable $as_you_____type"/>
</file>
<file name="somefile.php">
 <error line="3" column="10" severity="error" message="MixedReturnStatement: Could not infer a return type"/>
</file>
<file name="somefile.php">
 <error line="2" column="42" severity="error" message="MixedInferredReturnType: Could not verify return type \'null|string\' for psalmCanVerify"/>
</file>
<file name="somefile.php">
 <error line="8" column="6" severity="error" message="UndefinedConstant: Const CHANGE_ME is not defined"/>
</file>
<file name="somefile.php">
 <error line="17" column="6" severity="info" message="PossiblyUndefinedGlobalVariable: Possibly undefined global variable $a, first seen on line 11"/>
</file>
</checkstyle>
',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), $checkstyle_report_options)
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
            '<?xml version="1.0" encoding="UTF-8"?>
<testsuites failures="4" errors="0" name="psalm" tests="5" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="https://raw.githubusercontent.com/junit-team/junit5/r5.5.1/platform-tests/src/test/resources/jenkins-junit.xsd">
  <testsuite name="somefile.php" failures="4" errors="0" tests="5">
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
    <testcase name="somefile.php:2" classname="MixedInferredReturnType" assertions="1">
      <failure type="MixedInferredReturnType">message: Could not verify return type \'null|string\' for psalmCanVerify
type: MixedInferredReturnType
snippet: function psalmCanVerify(int $your_code): ?string {
selected_text: ?string
line: 2
column_from: 42
column_to: 49
</failure>
    </testcase>
    <testcase name="somefile.php:8" classname="UndefinedConstant" assertions="1">
      <failure type="UndefinedConstant">message: Const CHANGE_ME is not defined
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
',
            $xml
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

    public function testEmptyReportIfNotError(): void
    {
        $this->addFile(
            'somefile.php',
            '<?php ?>'
        );

        $this->analyzeFile('somefile.php', new Context());
        $this->assertSame(
            '[]
',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.json'])[0])
        );
        $this->assertSame(
            '',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.emacs'])[0])
        );
        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<report>
  <item/>
</report>
',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.xml'])[0])
        );

        $this->assertSame(
            '<?xml version="1.0" encoding="UTF-8"?>
<checkstyle>
</checkstyle>
',
            IssueBuffer::getOutput(IssueBuffer::getIssuesData(), ProjectAnalyzer::getFileReportOptions([__DIR__ . '/test-report.checkstyle.xml'])[0])
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
     *
     * @psalm-pure
     */
    private function toUnixLineEndings(string $output): string
    {
        return preg_replace('~\r\n?~', "\n", $output);
    }
}
