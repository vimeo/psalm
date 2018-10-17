<?php
declare(strict_types = 1);

namespace Psalm\LanguageServer;

use Psalm\LanguageServer\Message;
use Sabre\Event\Promise;

interface ProtocolWriter
{
    /**
     * Sends a Message to the client
     *
     * @param Message $msg
     * @return Promise Resolved when the message has been fully written out to the output stream
     */
    public function write(Message $msg): Promise;
}
