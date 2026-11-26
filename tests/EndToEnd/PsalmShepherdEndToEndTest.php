<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use Symfony\Component\Process\Process;

use function array_keys;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fread;
use function fwrite;
use function json_decode;
use function preg_match;
use function sort;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function stream_socket_accept;
use function stream_socket_get_name;
use function stream_socket_server;
use function strlen;
use function strpos;
use function substr;
use function trim;

use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;
use const PHP_VERSION;

/**
 * Tests which endpoint the psalm command sends the Shepherd report to and what it says when it can't send a report,
 * by launching a new process as if invoked by a real user.
 */
final class PsalmShepherdEndToEndTest extends PsalmEndToEndTestCase
{
    /** A port nothing listens on, so that the report is refused at once */
    private const UNREACHABLE_HOST = '127.0.0.1:1';

    #[Override]
    public function tearDown(): void
    {
        if (file_exists(self::$tmpDir . '/.git')) {
            self::recursiveRemoveDirectory(self::$tmpDir . '/.git');
        }

        parent::tearDown();
    }

    public function testPsalmWarnsWhenShepherdIsEnabledOutsideASupportedCi(): void
    {
        $process = $this->runPsalmWithShepherd(['--shepherd'], []);

        $this->assertStringContainsString(
            'Warning: Results not sent to Shepherd: no CI service with a GitHub repository detected',
            $process->getErrorOutput(),
        );
        $this->assertSame(0, $process->getExitCode());
    }

    public function testPsalmSendsNothingOutsideACiServiceInAGithubCheckout(): void
    {
        $this->initGitRepository();

        $process = $this->runPsalmWithShepherd(['--shepherd=http://' . self::UNREACHABLE_HOST], []);

        $this->assertStringContainsString('no CI service with a GitHub repository detected', $process->getErrorOutput());
        $this->assertStringNotContainsString('Results not sent to Shepherd (127.0.0.1)', $process->getErrorOutput());
    }

    public function testPsalmSendsNothingFromACiServiceWithoutAGithubRemote(): void
    {
        $this->initGitRepository('https://gitlab.com/psalm/example.git');

        $process = $this->runPsalmWithShepherd(['--shepherd=http://' . self::UNREACHABLE_HOST], ['CI' => 'true']);

        $this->assertStringContainsString('no CI service with a GitHub repository detected', $process->getErrorOutput());
        $this->assertStringNotContainsString('Results not sent to Shepherd (127.0.0.1)', $process->getErrorOutput());
    }

    public function testPsalmDoesNotWarnAboutTheCiWhenItSendsTheReport(): void
    {
        $process = $this->runPsalmWithShepherd(['--shepherd=http://' . self::UNREACHABLE_HOST], self::githubActions());

        $this->assertStringNotContainsString('no CI service with a GitHub repository detected', $process->getErrorOutput());
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

    public function testPsalmSendsTheReportOnlyToAnHttpUrl(): void
    {
        $process = $this->runPsalmWithShepherd(['--shepherd=ftp://127.0.0.1:1/report'], self::githubActions());

        $this->assertStringContainsString(
            'Warning: Results not sent to Shepherd: the URL must use http or https',
            $process->getErrorOutput(),
        );
        $this->assertSame(0, $process->getExitCode());
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

    public function testPsalmSendsTheReportOfAGitlabCiJobWithADetachedHead(): void
    {
        $head_of_checkout = $this->initGitRepository(
            'https://gitlab-ci-token:s3cret@github.com/psalm/example.git',
            true,
        );

        $report = $this->runPsalmAndReadTheReport([
            'CI' => 'true',
            'CI_COMMIT_REF_NAME' => 'feature',
            'CI_MERGE_REQUEST_IID' => '7',
        ]);

        $this->assertSame('generic', $report['build']['CI_NAME']);
        $this->assertSame('psalm', $report['build']['CI_REPO_OWNER']);
        $this->assertSame('example', $report['build']['CI_REPO_NAME']);
        $this->assertSame('feature', $report['build']['CI_BRANCH']);
        $this->assertSame('7', $report['build']['CI_PR_NUMBER']);
        $this->assertSame($head_of_checkout, $report['git']['head']['id']);
    }

    public function testPsalmSendsTheReportOfAnAzurePipelinesJob(): void
    {
        $this->initGitRepository('git@github.com:psalm/example.git', true);

        $report = $this->runPsalmAndReadTheReport(['TF_BUILD' => 'True', 'BUILD_SOURCEBRANCHNAME' => 'master']);

        $this->assertSame('psalm', $report['build']['CI_REPO_OWNER']);
        $this->assertSame('master', $report['build']['CI_BRANCH']);
        $this->assertArrayNotHasKey('CI_PR_NUMBER', $report['build']);
        // a run on the path of Clean.php only
        $this->assertFalse($report['analysis']['full']);
        $this->assertSame(1, $report['analysis']['file_count']);
    }

    public function testPsalmSendsEmptyGroupsAndTheAnalysisFlagsInEffect(): void
    {
        $this->initProject();
        // no CLI flag turns unused code detection off; --taint-analysis overrides runTaintAnalysis="false"
        $psalmXml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(
            self::$tmpDir . '/psalm.xml',
            str_replace('findUnusedCode="true"', 'findUnusedCode="false"', $psalmXml),
        );

        $report = $this->runPsalmAndReadTheRawReport(
            ['--no-cache', '--taint-analysis', '--php-version=8.1', self::$tmpDir . '/src/Clean.php'],
            self::githubActions(),
        );

        // objects even when empty, not the empty arrays PHP encodes by default
        $this->assertStringContainsString('"issue_counts":{"baselined":{},"info":{}}', $report);
        $this->assertStringContainsString('"target_php_version":"8.1"', $report);
        $this->assertStringContainsString('"find_unused_code":false', $report);
        $this->assertStringContainsString('"taint_analysis":true', $report);
    }

    public function testPsalmSendsTheIssueCountsOfTheBaselineAndTheConfig(): void
    {
        $this->initProject();
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\nfunction a(): int\n{\n    return 'a';\n}\n");
        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);

        file_put_contents(
            self::$tmpDir . '/src/B.php',
            "<?php\nfunction b(): int\n{\n    return 'b';\n}\n\nfunction c()\n{\n    return 1;\n}\n",
        );
        $psalmXml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(self::$tmpDir . '/psalm.xml', str_replace(
            '</psalm>',
            '<issueHandlers><InvalidReturnType errorLevel="info" /><MissingReturnType errorLevel="info" />'
                . '</issueHandlers></psalm>',
            $psalmXml,
        ));

        $report = $this->runPsalmAndReadTheRawReport(['--no-cache'], self::githubActions());

        // the InvalidReturnType of a() and foo() are info now, but the baseline still covers them (it has no
        // unused entry); those of b() and the MissingReturnType of c() are info, and not in the baseline; each issue
        // is in one group only, and the InvalidReturnStatement of b() is an error, sent in `issues`
        $this->assertStringContainsString(
            '"issue_counts":{"baselined":{"InvalidReturnStatement":2,"InvalidReturnType":2},'
                . '"info":{"InvalidReturnType":1,"MissingReturnType":1}}',
            $report,
        );
        $this->assertSame(['src/B.php InvalidReturnStatement'], self::sentIssues($report));

        // the key list, so that no key is added or removed unnoticed
        /** @var array{analysis: array<string, mixed>} $decoded */
        $decoded = self::decode($report);
        $this->assertSame(
            [
                'build',
                'git',
                'issues',
                'issue_counts',
                'analysis',
                'coverage',
                'level',
                'versions',
            ],
            array_keys($decoded),
        );
        // composer.json requires PHP >= 7.1, and the generated psalm.xml has no phpVersion
        $this->assertSame(
            [
                'full' => true,
                'file_count' => 4,
                'target_php_version' => '7.1',
                'find_unused_code' => true,
                'taint_analysis' => false,
            ],
            $decoded['analysis'],
        );
        $this->assertStringContainsString('"php":"' . PHP_VERSION . '"', $report);
    }

    public function testPsalmWritesTheReportToAFile(): void
    {
        $report_file = str_replace('\\', '/', self::$tmpDir) . '/src/report.json';
        // file:///C:/... on Windows
        $path = str_starts_with($report_file, '/') ? $report_file : '/' . $report_file;

        // curl would read the file and show it as the response
        file_put_contents($report_file, 'local-secret-value');

        $process = $this->runPsalmWithShepherd(['--debug', '--shepherd=file://' . $path], self::githubActions());

        $this->assertStringContainsString('Results written to ' . $path, $process->getErrorOutput());
        $this->assertStringNotContainsString('local-secret-value', $process->getErrorOutput());
        $this->assertFileExists($report_file);
        $report = (string) file_get_contents($report_file);
        $this->assertSame(
            ['build', 'git', 'issues', 'issue_counts', 'analysis', 'coverage', 'level', 'versions'],
            array_keys(self::decode($report)),
        );
        // encoded as it is sent
        $this->assertStringContainsString('"issue_counts":{"baselined":{},"info":{}}', $report);
        $this->assertSame(0, $process->getExitCode());
    }

    /**
     * Google Apps Script answers the POST with a 302 to the result, which curl fetches with a GET.
     * Google rejects that GET with HTTP 400 when it still carries the Content-Length of the POST body.
     */
    public function testPsalmSendsTheReportToAnEndpointThatRedirectsToItsResult(): void
    {
        $this->initProject();
        $server = stream_socket_server('tcp://127.0.0.1:0', $error_code, $error_message);
        $this->assertNotFalse($server, $error_message);
        $address = stream_socket_get_name($server, false);
        $this->assertIsString($address);

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '-c=' . self::$tmpDir . '/psalm.xml',
                '--no-cache',
                self::$tmpDir . '/src/Clean.php',
                '--shepherd=http://' . $address . '/exec',
            ],
            self::$tmpDir,
            self::githubActions() + self::ciEnvVarsToUnset() + self::agentEnvVarsToUnset(),
        );
        $process->start();

        try {
            [$connection, $post] = self::acceptRequest($server, $process);
            $this->assertStringStartsWith('POST /exec ', $post);
            fwrite($connection, "HTTP/1.1 302 Found\r\nLocation: /result\r\nContent-Length: 0\r\nConnection: close\r\n\r\n");
            fclose($connection);

            [$connection, $get] = self::acceptRequest($server, $process);
            $this->assertStringStartsWith('GET /result ', $get);
            $status = preg_match('/^Content-Length:/mi', $get) === 1 ? '400 Bad Request' : '200 OK';
            fwrite($connection, "HTTP/1.1 $status\r\nContent-Length: 0\r\nConnection: close\r\n\r\n");
            fclose($connection);
            $process->wait();
        } finally {
            fclose($server);
        }

        $this->assertStringContainsString('Results sent to Shepherd (127.0.0.1)', $process->getErrorOutput());
        $this->assertStringNotContainsString('Results not sent to Shepherd', $process->getErrorOutput());
    }

    /**
     * Accepts one request and reads it; the body is read only for a POST, so a GET is never waited on
     *
     * @param resource $server
     * @return array{resource, string} the connection and the request line with the headers
     */
    private static function acceptRequest($server, Process $process): array
    {
        $connection = null;
        while ($connection === null && $process->isRunning()) {
            $connection = @stream_socket_accept($server, 1) ?: null;
        }
        self::assertNotNull($connection, "Psalm sent no request\n" . $process->getErrorOutput());

        $request = '';
        while (!str_contains($request, "\r\n\r\n")) {
            $request .= (string) fread($connection, 8192);
        }
        $headers_end = strpos($request, "\r\n\r\n");
        self::assertIsInt($headers_end);
        $headers = substr($request, 0, $headers_end);

        if (str_starts_with($headers, 'POST ') && preg_match('/^Content-Length: (\d+)/mi', $headers, $matches) === 1) {
            $body = substr($request, $headers_end + 4);
            while (strlen($body) < (int) $matches[1]) {
                $body .= (string) fread($connection, 65_536);
            }
        }

        return [$connection, $headers];
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
        $this->initProject();

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
    private function initGitRepository(
        string $remote_url = 'https://github.com/psalm/example.git',
        bool $detached = false,
    ): string {
        $commands = [
            ['init', '--quiet'],
            ['remote', 'add', 'origin', $remote_url],
            [
                '-c', 'user.name=Psalm', '-c', 'user.email=psalm@example.com',
                'commit', '--quiet', '--allow-empty', '-m', 'Init',
            ],
        ];

        if ($detached) {
            $commands[] = ['checkout', '--quiet', '--detach'];
        }

        foreach ($commands as $command) {
            self::newProcess(['git', ...$command], self::$tmpDir)->mustRun();
        }

        return trim(self::newProcess(['git', 'rev-parse', 'HEAD'], self::$tmpDir)->mustRun()->getOutput());
    }

    /**
     * Runs Psalm with --shepherd on a local server that accepts one report
     *
     * @param array<string, string> $env
     * @return array{
     *     build: array<string, string>,
     *     git: array{head: array{id: string}},
     *     analysis: array{full: bool, file_count: int},
     * }
     */
    private function runPsalmAndReadTheReport(array $env): array
    {
        $this->initProject();

        /** @var array{build: array<string, string>, git: array{head: array{id: string}}, analysis: array{full: bool, file_count: int}} */
        return self::decode(
            $this->runPsalmAndReadTheRawReport(['--no-cache', self::$tmpDir . '/src/Clean.php'], $env),
        );
    }

    /**
     * Runs Psalm with --shepherd on a local server that accepts one report
     *
     * @param list<string> $args
     * @param array<string, string> $env
     * @return string the report as sent
     */
    private function runPsalmAndReadTheRawReport(array $args, array $env): string
    {
        $server = stream_socket_server('tcp://127.0.0.1:0', $error_code, $error_message);
        $this->assertNotFalse($server, $error_message);
        $address = stream_socket_get_name($server, false);
        $this->assertIsString($address);

        $process = self::newProcess(
            [
                PHP_BINARY,
                $this->psalm,
                '-c=' . self::$tmpDir . '/psalm.xml',
                ...$args,
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
        }

        return $body;
    }

    private function initProject(): void
    {
        $this->runPsalmInit(1);
        $psalmXml = file_get_contents(self::$tmpDir . '/psalm.xml');
        $psalmXml = str_replace('<psalm', '<psalm runTaintAnalysis="false"', (string)$psalmXml);
        file_put_contents(self::$tmpDir . '/psalm.xml', $psalmXml);
        file_put_contents(self::$tmpDir . '/src/Clean.php', "<?php\nfunction clean(): void {}\n");
    }

    /**
     * @return array<string, mixed>
     * @psalm-pure
     */
    private static function decode(string $report): array
    {
        /** @var array<string, mixed> */
        return json_decode($report, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string> the file and type of each issue sent, sorted
     * @psalm-pure
     */
    private static function sentIssues(string $report): array
    {
        /** @var list<array{file_name: string, type: string}> $issues */
        $issues = self::decode($report)['issues'];
        $sent = [];
        foreach ($issues as $issue) {
            $sent[] = $issue['file_name'] . ' ' . $issue['type'];
        }
        sort($sent);

        return $sent;
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
            'GITHUB_HEAD_REF' => false,
            'GITHUB_RUN_ID' => false,
            'TRAVIS' => false,
            'CIRCLECI' => false,
            'APPVEYOR' => false,
            'JENKINS_URL' => false,
            'SCRUTINIZER' => false,
            'CI' => false,
            'TF_BUILD' => false,
            'CI_COMMIT_BRANCH' => false,
            'CI_COMMIT_REF_NAME' => false,
            'BITBUCKET_BRANCH' => false,
            'BUILDKITE_BRANCH' => false,
            'BUILD_SOURCEBRANCHNAME' => false,
            'DRONE_BRANCH' => false,
            'CI_MERGE_REQUEST_IID' => false,
            'BITBUCKET_PR_ID' => false,
            'BUILDKITE_PULL_REQUEST' => false,
            'SYSTEM_PULLREQUEST_PULLREQUESTNUMBER' => false,
            'CI_COMMIT_PULL_REQUEST' => false,
            'DRONE_PULL_REQUEST' => false,
            'PSALM_SHEPHERD' => false,
        ];
    }
}
