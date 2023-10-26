<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer\Server;

use Amp\Promise;
use Amp\Success;
use LanguageServerProtocol\CodeAction;
use LanguageServerProtocol\CodeActionContext;
use LanguageServerProtocol\CodeActionKind;
use LanguageServerProtocol\CompletionList;
use LanguageServerProtocol\Hover;
use LanguageServerProtocol\Location;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\SignatureHelp;
use LanguageServerProtocol\TextDocumentContentChangeEvent;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\TextEdit;
use LanguageServerProtocol\VersionedTextDocumentIdentifier;
use LanguageServerProtocol\WorkspaceEdit;
use Psalm\Codebase;
use Psalm\Exception\TypeParseTreeException;
use Psalm\Exception\UnanalyzedFileException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\LanguageServer\LanguageServer;
use UnexpectedValueException;

use function array_values;
use function count;
use function preg_match;
use function substr_count;

/**
 * Provides method handlers for all textDocument/* methods
 *
 * @internal
 */
final class TextDocument
{
    protected LanguageServer $server;

    protected Codebase $codebase;

    protected ProjectAnalyzer $project_analyzer;

    public function __construct(
        LanguageServer $server,
        Codebase $codebase,
        ProjectAnalyzer $project_analyzer
    ) {
        $this->server = $server;
        $this->codebase = $codebase;
        $this->project_analyzer = $project_analyzer;
    }

    /**
     * The document open notification is sent from the client to the server to signal newly opened text documents. The
     * document’s content is now managed by the client and the server must not try to read the document’s content using
     * the document’s Uri. Open in this sense means it is managed by the client. It doesn’t necessarily mean that its
     * content is presented in an editor. An open notification must not be sent more than once without a corresponding
     * close notification send before. This means open and close notification must be balanced and the max open count
     * for a particular textDocument is one. Note that a server’s ability to fulfill requests is independent of whether
     * a text document is open or closed.
     *
     * @param TextDocumentItem $textDocument the document that was opened
     */
    public function didOpen(TextDocumentItem $textDocument): void
    {
        $this->server->logDebug(
            'textDocument/didOpen',
            ['version' => $textDocument->version, 'uri' => $textDocument->uri],
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        $this->codebase->removeTemporaryFileChanges($file_path);
        $this->codebase->file_provider->openFile($file_path);
        $this->codebase->file_provider->setOpenContents($file_path, $textDocument->text);

        $this->server->queueOpenFileAnalysis($file_path, $textDocument->uri, $textDocument->version);
    }

    /**
     * The document save notification is sent from the client to the server when the document was saved in the client
     *
     * @param TextDocumentIdentifier $textDocument the document that was opened
     * @param string|null $text Optional the content when saved. Depends on the includeText value
     *                          when the save notification was requested.
     */
    public function didSave(TextDocumentIdentifier $textDocument, ?string $text = null): void
    {
        $this->server->logDebug(
            'textDocument/didSave',
            ['uri' => (array) $textDocument],
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        // reopen file
        $this->codebase->removeTemporaryFileChanges($file_path);
        $this->codebase->file_provider->setOpenContents($file_path, $text);

        $this->server->queueSaveFileAnalysis($file_path, $textDocument->uri);
    }

    /**
     * The document change notification is sent from the client to the server to signal changes to a text document.
     *
     * @param VersionedTextDocumentIdentifier $textDocument the document that was changed
     * @param TextDocumentContentChangeEvent[] $contentChanges
     */
    public function didChange(VersionedTextDocumentIdentifier $textDocument, array $contentChanges): void
    {
        $this->server->logDebug(
            'textDocument/didChange',
            ['version' => $textDocument->version, 'uri' => $textDocument->uri],
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        if (count($contentChanges) === 1 && isset($contentChanges[0]) && $contentChanges[0]->range === null) {
            $new_content = $contentChanges[0]->text;
        } else {
            throw new UnexpectedValueException('Not expecting partial diff');
        }

        if ($this->project_analyzer->onchange_line_limit !== null) {
            if (substr_count($new_content, "\n") > $this->project_analyzer->onchange_line_limit) {
                return;
            }
        }

        $this->codebase->addTemporaryFileChanges($file_path, $new_content, $textDocument->version);
        $this->server->queueChangeFileAnalysis($file_path, $textDocument->uri, $textDocument->version);
    }

    /**
     * The document close notification is sent from the client to the server when the document got closed in the client.
     * The document’s master now exists where the document’s Uri points to (e.g. if the document’s Uri is a file Uri the
     * master now exists on disk). As with the open notification the close notification is about managing the document’s
     * content. Receiving a close notification doesn’t mean that the document was open in an editor before. A close
     * notification requires a previous open notification to be sent. Note that a server’s ability to fulfill requests
     * is independent of whether a text document is open or closed.
     *
     * @param TextDocumentIdentifier $textDocument The document that was closed
     */
    public function didClose(TextDocumentIdentifier $textDocument): void
    {
        $this->server->logDebug(
            'textDocument/didClose',
            ['uri' => $textDocument->uri],
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        $this->codebase->file_provider->closeFile($file_path);
        $this->server->client->textDocument->publishDiagnostics($textDocument->uri, []);
    }

    /**
     * The goto definition request is sent from the client to the server to resolve the definition location of a symbol
     * at a given text document position.
     *
     * @param TextDocumentIdentifier $textDocument The text document
     * @param Position $position The position inside the text document
     * @psalm-return Promise<Location>|Promise<null>
     */
    public function definition(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        if (!$this->server->client->clientConfiguration->provideDefinition) {
            return new Success(null);
        }

        $this->server->logDebug(
            'textDocument/definition',
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        //This currently doesnt work right with out of project files
        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return new Success(null);
        }

        try {
            $reference = $this->codebase->getReferenceAtPositionAsReference($file_path, $position);
        } catch (UnanalyzedFileException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        }

        if ($reference === null) {
            return new Success(null);
        }


        $code_location = $this->codebase->getSymbolLocationByReference($reference);

        if (!$code_location) {
            return new Success(null);
        }

        return new Success(
            new Location(
                $this->server->pathToUri($code_location->file_path),
                new Range(
                    new Position($code_location->getLineNumber() - 1, $code_location->getColumn() - 1),
                    new Position($code_location->getEndLineNumber() - 1, $code_location->getEndColumn() - 1),
                ),
            ),
        );
    }

    /**
     * The hover request is sent from the client to the server to request
     * hover information at a given text document position.
     *
     * @param TextDocumentIdentifier $textDocument The text document
     * @param Position $position The position inside the text document
     * @psalm-return Promise<Hover>|Promise<null>
     */
    public function hover(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        if (!$this->server->client->clientConfiguration->provideHover) {
            return new Success(null);
        }

        $this->server->logDebug(
            'textDocument/hover',
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        //This currently doesnt work right with out of project files
        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return new Success(null);
        }

        try {
            $reference = $this->codebase->getReferenceAtPositionAsReference($file_path, $position);
        } catch (UnanalyzedFileException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        }

        if ($reference === null) {
            return new Success(null);
        }

        try {
            $markup = $this->codebase->getMarkupContentForSymbolByReference($reference);
        } catch (UnexpectedValueException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        }

        if ($markup === null) {
            return new Success(null);
        }

        return new Success(new Hover($markup, $reference->range));
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
     * @param TextDocumentIdentifier $textDocument The text document
     * @param Position $position The position
     * @psalm-return Promise<array<empty, empty>>|Promise<CompletionList>|Promise<null>
     */
    public function completion(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        if (!$this->server->client->clientConfiguration->provideCompletion) {
            return new Success(null);
        }

        $this->server->logDebug(
            'textDocument/completion',
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        //This currently doesnt work right with out of project files
        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return new Success(null);
        }

        try {
            $completion_data = $this->codebase->getCompletionDataAtPosition($file_path, $position);
            if ($completion_data) {
                [$recent_type, $gap, $offset] = $completion_data;

                if ($gap === '->' || $gap === '::') {
                    $snippetSupport = $this->server->clientCapabilities
                        ->textDocument->completion->completionItem->snippetSupport ?? false;
                    $completion_items =
                        $this->codebase->getCompletionItemsForClassishThing($recent_type, $gap, $snippetSupport);
                } elseif ($gap === '[') {
                    $completion_items = $this->codebase->getCompletionItemsForArrayKeys($recent_type);
                } else {
                    $completion_items = $this->codebase->getCompletionItemsForPartialSymbol(
                        $recent_type,
                        $offset,
                        $file_path,
                    );
                }
                return new Success(new CompletionList($completion_items, false));
            }
        } catch (UnanalyzedFileException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        } catch (TypeParseTreeException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        }

        try {
            $type_context = $this->codebase->getTypeContextAtPosition($file_path, $position);
            if ($type_context) {
                $completion_items = $this->codebase->getCompletionItemsForType($type_context);
                return new Success(new CompletionList($completion_items, false));
            }
        } catch (UnexpectedValueException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        } catch (TypeParseTreeException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        }

        $this->server->logError('completion not found at ' . $position->line . ':' . $position->character);
        return new Success(null);
    }

    /**
     * The signature help request is sent from the client to the server to request signature
     * information at a given cursor position.
     */
    public function signatureHelp(TextDocumentIdentifier $textDocument, Position $position): Promise
    {
        if (!$this->server->client->clientConfiguration->provideSignatureHelp) {
            return new Success(null);
        }

        $this->server->logDebug(
            'textDocument/signatureHelp',
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        //This currently doesnt work right with out of project files
        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return new Success(null);
        }

        try {
            $argument_location = $this->codebase->getFunctionArgumentAtPosition($file_path, $position);
        } catch (UnanalyzedFileException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        }

        if ($argument_location === null) {
            return new Success(null);
        }

        try {
            $signature_information = $this->codebase->getSignatureInformation($argument_location[0], $file_path);
        } catch (UnexpectedValueException $e) {
            $this->server->logThrowable($e);
            return new Success(null);
        }

        if (!$signature_information) {
            return new Success(null);
        }

        return new Success(
            new SignatureHelp(
                [$signature_information],
                0,
                $argument_location[1],
            ),
        );
    }

    /**
     * The code action request is sent from the client to the server to compute commands
     * for a given text document and range. These commands are typically code fixes to
     * either fix problems or to beautify/refactor code.
     */
    public function codeAction(TextDocumentIdentifier $textDocument, CodeActionContext $context): Promise
    {
        if (!$this->server->client->clientConfiguration->provideCodeActions) {
            return new Success(null);
        }

        $this->server->logDebug(
            'textDocument/codeAction',
        );

        $file_path = $this->server->uriToPath($textDocument->uri);

        //Don't report code actions for files we arent watching
        if (!$this->codebase->config->isInProjectDirs($file_path)) {
            return new Success(null);
        }

        $fixers = [];
        foreach ($context->diagnostics as $diagnostic) {
            if ($diagnostic->source !== 'psalm') {
                continue;
            }

            /** @var array{type: string, snippet: string, line_from: int, line_to: int} */
            $data = (array)$diagnostic->data;

            //$file_path = $this->server->uriToPath($textDocument->uri);
            //$contents = $this->codebase->file_provider->getContents($file_path);

            $snippetRange = new Range(
                new Position($data['line_from'] - 1, 0),
                new Position($data['line_to'], 0),
            );

            $indentation = '';
            if (preg_match('/^(\s*)/', $data['snippet'], $matches)) {
                $indentation = $matches[1] ?? '';
            }

            //Suppress Ability
            $fixers["suppress.{$data['type']}"] = new CodeAction(
                "Suppress {$data['type']} for this line",
                CodeActionKind::QUICK_FIX,
                null,
                null,
                null,
                new WorkspaceEdit([
                    $textDocument->uri => [
                        new TextEdit(
                            $snippetRange,
                            "{$indentation}/** @psalm-suppress {$data['type']} */\n".
                            "{$data['snippet']}\n",
                        ),
                    ],
                ]),
            );
        }

        if (empty($fixers)) {
            return new Success(null);
        }

        return new Success(
            array_values($fixers),
        );
    }
}
