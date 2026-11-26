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
    /**
     * @return iterable<string, array{int}>
     * @psalm-pure
     */
    public static function threadCounts(): iterable
    {
        yield '1 thread' => [1];
        yield '2 threads' => [2];
    }

    #[DataProvider('threadCounts')]
    public function testAFileRemovedDuringTheScanAndRestoredBeforeTheAnalysisIsReportedInOneLine(int $threads): void
    {
        if ($threads > 1 && !(extension_loaded('pcntl') && extension_loaded('posix'))) {
            $this->markTestSkipped('--threads needs pcntl and posix');
        }

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

        $result = $this->runPsalmExpectingExitCode1($threads);

        $this->assertStringContainsString(
            'src/C3.php was removed or created while Psalm was running: run Psalm again' . PHP_EOL,
            $result['STDERR'],
        );
        $this->assertStringNotContainsString('Stack trace', $result['STDERR']);
        $this->assertStringNotContainsString('#0', $result['STDERR']);
        $this->assertStringNotContainsString('crashed', $result['STDERR']);
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
     * @return array{STDOUT: string, STDERR: string, CODE: int|null}
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
            'CODE' => $process->getExitCode(),
        ];
    }
}
