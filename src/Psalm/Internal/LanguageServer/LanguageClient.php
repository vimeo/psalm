<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use JsonMapper;
use LanguageServerProtocol\LogMessage;
use LanguageServerProtocol\LogTrace;
use Psalm\Internal\LanguageServer\Client\Progress\LegacyProgress;
use Psalm\Internal\LanguageServer\Client\Progress\Progress;
use Psalm\Internal\LanguageServer\Client\Progress\ProgressInterface;
use Psalm\Internal\LanguageServer\Client\TextDocument as ClientTextDocument;
use Psalm\Internal\LanguageServer\Client\Workspace as ClientWorkspace;

use function is_null;
use function json_decode;
use function json_encode;

/**
 * @internal
 */
final class LanguageClient
{
    /**
     * Handles textDocument/* methods
     */
    public ClientTextDocument $textDocument;

    /**
     * Handles workspace/* methods
     */
    public ClientWorkspace $workspace;

    /**
     * The client handler
     */
    private ClientHandler $handler;

    /**
     * The Language Server
     */
    private LanguageServer $server;

    /**
     * The Client Configuration
     */
    public ClientConfiguration $clientConfiguration;

    public function __construct(
        ProtocolReader $reader,
        ProtocolWriter $writer,
        LanguageServer $server,
        ClientConfiguration $clientConfiguration
    ) {
        $this->handler = new ClientHandler($reader, $writer);
        $this->server = $server;

        $this->textDocument = new ClientTextDocument($this->handler, $this->server);
        $this->workspace = new ClientWorkspace($this->handler, new JsonMapper, $this->server);
        $this->clientConfiguration = $clientConfiguration;
    }

    /**
     * Request Configuration from Client and save it
     */
    public function refreshConfiguration(): void
    {
        $capabilities = $this->server->clientCapabilities;
        if ($capabilities->workspace->configuration ?? false) {
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
            $logTrace,
        );
    }

    /**
     * Send a log message to the client.
     */
    public function logMessage(LogMessage $logMessage): void
    {
        $this->handler->notify(
            'window/logMessage',
            $logMessage,
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
     */
    public function event(LogMessage $logMessage): void
    {
        $this->handler->notify(
            'telemetry/event',
            $logMessage,
        );
    }

    public function makeProgress(string $token): ProgressInterface
    {
        if ($this->server->clientCapabilities->window->workDoneProgress ?? false) {
            return new Progress($this->handler, $token);
        } else {
            return new LegacyProgress($this->handler);
        }
    }

    /**
     * Configuration Refreshed from Client
     */
    private function configurationRefreshed(array $config): void
    {
        //do things when the config is refreshed

        if (empty($config)) {
            return;
        }

        /** @var array */
        $array = json_decode(json_encode($config), true);

        if (isset($array['hideWarnings'])) {
            $this->clientConfiguration->hideWarnings = (bool) $array['hideWarnings'];
        }

        if (isset($array['provideCompletion'])) {
            $this->clientConfiguration->provideCompletion = (bool) $array['provideCompletion'];
        }

        if (isset($array['provideDefinition'])) {
            $this->clientConfiguration->provideDefinition = (bool) $array['provideDefinition'];
        }

        if (isset($array['provideHover'])) {
            $this->clientConfiguration->provideHover = (bool) $array['provideHover'];
        }

        if (isset($array['provideSignatureHelp'])) {
            $this->clientConfiguration->provideSignatureHelp = (bool) $array['provideSignatureHelp'];
        }

        if (isset($array['provideCodeActions'])) {
            $this->clientConfiguration->provideCodeActions = (bool) $array['provideCodeActions'];
        }

        if (isset($array['provideDiagnostics'])) {
            $this->clientConfiguration->provideDiagnostics = (bool) $array['provideDiagnostics'];
        }

        if (isset($array['findUnusedVariables'])) {
            $this->clientConfiguration->findUnusedVariables = (bool) $array['findUnusedVariables'];
        }
    }
}
