<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Config;
use Symfony\Component\Process\Process;

use function basename;
use function clearstatcache;
use function dirname;
use function file_put_contents;
use function filemtime;
use function filesize;
use function glob;
use function json_decode;
use function ksort;
use function mkdir;
use function sort;
use function sys_get_temp_dir;
use function uniqid;
use function var_export;

use const DIRECTORY_SEPARATOR;
use const GLOB_BRACE;
use const GLOB_ONLYDIR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;

/**
 * A run on a warm cache gives the answer of a run without a cache, whatever the runs before it were given
 */
final class CacheIdentityEndToEndTest extends TestCase
{
    use PsalmRunnerTrait;

    private const STR_CONTAINS = <<<'PHP'
        <?php
        namespace App;

        final class A
        {
            public function has(string $h): bool
            {
                return str_contains($h, 'x');
            }

            public function other(string $h): bool
            {
                return $h === 'y';
            }
        }
        PHP;

    private string $project;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->project = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-cache-identity-' . uniqid('', true);
        mkdir($this->project . '/src', 0o777, true);
    }

    #[Override]
    public function tearDown(): void
    {
        Config::removeCacheDirectory($this->project);

        parent::tearDown();
    }

    public function testACacheOfAnotherPhpVersionIsNotRead(): void
    {
        $this->writeConfig();
        file_put_contents($this->project . '/src/A.php', self::STR_CONTAINS);

        $this->psalm(['--php-version=8.0']);

        $this->assertWarmIsCold(['--php-version=7.4']);
    }

    public function testAChangedXIncludedFileIsSeen(): void
    {
        $this->writeConfig('', '<xi:include href="plugins.xml"/>');
        $this->writePlugin(true);
        $this->writeDeprecatedClassUse();
        $included = $this->project . '/plugins.xml';
        file_put_contents(
            $included,
            '<plugins xmlns="https://getpsalm.org/schema/config"><plugin filename="plugins/Deprecator.php"/></plugins>',
        );

        $this->psalm([]);
        file_put_contents($included, '<plugins xmlns="https://getpsalm.org/schema/config"/>');

        $this->assertWarmIsCold([]);
    }

    public function testAChangedPluginFileIsSeen(): void
    {
        $this->writeConfig('', '<plugins><plugin filename="plugins/Deprecator.php"/></plugins>');
        $this->writePlugin(true);
        $this->writeDeprecatedClassUse();

        $this->psalm([]);
        $this->writePlugin(false);

        $this->assertWarmIsCold([]);
    }

    public function testACacheOfAnotherErrorLevelIsNotRead(): void
    {
        $this->writeConfig();
        file_put_contents(
            $this->project . '/src/a.php',
            "<?php\n\n/** @param mixed \$m */\nfunction f(\$m): string\n{\n    return \$m;\n}\n",
        );

        $this->psalm(['--error-level=1']);

        $this->assertWarmIsCold(['--error-level=8']);
    }

    public function testPsalmAfterPsalmRefactorGivesTheColdAnswer(): void
    {
        $this->writeConfig('phpVersion="7.4"');
        file_put_contents($this->project . '/src/A.php', self::STR_CONTAINS);

        $refactor = self::newProcess(
            [
                PHP_BINARY,
                $this->psalmRefactor,
                '-c=' . $this->project . '/psalm.xml',
                '--no-progress',
                '--rename',
                'App\A::other',
                '--to',
                'App\A::another',
            ],
            null,
            self::agentEnvVarsToUnset(),
        );
        $refactor->mustRun();

        $this->assertWarmIsCold([]);
    }

    public function testACacheOfARuntimeWithOtherDisabledFunctionsIsNotRead(): void
    {
        $this->writeConfig();
        file_put_contents(
            $this->project . '/src/A.php',
            "<?php\n\nif (!function_exists('phpinfo')) {\n    final class A { public const VALUE = 'polyfill'; }\n"
                . "} else {\n    final class A { public const VALUE = 123; }\n}\n",
        );
        file_put_contents($this->project . '/src/b.php', "<?php\n\nfunction value(): int\n{\n    return A::VALUE;\n}\n");

        $this->psalm([]);

        $this->assertWarmIsCold([], ['-d', 'disable_functions=phpinfo']);
    }

    public function testAChangedPluginClassComposerDoesNotMapIsSeen(): void
    {
        $this->writeConfig('', '<plugins><pluginClass class="LoadedDeprecator"/></plugins>');
        $this->writeDeprecatedClassUse();
        mkdir($this->project . '/vendor');
        // the autoloader of the project loads the plugin itself
        file_put_contents(
            $this->project . '/vendor/autoload.php',
            "<?php\n\n\$loader = require " . var_export(dirname(__DIR__, 2) . '/vendor/autoload.php', true) . ";\n"
                . "require_once __DIR__ . '/../plugins/LoadedDeprecator.php';\n\nreturn \$loader;\n",
        );
        $this->writeLoadedPlugin(true);

        $this->psalm([], [], $this->project);
        $this->writeLoadedPlugin(false);

        $this->assertWarmIsCold([], [], $this->project);
    }

    public function testAChangedFileOfAPluginDirectoryIsSeen(): void
    {
        $this->writeConfig('', '<plugins><plugin filename="tools/Deprecator.php"/></plugins>');
        $this->writeDeprecatedClassUse();
        mkdir($this->project . '/tools');
        file_put_contents(
            $this->project . '/tools/Deprecator.php',
            <<<'PHP'
                <?php

                use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
                use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;

                require_once __DIR__ . '/Deprecation.php';

                final class Deprecator implements AfterClassLikeVisitInterface
                {
                    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void
                    {
                        $event->getStorage()->deprecated = Deprecation::DEPRECATED;
                    }
                }
                PHP,
        );
        $this->writeDeprecation(true);

        $this->psalm([]);
        $this->writeDeprecation(false);

        $this->assertWarmIsCold([]);
    }

    public function testConsolidateCacheConsolidatesTheCacheOfAnyOptions(): void
    {
        $this->writeConfig();
        $this->writeDeprecatedClassUse();

        $this->psalm(['--php-version=8.2', '--find-unused-code']);
        $consolidate = $this->process(['--consolidate-cache']);

        $this->assertSame(0, $consolidate->getExitCode(), $consolidate->getErrorOutput());
        $identities = $this->identityDirectories();
        $this->assertCount(1, $identities);
        // parser, file storage, class-like storage and reference caches
        $this->assertCount(4, $this->glob($identities[0] . '/*/*/consolidated'));
    }

    public function testConsolidateCacheConsolidatesTheCachesMadeWithAnotherLockFile(): void
    {
        $this->writeConfig();
        $this->writeDeprecatedClassUse();

        file_put_contents($this->project . '/composer.lock', '{"packages":[]}');
        $this->psalm([]);
        file_put_contents($this->project . '/composer.lock', '{"packages": []}');
        $this->psalm([]);
        $caches = $this->glob($this->project . '/cache/*/*/*/*', GLOB_ONLYDIR);
        $this->assertCount(2, $this->identityDirectories());

        $consolidate = $this->process(['--consolidate-cache']);

        $this->assertSame(0, $consolidate->getExitCode(), $consolidate->getErrorOutput());
        // no cache is created, and each one that the analyses made is consolidated where it is
        $this->assertSame($caches, $this->glob($this->project . '/cache/*/*/*/*', GLOB_ONLYDIR));
        foreach ($caches as $cache) {
            $this->assertFileExists($cache . '/consolidated');
        }
    }

    public function testAChangedPluginEntryFileOfAnotherExtensionIsSeen(): void
    {
        $this->writeConfig('', '<plugins><plugin filename="tools/Deprecator.inc"/></plugins>');
        $this->writeDeprecatedClassUse();
        mkdir($this->project . '/tools');
        $this->writePlugin(true, $this->project . '/tools/Deprecator.inc');

        $this->psalm([]);
        $this->writePlugin(false, $this->project . '/tools/Deprecator.inc');

        $this->assertWarmIsCold([]);
    }

    public function testAConfigThatCannotBeAnalysedCanBeCleared(): void
    {
        $this->writeConfig('phpVersion="9.9"');

        $clear = $this->process(['--clear-cache']);

        $this->assertSame(0, $clear->getExitCode(), $clear->getErrorOutput());
    }

    public function testRunsWithAnotherThreadCountReadTheSameCache(): void
    {
        $this->writeConfig();
        $this->writeDeprecatedClassUse();

        $this->assertSame(0, $this->process(['--threads=2'])->getExitCode());
        $statements = $this->statementEntries();
        $this->assertNotSame([], $statements);
        $this->assertSame(0, $this->process(['--threads=1'])->getExitCode());

        $this->assertCount(1, $this->identityDirectories());
        // nothing parsed again
        $this->assertSame($statements, $this->statementEntries());
    }

    public function testEachIdentityKeepsItsOwnRunState(): void
    {
        $this->writeConfig();
        $this->writeDeprecatedClassUse();

        $this->psalm(['--php-version=8.0']);
        $this->psalm(['--php-version=7.4']);

        $identities = $this->identityDirectories();
        $this->assertCount(2, $identities);
        foreach ($identities as $identity) {
            $this->assertFileExists($identity . '/good_run');
        }
        $this->assertSame([], $this->glob($this->project . '/cache/*/{good_run,custom_taints}', GLOB_BRACE));
    }

    /**
     * @param list<string> $args
     * @param list<string> $php_options
     */
    private function assertWarmIsCold(array $args, array $php_options = [], ?string $cwd = null): void
    {
        $this->assertSame(
            $this->psalm([...$args, '--no-cache'], $php_options, $cwd),
            $this->psalm($args, $php_options, $cwd),
        );
    }

    /**
     * @param list<string> $args
     * @param list<string> $php_options
     * @return list<string> the issues, sorted
     */
    private function psalm(array $args, array $php_options = [], ?string $cwd = null): array
    {
        $process = $this->process(['--output-format=json', '--threads=1', ...$args], $php_options, $cwd);

        $output = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($output, $process->getErrorOutput());

        $issues = [];
        /** @var array{type: string, file_name: string, line_from: int, message: string} $issue */
        foreach ($output as $issue) {
            $issues[] = $issue['type'] . ' ' . $issue['file_name'] . ':' . $issue['line_from'] . ' ' . $issue['message'];
        }
        sort($issues);

        return $issues;
    }

    /**
     * @param list<string> $args
     * @param list<string> $php_options
     */
    private function process(array $args, array $php_options = [], ?string $cwd = null): Process
    {
        $process = self::newProcess(
            [
                PHP_BINARY,
                ...$php_options,
                $this->psalm,
                '-c=' . $this->project . '/psalm.xml',
                '--no-progress',
                ...$args,
            ],
            $cwd,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        return $process;
    }

    /** @return list<string> the directories of the analysis identities in the cache of the project */
    private function identityDirectories(): array
    {
        return $this->glob($this->project . '/cache/*/*', GLOB_ONLYDIR);
    }

    private function writeConfig(string $attributes = '', string $elements = ''): void
    {
        file_put_contents(
            $this->project . '/psalm.xml',
            <<<XML
                <?xml version="1.0"?>
                <psalm
                    errorLevel="1"
                    resolveFromConfigFile="true"
                    cacheDirectory="cache"
                    findUnusedCode="false"
                    findUnusedBaselineEntry="false"
                    runTaintAnalysis="false"
                    $attributes
                    xmlns="https://getpsalm.org/schema/config"
                    xmlns:xi="http://www.w3.org/2001/XInclude"
                >
                    <projectFiles><directory name="src"/></projectFiles>
                    $elements
                </psalm>
                XML,
        );
    }

    /** A class, and a file that uses it: DeprecatedClass when a plugin marks the class deprecated */
    private function writeDeprecatedClassUse(): void
    {
        file_put_contents($this->project . '/src/A.php', "<?php\n\nnamespace App;\n\nfinal class A {}\n");
        file_put_contents($this->project . '/src/b.php', "<?php\n\nnew \\App\\A();\n");
    }

    private function writePlugin(bool $deprecates, ?string $path = null): void
    {
        @mkdir($this->project . '/plugins');
        file_put_contents(
            $path ?? $this->project . '/plugins/Deprecator.php',
            <<<PHP
                <?php

                use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
                use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;

                final class Deprecator implements AfterClassLikeVisitInterface
                {
                    public static function afterClassLikeVisit(AfterClassLikeVisitEvent \$event): void
                    {
                        \$event->getStorage()->deprecated = {$this->bool($deprecates)};
                    }
                }
                PHP,
        );
    }

    /** A plugin class the autoloader of the project loads, which Composer's class map does not know */
    private function writeLoadedPlugin(bool $deprecates): void
    {
        @mkdir($this->project . '/plugins');
        file_put_contents(
            $this->project . '/plugins/LoadedDeprecator.php',
            <<<PHP
                <?php

                use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
                use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
                use Psalm\Plugin\PluginEntryPointInterface;
                use Psalm\Plugin\RegistrationInterface;

                final class LoadedDeprecator implements PluginEntryPointInterface, AfterClassLikeVisitInterface
                {
                    public function __invoke(RegistrationInterface \$registration, ?SimpleXMLElement \$config = null): void
                    {
                        \$registration->registerHooksFromClass(self::class);
                    }

                    public static function afterClassLikeVisit(AfterClassLikeVisitEvent \$event): void
                    {
                        \$event->getStorage()->deprecated = {$this->bool($deprecates)};
                    }
                }
                PHP,
        );
    }

    /** The file of a plugin directory, beside its entry file, that decides what the plugin does */
    private function writeDeprecation(bool $deprecates): void
    {
        file_put_contents(
            $this->project . '/tools/Deprecation.php',
            "<?php\n\nfinal class Deprecation\n{\n    public const DEPRECATED = {$this->bool($deprecates)};\n}\n",
        );
    }

    /** @return array<string, string> the entries of the parser cache, with their size and modification time */
    private function statementEntries(): array
    {
        $entries = [];
        foreach ($this->glob($this->project . '/cache/*/*/php-parser/*/*') as $entry) {
            if (basename($entry) !== 'lock') {
                clearstatcache(true, $entry);
                $entries[$entry] = (string) filesize($entry) . ' ' . (string) filemtime($entry);
            }
        }
        ksort($entries);

        return $entries;
    }

    /**
     * @param int-mask<GLOB_BRACE, GLOB_ONLYDIR> $flags
     * @return list<string>
     */
    private function glob(string $pattern, int $flags = 0): array
    {
        $paths = glob($pattern, $flags);
        $this->assertNotFalse($paths);

        return $paths;
    }

    /** @psalm-pure */
    private function bool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
