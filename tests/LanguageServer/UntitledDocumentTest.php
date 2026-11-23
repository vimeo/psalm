<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\CodeActionContext;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\TextDocumentContentChangeEvent;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
use LanguageServerProtocol\VersionedTextDocumentIdentifier;
use Psalm\Internal\LanguageServer\ClientConfiguration;
use Psalm\Internal\LanguageServer\LanguageServer;
use Psalm\Internal\LanguageServer\PathMapper;
use Psalm\Internal\LanguageServer\Progress;
use Psalm\Internal\LanguageServer\Server\TextDocument;
use Psalm\Tests\TestCase;

use function getcwd;

/**
 * An unsaved document of the editor has an `untitled:` URI, which names no file to analyse.
 */
final class UntitledDocumentTest extends TestCase
{
    private const URI = 'untitled:Untitled-4';

    public function testDidOpenIgnoresAnUntitledDocument(): void
    {
        $this->getTextDocument()->didOpen(new TextDocumentItem(self::URI, 'php', 1, '<?php echo 1;'));

        $this->addToAssertionCount(1);
    }

    public function testDidChangeIgnoresAnUntitledDocument(): void
    {
        $this->getTextDocument()->didChange(
            new VersionedTextDocumentIdentifier(self::URI, 2),
            [new TextDocumentContentChangeEvent(null, null, '<?php echo 2;')],
        );

        $this->addToAssertionCount(1);
    }

    public function testDidSaveIgnoresAnUntitledDocument(): void
    {
        $this->getTextDocument()->didSave(new TextDocumentIdentifier(self::URI), '<?php echo 2;');

        $this->addToAssertionCount(1);
    }

    public function testDidCloseIgnoresAnUntitledDocument(): void
    {
        $this->getTextDocument()->didClose(new TextDocumentIdentifier(self::URI));

        $this->addToAssertionCount(1);
    }

    public function testHoverOnAnUntitledDocumentIsNull(): void
    {
        $this->assertNull(
            $this->getTextDocument()->hover(new TextDocumentIdentifier(self::URI), new Position(0, 0)),
        );
    }

    public function testDefinitionOnAnUntitledDocumentIsNull(): void
    {
        $this->assertNull(
            $this->getTextDocument()->definition(new TextDocumentIdentifier(self::URI), new Position(0, 0)),
        );
    }

    public function testCompletionOnAnUntitledDocumentIsNull(): void
    {
        $this->assertNull(
            $this->getTextDocument()->completion(new TextDocumentIdentifier(self::URI), new Position(0, 0)),
        );
    }

    public function testSignatureHelpOnAnUntitledDocumentIsNull(): void
    {
        $this->assertNull(
            $this->getTextDocument()->signatureHelp(new TextDocumentIdentifier(self::URI), new Position(0, 0)),
        );
    }

    public function testCodeActionOnAnUntitledDocumentIsNull(): void
    {
        $this->assertNull(
            $this->getTextDocument()->codeAction(new TextDocumentIdentifier(self::URI), new CodeActionContext()),
        );
    }

    private function getTextDocument(): TextDocument
    {
        $codebase = $this->project_analyzer->getCodebase();

        $clientConfiguration = new ClientConfiguration();
        $clientConfiguration->logLevel = null;
        $clientConfiguration->provideHover = true;
        $clientConfiguration->provideDefinition = true;
        $clientConfiguration->provideCompletion = true;
        $clientConfiguration->provideSignatureHelp = true;
        $clientConfiguration->provideCodeActions = true;

        $server = new LanguageServer(
            new MockProtocolStream(),
            new MockProtocolStream(),
            $this->project_analyzer,
            $codebase,
            $clientConfiguration,
            new Progress,
            new PathMapper((string) getcwd(), (string) getcwd()),
        );

        return new TextDocument($server, $codebase, $this->project_analyzer);
    }
}
