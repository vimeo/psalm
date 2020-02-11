<?php

namespace Psalm\Report;

use Psalm\Report;

use function json_encode;
use function array_values;

class JsonReport extends Report
{
    /**
     * {@inheritdoc}
     */
    public function create(): string
    {
        return json_encode(array_values($this->issues_data)) . "\n";
    }
}
