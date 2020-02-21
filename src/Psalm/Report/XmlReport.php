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
        $items = Array2XML::createXML(
            'report',
            [
                'item' => 
                    function (IssueData $issue_data) {
                        return (array) $issue_data;
                    },
                    $this->issues_data
                )
            ]
        );

        return $xml->saveXML();
    }
}
