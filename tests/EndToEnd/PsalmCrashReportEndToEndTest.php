<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use PHPUnit\Framework\Attributes\DataProvider;

use function basename;
use function extension_loaded;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function json_decode;
use function json_encode;
use function mkdir;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;
use const PHP_EOL;

/**
 * Runs psalm on a project with a plugin package whose hook throws
 */
final class PsalmCrashReportEndToEndTest extends PsalmEndToEndTestCase
{
    #[Override]
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        mkdir(self::$tmpDir . '/vendor/acme/psalm-crash-plugin', 0777, true);
        file_put_contents(
            self::$tmpDir . '/vendor/acme/psalm-crash-plugin/plugin.php',
            <<<'PHP'
                <?php

                namespace Acme\CrashPlugin;

                use PhpParser\Node\Scalar\String_;
                use Psalm\Plugin\EventHandler\AfterExpressionAnalysisInterface;
                use Psalm\Plugin\EventHandler\Event\AfterExpressionAnalysisEvent;

                final class ThrowOnBoom implements AfterExpressionAnalysisInterface
                {
                    public static function afterExpressionAnalysis(AfterExpressionAnalysisEvent $event): ?bool
                    {
                        $expr = $event->getExpr();
                        if ($expr instanceof String_ && $expr->value === 'boom') {
                            throw new \LogicException('boom');
                        }

                        return null;
                    }
                }
                PHP,
        );

        $installed_path = self::$tmpDir . '/vendor/composer/installed.json';
        $installed = json_decode((string) file_get_contents($installed_path), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($installed);
        self::assertIsArray($installed['packages']);
        $installed['packages'][] = [
            'name' => 'acme/psalm-crash-plugin',
            'version' => '1.2.0',
            'type' => 'psalm-plugin',
            'support' => ['issues' => 'https://github.com/acme/psalm-crash-plugin/issues'],
            'install-path' => '../acme/psalm-crash-plugin',
        ];
        file_put_contents($installed_path, json_encode($installed, JSON_THROW_ON_ERROR));
    }

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
    public function testAHookThatThrowsIsReportedWithItsPluginStatementReportFileAndLink(int $threads): void
    {
        if ($threads > 1 && !(extension_loaded('pcntl') && extension_loaded('posix'))) {
            $this->markTestSkipped('--threads needs pcntl and posix');
        }

        unlink(self::$tmpDir . '/src/FileWithErrors.php');
        // more files than threads, so that the analysis forks
        for ($i = 1; $i <= 3; ++$i) {
            file_put_contents(self::$tmpDir . "/src/C$i.php", "<?php\n\nfinal class C$i {}\n");
        }

        file_put_contents(
            self::$tmpDir . '/src/Greeter.php',
            <<<'PHP'
                <?php

                final class Greeter
                {
                    public function greet(bool $loud): void
                    {
                        if ($loud) {
                            echo 'boom';
                        }
                    }
                }
                PHP,
        );
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            <<<'XML'
                <?xml version="1.0"?>
                <psalm errorLevel="1" findUnusedCode="false" resolveFromConfigFile="true">
                    <projectFiles>
                        <directory name="src"/>
                    </projectFiles>
                    <plugins>
                        <plugin filename="vendor/acme/psalm-crash-plugin/plugin.php"/>
                    </plugins>
                </psalm>
                XML,
        );

        $reports = self::$tmpDir . '/src/reports';
        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '--output-format=console',
                '--no-cache',
                '--no-progress',
                "--threads=$threads",
                '-c=' . self::$tmpDir . '/psalm.xml',
            ],
            self::$tmpDir,
            ['PSALM_CRASH_REPORT' => $reports] + self::agentEnvVarsToUnset(),
        );
        $process->run();
        $stderr = $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $stderr . $process->getOutput());
        $this->assertStringContainsString(
            ' crashed in plugin acme/psalm-crash-plugin 1.2.0'
                . ' (AfterExpressionAnalysisInterface, Acme\CrashPlugin\ThrowOnBoom)' . PHP_EOL
                . 'while analysing Greeter::greet() at src/Greeter.php:8' . PHP_EOL
                . 'LogicException: boom' . PHP_EOL,
            $stderr,
        );

        $files = glob($reports . '/crash-*.txt');
        $this->assertIsArray($files);
        $this->assertCount(1, $files);
        $this->assertStringContainsString(
            PHP_EOL . 'Full report: ' . $reports . DIRECTORY_SEPARATOR . basename($files[0]) . PHP_EOL,
            $stderr,
        );
        $this->assertStringContainsString(
            PHP_EOL . 'Report it to the plugin: https://github.com/acme/psalm-crash-plugin/issues/new?title=',
            $stderr,
        );

        $report = (string) file_get_contents($files[0]);
        $this->assertStringContainsString(PHP_EOL . 'Phase: analysing' . PHP_EOL, $report);
        $this->assertStringContainsString(
            PHP_EOL . '  at acme/psalm-crash-plugin/plugin.php:15' . PHP_EOL,
            $report,
        );
        $this->assertStringNotContainsString(self::$tmpDir, $report);
    }
}
