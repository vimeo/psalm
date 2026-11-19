<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use LanguageServerProtocol\Position;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\LanguageServer\Server\TextDocument;

final class SuppressTextEditTest extends TestCase
{
    public function testSuppressionIsInsertedBeforeTheLineOfTheIssue(): void
    {
        $edit = TextDocument::getSuppressTextEdit('MixedAssignment', 4, '    $a = foo();');

        $this->assertSame("    /** @psalm-suppress MixedAssignment */\n", $edit->newText);
        $this->assertEquals(new Position(3, 0), $edit->range->start);
        // nothing of the code is replaced, whichever line the issue is on (the last line of a file included)
        $this->assertEquals($edit->range->start, $edit->range->end);
    }

    public function testSuppressionOfAnIssueWithoutIndentation(): void
    {
        $edit = TextDocument::getSuppressTextEdit('UndefinedVariable', 1, 'echo $a;');

        $this->assertSame("/** @psalm-suppress UndefinedVariable */\n", $edit->newText);
        $this->assertEquals(new Position(0, 0), $edit->range->start);
    }
}
