<?php
namespace Psalm\Output;

use Psalm\Output;

class Json extends Output
{
    /**
     * {{@inheritdoc}}
     */
    public function create(): string
    {
        return json_encode($this->issues_data) . "\n";
    }
}
