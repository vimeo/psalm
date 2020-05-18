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
        $options = $this->pretty ? Json::PRETTY : Json::DEFAULT;

        return Json::encode(array_values($this->issues_data), $options) . "\n";
    }
}
