<?php
namespace Vimeo\Test\DummyProject;

class Bar
{
    use SomeTrait;

    /** @var string */
    public $x = 'hello';

    public function __construct()
    {
    }
}

/**
 * @return void
 */
function someFunction()
{
    echo 'here';
}
