<?php

declare(strict_types=1);

namespace Psalm\Tests\EndToEnd;

use Override;
use Symfony\Component\Process\Process;

use function defined;
use function extension_loaded;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fread;
use function function_exists;
use function fwrite;
use function implode;
use function json_decode;
use function preg_match;
use function sort;
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
    }

    public function testPsalmSendsAnEmptyBaselinedObjectWhenNoIssueIsBaselined(): void
    {
        $this->initProject();

        $report = $this->runPsalmAndReadTheRawReport(['--no-cache'], self::githubActions());

        $this->assertStringContainsString('"baselined":{}', $report);

        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);
        $report = $this->runPsalmAndReadTheRawReport(['--no-cache', '--ignore-baseline'], self::githubActions());

        $this->assertStringContainsString('"baselined":{}', $report);
        $this->assertSame(
            ['src/FileWithErrors.php InvalidReturnStatement', 'src/FileWithErrors.php InvalidReturnType'],
            self::sentIssues($report),
        );

        // the baseline is in use, but none of its entries matches the new error
        file_put_contents(self::$tmpDir . '/src/FileWithErrors.php', "<?php\nfunction e()\n{\n    return 1;\n}\n");
        $report = $this->runPsalmAndReadTheRawReport(['--no-cache'], self::githubActions());

        $this->assertStringContainsString('"baselined":{}', $report);
        $this->assertStringContainsString('"info":{}', $report);
        $this->assertSame(
            [
                'src/FileWithErrors.php MissingReturnType',
                'src/FileWithErrors.php UnusedBaselineEntry',
                'src/FileWithErrors.php UnusedBaselineEntry',
            ],
            self::sentIssues($report),
        );
    }

    public function testPsalmSendsTheNumberOfBaselinedIssuesByType(): void
    {
        $this->initProject();
        $this->writeFilesWithErrors();
        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);

        // c() is fixed, which leaves its baseline entry unused, and d() is a new error
        file_put_contents(self::$tmpDir . '/src/B.php', "<?php\nfunction b(): int\n{\n    return 'b';\n}\n");
        file_put_contents(self::$tmpDir . '/src/C.php', "<?php\nfunction d(): int\n{\n    return 'd';\n}\n");

        $report = $this->runPsalmAndReadTheRawReport(['--no-cache'], self::githubActions());

        $this->assertStringContainsString('"baselined":{"InvalidReturnStatement":3,"InvalidReturnType":3}', $report);
        $this->assertSame(
            ['src/B.php UnusedBaselineEntry', 'src/C.php InvalidReturnStatement', 'src/C.php InvalidReturnType'],
            self::sentIssues($report),
        );
    }

    public function testPsalmCountsOnlyTheInfoIssuesTheBaselineCovers(): void
    {
        $this->initProject();
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\nfunction a(): int\n{\n    return 'a';\n}\n");
        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);

        $this->writeFilesWithErrors();
        $psalmXml = (string) file_get_contents(self::$tmpDir . '/psalm.xml');
        file_put_contents(self::$tmpDir . '/psalm.xml', str_replace(
            '</psalm>',
            '<issueHandlers><InvalidReturnType errorLevel="info" /><MissingReturnType errorLevel="info" />'
                . '</issueHandlers></psalm>',
            $psalmXml,
        ));

        $report = $this->runPsalmAndReadTheRawReport(['--no-cache'], self::githubActions());

        // the InvalidReturnType of a() and foo() are info now, but the baseline still covers them (it has no
        // unused entry); those of b() and the MissingReturnType of c() are info, and not in the baseline
        $this->assertStringContainsString('"baselined":{"InvalidReturnStatement":2,"InvalidReturnType":2}', $report);
        $this->assertStringContainsString('"info":{"InvalidReturnType":1,"MissingReturnType":1}', $report);
        $this->assertSame(['src/B.php InvalidReturnStatement'], self::sentIssues($report));
        // all the issues Psalm can fix, whether reported as errors, as info or covered by the baseline
        $this->assertSame(['InvalidReturnType' => 3, 'MissingReturnType' => 1], self::decode($report)['fixable']);
    }

    public function testPsalmSendsTheSameBaselinedIssuesWithTheCacheAndWithThreads(): void
    {
        $this->initProject();
        $this->writeFilesWithErrors();
        $this->runPsalm(['--set-baseline'], self::$tmpDir, true);
        file_put_contents(self::$tmpDir . '/src/C.php', "<?php\nfunction d(): int\n{\n    return 'd';\n}\n");

        $without_cache = self::decode(
            $this->runPsalmAndReadTheRawReport(['--no-cache', '--threads=1'], self::githubActions()),
        );
        $this->assertSame(
            ['InvalidReturnStatement' => 3, 'InvalidReturnType' => 3, 'MissingReturnType' => 1],
            $without_cache['baselined'],
        );

        // the conditions under which Psalm::getThreads() allows more than one thread
        $can_fork = !defined('PHP_WINDOWS_VERSION_MAJOR')
            && extension_loaded('pcntl')
            && extension_loaded('posix')
            && function_exists('pcntl_fork');

        // with a cold cache (--set-baseline changed psalm.xml, which the cache is keyed by), then a warm one, then
        // a warm one with forked workers, as there are more files than threads
        foreach ([['--threads=1'], ['--threads=1'], ['--threads=2', '--debug']] as $args) {
            $report = self::decode($this->runPsalmAndReadTheRawReport($args, self::githubActions(), $error_output));

            $this->assertSame($without_cache['baselined'], $report['baselined'], implode(' ', $args));
            $this->assertSame($without_cache['fixable'], $report['fixable'], implode(' ', $args));
            $this->assertSame($without_cache['issues'], $report['issues'], implode(' ', $args));

            /** @var array{seconds: mixed, peak_memory: mixed, threads: mixed} $run */
            $run = $report['run'];
            $this->assertSame($args[0] === '--threads=2' && $can_fork ? 2 : 1, $run['threads'], implode(' ', $args));
            $this->assertIsFloat($run['seconds']);
            $this->assertGreaterThanOrEqual(0, $run['seconds']);
            $this->assertIsInt($run['peak_memory']);
            $this->assertGreaterThan(0, $run['peak_memory']);
        }

        if ($can_fork) {
            $this->assertStringContainsString('Forking analysis', $error_output);
        }
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
     * @return array{build: array<string, string>, git: array{head: array{id: string}}}
     */
    private function runPsalmAndReadTheReport(array $env): array
    {
        $this->initProject();

        /** @var array{build: array<string, string>, git: array{head: array{id: string}}} */
        return self::decode(
            $this->runPsalmAndReadTheRawReport(['--no-cache', self::$tmpDir . '/src/Clean.php'], $env),
        );
    }

    /**
     * Runs Psalm with --shepherd on a local server that accepts one report
     *
     * @param list<string> $args
     * @param array<string, string> $env
     * @param-out string $error_output
     * @return string the report as sent
     */
    private function runPsalmAndReadTheRawReport(array $args, array $env, ?string &$error_output = null): string
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
            $error_output = $process->getErrorOutput();
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
     * Next to the errors of FileWithErrors.php: two of the same types in a() and b(), and a MissingReturnType in c()
     */
    private function writeFilesWithErrors(): void
    {
        file_put_contents(self::$tmpDir . '/src/A.php', "<?php\nfunction a(): int\n{\n    return 'a';\n}\n");
        file_put_contents(
            self::$tmpDir . '/src/B.php',
            "<?php\nfunction b(): int\n{\n    return 'b';\n}\n\nfunction c()\n{\n    return 1;\n}\n",
        );
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
