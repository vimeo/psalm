<?php

namespace Psalm\Tests\LanguageServer;

use AdvancedJsonRpc\Message as AdvancedJsonRpcMessage;
use AdvancedJsonRpc\Notification;
use AdvancedJsonRpc\Request;
use AdvancedJsonRpc\SuccessResponse;
use AdvancedJsonRpc\ErrorResponse;

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
     * @return AdvancedJsonRpcMessage
     */
    public static function parseArray(array $msg): AdvancedJsonRpcMessage
    {
        $decoded = (object) $msg;
        if (Notification::isNotification($decoded)) {
            $obj = new Notification($decoded->method, $decoded->params ?? null);
        } else if (Request::isRequest($decoded)) {
            $obj = new Request($decoded->id, $decoded->method, $decoded->params ?? null);
        } else if (SuccessResponse::isSuccessResponse($decoded)) {
            $obj = new SuccessResponse($decoded->id, $decoded->result);
        } else if (ErrorResponse::isErrorResponse($decoded)) {
            $obj = new ErrorResponse($decoded->id, new Error($decoded->error->message, $decoded->error->code, $decoded->error->data ?? null));
        } else {
            throw new Error('Invalid message', ErrorCode::INVALID_REQUEST);
        }
        return $obj;
    }
}
