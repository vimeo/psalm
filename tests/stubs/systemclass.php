<?php

const ROOT_CONST_CONSTANT = 5;
define('ROOT_DEFINE_CONSTANT', 10);

class SystemClass
{
    const HELLO = 'hello';

    /**
     * @param int       $a
     * @param string    $b
     *
     * @return string
     */
    public function foo($a, $b)
    {
    }

    /**
     * @param int       $a
     * @param string    $b
     *
     * @return string
     */
    public static function bar($a, $b)
    {
    }
}
