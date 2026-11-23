<?php

declare(strict_types=1);

namespace Psalm\Internal\Perf;

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileStorageProvider;

use function array_column;
use function array_slice;
use function array_values;
use function count;
use function dirname;
use function error_get_last;
use function file_put_contents;
use function function_exists;
use function get_loaded_extensions;
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
use function str_replace;
use function strlen;
use function usort;

use const DIRECTORY_SEPARATOR;
use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_EOL;
use const PHP_INT_MAX;
use const PHP_OS_FAMILY;
use const PHP_VERSION;

/**
 * Prints the `--debug-performance` summary and writes its dump
 *
 * @internal
 * @psalm-import-type Measurements from Collector
 * @psalm-type Counts = array{
 *     classlikes: int,
 *     functions: int,
 *     methods: int,
 *     file_storages: int,
 *     files_parsed: int,
 *     parser_cache_hits: int,
 *     parser_cache_misses: int,
 * }
 */
final class DumpWriter
{
    private const SUMMARY_ROWS = 10;

    /**
     * @param Measurements $measurements
     * @param bool $summary_to_stderr whether the summary goes to stderr, through the progress, not to stdout
     */
    public static function emit(
        array $measurements,
        ProjectAnalyzer $project_analyzer,
        bool $print_summary,
        bool $summary_to_stderr,
    ): void {
        $path = Collector::dumpPath();

        $counts = self::counts($measurements['counts']);

        $failure = $path === null
            ? null
            : self::write(
                $path,
                ['schema' => 1, 'env' => self::env($project_analyzer), 'counts' => $counts] + $measurements,
                $project_analyzer,
            );

        if ($print_summary) {
            $summary = self::summary($measurements, $counts, $path, $path !== null && $failure === null);

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
            $project_analyzer->progress->write('Performance dump written to ' . $path . PHP_EOL);
        } else {
            $project_analyzer->progress->warning($failure);
        }
    }

    /**
     * @param Measurements $measurements
     * @param Counts $counts
     * @param bool $written whether the dump is in the file
     * @psalm-pure
     */
    public static function summary(array $measurements, array $counts, ?string $path, bool $written): string
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

        foreach ($stages as $name => $stage) {
            $share = $totals['wall_us'] > 0 ? 100.0 * (float) $stage['wall_us'] / (float) $totals['wall_us'] : 0.0;
            $out .= sprintf(
                "  %-18s %9s wall %9s cpu %5.1f%%\n",
                $name,
                self::seconds($stage['wall_us']),
                self::seconds($stage['cpu_us']),
                $share,
            );
        }

        $out .= sprintf(
            "\nPeak memory %s. Counts: %d class-likes, %d functions, %d methods, %d file storages, "
            . "%d files parsed (parser cache: %d hits, %d misses)\n",
            self::megabytes($totals['php_peak_bytes']),
            $counts['classlikes'],
            $counts['functions'],
            $counts['methods'],
            $counts['file_storages'],
            $counts['files_parsed'],
            $counts['parser_cache_hits'],
            $counts['parser_cache_misses'],
        );

        $analysis_us = 0;
        foreach ($measurements['files'] as $file) {
            $analysis_us += $file['op'] === 'analysis' ? $file['wall_us'] : 0;
        }

        $out .= "\n" . 'Plugins and providers (time in handlers, as a share of file analysis time over all processes)'
            . "\n";
        foreach (array_slice($measurements['hooks'], 0, self::SUMMARY_ROWS) as $hook) {
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
            foreach ($measurements['workers'] as $worker) {
                if ($worker['pool'] === $pool['id']) {
                    ++$count;
                    $busy_sum += $worker['busy_us'];
                    $busy_max = max($busy_max, $worker['busy_us']);
                    $first_end = min($first_end, $worker['end_us']);
                    $last_end = max($last_end, $worker['end_us']);
                }
            }

            if ($count === 0) {
                continue;
            }

            $out .= sprintf(
                "\n%s pool: %d workers, %d tasks, busy imbalance %.2f (max / mean), tail %s\n",
                $pool['role'],
                $pool['workers'],
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

        $out .= "\n" . 'Hottest statements (own time)' . "\n";
        foreach (array_slice($measurements['top']['statements'], 0, self::SUMMARY_ROWS) as $statement) {
            $out .= sprintf(
                "  %9s  %s:%d %s\n",
                self::seconds($statement['self_us']),
                $statement['path'],
                $statement['line'],
                $statement['node'],
            );
        }

        $out .= "\n" . 'Hottest functions and methods' . "\n";
        foreach (array_slice($measurements['top']['functions'], 0, self::SUMMARY_ROWS) as $function) {
            $out .= sprintf(
                "  %9s  %s (%s)  %s:%d\n",
                self::seconds($function['wall_us']),
                $function['id'],
                $function['pass'],
                $function['path'],
                $function['line'],
            );
        }

        if ($path === null) {
            return $out . "\n" . 'Run with --debug-performance=PATH to write all of it to a JSON file' . "\n\n";
        }

        return $out . "\n" . ($written ? 'The full data is in ' . $path . "\n\n" : '');
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
            'memory_limit' => (string) ini_get('memory_limit'),
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
            'plugins' => self::pluginClasses($project_analyzer),
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
     * @param array{files_parsed: int, parser_cache_hits: int, parser_cache_misses: int} $parser
     * @return Counts
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    private static function counts(array $parser): array
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
        ] + $parser;
    }

    /**
     * The classes of the plugins: those the config names and those declared by the files it names
     *
     * @return list<string>
     */
    private static function pluginClasses(ProjectAnalyzer $project_analyzer): array
    {
        $codebase = $project_analyzer->getCodebase();
        $classes = array_column($codebase->config->getPluginClasses(), 'class');

        foreach ($codebase->config->plugin_paths as $path) {
            $declared = array_values(ClassLikeAnalyzer::getClassesForFile(
                $codebase,
                str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path),
            ));

            if ($declared !== []) {
                $classes[] = $declared[0];
            }
        }

        return $classes;
    }

    /**
     * @param array<string, mixed> $document
     * @return ?string why the dump could not be written
     */
    private static function write(string $path, array $document, ProjectAnalyzer $project_analyzer): ?string
    {
        // the reports and the baseline are written by now: the check at the start can't see them by a name that
        // the file system maps to one file, such as a Unicode name in another normalization
        $protected = [];
        foreach ($project_analyzer->generated_report_options as $report_options) {
            $protected[] = (string) $report_options->output_path;
        }

        $protected[] = (string) $project_analyzer->getConfig()->error_baseline;
        $same_file = Collector::sameFileAs($path, $protected);

        if ($same_file !== null) {
            return 'The performance dump ' . $path . ' is the file ' . $same_file . ', which Psalm wrote: not written';
        }

        $json = json_encode(
            $document,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR,
        ) . "\n";

        $folder = dirname($path);
        if (!is_dir($folder) && !@mkdir($folder, 0777, true) && !is_dir($folder)) {
            return 'Could not create the directory of the performance dump ' . $path
                . ': ' . (error_get_last()['message'] ?? 'unknown error');
        }

        if (@file_put_contents($path, $json) !== strlen($json)) {
            return 'Could not write the performance dump ' . $path
                . ': ' . (error_get_last()['message'] ?? 'unknown error');
        }

        return null;
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
