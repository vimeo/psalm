<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use AdvancedJsonRpc\Dispatcher;
use AdvancedJsonRpc\Error;
use AdvancedJsonRpc\ErrorCode;
use AdvancedJsonRpc\ErrorResponse;
use AdvancedJsonRpc\Request;
use AdvancedJsonRpc\Response;
use AdvancedJsonRpc\SuccessResponse;
use Amp\Promise;
use Amp\Success;
use Generator;
use InvalidArgumentException;
use LanguageServerProtocol\ClientCapabilities;
use LanguageServerProtocol\ClientInfo;
use LanguageServerProtocol\CodeDescription;
use LanguageServerProtocol\CompletionOptions;
use LanguageServerProtocol\Diagnostic;
use LanguageServerProtocol\DiagnosticSeverity;
use LanguageServerProtocol\ExecuteCommandOptions;
use LanguageServerProtocol\InitializeResult;
use LanguageServerProtocol\InitializeResultServerInfo;
use LanguageServerProtocol\LogMessage;
use LanguageServerProtocol\MessageType;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\SaveOptions;
use LanguageServerProtocol\ServerCapabilities;
use LanguageServerProtocol\SignatureHelpOptions;
use LanguageServerProtocol\TextDocumentSyncKind;
use LanguageServerProtocol\TextDocumentSyncOptions;
use LanguageServerProtocol\WorkspaceFolder;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\LanguageServer\Server\TextDocument as ServerTextDocument;
use Psalm\Internal\LanguageServer\Server\Workspace as ServerWorkspace;
use Psalm\IssueBuffer;
use Throwable;

use function Amp\asyncCoroutine;
use function Amp\call;
use function array_combine;
use function array_keys;
use function array_map;
use function array_shift;
use function array_unshift;
use function explode;
use function implode;
use function max;
use function parse_url;
use function rawurlencode;
use function realpath;
use function str_replace;
use function strpos;
use function substr;
use function trim;
use function urldecode;

/**
 * @internal
 */
class LanguageServer extends Dispatcher
{
    /**
     * Handles textDocument/* method calls
     *
     * @var ?ServerTextDocument
     */
    public $textDocument;

    /**
     * Handles workspace/* method calls
     *
     * @var ?ServerWorkspace
     */
    public $workspace;

    /**
     * @var ProtocolReader
     */
    protected $protocolReader;

    /**
     * @var ProtocolWriter
     */
    protected $protocolWriter;

    /**
     * @var LanguageClient
     */
    public $client;

    /**
     * @var ClientCapabilities
     */
    public $clientCapabilities;

    /**
     * @var string|null
     */
    public $trace;

    /**
     * @var ProjectAnalyzer
     */
    protected $project_analyzer;

    public function __construct(
        ProtocolReader $reader,
        ProtocolWriter $writer,
        ProjectAnalyzer $project_analyzer,
        ClientConfiguration $clientConfiguration
    ) {
        parent::__construct($this, '/');
        $this->project_analyzer = $project_analyzer;

        $this->protocolWriter = $writer;

        $this->protocolReader = $reader;
        $this->protocolReader->on(
            'close',
            function (): void {
                $this->shutdown();
                $this->exit();
            }
        );
        $this->protocolReader->on(
            'message',
            asyncCoroutine(
                /**
                 * @return Generator<int, Promise, mixed, void>
                 */
                function (Message $msg): Generator {
                    if (!$msg->body) {
                        return;
                    }

                    // Ignore responses, this is the handler for requests and notifications
                    if (Response::isResponse($msg->body)) {
                        return;
                    }

                    $result = null;
                    $error = null;
                    try {
                        // Invoke the method handler to get a result
                        /**
                         * @var Promise
                         */
                        $dispatched = $this->dispatch($msg->body);
                        /** @psalm-suppress MixedAssignment */
                        $result = yield $dispatched;
                    } catch (Error $e) {
                        // If a ResponseError is thrown, send it back in the Response
                        $error = $e;
                    } catch (Throwable $e) {
                        // If an unexpected error occurred, send back an INTERNAL_ERROR error response
                        $error = new Error(
                            (string) $e,
                            ErrorCode::INTERNAL_ERROR,
                            null,
                            $e
                        );
                    }
                    if ($error !== null) {
                        $this->logError($error->message);
                    }
                    // Only send a Response for a Request
                    // Notifications do not send Responses
                    /**
                     * @psalm-suppress UndefinedPropertyFetch
                     * @psalm-suppress MixedArgument
                     */
                    if (Request::isRequest($msg->body)) {
                        if ($error !== null) {
                            $responseBody = new ErrorResponse($msg->body->id, $error);
                        } else {
                            $responseBody = new SuccessResponse($msg->body->id, $result);
                        }
                        yield $this->protocolWriter->write(new Message($responseBody));
                    }
                }
            )
        );

        $this->protocolReader->on(
            'readMessageGroup',
            function (): void {
                //$this->verboseLog('Received message group');
                //$this->doAnalysis();
            }
        );

        $this->client = new LanguageClient($reader, $writer, $this, $clientConfiguration);

        $this->project_analyzer->progress = new Progress($this);

        $this->logInfo("Psalm Language Server ".PSALM_VERSION." has started.");
    }

    /**
     * The initialize request is sent as the first request from the client to the server.
     *
     * @param ClientCapabilities $capabilities The capabilities provided by the client (editor)
     * @param string|null $rootPath The rootPath of the workspace. Is null if no folder is open.
     * @param int|null $processId The process Id of the parent process that started the server.
     * Is null if the process has not been started by another process. If the parent process is
     * not alive then the server should exit (see exit notification) its process.
     * @param ClientInfo|null $clientInfo Information about the client
     * @param string|null $locale  The locale the client is currently showing the user interface
     * in. This must not necessarily be the locale of the operating
     * system.
     * @param string|null $rootPath The rootPath of the workspace. Is null if no folder is open.
     * @param mixed $initializationOptions
     * @param string|null $trace The initial trace setting. If omitted trace is disabled ('off').
     * @param array|null $workspaceFolders The workspace folders configured in the client when
     * the server starts. This property is only available if the client supports workspace folders.
     * It can be `null` if the client supports workspace folders but none are
     * configured.
     * @psalm-return Promise<InitializeResult>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function initialize(
        ClientCapabilities $capabilities,
        ?int $processId = null,
        ?ClientInfo $clientInfo = null,
        ?string $locale = null,
        ?string $rootPath = null,
        ?string $rootUri = null,
        $initializationOptions = null,
        ?string $trace = null,
        //?array $workspaceFolders = null //error in json-dispatcher
    ): Promise {
        $this->clientCapabilities = $capabilities;
        $this->trace = $trace;
        return call(
            /** @return Generator<int, true, mixed, InitializeResult> */
            function () use($capabilities) {
                $this->logInfo("Initializing...");
                $this->clientStatus('initializing');

                // Eventually, this might block on something. Leave it as a generator.
                /** @psalm-suppress TypeDoesNotContainType */
                if (false) {
                    yield true;
                }

                $this->logInfo("Initializing: Getting code base...");
                $this->clientStatus('initializing', 'getting code base');
                $codebase = $this->project_analyzer->getCodebase();

                $this->logInfo("Initializing: Scanning files...");
                $this->clientStatus('initializing', 'scanning files');
                $codebase->scanFiles($this->project_analyzer->threads);

                $this->logInfo("Initializing: Registering stub files...");
                $this->clientStatus('initializing', 'registering stub files');
                $codebase->config->visitStubFiles($codebase);

                if ($this->textDocument === null) {
                    $this->textDocument = new ServerTextDocument(
                        $this,
                        $codebase,
                        $this->project_analyzer
                    );
                }

                if ($this->workspace === null) {
                    $this->workspace = new ServerWorkspace(
                        $this,
                        $codebase,
                        $this->project_analyzer
                    );
                }

                $serverCapabilities = new ServerCapabilities();

                $serverCapabilities->executeCommandProvider = new ExecuteCommandOptions(['test']);

                $textDocumentSyncOptions = new TextDocumentSyncOptions();

                $textDocumentSyncOptions->openClose = true;

                $saveOptions = new SaveOptions();
                $saveOptions->includeText = true;
                $textDocumentSyncOptions->save = $saveOptions;

                if ($this->project_analyzer->onchange_line_limit === 0) {
                    $textDocumentSyncOptions->change = TextDocumentSyncKind::NONE;
                } else {
                    $textDocumentSyncOptions->change = TextDocumentSyncKind::FULL;
                }

                $serverCapabilities->textDocumentSync = $textDocumentSyncOptions;

                // Support "Find all symbols"
                $serverCapabilities->documentSymbolProvider = false;
                // Support "Find all symbols in workspace"
                $serverCapabilities->workspaceSymbolProvider = false;
                // Support "Go to definition"
                $serverCapabilities->definitionProvider = true;
                // Support "Find all references"
                $serverCapabilities->referencesProvider = false;
                // Support "Hover"
                $serverCapabilities->hoverProvider = true;
                // Support "Completion"

                // Support "Code Actions" if we support data
                if(
                    $this->clientCapabilities &&
                    $this->clientCapabilities->textDocument &&
                    $this->clientCapabilities->textDocument->publishDiagnostics &&
                    $this->clientCapabilities->textDocument->publishDiagnostics->dataSupport
                ) {
                    $serverCapabilities->codeActionProvider = true;
                }

                if ($this->project_analyzer->provide_completion) {
                    $serverCapabilities->completionProvider = new CompletionOptions();
                    $serverCapabilities->completionProvider->resolveProvider = false;
                    $serverCapabilities->completionProvider->triggerCharacters = ['$', '>', ':',"[", "(", ",", " "];
                }

                $serverCapabilities->signatureHelpProvider = new SignatureHelpOptions(['(', ',']);

                // Support global references
                $serverCapabilities->xworkspaceReferencesProvider = false;
                $serverCapabilities->xdefinitionProvider = false;
                $serverCapabilities->dependenciesProvider = false;

                $this->logInfo("Initializing: Complete.");
                $this->clientStatus('initialized');

                $initializeResultServerInfo = new InitializeResultServerInfo('Psalm Language Server', PSALM_VERSION);

                return new InitializeResult($serverCapabilities, $initializeResultServerInfo);
            }
        );
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     */
    public function initialized(): void
    {
        try {
            $this->client->refreshConfiguration();
        } catch(Throwable $e) {
            $this->server->logError((string) $e);
        }
        $this->clientStatus('running');
    }

    public function queueChangeFileAnalysis(string $file_path, string $uri, ?int $version=null) {
        $this->doVersionedAnalysis([$file_path => $uri], $version);
    }

    public function queueOpenFileAnalysis(string $file_path, string $uri, ?int $version=null) {
        $this->doVersionedAnalysis([$file_path => $uri], $version);
    }

    /**
     * Queue Saved File Analysis
     * @param string $file_path
     * @param string $uri
     * @return void
     */
    public function queueSaveFileAnalysis(string $file_path, string $uri) {
        //Always reanalzye open files because of things changing elsewhere
        $opened = array_reduce(
            $this->project_analyzer->getCodebase()->file_provider->getOpenFilesPath(),
            function (array $opened, string $file_path) {
                $opened[$file_path] = $this->pathToUri($file_path);
                return $opened;
            },
        [$file_path => $this->pathToUri($file_path)]);

        $this->doVersionedAnalysis($opened);
    }

    public function doVersionedAnalysis($all_files_to_analyze, ?int $version=null):void {
        try {
            if(empty($all_files_to_analyze)) {
                $this->logWarning("No versioned analysis to do.");
                return;
            }


            /** @var array */
            $files = $all_files_to_analyze;

            $codebase = $this->project_analyzer->getCodebase();
            $codebase->reloadFiles(
                $this->project_analyzer,
                array_keys($files)
            );

            $codebase->analyzer->addFilesToAnalyze(
                array_combine(array_keys($files), array_keys($files))
            );
            $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);

            $this->emitVersionedIssues($files,$version);
        } catch(Throwable $e) {
            $this->server->logError((string) $e);
        }
    }

    public function emitVersionedIssues(array $files, ?int $version = null): void {
        $this->logDebug("Perform Analysis",[
            'files' => array_keys($files),
            'version' => $version
        ]);

        $data = IssueBuffer::clear();
        foreach ($files as $file_path => $uri) {
            //Dont report errors in files we are not watching
            if (!$this->project_analyzer->getCodebase()->config->isInProjectDirs($file_path)) {
                continue;
            }
            $diagnostics = array_map(
                function (IssueData $issue_data): Diagnostic {
                    //$check_name = $issue->check_name;
                    $description = $issue_data->message;
                    $severity = $issue_data->severity;

                    $start_line = max($issue_data->line_from, 1);
                    $end_line = $issue_data->line_to;
                    $start_column = $issue_data->column_from;
                    $end_column = $issue_data->column_to;
                    // Language server has 0 based lines and columns, phan has 1-based lines and columns.
                    $range = new Range(
                        new Position($start_line - 1, $start_column - 1),
                        new Position($end_line - 1, $end_column - 1)
                    );
                    switch ($severity) {
                        case Config::REPORT_INFO:
                            $diagnostic_severity = DiagnosticSeverity::WARNING;
                            break;
                        case Config::REPORT_ERROR:
                        default:
                            $diagnostic_severity = DiagnosticSeverity::ERROR;
                            break;
                    }
                    $diagnostic = new Diagnostic(
                        $description,
                        $range,
                        null,
                        $diagnostic_severity,
                        'psalm'
                    );

                    $diagnostic->data = [
                        'type' => $issue_data->type,
                        'snippet' => $issue_data->snippet,
                        'line_from' => $issue_data->line_from,
                        'line_to' => $issue_data->line_to
                    ];

                    $diagnostic->code = $issue_data->shortcode;

                    if ($this->clientCapabilities->textDocument &&
                        $this->clientCapabilities->textDocument->publishDiagnostics &&
                        $this->clientCapabilities->textDocument->publishDiagnostics->codeDescriptionSupport
                    ) {
                        $diagnostic->codeDescription = new CodeDescription($issue_data->link);
                    }

                    return $diagnostic;
                },
                array_filter(
                    $data[$file_path] ?? [],
                    function (IssueData $issue_data) {
                        if ($issue_data->severity === Config::REPORT_INFO &&
                            $this->client->clientConfiguration->hideWarnings
                        ) {
                            return false;
                        }

                        return true;
                    }
                )
            );

            $this->client->textDocument->publishDiagnostics($uri, $diagnostics, $version);
        }
    }

    /**
     * The shutdown request is sent from the client to the server. It asks the server to shut down,
     * but to not exit (otherwise the response might not be delivered correctly to the client).
     * There is a separate exit notification that asks the server to exit.
     * @psalm-suppress PossiblyUnusedReturnValue
     */
    public function shutdown(): Promise
    {
        $this->clientStatus('closing');
        $this->logInfo("Shutting down...");
        $codebase = $this->project_analyzer->getCodebase();
        $scanned_files = $codebase->scanner->getScannedFiles();
        $codebase->file_reference_provider->updateReferenceCache(
            $codebase,
            $scanned_files
        );
        $this->clientStatus('closed');
        return new Success(null);
    }

    /**
     * A notification to ask the server to exit its process.
     *
     */
    public function exit(): void
    {
        exit(0);
    }


    /**
     * Send log message to the client
     *
     * @psalm-param 1|2|3|4 $type
     * @param int $type The log type:
     *  - 1 = Error
     *  - 2 = Warning
     *  - 3 = Info
     *  - 4 = Log
     * @see MessageType
     * @param string  $message The log message to send to the client.
     * @param mixed[] $context The log context
     */
    public function log(int $type, string $message, array $context = []): void
    {
        if(!empty($context)) {
            $message .= "\n" . \json_encode($context, JSON_PRETTY_PRINT);
        }
        try {
            $this->client->logMessage(
                new LogMessage(
                    $type,
                    $message,
                )
            );
        } catch (Throwable $err) {
            // do nothing as we could potentially go into a loop here is not careful
            //TODO: Investigate if we can use error_log instead
        }
    }

    public function logError(string $message, array $context = []) {
        $this->log(MessageType::ERROR, $message, $context);
    }

    public function logWarning(string $message, array $context = []) {
        $this->log(MessageType::WARNING, $message, $context);
    }

    public function logInfo(string $message, array $context = []) {
        $this->log(MessageType::INFO, $message, $context);
    }

    public function logDebug(string $message, array $context = []) {
        $this->log(MessageType::LOG, $message, $context);
    }

    /**
     * Send status message to client.  This is the same as sending a log message,
     * except this is meant for parsing by the client to present status updates in a UI.
     *
     * @param string $status The log message to send to the client. Should not contain colons `:`.
     * @param string|null $additional_info This is additional info that the client
     *                                       can use as part of the display message.
     */
    private function clientStatus(string $status, ?string $additional_info = null): void
    {
        try {
            $this->client->event(
                new LogMessage(
                    MessageType::INFO,
                    $status . (!empty($additional_info) ? ': ' . $additional_info : '')
                )
            );
        } catch (Throwable $err) {
            // do nothing
        }
    }

    /**
     * Transforms an absolute file path into a URI as used by the language server protocol.
     *
     * @psalm-pure
     */
    public static function pathToUri(string $filepath): string
    {
        $filepath = trim(str_replace('\\', '/', $filepath), '/');
        $parts = explode('/', $filepath);
        // Don't %-encode the colon after a Windows drive letter
        $first = array_shift($parts);
        if (substr($first, -1) !== ':') {
            $first = rawurlencode($first);
        }
        $parts = array_map('rawurlencode', $parts);
        array_unshift($parts, $first);
        $filepath = implode('/', $parts);

        return 'file:///' . $filepath;
    }

    /**
     * Transforms URI into file path
     *
     *
     */
    public static function uriToPath(string $uri): string
    {
        $fragments = parse_url($uri);
        if ($fragments === false
            || !isset($fragments['scheme'])
            || $fragments['scheme'] !== 'file'
            || !isset($fragments['path'])
        ) {
            throw new InvalidArgumentException("Not a valid file URI: $uri");
        }

        $filepath = urldecode($fragments['path']);

        if (strpos($filepath, ':') !== false) {
            if ($filepath[0] === '/') {
                $filepath = substr($filepath, 1);
            }
            $filepath = str_replace('/', '\\', $filepath);
        }

        $realpath = realpath($filepath);
        if ($realpath !== false) {
            return $realpath;
        }

        return $filepath;
    }
}
