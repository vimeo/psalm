<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use JsonMapper;
use LanguageServerProtocol\LogMessage;
use LanguageServerProtocol\LogTrace;
use Psalm\Internal\LanguageServer\Client\TextDocument as ClientTextDocument;
use Psalm\Internal\LanguageServer\Client\Workspace as ClientWorkspace;

use function is_null;

/**
 * @internal
 */
class LanguageClient
{
    /**
     * Handles textDocument/* methods
     *
     * @var ClientTextDocument
     */
    public $textDocument;

    /**
     * Handles workspace/* methods
     *
     * @var ClientWorkspace
     */
    public $workspace;

    /**
     * The client handler
     *
     * @var ClientHandler
     */
    private $handler;

    /**
     * The Language Server
     *
     * @var LanguageServer
     */
    private $server;

    /**
     * The Client Configuration
     *
     * @var ClientConfiguration
     */
    public $clientConfiguration;

    /**
     * The JsonMapper
     *
     * @var JsonMapper
     */
    private $mapper;

    public function __construct(
        ProtocolReader $reader,
        ProtocolWriter $writer,
        LanguageServer $server,
        ClientConfiguration $clientConfiguration
    ) {
        $this->handler = new ClientHandler($reader, $writer);
        $this->mapper = new JsonMapper;
        $this->server = $server;

        $this->textDocument = new ClientTextDocument($this->handler, $this->server);
        $this->workspace = new ClientWorkspace($this->handler, $this->mapper, $this->server);
        $this->clientConfiguration = $clientConfiguration;
    }

    /**
     * Request Configuration from Client and save it
     */
    public function refreshConfiguration(): void
    {
        $capabilities = $this->server->clientCapabilities;
        if ($capabilities && $capabilities->workspace && $capabilities->workspace->configuration) {
            $this->workspace->requestConfiguration('psalm')->onResolve(function ($error, $value): void {
                if ($error) {
                    $this->server->logError('There was an error getting configuration');
                } else {
                    /** @var array<int, object> $value */
                    [$config] = $value;
                    $this->configurationRefreshed((array) $config);
                }
            });
        }
    }

    /**
     * A notification to log the trace of the server’s execution.
     * The amount and content of these notifications depends on the current trace configuration.
     *
     * @param LogTrace $logTrace
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function logTrace(LogTrace $logTrace): void
    {
        //If trace is 'off', the server should not send any logTrace notification.
        if (is_null($this->server->trace) || $this->server->trace === 'off') {
            return;
        }

        //If trace is 'messages', the server should not add the 'verbose' field in the LogTraceParams.
        if ($this->server->trace === 'messages') {
            $logTrace->verbose = null;
        }

        $this->handler->notify(
            '$/logTrace',
            $logTrace
        );
    }

    /**
     * Send a log message to the client.
     *
     * @param LogMessage $message
     */
    public function logMessage(LogMessage $logMessage): void
    {
        $this->handler->notify(
            'window/logMessage',
            $logMessage
        );
    }

    /**
     * The telemetry notification is sent from the
     * server to the client to ask the client to log
     * a telemetry event.
     *
     * The protocol doesn’t specify the payload since no
     * interpretation of the data happens in the protocol.
     * Most clients even don’t handle the event directly
     * but forward them to the extensions owing the corresponding
     * server issuing the event.
     *
     * @param LogMessage $logMessage
     */
    public function event(LogMessage $logMessage): void
    {
        $this->handler->notify(
            'telemetry/event',
            $logMessage
        );
    }

    /**
     * Configuration Refreshed from Client
     *
     * @param array $config
     */
    private function configurationRefreshed(array $config): void
    {
        //do things when the config is refreshed

        if (empty($config)) {
            return;
        }

        if (!is_null($this->clientConfiguration->provideCompletion)) {
            //$this->server->project_analyzer->provide_completion = $this->clientConfiguration->provideCompletion;
        }
    }
}
