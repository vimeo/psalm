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

    public function __construct(
        LanguageServer $server,
        Codebase $codebase
    ) {
        $this->server = $server;
        $this->codebase = $codebase;
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
            return;
        }

        $this->server->invalidateFileAndDependents($textDocument->uri);

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

        $time = microtime(true);

        $this->server->invalidateFileAndDependents($textDocument->uri);

        $this->server->analyzePath($file_path);
        $this->server->emitIssues($textDocument->uri);

        $diff = microtime(true) - $time;

        error_log('Scanning & analysis took ' . number_format($diff, 4));
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
    }
}
