<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash;

use LogicException;
use PHPUnit\Framework\Attributes\After;
use Psalm\Context;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Crash\CrashReport;
use Psalm\Internal\EventDispatcher;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Tests\Internal\Crash\Plugin\NestingHandler;
use Psalm\Tests\Internal\Crash\Plugin\PluginCode;
use Psalm\Tests\Internal\Crash\Plugin\ThrowOnBoom;
use Psalm\Tests\Internal\Crash\Plugin\ThrowingHandler;
use Psalm\Tests\Internal\Crash\Plugin\ThrowingInterpreter;
use Psalm\Tests\TestCase;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

use function array_keys;
use function dirname;
use function explode;
use function file_get_contents;
use function fopen;
use function getenv;
use function getmypid;
use function glob;
use function mkdir;
use function parse_str;
use function parse_url;
use function putenv;
use function rawurlencode;
use function rewind;
use function rtrim;
use function str_repeat;
use function stream_get_contents;
use function strlen;
use function sys_get_temp_dir;
use function touch;
use function uniqid;

use const PHP_EOL;
use const PHP_URL_QUERY;
use const PHP_VERSION;

/**
 * The classes in Plugin/ stand for a Composer package of a plugin
 */
final class CrashReportTest extends TestCase
{
    /** @var list<string> */
    private array $temporary_paths = [];

    #[After]
    public function removeReports(): void
    {
        putenv('PSALM_CRASH_REPORT');
        (new Filesystem())->remove($this->temporary_paths);
    }

    public function testAHookThatThrowsIsAttributedToItsPackageAndHook(): void
    {
        $e = $this->dispatchAfterCodebasePopulated(ThrowingHandler::class);

        // Psalm's catches and the tests still get what the plugin threw
        $this->assertSame(ThrowingHandler::$thrown, $e);
        $this->assertStringEndsWith(
            ' crashed in plugin acme/crash 1.2.0 (AfterCodebasePopulatedInterface, ' . ThrowingHandler::class . ')',
            (new CrashReport($e, null, [self::package()]))->getHeadline(),
        );
    }

    public function testAHookOutsideAPackageIsAttributedToItsHook(): void
    {
        $e = $this->dispatchAfterCodebasePopulated(ThrowingHandler::class);

        $this->assertStringEndsWith(
            ' crashed in a plugin (AfterCodebasePopulatedInterface, ' . ThrowingHandler::class . ')',
            (new CrashReport($e, null, []))->getHeadline(),
        );
    }

    public function testAHookThatThrowsInsideAnotherHookIsTheOneReported(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->registerClass(ThrowingInterpreter::class);
        NestingHandler::$dispatcher = $dispatcher;

        $e = $this->dispatchAfterCodebasePopulated(NestingHandler::class);

        $this->assertStringEndsWith(
            ' crashed in plugin acme/crash 1.2.0 (StringInterpreterInterface, ' . ThrowingInterpreter::class . ')',
            (new CrashReport($e, null, [self::package()]))->getHeadline(),
        );
    }

    public function testACrashOutsideAHookIsProbablyInThePluginPackageOfItsFirstFrame(): void
    {
        try {
            PluginCode::fail();
        } catch (LogicException $e) {
            $this->assertStringEndsWith(
                ' crashed, probably in plugin acme/crash 1.2.0',
                (new CrashReport($e, null, [self::package()]))->getHeadline(),
            );

            // only the packages of plugins are guessed
            $this->assertStringEndsWith(
                ' crashed',
                (new CrashReport($e, null, [['plugin_class' => null] + self::package()]))->getHeadline(),
            );
        }
    }

    public function testTheContextIsTheInnermostStatement(): void
    {
        $this->project_analyzer->getConfig()->eventDispatcher->registerClass(ThrowOnBoom::class);
        $this->addFile(
            'somefile.php',
            <<<'PHP'
                <?php
                final class Greeter
                {
                    public function greet(bool $loud): void
                    {
                        if ($loud) {
                            foreach ([1, 2] as $_) {
                                echo 'boom';
                            }
                        }
                    }
                }
                PHP,
        );

        try {
            $this->analyzeFile('somefile.php', new Context());
            $this->fail('The hook should have thrown');
        } catch (LogicException $e) {
            $this->assertSame(
                'while analysing Greeter::greet() at somefile.php:8',
                (new CrashReport($e, null, []))->getContextLine(),
            );
        }
    }

    public function testTheContextOfAClosureNamesTheClosure(): void
    {
        $this->project_analyzer->getConfig()->eventDispatcher->registerClass(ThrowOnBoom::class);
        $this->addFile(
            'somefile.php',
            <<<'PHP'
                <?php
                $greet = function (): void {
                    echo 'boom';
                };
                PHP,
        );

        try {
            $this->analyzeFile('somefile.php', new Context());
            $this->fail('The hook should have thrown');
        } catch (LogicException $e) {
            $this->assertSame(
                'while analysing {closure}() at somefile.php:3',
                (new CrashReport($e, null, []))->getContextLine(),
            );
        }
    }

    public function testPsalmCrashReport0WritesNoReport(): void
    {
        putenv('PSALM_CRASH_REPORT=0');

        $output = self::printCrash(self::pluginFailure());

        $this->assertStringContainsString('LogicException: the plugin failed', $output);
        $this->assertStringNotContainsString('Full report', $output);
        $this->assertStringNotContainsString('could not write', $output);
    }

    public function testPsalmCrashReportPutsTheReportInItsDirectory(): void
    {
        $directory = $this->makeTemporaryDirectory() . '/reports';
        putenv('PSALM_CRASH_REPORT=' . $directory);

        $output = self::printCrash(self::pluginFailure());

        $reports = glob($directory . '/crash-*-' . (int) getmypid() . '.txt');
        $this->assertIsArray($reports);
        $this->assertCount(1, $reports);
        $this->assertStringContainsString('Full report: ' . $reports[0] . PHP_EOL, $output);

        $report = (string) file_get_contents($reports[0]);
        $this->assertStringContainsString('PHP: ' . PHP_VERSION . PHP_EOL, $report);
        $this->assertStringContainsString('Command: psalm --threads=2' . PHP_EOL, $report);
        $this->assertStringContainsString('LogicException: the plugin failed' . PHP_EOL, $report);
        // the tests run in Psalm's own project
        $this->assertStringContainsString('  at <project>/tests/Internal/Crash/Plugin/PluginCode.php:', $report);
        $this->assertStringNotContainsString(__DIR__, $report);
        $this->assertStringNotContainsString((string) getenv('HOME') . '/', $report);
    }

    public function testAReportThatCannotBeWrittenDoesNotHideTheCrash(): void
    {
        // a file where the directory should be
        $directory = $this->makeTemporaryDirectory() . '/reports';
        touch($directory);
        putenv('PSALM_CRASH_REPORT=' . $directory);

        $output = self::printCrash(self::pluginFailure());

        $this->assertStringContainsString('LogicException: the plugin failed', $output);
        $this->assertStringContainsString(
            PHP_EOL . 'Psalm could not write the full report: could not create ' . $directory . PHP_EOL
                . 'Report the bug to Psalm: https://',
            $output,
        );
    }

    public function testWithoutACacheTheReportGoesToTheTemporaryDirectory(): void
    {
        putenv('PSALM_CRASH_REPORT');
        $config = $this->project_analyzer->getConfig();
        $config->cache_directory = null;

        $path = (new CrashReport(self::pluginFailure(), $config, []))->writeFile('psalm');

        $this->assertNotNull($path);
        $this->temporary_paths[] = $path;
        $this->assertSame(rtrim(sys_get_temp_dir(), '/\\'), dirname($path));
    }

    public function testTheLinkToPsalmHasNoPathOfTheMachineAndFitsTheCap(): void
    {
        $home = (string) getenv('HOME');
        $e = new RuntimeException(
            'PHP Error: Undefined array key "X" in ' . __FILE__ . ':12'
            . ' for command with CLI args "psalm -c=' . $home . '/secret/psalm.xml"',
        );

        $url = (new CrashReport($e, $this->project_analyzer->getConfig(), []))->getPsalmIssueUrl();
        $fields = self::getQuery($url, 'https://github.com/vimeo/psalm/issues/new?');

        $this->assertSame(
            ['template', 'title', 'psalm_version', 'php_version', 'os', 'plugins', 'exception', 'context', 'frames'],
            array_keys($fields),
        );
        $this->assertSame('crash.yml', $fields['template']);
        $this->assertSame('Crash: RuntimeException: Undefined array key "X"', $fields['title']);
        $this->assertSame(
            'RuntimeException: PHP Error: Undefined array key "X"'
                . ' in <project>/tests/Internal/Crash/CrashReportTest.php:12',
            $fields['exception'],
        );
        // where it was thrown and 10 frames
        $this->assertCount(11, explode("\n", $fields['frames']));
        $this->assertStringStartsWith('at <project>/tests/Internal/Crash/CrashReportTest.php:', $fields['frames']);
        foreach ([dirname(__DIR__, 3), $home . '/', sys_get_temp_dir()] as $private) {
            $this->assertStringNotContainsString($private, $url);
            $this->assertStringNotContainsString(rawurlencode($private), $url);
        }

        $long = (new CrashReport(new RuntimeException(str_repeat('x', 20_000)), null, []))->getPsalmIssueUrl();
        $this->assertLessThanOrEqual(7_500, strlen($long));
        $this->assertStringEndsWith(
            'xxx...',
            self::getQuery($long, 'https://github.com/vimeo/psalm/issues/new?')['exception'],
        );
    }

    public function testACrashInAPluginLinksToItsTrackerWithATitleAndABody(): void
    {
        $e = $this->dispatchAfterCodebasePopulated(ThrowingHandler::class);

        $line = (new CrashReport($e, null, [self::package()]))->getReportLine();
        $fields = self::getQuery($line, 'Report it to the plugin: https://github.com/acme/crash/issues/new?');
        $this->assertSame(['title', 'body'], array_keys($fields));
        $this->assertSame('Crash: LogicException: the handler failed', $fields['title']);
        $this->assertStringContainsString(
            '- Hook: ' . AfterCodebasePopulatedInterface::class . ', ' . ThrowingHandler::class,
            $fields['body'],
        );

        $from_source = ['issues' => null, 'source' => 'git@github.com:acme/crash.git'] + self::package();
        $this->assertStringStartsWith(
            'Report it to the plugin: https://github.com/acme/crash/issues/new?title=',
            (new CrashReport($e, null, [$from_source]))->getReportLine(),
        );

        $elsewhere = ['issues' => 'https://tracker.example.com/crash'] + self::package();
        $this->assertSame(
            'Report it to the plugin: https://tracker.example.com/crash',
            (new CrashReport($e, null, [$elsewhere]))->getReportLine(),
        );

        $this->assertSame(
            'Report it to the authors of the plugin',
            (new CrashReport($e, null, []))->getReportLine(),
        );
    }

    public function testACrashOfTheEnvironmentGetsAHintAndNoLink(): void
    {
        putenv('PSALM_CRASH_REPORT=0');

        $output = self::printCrash(new ConfigException('Could not resolve config path'));
        $this->assertStringEndsWith(
            PHP_EOL . 'Psalm could not load its configuration: fix what the message says and run Psalm again' . PHP_EOL,
            $output,
        );
        $this->assertStringNotContainsString('https://', $output);

        $output = self::printCrash(new RuntimeException(
            'PHP Error: file_put_contents(cache): Failed to open stream: No space left on device',
        ));
        $this->assertStringEndsWith(
            PHP_EOL . 'The disk is full: free some space and run Psalm again' . PHP_EOL,
            $output,
        );
        $this->assertStringNotContainsString('https://', $output);

        // a plugin that fails to load is a bug of the plugin
        $e = new ConfigException('Failed to load plugin Acme\Crash\Plugin', 0, self::pluginFailure());
        $report = new CrashReport($e, null, [self::package()]);
        $this->assertNull($report->getHint());
        $this->assertStringStartsWith('Report it to the plugin: https://', $report->getReportLine());

        $output = self::printCrash(new RuntimeException('boom'));
        $this->assertStringContainsString(
            PHP_EOL . 'Report the bug to Psalm: https://github.com/vimeo/psalm/issues/new?template=crash.yml&',
            $output,
        );
    }

    /**
     * The decoded query of a URL, after its expected prefix
     *
     * @param non-empty-string $prefix
     * @return array<string, string>
     */
    private static function getQuery(string $url, string $prefix): array
    {
        self::assertStringStartsWith($prefix, $url);
        parse_str((string) parse_url($url, PHP_URL_QUERY), $fields);

        /** @var array<string, string> */
        return $fields;
    }

    /**
     * @psalm-pure
     */
    private static function pluginFailure(): LogicException
    {
        try {
            PluginCode::fail();
        } catch (LogicException $e) {
            return $e;
        }
    }

    /**
     * What Psalm prints for the crash
     */
    private static function printCrash(Throwable $e): string
    {
        $output = fopen('php://memory', 'w+');
        self::assertIsResource($output);
        CrashReport::print($e, 'psalm --threads=2', $output);
        rewind($output);

        return (string) stream_get_contents($output);
    }

    private function makeTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/psalm-crash-report-' . uniqid();
        mkdir($directory);
        $this->temporary_paths[] = $directory;

        return $directory;
    }

    /**
     * @param class-string $handler
     */
    private function dispatchAfterCodebasePopulated(string $handler): LogicException
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->registerClass($handler);

        try {
            $dispatcher->dispatchAfterCodebasePopulated(
                new AfterCodebasePopulatedEvent($this->project_analyzer->getCodebase()),
            );
        } catch (LogicException $e) {
            return $e;
        }

        $this->fail('The handler should have thrown');
    }

    /**
     * @return array{
     *     name: string,
     *     version: ?string,
     *     install_path: string,
     *     issues: ?string,
     *     source: ?string,
     *     plugin_class: ?string
     * }
     * @psalm-pure
     */
    private static function package(): array
    {
        return [
            'name' => 'acme/crash',
            'version' => '1.2.0',
            'install_path' => __DIR__ . '/Plugin',
            'issues' => 'https://github.com/acme/crash/issues',
            'source' => 'https://github.com/acme/crash.git',
            'plugin_class' => 'Acme\Crash\Plugin',
        ];
    }
}
