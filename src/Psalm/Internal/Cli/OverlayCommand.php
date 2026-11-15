<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Closure;
use JsonException;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Overlay\Engine;
use Psalm\Internal\Overlay\IssueDiff;
use Psalm\Internal\Overlay\Job;
use Psalm\Internal\Overlay\OverlayFailure;
use Psalm\Internal\Overlay\Protocol;
use Psalm\Internal\Overlay\ReferenceIndex;
use Psalm\Internal\Overlay\Request;
use Psalm\Internal\Overlay\RuntimeDeclarations;
use Psalm\Internal\Overlay\SnapshotServer;
use Psalm\Internal\Overlay\Strategy;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\IssueBuffer;
use Psalm\Report;
use Symfony\Component\Filesystem\Path;

use function array_map;
use function array_slice;
use function ceil;
use function count;
use function defined;
use function extension_loaded;
use function fclose;
use function file_get_contents;
use function fmod;
use function function_exists;
use function fwrite;
use function getenv;
use function getmypid;
use function is_resource;
use function is_string;
use function json_decode;
use function max;
use function microtime;
use function proc_open;
use function sort;
use function str_replace;
use function str_starts_with;
use function stream_get_contents;
use function stream_select;
use function strpos;
use function substr;
use function usleep;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const PHP_BINARY;
use const PHP_EOL;
use const STDERR;
use const STDIN;
use const STDOUT;

/**
 * `psalm --overlay=ORIGINAL=REPLACEMENT` and `psalm --overlays-from=FILE`: the issues the overlays introduce
 *
 * @psalm-import-type IssueArray from IssueDiff
 * @psalm-import-type Removed from RuntimeDeclarations
 * @internal
 */
final class OverlayCommand
{
    /** Set for the process that answers a request with fresh runs of the overlays, see Strategy::FRESH */
    public const FRESH_ENV = OverlayExitGuard::FRESH_ENV;

    /** Runs both passes in this process, as on a platform without fork */
    public const NO_FORK_ENV = 'PSALM_OVERLAY_NO_FORK';
    /**
     * @param array<string, mixed> $options
     * @param array<int, string> $argv the arguments of this process
     * @param string $cwd where this process started: relative overlay paths are relative to it
     * @param Closure(): ProjectAnalyzer $rebootstrap
     * @param array<string, array<string, array{o: int, s: array<int, string>}>> $baseline
     */
    public static function run(
        array $options,
        array $argv,
        string $cwd,
        ProjectAnalyzer $project_analyzer,
        Closure $rebootstrap,
        IncludeCollector $include_collector,
        array $baseline,
    ): never {
        try {
            $request = self::readRequest($options, $cwd);

            // from the start of the process, as the caller counts
            $deadline = $request->timeout_ms !== null
                ? OverlayExitGuard::getStartTime() + (float) $request->timeout_ms / 1_000.0
                : null;
            if ($deadline !== null) {
                OverlayExitGuard::setDeadline($deadline);
            }

            $engine = self::createEngine($project_analyzer, $rebootstrap, $include_collector, $baseline, $request);
            $engine->validate($request);

            OverlayExitGuard::setPhase(OverlayFailure::WORKER_FAILED);

            $fork = getenv(self::FRESH_ENV) !== '1' && getenv(self::NO_FORK_ENV) !== '1' && self::canFork();
            if (!$fork) {
                [$strategy, $files, $base_issues, $overlay_issues]
                    = $engine->runWithoutFork($request, getenv(self::FRESH_ENV) === '1');
                $envelope = $engine->buildEnvelope($request, $files, $base_issues, $overlay_issues);
            } else {
                $job = self::runJob($engine, $request, $deadline, $argv, $cwd);
                $strategy = $job->strategy;
                $envelope = $job->envelope;

                if ($envelope === null) {
                    throw $job->failure ?? new OverlayFailure(OverlayFailure::WORKER_FAILED, 'No answer');
                }
            }

            if ($strategy === Strategy::VISIBILITY && getenv(Strategy::VERIFY_ENV) === '1') {
                self::verify($engine, $request, $envelope, $argv, $cwd, $fork);
            }
        } catch (OverlayFailure $failure) {
            OverlayExitGuard::fail($failure->kind, $failure->getMessage());
        }

        if (getenv(Strategy::DEBUG_ENV) === '1') {
            fwrite(STDERR, Protocol::encode([
                'strategy' => $strategy,
                'analyzed' => count($envelope['analyzed_files']),
            ]) . PHP_EOL);
        }

        // nothing interrupts the answer, and until it is out, a failure is not one
        OverlayExitGuard::disarm();

        if ($project_analyzer->stdout_report_options?->format === Report::TYPE_JSON) {
            if (fwrite(STDOUT, Protocol::encode($envelope) . "\n") === false) {
                OverlayExitGuard::fail(OverlayFailure::WORKER_FAILED, 'The answer could not be written');
            }
        } else {
            self::render($project_analyzer, $envelope['new_issues']);
        }

        OverlayExitGuard::answered();

        foreach ($envelope['new_issues'] as $issue) {
            if ($issue['severity'] === IssueData::SEVERITY_ERROR) {
                exit(2);
            }
        }

        exit(0);
    }

    /**
     * The snapshot process of `psalm --overlay-server`, see OverlayServer
     *
     * @param array<int, string> $argv
     * @param string $cwd where the server started: relative overlay paths are relative to it
     * @param Closure(): ProjectAnalyzer $rebootstrap
     * @param array<string, array<string, array{o: int, s: array<int, string>}>> $baseline
     * @param positive-int $threads
     */
    public static function serve(
        array $argv,
        string $cwd,
        ProjectAnalyzer $project_analyzer,
        Closure $rebootstrap,
        IncludeCollector $include_collector,
        array $baseline,
        int $threads,
    ): never {
        $engine = null;
        $failure = null;

        try {
            $engine = self::createEngine($project_analyzer, $rebootstrap, $include_collector, $baseline);
        } catch (OverlayFailure $e) {
            $failure = $e;
        }

        OverlayExitGuard::setPhase(OverlayFailure::WORKER_FAILED);

        (new SnapshotServer(
            $engine,
            $failure,
            $cwd,
            $threads,
            static fn(Request $request, ?float $deadline, array $removed): array
                => self::startFreshProcess($request, $argv, $cwd, $deadline, $removed),
        ))->run();

        OverlayExitGuard::answered();

        exit(0);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function readRequest(array $options, string $cwd): Request
    {
        if (isset($options['overlays-from'])) {
            $source = $options['overlays-from'];
            if (!is_string($source)) {
                throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, '--overlays-from takes one file');
            }

            $json = $source === '-' ? stream_get_contents(STDIN) : @file_get_contents(self::absolute($source, $cwd));
            if ($json === false) {
                throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, 'Cannot read ' . $source);
            }

            try {
                $request = Protocol::parseAnalyzeParams(
                    json_decode(CliUtils::sentByOperator($json), true, 512, JSON_THROW_ON_ERROR),
                    $cwd,
                );
            } catch (JsonException $e) {
                throw new OverlayFailure(OverlayFailure::INVALID_REQUEST, 'Invalid JSON: ' . $e->getMessage());
            }
        } else {
            $overlays = [];
            /** @var mixed $overlay */
            foreach ((array) ($options['overlay'] ?? []) as $overlay) {
                $separator = is_string($overlay) ? strpos($overlay, '=') : false;
                if (!is_string($overlay) || $separator === false || $separator === 0) {
                    throw new OverlayFailure(
                        OverlayFailure::INVALID_REQUEST,
                        '--overlay expects ORIGINAL=REPLACEMENT',
                    );
                }

                $overlays[] = [
                    'path' => substr($overlay, 0, $separator),
                    'replacement' => substr($overlay, $separator + 1),
                ];
            }

            $request = Protocol::parseAnalyzeParams(['overlays' => $overlays], $cwd);
        }

        if ($request->timeout_ms === null
            && isset($options['overlay-timeout'])
            && is_string($options['overlay-timeout'])
        ) {
            return new Request(
                $request->overlays,
                $request->whole_project,
                max(1, (int) ceil((float) $options['overlay-timeout'] * 1_000.0)),
            );
        }

        return $request;
    }

    /**
     * @param Closure(): ProjectAnalyzer $rebootstrap
     * @param array<string, array<string, array{o: int, s: array<int, string>}>> $baseline
     */
    public static function createEngine(
        ProjectAnalyzer $project_analyzer,
        Closure $rebootstrap,
        IncludeCollector $include_collector,
        array $baseline,
        ?Request $fresh_request = null,
    ): Engine {
        $codebase = $project_analyzer->getCodebase();
        $config = $project_analyzer->getConfig();

        $cache = $codebase->file_reference_provider->cache;
        $index = $cache ? ReferenceIndex::load($cache) : null;

        if ($index === null) {
            throw new OverlayFailure(
                OverlayFailure::REFERENCES_NOT_RECORDED,
                'The cache has no reference index: run `psalm --record-references` first',
            );
        }

        $stale = $index->getStaleReason($project_analyzer->initProjectFiles(), $config);
        if ($stale !== null) {
            throw new OverlayFailure(
                OverlayFailure::REFERENCES_STALE,
                'The reference index is out of date (' . $stale . '): run `psalm --record-references` again',
            );
        }

        $fresh = getenv(self::FRESH_ENV) === '1' && $fresh_request !== null;
        [$project_files, $taint] = $fresh
            ? Engine::scanSnapshot($project_analyzer, $fresh_request->overlays, RuntimeDeclarations::fromEnvironment())
            : Engine::scanSnapshot($project_analyzer);

        $codebase = $project_analyzer->getCodebase();
        $not_checked = [];
        if ($codebase->find_unused_code || self::reportsClassMustBeFinal($config, $project_files)) {
            $not_checked[] = 'unused_code';
        }
        if ($taint) {
            $not_checked[] = 'taint';
        }
        if ($config->suggest_purity_annotations) {
            $not_checked[] = 'purity_suggestions';
        }

        if (getenv(Strategy::DEBUG_ENV) === '1') {
            $populated = 0;
            foreach (ClassLikeStorageProvider::getAll() as $storage) {
                $populated += $storage->populated ? 1 : 0;
            }
            fwrite(STDERR, Protocol::encode(['populated_after_scan' => $populated]) . PHP_EOL);
        }

        $registry_files = array_map(
            static fn(string $file): string => str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file),
            $include_collector->getFilteredIncludedFiles(),
        );

        return new Engine(
            $project_analyzer,
            $project_files,
            $index,
            $baseline,
            $registry_files,
            $not_checked,
            $taint,
            $rebootstrap,
        );
    }

    /**
     * @param array<int, string> $argv
     * @param string $cwd see startFreshProcess()
     */
    private static function runJob(Engine $engine, Request $request, ?float $deadline, array $argv, string $cwd): Job
    {
        $job = new Job(
            $engine,
            $request,
            $deadline,
            static fn(): ?array => null,
            static function (): void {
            },
            static fn(Request $request, ?float $deadline, array $removed): array
                => self::startFreshProcess($request, $argv, $cwd, $deadline, $removed),
        );

        // the deadline of the process stops what the job started
        OverlayExitGuard::setTeardown(static function () use ($job): void {
            $job->fail(new OverlayFailure(OverlayFailure::TIMEOUT, 'The request did not finish in time'));
        });

        $job->start();

        while (!$job->isDone()) {
            $read = $job->getStreams();
            $write = null;
            $except = null;

            if ($read) {
                $timeout = $deadline !== null ? max(0.0, $deadline - microtime(true)) : 1.0;
                @stream_select($read, $write, $except, (int) $timeout, (int) (fmod($timeout, 1.0) * 1_000_000.0));
            } else {
                usleep(10_000);
            }

            $job->poll();
        }

        OverlayExitGuard::setTeardown(null);

        return $job;
    }

    /**
     * Psalm in a new process, answering the request with fresh runs of the overlays. It leads a process group.
     *
     * @param array<int, string> $argv
     * @param string $cwd where this process started, which the paths in $argv are relative to
     * @param float|null $deadline when the request times out, a microtime(true)
     * @param Removed $removed see RuntimeDeclarations
     * @return array{resource, resource}
     */
    public static function startFreshProcess(
        Request $request,
        array $argv,
        string $cwd,
        ?float $deadline,
        array $removed,
    ): array {
        $command = [PHP_BINARY, Path::makeAbsolute($argv[0] ?? '', $cwd), ...self::getAnalysisArguments($argv)];
        $command[] = '--overlays-from=-';
        $command[] = '--output-format=json';

        $env = getenv();
        $env[self::FRESH_ENV] = '1';
        // killed on the deadline: a process the restarter would leave behind is not
        $env['PSALM_ALLOW_XDEBUG'] = '1';
        unset($env[OverlayServer::ROLE_ENV], $env[OverlayExitGuard::DEADLINE_ENV], $env[OverlayExitGuard::GROUP_ENV]);
        if ($deadline !== null) {
            // from its start, as long as it takes to start
            $env[OverlayExitGuard::DEADLINE_ENV] = (string) $deadline;
        }
        // stopped at once with what it starts, by this process or, once this process is gone, by its watchdog
        $env[OverlayExitGuard::GROUP_ENV] = '1';
        $env[OverlayExitGuard::OWNER_ENV] = (string) getmypid();
        // it scans the overlays only: what they no longer declare comes from the overlay pass
        $env[RuntimeDeclarations::REMOVED_ENV] = RuntimeDeclarations::encode($removed);

        $process = proc_open(
            CliUtils::withoutShell($command),
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => STDERR],
            $pipes,
            $cwd,
            $env,
        );
        if (!is_resource($process) || !isset($pipes[0], $pipes[1])) {
            throw new OverlayFailure(OverlayFailure::WORKER_FAILED, 'Could not start a fresh Psalm process');
        }

        fwrite($pipes[0], Protocol::encode($request->toParams()));
        fclose($pipes[0]);

        return [$process, $pipes[1]];
    }

    /**
     * The arguments of this process that configure the analysis, without those that make the request
     *
     * @param array<int, string> $argv
     * @return list<string>
     * @psalm-pure
     */
    public static function getAnalysisArguments(array $argv): array
    {
        $request_options = ['--overlay', '--overlays-from', '--overlay-timeout', '--output-format', '--threads',
            '--overlay-server'];
        $arguments = [];
        $skip_value = false;

        foreach (array_slice($argv, 1) as $argument) {
            if ($skip_value) {
                $skip_value = false;
                continue;
            }

            foreach ($request_options as $option) {
                if ($argument === $option) {
                    $skip_value = $option !== '--overlay-server';
                    continue 2;
                }

                if (str_starts_with($argument, $option . '=')) {
                    continue 2;
                }
            }

            $arguments[] = $argument;
        }

        return $arguments;
    }

    /**
     * The answer the request gets when visibility changes are analysed like any other signature change
     *
     * @param array{new_issues: list<IssueArray>, analyzed_files: list<string>, ...} $envelope
     * @param array<int, string> $argv
     * @param bool $fork whether the passes run in forked processes, or in this one
     */
    private static function verify(
        Engine $engine,
        Request $request,
        array $envelope,
        array $argv,
        string $cwd,
        bool $fork,
    ): void {
        $signature_request = new Request($request->overlays, $request->whole_project, null, Strategy::SIGNATURE);

        if ($fork) {
            $expected_envelope = self::runJob($engine, $signature_request, null, $argv, $cwd)->envelope;
        } else {
            // the snapshot of the first answer is populated
            $engine = $engine->rebuilt();
            [, $files, $base_issues, $overlay_issues] = $engine->runWithoutFork($signature_request, false);
            $expected_envelope = $engine->buildEnvelope($signature_request, $files, $base_issues, $overlay_issues);
        }

        /** @param list<IssueArray> $issues */
        $describe = static fn(array $issues): array => array_map(
            /** @param IssueArray $issue */
            static fn(array $issue): string => $issue['file_name'] . ':' . $issue['line_from'] . ' '
                . $issue['type'] . ' ' . $issue['message'],
            $issues,
        );

        $expected = $expected_envelope !== null ? $describe($expected_envelope['new_issues']) : ['(failed)'];
        $actual = $describe($envelope['new_issues']);
        sort($expected);
        sort($actual);

        fwrite(STDERR, Protocol::encode([
            'verify' => $expected === $actual ? 'same' : 'different',
            'visibility' => $actual,
            'signature' => $expected,
            'visibility_files' => count($envelope['analyzed_files']),
            'signature_files' => $expected_envelope !== null ? count($expected_envelope['analyzed_files']) : null,
        ]) . PHP_EOL);
    }

    /**
     * @param list<IssueArray> $new_issues
     */
    private static function render(ProjectAnalyzer $project_analyzer, array $new_issues): void
    {
        $report_options = $project_analyzer->stdout_report_options;
        if ($report_options === null) {
            return;
        }

        $issues_data = [];
        foreach ($new_issues as $issue) {
            /** @var array{severity: IssueData::SEVERITY_*, line_from: int, line_to: int, type: string,
             *     message: string, file_name: string, file_path: string, snippet: string, selected_text: string,
             *     from: int, to: int, snippet_from: int, snippet_to: int, column_from: int, column_to: int,
             *     shortcode: int, error_level: int, link: string} $issue */
            $issues_data[$issue['file_path']][] = new IssueData(
                $issue['severity'],
                $issue['line_from'],
                $issue['line_to'],
                $issue['type'],
                $issue['message'],
                $issue['file_name'],
                $issue['file_path'],
                $issue['snippet'],
                $issue['selected_text'],
                $issue['from'],
                $issue['to'],
                $issue['snippet_from'],
                $issue['snippet_to'],
                $issue['column_from'],
                $issue['column_to'],
                $issue['shortcode'],
                $issue['error_level'],
                null,
                null,
                null,
                $issue['link'] !== '' ? $issue['link'] : null,
            );
        }

        fwrite(STDOUT, IssueBuffer::getOutput($issues_data, $report_options));
    }

    /**
     * @psalm-pure
     */
    private static function canFork(): bool
    {
        return !defined('PHP_WINDOWS_VERSION_MAJOR')
            && extension_loaded('pcntl')
            && extension_loaded('posix')
            && function_exists('pcntl_fork');
    }

    /**
     * ClassMustBeFinal comes from the pass over the whole project whether unused code is found or not
     *
     * @param array<string, string> $project_files
     */
    private static function reportsClassMustBeFinal(Config $config, array $project_files): bool
    {
        foreach ($project_files as $file) {
            if ($config->getReportingLevelForFile('ClassMustBeFinal', $file) !== Config::REPORT_SUPPRESS) {
                return true;
            }
        }

        return false;
    }

    private static function absolute(string $path, string $cwd): string
    {
        return Path::makeAbsolute($path, $cwd);
    }
}
