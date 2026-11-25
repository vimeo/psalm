<?php

declare(strict_types=1);

namespace Psalm\Internal\Mcp;

use RuntimeException;

/**
 * A tool call that can't be answered, with a message that tells the agent what to do instead
 *
 * @internal
 */
final class ToolError extends RuntimeException
{
}
