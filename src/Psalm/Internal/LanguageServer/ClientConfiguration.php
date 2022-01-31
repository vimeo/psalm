<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\MessageType;

class ClientConfiguration
{

    /**
     * Use TCP mode (by default Psalm uses STDIO)
     *
     * @var string|null
     */
    public $TCPServerAddress;

    /**
     * Use TCP in server mode (default is client)
     *
     * @var bool|null
     */
    public $TCPServerMode;

    /**
     * Hide Warnings or not
     *
     * @var bool|null
     */
    public $hideWarnings;

    /**
     * Provide Completion or not
     *
     * @var bool|null
     */
    public $provideCompletion;

    /**
     * Provide Completion or not
     *
     * @var bool|null
     */
    public $findUnusedVariables;

    /**
     * Provide Completion or not
     *
     * @var bool|null
     */
    public $findUnusedCode;

    /**
     * Log Level
     *
     * @var int|null
     *
     * @see MessageType
     */
    public $logLevel;

    /**
     * Added in VSCode 1.43.0 and will be part of the LSP 3.16.0 standard.
     * Since this new functionality is not backwards compatible, we use a
     * configuration option so the end user must opt in to it using the cli argument.
     *
     * @var bool|null
     * @see https://github.com/microsoft/vscode/blob/1.43.0/src/vs/vscode.d.ts#L4688-L4699
     */
    public $VSCodeExtendedDiagnosticCodes;

    public function __construct(
        bool $hideWarnings = null,
        bool $provideCompletion = null
    ) {
        $this->hideWarnings = $hideWarnings;
        $this->provideCompletion = $provideCompletion;
    }
}