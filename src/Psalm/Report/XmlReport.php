<?php
namespace Psalm\Report;

use LSS\Array2XML;
use Psalm\Report;

class XmlReport extends Report
{
    /**
     * {@inheritdoc}
     */
    public function create(): string
    {
        $xml = Array2XML::createXML('report', ['item' => $this->issues_data]);

        return $xml->saveXML();
    }
}
