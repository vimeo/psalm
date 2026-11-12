<?php

declare(strict_types=1);

namespace Psalm\Tests\Config;

use FilesystemIterator;
use Override;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use Psalm\Config\FileFilter;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Tests\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_map;
use function dirname;
use function explode;
use function fclose;
use function file_put_contents;
use function is_dir;
use function json_decode;
use function mkdir;
use function preg_match;
use function proc_close;
use function proc_open;
use function realpath;
use function rmdir;
use function sort;
use function str_replace;
use function stream_get_contents;
use function strlen;
use function substr;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class FileFilterGlobTest extends TestCase
{
    private string $root;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm-glob-', true);

        $this->createFiles([
            'app/Main.php',
            'app/vendor/Copied.php',
            'vendor/acme/lib/Lib.php',
            'vendor/acme/lib/vendor/bundled/Bundled.php',
            'packages/one/src/One.php',
            'packages/one/vendor/dep/Dep.php',
            'packages/one/lib/inner/vendor/Deep.php',
            'packages/one/lib/inner/vendor/x/lib/y/vendor/Deeper.php',
            'src/Top.php',
            'src/feature/src/Nested.php',
            'src/OldTool.php/NewTool.php',
            'packages/two/vendor/q/lib/r/vendor/Far.php',
        ]);

        $this->root = (string) realpath($this->root);
    }

    /**
     * @param list<string> $files
     */
    private function createFiles(array $files): void
    {
        foreach ($files as $file) {
            $path = $this->root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);

            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, '<?php');
        }
    }

    public function testRecursiveEntryDoesNotListMatchesInsideMatches(): void
    {
        try {
            $filter = FileFilter::loadFromArray(['directory' => [['name' => '**/vendor']]], $this->root, false);

            self::assertSame(
                ['app/vendor/', 'packages/one/lib/inner/vendor/', 'packages/one/vendor/', 'packages/two/vendor/', 'vendor/'],
                $this->relativeDirectories($filter),
            );

            self::assertSame(
                [
                    'app/Main.php',
                    'packages/one/src/One.php',
                    'src/OldTool.php/NewTool.php',
                    'src/Top.php',
                    'src/feature/src/Nested.php',
                ],
                $this->allowedFiles($filter),
            );
        } finally {
            $this->removeFixture();
        }
    }

    public function testEntryWithTwoRecursiveLevels(): void
    {
        try {
            $filter = FileFilter::loadFromArray(
                ['directory' => [['name' => 'packages/**/lib/**/vendor']]],
                $this->root,
                false,
            );

            self::assertSame(
                ['packages/one/lib/inner/vendor/', 'packages/two/vendor/q/lib/r/vendor/'],
                $this->relativeDirectories($filter),
            );

            self::assertSame(
                [
                    'app/Main.php',
                    'app/vendor/Copied.php',
                    'packages/one/src/One.php',
                    'packages/one/vendor/dep/Dep.php',
                    'src/OldTool.php/NewTool.php',
                    'src/Top.php',
                    'src/feature/src/Nested.php',
                    'vendor/acme/lib/Lib.php',
                    'vendor/acme/lib/vendor/bundled/Bundled.php',
                ],
                $this->allowedFiles($filter),
            );
        } finally {
            $this->removeFixture();
        }
    }

    public function testRecursiveProjectEntryStillFindsEveryFile(): void
    {
        try {
            $filter = FileFilter::loadFromArray(['directory' => [['name' => '**/src']]], $this->root, true);

            self::assertSame(['packages/one/src/', 'src/'], $this->relativeDirectories($filter));

            $files = [];

            foreach ($filter->getDirectories() as $directory) {
                foreach ((new FileProvider())->getFilesInDir($directory, ['php']) as $file) {
                    $files[$file] = str_replace(DIRECTORY_SEPARATOR, '/', substr($file, strlen($this->root) + 1));
                }
            }

            sort($files);

            self::assertSame(
                ['packages/one/src/One.php', 'src/OldTool.php/NewTool.php', 'src/Top.php', 'src/feature/src/Nested.php'],
                $files,
            );
            self::assertSame($files, $this->allowedFiles($filter));
        } finally {
            $this->removeFixture();
        }
    }

    public function testRecursiveFileEntryStillSearchesDirectoriesNamedLikeTheFiles(): void
    {
        try {
            $filter = FileFilter::loadFromArray(['file' => [['name' => '**/*Tool.php']]], $this->root, false);

            $files = array_map(
                fn(string $file): string => str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    substr($file, strlen($this->root) + 1),
                ),
                $filter->getFiles(),
            );
            sort($files);

            self::assertSame(['src/OldTool.php', 'src/OldTool.php/NewTool.php'], $files);
        } finally {
            $this->removeFixture();
        }
    }

    public function testFileEntryWithTwoRecursiveLevelsListsEachFileOnce(): void
    {
        try {
            $filter = FileFilter::loadFromArray(['file' => [['name' => 'packages/**/lib/**/*.php']]], $this->root, false);

            $files = array_map(
                fn(string $file): string => str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    substr($file, strlen($this->root) + 1),
                ),
                $filter->getFiles(),
            );
            sort($files);

            self::assertSame(
                [
                    'packages/one/lib/inner/vendor/Deep.php',
                    'packages/one/lib/inner/vendor/x/lib/y/vendor/Deeper.php',
                    'packages/two/vendor/q/lib/r/vendor/Far.php',
                ],
                $files,
            );
        } finally {
            $this->removeFixture();
        }
    }

    public function testSlashAfterRecursiveWildcardIsWalkedAsBefore(): void
    {
        try {
            $this->createFiles([
                'foo/bar/C.php',
                'foo/baz/D.php',
                'a/foo/bar/A.php',
                'a/foo/bar/foo/bar/B.php',
            ]);
            $filter = FileFilter::loadFromArray(['directory' => [['name' => '**/foo/bar']]], $this->root, false);

            self::assertSame(
                ['a/foo/bar/', 'a/foo/bar/foo/bar/', 'foo/bar/'],
                $this->relativeDirectories($filter),
            );

            self::assertSame(
                [
                    'app/Main.php',
                    'app/vendor/Copied.php',
                    'foo/baz/D.php',
                    'packages/one/lib/inner/vendor/Deep.php',
                    'packages/one/lib/inner/vendor/x/lib/y/vendor/Deeper.php',
                    'packages/one/src/One.php',
                    'packages/one/vendor/dep/Dep.php',
                    'packages/two/vendor/q/lib/r/vendor/Far.php',
                    'src/OldTool.php/NewTool.php',
                    'src/Top.php',
                    'src/feature/src/Nested.php',
                    'vendor/acme/lib/Lib.php',
                    'vendor/acme/lib/vendor/bundled/Bundled.php',
                ],
                $this->allowedFiles($filter),
            );
        } finally {
            $this->removeFixture();
        }
    }

    public function testWildcardInTheNextPartPrunesLikeALiteralPart(): void
    {
        try {
            $this->createFiles(['venue/V.php', 'venue/vendor/W.php', 'veto/Z.php']);
            $filter = FileFilter::loadFromArray(['directory' => [['name' => '**/ven*']]], $this->root, false);

            self::assertSame(
                [
                    'app/vendor/',
                    'packages/one/lib/inner/vendor/',
                    'packages/one/vendor/',
                    'packages/two/vendor/',
                    'vendor/',
                    'venue/',
                ],
                $this->relativeDirectories($filter),
            );

            self::assertSame(
                [
                    'app/Main.php',
                    'packages/one/src/One.php',
                    'src/OldTool.php/NewTool.php',
                    'src/Top.php',
                    'src/feature/src/Nested.php',
                    'veto/Z.php',
                ],
                $this->allowedFiles($filter),
            );
        } finally {
            $this->removeFixture();
        }
    }

    public function testRecursiveFileEntryWithOneRecursiveLevelFindsFilesInNestedMatches(): void
    {
        try {
            $filter = FileFilter::loadFromArray(['file' => [['name' => '**/vendor/**/*.php']]], $this->root, false);

            $files = array_map(
                fn(string $file): string => str_replace(
                    DIRECTORY_SEPARATOR,
                    '/',
                    substr($file, strlen($this->root) + 1),
                ),
                $filter->getFiles(),
            );
            sort($files);

            self::assertSame(
                [
                    'app/vendor/Copied.php',
                    'packages/one/lib/inner/vendor/Deep.php',
                    'packages/one/lib/inner/vendor/x/lib/y/vendor/Deeper.php',
                    'packages/one/vendor/dep/Dep.php',
                    'packages/two/vendor/q/lib/r/vendor/Far.php',
                    'vendor/acme/lib/Lib.php',
                    'vendor/acme/lib/vendor/bundled/Bundled.php',
                ],
                $files,
            );
        } finally {
            $this->removeFixture();
        }
    }

    /**
     * Runs the CLI: IssueBuffer::finish() exits when it reports an error.
     */
    public function testUnusedIssueHandlerSuppressionNamesEveryMatchOnce(): void
    {
        try {
            $this->createFiles(['pk/vendor/r.php', 'pk/vendor/x/vendor/q.php', 'vendor/a/vendor/b.php']);
            file_put_contents(
                $this->root . '/psalm.xml',
                '<?xml version="1.0"?>
                <psalm xmlns="https://getpsalm.org/schema/config">
                    <projectFiles>
                        <directory name="app" />
                    </projectFiles>
                    <issueHandlers>
                        <UnusedVariable>
                            <errorLevel type="suppress">
                                <directory name="**/vendor" />
                                <file name="pk/**/vendor/**/*.php" />
                            </errorLevel>
                        </UnusedVariable>
                    </issueHandlers>
                </psalm>',
            );

            $process = proc_open(
                [
                    PHP_BINARY,
                    dirname(__DIR__, 2) . '/psalm',
                    '--no-cache',
                    '--threads=1',
                    '--no-progress',
                    '--output-format=json',
                    '--config=' . $this->root . '/psalm.xml',
                ],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            self::assertIsResource($process);
            $output = (string) stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            /** @var list<array{type: string, message: string}> $issues */
            $issues = json_decode($output, true);
            $messages = [];

            foreach ($issues as $issue) {
                if ($issue['type'] === 'UnusedIssueHandlerSuppression') {
                    $messages[] = $issue['message'];
                }
            }

            self::assertCount(1, $messages);
            self::assertSame(
                1,
                preg_match('/^Suppressed issue type "UnusedVariable" for (.*) was not thrown\.$/', $messages[0], $matches),
            );

            $listed = explode(', ', str_replace(DIRECTORY_SEPARATOR, '/', $matches[1]));
            sort($listed);

            // the order of the files and directories depends on the file system
            self::assertSame(
                [
                    '/app/vendor/',
                    '/packages/one/lib/inner/vendor/',
                    '/packages/one/vendor/',
                    '/packages/two/vendor/',
                    '/pk/vendor/',
                    '/pk/vendor/r.php',
                    '/pk/vendor/x/vendor/q.php',
                    '/vendor/',
                ],
                $listed,
            );
        } finally {
            $this->removeFixture();
        }
    }

    #[RequiresOperatingSystem('^(?!WIN)')]
    public function testRecursiveEntryDoesNotSearchAMatchedDirectoryThroughASymlink(): void
    {
        $outside = $this->root . '-outside';
        mkdir($outside . '/pkg/vendor', 0777, true);
        file_put_contents($outside . '/pkg/vendor/Q.php', '<?php');

        try {
            if (!@symlink($outside . '/pkg', $this->root . '/vendor/acme/pkg')) {
                self::markTestSkipped('symlinks cannot be created');
            }

            $directories = FileFilter::loadFromArray(
                ['directory' => [['name' => '**/vendor']]],
                $this->root,
                false,
            )->getDirectories();

            // vendor/acme/pkg lies inside the match vendor/, so the vendor directory it links to is not searched for
            self::assertContains($this->root . '/vendor/', $directories);
            self::assertNotContains($outside . '/pkg/vendor/', $directories);

            $files = FileFilter::loadFromArray(
                ['file' => [['name' => '**/vendor/**/*.php']]],
                $this->root,
                false,
            )->getFiles();

            // the files below the link are still found, through the match vendor/
            self::assertContains($outside . '/pkg/vendor/Q.php', $files);
        } finally {
            $this->removeFixture();
        }
    }

    private function removeFixture(): void
    {
        $this->removeTree($this->root);

        if (is_dir($this->root . '-outside')) {
            $this->removeTree($this->root . '-outside');
        }
    }

    private function removeTree(string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }

    /**
     * @return list<string>
     * @psalm-capabilities read-props
     */
    private function relativeDirectories(FileFilter $filter): array
    {
        $directories = array_map(
            fn(string $directory): string => str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                substr($directory, strlen($this->root) + 1),
            ),
            $filter->getDirectories(),
        );
        sort($directories);

        return $directories;
    }

    /**
     * @return list<string>
     */
    private function allowedFiles(FileFilter $filter): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->isFile() && $filter->allows($file->getPathname())) {
                $files[] = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($this->root) + 1));
            }
        }

        sort($files);

        return $files;
    }
}
