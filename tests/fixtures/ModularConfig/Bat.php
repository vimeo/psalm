<?php
namespace Vimeo\Test\DummyProject;

final class Bat
{
    public function __construct()
    {
        $a = new Bar();

        someFunction();
        someOtherFunction();
    }
}
