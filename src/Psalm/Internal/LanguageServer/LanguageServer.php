<?php
declare(strict_types = 1);

namespace Psalm\Internal\LanguageServer;

use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Config;
use LanguageServerProtocol\{
    ServerCapabilities,
    ClientCapabilities,
    TextDocumentSyncKind,
    TextDocumentSyncOptions,
    InitializeResult,
    CompletionOptions,
    SignatureHelpOptions
};
use Psalm\Internal\LanguageServer\FilesFinder\{FilesFinder, ClientFilesFinder, FileSystemFilesFinder};
use Psalm\Internal\LanguageServer\ContentRetriever\{
    ContentRetriever,
    ClientContentRetriever,
    FileSystemContentRetriever
};
use Psalm\Internal\LanguageServer\Index\{DependenciesIndex, GlobalIndex, Index, ProjectIndex, StubsIndex};
use Psalm\Internal\LanguageServer\Cache\{FileSystemCache, ClientCache};
use Psalm\Internal\LanguageServer\Server\TextDocument;
use LanguageServerProtocol\{Range, Position, Diagnostic, DiagnosticSeverity};
use AdvancedJsonRpc;
use Sabre\Event\Loop;
use Sabre\Event\Promise;
use function Sabre\Event\coroutine;
use Throwable;
use Webmozart\PathUtil\Path;

/**
 * @internal
 */
class LanguageServer extends AdvancedJsonRpc\Dispatcher
{
    /**
     * Handles textDocument/* method calls
     *
     * @var ?Server\TextDocument
     */
    public $textDocument;

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
     * @var ProjectAnalyzer
     */
    protected $project_analyzer;

    /**
     * @var array<string, string>
     */
    protected $onsave_paths_to_analyze = [];

    /**
     * @var array<string, string>
     */
    protected $onchange_paths_to_analyze = [];

    /**
     * @param ProtocolReader  $reader
     * @param ProtocolWriter $writer
     */
    public function __construct(
        ProtocolReader $reader,
        ProtocolWriter $writer,
        ProjectAnalyzer $project_analyzer
    ) {
        parent::__construct($this, '/');
        $this->project_analyzer = $project_analyzer;

        $this->protocolWriter = $writer;

        $this->protocolReader = $reader;
        $this->protocolReader->on(
            'close',
            /**
             * @return void
             */
            function () {
                $this->shutdown();
                $this->exit();
            }
        );
        $this->protocolReader->on(
            'message',
            /** @return void */
            function (Message $msg) {
                coroutine(
                    /** @return \Generator<int, Promise, mixed, void> */
                    function () use ($msg) {
                        if (!$msg->body) {
                            return;
                        }

                        // Ignore responses, this is the handler for requests and notifications
                        if (AdvancedJsonRpc\Response::isResponse($msg->body)) {
                            return;
                        }
                        $result = null;
                        $error = null;
                        try {
                            // Invoke the method handler to get a result
                            /**
                             * @var Promise
                             * @psalm-suppress UndefinedClass
                             */
                            $dispatched = $this->dispatch($msg->body);
                            $result = yield $dispatched;
                        } catch (AdvancedJsonRpc\Error $e) {
                            // If a ResponseError is thrown, send it back in the Response
                            $error = $e;
                        } catch (Throwable $e) {
                            // If an unexpected error occurred, send back an INTERNAL_ERROR error response
                            $error = new AdvancedJsonRpc\Error(
                                (string)$e,
                                AdvancedJsonRpc\ErrorCode::INTERNAL_ERROR,
                                null,
                                $e
                            );
                        }
                        // Only send a Response for a Request
                        // Notifications do not send Responses
                        /**
                         * @psalm-suppress UndefinedPropertyFetch
                         * @psalm-suppress MixedArgument
                         */
                        if (AdvancedJsonRpc\Request::isRequest($msg->body)) {
                            if ($error !== null) {
                                $responseBody = new AdvancedJsonRpc\ErrorResponse($msg->body->id, $error);
                            } else {
                                $responseBody = new AdvancedJsonRpc\SuccessResponse($msg->body->id, $result);
                            }
                            $this->protocolWriter->write(new Message($responseBody));
                        }
                    }
                )->otherwise('\Psalm\Internal\LanguageServer\LanguageServer::crash');
            }
        );

        $this->protocolReader->on(
            'readMessageGroup',
            /** @return void */
            function () {
                $this->doAnalysis();
            }
        );

        $this->client = new LanguageClient($reader, $writer);
    }

    /**
     * The initialize request is sent as the first request from the client to the server.
     *
     * @param ClientCapabilities $capabilities The capabilities provided by the client (editor)
     * @param string|null $rootPath The rootPath of the workspace. Is null if no folder is open.
     * @param int|null $processId The process Id of the parent process that started the server.
     * Is null if the process has not been started by another process. If the parent process is
     * not alive then the server should exit (see exit notification) its process.
     * @psalm-return Promise<InitializeResult>
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function initialize(
        ClientCapabilities $capabilities,
        string $rootPath = null,
        int $processId = null
    ): Promise {
        return coroutine(
            /** @return \Generator<int, true, mixed, InitializeResult> */
            function () use ($capabilities, $rootPath, $processId) {
                // Eventually, this might block on something. Leave it as a generator.
                if (false) {
                    yield true;
                }

                $codebase = $this->project_analyzer->getCodebase();

                $codebase->scanFiles($this->project_analyzer->threads);

                $codebase->config->visitStubFiles($codebase, false);

                if ($this->textDocument === null) {
                    $this->textDocument = new TextDocument(
                        $this,
                        $codebase,
                        $this->project_analyzer->onchange_line_limit
                    );
                }

                $serverCapabilities = new ServerCapabilities();

                $textDocumentSyncOptions = new TextDocumentSyncOptions();

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

                /**
                $serverCapabilities->completionProvider = new CompletionOptions;
                $serverCapabilities->completionProvider->resolveProvider = false;
                $serverCapabilities->completionProvider->triggerCharacters = ['$', '>', ':'];
                */

                /*
                $serverCapabilities->signatureHelpProvider = new SignatureHelpOptions();
                $serverCapabilities->signatureHelpProvider->triggerCharacters = ['(', ','];
                */

                // Support global references
                $serverCapabilities->xworkspaceReferencesProvider = false;
                $serverCapabilities->xdefinitionProvider = false;
                $serverCapabilities->dependenciesProvider = false;

                return new InitializeResult($serverCapabilities);
            }
        );
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod
     * @return void
     */
    public function initialized()
    {
    }

    /**
     * @return void
     */
    public function queueTemporaryFileAnalysis(string $file_path, string $uri)
    {
        $this->onchange_paths_to_analyze[$file_path] = $uri;
    }

    /**
     * @return void
     */
    public function queueFileAnalysis(string $file_path, string $uri)
    {
        $this->onsave_paths_to_analyze[$file_path] = $uri;
    }

    /**
     * @return void
     */
    public function doAnalysis()
    {
        $codebase = $this->project_analyzer->getCodebase();

        $all_files_to_analyze = $this->onchange_paths_to_analyze + $this->onsave_paths_to_analyze;

        if (!$all_files_to_analyze) {
            return;
        }

        if ($this->onsave_paths_to_analyze) {
            $codebase->reloadFiles($this->project_analyzer, array_keys($this->onsave_paths_to_analyze));
        }

        if ($this->onchange_paths_to_analyze) {
            $codebase->reloadFiles($this->project_analyzer, array_keys($this->onchange_paths_to_analyze));
        }

        $all_file_paths_to_analyze = array_keys($all_files_to_analyze);
        $codebase->analyzer->addFiles(array_combine($all_file_paths_to_analyze, $all_file_paths_to_analyze));
        $codebase->analyzer->analyzeFiles($this->project_analyzer, 1, false);
        $this->emitIssues($all_files_to_analyze);

        $this->onchange_paths_to_analyze = [];
        $this->onsave_paths_to_analyze = [];
    }

    /**
     * @param array<string, string> $uris
     * @return void
     */
    public function emitIssues(array $uris)
    {
        $data = \Psalm\IssueBuffer::clear();

        foreach ($uris as $file_path => $uri) {
            $data = array_values(array_filter(
                $data,
                function (array $issue_data) use ($file_path) : bool {
                    return $issue_data['file_path'] === $file_path;
                }
            ));

            $diagnostics = array_map(
                /**
                 * @param array{
                 *     severity: string,
                 *     message: string,
                 *     line_from: int,
                 *     line_to: int,
                 *     column_from: int,
                 *     column_to: int
                 * } $issue_data
                 */
                function (array $issue_data) use ($file_path) : Diagnostic {
                    //$check_name = $issue['check_name'];
                    $description = $issue_data['message'];
                    $severity = $issue_data['severity'];

                    $start_line = max($issue_data['line_from'], 1);
                    $end_line = $issue_data['line_to'];
                    $start_column = $issue_data['column_from'];
                    $end_column = $issue_data['column_to'];
                    // Language server has 0 based lines and columns, phan has 1-based lines and columns.
                    $range = new Range(
                        new Position($start_line - 1, $start_column - 1),
                        new Position($end_line - 1, $end_column - 1)
                    );
                    switch ($severity) {
                        case \Psalm\Config::REPORT_INFO:
                            $diagnostic_severity = DiagnosticSeverity::WARNING;
                            break;
                        case \Psalm\Config::REPORT_ERROR:
                        default:
                            $diagnostic_severity = DiagnosticSeverity::ERROR;
                            break;
                    }
                    // TODO: copy issue code in 'json' format
                    return new Diagnostic(
                        $description,
                        $range,
                        null,
                        $diagnostic_severity,
                        'Psalm'
                    );
                },
                $data
            );

            $this->client->textDocument->publishDiagnostics($uri, $diagnostics);
        }
    }

    /**
     * The shutdown request is sent from the client to the server. It asks the server to shut down,
     * but to not exit (otherwise the response might not be delivered correctly to the client).
     * There is a separate exit notification that asks the server to exit.
     *
     * @return void
     */
    public function shutdown()
    {
        $codebase = $this->project_analyzer->getCodebase();
        $scanned_files = $codebase->scanner->getScannedFiles();
        $codebase->file_reference_provider->updateReferenceCache(
            $codebase,
            $scanned_files
        );
    }

    /**
     * A notification to ask the server to exit its process.
     *
     * @return void
     */
    public function exit()
    {
        exit(0);
    }

    /**
     * Transforms an absolute file path into a URI as used by the language server protocol.
     *
     * @param string $filepath
     * @return string
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
     * @param string $uri
     * @return string
     */
    public static function uriToPath(string $uri)
    {
        $fragments = parse_url($uri);
        if ($fragments === false || !isset($fragments['scheme']) || $fragments['scheme'] !== 'file') {
            throw new \InvalidArgumentException("Not a valid file URI: $uri");
        }
        $filepath = urldecode((string) $fragments['path']);
        if (strpos($filepath, ':') !== false) {
            if ($filepath[0] === '/') {
                $filepath = substr($filepath, 1);
            }
            $filepath = str_replace('/', '\\', $filepath);
        }
        return $filepath;
    }

    /**
     * Throws an exception on the next tick.
     * Useful for letting a promise crash the process on rejection.
     *
     * @param Throwable $err
     * @return void
     * @psalm-suppress PossiblyUnusedMethod
     */
    public static function crash(Throwable $err)
    {
        Loop\nextTick(
            /** @return void */
            function () use ($err) {
                throw $err;
            }
        );
    }
}
