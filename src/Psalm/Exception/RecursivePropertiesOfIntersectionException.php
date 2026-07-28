<?php

declare(strict_types=1);

namespace Psalm\Exception;

use Exception;

/**
 * The behaviour of intersection types within `recursive-properties-of<T>` is
 * not defined. If an intersection type is encountered during expansion, this
 * exception is thrown.
 */
final class RecursivePropertiesOfIntersectionException extends Exception
{
}
