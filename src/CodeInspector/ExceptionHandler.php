<?php

namespace CodeInspector;

class ExceptionHandler
{
    public static function accepts(Exception\CodeException $e)
    {
        throw $e;
        //return true;
    }
}
