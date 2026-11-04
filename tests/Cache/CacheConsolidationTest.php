<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\TestCase;
use ReflectionProperty;

use function array_keys;
use function array_map;
use function basename;
use function count;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function hash;
use function hash_final;
use function hash_init;
use function hash_update;
use function implode;
use function mkdir;
use function pack;
use function rename;
use function restore_error_handler;
use function set_error_handler;
use function sort;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const GLOB_ONLYDIR;

final class CacheConsolidationTest extends TestCase
{
    /** One directory for the class, a cache directory under it for each test */
    private static string $cache_root;

    private string $cache_directory;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];

        self::$cache_root = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('psalm-cache-consolidation-test-', true);
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        Config::removeCacheDirectory(self::$cache_root);

        parent::tearDownAfterClass();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->cache_directory = self::$cache_root . DIRECTORY_SEPARATOR . uniqid('', true);
    }

    /**
     * @psalm-external-mutation-free
     */
    #[Override]
    public function tearDown(): void
    {
        RuntimeCaches::clearAll();

        parent::tearDown();
    }

    private function newConfig(bool $array_cache = true): Config
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            <<<XML
                <?xml version="1.0"?>
                <psalm cacheDirectory="{$this->cache_directory}">
                    <projectFiles>
                        <directory name="src" />
                    </projectFiles>
                </psalm>
                XML,
        );
        $config->array_cache = $array_cache;

        return $config;
    }

    private function newCache(bool $array_cache = true): Cache
    {
        return new Cache($this->newConfig($array_cache), 'test_subdir');
    }

    /** The directory the cache of newCache() uses, with a trailing separator */
    private function directory(): string
    {
        $directories = glob($this->cache_directory . '/*/test_subdir/*', GLOB_ONLYDIR);
        $this->assertNotFalse($directories);
        $this->assertCount(1, $directories);

        return $directories[0] . DIRECTORY_SEPARATOR;
    }

    private function dataFile(): string
    {
        $files = glob($this->directory() . 'consolidated-*.data');
        $this->assertNotFalse($files);
        $this->assertCount(1, $files);

        return $files[0];
    }

    /** @return list<string> */
    private function fileNames(): array
    {
        $files = glob($this->cache_directory . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'test_subdir'
            . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*');
        $this->assertNotFalse($files);

        $names = array_map(basename(...), $files);
        sort($names);

        return $names;
    }

    public function testConsolidatedEntriesAreReadBackWithoutLooseFiles(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item a'], 'hash a');
        $cache->saveItem('b', ['item b'], 'hash b');
        $cache->saveItem('no hash', ['item c']);
        $cache->consolidate();

        $names = $this->fileNames();
        $this->assertCount(3, $names, 'lock, index and data file: ' . implode(', ', $names));

        foreach ([true, false] as $array_cache) {
            $reader = $this->newCache($array_cache);
            $this->assertSame(['item a'], $reader->getItem('a', 'hash a'));
            $this->assertSame(['item b'], $reader->getItem('b', null));
            $this->assertSame(['item c'], $reader->getItem('no hash'));
            $this->assertSame('hash b', $reader->getHash('b'));
            $this->assertNull($reader->getItem('a', 'another hash'));
            $this->assertNull($reader->getItem('missing', null));
            $this->assertNull($reader->getHash('missing'));
        }
    }

    public function testOpeningAConsolidatedCacheDoesNotLoadItsEntries(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item a'], 'hash a');
        $cache->consolidate();

        $reader = $this->newCache();
        $in_memory = new ReflectionProperty(Cache::class, 'cache');
        $this->assertSame([], $in_memory->getValue($reader));

        $this->assertSame(['item a'], $reader->getItem('a', 'hash a'));
        $in_memory_entries = $in_memory->getValue($reader);
        $this->assertIsArray($in_memory_entries);
        $this->assertSame(['a'], array_keys($in_memory_entries));
    }

    public function testConsolidatingAgainMergesNewerEntriesAndDropsTheOldDataFile(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('kept', ['kept'], 'hash kept');
        $cache->saveItem('replaced', ['old'], 'old hash');
        $cache->consolidate();
        $first_names = $this->fileNames();

        $cache = $this->newCache();
        $cache->saveItem('replaced', ['new'], 'new hash');
        $cache->saveItem('added', ['added'], 'hash added');
        $cache->consolidate();

        $this->assertCount(3, $this->fileNames());
        $this->assertNotSame($first_names, $this->fileNames(), 'the data file is replaced');

        $reader = $this->newCache();
        $this->assertSame(['kept'], $reader->getItem('kept', 'hash kept'));
        $this->assertSame(['new'], $reader->getItem('replaced', 'new hash'));
        $this->assertNull($reader->getItem('replaced', 'old hash'));
        $this->assertSame(['added'], $reader->getItem('added', 'hash added'));
    }

    public function testLooseEntryWrittenAfterConsolidationWins(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['old'], 'old hash');
        $cache->consolidate();

        $writer = $this->newCache();
        $writer->saveItem('a', ['new'], 'new hash');

        $reader = $this->newCache();
        $this->assertSame('new hash', $reader->getHash('a'));
        $this->assertSame(['new'], $reader->getItem('a', null));
        $this->assertSame(['new'], $reader->getItem('a', 'new hash'));
        // The consolidated one is still what it was
        $this->assertSame(['old'], $reader->getItem('a', 'old hash'));
    }

    public function testSavingAnEntryThatTheConsolidatedFileHasWritesNothing(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item'], 'hash');
        $cache->consolidate();
        $names = $this->fileNames();

        $writer = $this->newCache();
        $writer->saveItem('a', ['item'], 'hash');

        $this->assertSame($names, $this->fileNames());
    }

    public function testForgottenEntryIsSavedAgain(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item'], 'hash');
        $cache->consolidate();
        $names = $this->fileNames();

        $writer = $this->newCache();
        $writer->forgetItem('a');
        $writer->saveItem('a', ['item'], 'hash');

        $this->assertCount(count($names) + 1, $this->fileNames());
    }

    public function testHasItemSeesConsolidatedAndLooseEntries(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('consolidated', ['item'], 'hash');
        $cache->consolidate();
        $cache->saveItem('loose', ['item'], 'hash');

        $reader = $this->newCache(false);
        $this->assertTrue($reader->hasItem('consolidated'));
        $this->assertTrue($reader->hasItem('loose'));
        $this->assertFalse($reader->hasItem('missing'));
    }

    /** A file left by a writer that died before renaming it: no cache reads it and consolidation removes it */
    public function testStaleTemporaryFileIsNotConsolidatedAndIsRemoved(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item a'], 'hash a');
        $cache->saveItem('b', ['item b'], 'hash b');

        $directory = $this->directory();
        $temporary = $directory . hash('xxh128', 'b') . '.0123456789abcdef.tmp';
        rename($directory . hash('xxh128', 'b'), $temporary);

        $reader = $this->newCache();
        $this->assertNull($reader->getItem('b', null));
        $this->assertFalse($reader->hasItem('b'));
        unset($reader);

        $cache->consolidate();

        $names = $this->fileNames();
        $this->assertCount(3, $names, 'lock, index and data file: ' . implode(', ', $names));
        $this->assertFileDoesNotExist($temporary);

        $reader = $this->newCache(false);
        $this->assertSame(['item a'], $reader->getItem('a', 'hash a'));
        $this->assertNull($reader->getItem('b', null));
        $this->assertFalse($reader->hasItem('b'));
    }

    /** A record that sits under the file name of another key (a cut-short key hashes elsewhere) is dropped */
    public function testRecordUnderTheFileNameOfAnotherKeyIsNotConsolidated(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('b', ['item b'], 'hash b');
        $directory = $this->directory();
        rename($directory . hash('xxh128', 'b'), $directory . hash('xxh128', 'a'));

        $cache->consolidate();

        $reader = $this->newCache(false);
        $this->assertNull($reader->getItem('a', null));
        $this->assertNull($reader->getItem('b', null));
        $this->assertFalse($reader->hasItem('a'));
        $this->assertFalse($reader->hasItem('b'));
        $this->assertCount(3, $this->fileNames());
    }

    /** A directory of the layout before this one (one file for the payload, one for the header) is not read */
    public function testDirectoryOfAnOldLayoutIsIgnored(): void
    {
        $config = $this->newConfig();
        $serializer = $config->getCacheSerializer();

        // the name of the directory before the layout version became a part of it
        $name = hash_init('xxh128');
        hash_update($name, $config->computeHash());
        hash_update($name, "\0");
        hash_update($name, $serializer->serialize($serializer));
        $old = $config->getCacheDirectory() . DIRECTORY_SEPARATOR . 'test_subdir'
            . DIRECTORY_SEPARATOR . hash_final($name) . DIRECTORY_SEPARATOR;
        mkdir($old, 0777, true);

        file_put_contents($old . hash('xxh128', 'loose'), $serializer->serialize(['loose']));
        file_put_contents($old . hash('xxh128', 'loose') . '.hash', pack('V', 4) . 'hash' . 'loose');
        file_put_contents($old . 'consolidated', $serializer->serialize(['consolidated' => ['hash', ['consolidated']]]));
        $old_names = glob($old . '*');

        $cache = $this->newCache();

        $this->assertNull($cache->getItem('loose', null));
        $this->assertNull($cache->getItem('consolidated', null));
        $this->assertNull($cache->getHash('loose'));
        $this->assertFalse($cache->hasItem('loose'));
        $this->assertFalse($cache->hasItem('consolidated'));

        $cache->saveItem('loose', ['new'], 'new hash');
        $cache->consolidate();

        $this->assertSame($old_names, glob($old . '*'), 'the old directory is left alone');
        $this->assertSame(['new'], $this->newCache()->getItem('loose', 'new hash'));
    }

    /** What forgetItem() drops is the copy of this process, as before the entries were read on demand */
    public function testForgettingAConsolidatedEntryDoesNotChangeTheCacheOnDisk(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item a'], 'hash a');
        $cache->consolidate();

        $writer = $this->newCache();
        $this->assertSame(['item a'], $writer->getItem('a', 'hash a'));
        $writer->forgetItem('a');

        $this->assertFalse($writer->hasItem('a'));
        $this->assertNull($writer->getItem('a', 'hash a'));
        $this->assertNull($writer->getHash('a'));

        $reader = $this->newCache();
        $this->assertTrue($reader->hasItem('a'));
        $this->assertSame(['item a'], $reader->getItem('a', 'hash a'));
    }

    /** The data file can be cut short, changed or gone: every read of it is a miss, without a warning */
    public function testDamagedDataFileIsAMissWithoutWarnings(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item a'], 'hash a');
        $cache->consolidate();

        $path = $this->dataFile();
        $data = file_get_contents($path);
        $this->assertNotFalse($data);
        $flipped = $data;
        $flipped[strlen($flipped) - 1] = $flipped[strlen($flipped) - 1] === 'x' ? 'y' : 'x';

        $errors = [];
        set_error_handler(static function (int $code, string $message) use (&$errors): bool {
            $errors[] = $message;

            return true;
        });

        try {
            foreach ([
                'cut by one byte' => substr($data, 0, -1),
                'cut to the middle of the header' => substr($data, 0, 10),
                'empty' => '',
                'a byte changed' => $flipped,
            ] as $damage => $contents) {
                file_put_contents($path, $contents);
                $reader = $this->newCache();
                $this->assertNull($reader->getItem('a', 'hash a'), $damage);
                $this->assertNull($reader->getItem('a', null), $damage);
            }

            unlink($path);
            $reader = $this->newCache();
            $this->assertNull($reader->getItem('a', 'hash a'), 'removed');
            $this->assertNull($reader->getItem('a', null), 'removed');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors);
    }
}
