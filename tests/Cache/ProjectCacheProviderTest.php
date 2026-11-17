<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;
use Psalm\Tests\TestConfig;

use function microtime;
use function mkdir;
use function scandir;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

final class ProjectCacheProviderTest extends TestCase
{
    /** One directory for the class, a cache directory under it for each test */
    private static string $cache_root;

    private string $cache_directory;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$cache_root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-project-cache-test-'
            . uniqid('', true);
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

        $this->cache_directory = self::$cache_root . DIRECTORY_SEPARATOR . uniqid('', true);
        mkdir($this->cache_directory, 0o777, true);
    }

    public function testAReadOnlyCacheRecordsNoSuccessfulRun(): void
    {
        $provider = $this->readOnlyProvider();
        $provider->processSuccessfulRun(microtime(true), 'dev');

        self::assertSame(['.', '..'], scandir($this->cache_directory));
        self::assertFalse($provider->canDiffFiles());
    }

    public function testAReadOnlyCacheSavesNoCustomTaints(): void
    {
        $this->readOnlyProvider()->saveCustomTaints(['count' => 1, 'custom' => [], 'map' => []]);

        self::assertSame(['.', '..'], scandir($this->cache_directory));
    }

    private function readOnlyProvider(): ProjectCacheProvider
    {
        $config = new TestConfig();
        $config->cache_directory = $this->cache_directory;
        $config->cache_read_only = true;

        return new ProjectCacheProvider();
    }
}
