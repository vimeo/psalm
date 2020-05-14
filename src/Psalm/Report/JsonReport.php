<?php

namespace Psalm\Report;

use Psalm\Internal\Json\Json;
use Psalm\Report;

use function array_values;

class JsonReport extends Report
{
    /**
     * {@inheritdoc}
     */
    public function create(): string
    {
        return Json::encode(array_values($this->issues_data)) . "\n";
    }
}
