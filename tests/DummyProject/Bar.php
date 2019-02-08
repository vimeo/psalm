<?php

namespace Vimeo\Test\DummyProject;

class Bar
{
    /** @var string */
    public $x;

    public function __construct()
    {
        $this->x = "hello";
    }
}

function someFunction() : void
{
    echo "here";
}
