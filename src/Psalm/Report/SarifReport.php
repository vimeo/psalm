<?php

declare(strict_types=1);

namespace Psalm\Report;

use Override;
use Psalm\Config;
use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Json\Json;
use Psalm\Report;

use function file_exists;
use function file_get_contents;
use function max;
use function str_starts_with;

/**
 * SARIF report format suitable for import into any SARIF compatible solution
 *
 * https://docs.oasis-open.org/sarif/sarif/v2.1.0/cs01/sarif-v2.1.0-cs01.html
 *
 * @api
 */
final class SarifReport extends Report
{
    /**
     * The score (0.0 to 10.0, as a string) GitHub Code Scanning sorts a security alert by: 9.0 and more is critical,
     * 7.0 high, 4.0 medium.
     */
    private const SECURITY_SEVERITIES = [
        'TaintedShell' => '9.8',
        'TaintedEval' => '9.8',
        'TaintedSql' => '9.8',
        'TaintedInclude' => '9.8',
        'TaintedUnserialize' => '9.8',
        'TaintedCallable' => '9.8',
        'TaintedNosql' => '9.1',
        'TaintedLdap' => '9.1',
        'TaintedXpath' => '9.1',
        'TaintedExtract' => '8.8',
        'TaintedFile' => '7.5',
        'TaintedSSRF' => '7.5',
        'TaintedLlmPrompt' => '7.5',
        'TaintedUserSecret' => '7.5',
        'TaintedSystemSecret' => '7.5',
        'TaintedHtml' => '6.1',
        'TaintedTextWithQuotes' => '6.1',
        'TaintedHeader' => '6.1',
        'TaintedCookie' => '6.1',
        'TaintedUrlComponent' => '5.3',
        'TaintedUrlPath' => '5.3',
        'TaintedSleep' => '5.3',
    ];

    private const DEFAULT_SECURITY_SEVERITY = '7.0';


    #[Override]
    public function create(): string
    {
        $report = [
            'version' => '2.1.0',
            '$schema' => 'https://json.schemastore.org/sarif-2.1.0.json',
            'runs' => [
                [
                    'tool' => [
                        'driver' => [
                            'name' => 'Psalm',
                            'informationUri' => 'https://psalm.dev',
                            'version' => PSALM_VERSION,
                        ],
                    ],
                    'results' => [],
                ],
            ],
        ];

        $rules = [];

        foreach ($this->issues_data as $issue_data) {
            $rules[$issue_data->shortcode] = [
                'id' => (string)$issue_data->shortcode,
                'name' => $issue_data->type,
                'shortDescription' => [
                    'text' => $issue_data->type,
                ],
                'properties' => str_starts_with($issue_data->type, 'Tainted')
                    ? [
                        'tags' => ['security'],
                        'security-severity' => self::SECURITY_SEVERITIES[$issue_data->type]
                            ?? self::DEFAULT_SECURITY_SEVERITY,
                    ]
                    : ['tags' => ['maintainability']],
                'helpUri' => $issue_data->link,
            ];

            $markdown_documentation_path = __DIR__ . '/../../../docs/running_psalm/issues/' . $issue_data->type . '.md';
            if (file_exists($markdown_documentation_path)) {
                $markdown_documentation = file_get_contents($markdown_documentation_path);
                $rules[$issue_data->shortcode]['help']['markdown'] = $markdown_documentation;
                $rules[$issue_data->shortcode]['help']['text'] = $markdown_documentation;
            }

            $jsonEntry = [
                'ruleId' => (string)$issue_data->shortcode,
                'message' => [
                    'text' => $issue_data->message,
                ],
                'level' => ($issue_data->severity === Config::REPORT_ERROR) ? 'error' : 'note',
                'rank' => (float) ($issue_data->error_level > 0
                    ? (90 + (8 - $issue_data->error_level)) // 8 to 1 => 90 to 97
                    : (100 + $issue_data->error_level) // -1 to -2 => 99 to 98
                ),
                'locations' => [
                    [
                        'physicalLocation' => [
                            'artifactLocation' => [
                                'uri' => $issue_data->file_name,
                            ],
                            'region' => [
                                'startLine' => max(1, $issue_data->line_from),
                                'endLine' => max(1, $issue_data->line_to),
                                'startColumn' => max(1, $issue_data->column_from),
                                'endColumn' => max(1, $issue_data->column_to),
                            ],
                        ],
                    ],
                ],
            ];

            if ($issue_data->taint_trace != null) {
                $jsonEntry['codeFlows'] = [
                    [
                        'message' => [
                            'text' => 'Tracing the path from user input to insecure usage',
                        ],
                        'threadFlows' => [
                            [
                                'locations' => [],
                            ],
                        ],
                    ],
                ];

                foreach ($issue_data->taint_trace as $trace) {
                    if ($trace instanceof DataFlowNodeData
                        && $trace->line_from > 0
                    ) {
                        $jsonEntry['codeFlows'][0]['threadFlows'][0]['locations'][] = [
                            'location' => [
                                'physicalLocation' => [
                                    'artifactLocation' => [
                                        'uri' => $trace->file_name,
                                    ],
                                    'region' => [
                                        'startLine' => $trace->line_from,
                                        'endLine' => $trace->line_to,
                                        'startColumn' => $trace->column_from,
                                        'endColumn' => $trace->column_to,
                                    ],
                                ],
                            ],
                        ];
                    }
                }
            }

            $report['runs'][0]['results'][] = $jsonEntry;
        }

        foreach ($rules as $rule) {
            $report['runs'][0]['tool']['driver']['rules'][] = $rule;
        }

        $options = $this->pretty ? Json::PRETTY : Json::DEFAULT;

        return Json::encode($report, $options) . "\n";
    }
}
