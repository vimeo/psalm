<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Internal\RuntimeCaches;

use function restore_error_handler;
use function set_error_handler;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;

final class CacheDirectoryTest extends TestCase
{
    private string $cache_directory;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->cache_directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('psalm-cache-directory-test-', true);
    }

    #[Override]
    protected function tearDown(): void
    {
        RuntimeCaches::clearAll();

        Config::removeCacheDirectory($this->cache_directory);

        parent::tearDown();
    }

    public function testExistingCacheDirectoryDoesNotRaiseAWarning(): void
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

        new Cache($config, 'test_subdir');

        $errors = [];
        set_error_handler(static function (int $code, string $message) use (&$errors): bool {
            $errors[] = $message;

            return true;
        });

        try {
            // The second instance finds the directory created by the first one.
            new Cache($config, 'test_subdir');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $errors);
    }
}
