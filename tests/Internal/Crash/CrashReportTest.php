<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash;

use LogicException;
use PHPUnit\Framework\Attributes\After;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Crash\CrashContext;
use Psalm\Internal\Crash\CrashReport;
use Psalm\Internal\EventDispatcher;
use Psalm\Internal\Provider\AddRemoveTaints\HtmlFunctionTainter;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Plugin\EventHandler\RemoveTaintsInterface;
use Psalm\Tests\Internal\Crash\Plugin\PluginCode;
use Psalm\Tests\Internal\Crash\Plugin\ThrowingHandler;
use Psalm\Tests\TestCase;
use RuntimeException;
use Throwable;

use function array_filter;
use function array_keys;
use function dirname;
use function fopen;
use function getenv;
use function parse_str;
use function parse_url;
use function putenv;
use function rawurlencode;
use function rewind;
use function str_repeat;
use function str_replace;
use function stream_get_contents;
use function strlen;
use function sys_get_temp_dir;

use const PHP_EOL;
use const PHP_URL_QUERY;

/**
 * The classes in Plugin/ stand for a Composer package of a plugin. PsalmCrashReportEndToEndTest covers the console,
 * the statement, the report file and the link of a plugin with one and two threads.
 */
final class CrashReportTest extends TestCase
{
    #[After]
    public function resetCrashReportSetting(): void
    {
        putenv('PSALM_CRASH_REPORT');
    }

    public function testAHookThatThrowsIsAttributedToItsPackageAndHook(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->registerClass(ThrowingHandler::class);

        try {
            $dispatcher->dispatchAfterCodebasePopulated(
                new AfterCodebasePopulatedEvent($this->project_analyzer->getCodebase()),
            );
            $this->fail('The handler should have thrown');
        } catch (LogicException $e) {
            // Psalm's catches and the tests still get what the plugin threw
            $this->assertSame(ThrowingHandler::$thrown, $e);
            $this->assertSame(
                'Psalm crashed in plugin acme/crash 1.2.0 (AfterCodebasePopulatedInterface, '
                    . ThrowingHandler::class . ')',
                (new CrashReport($e, null, [self::package()]))->getHeadline(),
            );
        }
    }

    public function testAPluginHookAroundAHookOfPsalmIsNotBlamed(): void
    {
        $e = new LogicException('a hook of Psalm failed');
        // what EventDispatcher records as the exception goes up through the hooks, the innermost first
        CrashContext::recordHook($e, RemoveTaintsInterface::class, HtmlFunctionTainter::class);
        CrashContext::recordHook($e, AfterCodebasePopulatedInterface::class, ThrowingHandler::class);

        $this->assertSame('Psalm crashed', (new CrashReport($e, null, [self::package()]))->getHeadline());
    }

    public function testACrashOutsideAHookIsProbablyInThePluginPackageOfItsFirstFrame(): void
    {
        $this->assertSame(
            'Psalm crashed, probably in plugin acme/crash 1.2.0',
            (new CrashReport(self::pluginFailure(), null, [self::package()]))->getHeadline(),
        );
    }

    public function testTheLinkToPsalmHasNoPathOfTheMachineAndFitsTheCap(): void
    {
        $home = (string) getenv('HOME');
        $e = new RuntimeException(
            'PHP Error: Undefined array key "X" in ' . __FILE__ . ':12'
            . ' for command with CLI args "psalm -c=' . $home . '/secret/psalm.xml"',
        );

        $url = (new CrashReport($e, $this->project_analyzer->getConfig(), []))->getPsalmIssueUrl();
        $fields = self::getQuery($url);

        $this->assertSame(['title', 'body'], array_keys($fields));
        $this->assertSame('Crash: RuntimeException: Undefined array key "X"', $fields['title']);
        // the separators after the prefix and the ends of lines are the platform's
        $body = str_replace(['\\', "\r\n"], ['/', "\n"], $fields['body']);
        $this->assertMatchesRegularExpression(
            '~\n```\nRuntimeException: PHP Error: Undefined array key "X"'
                . ' in <project>/tests/Internal/Crash/CrashReportTest\.php:12'
                . '\n  at <project>/tests/Internal/Crash/CrashReportTest\.php:\d+'
                . '\n#0 <project>/vendor/phpunit/~',
            $body,
        );
        // 10 frames
        $this->assertStringContainsString("\n#9 ", $body);
        $this->assertStringNotContainsString("\n#10 ", $body);
        $this->assertStringNotContainsString('for command with CLI args', $body);
        foreach (array_filter([dirname(__DIR__, 3), $home === '' ? '' : $home . '/', sys_get_temp_dir()]) as $private) {
            $this->assertStringNotContainsString($private, $url);
            $this->assertStringNotContainsString(rawurlencode($private), $url);
        }

        $long = (new CrashReport(new RuntimeException(str_repeat('x', 20_000)), null, []))->getPsalmIssueUrl();
        $this->assertLessThanOrEqual(7_500, strlen($long));
        $this->assertStringContainsString('xxx...' . PHP_EOL . '  at ', self::getQuery($long)['body']);
    }

    public function testACrashOfTheEnvironmentGetsAHintAndNoLink(): void
    {
        putenv('PSALM_CRASH_REPORT=0');

        $output = self::printCrash(new RuntimeException(
            'PHP Error: file_put_contents(cache): Failed to open stream: No space left on device',
        ));
        $this->assertStringEndsWith(
            PHP_EOL . 'The disk is full: free some space and run Psalm again' . PHP_EOL,
            $output,
        );
        $this->assertStringNotContainsString('https://', $output);
        $this->assertStringNotContainsString('Full report', $output);

        $output = self::printCrash(new ConfigException('Could not resolve config path'));
        $this->assertStringEndsWith(
            PHP_EOL . 'Psalm could not load its configuration: fix what the message says and run Psalm again' . PHP_EOL,
            $output,
        );

        // a plugin that fails to load is a bug of the plugin
        $e = new ConfigException('Failed to load plugin Acme\Crash\Plugin', 0, self::pluginFailure());
        $report = new CrashReport($e, null, [self::package()]);
        $this->assertNull($report->getHint());
        $this->assertStringStartsWith('Report it to the plugin: https://', $report->getReportLine());
    }

    /**
     * The decoded query of a link to a new issue of Psalm
     *
     * @return array<string, string>
     */
    private static function getQuery(string $url): array
    {
        self::assertStringStartsWith('https://github.com/vimeo/psalm/issues/new?', $url);
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
        CrashReport::print($e, 'psalm', $output);
        rewind($output);

        return (string) stream_get_contents($output);
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
