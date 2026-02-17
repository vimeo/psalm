<?php
namespace Vimeo\Test\DummyProject;

/** @psalm-immutable */
final class Bar
{
    use SomeTrait;

    /** @var string */
    public $x;

    /** @psalm-mutation-free */
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
