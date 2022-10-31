<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Client;

use Amp\Promise;
use Generator;
use JsonMapper;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use Psalm\Internal\LanguageServer\ClientHandler;

use function Amp\call;

/**
 * Provides method handlers for all textDocument/* methods
 *
 * @internal
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
     * @param Diagnostic[] $diagnostics
     */
    public function publishDiagnostics(string $uri, array $diagnostics): void
    {
        $this->handler->notify('textDocument/publishDiagnostics', [
            'uri' => $uri,
            'diagnostics' => $diagnostics,
        ]);
    }

    /**
     * The content request is sent from a server to a client
     * to request the current content of a text document identified by the URI
     *
     * @param TextDocumentIdentifier $textDocument The document to get the content for
     *
     * @return Promise<TextDocumentItem> The document's current content
     *
     * @psalm-suppress MixedReturnTypeCoercion due to Psalm bug
     */
    public function xcontent(TextDocumentIdentifier $textDocument): Promise
    {
        return call(
            /**
             * @return Generator<int, Promise<object>, object, TextDocumentItem>
             */
            static function () use ($textDocument) {
                /** @var Promise<object> */
                $promise = $this->handler->request(
                    'textDocument/xcontent',
                    ['textDocument' => $textDocument]
                );

                $result = yield $promise;

                /** @var TextDocumentItem */
                return $this->mapper->map($result, new TextDocumentItem);
            }
        );
    }
}
