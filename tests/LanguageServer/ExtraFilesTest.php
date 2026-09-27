<?php

declare(strict_types=1);

namespace Psalm\Tests\LanguageServer;

use Override;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\LanguageServer\ClientConfiguration;
use Psalm\Internal\LanguageServer\LanguageServer;
use Psalm\Internal\LanguageServer\PathMapper;
use Psalm\Internal\LanguageServer\Progress;
use Psalm\Internal\Provider\FakeFileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Tests\Internal\Provider\FakeParserCacheProvider;
use Psalm\Tests\TestCase;

use function dirname;
use function getcwd;
use function realpath;

/**
 * The language server has to make `<extraFiles>` known to the codebase just like the CLI does,
 * otherwise every symbol that is only declared in an extra file is reported as undefined.
 *
 * The fixture config deliberately has no `autoloader`, so `<extraFiles>` is the only way the
 * dependency can be found — this mirrors projects that vendor their dependencies outside of a
 * Composer-autoloadable `vendor/` directory.
 */
final class ExtraFilesTest extends TestCase
{
    private const EXTRA_FILES_CLASS = 'Psalm\Tests\Fixtures\ExtraFiles\Dependency\ExtraFilesClass';
    private const EXTRA_FILES_INTERFACE = 'Psalm\Tests\Fixtures\ExtraFiles\Dependency\ExtraFilesInterface';

    private Codebase $codebase;

    #[Override]
    public function setUp(): void
    {
        RuntimeCaches::clearAll();

        $this->file_provider = new FakeFileProvider();

        $config = Config::loadFromXML(
            dirname(__DIR__, 2),
            '<?xml version="1.0"?>
            <psalm>
                <projectFiles>
                    <directory name="tests/fixtures/ExtraFiles/src" />
                </projectFiles>
                <extraFiles>
                    <directory name="tests/fixtures/ExtraFiles/dependency" />
                </extraFiles>
            </psalm>',
        );
        $config->cache_directory = null;
        $config->setIncludeCollector(new IncludeCollector());

        $providers = new Providers(
            $this->file_provider,
            new FakeParserCacheProvider(),
        );

        $this->codebase = new Codebase($config, $providers);

        $this->project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            null,
            [],
            1,
            1,
            null,
            $this->codebase,
        );

        $this->project_analyzer->setPhpVersion('8.1', 'tests');
    }

    public function testExtraFilesAreKnownToTheCodebaseInServerMode(): void
    {
        $this->enterServerMode();

        $this->assertTrue(
            $this->codebase->interfaceExists(self::EXTRA_FILES_INTERFACE),
            'An interface declared in an extra file should be known to the language server',
        );
        $this->assertTrue(
            $this->codebase->classExists(self::EXTRA_FILES_CLASS),
            'A class declared in an extra file should be known to the language server',
        );
    }

    public function testExtraFilesAreOnlyShallowScannedInServerMode(): void
    {
        $this->enterServerMode();

        $scanned_files = $this->codebase->scanner->getScannedFiles();

        $extra_file = self::fixturePath('dependency/ExtraFilesInterface.php');
        $project_file = self::fixturePath('src/ConsumesExtraFiles.php');

        $this->assertArrayHasKey($extra_file, $scanned_files, 'Extra files should be scanned');
        $this->assertFalse($scanned_files[$extra_file], 'Extra files should not be deep scanned');

        $this->assertArrayHasKey($project_file, $scanned_files, 'Project files should be scanned');
        $this->assertTrue($scanned_files[$project_file], 'Project files should be deep scanned');
    }

    public function testExtraFilesAreKnownToTheCodebaseWhenCheckingPaths(): void
    {
        // The CLI counterpart of the above, so the two entry points cannot drift apart again.
        $this->project_analyzer->checkPaths([self::fixturePath('src')]);

        $this->assertTrue($this->codebase->interfaceExists(self::EXTRA_FILES_INTERFACE));
        $this->assertTrue($this->codebase->classExists(self::EXTRA_FILES_CLASS));
    }

    private function enterServerMode(): void
    {
        $clientConfiguration = new ClientConfiguration();
        $clientConfiguration->logLevel = null;

        $server = new LanguageServer(
            new MockProtocolStream(),
            new MockProtocolStream(),
            $this->project_analyzer,
            $this->codebase,
            $clientConfiguration,
            new Progress,
            new PathMapper((string) getcwd(), (string) getcwd()),
        );

        $this->project_analyzer->serverMode($server);
        $this->codebase->scanFiles();
    }

    private static function fixturePath(string $relative_path): string
    {
        return (string) realpath(dirname(__DIR__) . '/fixtures/ExtraFiles/' . $relative_path);
    }
}
