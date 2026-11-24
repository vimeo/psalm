<?php

declare(strict_types=1);

namespace Psalm\Internal\Perf;

use Composer\XdebugHandler\XdebugHandler;
use Fidry\CpuCoreCounter\CpuCoreCounter;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;

use function array_filter;
use function array_slice;
use function count;
use function dirname;
use function error_clear_last;
use function error_get_last;
use function file_put_contents;
use function function_exists;
use function fwrite;
use function get_loaded_extensions;
use function getmypid;
use function hash_file;
use function ini_get;
use function is_dir;
use function is_file;
use function json_encode;
use function max;
use function min;
use function mkdir;
use function number_format;
use function opcache_get_status;
use function php_uname;
use function sort;
use function sprintf;
use function str_ends_with;
use function strlen;
use function usort;

use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;
use const PHP_INT_MAX;
use const PHP_OS_FAMILY;
use const PHP_VERSION;
use const STDERR;

/**
 * Prints the `--debug-performance` summary and writes its performance profile
 *
 * @internal
 * @psalm-import-type Measurements from Collector
 * @psalm-type Counts = array{
 *     classlikes: int,
 *     functions: int,
 *     methods: int,
 *     file_storages: int,
 * }
 */
final class DumpWriter
{
    private const SUMMARY_ROWS = 10;

    /** A static page that reads the profile in the browser and sends it nowhere */
    private const VIEWER = 'https://psalm.dev/profile/';

    /** A statement or function-like that took less of its own time is left out of the summary */
    private const MIN_ROW_US = 10_000;

    /**
     * @param Measurements $measurements
     * @param bool $summary_to_stderr the report owns stdout: the summary goes to stderr, through the progress
     */
    public static function emit(
        array $measurements,
        ProjectAnalyzer $project_analyzer,
        bool $print_summary,
        bool $summary_to_stderr,
    ): void {
        $path = Collector::dumpPath();

        $counts = self::counts();

        $failure = $path === null
            ? null
            : self::write(
                $path,
                [
                    'schema' => 1,
                    'env' => self::env($project_analyzer),
                    'counts' => $counts,
                ] + $measurements,
            );

        if ($print_summary) {
            $summary = self::summary($measurements, $counts, $path);

            if ($summary_to_stderr) {
                $project_analyzer->progress->write($summary);
            } else {
                echo $summary;
            }
        }

        if ($path === null) {
            return;
        }

        if ($failure === null) {
            $project_analyzer->progress->write(
                'Performance profile written to ' . $path . ', open it in ' . self::VIEWER . PHP_EOL,
            );
        } else {
            // not through the progress: that one says nothing with --no-progress
            fwrite(STDERR, 'Warning: ' . $failure . PHP_EOL);
        }
    }

    /**
     * @param Measurements $measurements
     * @param Counts $counts
     * @psalm-pure
     */
    public static function summary(array $measurements, array $counts, ?string $path): string
    {
        $totals = $measurements['totals'];
        $out = '-----------------' . "\n" . 'Performance' . "\n" . '-----------------' . "\n\n";

        $out .= 'Stages' . "\n";

        // a stage that comes back (the scan of the stubs, then of the project) shows as one row
        $stages = [];
        foreach ($measurements['stages'] as $stage) {
            $stages[$stage['name']] = [
                'wall_us' => ($stages[$stage['name']]['wall_us'] ?? 0) + $stage['wall_us'],
                'cpu_us' => ($stages[$stage['name']]['cpu_us'] ?? 0) + $stage['cpu_us'],
            ];
        }

        // stages, each followed by its steps that took at least 1 % of the run, all in the same columns
        $rows = [];
        foreach ($stages as $name => $stage) {
            $rows[] = ['  ' . $name, $stage['wall_us'], $stage['cpu_us']];

            $steps = [];
            foreach ($measurements['steps'] as $step) {
                if ($step['stage'] === $name) {
                    $steps[$step['name']] = [
                        'wall_us' => ($steps[$step['name']]['wall_us'] ?? 0) + $step['wall_us'],
                        'cpu_us' => ($steps[$step['name']]['cpu_us'] ?? 0) + $step['cpu_us'],
                    ];
                }
            }

            foreach ($steps as $step_name => $step) {
                if ($totals['wall_us'] > 0 && 100 * $step['wall_us'] >= $totals['wall_us']) {
                    $rows[] = ['    ' . $step_name, $step['wall_us'], $step['cpu_us']];
                }
            }
        }

        $name_width = 20;
        foreach ($rows as [$label]) {
            $name_width = max($name_width, strlen($label) + 1);
        }

        foreach ($rows as [$label, $wall_us, $cpu_us]) {
            $out .= sprintf(
                "%-{$name_width}s %9s wall %9s cpu %5.1f%%\n",
                $label,
                self::seconds($wall_us),
                self::seconds($cpu_us),
                $totals['wall_us'] > 0 ? 100.0 * (float) $wall_us / (float) $totals['wall_us'] : 0.0,
            );
        }

        $out .= sprintf(
            "\nPeak memory %s. Counts: %d class-likes, %d functions, %d methods, %d file storages, "
            . "%d parses, %d parser cache hits\n",
            self::megabytes($totals['php_peak_bytes']),
            $counts['classlikes'],
            $counts['functions'],
            $counts['methods'],
            $counts['file_storages'],
            $measurements['parser']['parses'],
            $measurements['parser']['cache_hits'],
        );

        $analysis_us = 0;
        foreach ($measurements['files'] as $file) {
            $analysis_us += $file['op'] === 'analysis' ? $file['wall_us'] : 0;
        }

        $plugins = array_filter(
            $measurements['plugins'],
            static fn(array $plugin): bool => $plugin['init_us'] >= self::MIN_ROW_US,
        );

        if ($plugins !== []) {
            $out .= "\n" . 'Plugin initialisation' . "\n";
        }

        foreach ($plugins as $plugin) {
            $out .= sprintf("  %9s  %s\n", self::seconds($plugin['init_us']), $plugin['class']);
        }

        $hooks = array_filter(
            $measurements['hooks'],
            static fn(array $hook): bool => $hook['wall_us'] >= self::MIN_ROW_US,
        );

        if ($hooks !== []) {
            $out .= "\n"
                . 'Plugins and providers (time in handlers, as a share of file analysis time over all processes)'
                . "\n";
        }

        foreach (array_slice($hooks, 0, self::SUMMARY_ROWS) as $hook) {
            $out .= sprintf(
                "  %9s %5.1f%% %9d calls  %s (%s)\n",
                self::seconds($hook['wall_us']),
                $analysis_us > 0 ? 100.0 * (float) $hook['wall_us'] / (float) $analysis_us : 0.0,
                $hook['calls'],
                $hook['handler'],
                $hook['hook'],
            );
        }

        foreach ($measurements['pools'] as $pool) {
            $count = 0;
            $busy_sum = 0;
            $busy_max = 0;
            $first_end = PHP_INT_MAX;
            $last_end = 0;
            foreach ($pool['workers'] as $worker) {
                ++$count;
                $busy_sum += $worker['busy_us'];
                $busy_max = max($busy_max, $worker['busy_us']);
                $first_end = min($first_end, $worker['end_us']);
                $last_end = max($last_end, $worker['end_us']);
            }

            if ($count === 0) {
                continue;
            }

            $out .= sprintf(
                "\n%s pool: %d workers, %d tasks, busy imbalance %.2f (max / mean), tail %s\n",
                $pool['role'],
                $count,
                $pool['tasks'],
                $busy_sum > 0 ? $busy_max * $count / $busy_sum : 1.0,
                self::seconds($last_end - $first_end),
            );
        }

        $files = $measurements['files'];
        usort($files, static fn(array $a, array $b): int => $b['cpu_us'] <=> $a['cpu_us']);

        $out .= "\n" . 'Slowest files by CPU' . "\n";
        foreach (array_slice($files, 0, self::SUMMARY_ROWS) as $file) {
            $out .= sprintf(
                "  %9s cpu %9s wall %8s peak growth  %s %s\n",
                self::seconds($file['cpu_us']),
                self::seconds($file['wall_us']),
                self::megabytes($file['peak_growth_bytes']),
                $file['op'],
                $file['path'],
            );
        }

        $rows = array_filter(
            $measurements['hot']['statements'],
            static fn(array $record): bool => $record['self_us'] >= self::MIN_ROW_US,
        );

        if ($rows !== []) {
            $out .= "\n" . 'Hottest statements (own time)' . "\n";
        }

        foreach (array_slice($rows, 0, self::SUMMARY_ROWS) as $statement) {
            $out .= sprintf(
                "  %9s  %s:%d %s%s\n",
                self::seconds($statement['self_us']),
                $statement['path'],
                $statement['line'],
                $statement['node'],
                self::passes($statement['passes']),
            );
        }

        $rows = array_filter(
            $measurements['hot']['functions'],
            static fn(array $record): bool => $record['self_us'] >= self::MIN_ROW_US,
        );

        if ($rows !== []) {
            $out .= "\n" . 'Hottest functions and methods (own time, and with the functions analysed meanwhile)' . "\n";
        }

        foreach (array_slice($rows, 0, self::SUMMARY_ROWS) as $function) {
            $out .= sprintf(
                "  %9s own  %9s total  %s (%s)  %s:%d%s\n",
                self::seconds($function['self_us']),
                self::seconds($function['wall_us']),
                $function['id'],
                $function['pass'],
                $function['path'],
                $function['line'],
                self::passes($function['passes']),
            );
        }

        if ($path === null) {
            return $out . "\n"
                . 'Run with --debug-performance=PATH to write all of it to a performance profile (a JSON file), '
                . 'and open that in ' . self::VIEWER . "\n\n";
        }

        return $out . "\n";
    }

    /**
     * @return array<string, mixed>
     */
    private static function env(ProjectAnalyzer $project_analyzer): array
    {
        $codebase = $project_analyzer->getCodebase();
        $config = $codebase->config;

        $opcache = function_exists('opcache_get_status') ? opcache_get_status(false) : false;
        $extensions = get_loaded_extensions();
        sort($extensions);

        $env = [
            'psalm' => PSALM_VERSION,
            'php' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'arch' => php_uname('m'),
            'opcache' => $opcache !== false,
            'jit' => $opcache !== false && ($opcache['jit']['on'] ?? false) === true,
            'zend_assertions' => (int) ini_get('zend.assertions'),
            'memory_limit' => ini_get('memory_limit'),
            'extensions' => $extensions,
            'cpu_count' => max(1, (new CpuCoreCounter())->getCountWithFallback(1)),
            'ci' => CliUtils::runningInCI(),
            'threads' => $project_analyzer->threads,
            'scan_threads' => $project_analyzer->scanThreads,
            'cache' => $config->cache_directory === null ? 'none' : 'dir',
            'taint_analysis' => $codebase->taint_flow_graph !== null,
            'find_unused_code' => $codebase->find_unused_code !== null,
            'target_php_version' => $codebase->getMajorAnalysisPhpVersion() . '.'
                . $codebase->getMinorAnalysisPhpVersion(),
            'config_sha256' => $config->source_filename !== null && is_file($config->source_filename)
                ? hash_file('sha256', $config->source_filename)
                : null,
            'pid' => (int) getmypid(),
            'restarted' => XdebugHandler::getRestartSettings() !== null,
            'options' => Collector::optionNames(),
        ];

        $traceparent = Collector::traceparent();
        if ($traceparent !== null) {
            $env['traceparent'] = $traceparent;
        }

        return $env;
    }

    /**
     * What the main process holds once the workers' results are merged
     *
     * @return Counts
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function counts(): array
    {
        $classlike_storages = ClassLikeStorageProvider::getAll();
        $methods = 0;
        foreach ($classlike_storages as $storage) {
            $methods += count($storage->methods);
        }

        $file_storages = FileStorageProvider::getAll();
        $functions = 0;
        foreach ($file_storages as $storage) {
            foreach ($storage->functions as $function_id => $_) {
                // the id of a closure or an arrow function ends like this (see FunctionLikeNodeScanner)
                $functions += str_ends_with($function_id, ':-:closure') ? 0 : 1;
            }
        }

        return [
            'classlikes' => count($classlike_storages),
            'functions' => $functions,
            'methods' => $methods,
            'file_storages' => count($file_storages),
        ];
    }

    /**
     * @param array<string, mixed> $document
     * @return ?string why the profile could not be written
     */
    private static function write(string $path, array $document): ?string
    {
        $json = json_encode(
            $document,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        ) . "\n";

        $folder = dirname($path);
        error_clear_last();

        if ((!is_dir($folder) && !@mkdir($folder, 0777, true) && !is_dir($folder))
            || @file_put_contents($path, $json) === false
        ) {
            return 'Could not write the performance profile ' . $path
                . ': ' . (error_get_last()['message'] ?? 'unknown error');
        }

        return null;
    }

    /** @psalm-pure */
    private static function passes(int $passes): string
    {
        return $passes > 1 ? '  ' . $passes . ' passes' : '';
    }

    /** @psalm-pure */
    private static function seconds(int $microseconds): string
    {
        return number_format($microseconds / 1_000_000, 2) . ' s';
    }

    /** @psalm-pure */
    private static function megabytes(int $bytes): string
    {
        return number_format($bytes / (1_024 * 1_024), 1) . ' MB';
    }
}
