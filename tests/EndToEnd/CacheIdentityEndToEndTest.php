<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use PHPUnit\Framework\TestCase;
use Psalm\Config;

use function file_put_contents;
use function json_decode;
use function mkdir;
use function sort;
use function sys_get_temp_dir;
use function uniqid;

use const DIRECTORY_SEPARATOR;
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

    /** @param list<string> $args */
    private function assertWarmIsCold(array $args): void
    {
        $this->assertSame($this->psalm([...$args, '--no-cache']), $this->psalm($args));
    }

    /**
     * @param list<string> $args
     * @return list<string> the issues, sorted
     */
    private function psalm(array $args): array
    {
        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '-c=' . $this->project . '/psalm.xml',
                '--output-format=json',
                '--no-progress',
                '--threads=1',
                ...$args,
            ],
            null,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

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

    private function writePlugin(bool $deprecates): void
    {
        @mkdir($this->project . '/plugins');
        file_put_contents(
            $this->project . '/plugins/Deprecator.php',
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

    /** @psalm-pure */
    private function bool(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
