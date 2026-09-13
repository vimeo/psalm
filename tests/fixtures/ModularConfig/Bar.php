<?php
namespace Vimeo\Test\DummyProject;

final class Bar
{
    use SomeTrait;

    /** @var string */
    public $x;

    /** @psalm-external-mutation-free */
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
