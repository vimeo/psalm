<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
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
use function define;
use function defined;
use function microtime;

use const DIRECTORY_SEPARATOR;

/**
 * A global constant of a file scanned from the cache must have the type a fresh scan of the file gives it.
 */
final class GlobalConstantsFromCacheTest extends TestCase
{
    /** Defined in this process, as a constant the project's autoloader defines is in Psalm's process. */
    private const RUNTIME_CONSTANT = 'PSALM_TEST_CONSTANT_DEFINED_BY_THE_AUTOLOADER';

    private const NATIVE_CONSTANT_TYPE_UNKNOWN = <<<'PHP'
        <?php
        /** @return int */
        function unknownValue() { return 1; }
        define('E_ALL', unknownValue());
        PHP;

    private const NATIVE_CONSTANT_TYPE_KNOWN = <<<'PHP'
        <?php
        define('E_ALL', 'a');
        PHP;

    private const RETURNS_NATIVE_CONSTANT = <<<'PHP'
        <?php
        function nativeConstant(): int { return E_ALL; }
        PHP;

    private const RUNTIME_CONSTANT_TYPE_UNKNOWN = <<<'PHP'
        <?php
        /** @return string */
        function unknownPath() { return '/app'; }
        define('PSALM_TEST_CONSTANT_DEFINED_BY_THE_AUTOLOADER', unknownPath());
        PHP;

    private const RETURNS_RUNTIME_CONSTANT = <<<'PHP'
        <?php
        function root(): string { return PSALM_TEST_CONSTANT_DEFINED_BY_THE_AUTOLOADER; }
        PHP;

    private const USER_CONSTANT_TYPE_UNKNOWN = <<<'PHP'
        <?php
        /** @return int */
        function unknownValue() { return 1; }
        define('USER_CONSTANT', unknownValue());
        PHP;

    private const DEFINES_USER_CONSTANT = <<<'PHP'
        <?php
        define('USER_CONSTANT', 'a');
        PHP;

    private const RETURNS_USER_CONSTANT = <<<'PHP'
        <?php
        function userConstant(): int { return USER_CONSTANT; }
        PHP;

    private const DEFINES_ENUM_CONSTANT = <<<'PHP'
        <?php
        const FAVOURITE = Suit::Hearts;
        PHP;

    private const DECLARES_ENUM = <<<'PHP'
        <?php
        enum Suit { case Hearts; }
        PHP;

    private const RETURNS_ENUM_CONSTANT = <<<'PHP'
        <?php
        function favourite(): int { return FAVOURITE; }
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
     * @psalm-capabilities read-props|write-this-props|write-refs|read-globals|write-globals
     */
    #[Override]
    public function tearDown(): void
    {
        RuntimeCaches::clearAll();

        parent::tearDown();
    }

    /**
     * The mixed type of a polyfill of a native constant doesn't replace the native one.
     */
    public function testNativeConstantOfUnknownTypeKeepsItsNativeType(): void
    {
        $this->assertWarmScanMatchesColdScan(
            ['a.php' => self::NATIVE_CONSTANT_TYPE_UNKNOWN, 'b.php' => self::RETURNS_NATIVE_CONSTANT],
            [],
        );
    }

    /**
     * A constant that the project's autoloader defines while Psalm loads it, like DRUPAL_ROOT.
     */
    public function testConstantDefinedAtRuntimeOfUnknownTypeKeepsItsRuntimeType(): void
    {
        if (!defined(self::RUNTIME_CONSTANT)) {
            define(self::RUNTIME_CONSTANT, '/app');
        }

        $this->assertWarmScanMatchesColdScan(
            ['a.php' => self::RUNTIME_CONSTANT_TYPE_UNKNOWN, 'b.php' => self::RETURNS_RUNTIME_CONSTANT],
            [],
        );
    }

    public function testNativeConstantOfKnownTypeIsReplaced(): void
    {
        $this->assertWarmScanMatchesColdScan(
            ['a.php' => self::NATIVE_CONSTANT_TYPE_KNOWN, 'b.php' => self::RETURNS_NATIVE_CONSTANT],
            ['b.php:InvalidReturnStatement', 'b.php:InvalidReturnType'],
        );
    }

    public function testConstantOfUnknownTypeIsMixed(): void
    {
        $this->assertWarmScanMatchesColdScan(
            ['a.php' => self::USER_CONSTANT_TYPE_UNKNOWN, 'b.php' => self::RETURNS_USER_CONSTANT],
            ['b.php:MixedReturnStatement'],
        );
    }

    public function testConstantOfKnownType(): void
    {
        $this->assertWarmScanMatchesColdScan(
            ['a.php' => self::DEFINES_USER_CONSTANT, 'b.php' => self::RETURNS_USER_CONSTANT],
            ['b.php:InvalidReturnStatement', 'b.php:InvalidReturnType'],
        );
    }

    /**
     * A constant whose value depends on a class is resolved when it is fetched.
     */
    public function testConstantDependingOnAClass(): void
    {
        $this->assertWarmScanMatchesColdScan(
            ['a.php' => self::DEFINES_ENUM_CONSTANT, 'b.php' => self::RETURNS_ENUM_CONSTANT, 'c.php' => self::DECLARES_ENUM],
            ['b.php:InvalidReturnStatement', 'b.php:InvalidReturnType'],
        );
    }

    /**
     * @param array<string, string> $files the contents by name in `src`
     * @param list<string> $expected `file:type` of the issues
     */
    private function assertWarmScanMatchesColdScan(array $files, array $expected): void
    {
        $config = Config::loadFromXML(
            __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir',
            '<?xml version="1.0"?>'
            . '<psalm allConstantsGlobal="true">'
            . '<projectFiles><directory name="src" /></projectFiles>'
            . '</psalm>',
        );
        $config->setIncludeCollector(new IncludeCollector());

        $file_provider = new FakeFileProvider();
        foreach ($files as $name => $contents) {
            $file_provider->registerFile(
                __DIR__ . DIRECTORY_SEPARATOR . 'test_base_dir' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR
                    . $name,
                $contents,
            );
        }

        $providers = new Providers(
            $file_provider,
            new ParserCacheProvider($config, '', false),
            new FileStorageCacheProvider($config, '', false),
            new ClassLikeStorageCacheProvider($config, '', false),
            new FileReferenceCacheProvider($config, '', false),
            new ProjectCacheProvider(),
        );

        self::assertSame($expected, $this->analyze($config, $providers), 'cold');
        self::assertSame($expected, $this->analyze($config, $providers), 'warm');
    }

    /**
     * @return list<string> `file:type` of the issues
     */
    private function analyze(Config $config, Providers $providers): array
    {
        RuntimeCaches::clearAll();
        IssueBuffer::clear();

        $project_analyzer = new ProjectAnalyzer($config, $providers);
        // what the command line does
        $project_analyzer->getCodebase()->all_constants_global = $config->all_constants_global;
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
