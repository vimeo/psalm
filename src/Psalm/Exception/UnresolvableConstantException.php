<?php

declare(strict_types=1);

namespace Psalm\Exception;

use Exception;

final class UnresolvableConstantException extends Exception
{
    public string $class_name;

    public string $const_name;

    public function __construct(string $class_name, string $const_name)
    {
        $this->class_name = $class_name;
        $this->const_name = $const_name;
    }
}
