<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use PHPUnit\Framework\Attributes\DataProvider;

use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function mkdir;
use function str_replace;
use function substr_count;
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

    public function testADependencyRemovedWhileAnotherIsScannedAndRestoredBeforeTheAnalysisIsReportedInOneLine(): void
    {
        $this->initProjectOfSixClasses(
            <<<'PHP'
                <?php

                namespace Repro;

                use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
                use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
                use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
                use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;

                final class RemoveAndRestoreDependency implements
                    AfterClassLikeVisitInterface,
                    AfterCodebasePopulatedInterface
                {
                    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void
                    {
                        // DepB is queued for the scan when DepA is visited, and gone when its turn comes
                        if ($event->getStorage()->name === 'DepA') {
                            rename(__DIR__ . '/dep/DepB.php', __DIR__ . '/DepB.php.away');
                        }
                    }

                    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
                    {
                        rename(__DIR__ . '/DepB.php.away', __DIR__ . '/dep/DepB.php');
                    }
                }
                PHP,
        );

        // the two files are scanned shallowly: they are not in the project, only a dependency of it
        mkdir(self::$tmpDir . '/plugins/dep');
        file_put_contents(self::$tmpDir . '/plugins/dep/DepA.php', "<?php\n\nfinal class DepA {}\n");
        file_put_contents(self::$tmpDir . '/plugins/dep/DepB.php', "<?php\n\nfinal class DepB {}\n");
        $psalm_xml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('</psalm>', '<extraFiles><directory name="plugins/dep"/></extraFiles></psalm>', $psalm_xml),
        );

        $result = $this->runPsalmExpectingExitCode1(1);

        $this->assertSame(
            PHP_EOL . 'plugins/dep/DepB.php changed while Psalm was running. Run Psalm again.' . PHP_EOL,
            $result['STDERR'],
        );
    }

    /**
     * @return iterable<string, array{int, bool}>
     * @psalm-pure
     */
    public static function crashes(): iterable
    {
        yield '1 thread, file edited' => [1, true];
        yield '1 thread, file untouched' => [1, false];
        yield '2 threads, file edited' => [2, true];
        yield '2 threads, file untouched' => [2, false];
    }

    #[DataProvider('crashes')]
    public function testACrashIsReportedAsAChangeOnlyWhenTheFileChanged(int $threads, bool $edited): void
    {
        if ($threads > 1 && !(extension_loaded('pcntl') && extension_loaded('posix'))) {
            $this->markTestSkipped('--threads needs pcntl and posix');
        }

        $this->initProjectOfSixClasses(
            <<<'PHP'
                <?php

                namespace Repro;

                use LogicException;
                use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
                use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
                use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
                use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;

                final class CrashOnC3 implements AfterCodebasePopulatedInterface, AfterFileAnalysisInterface
                {
                    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
                    {
                        if (!is_file(__DIR__ . '/edit-c3')) {
                            return;
                        }

                        // The file provider keeps what it read, which the scan workers of a large project leave out
                        // of the parent process. Make it forget the file, under the path the scanner knows it by.
                        $path = realpath(dirname(__DIR__) . '/src/C3.php');
                        file_put_contents($path, "<?php\n\nfinal class C3 {}\n// " . uniqid() . "\n");
                        $event->getCodebase()->file_provider->closeFile($path);
                    }

                    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void
                    {
                        if (basename($event->getStatementsSource()->getFilePath()) === 'C3.php') {
                            throw new LogicException('plugin boom');
                        }
                    }
                }
                PHP,
        );

        if ($edited) {
            file_put_contents(self::$tmpDir . '/plugins/edit-c3', '');
        }

        $result = $this->runPsalmExpectingExitCode1($threads);

        if ($edited) {
            $this->assertSame(
                PHP_EOL . 'Psalm crashed on src/C3.php, which changed while Psalm was running.'
                    . ' Run Psalm again (--debug shows the crash).' . PHP_EOL,
                $result['STDERR'],
            );

            $debug = $this->runPsalmExpectingExitCode1($threads, ['--debug']);

            $this->assertStringContainsString(
                'Psalm crashed on src/C3.php' . PHP_EOL . 'LogicException: plugin boom' . PHP_EOL . '  at ',
                $debug['STDOUT'] . $debug['STDERR'],
            );

            return;
        }

        $this->assertStringStartsWith(
            PHP_EOL . 'Psalm crashed on src/C3.php' . PHP_EOL . 'LogicException: plugin boom' . PHP_EOL
                . '  at plugins/plugin.php:',
            $result['STDERR'],
        );
        $this->assertSame(1, substr_count($result['STDERR'], 'LogicException: plugin boom'));
        $this->assertStringNotContainsString('Revolt', $result['STDERR']);
        $this->assertStringNotContainsString('which changed', $result['STDERR']);
        $this->assertStringEndsWith('crashed due to an uncaught Throwable)' . PHP_EOL, $result['STDERR']);
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
