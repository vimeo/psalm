<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Crash;

use LogicException;
use Psalm\Context;
use Psalm\Internal\Crash\CrashReport;
use Psalm\Internal\EventDispatcher;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Tests\Internal\Crash\Plugin\NestingHandler;
use Psalm\Tests\Internal\Crash\Plugin\PluginCode;
use Psalm\Tests\Internal\Crash\Plugin\ThrowOnBoom;
use Psalm\Tests\Internal\Crash\Plugin\ThrowingHandler;
use Psalm\Tests\Internal\Crash\Plugin\ThrowingInterpreter;
use Psalm\Tests\TestCase;

/**
 * The classes in Plugin/ stand for a Composer package of a plugin
 */
final class CrashReportTest extends TestCase
{
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
