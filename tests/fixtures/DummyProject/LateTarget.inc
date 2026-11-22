<?php

namespace Vimeo\Test\DummyProject;

class LateTarget
{
    public function mixedThenInt($a): void {}
    public function intBeforeAndAfter($a): void {}
    public function onlyAfter($a): void {}
}
