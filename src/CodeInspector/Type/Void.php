<?php

namespace CodeInspector\Type;

class Void extends Type
{
    /** @var null|Void */
    private static $instance;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
