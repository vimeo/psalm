<?php
namespace Psalm\Report;

use LSS\Array2XML;
use Psalm\Report;
use Psalm\Internal\Analyzer\IssueData;
use function array_map;

class XmlReport extends Report
{
    /**
     * {@inheritdoc}
     */
    public function create(): string
    {
        $items = array_map(
            /**
             * @return array{severity: string, line_from: int, line_to: int, type: string, message: string,
             * file_name: string, file_path: string, snippet: string, from: int, to: int,
             * snippet_from: int, snippet_to: int, column_from: int, column_to: int, selected_text: string}
             */
            function (IssueData $issue_data) {
                return [
                    'severity'      => $issue_data->severity,
                    'line_from'     => $issue_data->line_from,
                    'line_to'       => $issue_data->line_to,
                    'type'          => $issue_data->type,
                    'message'       => $issue_data->message,
                    'file_name'     => $issue_data->file_name,
                    'file_path'     => $issue_data->file_path,
                    'snippet'       => $issue_data->snippet,
                    'from'          => $issue_data->from,
                    'to'            => $issue_data->to,
                    'snippet_from'  => $issue_data->snippet_from,
                    'snippet_to'    => $issue_data->snippet_to,
                    'column_from'   => $issue_data->column_from,
                    'column_to'     => $issue_data->column_to,
                    'selected_text' => $issue_data->selected_text,
                ];
            },
            $this->issues_data
        );
        $xml = Array2XML::createXML('report', ['item' => $items]);

        return $xml->saveXML();
    }
}
