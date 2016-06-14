<?php

namespace CodeInspector\Type;

class Mixed extends Type
{
    /** @var null|Mixed */
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
