<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Symfony\Component\Process\Process;

use function assert;
use function file_get_contents;
use function file_put_contents;
use function str_replace;
use function unlink;

use const PHP_BINARY;
use const PHP_EOL;

/**
 * Tests how the psalm command reads the paths to analyse, from its arguments and STDIN,
 * by launching a new process as if invoked by a real user.
 */
final class PsalmPathArgumentsEndToEndTest extends PsalmEndToEndTestCase
{
    public function testPsalmReadsThePathsToCheckFromStdinForADash(): void
    {
        // only the clean file is named on STDIN: FileWithErrors.php is in the project, but isn't analysed
        $process = $this->runPsalmWithPathsOnStdin(self::$tmpDir . '/src/Clean.php' . PHP_EOL);

        $this->assertStringNotContainsString('Uninitialized string offset', $process->getErrorOutput());
        $this->assertStringContainsString('No errors found!', $process->getOutput());
        $this->assertSame(0, $process->getExitCode());
    }

    public function testPsalmReadsThePathsOfEveryLineOfStdinForADash(): void
    {
        $process = $this->runPsalmWithPathsOnStdin(
            self::$tmpDir . '/src/Clean.php' . PHP_EOL . self::$tmpDir . '/src/FileWithErrors.php' . PHP_EOL,
        );

        $this->assertStringContainsString('2 errors', $process->getOutput());
        $this->assertSame(2, $process->getExitCode());
    }

    public function testPsalmIgnoresTheEnvironmentWhenItReadsThePathsFromStdin(): void
    {
        $process = $this->runPsalmWithPathsOnStdin(
            self::$tmpDir . '/src/Clean.php' . PHP_EOL,
            ['PSALM_STDIN_PATHS' => self::$tmpDir . '/src/FileWithErrors.php'],
        );

        $this->assertStringContainsString('No errors found!', $process->getOutput());
        $this->assertSame(0, $process->getExitCode());
    }

    /** @param array<string, string> $env */
    private function runPsalmWithPathsOnStdin(string $stdin, array $env = []): Process
    {
        $this->runPsalmInit(1);
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/Clean.php', "<?php\nfunction clean(): void {}\n");

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '--output-format=console',
                '--no-progress',
                '-c=' . self::$tmpDir . '/psalm.xml',
                '-',
            ],
            self::$tmpDir,
            $env + self::agentEnvVarsToUnset(),
            $stdin,
        );
        $process->run();

        return $process;
    }

    public function testPsalmRejectsAnEmptyPathWithoutCrashing(): void
    {
        $this->runPsalmInit(1);

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '--output-format=console',
                '--no-progress',
                '-c=' . self::$tmpDir . '/psalm.xml',
                '',
            ],
            self::$tmpDir,
            self::agentEnvVarsToUnset(),
        );
        $process->run();

        $this->assertStringNotContainsString('Uninitialized string offset', $process->getErrorOutput());
        $this->assertStringContainsString('Cannot locate', $process->getErrorOutput());
        $this->assertSame(1, $process->getExitCode());
    }

    public function testShowInfoWithoutAValueShowsInfoIssues(): void
    {
        $this->initProjectWithInfoIssues();

        $result = $this->runPsalm(['--no-progress', '--show-info'], self::$tmpDir);
        $this->assertStringContainsString('INFO: MissingReturnType', $result['STDOUT']);

        $result = $this->runPsalm(['--no-progress', '--show-info=false'], self::$tmpDir);
        $this->assertStringNotContainsString('INFO: MissingReturnType', $result['STDOUT']);
    }

    public function testShowInfoWithoutAValueDoesNotTakeThePathThatFollowsAsItsValue(): void
    {
        $this->initProjectWithInfoIssues();

        $result = $this->runPsalm(['--no-progress', '--show-info', self::$tmpDir . '/src/A.php'], self::$tmpDir);

        $this->assertStringContainsString('A.php', $result['STDOUT']);
        $this->assertStringNotContainsString('B.php', $result['STDOUT']);
    }

    public function testReportShowInfoWithoutAValueWritesTheReport(): void
    {
        $this->initProjectWithInfoIssues();
        $report = self::$tmpDir . '/report.json';
        $report_without_info = self::$tmpDir . '/report-without-info.json';

        $this->runPsalm(['--no-progress', '--report-show-info', '--report=' . $report], self::$tmpDir);
        $this->runPsalm(
            ['--no-progress', '--report-show-info=false', '--report=' . $report_without_info],
            self::$tmpDir,
        );

        $this->assertFileExists($report);
        $this->assertStringContainsString('MissingReturnType', (string) file_get_contents($report));
        $this->assertFileExists($report_without_info);
        $this->assertStringNotContainsString('MissingReturnType', (string) file_get_contents($report_without_info));
    }

    /**
     * A project with two files, each with an info issue
     */
    private function initProjectWithInfoIssues(): void
    {
        $this->runPsalmInit(1);

        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        assert($psalmXml !== false);
        $psalmXml = str_replace(
            ['findUnusedCode="true"', '</psalm>'],
            [
                'findUnusedCode="false" runTaintAnalysis="false"',
                '<issueHandlers><MissingReturnType errorLevel="info" /></issueHandlers></psalm>',
            ],
            $psalmXml,
        );
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);

        unlink(self::$tmpDir . '/src/FileWithErrors.php');
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\n\nfunction a() {\n    return 1;\n}\n");
        file_put_contents(self::$tmpDir . '/src/B.php', "<?php\n\nfunction b() {\n    return 1;\n}\n");
    }
}
