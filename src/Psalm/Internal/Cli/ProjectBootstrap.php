<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Composer\Autoload\ClassLoader;
use Psalm\Config;
use Psalm\ErrorBaseline;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Composer;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\ProjectCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Progress\Progress;
use Psalm\Report\ReportOptions;

use function array_key_exists;
use function in_array;
use function is_numeric;
use function is_string;

/**
 * The config, the providers and a configured ProjectAnalyzer, as `psalm` builds them from its options. Overlay
 * analysis builds them again in the same process where it cannot fork.
 *
 * @internal
 */
final class ProjectBootstrap
{
    /** @param list<ClassLoader> $autoloaders */
    public static function loadConfig(
        ?string $path_to_config,
        string $current_dir,
        string $output_format,
        array $autoloaders,
        bool $run_taint_analysis,
        array $options,
    ): Config {
        $config = CliUtils::initializeConfig(
            $path_to_config,
            $current_dir,
            $output_format,
            $autoloaders,
            $run_taint_analysis,
        );

        if (isset($options['error-level'])
            && is_numeric($options['error-level'])
        ) {
            $config_level = (int) $options['error-level'];

            if (!in_array($config_level, [1, 2, 3, 4, 5, 6, 7, 8], true)) {
                throw new ConfigException(
                    'Invalid error level ' . $config_level,
                );
            }

            $config->level = $config_level;
        }
        return $config;
    }

    public static function initProviders(array $options, Config $config, string $current_dir): Providers
    {
        if ($config->cache_directory === null || isset($options['i'])) {
            $providers = new Providers(
                new FileProvider,
                new ParserCacheProvider($config, Composer::getLockFile($current_dir), false),
                new FileStorageCacheProvider($config, Composer::getLockFile($current_dir), false),
                new ClassLikeStorageCacheProvider($config, Composer::getLockFile($current_dir), false),
                new FileReferenceCacheProvider($config, Composer::getLockFile($current_dir), false),
            );
        } else {
            $no_reflection_cache = isset($options['no-reflection-cache']);
            $no_file_cache = isset($options['no-file-cache']);
            $no_reference_cache = isset($options['no-reference-cache']);

            $providers = new Providers(
                new FileProvider,
                new ParserCacheProvider($config, Composer::getLockFile($current_dir), !$no_file_cache),
                new FileStorageCacheProvider($config, Composer::getLockFile($current_dir), !$no_reflection_cache),
                new ClassLikeStorageCacheProvider($config, Composer::getLockFile($current_dir), !$no_reflection_cache),
                new FileReferenceCacheProvider($config, Composer::getLockFile($current_dir), !$no_reference_cache),
                new ProjectCacheProvider(),
            );
        }
        return $providers;
    }

    /**
     * @param list<ReportOptions> $file_report_options
     * @param int<1, max> $threads
     * @param int<1, max> $scan_threads
     * @param array<mixed> $plugins paths to plugin files
     */
    public static function createProjectAnalyzer(
        array $options,
        Config $config,
        Providers $providers,
        ReportOptions $stdout_report_options,
        array $file_report_options,
        int $threads,
        int $scan_threads,
        Progress $progress,
        array $plugins,
        ?string $find_references_to,
        bool $run_taint_analysis,
    ): ProjectAnalyzer {
        $project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            $stdout_report_options,
            $file_report_options,
            $threads,
            $scan_threads,
            $progress,
        );

        CliUtils::initPhpVersion($options, $config, $project_analyzer);

        self::configureProjectAnalyzer(
            $options,
            $config,
            $project_analyzer,
            $find_references_to,
            self::shouldFindUnusedCode($options, $config),
            isset($options['find-unused-variables']),
            $run_taint_analysis,
        );

        /** @var string $plugin_path */
        foreach ($plugins as $plugin_path) {
            $config->addPluginPath($plugin_path);
        }

        return $project_analyzer;
    }

    /**
     * The baseline that applies to a run: the one `--use-baseline` names or the config's, unless `--ignore-baseline`
     *
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    public static function readBaseline(array $options, Config $config): array
    {
        if (isset($options['use-baseline'])) {
            if (!is_string($options['use-baseline'])) {
                fwrite(STDERR, '--use-baseline must be a string' . PHP_EOL);
                exit(1);
            }

            $config->error_baseline = $options['use-baseline'];
        }

        if ($config->error_baseline === null || $config->error_baseline === '' || isset($options['ignore-baseline'])) {
            return [];
        }

        return self::readBaselineFile($config->error_baseline);
    }

    /**
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     * @throws ProjectBootstrapException if the file is missing or not a baseline, with what to do about it
     */
    public static function readBaselineFile(string $baseline_file_path): array
    {
        try {
            return ErrorBaseline::read(
                new FileProvider,
                $baseline_file_path,
            );
        } catch (ConfigException $exception) {
            throw new ProjectBootstrapException(
                'Could not read the baseline: ' . $exception->getMessage()
                    . '. Create it with --set-baseline=' . $baseline_file_path,
                0,
                $exception,
            );
        }
    }

    /**
     * @psalm-pure
     */
    public static function shouldRunTaintAnalysis(array $options): bool
    {
        return (isset($options['track-tainted-input'])
            || isset($options['security-analysis'])
            || isset($options['taint-analysis']));
    }

    /**
     * @return false|'always'|'auto'
     * @psalm-mutation-free
     */
    private static function shouldFindUnusedCode(array $options, Config $config): bool|string
    {
        $find_unused_code = false;
        if (isset($options['find-dead-code'])) {
            $options['find-unused-code'] = $options['find-dead-code'] === 'always' ? 'always' : 'auto';
        }

        if (isset($options['find-unused-code'])) {
            if ($options['find-unused-code'] === 'always') {
                $find_unused_code = 'always';
            } else {
                $find_unused_code = 'auto';
            }
        } elseif ($config->find_unused_code) {
            $find_unused_code = 'auto';
        }

        return $find_unused_code;
    }

    /**
     * @param false|'always'|'auto' $find_unused_code
     */
    private static function configureProjectAnalyzer(
        array $options,
        Config $config,
        ProjectAnalyzer $project_analyzer,
        ?string $find_references_to,
        false|string $find_unused_code,
        bool $find_unused_variables,
        bool $run_taint_analysis,
    ): void {
        if (isset($options['generate-json-map']) && is_string($options['generate-json-map'])) {
            $project_analyzer->getCodebase()->store_node_types = true;
        }

        if (array_key_exists('debug-by-line', $options)) {
            $project_analyzer->debug_lines = true;
        }

        if (array_key_exists('debug-performance', $options)) {
            $project_analyzer->debug_performance = true;
        }

        if ($find_references_to !== null) {
            $project_analyzer->getCodebase()->collectLocations();
            $project_analyzer->show_issues = false;
        }

        if ($find_unused_code) {
            $project_analyzer->getCodebase()->reportUnusedCode($find_unused_code);
        }

        if ($config->find_unused_variables || $find_unused_variables) {
            $project_analyzer->getCodebase()->reportUnusedVariables();
        }

        if ($config->literal_array_key_check) {
            $project_analyzer->getCodebase()->literal_array_key_check = true;
        }
        $project_analyzer->getCodebase()->all_constants_global = $config->all_constants_global;
        $project_analyzer->getCodebase()->all_functions_global = $config->all_functions_global;

        if ($config->run_taint_analysis || $run_taint_analysis) {
            $project_analyzer->trackTaintedInputs();
        }

        if ($config->find_unused_psalm_suppress || isset($options['find-unused-psalm-suppress'])) {
            $project_analyzer->trackUnusedSuppressions();
        }
    }
}
