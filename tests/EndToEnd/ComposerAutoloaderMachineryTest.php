<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

use function array_map;
use function assert;
use function explode;
use function file_put_contents;
use function glob;
use function is_dir;
use function mkdir;
use function realpath;
use function rmdir;
use function str_replace;
use function str_starts_with;
use function sys_get_temp_dir;
use function tempnam;
use function trim;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const GLOB_BRACE;

/**
 * Runs Psalm on a project with an optimized Composer autoloader.
 */
final class ComposerAutoloaderMachineryTest extends TestCase
{
    use PsalmRunnerTrait;

    private string $project_dir;

    #[Override]
    protected function setUp(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'PsalmComposerMachinery_');
        assert($tmp !== false);
        unlink($tmp);
        mkdir($tmp);
        $this->project_dir = (string) realpath($tmp);
        mkdir($this->project_dir . '/src');

        file_put_contents($this->project_dir . '/composer.json', <<<'JSON'
            {
                "autoload": {
                    "psr-4": {"Acme\\": "src/"},
                    "files": ["src/helpers.php"]
                }
            }
            JSON);
        file_put_contents($this->project_dir . '/psalm.xml', <<<'XML'
            <?xml version="1.0"?>
            <psalm errorLevel="1" runTaintAnalysis="false" resolveFromConfigFile="true"
                xmlns="https://getpsalm.org/schema/config">
                <projectFiles><directory name="src"/></projectFiles>
                <issueHandlers>
                    <UnusedClass errorLevel="suppress"/>
                    <MissingPureAnnotation errorLevel="suppress"/>
                </issueHandlers>
            </psalm>
            XML);
        file_put_contents($this->project_dir . '/src/helpers.php', <<<'PHP'
            <?php
            namespace Acme;

            /** @psalm-pure */
            function helper(int $i): int
            {
                return $i + 1;
            }
            PHP);
        file_put_contents($this->project_dir . '/src/Version.php', <<<'PHP'
            <?php
            namespace Acme;

            use Composer\Autoload\ClassLoader;
            use Composer\InstalledVersions;

            final class Version
            {
                public static function of(string $package): ?string
                {
                    return InstalledVersions::getVersion($package);
                }

                /** @return array<string, list<string>> */
                public static function prefixes(ClassLoader $loader): array
                {
                    return $loader->getPrefixesPsr4();
                }

                public static function next(): int
                {
                    return helper(1);
                }
            }
            PHP);

        (new Process(['composer', 'dump-autoload', '--optimize', '--no-plugins'], $this->project_dir, null, null, 120))
            ->mustRun();
    }

    #[Override]
    protected function tearDown(): void
    {
        self::removeDirectory($this->project_dir);
    }

    public function testComposerMachineryIsNotDeepScannedButComposerClassesStillResolve(): void
    {
        $result = $this->runPsalm(['--no-cache', '--debug', '--no-progress'], $this->project_dir, false, false);

        self::assertStringContainsString('No errors found!', $result['STDOUT']);

        $registered = $this->registeredAutoloadFiles($result['STDERR']);
        self::assertContains($this->path('src/helpers.php'), $registered);

        self::assertNotContains($this->path('vendor/autoload.php'), $registered);
        self::assertNotContains($this->path('vendor/composer/autoload_real.php'), $registered);
        self::assertNotContains($this->path('vendor/composer/autoload_static.php'), $registered);
        self::assertNotContains($this->path('vendor/composer/autoload_files.php'), $registered);
        self::assertNotContains($this->path('vendor/composer/ClassLoader.php'), $registered);
        self::assertNotContains($this->path('vendor/composer/platform_check.php'), $registered);
    }

    /**
     * Psalm reports included files with the native directory separator
     *
     * @psalm-capabilities read-props
     */
    private function path(string $relative_path): string
    {
        return $this->project_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
    }

    /**
     * @return list<string>
     * @psalm-pure
     */
    private function registeredAutoloadFiles(string $debug_output): array
    {
        $registered = [];
        $in_section = false;

        foreach (explode("\n", $debug_output) as $line) {
            if ($line === 'Registering autoloaded files') {
                $in_section = true;
            } elseif ($line === 'Finished registering autoloaded files') {
                break;
            } elseif ($in_section && str_starts_with($line, '   ')) {
                $registered[] = trim($line);
            }
        }

        return $registered;
    }

    private static function removeDirectory(string $dir): void
    {
        foreach (array_map('strval', (array) glob($dir . '/{,.}[!.]*', GLOB_BRACE)) as $entry) {
            if (is_dir($entry)) {
                self::removeDirectory($entry);
            } else {
                unlink($entry);
            }
        }

        rmdir($dir);
    }
}
