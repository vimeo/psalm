<?php

declare(strict_types=1);

namespace Psalm\Tests\Config;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use Psalm\Config\FileFilter;
use Psalm\Tests\TestCase;

use function array_diff;
use function array_filter;
use function array_values;
use function assert;
use function dirname;
use function file_put_contents;
use function is_dir;
use function is_file;
use function is_link;
use function mkdir;
use function realpath;
use function rmdir;
use function scandir;
use function sort;
use function str_replace;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class FileFilterGlobTest extends TestCase
{
    private string $root = '';

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-glob-', true);
        mkdir($this->root);
        $this->root = (string) realpath($this->root);
    }

    /**
     * @param list<string> $files
     * @param 'directory'|'file' $kind
     * @param list<string> $listed what getDirectories() (or getFiles() for a file entry) returns
     * @param list<string> $excluded the fixture files the filter does not allow
     */
    #[DataProvider('providerEntries')]
    public function testEntry(array $files, string $kind, string $entry, array $listed, array $excluded): void
    {
        try {
            $this->createFiles($files);
            $filter = FileFilter::loadFromArray([$kind => [['name' => $entry]]], $this->root, false);

            $actual = str_replace(
                [$this->root . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR],
                ['', '/'],
                $kind === 'file' ? $filter->getFiles() : $filter->getDirectories(),
            );
            sort($actual);
            self::assertSame($listed, $actual);

            $actual = array_values(array_filter(
                $files,
                fn(string $file): bool => !$filter->allows($this->path($file)),
            ));
            sort($actual);
            self::assertSame($excluded, $actual);
        } finally {
            self::removeTree($this->root);
        }
    }

    /**
     * @psalm-pure
     * @return iterable<string, array{list<string>, 'directory'|'file', string, list<string>, list<string>}>
     */
    public static function providerEntries(): iterable
    {
        yield 'a match inside a match is not listed again' => [
            ['vendor/a/vendor/A.php', 'app/vendor/B.php', 'app/Main.php'],
            'directory',
            '**/vendor',
            ['app/vendor/', 'vendor/'],
            ['app/vendor/B.php', 'vendor/a/vendor/A.php'],
        ];

        yield 'a wildcard in the next part prunes like a literal one' => [
            ['venue/V.php', 'venue/vendor/W.php', 'veto/Z.php'],
            'directory',
            '**/ven*',
            ['venue/'],
            ['venue/V.php', 'venue/vendor/W.php'],
        ];

        yield 'a slash after ** is walked in full' => [
            ['foo/bar/C.php', 'foo/baz/D.php', 'a/foo/bar/foo/bar/B.php'],
            'directory',
            '**/foo/bar',
            ['a/foo/bar/', 'a/foo/bar/foo/bar/', 'foo/bar/'],
            ['a/foo/bar/foo/bar/B.php', 'foo/bar/C.php'],
        ];

        yield 'a file entry with two ** levels lists each file once' => [
            ['packages/one/lib/x/lib/y/Deeper.php', 'packages/one/lib/Lib.php', 'packages/one/src/One.php'],
            'file',
            'packages/**/lib/**/*.php',
            ['packages/one/lib/Lib.php', 'packages/one/lib/x/lib/y/Deeper.php'],
            ['packages/one/lib/Lib.php', 'packages/one/lib/x/lib/y/Deeper.php'],
        ];

        yield 'a file entry still searches a directory named like the files' => [
            ['src/OldTool.php/NewTool.php', 'src/Top.php'],
            'file',
            '**/*Tool.php',
            ['src/OldTool.php', 'src/OldTool.php/NewTool.php'],
            ['src/OldTool.php/NewTool.php'],
        ];
    }

    #[RequiresOperatingSystem('^(?!WIN)')]
    public function testMatchedDirectoryIsNotSearchedThroughASymlink(): void
    {
        $outside = $this->root . '-outside';

        try {
            mkdir($outside . '/pkg/vendor', 0777, true);
            file_put_contents($outside . '/pkg/vendor/Q.php', '<?php');
            $this->createFiles(['vendor/acme/Lib.php']);

            if (!@symlink($outside . '/pkg', $this->root . '/vendor/acme/pkg')) {
                self::markTestSkipped('symlinks cannot be created');
            }

            // vendor/acme/pkg lies inside the match vendor/, so the vendor directory it links to is not searched
            $directories = FileFilter::loadFromArray(['directory' => [['name' => '**/vendor']]], $this->root, false)
                ->getDirectories();
            self::assertSame([$this->root . '/vendor/'], $directories);

            // the files below the link are still found, through the match vendor/
            $files = FileFilter::loadFromArray(['file' => [['name' => '**/vendor/**/*.php']]], $this->root, false)
                ->getFiles();
            self::assertContains($outside . '/pkg/vendor/Q.php', $files);
        } finally {
            self::removeTree($this->root);
            self::removeTree($outside);
        }
    }

    /**
     * @param list<string> $files
     */
    private function createFiles(array $files): void
    {
        foreach ($files as $file) {
            $path = $this->path($file);

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, '<?php');
        }
    }

    /**
     * @psalm-capabilities read-props
     */
    private function path(string $file): string
    {
        return $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    }

    private static function removeTree(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            unlink($path);
        } elseif (is_dir($path)) {
            $entries = scandir($path);
            assert($entries !== false);

            foreach (array_diff($entries, ['.', '..']) as $entry) {
                self::removeTree($path . DIRECTORY_SEPARATOR . $entry);
            }

            rmdir($path);
        }
    }
}
