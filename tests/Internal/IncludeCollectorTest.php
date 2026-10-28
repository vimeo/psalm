<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Internal\IncludeCollector;

use function array_diff;
use function array_keys;
use function array_map;
use function array_values;
use function dirname;
use function file_put_contents;
use function get_included_files;
use function glob;
use function is_dir;
use function is_file;
use function mkdir;
use function realpath;
use function rmdir;
use function rsort;
use function sort;
use function str_replace;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class IncludeCollectorTest extends TestCase
{
    private string $root;

    /** @var list<string> */
    private array $created_files = [];

    /** @var array<string, true> */
    private array $created_dirs = [];

    #[Override]
    protected function setUp(): void
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('psalm_include_collector_', true);
        mkdir($root);
        $this->root = (string) realpath($root);
        $this->created_dirs[$this->root] = true;
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach ($this->created_files as $file) {
            unlink($file);
        }

        $dirs = array_keys($this->created_dirs);
        rsort($dirs);
        foreach ($dirs as $dir) {
            rmdir($dir);
        }
    }

    public function testComposerAutoloaderMachineryIsFilteredButAutoloadedFilesAreKept(): void
    {
        $expected = [
            $this->createFile('vendor/acme/functions.php'),
            $this->createFile('vendor/composer/pcre.php'),
            $this->createFile('src/helpers.php'),
            // named like Composer's generated files, but outside the Composer directory
            $this->createFile('src/installed.php'),
            $this->createFile('src/autoload.php'),
        ];
        $machinery = [
            $this->createFile('vendor/autoload.php'),
            $this->createFile('vendor/composer/autoload_real.php'),
            $this->createFile('vendor/composer/platform_check.php'),
            $this->createFile('vendor/composer/ClassLoader.php'),
            $this->createFile('vendor/composer/autoload_static.php'),
            $this->createFile('vendor/composer/autoload_classmap.php'),
            $this->createFile('vendor/composer/autoload_namespaces.php'),
            $this->createFile('vendor/composer/autoload_psr4.php'),
            $this->createFile('vendor/composer/autoload_files.php'),
            $this->createFile('vendor/composer/InstalledVersions.php'),
            $this->createFile('vendor/composer/installed.php'),
        ];

        $collector = new IncludeCollector();
        $collector->runAndCollect(static function () use ($machinery, $expected): void {
            self::includeFiles(...$machinery, ...$expected);
        });

        $filtered = $collector->getFilteredIncludedFiles();
        sort($filtered);
        sort($expected);

        self::assertSame($expected, $filtered);
        self::assertCount(16, $collector->getIncludedFiles());
    }

    public function testFilesInAComposerDirectoryAreKeptWithoutAutoloaderBootstrap(): void
    {
        $files = [
            $this->createFile('vendor/composer/installed.php'),
            $this->createFile('vendor/autoload.php'),
        ];

        $collector = new IncludeCollector();
        $collector->runAndCollect(static function () use ($files): void {
            self::includeFiles(...$files);
        });

        self::assertSame($files, $collector->getFilteredIncludedFiles());
    }

    public function testCustomVendorDirectoryIsFiltered(): void
    {
        $machinery = [
            $this->createFile('deps/autoload.php'),
            $this->createFile('deps/composer/autoload_real.php'),
            $this->createFile('deps/composer/autoload_static.php'),
            $this->createFile('deps/composer/installed.php'),
        ];
        $kept = $this->createFile('src/helpers.php');

        $collector = new IncludeCollector();
        $collector->runAndCollect(static function () use ($machinery, $kept): void {
            self::includeFiles(...$machinery, ...[$kept]);
        });

        self::assertSame([$kept], $collector->getFilteredIncludedFiles());
    }

    public function testPsalmSourceFilesAreFiltered(): void
    {
        $psalm_dir = dirname(__DIR__, 2) . '/src/Psalm';

        $issue_files = glob($psalm_dir . '/Issue/*.php');
        self::assertNotFalse($issue_files);

        // an issue class that no other test has autoloaded yet: it only declares a class
        $candidates = array_values(array_diff(
            array_map(self::normalizePath(...), $issue_files),
            get_included_files(),
        ));

        if ($candidates === []) {
            self::markTestSkipped('All Psalm issue classes were already loaded');
        }

        $psalm_file = $candidates[0];

        $kept = $this->createFile('src/helpers.php');

        $collector = new IncludeCollector();
        $collector->runAndCollect(static function () use ($psalm_file, $kept): void {
            self::includeFiles($psalm_file, $kept);
        });

        self::assertContains($psalm_file, $collector->getIncludedFiles());
        self::assertSame([$kept], $collector->getFilteredIncludedFiles());
    }

    private static function includeFiles(string ...$files): void
    {
        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }
    }

    /**
     * get_included_files() reports paths with the native directory separator
     *
     * @psalm-pure
     */
    private static function normalizePath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    private function createFile(string $relative_path): string
    {
        $path = self::normalizePath($this->root . '/' . $relative_path);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            for ($missing = $dir; !is_dir($missing); $missing = dirname($missing)) {
                $this->created_dirs[$missing] = true;
            }

            mkdir($dir, 0777, true);
        }

        file_put_contents($path, "<?php\n");
        $this->created_files[] = $path;

        return $path;
    }
}
