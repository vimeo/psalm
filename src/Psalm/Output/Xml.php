<?php
namespace Psalm\Output;

use LSS\Array2XML;
use Psalm\Output;

class Xml extends Output
{
    /**
     * {{@inheritdoc}}
     */
    public function create(): string
    {
        $xml = Array2XML::createXML('report', ['item' => $this->issues_data]);

        return $xml->saveXML();
    }
}
