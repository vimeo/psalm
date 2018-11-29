<?php

class A {
    /**
     * @var string
     */
    public $foo = "hello";

    public function bar(string $s) : string {
        return $s . " I’m here";
    }
}

class_alias("A", "B");
