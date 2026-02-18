<?php

declare(strict_types=1);

namespace Psalm\Exception;

use Exception;

/**
 * @psalm-immutable
 */
final class UnresolvableConstantException extends Exception
{
    /**
     * @psalm-mutation-free
     */
    public function __construct(public string $class_name, public string $const_name)
    {
    }
}
