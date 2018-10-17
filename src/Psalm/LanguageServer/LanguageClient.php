<?php
declare(strict_types = 1);

namespace Psalm\LanguageServer;

use JsonMapper;

class LanguageClient
{
    /**
     * Handles textDocument/* methods
     *
     * @var Client\TextDocument
     */
    public $textDocument;

    public function __construct(ProtocolReader $reader, ProtocolWriter $writer)
    {
        $handler = new ClientHandler($reader, $writer);
        $mapper = new JsonMapper;

        $this->textDocument = new Client\TextDocument($handler, $mapper);
    }
}
