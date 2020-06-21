<?php

interface Bar {}

class Foo
{
    /**
     * @self-out Bar
     * @return void
     */
    public function makeBar() {}
}

$foo = new Foo();
$foo->makeBar();
$foo->makeBar();
