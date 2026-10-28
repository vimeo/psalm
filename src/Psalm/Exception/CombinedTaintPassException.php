<?php

declare(strict_types=1);

namespace Psalm\Exception;

use RuntimeException;

/**
 * The taint analysis pass of a combined taint analysis run could not run or failed.
 */
final class CombinedTaintPassException extends RuntimeException
{
}
