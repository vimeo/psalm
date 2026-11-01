<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

use function array_map;
use function file_put_contents;
use function glob;
use function implode;
use function is_dir;
use function mkdir;
use function preg_match_all;
use function range;
use function realpath;
use function rmdir;
use function sprintf;
use function str_contains;
use function strpos;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function var_export;

use const DIRECTORY_SEPARATOR;

/**
 * The files an autoloader includes are scanned by several processes (see ScannerParallelTest for the cases):
 * this runs it as a user does, to check that the scan of them is given the threads.
 */
#[RequiresPhpExtension('pcntl')]
#[RequiresPhpExtension('posix')]
final class ParallelAutoloadScanTest extends TestCase
{
    use PsalmRunnerTrait;

    /** more files than a scan forks for */
    private const FILES = 70;

    private string $dir;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ParallelAutoloadScanTest_');
        mkdir($dir);
        // a file reached through a symlink is scanned a second time, by its real path
        $this->dir = (string) realpath($dir);
    }

    #[Override]
    protected function tearDown(): void
    {
        foreach (['src', 'libs', 'vendor'] as $sub) {
            $files = glob($this->dir . DIRECTORY_SEPARATOR . $sub . DIRECTORY_SEPARATOR . '*');

            foreach ($files === false ? [] : $files as $file) {
                unlink($file);
            }

            if (is_dir($this->dir . DIRECTORY_SEPARATOR . $sub)) {
                rmdir($this->dir . DIRECTORY_SEPARATOR . $sub);
            }
        }

        (new Filesystem())->remove($this->dir . '/cache');

        unlink($this->dir . DIRECTORY_SEPARATOR . 'psalm.xml');
        rmdir($this->dir);

        parent::tearDown();
    }

    /**
     * @return iterable<string, array{array<int, string>, non-empty-string, bool}>
     * @psalm-pure
     */
    public static function provideAutoloadFiles(): iterable
    {
        yield 'the first declaration of a function counts' => [
            [
                1 => '<?php function dup(): int { return 1; }',
                self::FILES => '<?php if (!function_exists("dup")) { function dup(): string { return ""; } }',
            ],
            '$dup = dup();',
            false,
        ];

        // every file but the first one looks BASE up, so a process other than the one that scans the first file does
        $derived = [1 => '<?php define("BASE", (/** @var non-empty-string */ "abc"));'];
        for ($i = 2; $i <= self::FILES; $i++) {
            $derived[$i] = sprintf('<?php define("DERIVED_%d", BASE);', $i);
        }

        yield 'a constant defined from the type of a constant of another file' => [
            $derived,
            '$base = BASE; $derived = DERIVED_2; $derived_last = DERIVED_70;',
            true,
        ];
    }

    /**
     * @param array<int, string> $files the contents of the autoloaded files, by position; the others are fillers
     * @param non-empty-string $code
     * @param bool $needs_serial_scan whether the scan has to be redone in one process
     */
    #[DataProvider('provideAutoloadFiles')]
    public function testAutoloadFilesAreRegisteredInOrder(array $files, string $code, bool $needs_serial_scan): void
    {
        $this->writeProject($files, $code);

        $serial = $this->runPsalm(['--no-cache', '--threads=1'], $this->dir, true);
        $parallel = $this->runPsalm(['--no-cache', '--threads=4', '--debug'], $this->dir, true);

        $expected = self::getTraces($serial['STDOUT']);
        $this->assertNotSame([], $expected);
        $this->assertSame($expected, self::getTraces($parallel['STDOUT']));

        $parallel = $parallel['STDERR'];

        // that the autoloaded files were scanned by several processes is what the test is about
        $start = (int) strpos($parallel, 'Registering autoloaded files');
        $end = (int) strpos($parallel, 'Finished registering autoloaded files', $start);
        $autoload_scan = substr($parallel, $start, $end - $start);
        $this->assertStringContainsString('Forking process for scanning', $autoload_scan);

        $this->assertSame($needs_serial_scan, str_contains($autoload_scan, 'Scanning the files again'));
    }

    /**
     * A process that scanned a file against a constant another process redefines must not leave the file in the
     * cache for the scan in one process that follows.
     */
    public function testRescanDoesNotUseTheCacheOfTheProcesses(): void
    {
        $this->writeProject(
            [
                1 => '<?php if (!defined("X")) { define("X", (/** @var non-empty-string */ "a")); }',
                // slow, so that the process that scans it is not given the file that reads X
                2 => '<?php ' . implode(
                    ' ',
                    array_map(
                        static fn(int $i): string => sprintf('function slow%d(): int { return %d; }', $i, $i),
                        range(1, 3000),
                    ),
                ) . ' if (!defined("X")) { define("X", (/** @var int */ 1)); }',
                4 => '<?php define("DERIVED", X);',
            ],
            '$derived = DERIVED;',
        );

        $serial = $this->runPsalm(['--no-cache', '--threads=1'], $this->dir, true);

        $expected = self::getTraces($serial['STDOUT']);
        $this->assertSame(['$derived: int'], $expected);

        // the first run writes the cache, the second reads it
        $cold = $this->runPsalm(['--threads=4', '--debug'], $this->dir, true);
        $this->assertStringContainsString('Scanning the files again', $cold['STDERR']);
        $this->assertSame($expected, self::getTraces($cold['STDOUT']));

        $warm = $this->runPsalm(['--threads=4'], $this->dir, true);
        $this->assertSame($expected, self::getTraces($warm['STDOUT']));
    }

    /**
     * @param array<int, string> $files
     */
    private function writeProject(array $files, string $code): void
    {
        mkdir($this->dir . '/src');
        mkdir($this->dir . '/libs');
        mkdir($this->dir . '/vendor');

        $autoload = "<?php\n";

        for ($i = 1; $i <= self::FILES; $i++) {
            $path = sprintf('%s/libs/f%02d.php', $this->dir, $i);
            file_put_contents($path, $files[$i] ?? sprintf('<?php function filler%d(): int { return %d; }', $i, $i));
            $autoload .= sprintf("require_once %s;\n", var_export($path, true));
        }

        file_put_contents($this->dir . '/vendor/autoload.php', $autoload);

        $traced = '';
        foreach (['dup', 'base', 'derived', 'derived_last'] as $name) {
            if (str_contains($code, '$' . $name . ' ')) {
                $traced .= ($traced === '' ? '' : ', ') . '$' . $name;
            }
        }

        file_put_contents(
            $this->dir . '/src/a.php',
            "<?php\n" . $code . "\n/** @psalm-trace " . $traced . " */\n",
        );

        file_put_contents(
            $this->dir . '/psalm.xml',
            '<?xml version="1.0"?>
<psalm errorLevel="1" findUnusedCode="false" resolveFromConfigFile="true" autoloader="vendor/autoload.php"'
            . ' cacheDirectory="cache" xmlns="https://getpsalm.org/schema/config">
    <projectFiles><directory name="src"/></projectFiles>
</psalm>
',
        );
    }

    /**
     * @return list<string>
     * @psalm-pure
     */
    private static function getTraces(string $output): array
    {
        preg_match_all('/Trace - \S+ - (\$\w+: [^\n]*?) \(see/', $output, $matches);

        return $matches[1];
    }
}
