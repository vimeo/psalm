<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Client;

use Amp\Promise;
use JsonMapper;
use Psalm\Internal\LanguageServer\ClientHandler;
use Psalm\Internal\LanguageServer\LanguageServer;

/**
 * Provides method handlers for all textDocument/* methods
 *
 * @internal
 */
final class Workspace
{
    private ClientHandler $handler;

    /**
     * @psalm-suppress UnusedProperty
     */
    private JsonMapper $mapper;

    private LanguageServer $server;

    public function __construct(ClientHandler $handler, JsonMapper $mapper, LanguageServer $server)
    {
        $this->handler = $handler;
        $this->mapper = $mapper;
        $this->server = $server;
    }

    /**
     * The workspace/configuration request is sent from the server to the client to
     * fetch configuration settings from the client. The request can fetch several
     * configuration settings in one roundtrip. The order of the returned configuration
     * settings correspond to the order of the passed ConfigurationItems (e.g. the first
     * item in the response is the result for the first configuration item in the params).
     *
     * @param string $section The configuration section asked for.
     * @param string|null $scopeUri The scope to get the configuration section for.
     */
    public function requestConfiguration(string $section, ?string $scopeUri = null): Promise
    {
        $this->server->logDebug("workspace/configuration");

        /** @var Promise<object> */
        return $this->handler->request('workspace/configuration', [
            'items' => [
                [
                    'section' => $section,
                    'scopeUri' => $scopeUri,
                ],
            ],
        ]);
    }
}
