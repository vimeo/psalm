<?php

declare(strict_types=1);

namespace Psalm\Internal\Mcp;

use RuntimeException;

/**
 * A JSON-RPC error response, with its error code as the exception code
 *
 * @internal
 */
final class McpError extends RuntimeException
{
    public const PARSE_ERROR = -32_700;
    public const INVALID_REQUEST = -32_600;
    public const METHOD_NOT_FOUND = -32_601;
    public const INVALID_PARAMS = -32_602;

    /**
     * @psalm-capabilities read-props
     */
    public function __construct(int $code, string $message)
    {
        parent::__construct($message, $code);
    }
}
