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
use ReflectionClass;

use function count;
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

    /**
     * @return iterable<string, array{bool}>
     * @psalm-pure
     */
    public static function provideSnippetStates(): iterable
    {
        yield 'snippet not computed' => [false];
        yield 'snippet computed' => [true];
    }

    /**
     * Workers send their locations to the parent, which must get back what they had: the lazily computed
     * properties only when they were computed, and the previous location of one.
     *
     * The location is restored through __unserialize() rather than unserialize(): its file path comes
     * from FileSource::getFilePath(), which Psalm's taint analysis treats as user input.
     */
    #[DataProvider('provideSnippetStates')]
    public function testSerializationRoundTrip(bool $computed): void
    {
        $contents = "<?php\n// Grüße\n\$a = 'x' . \$b;\n";
        $previous = $this->locate($contents, '$b', 3);

        $start = strlen("<?php\n// Grüße\n\$a = ");
        $node = new String_('x', ['startFilePos' => $start, 'endFilePos' => $start + 2, 'startLine' => 3]);
        $location = new CodeLocation($this->file_analyzer, $node, $previous, true, null, null, 7);

        if ($computed) {
            $this->assertNotSame('', $location->getSnippet());
        }

        $copy = (new ReflectionClass(CodeLocation::class))->newInstanceWithoutConstructor();
        $copy->__unserialize($location->__serialize());

        $this->assertEquals($location, $copy);
        $this->assertSame($location->getSelectedText(), $copy->getSelectedText());
        $this->assertSame($location->getSnippet(), $copy->getSnippet());
        $this->assertSame($location->getLineNumber(), $copy->getLineNumber());
        $this->assertSame($location->getEndLineNumber(), $copy->getEndLineNumber());
        $this->assertSame($location->getSelectionBounds(), $copy->getSelectionBounds());
        $this->assertSame($location->getColumn(), $copy->getColumn());
        $this->assertEquals($previous, $copy->previous_location);
    }

    public function testLocationNotAskedForItsSnippetSerializesWithoutTheComputedProperties(): void
    {
        $location = $this->locate("<?php\n\$a = 1;\n", '$a', 2);
        $fresh_property_count = count($location->__serialize());

        $this->assertSame('$a = 1;', $location->getSnippet());

        $this->assertLessThan(count($location->__serialize()), $fresh_property_count);
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
