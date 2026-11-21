<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use LanguageServerProtocol\TextDocumentIdentifier;
use LanguageServerProtocol\TextDocumentItem;
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
    public function testRequestsForAnUntitledDocumentAreAnsweredWithNothing(): void
    {
        $codebase = $this->project_analyzer->getCodebase();

        $clientConfiguration = new ClientConfiguration();
        $clientConfiguration->logLevel = null;

        $server = new LanguageServer(
            new MockProtocolStream(),
            new MockProtocolStream(),
            $this->project_analyzer,
            $codebase,
            $clientConfiguration,
            new Progress,
            new PathMapper((string) getcwd(), (string) getcwd()),
        );

        $text_document = new TextDocument($server, $codebase, $this->project_analyzer);
        $uri = 'untitled:Untitled-4';
        $document = new TextDocumentIdentifier($uri);
        $position = new Position(0, 0);

        $text_document->didOpen(new TextDocumentItem($uri, 'php', 1, '<?php echo 1;'));

        $this->assertNull($text_document->hover($document, $position));
        $this->assertNull($text_document->definition($document, $position));
        $this->assertNull($text_document->completion($document, $position));
        $this->assertNull($text_document->signatureHelp($document, $position));

        $text_document->didClose($document);
    }
}
