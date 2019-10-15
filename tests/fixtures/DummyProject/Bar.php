<?php
namespace Vimeo\Test\DummyProject;

class Bar
{
    use SomeTrait;

    /** @var string */
    public $x;

    public function __construct()
    {
        $this->x = 'hello';
    }
}

/**
 * @return void
 */
function someFunction()
{
    echo 'here';
}
