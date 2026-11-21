<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Symfony\Component\Process\Process;

use function file_get_contents;
use function file_put_contents;
use function str_replace;

use const PHP_BINARY;

/**
 * Tests which endpoint the psalm command sends the Shepherd report to and what it says when it can't send a report,
 * by launching a new process as if invoked by a real user.
 */
final class PsalmShepherdEndToEndTest extends PsalmEndToEndTestCase
{
    /** A port nothing listens on, so that the report is refused at once */
    private const UNREACHABLE_HOST = '127.0.0.1:1';

    public function testPsalmWarnsWhenShepherdIsEnabledOutsideASupportedCi(): void
    {
        $process = $this->runPsalmWithShepherd(['--shepherd'], []);

        $this->assertStringContainsString(
            'Warning: Results not sent to Shepherd: no supported CI service detected',
            $process->getErrorOutput(),
        );
        $this->assertSame(0, $process->getExitCode());
    }

    public function testPsalmDoesNotWarnAboutTheCiWhenItSendsTheReport(): void
    {
        $process = $this->runPsalmWithShepherd(['--shepherd=http://' . self::UNREACHABLE_HOST], self::githubActions());

        $this->assertStringNotContainsString('no supported CI service detected', $process->getErrorOutput());
        $this->assertStringContainsString('Results not sent to Shepherd (127.0.0.1)', $process->getErrorOutput());
        $this->assertSame(0, $process->getExitCode());
    }

    public function testPsalmSendsTheReportToTheGivenUrlAsItIs(): void
    {
        $process = $this->runPsalmWithShepherd(
            ['--debug', '--shepherd=http://' . self::UNREACHABLE_HOST],
            self::githubActions(),
        );

        // a custom endpoint may listen on /, so no path is added
        $this->assertStringContainsString(
            'Shepherd endpoint: http://' . self::UNREACHABLE_HOST . "\n",
            $process->getErrorOutput(),
        );
    }

    public function testPsalmSendsTheReportToTheGivenPath(): void
    {
        $process = $this->runPsalmWithShepherd(
            ['--debug', '--shepherd=http://' . self::UNREACHABLE_HOST . '/hooks/psalm'],
            self::githubActions(),
        );

        $this->assertStringContainsString(
            'Shepherd endpoint: http://' . self::UNREACHABLE_HOST . '/hooks/psalm' . "\n",
            $process->getErrorOutput(),
        );
    }

    public function testPsalmAddsOnlyTheSchemeToAShepherdHost(): void
    {
        $process = $this->runPsalmWithShepherd(
            ['--debug', '--shepherd=' . self::UNREACHABLE_HOST],
            self::githubActions(),
        );

        $this->assertStringContainsString(
            'Shepherd endpoint: https://' . self::UNREACHABLE_HOST . "\n",
            $process->getErrorOutput(),
        );
    }

    public function testPsalmSendsTheReportToTheUrlOfTheShepherdEnvironmentVariable(): void
    {
        $process = $this->runPsalmWithShepherd(
            ['--debug'],
            ['PSALM_SHEPHERD' => 'http://' . self::UNREACHABLE_HOST . '/hooks/psalm'] + self::githubActions(),
        );

        $this->assertStringContainsString(
            'Shepherd endpoint: http://' . self::UNREACHABLE_HOST . '/hooks/psalm' . "\n",
            $process->getErrorOutput(),
        );
    }

    /**
     * @return array<string, string>
     * @psalm-pure
     */
    private static function githubActions(): array
    {
        return [
            'GITHUB_ACTIONS' => 'true',
            'GITHUB_REF' => 'refs/heads/master',
            'GITHUB_REPOSITORY' => 'psalm/example',
        ];
    }

    /**
     * @param list<string> $args
     * @param array<string, string> $env
     */
    private function runPsalmWithShepherd(array $args, array $env): Process
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
                '--no-cache',
                '-c=' . self::$tmpDir . '/psalm.xml',
                self::$tmpDir . '/src/Clean.php',
                ...$args,
            ],
            self::$tmpDir,
            $env + self::ciEnvVarsToUnset() + self::agentEnvVarsToUnset(),
        );
        $process->run();

        return $process;
    }

    /**
     * The variables that make a Psalm run look like it is on a supported CI service
     * (see BuildInfoCollector), unset so that a test doesn't depend on the CI it runs on
     *
     * @return array<string, false>
     * @psalm-pure
     */
    private static function ciEnvVarsToUnset(): array
    {
        return [
            'GITHUB_ACTIONS' => false,
            'GITHUB_EVENT_PATH' => false,
            'TRAVIS' => false,
            'CIRCLECI' => false,
            'APPVEYOR' => false,
            'JENKINS_URL' => false,
            'SCRUTINIZER' => false,
            'PSALM_SHEPHERD' => false,
        ];
    }
}
