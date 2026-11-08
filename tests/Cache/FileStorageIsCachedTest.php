<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use Psalm\Config;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\RuntimeCaches;
use Psalm\Storage\FileStorage;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function file_put_contents;
use function glob;
use function hash;
use function pack;
use function strlen;
use function strtolower;
use function substr;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

/**
 * Whether a file storage is ready for a scan: in memory, or with a cache entry that is complete.
 */
final class FileStorageIsCachedTest extends TestCase
{
    private const PATH = '/src/A.php';

    private static string $cache_root;

    private string $cache_directory;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];

        self::$cache_root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-is-cached-test-' . uniqid('', true);
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

    public function testFileWithACacheEntry(): void
    {
        $this->cacheStorage(self::PATH, 'contents');

        self::assertTrue($this->newProvider()->isCached(self::PATH, hash('xxh128', 'contents')));
    }

    public function testFileWithoutACacheEntry(): void
    {
        $this->cacheStorage('/src/B.php', 'contents');

        self::assertFalse($this->newProvider()->isCached(self::PATH, hash('xxh128', 'contents')));
    }

    public function testFileWithoutAnyCache(): void
    {
        self::assertFalse((new FileStorageProvider())->isCached(self::PATH, hash('xxh128', 'contents')));
    }

    /**
     * The scan of a file whose contents changed since its storage was cached makes a new storage.
     */
    public function testEntryOfOtherContentsIsNotCached(): void
    {
        $this->cacheStorage(self::PATH, 'old contents');

        self::assertFalse($this->newProvider()->isCached(self::PATH, hash('xxh128', 'new contents')));
        self::assertTrue($this->newProvider()->isCached(self::PATH, hash('xxh128', 'old contents')));
    }

    /**
     * The cache doesn't tell the spellings of a path apart.
     */
    public function testEntryOfAnotherSpellingOfThePath(): void
    {
        $this->cacheStorage('/src/a.php', 'contents');

        self::assertTrue($this->newProvider()->isCached('/SRC/A.php', hash('xxh128', 'contents')));
    }

    /** Only the hash and the key of the record are read */
    public function testEntryWithARecordOfTheKey(): void
    {
        $this->cacheStorage(self::PATH, 'contents');
        file_put_contents($this->entryPath(), self::record(hash('xxh128', 'contents'), strtolower(self::PATH)));

        self::assertTrue($this->newProvider()->isCached(self::PATH, hash('xxh128', 'contents')));
    }

    /**
     * A record that is cut short, declares more than the file holds, or is of another key: the file is scanned
     * without a storage.
     */
    #[DataProvider('provideUnusableRecords')]
    public function testEntryWithAnUnusableRecord(string $record): void
    {
        $this->cacheStorage(self::PATH, 'contents');
        file_put_contents($this->entryPath(), $record);

        self::assertFalse($this->newProvider()->isCached(self::PATH, hash('xxh128', 'contents')));
    }

    /**
     * @return iterable<string, list{string}>
     * @psalm-pure
     */
    public static function provideUnusableRecords(): iterable
    {
        $hash = hash('xxh128', 'contents');
        $key = strtolower(self::PATH);
        $record = self::record($hash, $key);

        yield 'empty' => [''];
        yield 'prefix cut short' => [substr($record, 0, 20)];
        yield 'hash and key cut short' => [substr($record, 0, 28 + 8)];
        yield 'declared hash length overruns the file' => [
            pack('VVV', 4096, strlen($key), 0) . hash('xxh128', '', true) . $hash . $key,
        ];
        yield 'record of another key' => [self::record($hash, '/src/b.php')];
    }

    public function testFileStorageInMemory(): void
    {
        $provider = new FileStorageProvider();
        $provider->create(self::PATH);

        self::assertTrue($provider->isCached(self::PATH, hash('xxh128', 'contents')));
        self::assertTrue($provider->isCached('/SRC/a.php', hash('xxh128', 'contents')));
        self::assertFalse($provider->isCached('/src/b.php', hash('xxh128', 'contents')));
    }

    public function testFileStorageInMemoryWithACache(): void
    {
        $provider = $this->newProvider();
        $provider->create(self::PATH);

        self::assertTrue($provider->isCached(self::PATH, hash('xxh128', 'contents')));
    }

    private function newConfig(): TestConfig
    {
        $config = new TestConfig();
        $config->cache_directory = $this->cache_directory;

        return $config;
    }

    private function newProvider(): FileStorageProvider
    {
        return new FileStorageProvider(new FileStorageCacheProvider($this->newConfig(), ''));
    }

    private function cacheStorage(string $file_path, string $file_contents): void
    {
        (new FileStorageCacheProvider($this->newConfig(), ''))
            ->writeToCache(new FileStorage($file_path), $file_contents);
    }

    /**
     * A record as Cache writes it, with an empty payload
     *
     * @psalm-pure
     */
    private static function record(string $hash, string $key): string
    {
        return pack('VVV', strlen($hash), strlen($key), 0) . hash('xxh128', '', true) . $hash . $key;
    }

    /** The entry of PATH: <cache directory>/file_cache/<cache hash>/<hash of the key> */
    private function entryPath(): string
    {
        $paths = glob($this->cache_directory . '/*/*/' . hash('xxh128', strtolower(self::PATH)));

        self::assertNotFalse($paths);
        self::assertCount(1, $paths);

        return $paths[0];
    }
}
