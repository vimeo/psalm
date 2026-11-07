<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Amp\Serialization\SerializationException;
use Override;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\ZstdSerializer;
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
use function random_bytes;
use function rename;
use function restore_error_handler;
use function set_error_handler;
use function sort;
use function strlen;
use function substr;
use function substr_replace;
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

    /** @param 'zstd'|'lz4'|'deflate'|'off'|null $compressor null for the one the config picks */
    private function newConfig(bool $array_cache = true, ?string $compressor = null): Config
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
        if ($compressor !== null) {
            $config->compressor = $compressor;
        }

        return $config;
    }

    /** @param 'zstd'|'lz4'|'deflate'|'off'|null $compressor null for the one the config picks */
    private function newCache(bool $array_cache = true, ?string $compressor = null): Cache
    {
        return new Cache($this->newConfig($array_cache, $compressor), 'test_subdir');
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
                // the record of 'a' is <28 bytes of lengths and checksum><hash a><a><payload>
                'payload length overstated' => substr_replace($data, pack('V', 0xFFFFFFFF), 8, 4),
                'key changed' => substr_replace($data, 'b', 28 + strlen('hash a'), 1),
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

    /** The index says which hash the record has, but the record is what counts */
    public function testConsolidatedRecordWithAnotherHashThanTheIndexIsAMiss(): void
    {
        $cache = $this->newCache();
        $cache->saveItem('a', ['item a'], 'hash a');
        $cache->consolidate();

        $path = $this->dataFile();
        $data = file_get_contents($path);
        $this->assertNotFalse($data);
        file_put_contents($path, substr_replace($data, 'x', 28, 1));

        $reader = $this->newCache();
        $this->assertNull($reader->getItem('a', 'hash a'));

        file_put_contents($path, $data);
        $this->assertSame(['item a'], $this->newCache()->getItem('a', 'hash a'));
    }

    /**
     * Every record carries the same bytes, which is what a dictionary is made of, and the bytes do not
     * compress on their own.
     *
     * @return array<string, array{string, string}> key => [item, hash]
     */
    private static function similarItems(int $from, int $to): array
    {
        $shared = random_bytes(3000);
        $items = [];
        for ($i = $from; $i < $to; $i++) {
            $items["key $i"] = [$shared . "item $i", "hash $i"];
        }

        return $items;
    }

    /**
     * @return array{string, array<string, array{string, int, int}>, array{string, string, int}|null}
     *     data file, entries, dictionary with its hash and the number of records it was made for
     */
    private function readIndex(): array
    {
        $serializer = $this->newConfig(true, 'zstd')->getCacheSerializer();
        $index = file_get_contents($this->directory() . 'consolidated');
        $this->assertNotFalse($index);

        /** @var array{string, array<string, array{string, int, int}>, array{string, string, int}|null} */
        return $serializer->unserialize($index);
    }

    /** The compressed payload of the record of the key in the consolidated data file */
    private function payloadOf(string $key): string
    {
        [$data_file, $entries] = $this->readIndex();
        [$hash, $offset, $length] = $entries[$key];
        $header_length = 28 + strlen($hash) + strlen($key);
        $data = file_get_contents($this->directory() . $data_file);
        $this->assertNotFalse($data);

        return substr($data, $offset + $header_length, $length - $header_length);
    }

    /** @param iterable<string, array{string, string}> $items */
    private function saveAll(Cache $cache, iterable $items): void
    {
        foreach ($items as $key => [$item, $hash]) {
            $cache->saveItem($key, [$item], $hash);
        }
    }

    #[RequiresPhpExtension('zstd', '>=0.15.0')]
    public function testZstdConsolidatedEntriesAreReadBackOneByOne(): void
    {
        $items = self::similarItems(0, 20);
        $cache = $this->newCache(true, 'zstd');
        foreach ($items as $key => [$item, $hash]) {
            $cache->saveItem($key, [$item], $hash);
        }
        $cache->consolidate();
        $this->assertCount(3, $this->fileNames());

        foreach ([true, false] as $array_cache) {
            $reader = $this->newCache($array_cache, 'zstd');
            foreach ($items as $key => [$item, $hash]) {
                $this->assertSame([$item], $reader->getItem($key, $hash));
                $this->assertSame([$item], $reader->getItem($key, null));
            }
        }
    }

    #[RequiresPhpExtension('zstd', '>=0.15.0')]
    public function testZstdConsolidatedRecordCannotBeDecompressedWithoutTheDictionary(): void
    {
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, self::similarItems(0, 20));
        $cache->consolidate();

        $dictionary = $this->readIndex()[2];
        $this->assertNotNull($dictionary);
        $payload = $this->payloadOf('key 5');

        $serializer = $this->newConfig(true, 'zstd')->getCacheSerializer();
        $this->assertInstanceOf(ZstdSerializer::class, $serializer);
        $this->assertNotSame('', $serializer->withDictionary($dictionary[0])->uncompress($payload));

        $this->expectException(SerializationException::class);
        $this->withoutWarnings(static fn(): string => $serializer->uncompress($payload));
    }

    #[RequiresPhpExtension('zstd', '>=0.15.0')]
    public function testZstdConsolidatingAgainReencodesCarriedEntriesAndLooseEntriesWin(): void
    {
        $first = self::similarItems(0, 5);
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, $first);
        $cache->consolidate();
        $first_dictionary = $this->readIndex()[2];

        // 5 records carried and 31 loose ones are more than 4 times the 5 the dictionary was made for
        $second = self::similarItems(10, 40);
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, $second);
        $cache->saveItem('key 3', ['replaced'], 'new hash');
        $cache->consolidate();

        $this->assertNotSame($first_dictionary, $this->readIndex()[2]);
        $this->assertCount(3, $this->fileNames());

        $cache = $this->newCache(true, 'zstd');
        $cache->saveItem('key 4', ['newer'], 'newest hash');

        unset($first['key 3']);
        foreach ([true, false] as $array_cache) {
            $reader = $this->newCache($array_cache, 'zstd');
            foreach ([...$first, ...$second] as $key => [$item, $hash]) {
                $this->assertSame([$item], $reader->getItem($key, $hash));
            }
            $this->assertSame(['replaced'], $reader->getItem('key 3', 'new hash'));
            $this->assertSame(['newer'], $reader->getItem('key 4', 'newest hash'));
            $this->assertSame(['newer'], $reader->getItem('key 4', null));
        }
    }

    #[RequiresPhpExtension('zstd', '>=0.15.0')]
    public function testZstdDictionaryIsReusedWhileTheRecordsStayFewerThanFourTimesAsMany(): void
    {
        $first = self::similarItems(0, 10);
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, $first);
        $cache->consolidate();
        $dictionary = $this->readIndex()[2];

        // 10 carried and 29 loose records: 39 are fewer than 40
        $second = self::similarItems(10, 39);
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, $second);
        $cache->consolidate();

        $this->assertSame($dictionary, $this->readIndex()[2]);

        $reader = $this->newCache(false, 'zstd');
        foreach ([...$first, ...$second] as $key => [$item, $hash]) {
            $this->assertSame([$item], $reader->getItem($key, $hash));
        }
        // consolidate() waits for the readers of the cache to be gone
        unset($reader);

        // 39 carried and 1 loose record are 4 times the 10 the dictionary was made for
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, self::similarItems(100, 101));
        $cache->consolidate();
        $this->assertNotSame($dictionary, $this->readIndex()[2]);
        $this->assertSame([$first['key 5'][0]], $this->newCache(false, 'zstd')->getItem('key 5', 'hash 5'));
    }

    /** A record that replaces a consolidated one is not one more record for the dictionary */
    #[RequiresPhpExtension('zstd', '>=0.15.0')]
    public function testZstdReplacedRecordsAreNotCountedTowardsRebuildingTheDictionary(): void
    {
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, self::similarItems(0, 2));
        $cache->consolidate();
        $dictionary = $this->readIndex()[2];
        $this->assertNotNull($dictionary);
        $this->assertSame(2, $dictionary[2]);

        // Both are replaced and 5 are added: 7 records, fewer than 4 times 2
        $cache = $this->newCache(true, 'zstd');
        foreach (['key 0', 'key 1'] as $key) {
            $cache->saveItem($key, ['replaced'], 'new hash');
        }
        $this->saveAll($cache, self::similarItems(2, 7));
        $cache->consolidate();
        $this->assertSame($dictionary, $this->readIndex()[2]);

        // 7 carried and 1 added: 8 records are 4 times 2
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, self::similarItems(7, 8));
        $cache->consolidate();
        $rebuilt = $this->readIndex()[2];
        $this->assertNotNull($rebuilt);
        $this->assertNotSame($dictionary[0], $rebuilt[0]);
        $this->assertSame(8, $rebuilt[2]);
        $this->assertSame(['replaced'], $this->newCache(false, 'zstd')->getItem('key 1', 'new hash'));
    }

    #[RequiresPhpExtension('zstd', '>=0.15.0')]
    public function testZstdConsolidatedRecordsWithADamagedDictionaryAreAbsent(): void
    {
        $cache = $this->newCache(true, 'zstd');
        $this->saveAll($cache, self::similarItems(0, 5));
        $cache->consolidate();
        unset($cache);

        [$data_file, $entries, $dictionary] = $this->readIndex();
        $this->assertNotNull($dictionary);
        $dictionary[0][0] = $dictionary[0][0] === 'a' ? 'b' : 'a';
        $serializer = $this->newConfig(true, 'zstd')->getCacheSerializer();
        file_put_contents($this->directory() . 'consolidated', $serializer->serialize([$data_file, $entries, $dictionary]));

        $reader = $this->newCache(true, 'zstd');
        $this->assertNull($reader->getItem('key 1', 'hash 1'));
        $this->assertNull($reader->getItem('key 1', null));
        $this->assertNull($reader->getHash('key 1'));
        $this->assertFalse($reader->hasItem('key 1'));

        $reader->saveItem('new', ['new'], 'hash new');
        $reader->consolidate();

        $this->assertCount(3, $this->fileNames(), 'the damaged data file is gone');
        $this->assertSame(['new'], $this->newCache()->getItem('new', 'hash new'));
        $this->assertNull($this->newCache()->getItem('key 1', 'hash 1'));
    }

    /** PHP turns the key "123" into an int in the arrays that hold the keys of the records */
    #[RequiresPhpExtension('zstd', '>=0.15.0')]
    public function testZstdConsolidatingTwiceKeepsAnEntryWithANumericKey(): void
    {
        foreach (['123', 'other'] as $key) {
            $cache = $this->newCache(true, 'zstd');
            $cache->saveItem($key, ["item $key"], "hash $key");
            $cache->consolidate();
        }

        $reader = $this->newCache(true, 'zstd');
        $this->assertSame(['item 123'], $reader->getItem('123', 'hash 123'));
        $this->assertSame(['item other'], $reader->getItem('other', 'hash other'));
    }
}
