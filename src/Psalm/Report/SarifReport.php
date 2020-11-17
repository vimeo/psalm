<?php
namespace Psalm\Report;

use Psalm\Internal\Json\Json;
use function max;
use Psalm\Config;
use Psalm\Issue;
use Psalm\Report;
use function file_exists;
use function file_get_contents;

/**
 * SARIF report format suitable for import into any SARIF compatible solution
 *
 * https://docs.oasis-open.org/sarif/sarif/v2.1.0/cs01/sarif-v2.1.0-cs01.html
 */
class SarifReport extends Report
{
    public function create(): string
    {
        $report = [
            'version' => '2.1.0',
            'runs' => [
                [
                    'tool' => [
                        'driver' => [
                            'name' => 'Psalm',
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
                'properties' => [
                    'tags' => [
                        (\substr($issue_data->type, 0, 7) === 'Tainted') ? 'security' : 'maintainability',
                    ],
                ],
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
                'locations' => [
                    [
                        'physicalLocation' => [
                            'artifactLocation' => [
                                'uri' => $issue_data->file_name,
                            ],
                            'region' => [
                                'startLine' => $issue_data->line_from,
                                'endLine' => $issue_data->line_to,
                                'startColumn' => $issue_data->column_from,
                                'endColumn' => $issue_data->column_to,
                            ],
                        ],
                    ]
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
                    ]
                ];

                /** @var \Psalm\Internal\Analyzer\DataFlowNodeData $trace */
                foreach ($issue_data->taint_trace as $trace) {
                    if (isset($trace->line_from)) {
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
