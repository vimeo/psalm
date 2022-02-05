<?php

namespace Psalm\Exception;

use Exception;

final class UnresolvableConstantException extends Exception
{
    /**
     * @var string
     */
    public $class_name;

    /**
     * @var string
     */
    public $const_name;

    public function __construct(string $class_name, string $const_name)
    {
        $this->class_name = $class_name;
        $this->const_name = $const_name;
    }
}
