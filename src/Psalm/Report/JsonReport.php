<?php
namespace Psalm\Report;

use Psalm\Report;

class JsonReport extends Report
{
    /**
     * {{@inheritdoc}}
     */
    public function create(): string
    {
        return json_encode($this->issues_data) . "\n";
    }
}
