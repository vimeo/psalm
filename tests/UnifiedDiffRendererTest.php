<?php

declare(strict_types=1);

namespace Psalm\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\FileManipulation\UnifiedDiffRenderer;

final class UnifiedDiffRendererTest extends TestCase
{
    public function testPlainOutputIsGitStylePatchWithTitle(): void
    {
        $output = (new UnifiedDiffRenderer())->render(
            2,
            'src/A.php',
            '/project/src/A.php',
            "<?php\n\nfunction a() {}\n",
            "<?php\n\nfunction a(): void {}\n",
        );

        $this->assertSame(
            "\n2) src/A.php:3\n"
            . "--- a/src/A.php\n"
            . "+++ b/src/A.php\n"
            . "@@ -1,3 +1,3 @@\n"
            . " <?php\n"
            . " \n"
            . "-function a() {}\n"
            . "+function a(): void {}\n",
            $output,
        );
    }

    public function testPlainHeadersOfPathsWithSpacesAreTabTerminated(): void
    {
        $output = (new UnifiedDiffRenderer())->render(1, 'src/my file.php', '/p/src/my file.php', "a\n", "b\n");

        $this->assertStringContainsString("\n--- a/src/my file.php\t\n+++ b/src/my file.php\t\n", $output);
        $this->assertStringStartsWith("1) src/my file.php:1\n", $output);
    }

    public function testColoredOutputHighlightsChangedPartsWithoutSplittingMultibyteCharacters(): void
    {
        $output = (new UnifiedDiffRenderer(true, true))->render(
            1,
            'src/A.php',
            '/project/src/A.php',
            "<?php\n\$a = 'é1';\n\$b = 1;\n",
            "<?php\n\$a = 'ê1';\n\$b = 2;\n",
        );

        $this->assertSame(
            "\e[1m1) src/A.php:2\e[0m\n"
            . "\e[36m@@ -1,3 +1,3 @@\e[0m\n"
            . " <?php\n"
            . "\e[31m-\$a = '\e[7mé\e[27m1';\e[0m\n"
            . "\e[31m-\$b = \e[7m1\e[27m;\e[0m\n"
            . "\e[32m+\$a = '\e[7mê\e[27m1';\e[0m\n"
            . "\e[32m+\$b = \e[7m2\e[27m;\e[0m\n",
            $output,
        );
    }

    public function testColoredOutputDoesNotHighlightUnequalRunsAndLinksTitle(): void
    {
        $output = (new UnifiedDiffRenderer(true, false))->render(
            1,
            'a.txt',
            '/project/a.txt',
            "one\ntwo\n",
            "uno\n",
        );

        $this->assertStringContainsString("\e]8;;", $output);
        $this->assertStringContainsString("\e[1m1) a.txt:1\e[0m", $output);
        $this->assertStringContainsString("\e[31m-one\e[0m\n\e[31m-two\e[0m\n\e[32m+uno\e[0m\n", $output);
        $this->assertStringNotContainsString("\e[7m", $output);
        $this->assertStringNotContainsString('--- a/', $output);
    }

    public function testColoredOutputDimsMissingNewlineMarker(): void
    {
        $output = (new UnifiedDiffRenderer(true, true))->render(1, 'a.txt', '/p/a.txt', "a\n", 'b');

        $this->assertStringContainsString("\e[2m\\ No newline at end of file\e[0m\n", $output);
    }

    #[DataProvider('firstChangedLineProvider')]
    public function testTitleLinePointsAtTheFirstChangedLine(string $old, string $new, int $expected_line): void
    {
        $output = (new UnifiedDiffRenderer())->render(1, 'a.txt', '/p/a.txt', $old, $new);

        $this->assertStringStartsWith('1) a.txt:' . $expected_line . "\n", $output);
    }

    /**
     * @return iterable<string, array{string, string, int}>
     * @psalm-pure
     */
    public static function firstChangedLineProvider(): iterable
    {
        yield 'append at end of file' => ["a\nb\n", "a\nb\nc\n", 3];
        yield 'delete last line' => ["a\nb\n", "a\n", 1];
        yield 'change on first line' => ["a\nb\n", "x\nb\n", 1];
        yield 'change on first line without trailing newline' => ['a', 'b', 1];
        yield 'change in the middle' => ["a\nb\nc\n", "a\nB\nc\n", 2];
    }
}
