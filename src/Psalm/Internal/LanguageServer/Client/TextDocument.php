<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Client;

use LanguageServerProtocol\Diagnostic;
use Psalm\Internal\LanguageServer\ClientHandler;
use Psalm\Internal\LanguageServer\LanguageServer;

/**
 * Provides method handlers for all textDocument/* methods
 *
 * @internal
 */
final class TextDocument
{
    private ClientHandler $handler;

    private LanguageServer $server;

    public function __construct(ClientHandler $handler, LanguageServer $server)
    {
        $this->handler = $handler;
        $this->server = $server;
    }

    /**
     * Diagnostics notification are sent from the server to the client to signal results of validation runs.
     *
     * @param Diagnostic[] $diagnostics
     */
    public function publishDiagnostics(string $uri, array $diagnostics, ?int $version = null): void
    {
        if (!$this->server->client->clientConfiguration->provideDiagnostics) {
            return;
        }

        $this->server->logDebug("textDocument/publishDiagnostics");

        $this->handler->notify('textDocument/publishDiagnostics', [
            'uri' => $uri,
            'diagnostics' => $diagnostics,
            'version' => $version,
        ]);
    }
}
