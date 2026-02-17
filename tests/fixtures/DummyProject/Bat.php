<?php
namespace Vimeo\Test\DummyProject;

/** @psalm-mutable */
final class Bat
{
    public function __construct()
    {
        $a = new Bar();

        someFunction();
        someOtherFunction();
    }
}
