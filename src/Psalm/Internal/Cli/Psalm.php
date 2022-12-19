<?php

namespace Psalm\Internal\Cli;

use Composer\Autoload\ClassLoader;
use Psalm\Config;
use Psalm\Config\Creator;
use Psalm\ErrorBaseline;
use Psalm\Exception\ConfigCreationException;
use Psalm\Exception\ConfigException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\CliUtils;
use Psalm\Internal\Codebase\ReferenceMapGenerator;
use Psalm\Internal\Composer;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Fork\Pool;
use Psalm\Internal\Fork\PsalmRestarter;
use Psalm\Internal\IncludeCollector;
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
use Symfony\Component\Filesystem\Path;

use function array_filter;
use function array_map;
use function array_merge;
use function array_slice;
use function array_sum;
use function array_values;
use function chdir;
use function count;
use function file_exists;
use function file_put_contents;
use function fwrite;
use function gc_collect_cycles;
use function gc_disable;
use function getcwd;
use function getenv;
use function implode;
use function in_array;
use function ini_get;
use function ini_set;
use function json_encode;
use function max;
use function microtime;
use function preg_match;
use function preg_replace;
use function realpath;
use function setlocale;
use function str_repeat;
use function strpos;
use function substr;
use function version_compare;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const LC_CTYPE;
use const PHP_EOL;
use const PHP_OS;
use const PHP_VERSION;
use const STDERR;

// phpcs:disable PSR1.Files.SideEffects

require_once __DIR__ . '/Options.php';
require_once __DIR__ . '/../ErrorHandler.php';
require_once __DIR__ . '/../CliUtils.php';
require_once __DIR__ . '/../Composer.php';
require_once __DIR__ . '/../IncludeCollector.php';
require_once __DIR__ . '/../../IssueBuffer.php';

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
        'no-cache',
        'no-reflection-cache',
        'no-file-cache',
        'output-format:',
        'plugin:',
        'report:',
        'report-show-info:',
        'root:',
        'set-baseline:',
        'show-info:',
        'show-snippet:',
        'stats',
        'threads:',
        'update-baseline',
        'use-baseline:',
        'use-ini-defaults',
        'version',
        'php-version:',
        'generate-json-map:',
        'generate-stubs:',
        'alter',
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
     */
    public static function run(array $argv): void
    {
        CliUtils::checkRuntimeRequirements();
        gc_collect_cycles();
        gc_disable();

        ErrorHandler::install($argv);

        $args = array_slice($argv, 1);

        // get options from command line
        $options = Options::fromGetopt();

        self::forwardCliCall($options, $argv);

        self::validateCliArguments($args);

        self::setMemoryLimit($options);

        if ($options->help) {
            echo self::getHelpText();
            exit;
        }

        $current_dir = self::getCurrentDir($options);

        $path_to_config = CliUtils::getPathToConfig($options->config);

        $vendor_dir = CliUtils::getVendorDir($current_dir);

        // capture environment before registering autoloader (it may destroy it)
        IssueBuffer::captureServer($_SERVER);

        $include_collector = new IncludeCollector();
        $first_autoloader = $include_collector->runAndCollect(
            // we ignore the FQN because of a hack in scoper.inc that needs full path
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
            static fn(): ?\Composer\Autoload\ClassLoader =>
                CliUtils::requireAutoloaders($current_dir, $options->root !== null, $vendor_dir)
        );

        if ($options->version) {
            echo 'Psalm ' . PSALM_VERSION . PHP_EOL;
            exit;
        }

        $output_format = self::initOutputFormat($options);

        [$config, $init_source_dir] = self::initConfig(
            $current_dir,
            $args,
            $vendor_dir,
            $first_autoloader,
            $path_to_config,
            $output_format,
            $options->taint_analysis,
            $options,
        );

        if ($options->no_cache) {
            $config->cache_directory = null;
        }

        $config->setIncludeCollector($include_collector);

        $in_ci = CliUtils::runningInCI();        // disable progressbar on CI

        if ($in_ci) {
            $options->long_progress = true;
        }

        $threads = self::detectThreads($options, $config, $in_ci);

        self::emitMacPcreWarning($options, $threads);

        self::restart($options, $threads);

        if ($options->debug_emitted_issues) {
            $config->debug_emitted_issues = true;
        }

        setlocale(LC_CTYPE, 'C');

        $paths_to_check = CliUtils::getPathsToCheck($options->files);

        if ($config->resolve_from_config_file) {
            $current_dir = $config->base_dir;
            chdir($current_dir);
        }

        if ($options->shepherd !== null || getenv('PSALM_SHEPHERD')) {
            if ($options->shepherd !== null) {
                if ($options->shepherd !== '') {
                    $config->shepherd_host = $options->shepherd;
                }
            } elseif (getenv('PSALM_SHEPHERD')) {
                if (false !== ($shepherd_host = getenv('PSALM_SHEPHERD_HOST'))) {
                    $config->shepherd_host = $shepherd_host;
                }
            }
            $shepherd_plugin = Path::canonicalize(__DIR__ . '/../../Plugin/Shepherd.php');

            if (!file_exists($shepherd_plugin)) {
                die('Could not find Shepherd plugin location ' . $shepherd_plugin . PHP_EOL);
            }

            $options->plugin[] = $shepherd_plugin;
        }

        if ($options->clear_cache) {
            self::clearCache($config);
        }

        if ($options->clear_global_cache) {
            self::clearGlobalCache($config);
        }

        $progress = self::initProgress($options, $config);
        $providers = self::initProviders($options, $config, $current_dir);

        $stdout_report_options = self::initStdoutReportOptions($options, $options->show_info, $output_format, $in_ci);

        $project_analyzer = new ProjectAnalyzer(
            $config,
            $providers,
            $stdout_report_options,
            ProjectAnalyzer::getFileReportOptions(
                $options->report,
                $options->report_show_info,
            ),
            $threads,
            $progress,
        );

        CliUtils::initPhpVersion($options, $config, $project_analyzer);

        $start_time = microtime(true);

        self::configureProjectAnalyzer(
            $options,
            $config,
            $project_analyzer,
            $options->find_references_to,
            $config->find_unused_code ? 'auto' : ($options->find_unused_code ?? false),
            $options->find_unused_variables,
            $options->taint_analysis,
        );

        /** @var string $plugin_path */
        foreach ($options->plugin as $plugin_path) {
            $config->addPluginPath($plugin_path);
        }

        if ($paths_to_check === null) {
            $project_analyzer->check(
                $current_dir,
                !$options->no_diff
                    && $options->set_baseline !== null
                    && !$options->update_baseline
                    && !$config->run_taint_analysis
                    && !$options->taint_analysis,
            );
        } elseif ($paths_to_check) {
            $project_analyzer->checkPaths($paths_to_check);
        }

        if ($options->find_references_to) {
            $project_analyzer->findReferencesTo($options->find_references_to);
        }

        self::storeFlowGraph($options, $project_analyzer);

        if ($options->generate_json_map !== null) {
            self::storeTypeMap($providers, $config, $options->generate_json_map);
        }

        if ($options->generate_stubs !== null) {
            self::generateStubs($options->generate_stubs, $providers, $project_analyzer);
        }

        if (!$options->init) {
            IssueBuffer::finish(
                $project_analyzer,
                !$paths_to_check,
                $start_time,
                $options->stats,
                self::initBaseline($options, $config, $current_dir, $path_to_config),
            );
        } else {
            self::autoGenerateConfig($project_analyzer, $current_dir, $init_source_dir, $vendor_dir);
        }
    }

    private static function initOutputFormat(Options $options): string
    {
        return $options->output_format ?? Report::TYPE_CONSOLE;
    }

    /**
     * @param array<int,string> $args
     */
    private static function validateCliArguments(array $args): void
    {
        array_map(
            static function (string $arg): void {
                if (strpos($arg, '--') === 0 && $arg !== '--') {
                    $arg_name = preg_replace('/=.*$/', '', substr($arg, 2), 1);

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
                } elseif (strpos($arg, '-') === 0 && $arg !== '-' && $arg !== '--') {
                    $arg_name = preg_replace('/=.*$/', '', substr($arg, 1));

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

    private static function setMemoryLimit(Options $options): void
    {
        if (!$options->use_ini_defaults) {
            ini_set('display_errors', 'stderr');
            ini_set('display_startup_errors', '1');

            $memoryLimit = $options->memory_limit ?? 8 * 1_024 * 1_024 * 1_024;
            ini_set('memory_limit', (string) $memoryLimit);
        }
    }

    /**
     * @param array<int, string> $args
     */
    private static function generateConfig(string $current_dir, array &$args): void
    {
        if (file_exists($current_dir . 'psalm.xml')) {
            die('A config file already exists in the current directory' . PHP_EOL);
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
                && strpos($arg, '--disable-extension=') !== 0
                && strpos($arg, '--root=') !== 0
                && strpos($arg, '--r=') !== 0
        ));

        $init_level = null;
        $init_source_dir = null;
        if (count($args)) {
            if (count($args) > 2) {
                die('Too many arguments provided for psalm --init' . PHP_EOL);
            }

            if (isset($args[1])) {
                if (!preg_match('/^[1-8]$/', $args[1])) {
                    die('Config strictness must be a number between 1 and 8 inclusive' . PHP_EOL);
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
                die($e->getMessage() . PHP_EOL);
            }

            if (!file_put_contents($current_dir . 'psalm.xml', $template_contents)) {
                die('Could not write to psalm.xml' . PHP_EOL);
            }

            exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
        }
    }

    private static function loadConfig(
        ?string $path_to_config,
        string $current_dir,
        string $output_format,
        ?ClassLoader $first_autoloader,
        bool $run_taint_analysis,
        Options $options
    ): Config {
        $config = CliUtils::initializeConfig(
            $path_to_config,
            $current_dir,
            $output_format,
            $first_autoloader,
            $run_taint_analysis,
        );

        if ($options->error_level !== null) {
            if (!in_array($options->error_level, [1, 2, 3, 4, 5, 6, 7, 8], true)) {
                throw new ConfigException('Invalid error level ' . $options->error_level);
            }
            $config->level = $options->error_level;
        }
        return $config;
    }

    private static function initProgress(Options $options, Config $config): Progress
    {
        if ($options->debug || $options->debug_by_line) {
            $progress = new DebugProgress();
        } elseif ($options->no_progress) {
            $progress = new VoidProgress();
        } else {
            $show_errors = !$config->error_baseline || $options->ignore_baseline;
            if ($options->long_progress) {
                $progress = new LongProgress($show_errors, $options->show_info);
            } else {
                $progress = new DefaultProgress($show_errors, $options->show_info);
            }
        }
        return $progress;
    }

    private static function initProviders(Options $options, Config $config, string $current_dir): Providers
    {
        if ($options->no_cache || $options->init) {
            $providers = new Providers(new FileProvider);
        } else {
            $file_storage_cache_provider = $options->no_reflection_cache
                ? null
                : new FileStorageCacheProvider($config);

            $classlike_storage_cache_provider = $options->no_reflection_cache
                ? null
                : new ClassLikeStorageCacheProvider($config);

            $providers = new Providers(
                new FileProvider,
                new ParserCacheProvider($config, !$options->no_file_cache),
                $file_storage_cache_provider,
                $classlike_storage_cache_provider,
                new FileReferenceCacheProvider($config),
                new ProjectCacheProvider(Composer::getLockFilePath($current_dir)),
            );
        }
        return $providers;
    }

    /**
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function generateBaseline(
        Options $options,
        Config $config,
        string $current_dir,
        ?string $path_to_config
    ): array {
        fwrite(STDERR, 'Writing error baseline to file...' . PHP_EOL);

        try {
            $issue_baseline = ErrorBaseline::read(
                new FileProvider,
                $options->set_baseline,
            );
        } catch (ConfigException $e) {
            $issue_baseline = [];
        }

        ErrorBaseline::create(
            new FileProvider,
            $options->set_baseline,
            IssueBuffer::getIssuesData(),
            $config->include_php_versions_in_error_baseline || $options->include_php_versions,
        );

        fwrite(STDERR, "Baseline saved to {$options->set_baseline}.");

        CliUtils::updateConfigFile(
            $config,
            $path_to_config ?? $current_dir,
            $options->set_baseline,
        );

        fwrite(STDERR, PHP_EOL);

        return $issue_baseline;
    }

    /**
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function updateBaseline(Options $options, Config $config): array
    {
        $baselineFile = $config->error_baseline;

        if (empty($baselineFile)) {
            die('Cannot update baseline, because no baseline file is configured.' . PHP_EOL);
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
                $config->include_php_versions_in_error_baseline || $options->include_php_versions,
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
        string $vendor_dir
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
            die($e->getMessage() . PHP_EOL);
        }

        if (!file_put_contents($current_dir . 'psalm.xml', $template_contents)) {
            die('Could not write to psalm.xml' . PHP_EOL);
        }

        exit('Config file created successfully. Please re-run psalm.' . PHP_EOL);
    }

    private static function initStdoutReportOptions(
        Options $options,
        bool $show_info,
        string $output_format,
        bool $in_ci
    ): ReportOptions {
        $stdout_report_options = new ReportOptions();
        $stdout_report_options->use_color = !$options->monochrome;
        $stdout_report_options->show_info = $show_info;
        $stdout_report_options->show_suggestions = !$options->no_suggestions;
        /**
         * @psalm-suppress PropertyTypeCoercion
         */
        $stdout_report_options->format = $output_format;
        $stdout_report_options->show_snippet = $options->show_snippet;
        $stdout_report_options->pretty = $options->pretty_print;
        $stdout_report_options->in_ci = $in_ci;

        return $stdout_report_options;
    }

    /** @return never */
    private static function clearGlobalCache(Config $config): void
    {
        $cache_directory = $config->getGlobalCacheDirectory();

        if ($cache_directory) {
            Config::removeCacheDirectory($cache_directory);
            echo 'Global cache directory deleted' . PHP_EOL;
        }

        exit;
    }

    /** @return never */
    private static function clearCache(Config $config): void
    {
        $cache_directory = $config->getCacheDirectory();

        if ($cache_directory !== null) {
            Config::removeCacheDirectory($cache_directory);
        }
        echo 'Cache directory deleted' . PHP_EOL;
        exit;
    }

    private static function getCurrentDir(Options $options): string
    {
        $cwd = getcwd();
        if (false === $cwd) {
            fwrite(STDERR, 'Cannot get current working directory' . PHP_EOL);
            exit(1);
        }

        $current_dir = $cwd . DIRECTORY_SEPARATOR;

        if ($options->root !== null) {
            $root_path = realpath($options->root);

            if (!$root_path) {
                fwrite(
                    STDERR,
                    'Could not locate root directory ' . $current_dir . DIRECTORY_SEPARATOR . $options->root . PHP_EOL,
                );
                exit(1);
            }

            $current_dir = $root_path . DIRECTORY_SEPARATOR;
        }

        return $current_dir;
    }

    private static function emitMacPcreWarning(Options $options, int $threads): void
    {
        if ($options->threads === null
            && !$options->debug
            && $threads === 1
            && ini_get('pcre.jit') === '1'
            && PHP_OS === 'Darwin'
            && version_compare(PHP_VERSION, '7.3.0') >= 0
            && version_compare(PHP_VERSION, '7.4.0') < 0
        ) {
            echo(
                'If you want to run Psalm as a language server, or run Psalm with' . PHP_EOL
                    . 'multiple processes (--threads=4), beware:' . PHP_EOL
                    . Pool::MAC_PCRE_MESSAGE . PHP_EOL . PHP_EOL
            );
        }
    }

    private static function restart(Options $options, int $threads): void
    {
        $ini_handler = new PsalmRestarter('PSALM');

        foreach ($options->disable_extension as $extension) {
            $ini_handler->disableExtension($extension);
        }

        if ($threads > 1) {
            $ini_handler->disableExtension('grpc');
        }

        $ini_handler->disableExtension('uopz');

        // If Xdebug is enabled, restart without it
        $ini_handler->check();
    }

    private static function detectThreads(Options $options, Config $config, bool $in_ci): int
    {
        if ($options->threads !== null) {
            $threads = $options->threads;
        } elseif ($options->debug || $in_ci) {
            $threads = 1;
        } elseif ($config->threads) {
            $threads = $config->threads;
        } else {
            $threads = max(1, ProjectAnalyzer::getCpuCount() - 1);
        }
        return $threads;
    }

    /** @psalm-suppress UnusedParam $argv is being reported as unused */
    private static function forwardCliCall(Options $options, array $argv): void
    {
        if ($options->alter) {
            require_once __DIR__ . '/Psalter.php';
            Psalter::run($argv);
            exit;
        }

        if ($options->language_server) {
            require_once __DIR__ . '/LanguageServer.php';
            LanguageServer::run($argv);
            exit;
        }

        if ($options->refactor) {
            require_once __DIR__ . '/Refactor.php';
            Refactor::run($argv);
            exit;
        }
    }

    /**
     * @param array<int, string> $args
     * @return array{Config,?string}
     */
    private static function initConfig(
        string $current_dir,
        array $args,
        string $vendor_dir,
        ?ClassLoader $first_autoloader,
        ?string $path_to_config,
        string $output_format,
        bool $run_taint_analysis,
        Options $options
    ): array {
        $init_source_dir = null;
        if ($options->init) {
            self::generateConfig($current_dir, $args);
            // if we ever got here, it means we need to run Psalm once and generate the config
            // based on the errors we find
            $init_source_dir = $args[0] ?? null;

            echo "Calculating best config level based on project files\n";
            Creator::createBareConfig($current_dir, $init_source_dir, $vendor_dir);
            $config = Config::getInstance();
            $config->setComposerClassLoader($first_autoloader);
        } else {
            $config = self::loadConfig(
                $path_to_config,
                $current_dir,
                $output_format,
                $first_autoloader,
                $run_taint_analysis,
                $options,
            );
        }
        return [$config, $init_source_dir];
    }

    /**
     * @return array<string,array<string,array{o:int, s: list<string>}>>
     */
    private static function initBaseline(
        Options $options,
        Config $config,
        string $current_dir,
        ?string $path_to_config
    ): array {
        $issue_baseline = [];

        if ($options->set_baseline !== null) {
            $issue_baseline = self::generateBaseline($options, $config, $current_dir, $path_to_config);
        }

        if ($options->use_baseline !== null) {
            $baseline_file_path = $options->use_baseline;
            $config->error_baseline = $baseline_file_path;
        } else {
            $baseline_file_path = $config->error_baseline;
        }

        if ($options->update_baseline) {
            $issue_baseline = self::updateBaseline($options, $config);
        }

        if (!$issue_baseline && $baseline_file_path && !$options->ignore_baseline) {
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

        return $issue_baseline;
    }

    private static function storeFlowGraph(Options $options, ProjectAnalyzer $project_analyzer): void
    {
        $flow_graph = $project_analyzer->getCodebase()->taint_flow_graph;
        if ($flow_graph !== null && $options->dump_taint_graph !== null) {
            file_put_contents($options->dump_taint_graph, "digraph Taints {\n\t".
                implode("\n\t", array_map(
                    static fn(array $edges) => '"'.implode('" -> "', $edges).'"',
                    $flow_graph->summarizeEdges(),
                )) .
                "\n}\n");
        }
    }

    /**
     * @param string|bool|null $find_references_to
     * @param false|'always'|'auto' $find_unused_code
     */
    private static function configureProjectAnalyzer(
        Options $options,
        Config $config,
        ProjectAnalyzer $project_analyzer,
        $find_references_to,
        $find_unused_code,
        bool $find_unused_variables,
        bool $run_taint_analysis
    ): void {
        if ($options->generate_json_map !== null) {
            $project_analyzer->getCodebase()->store_node_types = true;
        }

        if ($options->debug_by_line) {
            $project_analyzer->debug_lines = true;
        }

        if ($options->debug_performance) {
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

        if ($config->run_taint_analysis || $run_taint_analysis) {
            $project_analyzer->trackTaintedInputs();
        }

        if ($config->find_unused_psalm_suppress || $options->find_unused_psalm_suppress) {
            $project_analyzer->trackUnusedSuppressions();
        }
    }

    private static function generateStubs(
        string $file_path,
        Providers $providers,
        ProjectAnalyzer $project_analyzer
    ): void {
        $providers->file_provider->setContents(
            $file_path,
            StubsGenerator::getAll(
                $project_analyzer->getCodebase(),
                $providers->classlike_storage_provider,
                $providers->file_storage_provider,
            ),
        );
    }

    /**
     * @psalm-pure
     */
    private static function getHelpText(): string
    {
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

            --disable-extension=EXTENSION
                Used to disable certain extensions while Psalm is running.

            --threads=INT
                If greater than one, Psalm will run analysis on multiple threads, speeding things up.

            --no-diff
                Turns off Psalm’s diff mode, checks all files regardless of whether they’ve changed.

            --php-version=PHP_VERSION
                Explicitly set PHP version to analyse code against.

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
            --set-baseline=PATH
                Save all current error level issues to a file, to mark them as info in subsequent runs

                Add --include-php-versions to also include a list of PHP extension versions

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
                Available formats: compact, console, text, emacs, json, pylint, xml, checkstyle, junit, sonarqube,
                                   github, phpstorm, codeclimate, by-issue-level

            --no-progress
                Disable the progress indicator

            --long-progress
                Use a progress indicator suitable for Continuous Integration logs

            --stats
                Shows a breakdown of Psalm’s ability to infer types in the codebase

        Reports:
            --report=PATH
                The path where to output report file. The output format is based on the file extension.
                (Currently supported formats: ".json", ".xml", ".txt", ".emacs", ".pylint", ".console",
                ".sarif", "checkstyle.xml", "sonarqube.json", "codeclimate.json", "summary.json", "junit.xml")

            --report-show-info[=BOOLEAN]
                Whether the report should include non-errors in its output (defaults to true)

        Caching:
            --clear-cache
                Clears all cache files that Psalm uses for this specific project

            --clear-global-cache
                Clears all cache files that Psalm uses for all projects

            --no-cache
                Runs Psalm without using cache

            --no-reflection-cache
                Runs Psalm without using cached representations of unchanged classes and files.
                Useful if you want the afterClassLikeVisit plugin hook to run every time you visit a file.

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

            -r PATH, --root=PATH
                If running Psalm globally you’ll need to specify a project root. Defaults to cwd

            --generate-json-map=PATH
                Generate a map of node references and types in JSON format, saved to the given path.

            --generate-stubs=PATH
                Generate stubs for the project and dump the file in the given path

            --shepherd[=host]
                Send data to Shepherd, Psalm’s GitHub integration tool.

            --alter
                Run Psalter

            --language-server
                Run Psalm Language Server

        HELP;
    }
}
