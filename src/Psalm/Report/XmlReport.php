<?php

namespace Psalm\Report;

use Psalm\Internal\Analyzer\DataFlowNodeData;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Report;
use Spatie\ArrayToXml\ArrayToXml;

use function array_map;
use function get_object_vars;

final class XmlReport extends Report
{
    public function create(): string
    {
        $xml = ArrayToXml::convert(
            [
                'item' => array_map(
                    static function (IssueData $issue_data): array {
                        $issue_data = get_object_vars($issue_data);
                        unset($issue_data['dupe_key']);

                        if (null !== $issue_data['taint_trace']) {
                            $issue_data['taint_trace'] = array_map(
                                static fn($trace): array => (array) $trace,
                                $issue_data['taint_trace'],
                            );
                        }

                        // replace null values, as XML serializers tend to have problems with them
                        $issue_data['taint_trace'] ??= '';

                        if (null !== $issue_data['other_references']) {
                            $issue_data['other_references'] = array_map(
                                static fn(DataFlowNodeData $reference): array => (array) $reference,
                                $issue_data['other_references'],
                            );
                        }

                        // replace null values, as XML serializers tend to have problems with them
                        $issue_data['other_references'] ??= '';

                        return $issue_data;
                    },
                    $this->issues_data,
                ),
            ],
            'report',
            true,
            'UTF-8',
            '1.0',
            ['preserveWhiteSpace' => false, 'formatOutput' => true],
        );

        return $xml;
    }
}
