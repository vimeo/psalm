<?php

declare(strict_types=1);

namespace Psalm\Exception;

use Exception;

/**
 * When `recursive-properties-of<T>` attempts to expand a cyclic reference,
 * this exception is raised in lieu of running into a stack overflow.
 */
final class RecursivePropertiesOfCycleException extends Exception
{
}
