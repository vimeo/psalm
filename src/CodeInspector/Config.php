<?php

namespace CodeInspector;

class Config
{
    private static $_config;

    public $stopOnError;

    private function __construct()
    {
        self::$_config = $this;
    }

    public static function getInstance()
    {
        if (self::$_config) {
            return self::$_config;
        }

        return new self();
    }

    public function doesInheritVariables($file_name)
    {
        return false;
    }
}
