<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;

use function extension_loaded;
use function file_put_contents;
use function mkdir;
use function unlink;

use const PHP_BINARY;
use const PHP_EOL;

/**
 * Runs psalm on a project whose files are changed by a plugin while Psalm runs.
 */
final class PsalmFileChangedDuringRunEndToEndTest extends PsalmEndToEndTestCase
{
    public function testAFileRemovedDuringTheScanAndRestoredBeforeTheAnalysisIsReportedInOneLine(): void
    {
        $this->initProjectOfSixClasses(
            <<<'PHP'
                <?php

                namespace Repro;

                use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
                use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

                rename(dirname(__DIR__) . '/src/C3.php', __DIR__ . '/C3.php.away');

                final class RestoreFile implements AfterCodebasePopulatedInterface
                {
                    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
                    {
                        rename(__DIR__ . '/C3.php.away', dirname(__DIR__) . '/src/C3.php');
                    }
                }
                PHP,
        );

        $result = $this->runPsalmExpectingExitCode1(1);

        $this->assertSame(
            PHP_EOL . 'src/C3.php changed while Psalm was running. Run Psalm again.' . PHP_EOL,
            $result['STDERR'],
        );
    }

    public function testAFileRemovedWhileAnotherIsScannedAndRestoredBeforeTheAnalysisIsReportedInOneLine(): void
    {
        $this->initProjectOfSixClasses(
            <<<'PHP'
                <?php

                namespace Repro;

                use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
                use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
                use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
                use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

                final class RemoveAndRestoreFile implements
                    AfterClassLikeVisitInterface,
                    AfterCodebasePopulatedInterface
                {
                    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void
                    {
                        // C3 is queued for the scan when C1 is visited, and gone when its turn comes
                        if ($event->getStorage()->name === 'C1') {
                            rename(dirname(__DIR__) . '/src/C3.php', __DIR__ . '/C3.php.away');
                        }
                    }

                    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
                    {
                        rename(__DIR__ . '/C3.php.away', dirname(__DIR__) . '/src/C3.php');
                    }
                }
                PHP,
        );

        $result = $this->runPsalmExpectingExitCode1(1);

        $this->assertSame(
            PHP_EOL . 'src/C3.php changed while Psalm was running. Run Psalm again.' . PHP_EOL,
            $result['STDERR'],
        );
    }

    /**
     * @return iterable<string, array{int, string}>
     * @psalm-pure
     */
    public static function threadCountsWithTheirCrashReport(): iterable
    {
        yield '1 thread' => [1, 'RuntimeException: PHP Error: Undefined array key "X"'];
        yield '2 threads' => [
            2,
            'Psalm crashed while analysing src/C3.php' . PHP_EOL . 'RuntimeException: PHP Error: Undefined array key',
        ];
    }

    #[DataProvider('threadCountsWithTheirCrashReport')]
    public function testACrashOnAFileEditedDuringTheRunIsReportedInOneLine(int $threads, string $crash_report): void
    {
        if ($threads > 1 && !(extension_loaded('pcntl') && extension_loaded('posix'))) {
            $this->markTestSkipped('--threads needs pcntl and posix');
        }

        // The provider keeps what it read, which the scan workers of a large project leave out of the parent
        // process, so the plugin makes it forget the file it edits, under the path the scanner knows it by.
        $this->initProjectOfSixClasses(
            <<<'PHP'
                <?php

                namespace Repro;

                use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
                use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

                final class EditFile implements AfterCodebasePopulatedInterface
                {
                    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
                    {
                        $path = realpath(dirname(__DIR__) . '/src/C3.php');
                        file_put_contents($path, "<?php\n\nfinal class C3 { const X = 1; }\necho C3::X;\n");
                        $event->getCodebase()->file_provider->closeFile($path);
                    }
                }
                PHP,
        );

        $result = $this->runPsalmExpectingExitCode1($threads);

        $this->assertStringContainsString(
            'src/C3.php changed while Psalm was running: run Psalm again' . PHP_EOL,
            $result['STDERR'],
        );
        $this->assertStringNotContainsString('Stack trace', $result['STDERR']);
        $this->assertStringNotContainsString('#0', $result['STDERR']);
        $this->assertStringNotContainsString('crashed', $result['STDERR']);

        // the plugin has edited the file for good
        file_put_contents(self::$tmpDir . '/src/C3.php', "<?php\n\nfinal class C3 {}\n");
        $debug = $this->runPsalmExpectingExitCode1($threads, ['--debug']);

        $this->assertStringContainsString($crash_report, $debug['STDOUT'] . $debug['STDERR']);
        $this->assertStringContainsString('#0 ', $debug['STDOUT'] . $debug['STDERR']);
        $this->assertStringContainsString(
            'src/C3.php changed while Psalm was running: run Psalm again' . PHP_EOL,
            $debug['STDERR'],
        );
    }

    /**
     * Six classes in src/ and a plugin of the given code in plugins/
     */
    private function initProjectOfSixClasses(string $plugin): void
    {
        unlink(self::$tmpDir . '/src/FileWithErrors.php');
        for ($i = 1; $i <= 6; ++$i) {
            file_put_contents(self::$tmpDir . "/src/C$i.php", "<?php\n\nfinal class C$i {}\n");
        }

        mkdir(self::$tmpDir . '/plugins');
        file_put_contents(self::$tmpDir . '/plugins/plugin.php', $plugin);
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            <<<'XML'
                <?xml version="1.0"?>
                <psalm errorLevel="1" findUnusedCode="false" resolveFromConfigFile="true">
                    <projectFiles>
                        <directory name="src"/>
                    </projectFiles>
                    <plugins>
                        <plugin filename="plugins/plugin.php"/>
                    </plugins>
                </psalm>
                XML,
        );
    }

    /**
     * @param list<string> $extra_args
     * @return array{STDOUT: string, STDERR: string}
     */
    private function runPsalmExpectingExitCode1(int $threads, array $extra_args = []): array
    {
        $process = self::newProcess(
            [
                ...[PHP_BINARY, $this->psalm, '--output-format=console', '--no-cache', '--no-progress'],
                ...["--threads=$threads", '-c=' . self::$tmpDir . '/psalm.xml'],
                ...$extra_args,
            ],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertSame(
            1,
            $process->getExitCode(),
            $process->getErrorOutput() . $process->getOutput(),
        );

        return [
            'STDOUT' => $process->getOutput(),
            'STDERR' => $process->getErrorOutput(),
        ];
    }
}
