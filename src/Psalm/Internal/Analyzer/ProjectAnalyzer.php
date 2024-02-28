<?php

namespace Psalm\Internal\Analyzer;

use Fidry\CpuCoreCounter\CpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
use InvalidArgumentException;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\RefactorException;
use Psalm\Exception\UnsupportedIssueToFixException;
use Psalm\FileManipulation;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\LanguageServer\LanguageServer;
use Psalm\Internal\MethodIdentifier;
use Psalm\Internal\Provider\ClassLikeStorageProvider;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\ParserCacheProvider;
use Psalm\Internal\Provider\ProjectCacheProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\Provider\StatementsProvider;
use Psalm\Issue\CodeIssue;
use Psalm\Issue\InvalidFalsableReturnType;
use Psalm\Issue\InvalidNullableReturnType;
use Psalm\Issue\InvalidReturnType;
use Psalm\Issue\LessSpecificReturnType;
use Psalm\Issue\MismatchingDocblockParamType;
use Psalm\Issue\MismatchingDocblockReturnType;
use Psalm\Issue\MissingClosureReturnType;
use Psalm\Issue\MissingParamType;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\MissingReturnType;
use Psalm\Issue\ParamNameMismatch;
use Psalm\Issue\PossiblyUndefinedGlobalVariable;
use Psalm\Issue\PossiblyUndefinedVariable;
use Psalm\Issue\PossiblyUnusedMethod;
use Psalm\Issue\PossiblyUnusedProperty;
use Psalm\Issue\RedundantCast;
use Psalm\Issue\RedundantCastGivenDocblockType;
use Psalm\Issue\UnnecessaryVarAnnotation;
use Psalm\Issue\UnusedMethod;
use Psalm\Issue\UnusedProperty;
use Psalm\Issue\UnusedVariable;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use Psalm\Type;
use ReflectionProperty;
use UnexpectedValueException;

use function array_combine;
use function array_diff;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_shift;
use function clearstatcache;
use function count;
use function defined;
use function dirname;
use function end;
use function explode;
use function extension_loaded;
use function file_exists;
use function fwrite;
use function implode;
use function in_array;
use function is_dir;
use function is_file;
use function microtime;
use function mkdir;
use function number_format;
use function preg_match;
use function rename;
use function sprintf;
use function strlen;
use function strpos;
use function strtolower;
use function substr;
use function usort;

use const PHP_EOL;
use const PSALM_VERSION;
use const STDERR;

/**
 * @internal
 */
final class ProjectAnalyzer
{
    /**
     * Cached config
     */
    private Config $config;

    public static ProjectAnalyzer $instance;

    /**
     * An object representing everything we know about the code
     */
    private Codebase $codebase;

    private FileProvider $file_provider;

    private ClassLikeStorageProvider $classlike_storage_provider;

    private ?ParserCacheProvider $parser_cache_provider = null;

    public ?ProjectCacheProvider $project_cache_provider = null;

    private FileReferenceProvider $file_reference_provider;

    public Progress $progress;

    public bool $debug_lines = false;

    public bool $debug_performance = false;

    public bool $show_issues = true;

    public int $threads;

    /**
     * @var array<string, bool>
     */
    private array $issues_to_fix = [];

    public bool $dry_run = false;

    public bool $full_run = false;

    public bool $only_replace_php_types_with_non_docblock_types = false;

    public ?int $onchange_line_limit = null;

    public bool $provide_completion = false;

    /**
     * @var list<string>
     */
    public array $check_paths_files = [];

    /**
     * @var array<string,string>
     */
    private array $project_files = [];

    /**
     * @var array<string,string>
     */
    private array $extra_files = [];

    /**
     * @var array<string, string>
     */
    private array $to_refactor = [];

    public ?ReportOptions $stdout_report_options = null;

    /**
     * @var array<ReportOptions>
     */
    public array $generated_report_options;

    /**
     * @var array<int, class-string<CodeIssue>>
     */
    private const SUPPORTED_ISSUES_TO_FIX = [
        InvalidFalsableReturnType::class,
        InvalidNullableReturnType::class,
        InvalidReturnType::class,
        LessSpecificReturnType::class,
        MismatchingDocblockParamType::class,
        MismatchingDocblockReturnType::class,
        MissingClosureReturnType::class,
        MissingParamType::class,
        MissingPropertyType::class,
        MissingReturnType::class,
        ParamNameMismatch::class,
        PossiblyUndefinedGlobalVariable::class,
        PossiblyUndefinedVariable::class,
        PossiblyUnusedMethod::class,
        PossiblyUnusedProperty::class,
        RedundantCast::class,
        RedundantCastGivenDocblockType::class,
        UnusedMethod::class,
        UnusedProperty::class,
        UnusedVariable::class,
        UnnecessaryVarAnnotation::class,
    ];

    private const PHP_VERSION_REGEX = '^(0|[1-9]\d*)\.(0|[1-9]\d*)(?:\..*)?$';

    private const PHP_SUPPORTED_VERSIONS_REGEX = '^(5\.[456]|7\.[01234]|8\.[0123])(\..*)?$';

    /**
     * @param array<ReportOptions> $generated_report_options
     */
    public function __construct(
        Config $config,
        Providers $providers,
        ?ReportOptions $stdout_report_options = null,
        array $generated_report_options = [],
        int $threads = 1,
        ?Progress $progress = null,
        ?Codebase $codebase = null
    ) {
        if ($progress === null) {
            $progress = new VoidProgress();
        }

        if ($codebase === null) {
            $codebase = new Codebase(
                $config,
                $providers,
                $progress,
            );
        }

        $this->parser_cache_provider = $providers->parser_cache_provider;
        $this->project_cache_provider = $providers->project_cache_provider;
        $this->file_provider = $providers->file_provider;
        $this->classlike_storage_provider = $providers->classlike_storage_provider;
        $this->file_reference_provider = $providers->file_reference_provider;

        $this->progress = $progress;
        $this->threads = $threads;
        $this->config = $config;

        $this->clearCacheDirectoryIfConfigOrComposerLockfileChanged();

        $this->codebase = $codebase;

        $this->stdout_report_options = $stdout_report_options;
        $this->generated_report_options = $generated_report_options;

        $this->config->processPluginFileExtensions($this);
        $file_extensions = $this->config->getFileExtensions();

        foreach ($this->config->getProjectDirectories() as $dir_name) {
            $file_paths = $this->file_provider->getFilesInDir(
                $dir_name,
                $file_extensions,
                [$this->config, 'isInProjectDirs'],
            );

            foreach ($file_paths as $file_path) {
                $this->addProjectFile($file_path);
            }
        }

        foreach ($this->config->getExtraDirectories() as $dir_name) {
            $file_paths = $this->file_provider->getFilesInDir(
                $dir_name,
                $file_extensions,
                [$this->config, 'isInExtraDirs'],
            );

            foreach ($file_paths as $file_path) {
                $this->addExtraFile($file_path);
            }
        }

        foreach ($this->config->getProjectFiles() as $file_path) {
            $this->addProjectFile($file_path);
        }

        self::$instance = $this;
    }

    private function clearCacheDirectoryIfConfigOrComposerLockfileChanged(): void
    {
        $cache_directory = $this->config->getCacheDirectory();
        if ($cache_directory === null) {
            return;
        }

        if ($this->project_cache_provider
            && $this->project_cache_provider->hasLockfileChanged()
        ) {
            // we only clear the cache if it actually exists
            // if it's not populated yet, we don't clear anything but populate the cache instead
            clearstatcache(true, $cache_directory);
            if (is_dir($cache_directory)) {
                $this->progress->debug(
                    'Composer lockfile change detected, clearing cache directory ' . $cache_directory . "\n",
                );

                Config::removeCacheDirectory($cache_directory);
            }

            if ($this->file_reference_provider->cache) {
                $this->file_reference_provider->cache->setConfigHashCache();
            }

            $this->project_cache_provider->updateComposerLockHash();
        } elseif ($this->file_reference_provider->cache
            && $this->file_reference_provider->cache->hasConfigChanged()
        ) {
            clearstatcache(true, $cache_directory);
            if (is_dir($cache_directory)) {
                $this->progress->debug(
                    'Config change detected, clearing cache directory ' . $cache_directory . "\n",
                );

                Config::removeCacheDirectory($cache_directory);
            }

            $this->file_reference_provider->cache->setConfigHashCache();

            if ($this->project_cache_provider) {
                $this->project_cache_provider->updateComposerLockHash();
            }
        }
    }

    /**
     * @param  array<string>  $report_file_paths
     * @return list<ReportOptions>
     */
    public static function getFileReportOptions(array $report_file_paths, bool $show_info = true): array
    {
        $report_options = [];

        $mapping = [
            'checkstyle.xml' => Report::TYPE_CHECKSTYLE,
            'sonarqube.json' => Report::TYPE_SONARQUBE,
            'codeclimate.json' => Report::TYPE_CODECLIMATE,
            'summary.json' => Report::TYPE_JSON_SUMMARY,
            'junit.xml' => Report::TYPE_JUNIT,
            '.xml' => Report::TYPE_XML,
            '.json' => Report::TYPE_JSON,
            '.txt' => Report::TYPE_TEXT,
            '.emacs' => Report::TYPE_EMACS,
            '.pylint' => Report::TYPE_PYLINT,
            '.console' => Report::TYPE_CONSOLE,
            '.sarif' => Report::TYPE_SARIF,
            'count.txt' => Report::TYPE_COUNT,
        ];

        foreach ($report_file_paths as $report_file_path) {
            foreach ($mapping as $extension => $type) {
                if (substr($report_file_path, -strlen($extension)) === $extension) {
                    $o = new ReportOptions();

                    $o->format = $type;
                    $o->show_info = $show_info;
                    $o->output_path = $report_file_path;
                    $o->use_color = false;
                    $report_options[] = $o;
                    continue 2;
                }
            }

            throw new UnexpectedValueException('Unknown report format ' . $report_file_path);
        }

        return $report_options;
    }

    private function visitAutoloadFiles(): void
    {
        $start_time = microtime(true);

        $this->config->visitComposerAutoloadFiles($this, $this->progress);

        $now_time = microtime(true);

        $this->progress->debug(
            'Visiting autoload files took ' . number_format($now_time - $start_time, 3) . 's' . "\n",
        );
    }

    public function serverMode(LanguageServer $server): void
    {
        $server->logInfo("Initializing: Visiting Autoload Files...");
        $this->visitAutoloadFiles();
        $this->codebase->diff_methods = true;
        $server->logInfo("Initializing: Loading Reference Cache...");
        $this->file_reference_provider->loadReferenceCache();
        $this->codebase->enterServerMode();

        $cpu_count = self::getCpuCount();

        // let's not go crazy
        $usable_cpus = $cpu_count - 2;

        if ($usable_cpus > 1) {
            $this->threads = $usable_cpus;
        }

        $server->logInfo("Initializing: Initialize Plugins...");
        $this->config->initializePlugins($this);

        foreach ($this->config->getProjectDirectories() as $dir_name) {
            $this->checkDirWithConfig($dir_name, $this->config);
        }
    }

    /** @psalm-mutation-free */
    public static function getInstance(): ProjectAnalyzer
    {
        /** @psalm-suppress ImpureStaticProperty */
        return self::$instance;
    }

    /** @psalm-mutation-free */
    public function canReportIssues(string $file_path): bool
    {
        return isset($this->project_files[$file_path]);
    }

    private function generatePHPVersionMessage(): string
    {
        $codebase = $this->codebase;

        switch ($codebase->php_version_source) {
            case 'cli':
                $source = '(set by CLI argument)';
                break;
            case 'config':
                $source = '(set by config file)';
                break;
            case 'composer':
                $source = '(inferred from composer.json)';
                break;
            case 'tests':
                $source = '(set by tests)';
                break;
            case 'runtime':
                $source = '(inferred from current PHP version)';
                break;
        }

        $unsupported_php_extensions = array_diff(
            array_keys($codebase->config->php_extensions_not_supported),
            $codebase->config->php_extensions_supported_by_psalm_callmaps,
        );

        $message = sprintf(
            "Target PHP version: %d.%d %s",
            $codebase->getMajorAnalysisPhpVersion(),
            $codebase->getMinorAnalysisPhpVersion(),
            $source,
        );

        $enabled_extensions_names = array_keys(array_filter($codebase->config->php_extensions));
        if (count($enabled_extensions_names) > 0) {
            $message .= ' Enabled extensions: ' . implode(', ', $enabled_extensions_names);
        }

        if (count($unsupported_php_extensions) > 0) {
            $message .= ' (unsupported extensions: ' . implode(', ', $unsupported_php_extensions) . ')';
        }

        return "$message.\n";
    }

    public function check(string $base_dir, bool $is_diff = false): void
    {
        $start_checks = (int)microtime(true);

        if (!$base_dir) {
            throw new InvalidArgumentException('Cannot work with empty base_dir');
        }

        $diff_files = null;
        $deleted_files = null;

        $this->full_run = true;

        $reference_cache = $this->file_reference_provider->loadReferenceCache(true);

        $this->codebase->diff_methods = $is_diff;

        if ($is_diff
            && $reference_cache
            && $this->project_cache_provider
            && $this->project_cache_provider->canDiffFiles()
        ) {
            $deleted_files = $this->file_reference_provider->getDeletedReferencedFiles();
            $diff_files = [...$deleted_files, ...$this->getDiffFiles()];
        }

        $this->progress->write($this->generatePHPVersionMessage());
        $this->progress->startScanningFiles();

        $diff_no_files = false;

        if ($diff_files === null
            || $deleted_files === null
            || count($diff_files) > 200
        ) {
            $this->config->visitPreloadedStubFiles($this->codebase, $this->progress);
            $this->visitAutoloadFiles();

            $this->codebase->scanner->addFilesToShallowScan($this->extra_files);
            $this->codebase->scanner->addFilesToDeepScan($this->project_files);
            $this->codebase->analyzer->addFilesToAnalyze($this->project_files);

            $this->config->initializePlugins($this);

            $this->codebase->scanFiles($this->threads);

            $this->codebase->infer_types_from_usage = true;
        } else {
            $this->progress->debug(count($diff_files) . ' changed files: ' . "\n");
            $this->progress->debug('    ' . implode("\n    ", $diff_files) . "\n");

            $this->codebase->analyzer->addFilesToShowResults($this->project_files);

            if ($diff_files) {
                $file_list = $this->getReferencedFilesFromDiff($diff_files);

                // strip out deleted files
                $file_list = array_diff($file_list, $deleted_files);

                if ($file_list) {
                    $this->config->visitPreloadedStubFiles($this->codebase, $this->progress);
                    $this->visitAutoloadFiles();

                    $this->checkDiffFilesWithConfig($this->config, $file_list);

                    $this->config->initializePlugins($this);

                    $this->codebase->scanFiles($this->threads);
                } else {
                    $diff_no_files = true;
                }
            } else {
                $diff_no_files = true;
            }
        }

        if (!$diff_no_files) {
            $this->config->visitStubFiles($this->codebase, $this->progress);

            $event = new AfterCodebasePopulatedEvent($this->codebase);

            $this->config->eventDispatcher->dispatchAfterCodebasePopulated($event);
        }

        $this->progress->startAnalyzingFiles();

        $this->codebase->analyzer->analyzeFiles(
            $this,
            $this->threads,
            $this->codebase->alter_code,
            true,
        );

        if ($this->parser_cache_provider && !$is_diff) {
            $removed_parser_files = $this->parser_cache_provider->deleteOldParserCaches($start_checks);

            if ($removed_parser_files) {
                $this->progress->debug('Removed ' . $removed_parser_files . ' old parser caches' . "\n");
            }
        }
    }

    public function consolidateAnalyzedData(): void
    {
        $this->codebase->classlikes->consolidateAnalyzedData(
            $this->codebase->methods,
            $this->progress,
            (bool)$this->codebase->find_unused_code,
        );
    }

    public function trackTaintedInputs(): void
    {
        $this->codebase->taint_flow_graph = new TaintFlowGraph();
    }

    public function trackUnusedSuppressions(): void
    {
        $this->codebase->track_unused_suppressions = true;
    }

    public function interpretRefactors(): void
    {
        if (!$this->codebase->alter_code) {
            throw new UnexpectedValueException('Should not be checking references');
        }

        // interpret wildcards
        foreach ($this->to_refactor as $source => $destination) {
            if (($source_pos = strpos($source, '*'))
                && ($destination_pos = strpos($destination, '*'))
                && $source_pos === (strlen($source) - 1)
                && $destination_pos === (strlen($destination) - 1)
            ) {
                foreach ($this->codebase->classlike_storage_provider->getAll() as $class_storage) {
                    if (strpos($source, substr($class_storage->name, 0, $source_pos)) === 0) {
                        $this->to_refactor[$class_storage->name]
                            = substr($destination, 0, -1) . substr($class_storage->name, $source_pos);
                    }
                }

                unset($this->to_refactor[$source]);
            }
        }

        foreach ($this->to_refactor as $source => $destination) {
            $source_parts = explode('::', $source);
            $destination_parts = explode('::', $destination);

            if (!$this->codebase->classlikes->hasFullyQualifiedClassName($source_parts[0])) {
                throw new RefactorException(
                    'Source class ' . $source_parts[0] . ' doesn’t exist',
                );
            }

            if (count($source_parts) === 1 && count($destination_parts) === 1) {
                if ($this->codebase->classlikes->hasFullyQualifiedClassName($destination_parts[0])) {
                    throw new RefactorException(
                        'Destination class ' . $destination_parts[0] . ' already exists',
                    );
                }

                $source_class_storage = $this->codebase->classlike_storage_provider->get($source_parts[0]);

                $destination_parts = explode('\\', $destination, -1);
                $destination_ns = implode('\\', $destination_parts);

                $this->codebase->classes_to_move[strtolower($source)] = $destination;

                $destination_class_storage = $this->codebase->classlike_storage_provider->create($destination);

                $destination_class_storage->name = $destination;

                if ($source_class_storage->aliases) {
                    $destination_class_storage->aliases = clone $source_class_storage->aliases;
                    $destination_class_storage->aliases->namespace = $destination_ns;
                }

                $destination_class_storage->location = $source_class_storage->location;
                $destination_class_storage->stmt_location = $source_class_storage->stmt_location;
                $destination_class_storage->populated = true;

                $this->codebase->class_transforms[strtolower($source)] = $destination;

                continue;
            }

            $source_method_id = new MethodIdentifier(
                $source_parts[0],
                strtolower($source_parts[1]),
            );

            if ($this->codebase->methods->methodExists($source_method_id)) {
                if ($this->codebase->methods->methodExists(
                    new MethodIdentifier(
                        $destination_parts[0],
                        strtolower($destination_parts[1]),
                    ),
                )) {
                    throw new RefactorException(
                        'Destination method ' . $destination . ' already exists',
                    );
                }

                if (!$this->codebase->classlikes->classExists($destination_parts[0])) {
                    throw new RefactorException(
                        'Destination class ' . $destination_parts[0] . ' doesn’t exist',
                    );
                }

                $source_lc = strtolower($source);
                if (strtolower($source_parts[0]) !== strtolower($destination_parts[0])) {
                    $source_method_storage = $this->codebase->methods->getStorage($source_method_id);
                    $destination_class_storage
                        = $this->codebase->classlike_storage_provider->get($destination_parts[0]);

                    if (!$source_method_storage->is_static
                        && !isset(
                            $destination_class_storage->parent_classes[strtolower($source_method_id->fq_class_name)],
                        )
                    ) {
                        throw new RefactorException(
                            'Cannot move non-static method ' . $source
                                . ' into unrelated class ' . $destination_parts[0],
                        );
                    }

                    $this->codebase->methods_to_move[$source_lc]= $destination;
                } else {
                    $this->codebase->methods_to_rename[$source_lc] = $destination_parts[1];
                }

                $this->codebase->call_transforms[$source_lc . '\((.*\))'] = $destination . '($1)';
                continue;
            }

            if ($source_parts[1][0] === '$') {
                if ($destination_parts[1][0] !== '$') {
                    throw new RefactorException(
                        'Destination property must be of the form Foo::$bar',
                    );
                }

                if (!$this->codebase->properties->propertyExists($source, true)) {
                    throw new RefactorException(
                        'Property ' . $source . ' does not exist',
                    );
                }

                if ($this->codebase->properties->propertyExists($destination, true)) {
                    throw new RefactorException(
                        'Destination property ' . $destination . ' already exists',
                    );
                }

                if (!$this->codebase->classlikes->classExists($destination_parts[0])) {
                    throw new RefactorException(
                        'Destination class ' . $destination_parts[0] . ' doesn’t exist',
                    );
                }

                $source_id = strtolower($source_parts[0]) . '::' . $source_parts[1];

                if (strtolower($source_parts[0]) !== strtolower($destination_parts[0])) {
                    $source_storage = $this->codebase->properties->getStorage($source);

                    if (!$source_storage->is_static) {
                        throw new RefactorException(
                            'Cannot move non-static property ' . $source,
                        );
                    }

                    $this->codebase->properties_to_move[$source_id] = $destination;
                } else {
                    $this->codebase->properties_to_rename[$source_id] = substr($destination_parts[1], 1);
                }

                $this->codebase->property_transforms[$source_id] = $destination;
                continue;
            }

            $source_class_constants = $this->codebase->classlikes->getConstantsForClass(
                $source_parts[0],
                ReflectionProperty::IS_PRIVATE,
            );

            if (isset($source_class_constants[$source_parts[1]])) {
                if (!$this->codebase->classlikes->hasFullyQualifiedClassName($destination_parts[0])) {
                    throw new RefactorException(
                        'Destination class ' . $destination_parts[0] . ' doesn’t exist',
                    );
                }

                $destination_class_constants = $this->codebase->classlikes->getConstantsForClass(
                    $destination_parts[0],
                    ReflectionProperty::IS_PRIVATE,
                );

                if (isset($destination_class_constants[$destination_parts[1]])) {
                    throw new RefactorException(
                        'Destination constant ' . $destination . ' already exists',
                    );
                }

                $source_id = strtolower($source_parts[0]) . '::' . $source_parts[1];

                if (strtolower($source_parts[0]) !== strtolower($destination_parts[0])) {
                    $this->codebase->class_constants_to_move[$source_id] = $destination;
                } else {
                    $this->codebase->class_constants_to_rename[$source_id] = $destination_parts[1];
                }

                $this->codebase->class_constant_transforms[$source_id] = $destination;
                continue;
            }

            throw new RefactorException(
                'Psalm cannot locate ' . $source,
            );
        }
    }

    public function prepareMigration(): void
    {
        if (!$this->codebase->alter_code) {
            throw new UnexpectedValueException('Should not be checking references');
        }

        $this->codebase->classlikes->moveMethods(
            $this->codebase->methods,
            $this->progress,
        );

        $this->codebase->classlikes->moveProperties(
            $this->codebase->properties,
            $this->progress,
        );

        $this->codebase->classlikes->moveClassConstants(
            $this->progress,
        );
    }

    public function migrateCode(): void
    {
        if (!$this->codebase->alter_code) {
            throw new UnexpectedValueException('Should not be checking references');
        }

        $migration_manipulations = FileManipulationBuffer::getMigrationManipulations(
            $this->codebase->file_provider,
        );

        if ($migration_manipulations) {
            foreach ($migration_manipulations as $file_path => $file_manipulations) {
                usort(
                    $file_manipulations,
                    static function (FileManipulation $a, FileManipulation $b): int {
                        if ($a->start === $b->start) {
                            if ($b->end === $a->end) {
                                return $b->insertion_text > $a->insertion_text ? 1 : -1;
                            }

                            return $b->end > $a->end ? 1 : -1;
                        }

                        return $b->start > $a->start ? 1 : -1;
                    },
                );

                $existing_contents = $this->codebase->file_provider->getContents($file_path);

                foreach ($file_manipulations as $manipulation) {
                    $existing_contents = $manipulation->transform($existing_contents);
                }

                $this->codebase->file_provider->setContents($file_path, $existing_contents);
            }
        }

        if ($this->codebase->classes_to_move) {
            foreach ($this->codebase->classes_to_move as $source => $destination) {
                $source_class_storage = $this->codebase->classlike_storage_provider->get($source);

                if (!$source_class_storage->location) {
                    continue;
                }

                $potential_file_path = $this->config->getPotentialComposerFilePathForClassLike($destination);

                if ($potential_file_path && !file_exists($potential_file_path)) {
                    $containing_dir = dirname($potential_file_path);

                    if (!file_exists($containing_dir)) {
                        mkdir($containing_dir, 0777, true);
                    }

                    rename($source_class_storage->location->file_path, $potential_file_path);
                }
            }
        }
    }

    public function findReferencesTo(string $symbol): void
    {
        if (!$this->stdout_report_options) {
            throw new UnexpectedValueException('Not expecting to emit output');
        }

        $locations = $this->codebase->findReferencesToSymbol($symbol);

        foreach ($locations as $location) {
            $snippet = $location->getSnippet();

            $snippet_bounds = $location->getSnippetBounds();
            $selection_bounds = $location->getSelectionBounds();

            $selection_start = $selection_bounds[0] - $snippet_bounds[0];
            $selection_length = $selection_bounds[1] - $selection_bounds[0];

            echo $location->file_name . ':' . $location->getLineNumber() . "\n" .
                (
                    $this->stdout_report_options->use_color
                    ? substr($snippet, 0, $selection_start) .
                    "\e[97;42m" . substr($snippet, $selection_start, $selection_length) .
                    "\e[0m" . substr($snippet, $selection_length + $selection_start)
                    : $snippet
                ) . "\n" . "\n";
        }
    }

    public function checkDir(string $dir_name): void
    {
        $this->file_reference_provider->loadReferenceCache();

        $this->config->visitPreloadedStubFiles($this->codebase, $this->progress);

        $this->checkDirWithConfig($dir_name, $this->config, true);

        $this->progress->write($this->generatePHPVersionMessage());
        $this->progress->startScanningFiles();

        $this->config->initializePlugins($this);

        $this->codebase->scanFiles($this->threads);

        $this->config->visitStubFiles($this->codebase, $this->progress);

        $this->progress->startAnalyzingFiles();

        $this->codebase->analyzer->analyzeFiles(
            $this,
            $this->threads,
            $this->codebase->alter_code,
            $this->codebase->find_unused_code === 'always',
        );
    }

    private function checkDirWithConfig(string $dir_name, Config $config, bool $allow_non_project_files = false): void
    {
        $file_extensions = $config->getFileExtensions();
        $filter = $allow_non_project_files ? null : [$this->config, 'isInProjectDirs'];

        $file_paths = $this->file_provider->getFilesInDir(
            $dir_name,
            $file_extensions,
            $filter,
        );

        $files_to_scan = [];

        foreach ($file_paths as $file_path) {
            $files_to_scan[$file_path] = $file_path;
        }

        $this->codebase->addFilesToAnalyze($files_to_scan);
    }

    public function addProjectFile(string $file_path): void
    {
        $this->project_files[$file_path] = $file_path;
    }

    public function addExtraFile(string $file_path): void
    {
        $this->extra_files[$file_path] = $file_path;
    }

    /**
     * @return list<string>
     */
    protected function getDiffFiles(): array
    {
        if (!$this->parser_cache_provider || !$this->project_cache_provider) {
            throw new UnexpectedValueException('Parser cache provider cannot be null here');
        }

        $diff_files = [];

        $last_run = $this->project_cache_provider->getLastRun(PSALM_VERSION);

        foreach ($this->project_files as $file_path) {
            if ($this->file_provider->getModifiedTime($file_path) >= $last_run
                && $this->parser_cache_provider->loadExistingFileContentsFromCache($file_path)
                    !== $this->file_provider->getContents($file_path)
            ) {
                $diff_files[] = $file_path;
            }
        }

        return $diff_files;
    }

    /**
     * @param  array<string>    $file_list
     */
    private function checkDiffFilesWithConfig(Config $config, array $file_list = []): void
    {
        $files_to_scan = [];

        foreach ($file_list as $file_path) {
            if (!$this->file_provider->fileExists($file_path)) {
                continue;
            }

            if (!$config->isInProjectDirs($file_path)) {
                $this->progress->debug('skipping ' . $file_path . "\n");

                continue;
            }

            $files_to_scan[$file_path] = $file_path;
        }

        $this->codebase->addFilesToAnalyze($files_to_scan);
    }

    public function checkFile(string $file_path): void
    {
        $this->progress->debug('Checking ' . $file_path . "\n");

        $this->config->visitPreloadedStubFiles($this->codebase, $this->progress);

        $this->config->hide_external_errors = $this->config->isInProjectDirs($file_path);

        $this->codebase->addFilesToAnalyze([$file_path => $file_path]);

        $this->file_reference_provider->loadReferenceCache();

        $this->progress->write($this->generatePHPVersionMessage());
        $this->progress->startScanningFiles();

        $this->config->initializePlugins($this);

        $this->codebase->scanFiles($this->threads);

        $this->config->visitStubFiles($this->codebase, $this->progress);

        $this->progress->startAnalyzingFiles();

        $this->codebase->analyzer->analyzeFiles(
            $this,
            $this->threads,
            $this->codebase->alter_code,
            $this->codebase->find_unused_code === 'always',
        );
    }

    /**
     * @param string[] $paths_to_check
     */
    public function checkPaths(array $paths_to_check): void
    {
        $this->config->visitPreloadedStubFiles($this->codebase, $this->progress);
        $this->visitAutoloadFiles();

        $this->codebase->scanner->addFilesToShallowScan($this->extra_files);

        foreach ($paths_to_check as $path) {
            $this->progress->debug('Checking ' . $path . "\n");

            if (is_dir($path)) {
                $this->checkDirWithConfig($path, $this->config, true);
            } elseif (is_file($path)) {
                $this->check_paths_files[] = $path;
                $this->codebase->addFilesToAnalyze([$path => $path]);
                $this->config->hide_external_errors = $this->config->isInProjectDirs($path);
            }
        }

        $this->file_reference_provider->loadReferenceCache();

        $this->progress->write($this->generatePHPVersionMessage());
        $this->progress->startScanningFiles();

        $this->config->initializePlugins($this);


        $this->codebase->scanFiles($this->threads);

        $this->config->visitStubFiles($this->codebase, $this->progress);

        $event = new AfterCodebasePopulatedEvent($this->codebase);

        $this->config->eventDispatcher->dispatchAfterCodebasePopulated($event);

        $this->progress->startAnalyzingFiles();

        $this->codebase->analyzer->analyzeFiles(
            $this,
            $this->threads,
            $this->codebase->alter_code,
            $this->codebase->find_unused_code === 'always',
        );

        if ($this->stdout_report_options
            && in_array(
                $this->stdout_report_options->format,
                [Report::TYPE_CONSOLE, Report::TYPE_PHP_STORM],
            )
            && $this->codebase->collect_references
        ) {
            fwrite(
                STDERR,
                PHP_EOL . 'To whom it may concern: Psalm cannot detect unused classes, methods and properties'
                . PHP_EOL . 'when analyzing individual files and folders. Run on the full project to enable'
                . PHP_EOL . 'complete unused code detection.' . PHP_EOL,
            );
        }
    }

    public function finish(float $start_time, string $psalm_version): void
    {
        $this->codebase->file_reference_provider->removeDeletedFilesFromReferences();

        if ($this->project_cache_provider) {
            $this->project_cache_provider->processSuccessfulRun($start_time, $psalm_version);
        }
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * @param  array<string>  $diff_files
     * @return array<string, string>
     */
    public function getReferencedFilesFromDiff(array $diff_files, bool $include_referencing_files = true): array
    {
        $all_inherited_files_to_check = $diff_files;

        while ($diff_files) {
            $diff_file = array_shift($diff_files);

            $dependent_files = $this->file_reference_provider->getFilesInheritingFromFile($diff_file);

            $new_dependent_files = array_diff($dependent_files, $all_inherited_files_to_check);

            $all_inherited_files_to_check = array_merge($all_inherited_files_to_check, $new_dependent_files);
            $diff_files = array_merge($diff_files, $new_dependent_files);
        }

        $all_files_to_check = $all_inherited_files_to_check;

        if ($include_referencing_files) {
            foreach ($all_inherited_files_to_check as $file_name) {
                $dependent_files = $this->file_reference_provider->getFilesReferencingFile($file_name);
                $all_files_to_check = array_merge($dependent_files, $all_files_to_check);
            }
        }

        return array_combine($all_files_to_check, $all_files_to_check);
    }

    public function fileExists(string $file_path): bool
    {
        return $this->file_provider->fileExists($file_path);
    }

    public function isDirectory(string $file_path): bool
    {
        return $this->file_provider->isDirectory($file_path);
    }

    public function alterCodeAfterCompletion(
        bool $dry_run = false,
        bool $safe_types = false
    ): void {
        $this->codebase->alter_code = true;
        $this->codebase->infer_types_from_usage = true;
        $this->show_issues = false;
        $this->dry_run = $dry_run;
        $this->only_replace_php_types_with_non_docblock_types = $safe_types;
    }

    /**
     * @param array<string, string> $to_refactor
     */
    public function refactorCodeAfterCompletion(array $to_refactor): void
    {
        $this->to_refactor = $to_refactor;
        $this->codebase->alter_code = true;
        $this->show_issues = false;
    }

    /**
     * @param 'cli'|'config'|'composer'|'tests' $source
     */
    public function setPhpVersion(string $version, string $source): void
    {
        if (!preg_match('/' . self::PHP_VERSION_REGEX . '/', $version)) {
            throw new UnexpectedValueException('Expecting a version number in the format x.y or x.y.z');
        }

        if (!preg_match('/' . self::PHP_SUPPORTED_VERSIONS_REGEX . '/', $version)) {
            throw new UnexpectedValueException(
                'Psalm supports PHP version ">=5.4". The specified version '
                . $version
                . " is either not supported or doesn't exist.",
            );
        }

        [$php_major_version, $php_minor_version] = explode('.', $version);

        $php_major_version = (int) $php_major_version;
        $php_minor_version = (int) $php_minor_version;

        $analysis_php_version_id = $php_major_version * 10_000 + $php_minor_version * 100;

        if ($this->codebase->analysis_php_version_id !== $analysis_php_version_id) {
            // reset lexer and parser when php version changes
            StatementsProvider::clearLexer();
            StatementsProvider::clearParser();
        }

        $this->codebase->analysis_php_version_id = $analysis_php_version_id;
        $this->codebase->php_version_source = $source;
    }

    /**
     * @param array<string, bool> $issues
     * @throws UnsupportedIssueToFixException
     */
    public function setIssuesToFix(array $issues): void
    {
        $supported_issues_to_fix = static::getSupportedIssuesToFix();

        $supported_issues_to_fix[] = 'MissingImmutableAnnotation';
        $supported_issues_to_fix[] = 'MissingPureAnnotation';
        $supported_issues_to_fix[] = 'MissingThrowsDocblock';

        $unsupportedIssues = array_diff(array_keys($issues), $supported_issues_to_fix);

        if (! empty($unsupportedIssues)) {
            throw new UnsupportedIssueToFixException(
                'Psalm doesn\'t know how to fix issue(s): ' . implode(', ', $unsupportedIssues) . PHP_EOL
                . 'Supported issues to fix are: ' . implode(',', $supported_issues_to_fix),
            );
        }

        $this->issues_to_fix = $issues;
    }

    public function setAllIssuesToFix(): void
    {
        $keyed_issues = array_fill_keys(static::getSupportedIssuesToFix(), true);

        $this->setIssuesToFix($keyed_issues);
    }

    /**
     * @return array<string, bool>
     */
    public function getIssuesToFix(): array
    {
        return $this->issues_to_fix;
    }

    public function getCodebase(): Codebase
    {
        return $this->codebase;
    }

    public function getFileAnalyzerForClassLike(string $fq_class_name): FileAnalyzer
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        $file_path = $this->codebase->scanner->getClassLikeFilePath($fq_class_name_lc);

        return new FileAnalyzer(
            $this,
            $file_path,
            $this->config->shortenFileName($file_path),
        );
    }

    public function getMethodMutations(
        MethodIdentifier $original_method_id,
        Context $this_context,
        string $root_file_path,
        string $root_file_name
    ): void {
        $fq_class_name = $original_method_id->fq_class_name;

        $appearing_method_id = $this->codebase->methods->getAppearingMethodId($original_method_id);

        if (!$appearing_method_id) {
            // this can happen for some abstract classes implementing (but not fully) interfaces
            return;
        }

        $appearing_fq_class_name = $appearing_method_id->fq_class_name;

        $appearing_class_storage = $this->classlike_storage_provider->get($appearing_fq_class_name);

        if (!$appearing_class_storage->user_defined) {
            return;
        }

        $file_analyzer = $this->getFileAnalyzerForClassLike($fq_class_name);

        $file_analyzer->setRootFilePath($root_file_path, $root_file_name);

        if ($appearing_fq_class_name !== $fq_class_name) {
            $file_analyzer = $this->getFileAnalyzerForClassLike($appearing_fq_class_name);
        }

        $stmts = $this->codebase->getStatementsForFile(
            $file_analyzer->getFilePath(),
        );

        $file_analyzer->populateCheckers($stmts);

        if (!$this_context->self) {
            $this_context->self = $fq_class_name;
            $this_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
        }

        $file_analyzer->getMethodMutations($appearing_method_id, $this_context, true);

        $file_analyzer->class_analyzers_to_analyze = [];
        $file_analyzer->interface_analyzers_to_analyze = [];
        $file_analyzer->clearSourceBeforeDestruction();
    }

    public function getFunctionLikeAnalyzer(
        MethodIdentifier $method_id,
        string $file_path
    ): ?FunctionLikeAnalyzer {
        $file_analyzer = new FileAnalyzer(
            $this,
            $file_path,
            $this->config->shortenFileName($file_path),
        );

        $stmts = $this->codebase->getStatementsForFile(
            $file_analyzer->getFilePath(),
        );

        $file_analyzer->populateCheckers($stmts);

        $function_analyzer = $file_analyzer->getFunctionLikeAnalyzer($method_id);

        $file_analyzer->class_analyzers_to_analyze = [];
        $file_analyzer->interface_analyzers_to_analyze = [];

        return $function_analyzer;
    }

    /**
     * Adapted from https://gist.github.com/divinity76/01ef9ca99c111565a72d3a8a6e42f7fb
     * returns number of cpu cores
     * Copyleft 2018, license: WTFPL
     *
     * @throws NumberOfCpuCoreNotFound
     */
    public static function getCpuCount(): int
    {
        if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
            // No support desired for Windows at the moment
            return 1;
        }

        if (!extension_loaded('pcntl')) {
            // Psalm requires pcntl for multi-threads support
            return 1;
        }

        return (new CpuCoreCounter())->getCount();
    }

    /**
     * @return array<int, string>
     * @psalm-pure
     */
    public static function getSupportedIssuesToFix(): array
    {
        return array_map(
            /** @param class-string $issue_class */
            static function (string $issue_class): string {
                $parts = explode('\\', $issue_class);
                return end($parts);
            },
            self::SUPPORTED_ISSUES_TO_FIX,
        );
    }
}
