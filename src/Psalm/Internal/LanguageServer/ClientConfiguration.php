<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use LanguageServerProtocol\MessageType;

/**
 * @internal
 */
final class ClientConfiguration
{

    /**
     * TCP Server Address
     */
    public ?string $TCPServerAddress = null;

    /**
     * Use TCP in server mode (default is client)
     */
    public ?bool $TCPServerMode = null;

    /**
     * Debounce time in milliseconds for onChange events
     */
    public ?int $onChangeDebounceMs = null;

    /**
     * Undocumented function
     *
     * @param 'always'|'auto'|null $findUnusedCode
     */
    public function __construct(
        /**
         * Hide Warnings or not
         */
        public ?bool $hideWarnings = true,
        /**
         * Provide Completion or not
         */
        public ?bool $provideCompletion = null,
        /**
         * Provide GoTo Definitions or not
         */
        public ?bool $provideDefinition = null,
        /**
         * Provide Hover Requests or not
         */
        public ?bool $provideHover = null,
        /**
         * Provide Signature Help or not
         */
        public ?bool $provideSignatureHelp = null,
        /**
         * Provide Code Actions or not
         */
        public ?bool $provideCodeActions = null,
        /**
         * Provide Diagnostics or not
         */
        public ?bool $provideDiagnostics = null,
        /**
         * Provide Completion or not
         *
         * @psalm-suppress PossiblyUnusedProperty
         */
        public ?bool $findUnusedVariables = null,
        /**
         * Look for dead code
         */
        public ?string $findUnusedCode = null,
        /**
         * Log Level
         *
         * @see MessageType
         */
        public ?int $logLevel = null,
        /**
         * If added, the language server will not respond to onChange events.
         * You can also specify a line count over which Psalm will not run on-change events.
         */
        public ?int $onchangeLineLimit = null,
        /**
         * Location of Baseline file
         */
        public ?string $baseline = null,
    ) {
    }
}
