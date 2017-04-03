<?php
namespace Psalm\Tests;

use Psalm\Config;

class TestConfig extends Config
{
    /**
     * @psalm-suppress InvalidPropertyAssignment because cache_directory isn't strictly nullable
     */
    public function __construct()
    {
        parent::__construct();

        $this->throw_exception = true;
        $this->use_docblock_types = true;
        $this->totally_typed = true;
        $this->cache_directory = null;

        $this->collectPredefinedConstants();
        $this->collectPredefinedFunctions();
    }
}
