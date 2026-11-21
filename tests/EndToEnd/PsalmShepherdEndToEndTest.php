<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Symfony\Component\Process\Process;

use function fclose;
use function file_get_contents;
use function file_put_contents;
use function fread;
use function fwrite;
use function json_decode;
use function preg_match;
use function str_contains;
use function str_replace;
use function stream_socket_accept;
use function stream_socket_get_name;
use function stream_socket_server;
use function strlen;
use function strpos;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;
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

    public function testPsalmSendsTheHeadOfAGithubActionsPullRequestAsItsCommit(): void
    {
        $head_of_checkout = $this->initGitRepository();
        $event = __DIR__ . '/../fixtures/GithubEvents/pull_request.json';

        $report = $this->runPsalmAndReadTheReport(self::githubActions() + [
            'GITHUB_EVENT_NAME' => 'pull_request',
            'GITHUB_EVENT_PATH' => $event,
        ]);

        $this->assertSame('42', $report['build']['CI_PR_NUMBER']);
        $this->assertSame('psalm', $report['build']['CI_PR_REPO_OWNER']);
        $this->assertSame('0123456789abcdef0123456789abcdef01234567', $report['git']['head']['id']);
        $this->assertNotSame($head_of_checkout, $report['git']['head']['id']);
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

    /** @return string the hash of the commit the project is on */
    private function initGitRepository(): string
    {
        foreach ([
            ['init', '--quiet'],
            ['remote', 'add', 'origin', 'https://github.com/psalm/example.git'],
            ['-c', 'user.name=Psalm', '-c', 'user.email=psalm@example.com', 'commit', '--quiet', '--allow-empty', '-m', 'Init'],
        ] as $command) {
            self::newProcess(['git', ...$command], self::$tmpDir)->mustRun();
        }

        return trim(self::newProcess(['git', 'rev-parse', 'HEAD'], self::$tmpDir)->mustRun()->getOutput());
    }

    /**
     * Runs Psalm with --shepherd on a local server that accepts one report
     *
     * @param array<string, string> $env
     * @return array{build: array<string, string>, git: array{head: array{id: string}}}
     */
    private function runPsalmAndReadTheReport(array $env): array
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $error_code, $error_message);
        $this->assertNotFalse($server, $error_message);
        $address = stream_socket_get_name($server, false);
        $this->assertIsString($address);

        $this->runPsalmInit(1);
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/Clean.php', "<?php\nfunction clean(): void {}\n");

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '--no-cache',
                '-c=' . self::$tmpDir . '/psalm.xml',
                self::$tmpDir . '/src/Clean.php',
                '--shepherd=http://' . $address,
            ],
            self::$tmpDir,
            $env + self::ciEnvVarsToUnset() + self::agentEnvVarsToUnset(),
        );
        $process->start();

        try {
            $connection = null;
            while ($connection === null && $process->isRunning()) {
                $connection = @stream_socket_accept($server, 1) ?: null;
            }

            $this->assertNotNull($connection, "Psalm sent no report\n" . $process->getErrorOutput());

            $request = '';
            while (!str_contains($request, "\r\n\r\n")) {
                $request .= (string) fread($connection, 8192);
            }

            $headers_end = strpos($request, "\r\n\r\n");
            $this->assertIsInt($headers_end);
            $headers = substr($request, 0, $headers_end);
            $body = substr($request, $headers_end + 4);
            $this->assertSame(1, preg_match('/^Content-Length: (\d+)/mi', $headers, $matches));
            $content_length = (int) ($matches[1] ?? 0);
            while (strlen($body) < $content_length) {
                $body .= (string) fread($connection, 65_536);
            }

            fwrite($connection, "HTTP/1.1 200 OK\r\nContent-Length: 0\r\nConnection: close\r\n\r\n");
            fclose($connection);
            $process->wait();
        } finally {
            fclose($server);
            self::recursiveRemoveDirectory(self::$tmpDir . '/.git');
        }

        /** @var array{build: array<string, string>, git: array{head: array{id: string}}} */
        return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
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
            'GITHUB_EVENT_NAME' => false,
            'TRAVIS' => false,
            'CIRCLECI' => false,
            'APPVEYOR' => false,
            'JENKINS_URL' => false,
            'SCRUTINIZER' => false,
            'PSALM_SHEPHERD' => false,
        ];
    }
}
