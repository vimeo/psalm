<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\IssueBuffer;
use Psalm\Tests\Internal\Provider\ProjectCacheProvider;
use Psalm\Tests\TestCase;

use function basename;
use function microtime;

use const DIRECTORY_SEPARATOR;

/**
 * A file scanned from the cache must find its class-likes as a fresh scan of it does.
 */
final class ScanFromCacheTest extends TestCase
{
    private const POLYFILL = <<<'PHP'
        <?php
        class JsonException extends Exception
        {
            public const POLYFILL = 1;
        }
        PHP;

    private const DECLARES_FOO = <<<'PHP'
        <?php
        class Foo {}
        PHP;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // hack to isolate Psalm from PHPUnit cli arguments
        global $argv;
        $argv = [];
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        RuntimeCaches::clearAll();
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

    /**
     * A project file polyfilling a native class, scanned from the cache after the autoload files made Psalm reflect
     * the native class: the storage in memory is the reflection of the native class, not the file's.
     */
    public function testPolyfillOfNativeClassNamedByAutoloadFile(): void
    {
        $polyfill = $this->srcPath('JsonException.php');

        [$config, $file_provider, $providers] = $this->createProviders();
        $file_provider->registerFile($polyfill, self::POLYFILL);

        // no autoload file names the native class yet, so nothing is reflected and the polyfill has no duplicate
        self::assertSame([], $this->analyze($config, $providers));

        $config_with_autoloader = $this->createConfig('autoloader_names_native_class.php');
        $fresh_providers = $this->createFreshProviders($config_with_autoloader, $file_provider);
        $fresh = $this->analyze($config_with_autoloader, $fresh_providers);

        self::assertSame(['JsonException.php:DuplicateClass'], $fresh);
        self::assertSame($fresh, $this->analyze($config_with_autoloader, $providers));
    }

    /**
     * The class was declared by one file only when that file's storage was cached; another file declares it now.
     * The scan order of a fresh scan decides which declaration is the duplicate.
     */
    public function testClassDeclaredByAnotherFileSinceTheStorageWasCached(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $file_provider->registerFile($this->srcPath('B.php'), self::DECLARES_FOO);
        self::assertSame([], $this->analyze($config, $providers));

        $file_provider->registerFile($this->srcPath('A.php'), self::DECLARES_FOO);

        $fresh = $this->analyze($config, $this->createFreshProviders($config, $file_provider));

        self::assertSame(['B.php:DuplicateClass'], $fresh);
        self::assertSame($fresh, $this->analyze($config, $providers));
    }

    /**
     * The file's own storage is still in memory when the file is scanned from the cache again: it is kept, not
     * scanned afresh.
     */
    public function testStorageInMemoryOfTheSameFileIsKept(): void
    {
        [$config, $file_provider, $providers] = $this->createProviders();

        $file_provider->registerFile($this->srcPath('B.php'), self::DECLARES_FOO);
        self::assertSame([], $this->analyze($config, $providers));

        $storage = ClassLikeStorageProvider::getAll()['foo'];

        self::assertSame([], $this->analyzeKeepingStorages($config, $providers));
        self::assertSame($storage, ClassLikeStorageProvider::getAll()['foo']);
    }

    private function srcPath(string $name): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
            . $name;
    }

    private function createConfig(?string $autoloader = null): Config
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            '<?xml version="1.0"?>'
            . '<psalm' . ($autoloader !== null ? ' autoloader="' . $autoloader . '"' : '') . '>'
            . '<projectFiles><directory name="src" /></projectFiles>'
            . '<issueHandlers>'
            . '<MissingClassConstType errorLevel="suppress" />'
            . '<ClassMustBeFinal errorLevel="suppress" />'
            . '</issueHandlers>'
            . '</psalm>',
        );
        $config->setIncludeCollector(new IncludeCollector());

        return $config;
    }

    /**
     * @return array{Config, FakeFileProvider, Providers}
     */
    private function createProviders(): array
    {
        $config = $this->createConfig();
        $file_provider = new FakeFileProvider();

        return [$config, $file_provider, $this->createFreshProviders($config, $file_provider)];
    }

    private function createFreshProviders(Config $config, FakeFileProvider $file_provider): Providers
    {
        return new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );
    }

    /**
     * @return list<string> `file:type` of the issues
     */
    private function analyze(Config $config, Providers $providers): array
    {
        RuntimeCaches::clearAll();

        return $this->analyzeKeepingStorages($config, $providers);
    }

    /**
     * @return list<string> `file:type` of the issues
     */
    private function analyzeKeepingStorages(Config $config, Providers $providers): array
    {
        IssueBuffer::clear();

        $project_analyzer = new ProjectAnalyzer($config, $providers);
        $project_analyzer->check($config->base_dir);
        $project_analyzer->finish(microtime(true), PSALM_VERSION);

        $issues = [];

        foreach (IssueBuffer::getIssuesData() as $file_issues) {
            foreach ($file_issues as $issue) {
                $issues[] = basename($issue->file_path) . ':' . $issue->type;
            }
        }

        return $issues;
    }
}
