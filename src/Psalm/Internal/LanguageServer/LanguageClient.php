<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use JsonMapper;
use Psalm\Internal\LanguageServer\Client\TextDocument as ClientTextDocument;
use Psalm\Internal\LanguageServer\Client\Workspace as ClientWorkspace;
use Psalm\Internal\LanguageServer\LanguageServerProtocol\ClientCapabilities;

use function json_decode;
use function json_encode;

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
     * The client capabilities
     *
     * @var ClientCapabilities|null
     */
    private ?ClientCapabilities $capabilities = null;

    /**
     * The Client Configuration
     *
     * @var ClientConfiguration
     */
    private ClientConfiguration $configuration;

    public function __construct(ProtocolReader $reader, ProtocolWriter $writer)
    {
        $this->handler = new ClientHandler($reader, $writer);
        $mapper = new JsonMapper;

        $this->textDocument = new ClientTextDocument($this->handler, $mapper);
        $this->workspace = new ClientWorkspace($this->handler, $mapper);
        $this->configuration = new ClientConfiguration();
    }

    /**
     * Request Configuration from Client and save it
     */
    public function refreshConfiguration(): void
    {
        $capabilities = $this->getCapabilities();
        if ($capabilities && $capabilities->workspace && $capabilities->workspace->configuration) {
            $this->workspace->requestConfiguration('psalm')->onResolve(function ($error, $value): void {
                if ($error) {
                    $this->logMessage('There was an error getting configuration', 1);
                } else {
                    /** @var array|null $json */
                    $json = json_decode(json_encode($value), true);
                    if ($json) {
                        /** @var array $config */
                        [$config] = $json;
                        $this->updateConfiguration($config);
                    }
                }
            });
        }
    }

    /**
     * Send a log message to the client.
     *
     * @param string $message The message to send to the client.
     * @psalm-param 1|2|3|4 $type
     * @param int $type The log type:
     *  - 1 = Error
     *  - 2 = Warning
     *  - 3 = Info
     *  - 4 = Log
     */
    public function logMessage(string $message, int $type = 4, string $method = 'window/logMessage'): void
    {
        // https://microsoft.github.io/language-server-protocol/specifications/specification-current/#window_logMessage

        if ($type < 1 || $type > 4) {
            $type = 4;
        }

        $this->handler->notify(
            $method,
            [
                'type' => $type,
                'message' => $message
            ]
        );
    }

    /**
     * Get the value of capabilities
     */
    public function getCapabilities(): ?ClientCapabilities
    {
        return $this->capabilities;
    }

    /**
     * Set the value of capabilities
     *
     * @param ClientCapabilities $capabilities
     *
     */
    public function setCapabilities(ClientCapabilities $capabilities): self
    {
        $this->capabilities = $capabilities;

        return $this;
    }

    /**
     * Get Configuration Class
     *
     */
    public function getConfiguration(): ClientConfiguration
    {
        return $this->configuration;
    }

    /**
     * Update Configuration Class
     *
     * @param array $configuration
     */
    public function updateConfiguration(array $configuration): self
    {
        if (isset($configuration['hideWarnings'])) {
            $this->configuration->setHideWarnings($configuration['hideWarnings'] ? true : false);
        }

        return $this;
    }
}
