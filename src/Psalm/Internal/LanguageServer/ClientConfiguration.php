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
     * Provide GoTo Definitions or not
     *
     * @var bool|null
     */
    public $provideDefinition;

    /**
     * Provide Hover Requests or not
     *
     * @var bool|null
     */
    public $provideHover;

    /**
     * Provide Signature Help or not
     *
     * @var bool|null
     */
    public $provideSignatureHelp;

    /**
     * Provide Code Actions or not
     *
     * @var bool|null
     */
    public $provideCodeActions;

    /**
     * Provide Diagnostics or not
     *
     * @var bool|null
     */
    public $provideDiagnostics;

    /**
     * Provide Completion or not
     *
     * @var bool|null
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $findUnusedVariables;

    /**
     * Look for dead code
     *
     * @var 'always'|'auto'|null
     */
    public $findUnusedCode;

    /**
     * Log Level
     *
     * @var int|null
     *
     * @see MessageType
     * @psalm-suppress PossiblyUnusedProperty
     */
    public $logLevel;

    /**
     * If added, the language server will not respond to onChange events.
     * You can also specify a line count over which Psalm will not run on-change events.
     *
     * @var int|null
     *
     */
    public $onchangeLineLimit;

    /**
     * Debounce time in milliseconds for onChange events
     *
     * @var int|null
     *
     */
    public $onChangeDebounceMs;

    /**
     * Undocumented function
     *
     * @param boolean $hideWarnings
     * @param boolean|null $provideCompletion
     * @param boolean|null $provideDefinition
     * @param boolean|null $provideHover
     * @param boolean|null $provideSignatureHelp
     * @param boolean|null $provideCodeActions
     * @param boolean|null $provideDiagnostics
     * @param boolean|null $findUnusedVariables
     * @param 'always'|'auto'|null $findUnusedCode
     * @param integer|null $logLevel
     * @param integer|null $onchangeLineLimit
     */
    public function __construct(
        bool $hideWarnings = true,
        bool $provideCompletion = null,
        bool $provideDefinition = null,
        bool $provideHover = null,
        bool $provideSignatureHelp = null,
        bool $provideCodeActions = null,
        bool $provideDiagnostics = null,
        bool $findUnusedVariables = null,
        string $findUnusedCode = null,
        int $logLevel = null,
        int $onchangeLineLimit = null,
    ) {
        $this->hideWarnings = $hideWarnings;
        $this->provideCompletion = $provideCompletion;
        $this->provideDefinition = $provideDefinition;
        $this->provideHover = $provideHover;
        $this->provideSignatureHelp = $provideSignatureHelp;
        $this->provideCodeActions = $provideCodeActions;
        $this->provideDiagnostics = $provideDiagnostics;
        $this->findUnusedVariables = $findUnusedVariables;
        $this->findUnusedCode = $findUnusedCode;
        $this->logLevel = $logLevel;
        $this->onchangeLineLimit = $onchangeLineLimit;
    }
}
