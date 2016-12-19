<?php
namespace Psalm\Tests;

use Psalm\Config;

class TestConfig extends Config
{
    public function __construct()
    {
        parent::__construct();

        $this->throw_exception = true;
        $this->use_docblock_types = true;
    }
}
