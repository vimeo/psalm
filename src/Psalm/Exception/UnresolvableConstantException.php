<?php

declare(strict_types=1);

namespace Psalm\Exception;

use Exception;

final class UnresolvableConstantException extends Exception
{
    public function __construct(public string $class_name, public string $const_name)
    {
    }
}
