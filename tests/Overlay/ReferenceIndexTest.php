<?php

declare(strict_types=1);

namespace Psalm\Tests\Overlay;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Overlay\ReferenceIndex;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

use function array_filter;
use function array_keys;
use function array_map;
use function assert;
use function file_put_contents;
use function filemtime;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;
use function touch;
use function uniqid;
use function unlink;

use const ARRAY_FILTER_USE_BOTH;
use const FILE_APPEND;

final class ReferenceIndexTest extends TestCase
{
    public function testAnEditThatKeepsTheSizeAndTheTimeIsAChange(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'PsalmStamp_');
        assert($file !== false);

        try {
            file_put_contents($file, 'AAAA');
            $stamp = ReferenceIndex::stamp($file);
            $mtime = filemtime($file);
            assert($mtime !== false);

            $this->assertTrue(ReferenceIndex::isUnchanged($file, $stamp));

            file_put_contents($file, 'BBBB');
            touch($file, $mtime);

            $this->assertFalse(ReferenceIndex::isUnchanged($file, $stamp));
        } finally {
            unlink($file);
        }
    }

    /**
     * @param non-empty-string $config_xml
     */
    #[DataProvider('provideInputFiles')]
    public function testAChangedInputFileMakesTheStampsStale(string $config_xml, string $changed_file): void
    {
        $filesystem = new Filesystem();
        $dir = sys_get_temp_dir() . '/PsalmInputFiles_' . uniqid();
        $filesystem->dumpFile($dir . '/stub.phpstub', '<?php class Stubbed {}');
        $filesystem->dumpFile($dir . '/autoload.php', '<?php');
        $filesystem->dumpFile($dir . '/plugin.php', '<?php');
        $filesystem->dumpFile($dir . '/A.php', '<?php');
        $dir = (string) realpath($dir);

        try {
            $config = Config::loadFromXML($dir, $config_xml);
            $stamps = ReferenceIndex::stampFiles(ReferenceIndex::getInputFiles($config));

            file_put_contents($dir . '/' . $changed_file, "\n", FILE_APPEND);

            // Config joins paths with the directory separator, Composer with a slash
            $this->assertSame(
                [Path::join($dir, $changed_file)],
                array_map(Path::canonicalize(...), array_keys(array_filter(
                    $stamps,
                    static fn(?string $stamp, string $file_path): bool
                        => !ReferenceIndex::isUnchanged($file_path, $stamp),
                    ARRAY_FILTER_USE_BOTH,
                ))),
            );
        } finally {
            $filesystem->remove($dir);
        }
    }

    /**
     * @return iterable<string, array{non-empty-string, string}>
     * @psalm-pure
     */
    public static function provideInputFiles(): iterable
    {
        $project_files = '<projectFiles><file name="A.php"/></projectFiles>';

        yield 'stub file' => [
            '<psalm>' . $project_files . '<stubs><file name="stub.phpstub"/></stubs></psalm>',
            'stub.phpstub',
        ];
        yield 'preloaded stub file' => [
            '<psalm>' . $project_files . '<stubs><file name="stub.phpstub" preloadClasses="true"/></stubs></psalm>',
            'stub.phpstub',
        ];
        yield 'autoloader' => ['<psalm autoloader="autoload.php">' . $project_files . '</psalm>', 'autoload.php'];
        yield 'plugin file' => [
            '<psalm>' . $project_files . '<plugins><plugin filename="plugin.php"/></plugins></psalm>',
            'plugin.php',
        ];
        // created after the stamps were taken
        yield 'composer.json' => ['<psalm>' . $project_files . '</psalm>', 'composer.json'];
    }
}
