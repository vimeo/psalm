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
        $xml = Array2XML::createXML(
            'report',
            [
                'item' => array_map(
                    function (IssueData $issue_data): array {
                        return (array) $issue_data;
                    },
                    $this->issues_data
                )
            ]
        );

        return $xml->saveXML();
    }
}
