<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\RuntimeCaches;
use Psalm\Internal\VersionUtils;

use function define;
use function defined;
use function file_put_contents;
use function glob;
use function hash;
use function pack;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

final class ParserCacheProviderTest extends TestCase
{
    private const FILE_PATH = 'src/A.php';
    private const CONTENTS = '<?php echo "A";';

    private string $cache_directory;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];

        if (!defined('PHP_PARSER_VERSION')) {
            define('PHP_PARSER_VERSION', VersionUtils::getPhpParserVersion());
        }
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();

        $this->cache_directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . uniqid('psalm-parser-cache-provider-test-', true);
    }

    #[Override]
    protected function tearDown(): void
    {
        RuntimeCaches::clearAll();

        Config::removeCacheDirectory($this->cache_directory);

        parent::tearDown();
    }

    private function newProvider(): ParserCacheProvider
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

        return new ParserCacheProvider($config, '');
    }

    private function saveStatements(): void
    {
        $this->newProvider()->saveStatementsToCache(self::FILE_PATH, self::CONTENTS, [], []);
    }

    public function testFileWithUnchangedContentsIsNotChanged(): void
    {
        $this->saveStatements();

        $this->assertFalse($this->newProvider()->hasChanged(self::FILE_PATH, self::CONTENTS));
    }

    public function testFileWithOtherContentsIsChanged(): void
    {
        $this->saveStatements();

        $this->assertTrue($this->newProvider()->hasChanged(self::FILE_PATH, self::CONTENTS . ' // edited'));
    }

    public function testFileThatWasNeverCachedIsNotChanged(): void
    {
        $this->assertFalse($this->newProvider()->hasChanged(self::FILE_PATH, self::CONTENTS));
    }

    /** The statements are stale or untrustworthy when the contents they were parsed from are unknown. */
    #[DataProvider('provideUnusableHeaders')]
    public function testFileWithoutAUsableStoredHashIsChanged(?string $header): void
    {
        $this->saveStatements();

        $header_path = $this->headerPath();
        if ($header === null) {
            unlink($header_path);
        } else {
            file_put_contents($header_path, $header);
        }

        $this->assertTrue($this->newProvider()->hasChanged(self::FILE_PATH, self::CONTENTS));
    }

    /**
     * @return iterable<string, list{?string}>
     * @psalm-pure
     */
    public static function provideUnusableHeaders(): iterable
    {
        yield 'missing header' => [null];
        yield 'empty header' => [''];
        yield 'shorter than the length prefix' => ["\x01\x02"];
        yield 'declared length overruns the header' => [pack('V', 4096) . self::CONTENTS . self::FILE_PATH];
    }

    private function headerPath(): string
    {
        $files = glob($this->cache_directory . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'php-parser'
            . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . hash('xxh128', self::FILE_PATH) . '.hash');

        $this->assertNotFalse($files);
        $this->assertCount(1, $files);

        return $files[0];
    }
}
