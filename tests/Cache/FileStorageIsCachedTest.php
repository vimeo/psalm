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

    /**
     * A writer that died midway leaves a header that is empty or shorter than it declares: the file is scanned
     * without a storage.
     */
    #[DataProvider('provideUnusableHeaders')]
    public function testEntryWithAnUnusableHeader(string $header): void
    {
        $this->cacheStorage(self::PATH, 'contents');
        file_put_contents($this->headerPath(), $header);

        self::assertFalse($this->newProvider()->isCached(self::PATH, hash('xxh128', 'contents')));
    }

    /**
     * @return iterable<string, list{string}>
     * @psalm-pure
     */
    public static function provideUnusableHeaders(): iterable
    {
        yield 'empty' => [''];
        yield 'length prefix only' => [pack('V', 8)];
        yield 'declared length overruns the header' => [pack('V', 4096) . 'the hash' . strtolower(self::PATH)];
        yield 'key cut short' => [pack('V', 8) . 'the hash' . substr(strtolower(self::PATH), 0, 4)];
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

    /** The header file of PATH: <cache directory>/file_cache/<config hash>/<hash of the key>.hash */
    private function headerPath(): string
    {
        $paths = glob($this->cache_directory . '/*/*/' . hash('xxh128', strtolower(self::PATH)) . '.hash');

        self::assertNotFalse($paths);
        self::assertCount(1, $paths);

        return $paths[0];
    }
}
