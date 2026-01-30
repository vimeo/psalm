<?php

declare(strict_types=1);

namespace Psalm\Internal\Cli;

use Composer\Autoload\ClassLoader;
use Fidry\CpuCoreCounter\CpuCoreCounter;
use Psalm\Config;
use Psalm\Config\Creator;
use Psalm\ErrorBaseline;
use Psalm\Exception\ConfigCreationException;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\Codebase\ReferenceMapGenerator;
use Psalm\Internal\Composer;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Fork\PsalmRestarter;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Preloader;
use Psalm\Internal\Provider\ClassLikeStorageCacheProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceCacheProvider;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\ProjectCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Stubs\Generator\StubsGenerator;
use Psalm\IssueBuffer;
use Psalm\Progress\DebugProgress;
use Psalm\Progress\DefaultProgress;
use Psalm\Progress\LongProgress;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use Throwable;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_shift;
use function array_slice;
use function array_sum;
use function array_values;
use function chdir;
use function count;
use function defined;
use function extension_loaded;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function function_exists;
use function fwrite;
use function gc_collect_cycles;
use function gc_disable;
use function getcwd;
use function getenv;
use function getopt;
use function implode;
use function in_array;
use function ini_get;
use function is_array;
use function is_numeric;
use function is_string;
use function json_encode;
use function max;
use function microtime;
use function opcache_get_status;
use function parse_url;
use function preg_match;
use function preg_replace;
use function realpath;
use function setlocale;
use function sort;
use function str_repeat;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;
use function wordwrap;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const LC_CTYPE;
use const PHP_EOL;
use const PHP_URL_SCHEME;
use const PHP_VERSION;
use const STDERR;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';
require_once __DIR__ . '/../IncludeCollector.php';
require_once __DIR__ . '/../../IssueBuffer.php';
require_once __DIR__ . '/../../Report.php';

/**
 * @internal
 */
final class Psalm
{
    private const SHORT_OPTIONS = [
        'f:',
        'm',
        'h',
        'v',
        'c:',
        'i',
        'r:',
    ];

    private const LONG_OPTIONS = [
        'clear-cache',
        'consolidate-cache',
        'clear-global-cache',
        'config:',
        'debug',
        'debug-by-line',
        'debug-performance',
        'debug-emitted-issues',
        'diff',
        'disable-extension:',
        'find-dead-code::',
        'find-unused-code::',
        'find-unused-variables',
        'find-references-to:',
        'help',
        'ignore-baseline',
        'init',
        'memory-limit:',
        'monochrome',
        'no-diff',
        'force-jit',
        'no-cache',
        'no-reflection-cache',
        'no-file-cache',
        'no-reference-cache',
        'output-format:',
        'plugin:',
        'report:',
        'report-show-info:',
        'root:',
        'set-baseline::',
        'show-info:',
        'show-snippet:',
        'stats',
        'threads:',
        'scan-threads:',
        'update-baseline',
        'use-baseline:',
        'use-ini-defaults',
        'version',
        'php-version:',
        'generate-json-map:',
        'generate-stubs:',
        'alter',
        'review',
        'language-server',
        'refactor',
        'shepherd::',
        'no-progress',
        'long-progress',
        'no-suggestions',
        'include-php-versions', // used for baseline
        'pretty-print', // used for JSON reports
        'track-tainted-input',
        'taint-analysis',
        'security-analysis',
        'dump-taint-graph:',
        'find-unused-psalm-suppress',
        'error-level:',
    ];

    /**
     * @param array<int,string> $argv
     * @psalm-suppress ComplexMethod Maybe some of the option handling could be moved to its own function...
     */
    public static function run(array $argv): void
    {
        CliUtils::checkRuntimeRequirements();
        gc_collect_cycles();
        gc_disable();

        ErrorHandler::install($argv);

        $args = array_slice($argv, 1);

        // get options from command line
        $options = getopt(implode('', self::SHORT_OPTIONS), self::LONG_OPTIONS);
        if (false === $options) {
            throw new RuntimeException('Failed to parse CLI options');
        }

        // debug CI environment
        if (!array_key_exists('debug', $options)
            && 'true' === getenv('GITHUB_ACTIONS')
            && '1' === getenv('RUNNER_DEBUG')
        ) {
            $options['debug'] = false;
        }

        self::forwardCliCall($options, $argv);

        self::validateCliArguments($args);

        CliUtils::setMemoryLimit($options);

        self::syncShortOptions($options);

        if (isset($options['c']) && is_array($options['c'])) {
            fwrite(STDERR, 'Too many config files provided' . PHP_EOL);
            exit(1);
        }

        if (array_key_exists('h', $options)) {
            echo self::getHelpText();
            /*
            --shepherd[=endpoint]
                Send analysis statistics to Shepherd server.
                `endpoint` is the URL to the Shepherd server. It defaults to shepherd.dev
            */

            exit;
        }

        $current_dir = self::getCurrentDir($options);

        $path_to_config = CliUtils::getPathToConfig($options);

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        // capture environment before registering autoloader (it may destroy it)
        IssueBuffer::captureServer($_SERVER);

        $include_collector = new IncludeCollector();
        $autoloaders = $include_collector->runAndCollect(
            // we ignore the FQN because of a hack in scoper.inc that needs full path
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
            /** @return list<ClassLoader> */
            static fn(): array =>
                CliUtils::requireAutoloaders($current_dir, isset($options['r']), $vendor_dir),
        );

        $run_taint_analysis = self::shouldRunTaintAnalysis($options);

        if (array_key_exists('v', $options)) {
            echo 'Psalm ' . PSALM_VERSION . PHP_EOL;
            exit;
        }

        $output_format = self::initOutputFormat($options);

        [$config, $init_source_dir] = self::initConfig(
            $current_dir,
            $args,
            $vendor_dir,
            $autoloaders,
            $path_to_config,
            $output_format,
            $run_taint_analysis,
            $options,
        );

        if (isset($options['no-cache'])) {
            $config->cache_directory = null;
        }

        $config->setIncludeCollector($include_collector);

        $in_ci = CliUtils::runningInCI();        // disable progressbar on CI

        if ($in_ci) {
            $options['long-progress'] = true;
        }

        $threads = self::getThreads($options, $config, $in_ci, false);
        $scanThreads = self::getThreads($options, $config, $in_ci, true);

        $progress = self::initProgress($options, $config, $in_ci);

        $force_jit = $config->force_jit || isset($options['force-jit']);
        self::restart($options, $force_jit, $threads, $scanThreads, $progress);

        if (isset($options['debug-emitted-issues'])) {
            $config->debug_emitted_issues = true;
        }

        setlocale(LC_CTYPE, 'C');

        if (isset($options['set-baseline'])) {
            if (is_array($options['set-baseline'])) {
                fwrite(STDERR, 'Only one baseline file can be created at a time' . PHP_EOL);
                exit(1);
            }
        }

        $paths_to_check = CliUtils::getPathsToCheck($options['f'] ?? null);

        if ($config->resolve_from_config_file) {
            $current_dir = $config->base_dir;
            chdir($current_dir);
        }

        /** @var list<string> $plugins List of paths to plugin files */
        $plugins = [];

        if (isset($options['plugin'])) {
            $plugins_from_options = $options['plugin'];

            if (is_array($plugins_from_options)) {
                $plugins = $plugins_from_options;
            } elseif (is_string($plugins_from_options)) {
                $plugins = [$plugins_from_options];
            }
        }

        $show_info = self::initShowInfo($options);

        $is_diff = false; // self::initIsDiff($options);

        $find_unused_code = self::shouldFindUnusedCode($options, $config);

        $find_unused_variables = isset($options['find-unused-variables']);

        $find_references_to = isset($options['find-references-to']) && is_string($options['find-references-to'])
            ? $options['find-references-to']
            : null;

        self::configureShepherd($config, $options, $plugins);

        if (isset($options['clear-cache'])) {
            self::clearCache($config);
        }

        if (isset($options['consolidate-cache'])) {
            self::consolidateCache($config, $current_dir);
        }

        if (isset($options['clear-global-cache'])) {
            self::clearGlobalCache($config);
        }

        $providers = self::initProviders($options, $config, $current_dir);

        $stdout_report_options = self::initStdoutReportOptions($options, $show_info, $output_format, $in_ci);

        /** @var list<string>|string $report_file_paths type guaranteed by argument to getopt() */
        $report_file_paths = $options['report'] ?? [];
        if (is_string($report_file_paths)) {
            $report_file_paths = [$report_file_paths];
        }

        $project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            $stdout_report_options,
            ProjectAnalyzer::getFileReportOptions(
                $report_file_paths,
                isset($options['report-show-info'])
                    ? $options['report-show-info'] !== 'false' && $options['report-show-info'] !== '0'
                    : true,
            ),
            $threads,
            $scanThreads,
            $progress,
        );

        CliUtils::initPhpVersion($options, $config, $project_analyzer);

        $start_time = microtime(true);

        self::configureProjectAnalyzer(
            $options,
            $config,
            $project_analyzer,
            $find_references_to,
            $find_unused_code,
            $find_unused_variables,
            $run_taint_analysis,
        );

        if ($config->run_taint_analysis || $run_taint_analysis) {
            $is_diff = false;
        }

        /** @var string $plugin_path */
        foreach ($plugins as $plugin_path) {
            $config->addPluginPath($plugin_path);
        }

        // Prime cache
        InternalCallMapHandler::getCallMap();

        if ($paths_to_check === null) {
            $project_analyzer->check($current_dir, $is_diff);
        } elseif ($paths_to_check) {
            $project_analyzer->checkPaths($paths_to_check);
        }

        if ($find_references_to) {
            $project_analyzer->findReferencesTo($find_references_to);
        }

        self::storeFlowGraph($options, $project_analyzer);

        if (isset($options['generate-json-map']) && is_string($options['generate-json-map'])) {
            self::storeTypeMap($providers, $config, $options['generate-json-map']);
        }

        if (isset($options['generate-stubs'])) {
            self::generateStubs($options, $providers, $project_analyzer);
        }

        if (!isset($options['i'])) {
            IssueBuffer::finish(
                $project_analyzer,
                !$paths_to_check,
                $start_time,
                isset($options['stats']),
                self::initBaseline($options, $config, $current_dir, $path_to_config, $paths_to_check),
            );
        } else {
            self::autoGenerateConfig($project_analyzer, $current_dir, $init_source_dir, $vendor_dir);
        }
    }

    /** @return int<1, max> */
    public static function getThreads(array $options, Config $config, bool $in_ci, bool $for_scan): int
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            // No support desired for Windows at the moment
            return 1;
        } elseif (!extension_loaded('pcntl')) {
            // Psalm requires pcntl for multi-threads support
            return 1;
        }

        if ($for_scan) {
            if (isset($options['scan-threads'])) {
                $threads = max(1, (int)$options['scan-threads']);
            } elseif (isset($options['debug']) || $in_ci) {
                $threads = 1;
            } elseif ($config->scan_threads) {
                $threads = $config->scan_threads;
            } else {
                $threads = max(1, (new CpuCoreCounter())->getCount());
            }
        } else {
            if (isset($options['threads'])) {
                $threads = max(1, (int)$options['threads']);
            } elseif (isset($options['debug']) || $in_ci) {
                $threads = 1;
            } elseif ($config->threads) {
                $threads = $config->threads;
            } else {
                $threads = max(1, (new CpuCoreCounter())->getCount());
            }
        }
        return $threads;
    }

    private static function initOutputFormat(array $options): string
    {
        return isset($options['output-format']) && is_string($options['output-format'])
            ? $options['output-format']
            : self::findDefaultOutputFormat();
    }

    /**
     * @return Report::TYPE_*
     */
    private static function findDefaultOutputFormat(): string
    {
        $emulator = getenv('TERMINAL_EMULATOR');
        if (is_string($emulator) && str_starts_with($emulator, 'JetBrains')) {
            return Report::TYPE_PHP_STORM;
        }

        if ('true' === getenv('GITHUB_ACTIONS')) {
            return Report::TYPE_GITHUB_ACTIONS;
        }

        return Report::TYPE_CONSOLE;
    }

    private static function initShowInfo(array $options): bool
    {
        return isset($options['show-info'])
            ? $options['show-info'] === 'true' || $options['show-info'] === '1'
            : false;
    }

    /*private static function initIsDiff(array $options): bool
    {
        return !isset($options['no-diff'])
            && !isset($options['set-baseline'])
            && !isset($options['update-baseline']);
    }*/

    /**
     * @param array<int,string> $args
     */
    private static function validateCliArguments(array $args): void
    {
        array_map(
            static function (string $arg): void {
                if (str_starts_with($arg, '--') && $arg !== '--') {
                    $arg_name = (string) preg_replace('/=.*$/', '', substr($arg, 2), 1);

                    if (!in_array($arg_name, self::LONG_OPTIONS)
                        && !in_array($arg_name . ':', self::LONG_OPTIONS)
                        && !in_array($arg_name . '::', self::LONG_OPTIONS)
                    ) {
                        fwrite(
                            STDERR,
                            'Unrecognised argument "--' . $arg_name . '"' . PHP_EOL
                            . 'Type --help to see a list of supported arguments'. PHP_EOL,
                        );
                        exit(1);
                    }
                } elseif (str_starts_with($arg, '-') && $arg !== '-' && $arg !== '--') {
                    $arg_name = (string) preg_replace('/=.*$/', '', substr($arg, 1));

                    if (!in_array($arg_name, self::SHORT_OPTIONS)
                        && !in_array($arg_name . ':', self::SHORT_OPTIONS)
                    ) {
                        fwrite(
                            STDERR,
                            'Unrecognised argument "-' . $arg_name . '"' . PHP_EOL
                            . 'Type --help to see a list of supported arguments'. PHP_EOL,
                        );
                        exit(1);
                    }
                }
            },
            $args,
        );
    }

    /**
     * @param array<int, string> $args
     */
    private static function generateConfig(string $current_dir, array &$args): void
    {
        if (file_exists($current_dir . DIRECTORY_SEPARATOR . 'psalm.xml')) {
            fwrite(STDERR, 'A config file already exists in the current directory' . PHP_EOL);
            exit(1);
        }

        $args = array_values(array_filter(
            $args,
            static fn(string $arg): bool => $arg !== '--ansi'
                && $arg !== '--no-ansi'
                && $arg !== '-i'
                && $arg !== '--init'
                && $arg !== '--debug'
                && $arg !== '--debug-by-line'
                && $arg !== '--debug-emitted-issues'
                && !str_starts_with($arg, '--disable-extension=')
                && !str_starts_with($arg, '--root=')
                && !str_starts_with($arg, '--r='),
        ));

        $init_level = null;
        $init_source_dir = null;
        if (count($args)) {
            if (count($args) > 2) {
                fwrite(STDERR, 'Too many arguments provided for psalm --init' . PHP_EOL);
                exit(1);
            }

            if (isset($args[1])) {
                if (!preg_match('/^[1-8]$/', $args[1])) {
                    fwrite(STDERR, 'Config strictness must be a number between 1 and 8 inclusive' . PHP_EOL);
                    exit(1);
                }

                $init_level = (int)$args[1];
            }

            $init_source_dir = $args[0];
        }

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        if (null !== $init_level) {
            try {
                $template_contents = Creator::getContents(
                    $current_dir,
                    $init_source_dir,
                    $init_level,
                    $vendor_dir,
                );
            } catch (ConfigCreationException $e) {
                fwrite(STDERR, $e->getMessage() . PHP_EOL);
                exit(1);
            }

            if (file_put_contents($current_dir . DIRECTORY_SEPARATOR . 'psalm.xml', $template_contents) === false) {
                fwrite(STDERR, 'Could not write to psalm.xml' . PHP_EOL);
                exit(1);
            }

            exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
        }
    }

    /** @param list<ClassLoader> $autoloaders */
    private static function loadConfig(
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

    private static function initProgress(array $options, Config $config, bool $in_ci): Progress
    {
        $debug = array_key_exists('debug', $options) || array_key_exists('debug-by-line', $options);

        $show_info = isset($options['show-info'])
            ? $options['show-info'] === 'true' || $options['show-info'] === '1'
            : false;

        if ($debug) {
            $progress = new DebugProgress();
        } elseif (isset($options['no-progress'])) {
            $progress = new VoidProgress();
        } else {
            $show_errors = !$config->error_baseline || isset($options['ignore-baseline']);
            if (isset($options['long-progress'])) {
                $progress = new LongProgress($show_errors, $show_info, $in_ci);
            } else {
                $progress = new DefaultProgress($show_errors, $show_info, $in_ci);
            }
        }
        // output buffered warnings
        foreach ($config->config_warnings as $warning) {
            $progress->warning($warning);
        }
        return $progress;
    }

    private static function initProviders(array $options, Config $config, string $current_dir): Providers
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
     * @param array{"set-baseline": mixed, ...} $options
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function generateBaseline(
        array $options,
        Config $config,
        string $current_dir,
        ?string $path_to_config,
    ): array {
        fwrite(STDERR, 'Writing error baseline to file...' . PHP_EOL);

        $error_baseline = is_string($options['set-baseline']) ? $options['set-baseline'] :
            ($config->error_baseline ?? Config::DEFAULT_BASELINE_NAME);

        try {
            $issue_baseline = ErrorBaseline::read(
                new FileProvider,
                $error_baseline,
            );
        } catch (ConfigException) {
            $issue_baseline = [];
        }

        ErrorBaseline::create(
            new FileProvider,
            $error_baseline,
            IssueBuffer::getIssuesData(),
            $config->include_php_versions_in_error_baseline || isset($options['include-php-versions']),
        );

        fwrite(STDERR, "Baseline saved to $error_baseline.");

        if ($error_baseline !== $config->error_baseline) {
            CliUtils::updateConfigFile(
                $config,
                $path_to_config ?? $current_dir,
                $error_baseline,
            );
        }

        fwrite(STDERR, PHP_EOL);

        return $issue_baseline;
    }

    /**
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function updateBaseline(array $options, Config $config): array
    {
        $baselineFile = $config->error_baseline;

        if (empty($baselineFile)) {
            fwrite(STDERR, 'Cannot update baseline, because no baseline file is configured.' . PHP_EOL);
            exit(1);
        }

        try {
            $issue_current_baseline = ErrorBaseline::read(
                new FileProvider,
                $baselineFile,
            );
            $total_issues_current_baseline = ErrorBaseline::countTotalIssues($issue_current_baseline);

            $issue_baseline = ErrorBaseline::update(
                new FileProvider,
                $baselineFile,
                IssueBuffer::getIssuesData(),
                $config->include_php_versions_in_error_baseline || isset($options['include-php-versions']),
            );
            $total_issues_updated_baseline = ErrorBaseline::countTotalIssues($issue_baseline);

            $total_fixed_issues = $total_issues_current_baseline - $total_issues_updated_baseline;

            if ($total_fixed_issues > 0) {
                echo str_repeat('-', 30) . "\n";
                echo $total_fixed_issues . ' errors fixed' . "\n";
            }
        } catch (ConfigException $exception) {
            fwrite(STDERR, 'Could not update baseline file: ' . $exception->getMessage() . PHP_EOL);
            exit(1);
        }

        return $issue_baseline;
    }

    private static function storeTypeMap(Providers $providers, Config $config, string $type_map_location): void
    {
        $file_map = $providers->file_reference_provider->getFileMaps();

        $name_file_map = [];

        $expected_references = [];

        foreach ($file_map as $file_path => $map) {
            $file_name = $config->shortenFileName($file_path);
            foreach ($map[0] as $map_parts) {
                $expected_references[$map_parts[1]] = true;
            }
            $map[2] = [];
            $name_file_map[$file_name] = $map;
        }

        $reference_dictionary = ReferenceMapGenerator::getReferenceMap(
            $providers->classlike_storage_provider,
            $expected_references,
        );

        $type_map_string = json_encode(
            ['files' => $name_file_map, 'references' => $reference_dictionary],
            JSON_THROW_ON_ERROR,
        );

        $providers->file_provider->setContents(
            $type_map_location,
            $type_map_string,
        );
    }

    private static function autoGenerateConfig(
        ProjectAnalyzer $project_analyzer,
        string $current_dir,
        ?string $init_source_dir,
        string $vendor_dir,
    ): void {
        $issues_by_file = IssueBuffer::getIssuesData();

        if (!$issues_by_file) {
            $init_level = 1;
        } else {
            $codebase = $project_analyzer->getCodebase();
            $mixed_counts = $codebase->analyzer->getTotalTypeCoverage($codebase);

            $init_level = Creator::getLevel(
                array_merge(...array_values($issues_by_file)),
                array_sum($mixed_counts),
            );
        }

        echo "\n" . 'Detected level ' . $init_level . ' as a suitable initial default' . "\n";

        try {
            $template_contents = Creator::getContents(
                $current_dir,
                $init_source_dir,
                $init_level,
                $vendor_dir,
            );
        } catch (ConfigCreationException $e) {
            fwrite(STDERR, $e->getMessage() . PHP_EOL);
            exit(1);
        }

        if (file_put_contents($current_dir . DIRECTORY_SEPARATOR . 'psalm.xml', $template_contents) === false) {
            fwrite(STDERR, 'Could not write to psalm.xml' . PHP_EOL);
            exit(1);
        }

        exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
    }

    private static function initStdoutReportOptions(
        array $options,
        bool $show_info,
        string $output_format,
        bool $in_ci,
    ): ReportOptions {
        $stdout_report_options = new ReportOptions();
        $stdout_report_options->use_color = !array_key_exists('m', $options);
        $stdout_report_options->show_info = $show_info;
        $stdout_report_options->show_suggestions = !array_key_exists('no-suggestions', $options);
        /**
         * @psalm-suppress PropertyTypeCoercion
         */
        $stdout_report_options->format = $output_format;
        $stdout_report_options->show_snippet = !isset($options['show-snippet']) || $options['show-snippet'] !== "false";
        $stdout_report_options->pretty = isset($options['pretty-print']) && $options['pretty-print'] !== "false";
        $stdout_report_options->in_ci = $in_ci;

        return $stdout_report_options;
    }

    private static function clearGlobalCache(Config $config): never
    {
        $cache_directory = $config->getGlobalCacheDirectory();

        if ($cache_directory) {
            Config::removeCacheDirectory($cache_directory);
            echo 'Global cache directory deleted' . PHP_EOL;
        }

        exit;
    }

    private static function clearCache(Config $config): never
    {
        $cache_directory = $config->getCacheDirectory();

        if ($cache_directory !== null) {
            Config::removeCacheDirectory($cache_directory);
        }
        echo 'Cache directory deleted' . PHP_EOL;
        exit;
    }


    private static function consolidateCache(Config $config, string $current_dir): never
    {
        $cache_directory = $config->getCacheDirectory();

        if ($cache_directory !== null) {
            $lock = Composer::getLockFile($current_dir);
            (new ParserCacheProvider($config, $lock))->consolidate();
            (new FileStorageCacheProvider($config, $lock))->consolidate();
            (new ClassLikeStorageCacheProvider($config, $lock))->consolidate();
            (new FileReferenceCacheProvider($config, $lock))->consolidate();
        }
        echo 'Cache consolidated' . PHP_EOL;
        exit;
    }

    private static function getCurrentDir(array $options): string
    {
        $cwd = getcwd();
        if (false === $cwd) {
            fwrite(STDERR, 'Cannot get current working directory' . PHP_EOL);
            exit(1);
        }

        $current_dir = $cwd;

        if (isset($options['r']) && is_string($options['r'])) {
            $root_path = realpath($options['r']);

            if ($root_path === false) {
                fwrite(
                    STDERR,
                    'Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options['r'] . PHP_EOL,
                );
                exit(1);
            }

            $current_dir = $root_path;
        }

        return $current_dir;
    }

    private static function restart(
        array $options,
        bool $force_jit,
        int $threads,
        int $scanThreads,
        Progress $progress,
    ): void {
        $ini_handler = new PsalmRestarter('PSALM');

        if (isset($options['disable-extension'])) {
            if (is_array($options['disable-extension'])) {
                /** @psalm-suppress MixedAssignment */
                foreach ($options['disable-extension'] as $extension) {
                    if (is_string($extension)) {
                        $ini_handler->disableExtension($extension);
                    }
                }
            } elseif (is_string($options['disable-extension'])) {
                $ini_handler->disableExtension($options['disable-extension']);
            }
        }

        if (($threads > 1 || $scanThreads > 1)
            && extension_loaded('grpc')
            && (ini_get('grpc.enable_fork_support') === '1' && ini_get('grpc.poll_strategy') === 'epoll1') === false
        ) {
            $ini_handler->disableExtension('grpc');

            $progress->warning(PHP_EOL
                . 'grpc extension has been disabled. '
                . 'Set grpc.enable_fork_support = 1 and grpc.poll_strategy = epoll1 in php.ini to enable it. '
                . 'See https://github.com/grpc/grpc/issues/20250#issuecomment-531321945 for more information.'
                . PHP_EOL . PHP_EOL);
        }

        $ini_handler->disableExtensions([
            'uopz',
            // extensions that are incompatible with JIT (they are also usually make Psalm slow)
            'pcov',
            'blackfire',
            // Issues w/ parallel forking
            'uv',
        ]);

        // If Xdebug is enabled, restart without it
        $ini_handler->check();

        $progress->write(PHP_EOL."Running on PHP ".PHP_VERSION.', Psalm '.PSALM_VERSION.'.'.PHP_EOL);

        $hasJit = false;
        if (function_exists('opcache_get_status')) {
            if (true === (opcache_get_status()['jit']['on'] ?? false)) {
                $hasJit = true;
                $progress->write(PHP_EOL
                    . 'JIT acceleration: ON'
                    . PHP_EOL . PHP_EOL);
            } else {
                $progress->write(PHP_EOL
                    . 'JIT acceleration: OFF (an error occurred while enabling JIT)' . PHP_EOL
                    . 'Please report this to https://github.com/vimeo/psalm with your OS and PHP configuration!'
                    . PHP_EOL . PHP_EOL);
            }
        } else {
            $progress->write(PHP_EOL
                . 'JIT acceleration: OFF (opcache not installed or not enabled)' . PHP_EOL
                . 'Install and enable the opcache extension to make use of JIT for a 20%+ performance boost!'
                . PHP_EOL . PHP_EOL);
        }
        if ($force_jit && !$hasJit) {
            $progress->write('Exiting because JIT was requested but is not available.' . PHP_EOL . PHP_EOL);
            exit(1);
        }

        $overcommit = null;
        try {
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            $overcommit = trim(file_get_contents('/proc/sys/vm/overcommit_memory') ?: '');
        } catch (Throwable) {
        }

        if ($overcommit === '2') {
            $err = 'ERROR: VM overcommiting is disabled.' . PHP_EOL . PHP_EOL
                . "TL;DR: to fix, run these two commands:" . PHP_EOL . PHP_EOL
                . "echo 1 | sudo tee /proc/sys/vm/overcommit_memory" . PHP_EOL
                . "echo vm.overcommit_memory=1 | sudo tee /etc/sysctl.d/40-psalm.conf   # For persistence" . PHP_EOL
                . PHP_EOL
                . "Explanation: disabling VM overcommitting *WILL* cause failures when running Psalm "
                . "in multithreaded mode during analysis," . PHP_EOL
                . 'as Psalm relies very heavily on the copy-on-write semantics of fork(), which are currently disabled.'
                . PHP_EOL . PHP_EOL . PHP_EOL
                . "Please enable VM overcommitting to greatly speed up Psalm and avoid crashes in multithreaded mode."
                . PHP_EOL . PHP_EOL . PHP_EOL
                . "This warning may be ignored by setting the PSALM_IGNORE_NO_OVERCOMMIT=1 environment variable "
                . "(not recommended)."
                . PHP_EOL . PHP_EOL;
            
            fwrite(STDERR, $err);
            if (getenv('PSALM_IGNORE_NO_OVERCOMMIT') !== '1') {
                exit(1);
            }
        }

        Preloader::preload($progress, $hasJit);
    }

    /** @param array<int, string> $argv */
    private static function forwardCliCall(array $options, array $argv): void
    {
        if (isset($options['alter'])) {
            require_once __DIR__ . '/Psalter.php';
            Psalter::run($argv);
            exit;
        }

        if (isset($options['review'])) {
            require_once __DIR__ . '/Review.php';
            array_shift($argv);
            /** @psalm-suppress PossiblyNullArgument */
            Review::run(array_values($argv));
            exit;
        }

        if (isset($options['language-server'])) {
            require_once __DIR__ . '/LanguageServer.php';
            LanguageServer::run($argv);
            exit;
        }

        if (isset($options['refactor'])) {
            require_once __DIR__ . '/Refactor.php';
            Refactor::run($argv);
            exit;
        }
    }

    /**
     * @param array<string, false|list<mixed>|string> $options
     * @param-out array<string, false|list<mixed>|string> $options
     */
    private static function syncShortOptions(array &$options): void
    {
        if (array_key_exists('help', $options)) {
            $options['h'] = false;
        }

        if (array_key_exists('version', $options)) {
            $options['v'] = false;
        }

        if (array_key_exists('init', $options)) {
            $options['i'] = false;
        }

        if (array_key_exists('monochrome', $options)) {
            $options['m'] = false;
        }

        if (isset($options['config'])) {
            $options['c'] = $options['config'];
        }

        if (isset($options['root'])) {
            $options['r'] = $options['root'];
        }
    }

    /**
     * @param array<int, string> $args
     * @param list<ClassLoader> $autoloaders
     * @return array{Config,?string}
     */
    private static function initConfig(
        string $current_dir,
        array $args,
        string $vendor_dir,
        array $autoloaders,
        ?string $path_to_config,
        string $output_format,
        bool $run_taint_analysis,
        array $options,
    ): array {
        $init_source_dir = null;
        if (isset($options['i'])) {
            self::generateConfig($current_dir, $args);
            // if we ever got here, it means we need to run Psalm once and generate the config
            // based on the errors we find
            $init_source_dir = $args[0] ?? null;

            echo "Calculating best config level based on project files\n";
            Creator::createBareConfig($current_dir, $init_source_dir, $vendor_dir);
            $config = Config::getInstance();
            $config->setComposerClassLoader($autoloaders);
        } else {
            $config = self::loadConfig(
                $path_to_config,
                $current_dir,
                $output_format,
                $autoloaders,
                $run_taint_analysis,
                $options,
            );
        }
        return [$config, $init_source_dir];
    }

    /**
     * @param ?list<string> $paths_to_check
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function initBaseline(
        array $options,
        Config $config,
        string $current_dir,
        ?string $path_to_config,
        ?array $paths_to_check,
    ): array {
        $issue_baseline = [];

        if (isset($options['set-baseline'])) {
            if ($paths_to_check !== null) {
                fwrite(STDERR, PHP_EOL . 'Cannot generate baseline when checking specific files' . PHP_EOL);
                exit(1);
            }
            $issue_baseline = self::generateBaseline($options, $config, $current_dir, $path_to_config);
        }

        if (isset($options['use-baseline'])) {
            if (!is_string($options['use-baseline'])) {
                fwrite(STDERR, '--use-baseline must be a string' . PHP_EOL);
                exit(1);
            }

            $baseline_file_path = $options['use-baseline'];
            $config->error_baseline = $baseline_file_path;
        } else {
            $baseline_file_path = $config->error_baseline;
        }

        if (isset($options['update-baseline'])) {
            if ($paths_to_check !== null) {
                fwrite(STDERR, PHP_EOL . 'Cannot update baseline when checking specific files' . PHP_EOL);
                exit(1);
            }
            $issue_baseline = self::updateBaseline($options, $config);
        }

        if (!$issue_baseline && $baseline_file_path && !isset($options['ignore-baseline'])) {
            try {
                $issue_baseline = ErrorBaseline::read(
                    new FileProvider,
                    $baseline_file_path,
                );
            } catch (ConfigException $exception) {
                fwrite(STDERR, 'Error while reading baseline: ' . $exception->getMessage() . PHP_EOL);
                exit(1);
            }
        }

        if ($paths_to_check !== null) {
            $filtered_issue_baseline = [];
            foreach ($paths_to_check as $path_to_check) {
                // +1 to remove the initial slash from $path_to_check
                $path_to_check = substr($path_to_check, strlen($config->base_dir) + 1);
                if (isset($issue_baseline[$path_to_check])) {
                    $filtered_issue_baseline[$path_to_check] = $issue_baseline[$path_to_check];
                }
            }
            $issue_baseline = $filtered_issue_baseline;
        }

        return $issue_baseline;
    }

    private static function storeFlowGraph(array $options, ProjectAnalyzer $project_analyzer): void
    {
        /** @var string|null $dump_taint_graph */
        $dump_taint_graph = $options['dump-taint-graph'] ?? null;

        $flow_graph = $project_analyzer->getCodebase()->taint_flow_graph;
        if ($flow_graph !== null && $dump_taint_graph !== null) {
            file_put_contents($dump_taint_graph, "digraph Taints {\n\t".
                implode("\n\t", array_map(
                    static fn(array $edges) => '"'.implode('" -> "', $edges).'"',
                    $flow_graph->summarizeEdges(),
                )) .
                "\n}\n");
        }
    }

    /** @return false|'always'|'auto' */
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

    private static function shouldRunTaintAnalysis(array $options): bool
    {
        return (isset($options['track-tainted-input'])
            || isset($options['security-analysis'])
            || isset($options['taint-analysis']));
    }

    /**
     * @param false|'always'|'auto' $find_unused_code
     */
    private static function configureProjectAnalyzer(
        array $options,
        Config $config,
        ProjectAnalyzer $project_analyzer,
        string|bool|null $find_references_to,
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

    private static function configureShepherd(Config $config, array $options, array &$plugins): void
    {
        $is_shepherd_enabled = isset($options['shepherd']) || getenv('PSALM_SHEPHERD');
        if (! $is_shepherd_enabled) {
            return;
        }

        $plugins[] = Path::canonicalize(__DIR__ . '/../../Plugin/Shepherd.php');

        /** @psalm-suppress MixedAssignment */
        $custom_shepherd_endpoint = ($options['shepherd'] ?? getenv('PSALM_SHEPHERD'));
        if (is_string($custom_shepherd_endpoint) && strlen($custom_shepherd_endpoint) > 2) {
            if (parse_url($custom_shepherd_endpoint, PHP_URL_SCHEME) === null) {
                $custom_shepherd_endpoint = 'https://' . $custom_shepherd_endpoint;
            }

            $config->shepherd_endpoint = $custom_shepherd_endpoint;

            return;
        }
    }

    private static function generateStubs(
        array $options,
        Providers $providers,
        ProjectAnalyzer $project_analyzer,
    ): void {
        if (isset($options['generate-stubs']) && is_string($options['generate-stubs'])) {
            $stubs_location = $options['generate-stubs'];

            $providers->file_provider->setContents(
                $stubs_location,
                StubsGenerator::getAll(
                    $project_analyzer->getCodebase(),
                    $providers->classlike_storage_provider,
                    $providers->file_storage_provider,
                ),
            );
        }
    }

    /**
     * @psalm-pure
     */
    private static function getHelpText(): string
    {
        $formats = [];
        /** @var string $value */
        foreach ((new ReflectionClass(Report::class))->getConstants() as $constant => $value) {
            if (str_starts_with($constant, 'TYPE_')) {
                $formats[] = $value;
            }
        }
        sort($formats);
        $outputFormats = wordwrap(implode(', ', $formats), 75, "\n            ");

        /** @psalm-suppress ImpureMethodCall */
        $reports = array_keys(Report::getMapping());
        sort($reports);
        $reportFormats = wordwrap('"' . implode('", "', $reports) . '"', 75, "\n        ");

        // phpcs:disable Generic.Files.LineLength.TooLong
        return <<<HELP
        Usage:
            psalm [options] [file...]

        Basic configuration:
            -c, --config=psalm.xml
                Path to a psalm.xml configuration file. Run psalm --init to create one.

            --use-ini-defaults
                Use PHP-provided ini defaults for memory and error display

            --memory-limit=LIMIT
                Use a specific memory limit. Cannot be combined with --use-ini-defaults

            --disable-extension=[extension]
                Used to disable certain extensions while Psalm is running.

            --force-jit
                If set, requires JIT acceleration to be available in order to run Psalm, exiting immediately if it cannot be enabled.

            --threads=INT
                If greater than one, Psalm will run the scan and analysis on multiple threads, speeding things up.

            --scan-threads=INT
                If greater than one, Psalm will run the scan on multiple threads, speeding things up (if specified, takes priority over the --threads flag).

            --no-diff
                Turns off Psalm’s diff mode, checks all files regardless of whether they’ve changed.

            --php-version=PHP_VERSION
                Explicitly set PHP version to analyse code against.

            --error-level=ERROR_LEVEL
                Set the error reporting level

        Surfacing issues:
            --show-info[=BOOLEAN]
                Show non-exception parser findings (defaults to false).

            --show-snippet[=true]
                Show code snippets with errors. Options are 'true' or 'false'

            --find-dead-code[=auto]
            --find-unused-code[=auto]
                Look for unused code. Options are 'auto' or 'always'. If no value is specified, default is 'auto'

            --find-unused-psalm-suppress
                Finds all @psalm-suppress annotations that aren’t used

            --find-references-to=[class|method|property]
                Searches the codebase for references to the given fully-qualified class or method,
                where method is in the format class::methodName

            --no-suggestions
                Hide suggestions

            --taint-analysis
                Run Psalm in taint analysis mode – see https://psalm.dev/docs/security_analysis for more info

            --dump-taint-graph=OUTPUT_PATH
                Output the taint graph using the DOT language – requires --taint-analysis

        Issue baselines:
            --set-baseline[=PATH]
                Save all current error level issues to a file, to mark them as info in subsequent runs

                Add --include-php-versions to also include a list of PHP extension versions

                Default value is `psalm-baseline.xml`

            --use-baseline=PATH
                Allows you to use a baseline other than the default baseline provided in your config

            --ignore-baseline
                Ignore the error baseline

            --update-baseline
                Update the baseline by removing fixed issues. This will not add new issues to the baseline

                Add --include-php-versions to also include a list of PHP extension versions

        Plugins:
            --plugin=PATH
                Executes a plugin, an alternative to using the Psalm config

        Output:
            -m, --monochrome
                Enable monochrome output

            --output-format=console
                Changes the output format.
                Available formats:
                    $outputFormats

            --no-progress
                Disable the progress indicator

            --long-progress
                Use a progress indicator suitable for Continuous Integration logs

            --stats
                Shows a breakdown of Psalm’s ability to infer types in the codebase

        Reports:
            --report=PATH
                The path where to output report file. The output format is based on the file extension.
                (Currently supported formats: $reportFormats)

            --report-show-info[=BOOLEAN]
                Whether the report should include non-errors in its output (defaults to true)

        Caching:
            --consolidate-cache
                Consolidates all cache files that Psalm uses for this specific project into a single file,
                for quicker runs when doing whole project scans.  
                Make sure to consolidate the cache again after running Psalm before saving the cache via CI.

            --clear-cache
                Clears all cache files that Psalm uses for this specific project

            --clear-global-cache
                Clears all cache files that Psalm uses for all projects

            --no-cache
                Runs Psalm without using cache

            --no-reflection-cache
                Runs Psalm without using cached representations of unchanged classes and files.
                Useful if you want the afterClassLikeVisit plugin hook to run every time you visit a file.

            --no-reference-cache
                Runs Psalm without using cached representations of unchanged methods.

            --no-file-cache
                Runs Psalm without using caching every single file for later diffing.
                This reduces the space Psalm uses on disk and file I/O.

        Miscellaneous:
            -h, --help
                Display this help message

            -v, --version
                Display the Psalm version

            -i, --init [source_dir=src] [level=3]
                Create a psalm config file in the current directory that points to [source_dir]
                at the required level, from 1, most strict, to 8, most permissive.

            --debug
                Debug information

            --debug-by-line
                Debug information on a line-by-line level

            --debug-emitted-issues
                Print a php backtrace to stderr when emitting issues.

            -r, --root
                If running Psalm globally you’ll need to specify a project root. Defaults to cwd

            --generate-json-map=PATH
                Generate a map of node references and types in JSON format, saved to the given path.

            --generate-stubs=PATH
                Generate stubs for the project and dump the file in the given path

            --shepherd[=endpoint]
                Send analysis statistics to Shepherd (shepherd.dev) or your server.

            --alter
                Run Psalter

            --review
                Run the psalm-review tool

            --language-server
                Run Psalm Language Server

        HELP;
        // phpcs:enable
    }
}
