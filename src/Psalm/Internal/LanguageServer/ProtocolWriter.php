<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

interface ProtocolWriter
{
    /**
     * Sends a Message to the client.
     */
    public function write(Message $msg): void;
}
