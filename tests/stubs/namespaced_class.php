<?php

namespace Foo;

const BAR = "bat";

class SystemClass  {
    const HELLO = 'hello';

    /**
     * @param int       $a
     * @param string    $b
     * @return string
     */
    public function foo($a, $b) {}

    /**
     * @param int       $a
     * @param string    $b
     * @return string
     */
    public static function bar($a, $b) {}
}
