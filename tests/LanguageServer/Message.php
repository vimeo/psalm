<?php

namespace Psalm\Tests\LanguageServer;

use AdvancedJsonRpc\Error;
use AdvancedJsonRpc\ErrorCode;
use AdvancedJsonRpc\ErrorResponse;
use AdvancedJsonRpc\Message as AdvancedJsonRpcMessage;
use AdvancedJsonRpc\Notification;
use AdvancedJsonRpc\Request;
use AdvancedJsonRpc\SuccessResponse;

/**
 * Base message
 */
abstract class Message extends AdvancedJsonRpcMessage
{
    /**
     * A String specifying the version of the JSON-RPC protocol. MUST be exactly "2.0".
     *
     * @var string
     */
    public $jsonrpc = '2.0';

    /**
     * Returns the appropriate Message subclass
     *
     * @param array $msg
     */
    public static function parseArray(array $msg): AdvancedJsonRpcMessage
    {
        $decoded = (object) $msg;
        if (Notification::isNotification($decoded)) {
            /** @psalm-suppress MixedArgument */
            $obj = new Notification($decoded->method, $decoded->params ?? null);
        } elseif (Request::isRequest($decoded)) {
            /** @psalm-suppress MixedArgument */
            $obj = new Request($decoded->id, $decoded->method, $decoded->params ?? null);
        } elseif (SuccessResponse::isSuccessResponse($decoded)) {
            /** @psalm-suppress MixedArgument */
            $obj = new SuccessResponse($decoded->id, $decoded->result);
        } elseif (ErrorResponse::isErrorResponse($decoded)) {
            /** @psalm-suppress MixedArgument, MixedPropertyFetch */
            $obj = new ErrorResponse($decoded->id, new Error($decoded->error->message, $decoded->error->code, $decoded->error->data ?? null));
        } else {
            throw new Error('Invalid message', ErrorCode::INVALID_REQUEST);
        }
        return $obj;
    }
}
