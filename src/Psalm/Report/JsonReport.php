<?php
namespace Psalm\Report;

use Psalm\Report;
use function json_encode;

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
