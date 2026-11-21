<?php

declare(strict_types=1);

namespace Psalm\Tests\Cache;

use Override;
use Psalm\Config;
use Psalm\Internal\Cache\AnalysisIdentity;
use Psalm\Tests\TestCase;
use Symfony\Component\Process\Process;

use function chmod;
use function file_put_contents;
use function function_exists;
use function is_array;
use function json_decode;
use function mkdir;
use function posix_getuid;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;
use const PHP_OS_FAMILY;

final class AnalysisIdentityTest extends TestCase
{
    private const PSALM_DIRECTORY = __DIR__ . '/../..';

    private static string $root;

    private string $project;

    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'psalm-analysis-identity-' . uniqid('', true);
    }

    #[Override]
    public static function tearDownAfterClass(): void
    {
        Config::removeCacheDirectory(self::$root);

        parent::tearDownAfterClass();
    }

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->project = self::$root . DIRECTORY_SEPARATOR . uniqid('', true);
        mkdir($this->project . '/src', 0o777, true);
    }

    public function testTheIdentityHasTheVersionToAnalyseForWhateverItsSource(): void
    {
        $configured = $this->config('phpVersion="8.2"');
        $given = $this->config('phpVersion="8.2"');
        $given->setPhpVersionOverride('8.2');
        $tests = $this->config('phpVersion="8.2"');
        $tests->setPhpVersionOverride('8.2', 'tests');
        $other = $this->config('phpVersion="8.2"');
        $other->setPhpVersionOverride('7.4');

        $fingerprint = AnalysisIdentity::of($configured)->getFingerprint();
        $this->assertSame($fingerprint, AnalysisIdentity::of($given)->getFingerprint());
        $this->assertSame($fingerprint, AnalysisIdentity::of($tests)->getFingerprint());
        $this->assertNotSame($fingerprint, AnalysisIdentity::of($other)->getFingerprint());
    }

    public function testTheDepsComponentIsTheLockFileOfTheProject(): void
    {
        $other_project = $this->project . '/other';
        mkdir($other_project . '/src', 0o777, true);
        file_put_contents($this->project . '/composer.lock', '{"packages": []}');
        file_put_contents($other_project . '/composer.lock', '{"packages": [{"name": "a/b"}]}');

        $this->assertNotSame(
            $this->component($this->config(), 'deps'),
            $this->component($this->config('', '', $other_project), 'deps'),
        );
    }

    public function testTheEngineComponentCoversTheSerializerAndTheStorageFiles(): void
    {
        foreach (AnalysisIdentity::ENGINE_FILES as $file) {
            $this->assertFileExists(self::PSALM_DIRECTORY . '/src/Psalm/' . $file);
        }

        $this->assertNotSame(
            $this->component($this->config('compressor="off"'), 'engine'),
            $this->component($this->config('compressor="deflate"'), 'engine'),
        );
    }

    public function testThePluginsComponentCoversEveryFileOfAPluginDirectory(): void
    {
        $this->writePluginDirectory();
        $config = '<plugins><plugin filename="tools/Plugin.php"/></plugins>';
        $before = $this->component($this->config('', $config), 'plugins');

        file_put_contents($this->project . '/tools/Hook.php', "<?php\n\nfinal class Hook { /* changed */ }\n");

        $this->assertNotSame($before, $this->component($this->config('', $config), 'plugins'));
    }

    public function testThePluginsComponentCoversOnlyTheEntryFileOfAPluginInTheProjectRoot(): void
    {
        file_put_contents($this->project . '/Plugin.php', "<?php\n");
        file_put_contents($this->project . '/other.php', "<?php\n");
        $config = '<plugins><plugin filename="Plugin.php"/></plugins>';
        $before = $this->component($this->config('', $config), 'plugins');

        file_put_contents($this->project . '/other.php', "<?php\n// changed\n");

        $this->assertSame($before, $this->component($this->config('', $config), 'plugins'));
    }

    public function testThePluginsComponentLeavesOutTheDependenciesOfAPluginDirectory(): void
    {
        $this->writePluginDirectory();
        foreach (['vendor', 'node_modules', '.git'] as $directory) {
            mkdir($this->project . '/tools/' . $directory . '/package', 0o777, true);
            file_put_contents($this->project . '/tools/' . $directory . '/package/Code.php', "<?php\n");
        }
        $config = '<plugins><plugin filename="tools/Plugin.php"/></plugins>';
        $before = $this->component($this->config('', $config), 'plugins');

        foreach (['vendor', 'node_modules', '.git'] as $directory) {
            file_put_contents($this->project . '/tools/' . $directory . '/package/Code.php', "<?php\n// changed\n");
        }

        $this->assertSame($before, $this->component($this->config('', $config), 'plugins'));
    }

    public function testAnUnreadableDirectoryOfAPluginDirectoryIsLeftOut(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || (function_exists('posix_getuid') && posix_getuid() === 0)) {
            $this->markTestSkipped('permissions do not keep this process from reading a directory');
        }
        $this->writePluginDirectory();
        mkdir($this->project . '/tools/locked');
        chmod($this->project . '/tools/locked', 0o000);

        try {
            $config = $this->config('', '<plugins><plugin filename="tools/Plugin.php"/></plugins>');
            $plugins = $this->component($config, 'plugins');
        } finally {
            chmod($this->project . '/tools/locked', 0o755);
        }

        $this->assertNotSame('', $plugins);
    }

    public function testThePluginsComponentCoversAPluginClassComposerDoesNotMap(): void
    {
        $without = $this->config();
        // loaded, and no autoloader of the config maps it
        $with = $this->config();
        $with->addPluginClass(self::class);

        $this->assertNotSame($this->component($without, 'plugins'), $this->component($with, 'plugins'));
    }

    public function testTheRuntimeComponentCoversTheDisabledFunctions(): void
    {
        $this->config();
        $components = $this->componentsInAProcess($this->project . '/psalm.xml');

        $this->assertNotSame(
            $components['runtime'],
            $this->componentsInAProcess($this->project . '/psalm.xml', ['-d', 'disable_functions=phpinfo'])['runtime'],
        );
    }

    public function testTwoProcessesLoadingAConfigHaveOneIdentity(): void
    {
        $this->writePluginDirectory();
        $this->config('', '<plugins><plugin filename="tools/Plugin.php"/></plugins>');

        foreach ([self::PSALM_DIRECTORY . '/psalm.xml.dist', $this->project . '/psalm.xml'] as $config_file) {
            $this->assertSame($this->componentsInAProcess($config_file), $this->componentsInAProcess($config_file));
        }
    }

    public function testTheIdentityIsTheOneTheCachesOpenedWith(): void
    {
        $config = $this->config();
        $fingerprint = $config->computeHash();

        $config->level = 8;
        $config->addStubFile(__FILE__);

        $this->assertSame($fingerprint, $config->computeHash());
        $this->assertNotSame($fingerprint, AnalysisIdentity::of($config)->getFingerprint());
    }

    private function config(string $attributes = '', string $elements = '', ?string $project = null): Config
    {
        $project ??= $this->project;
        file_put_contents(
            $project . '/psalm.xml',
            <<<XML
                <?xml version="1.0"?>
                <psalm $attributes cacheDirectory="cache">
                    <projectFiles><directory name="src"/></projectFiles>
                    $elements
                </psalm>
                XML,
        );

        return Config::loadFromXMLFile($project . '/psalm.xml', $project);
    }

    private function component(Config $config, string $name): string
    {
        return AnalysisIdentity::of($config)->components[$name] ?? '';
    }

    private function writePluginDirectory(): void
    {
        mkdir($this->project . '/tools');
        file_put_contents($this->project . '/tools/Plugin.php', "<?php\n\nrequire_once __DIR__ . '/Hook.php';\n");
        file_put_contents($this->project . '/tools/Hook.php', "<?php\n\nfinal class Hook {}\n");
    }

    /**
     * The components of the identity of a config, as another process computes them
     *
     * @param list<string> $php_options
     * @return array<string, string>
     */
    private function componentsInAProcess(string $config_file, array $php_options = []): array
    {
        $script = $this->project . '/identity.php';
        file_put_contents(
            $script,
            <<<'PHP'
                <?php
                require $argv[1] . '/vendor/autoload.php';
                define('PSALM_VERSION', Psalm\Internal\VersionUtils::getPsalmVersion());
                define('PHP_PARSER_VERSION', Psalm\Internal\VersionUtils::getPhpParserVersion());
                $config = Psalm\Config::loadFromXMLFile($argv[2], dirname($argv[2]));
                echo json_encode(Psalm\Internal\Cache\AnalysisIdentity::of($config)->components);
                PHP,
        );

        $process = new Process([PHP_BINARY, ...$php_options, $script, self::PSALM_DIRECTORY, $config_file]);
        $process->mustRun();
        /** @var mixed $components */
        $components = json_decode($process->getOutput(), true);
        $this->assertTrue(is_array($components), $process->getOutput() . $process->getErrorOutput());

        /** @var array<string, string> $components */
        return $components;
    }
}
