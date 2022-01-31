<?php


namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\MessageType;
use Psalm\Progress\Progress as Base;

/**
 * @internal
 */
class Progress extends Base
{

    /**
     * @var LanguageServer
     */
    private $server;

    public function __construct(LanguageServer $server) {
        $this->server = $server;
    }

    public function debug(string $message): void
    {
        $this->server->logDebug($message);
    }

    public function write(string $message): void
    {
        $this->server->logInfo($message);
    }
}