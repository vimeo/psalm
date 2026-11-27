<?php

declare(strict_types=1);

namespace Psalm\Tests;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;
use PhpParser\Comment\Doc;
use PhpParser\Node\Scalar\String_;
use Psalm\CodeLocation;
use Psalm\Internal\Analyzer\FileAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;

use function mb_check_encoding;
use function restore_error_handler;
use function set_error_handler;
use function str_repeat;
use function strlen;
use function strpos;
use function substr;

final class CodeLocationTest extends BaseTestCase
{
    private const FILE = 'somefile.php';

    private FakeFileProvider $file_provider;

    private FileAnalyzer $file_analyzer;

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

        $project_analyzer = new ProjectAnalyzer(
            new TestConfig(),
            new Providers($this->file_provider, new FakeParserCacheProvider()),
        );

        $this->file_analyzer = new FileAnalyzer($project_analyzer, self::FILE, self::FILE);
    }

    /**
     * A selection followed by more than 200 bytes on its line is previewed up to 50 bytes past
     * its end. Each padding moves that cut to a different byte of a 3-byte character, so one of
     * them lands on the boundary and the others inside the character, where the snippet must
     * stop before it rather than split it.
     *
     * @return iterable<string, array{int}>
     * @psalm-pure
     */
    public static function providePaddings(): iterable
    {
        yield 'cut on a boundary' => [0];
        yield 'cut after the first byte' => [1];
        yield 'cut after the second byte' => [2];
    }

    #[DataProvider('providePaddings')]
    public function testLongLineSnippetDoesNotEndInsideAMultiByteCharacter(int $padding): void
    {
        $line = '$a = 1; //' . str_repeat(' ', $padding) . str_repeat('漢', 100);
        $contents = "<?php\n" . $line . "\n";
        $location = $this->locate($contents, '$a', 2);

        // The longest prefix of the line within 50 bytes of the selection ($a) that is valid UTF-8
        $expected = substr($line, 0, 2 + 50);
        while (!mb_check_encoding($expected, 'UTF-8')) {
            $expected = substr($expected, 0, -1);
        }

        $this->assertSame($expected, $location->getSnippet());
        $this->assertSame('$a', $location->getSelectedText());
    }

    public function testSnippetOfASelectionEndingTheFile(): void
    {
        $contents = "<?php\n// Grüße 漢字 🎲\necho \$a";
        $location = $this->locate($contents, '$a', 3);

        // The cut ends at the last byte of the file, so nothing past it may be read
        $errors = [];
        set_error_handler(static function (int $_errno, string $message) use (&$errors): bool {
            $errors[] = $message;
            return true;
        });
        try {
            $snippet = $location->getSnippet();
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors);
        $this->assertSame('echo $a', $snippet);
        $this->assertSame('$a', $location->getSelectedText());
        $this->assertSame(6, $location->getColumn());
        $this->assertSame(8, $location->getEndColumn());
    }

    public function testSnippetAfterMultiByteText(): void
    {
        $contents = "<?php\n/* Grüße — 漢字 🎲 */\n\$x = '→ é'; echo \$x;\n";
        $location = $this->locate($contents, "'→ é'", 3);

        $this->assertSame("\$x = '→ é'; echo \$x;", $location->getSnippet());
        $this->assertSame("'→ é'", $location->getSelectedText());
    }

    public function testRegexSelectionAfterMultiByteText(): void
    {
        $contents = "<?php\n/* 漢字 🎲 */ function f(): ?\\DateTime { return null; }\n";
        $location = $this->locate(
            $contents,
            'function f(): ?\\DateTime { return null; }',
            2,
            CodeLocation::FUNCTION_RETURN_TYPE,
        );

        $this->assertSame('?\\DateTime', $location->getSelectedText());
        $this->assertSame('/* 漢字 🎲 */ function f(): ?\\DateTime { return null; }', $location->getSnippet());
    }

    public function testDocblockSelectionAfterMultiByteText(): void
    {
        $docblock = "/**\n * Désolé — 漢字 🎲\n * @var int\n */";
        $contents = "<?php\n" . $docblock . "\n\$x = 1;\n";
        $doc_start = strlen("<?php\n");
        $statement_start = $doc_start + strlen($docblock) + 1;

        $node = new String_('x', [
            'startFilePos' => $statement_start,
            'endFilePos' => $statement_start + 6,
            'startLine' => 6,
            'comments' => [new Doc($docblock, 2, $doc_start)],
        ]);
        $this->file_provider->registerFile(self::FILE, $contents);
        $location = new CodeLocation($this->file_analyzer, $node, null, false, CodeLocation::VAR_TYPE, null, 4);

        $this->assertSame('int', $location->getSelectedText());
        $this->assertSame(4, $location->getLineNumber());
    }

    public function testDocblockSelectionAboveANodeWithoutTheDocComment(): void
    {
        $contents = "<?php\n/**\n * @param int \$b\n */\n\$f = function (string \$a): void {};\n";
        $location = $this->locateWithCommentLine($contents, 'function (string $a): void {}', 5, 3);

        $this->assertSame('$b', $location->getSelectedText());
        $this->assertSame(3, $location->getLineNumber());
        $this->assertSame(3, $location->getEndLineNumber());
        $this->assertSame(15, $location->getColumn());
        $this->assertSame(17, $location->getEndColumn());
    }

    public function testDocblockSelectionOnTheLineOfANodeWithoutTheDocComment(): void
    {
        $contents = "<?php\n/** @param int \$b */ \$f = function (string \$a): void {};\n";
        $location = $this->locateWithCommentLine($contents, 'function (string $a): void {}', 2, 2);

        $this->assertSame('$b', $location->getSelectedText());
        $this->assertSame(2, $location->getLineNumber());
        $this->assertSame(16, $location->getColumn());
    }

    public function testDocblockSelectionOnTheFirstLine(): void
    {
        $contents = "/** @param int \$b */\n\$f = 1;\n";
        $location = $this->locateWithCommentLine($contents, '1', 2, 1);

        $this->assertSame('$b', $location->getSelectedText());
        $this->assertSame(1, $location->getLineNumber());
        $this->assertSame(16, $location->getColumn());
    }

    public function testDocblockSelectionAfterCodeOnItsLine(): void
    {
        $contents = "<?php\n\$s = '@'; \$b = 1; /** @param int \$b */ function f(string \$a): void {}\n";
        $location = $this->locateWithCommentLine($contents, 'function f(string $a): void {}', 2, 2);

        $this->assertSame('$b', $location->getSelectedText());
        $this->assertSame(34, $location->getColumn());
    }

    public function testDocblockSelectionBeforeCodeOnItsLine(): void
    {
        $contents = "<?php\n/** @var int */ \$x = 1;\n";
        $location = $this->locateWithCommentLine($contents, '1', 2, 2, null, null);

        $this->assertSame('@var int', $location->getSelectedText());
        $this->assertSame(5, $location->getColumn());
    }

    public function testCommentLineSelectionOnAnIndentedLineWithoutATag(): void
    {
        $contents = "<?php\nclass A {\n    #[DataProvider('provider')]\n    public function testFoo(): void {}\n}\n";
        $location = $this->locateWithCommentLine($contents, 'testFoo', 4, 3, null, null);

        $this->assertSame("#[DataProvider('provider')]", $location->getSelectedText());
        $this->assertSame(3, $location->getLineNumber());
        $this->assertSame(5, $location->getColumn());
        $this->assertSame(32, $location->getEndColumn());
    }

    public function testCommentLineSelectionOnATabIndentedLineWithoutATag(): void
    {
        $contents = "<?php\nclass A {\n\t#[Attr]\n\tpublic function f(): void {}\n}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 4, 3, null, null);

        $this->assertSame('#[Attr]', $location->getSelectedText());
        $this->assertSame(2, $location->getColumn());
    }

    public function testCommentLineSelectionOnAnAttributeLineHoldingAnAt(): void
    {
        $contents = "<?php\nclass A {\n    #[TestWith(['a@b.c'])]\n    public function f(): void {}\n}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 4, 3, null, null);

        $this->assertSame("#[TestWith(['a@b.c'])]", $location->getSelectedText());
        $this->assertSame(5, $location->getColumn());
    }

    public function testCommentLineSelectionOnADocblockTextLineHoldingAnAt(): void
    {
        $contents = "<?php\n/**\n * Mail me at a@b.c\n */\nfunction f(): void {}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 5, 3, null, null);

        $this->assertSame('Mail me at a@b.c', $location->getSelectedText());
        $this->assertSame(4, $location->getColumn());
    }

    public function testCommentLineSelectionKeepsSlashes(): void
    {
        $contents = "<?php\n/**\n * @see https://example.com/foo\n */\nfunction f(): void {}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 5, 3, null, null);

        $this->assertSame('@see https://example.com/foo', $location->getSelectedText());
        $this->assertSame(4, $location->getColumn());
    }

    public function testCommentLineSelectionOfAOneLineCommentWithoutATag(): void
    {
        $contents = "<?php\n/** mail a@b */ function f(): void {}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 2, 2, null, null);

        $this->assertSame('mail a@b', $location->getSelectedText());
        $this->assertSame(5, $location->getColumn());
    }

    public function testCommentLineSelectionBeforeADoubleStarCommentEnd(): void
    {
        $contents = "<?php\n/** @return void **/\nfunction f(): void {}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 3, 2, null, null);

        $this->assertSame('@return void', $location->getSelectedText());
        $this->assertSame(5, $location->getColumn());
    }

    public function testCommentLineSelectionKeepsAWildcardBeforeTheCommentEnd(): void
    {
        $contents = "<?php\nclass A {\n    /** @return self::STATUS_* */\n    public function f() {}\n}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 4, 3, null, null);

        $this->assertSame('@return self::STATUS_*', $location->getSelectedText());
        $this->assertSame(9, $location->getColumn());
    }

    public function testCommentLineSelectionOfACommentOpeningAlone(): void
    {
        $contents = "<?php\nclass A {\n    /**\n     * @return void\n     */\n    public function f(): void {}\n}\n";
        $location = $this->locateWithCommentLine($contents, 'f(', 6, 3, null, null);

        $this->assertSame('/**', $location->getSelectedText());
        $this->assertSame(3, $location->getLineNumber());
        $this->assertSame(5, $location->getColumn());
    }

    private function locateWithCommentLine(
        string $contents,
        string $selection,
        int $line,
        int $comment_line,
        ?int $regex_type = CodeLocation::FUNCTION_PARAM_VAR,
        ?string $selected_text = '$b',
    ): CodeLocation {
        $this->file_provider->registerFile(self::FILE, $contents);

        $start = strpos($contents, $selection);
        $this->assertNotFalse($start);

        $node = new String_('x', [
            'startFilePos' => $start,
            'endFilePos' => $start + strlen($selection) - 1,
            'startLine' => $line,
        ]);

        return new CodeLocation(
            $this->file_analyzer,
            $node,
            null,
            true,
            $regex_type,
            $selected_text,
            $comment_line,
        );
    }

    private function locate(
        string $contents,
        string $selection,
        int $line,
        ?int $regex_type = null,
    ): CodeLocation {
        $this->file_provider->registerFile(self::FILE, $contents);

        $start = strpos($contents, $selection);
        $this->assertNotFalse($start);

        $node = new String_('x', [
            'startFilePos' => $start,
            'endFilePos' => $start + strlen($selection) - 1,
            'startLine' => $line,
        ]);

        return new CodeLocation($this->file_analyzer, $node, null, false, $regex_type);
    }
}
