<?php
declare(strict_types = 1);

namespace Psalm\LanguageServer\Server;

use PhpParser\PrettyPrinter\Standard as PrettyPrinter;
use PhpParser\{
    Node,
    NodeTraverser
};
use Psalm\LanguageServer\{
    LanguageServer,
    LanguageClient,
    PhpDocumentLoader,
    PhpDocument,
    DefinitionResolver,
    CompletionProvider
};
use Psalm\LanguageServer\NodeVisitor\VariableReferencesCollector;
use LanguageServerProtocol\{
    CompletionList,
    SymbolLocationInformation,
    SymbolDescriptor,
    TextDocumentItem,
    TextDocumentIdentifier,
    VersionedTextDocumentIdentifier,
    Position,
    Range,
    FormattingOptions,
    TextEdit,
    Location,
    SymbolInformation,
    ReferenceContext,
    Hover,
    MarkedString,
    SymbolKind,
    CompletionItem,
    CompletionItemKind
};
use Psalm\Codebase;
use Psalm\LanguageServer\Index\ReadableIndex;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ClassLikeChecker;
use Sabre\Event\Promise;
use Sabre\Uri;
use function Sabre\Event\coroutine;
use function Psalm\LanguageServer\{waitForEvent, isVendored};

/**
 * Provides method handlers for all textDocument/* methods
 */
class TextDocument
{
    /**
     * @var LanguageServer
     */
    protected $server;

    /**
     * @var Codebase
     */
    protected $codebase;

    /** @var ?int */
    protected $onchange_line_limit;

    public function __construct(
        LanguageServer $server,
        Codebase $codebase,
        ?int $onchange_line_limit
    ) {
        $this->server = $server;
        $this->codebase = $codebase;
        $this->onchange_line_limit = $onchange_line_limit;
    }

    /**
     * The document open notification is sent from the client to the server to signal newly opened text documents. The
     * document's truth is now managed by the client and the server must not try to read the document's truth using the
     * document's uri.
     *
     * @param \LanguageServerProtocol\TextDocumentItem $textDocument The document that was opened.
     * @return void
     */
    public function didOpen(TextDocumentItem $textDocument)
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            error_log($file_path . ' is not in project');
            return;
        }

        $this->server->invalidateFileAndDependents($textDocument->uri);

        $this->codebase->file_provider->openFile($file_path);

        $this->server->analyzePath($file_path);
        $this->server->emitIssues($textDocument->uri);
    }

    /**
     * @return void
     */
    public function didSave(TextDocumentItem $textDocument)
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return;
        }

        // reopen file
        $this->codebase->removeTemporaryFileChanges($file_path);
        $this->server->invalidateFileAndDependents($textDocument->uri);

        $this->server->analyzePath($file_path);
        $this->server->emitIssues($textDocument->uri);
    }

    /**
     * The document change notification is sent from the client to the server to signal changes to a text document.
     *
     * @param \LanguageServerProtocol\VersionedTextDocumentIdentifier $textDocument
     * @param \LanguageServerProtocol\TextDocumentContentChangeEvent[] $contentChanges
     * @return void
     */
    public function didChange(VersionedTextDocumentIdentifier $textDocument, array $contentChanges)
    {
        $file_path = \Psalm\LanguageServer\LanguageServer::uriToPath($textDocument->uri);

        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return;
        }

        $time = microtime(true);
        $this->codebase->addTemporaryFileChanges($file_path, $contentChanges);

        if ($this->onchange_line_limit !== null) {
            if ($this->onchange_line_limit === 0) {
                return;
            }

            $c = $this->codebase->getFileContents($file_path);

            if (substr_count($c, "\n") > $this->onchange_line_limit) {
                return;
            }
        }

        $this->server->analyzePath($file_path);
        $this->server->emitIssues($textDocument->uri);
        $diff = microtime(true) - $time;
        error_log('Scanning & Analysis took ' . number_format($diff, 3) . 's');
    }

    /**
     * The document close notification is sent from the client to the server when the document got closed in the client.
     * The document's truth now exists where the document's uri points to (e.g. if the document's uri is a file uri the
     * truth now exists on disk).
     *
     * @param \LanguageServerProtocol\TextDocumentIdentifier $textDocument The document that was closed
     * @return void
     */
    public function didClose(TextDocumentIdentifier $textDocument)
    {
        $file_path = LanguageServer::uriToPath($textDocument->uri);

        $this->codebase->file_provider->closeFile($file_path);
    }


    /**
     * The goto definition request is sent from the client to the server to resolve the definition location of a symbol
     * at a given text document position.
     *
     * @param TextDocumentIdentifier $textDocument The text document
     * @param Position $position The position inside the text document
     * @return Promise <Location|Location[]>
     */
    public function definition(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        return coroutine(
            /**
             * @return \Generator<int, true, mixed, Hover|Location>
             */
            function () use ($textDocument, $position) {
                if (false) {
                    yield true;
                }

                $file_path = LanguageServer::uriToPath($textDocument->uri);

                $reference_location = $this->codebase->getReferenceAtPosition($file_path, $position);

                if ($reference_location === null) {
                    return new Hover([]);
                }

                list($reference) = $reference_location;

                $code_location = $this->codebase->getSymbolLocation($file_path, $reference);

                if (!$code_location) {
                    return new Hover([]);
                }

                return new Location(
                    LanguageServer::pathToUri($code_location->file_path),
                    new Range(
                        new Position($code_location->getLineNumber() - 1, $code_location->getColumn() - 1),
                        new Position($code_location->getEndLineNumber() - 1, $code_location->getEndColumn() - 1)
                    )
                );
            }
        );
    }

    /**
     * The hover request is sent from the client to the server to request
     * hover information at a given text document position.
     *
     * @param TextDocumentIdentifier $textDocument The text document
     * @param Position $position The position inside the text document
     * @return Promise <Hover>
     */
    public function hover(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        return coroutine(
            /**
             * @return \Generator<int, true, mixed, Hover>
             */
            function () use ($textDocument, $position) {
                if (false) {
                    yield true;
                }

                $file_path = LanguageServer::uriToPath($textDocument->uri);

                $reference_location = $this->codebase->getReferenceAtPosition($file_path, $position);

                if ($reference_location === null) {
                    return new Hover([]);
                }

                list($reference, $range) = $reference_location;

                $contents = [];
                $contents[] = new MarkedString(
                    'php',
                    $this->codebase->getSymbolInformation($file_path, $reference)
                );

                return new Hover($contents, $range);
            }
        );
    }

    /**
     * The Completion request is sent from the client to the server to compute completion items at a given cursor
     * position. Completion items are presented in the IntelliSense user interface. If computing full completion items
     * is expensive, servers can additionally provide a handler for the completion item resolve request
     * ('completionItem/resolve'). This request is sent when a completion item is selected in the user interface. A
     * typically use case is for example: the 'textDocument/completion' request doesn't fill in the documentation
     * property for returned completion items since it is expensive to compute. When the item is selected in the user
     * interface then a 'completionItem/resolve' request is sent with the selected completion item as a param. The
     * returned completion item should have the documentation property filled in.
     *
     * @param TextDocumentIdentifier The text document
     * @param Position $position The position
     * @return Promise <CompletionItem[]|CompletionList>
     */
    public function completion(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        return coroutine(
            /**
             * @return \Generator<int, true, mixed, array<empty, empty>|CompletionList>
             */
            function () use ($textDocument, $position) {
                if (false) {
                    yield true;
                }

                $file_path = LanguageServer::uriToPath($textDocument->uri);

                $completion_data = $this->codebase->getCompletionDataAtPosition($file_path, $position);

                if (!$completion_data) {
                    error_log('completion not found at ' . $position->line . ':' . $position->character);
                    return [];
                }

                list($recent_type, $gap) = $completion_data;

                error_log('gap: "' . $gap . '" and type: "' . $recent_type . '"');

                $completion_items = [];

                if ($gap === '->' || $gap === '::') {
                    $instance_completion_items = [];
                    $static_completion_items = [];

                    try {
                        $class_storage = $this->codebase->classlike_storage_provider->get($recent_type);

                        foreach ($class_storage->appearing_method_ids as $declaring_method_id) {
                            $method_storage = $this->codebase->methods->getStorage($declaring_method_id);

                            $instance_completion_items[] = new CompletionItem(
                                (string)$method_storage,
                                CompletionItemKind::METHOD,
                                null,
                                null,
                                null,
                                null,
                                $method_storage->cased_name . '()'
                            );
                        }

                        foreach ($class_storage->declaring_property_ids as $property_name => $declaring_class) {
                            $property_storage = $this->codebase->properties->getStorage(
                                $declaring_class . '::$' . $property_name
                            );

                            $instance_completion_items[] = new CompletionItem(
                                $property_storage->getInfo() . ' $' . $property_name,
                                CompletionItemKind::PROPERTY,
                                null,
                                null,
                                null,
                                null,
                                ($gap === '::' ? '$' : '') . $property_name
                            );
                        }

                        foreach ($class_storage->class_constant_locations as $const_name => $_) {
                            $static_completion_items[] = new CompletionItem(
                                'const ' . $const_name,
                                CompletionItemKind::VARIABLE,
                                null,
                                null,
                                null,
                                null,
                                $const_name
                            );
                        }
                    } catch (\Exception $e) {
                        error_log($e->getMessage());
                        return [];
                    }

                    $completion_items = $gap === '->'
                        ? $instance_completion_items
                        : array_merge($instance_completion_items, $static_completion_items);

                    error_log('Found ' . count($completion_items) . ' items');
                }

                return new CompletionList($completion_items, false);
            }
        );
    }
}
