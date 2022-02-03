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

    public function __construct(
        bool $hideWarnings = null,
        bool $provideCompletion = null
    ) {
        $this->hideWarnings = $hideWarnings;
        $this->provideCompletion = $provideCompletion;
    }
}
