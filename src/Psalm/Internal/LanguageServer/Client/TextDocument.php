<?php
declare(strict_types = 1);

namespace Psalm\Internal\LanguageServer\Client;

use Psalm\Internal\LanguageServer\ClientHandler;
use LanguageServerProtocol\{Diagnostic, TextDocumentItem, TextDocumentIdentifier};
use Amp\Promise;
use JsonMapper;

/**
 * Provides method handlers for all textDocument/* methods
 */
class TextDocument
{
    /**
     * @var ClientHandler
     */
    private $handler;

    /**
     * @var JsonMapper
     */
    private $mapper;

    public function __construct(ClientHandler $handler, JsonMapper $mapper)
    {
        $this->handler = $handler;
        $this->mapper = $mapper;
    }

    /**
     * Diagnostics notification are sent from the server to the client to signal results of validation runs.
     *
     * @param string $uri
     * @param Diagnostic[] $diagnostics
     * @return Promise <void>
     */
    public function publishDiagnostics(string $uri, array $diagnostics): Promise
    {
        return $this->handler->notify('textDocument/publishDiagnostics', [
            'uri' => $uri,
            'diagnostics' => $diagnostics
        ]);
    }

    /**
     * The content request is sent from a server to a client
     * to request the current content of a text document identified by the URI
     *
     * @param TextDocumentIdentifier $textDocument The document to get the content for
     * @return Promise <TextDocumentItem> The document's current content
     */
    public function xcontent(TextDocumentIdentifier $textDocument): Promise
    {
        $promise = $this->handler->request(
            'textDocument/xcontent',
            ['textDocument' => $textDocument]
        );

        $promise->onResolve(
            /**
             * @param object $result
             * @return object
             */
            function ($result) {
                return $this->mapper->map($result, new TextDocumentItem);
            }
        );

        return $promise;
    }
}
