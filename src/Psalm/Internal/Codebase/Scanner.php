<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\IssueData;
use Psalm\Internal\ErrorHandler;
use Psalm\Internal\Fork\InitScannerTask;
use Psalm\Internal\Fork\Pool;
use Psalm\Internal\Fork\ScannerTask;
use Psalm\Internal\Fork\ShutdownScannerTask;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\FileReferenceProvider;
use Psalm\Internal\Provider\FileStorageProvider;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Interner;
use Psalm\IssueBuffer;
use Psalm\Progress\Progress;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Storage\FunctionStorage;
use Psalm\StrId;
use Psalm\Type;
use Psalm\Type\Union;
use ReflectionClass;
use Throwable;
use UnexpectedValueException;

use function Amp\Future\await;
use function array_filter;
use function array_merge;
use function array_pop;
use function array_replace;
use function ceil;
use function count;
use function error_reporting;
use function explode;
use function file_exists;
use function min;
use function realpath;
use function str_ends_with;
use function strcasecmp;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * @psalm-type  ThreadData = array{
 *     array<string, string>,
 *     array<string, string>,
 *     array<int, int>,
 *     array<int, bool>,
 *     array<int, bool>,
 *     array<int, string>,
 *     array<int, bool>,
 *     array<string, bool>,
 *     array<int, bool>
 * }
 *
 * @psalm-type  PoolData = array{
 *     classlikes_data:array{
 *         array<int, bool>,
 *         array<int, bool>,
 *         array<int, bool>,
 *         array<int, bool>,
 *         array<int, bool>
 *     },
 *     scanner_data: ThreadData,
 *     issues:array<string, list<IssueData>>,
 *     changed_members:array<string, array<string, bool>>,
 *     unchanged_signature_members:array<string, array<string, bool>>,
 *     diff_map:array<string, array<int, array{int, int, int, int}>>,
 *     deletion_ranges:array<string, array<int, array{int, int}>>,
 *     errors:array<string, bool>,
 *     classlike_storage:array<int, ClassLikeStorage>,
 *     file_storage:array<lowercase-string, FileStorage>,
 *     taint_data: ?TaintFlowGraph,
 *     global_constants: array<int, Union>,
 *     global_functions: array<int, FunctionStorage>
 * }
 */

/**
 * @internal
 *
 * Contains methods that aid in the scanning of Psalm's codebase
 */
final class Scanner
{
    /**
     * @var array<int, string> class name id => file path
     */
    private array $classlike_files = [];

    /**
     * @var array<int, bool> class name id => true
     */
    private array $deep_scanned_classlike_files = [];

    /**
     * @var array<string, string>
     */
    private array $files_to_scan = [];

    /**
     * @var array<int, int> class name id => class name id
     */
    private array $classes_to_scan = [];

    /**
     * @var array<int, bool> class name id => true
     */
    private array $classes_to_deep_scan = [];

    /**
     * @var array<string, string>
     */
    private array $files_to_deep_scan = [];

    /**
     * @var array<string, bool>
     */
    private array $scanned_files = [];

    /**
     * @var array<int, bool> class name id => store failure
     */
    private array $store_scan_failure = [];

    /**
     * @var array<int, bool> class name id => true
     */
    private array $reflected_classlikes = [];

    private bool $is_forked = false;

    /**
     * @psalm-mutation-free
     */
    public function __construct(
        private readonly Codebase $codebase,
        private readonly Config $config,
        private readonly FileStorageProvider $file_storage_provider,
        private readonly FileProvider $file_provider,
        private readonly Reflection $reflection,
        private readonly FileReferenceProvider $file_reference_provider,
        private readonly Progress $progress,
    ) {
    }

    /**
     * @param array<string, string> $files_to_scan
     * @psalm-external-mutation-free
     */
    public function addFilesToShallowScan(array $files_to_scan): void
    {
        $this->files_to_scan += $files_to_scan;
    }

    /**
     * @param array<string, string> $files_to_scan
     * @psalm-external-mutation-free
     */
    public function addFilesToDeepScan(array $files_to_scan): void
    {
        $this->files_to_scan += $files_to_scan;
        $this->files_to_deep_scan += $files_to_scan;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addFileToShallowScan(string $file_path): void
    {
        $this->files_to_scan[$file_path] = $file_path;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function addFileToDeepScan(string $file_path): void
    {
        $this->files_to_scan[$file_path] = $file_path;
        $this->files_to_deep_scan[$file_path] = $file_path;
    }

    /**
     * @psalm-external-mutation-free
     */
    public function removeFile(string $file_path): void
    {
        unset($this->scanned_files[$file_path]);
    }

    /**
     * @param int $fq_classlike_name class name id
     * @psalm-external-mutation-free
     */
    public function removeClassLike(int $fq_classlike_name): void
    {
        unset(
            $this->classlike_files[$fq_classlike_name],
            $this->deep_scanned_classlike_files[$fq_classlike_name],
        );
    }

    /**
     * @param int $fq_classlike_name class name id
     * @psalm-external-mutation-free
     */
    public function setClassLikeFilePath(int $fq_classlike_name, string $file_path): void
    {
        $this->classlike_files[$fq_classlike_name] = $file_path;
    }

    /**
     * @param int $fq_classlike_name class name id
     * @psalm-mutation-free
     */
    public function getClassLikeFilePath(int $fq_classlike_name): string
    {
        if (!isset($this->classlike_files[$fq_classlike_name])) {
            throw new UnexpectedValueException('Could not find file for ' . Interner::str($fq_classlike_name));
        }

        return $this->classlike_files[$fq_classlike_name];
    }

    /**
     * @param  array<int, mixed> $phantom_classes class name id => mixed
     */
    public function queueClassLikeForScanning(
        int $fq_classlike_name,
        bool $analyze_too = false,
        bool $store_failure = true,
        array $phantom_classes = [],
    ): void {
        if ($fq_classlike_name === StrId::static) {
            return;
        }

        // avoid checking classes that we know will just end in failure
        if ($fq_classlike_name === StrId::null
            || str_ends_with(Interner::str($fq_classlike_name), '\null')
        ) {
            return;
        }

        if (!isset($this->classlike_files[$fq_classlike_name])
            || ($analyze_too && !isset($this->deep_scanned_classlike_files[$fq_classlike_name]))
        ) {
            if (!isset($this->classes_to_scan[$fq_classlike_name]) || $store_failure) {
                $this->classes_to_scan[$fq_classlike_name] = $fq_classlike_name;
            }

            if ($analyze_too) {
                $this->classes_to_deep_scan[$fq_classlike_name] = true;
            }

            $this->store_scan_failure[$fq_classlike_name] = $store_failure;

            $public_mapped_properties = PropertyMap::getPropertiesForClass($fq_classlike_name);

            if ($public_mapped_properties !== null) {
                foreach ($public_mapped_properties as $public_mapped_property) {
                    $property_type = Type::parseString($public_mapped_property);
                    /** @psalm-suppress UnusedMethodCall */
                    $property_type->queueClassLikesForScanning(
                        $this->codebase,
                        null,
                        $phantom_classes + [$fq_classlike_name => true],
                    );
                }
            }
        }
    }

    public function scanFiles(ClassLikes $classlikes, int $pool_size = 1): bool
    {
        $has_changes = false;
        while ($this->files_to_scan || $this->classes_to_scan) {
            if ($this->files_to_scan) {
                if ($this->scanFilePaths($pool_size)) {
                    $has_changes = true;
                }
            } else {
                $this->convertClassesToFilePaths($classlikes);
            }
        }

        return $has_changes;
    }

    private function shouldScan(string $file_path): bool
    {
        return $this->file_provider->fileExists($file_path)
            && !$this->file_provider->isDirectory($file_path)
            && (!isset($this->scanned_files[$file_path])
                || (isset($this->files_to_deep_scan[$file_path]) && !$this->scanned_files[$file_path]));
    }

    private function scanFilePaths(int $pool_size): bool
    {
        $files_to_scan = array_filter(
            $this->files_to_scan,
            $this->shouldScan(...),
        );

        $this->files_to_scan = [];

        if (!$files_to_scan) {
            return false;
        }

        if (!$this->is_forked && $pool_size > 1 && count($files_to_scan) > 512) {
            $pool_size = (int) ceil(min($pool_size, count($files_to_scan) / 256));
        } else {
            $pool_size = 1;
        }

        $this->progress->expand(count($files_to_scan));
        if ($pool_size > 1) {
            $this->progress->debug('Forking process for scanning' . PHP_EOL);

            // Run scanning one file at a time, splitting the set of
            // files up among a given number of child processes.
            $pool = new Pool(
                $pool_size,
                $this->config->long_scan_warning,
                $this->progress,
            );

            await($pool->runAll(new InitScannerTask));
            $pool->run(
                $files_to_scan,
                ScannerTask::class,
                function (): void {
                    $this->progress->taskDone(0);
                },
                // Register custom taints a worker discovers in this (parent) process's single registry, so
                // every worker resolves a given taint name to the same bit.
                fn(string $taint_type): array => $this->codebase->registerTaintFromWorker($taint_type),
            );

            // Wait for all tasks to complete and collect the results.
            $forked_pool_data = $pool->runAll(new ShutdownScannerTask);

            foreach ($forked_pool_data as $pool_data) {
                $pool_data = $pool_data->await();

                IssueBuffer::addIssues($pool_data['issues']);

                $this->codebase->statements_provider->addChangedMembers(
                    $pool_data['changed_members'],
                );
                $this->codebase->statements_provider->addUnchangedSignatureMembers(
                    $pool_data['unchanged_signature_members'],
                );
                $this->codebase->statements_provider->addDiffMap(
                    $pool_data['diff_map'],
                );
                $this->codebase->statements_provider->addDeletionRanges(
                    $pool_data['deletion_ranges'],
                );
                $this->codebase->statements_provider->addErrors($pool_data['errors']);

                if ($this->codebase->taint_flow_graph && $pool_data['taint_data']) {
                    $this->codebase->taint_flow_graph->addGraph($pool_data['taint_data']);
                }

                $this->codebase->file_storage_provider->addMore($pool_data['file_storage']);
                $this->codebase->classlike_storage_provider->addMore($pool_data['classlike_storage']);

                $this->codebase->classlikes->addThreadData($pool_data['classlikes_data']);

                $this->addThreadData($pool_data['scanner_data']);

                $this->codebase->addGlobalConstantTypes($pool_data['global_constants']);
                $this->codebase->functions->addGlobalFunctions($pool_data['global_functions']);
            }
        } else {
            foreach ($files_to_scan as $file_path => $_) {
                $this->scanAPath($file_path);
                $this->progress->taskDone(0);
            }
        }

        $this->file_reference_provider->addClassLikeFiles($this->classlike_files);

        return true;
    }

    private function convertClassesToFilePaths(ClassLikes $classlikes): void
    {
        $classes_to_scan = $this->classes_to_scan;

        $this->classes_to_scan = [];

        foreach ($classes_to_scan as $fq_classlike_name) {
            if (isset($this->reflected_classlikes[$fq_classlike_name])) {
                continue;
            }

            if ($classlikes->isMissingClassLike($fq_classlike_name)) {
                continue;
            }

            if (!isset($this->classlike_files[$fq_classlike_name])) {
                if ($classlikes->doesClassLikeExist($fq_classlike_name)) {
                    if ($fq_classlike_name === StrId::self) {
                        continue;
                    }

                    $this->progress->debug(
                        'Using reflection to get metadata for ' . Interner::str($fq_classlike_name) . "\n",
                    );

                    /** @psalm-suppress ArgumentTypeCoercion */
                    $reflected_class = new ReflectionClass(Interner::str($fq_classlike_name));
                    $this->reflection->registerClass($reflected_class);
                    $this->reflected_classlikes[$fq_classlike_name] = true;
                } elseif ($this->fileExistsForClassLike($classlikes, $fq_classlike_name)) {
                    $fq_classlike_name = $classlikes->getUnAliasedName(
                        $fq_classlike_name,
                    );

                    // even though we've checked this above, calling the method invalidates it
                    if (isset($this->classlike_files[$fq_classlike_name])) {
                        $file_path = $this->classlike_files[$fq_classlike_name];
                        $this->files_to_scan[$file_path] = $file_path;
                        if (isset($this->classes_to_deep_scan[$fq_classlike_name])) {
                            unset($this->classes_to_deep_scan[$fq_classlike_name]);
                            $this->files_to_deep_scan[$file_path] = $file_path;
                        }
                    }
                } elseif ($this->store_scan_failure[$fq_classlike_name]) {
                    $classlikes->registerMissingClassLike($fq_classlike_name);
                }
            } elseif (isset($this->classes_to_deep_scan[$fq_classlike_name])
                && !isset($this->deep_scanned_classlike_files[$fq_classlike_name])
            ) {
                $file_path = $this->classlike_files[$fq_classlike_name];
                $this->files_to_scan[$file_path] = $file_path;
                unset($this->classes_to_deep_scan[$fq_classlike_name]);
                $this->files_to_deep_scan[$file_path] = $file_path;
                $this->deep_scanned_classlike_files[$fq_classlike_name] = true;
            }
        }
    }

    /**
     * @param  array<string, class-string<FileScanner>>  $filetype_scanners
     */
    private function scanFile(
        string $file_path,
        array $filetype_scanners,
        bool $will_analyze = false,
    ): void {
        $file_scanner = $this->getScannerForPath($file_path, $filetype_scanners, $will_analyze);

        if (isset($this->scanned_files[$file_path])
            && (!$will_analyze || $this->scanned_files[$file_path])
        ) {
            throw new UnexpectedValueException('Should not be rescanning ' . $file_path);
        }

        if (!$this->file_provider->fileExists($file_path) && $this->config->mustBeIgnored($file_path)) {
            // this should not happen, but might if the file was temporary
            return;
        }

        $file_contents = $this->file_provider->getContents($file_path);

        $from_cache = $this->file_storage_provider->has($file_path, $file_contents);

        if (!$from_cache) {
            $this->file_storage_provider->create($file_path);
        }

        $this->scanned_files[$file_path] = $will_analyze;

        $file_storage = $this->file_storage_provider->get($file_path);

        $file_scanner->scan(
            $this->codebase,
            $file_storage,
            $from_cache,
            $this->progress,
        );

        if (!$from_cache) {
            if (!$file_storage->has_visitor_issues && $this->file_storage_provider->cache) {
                $this->file_storage_provider->cache->writeToCache($file_storage, $file_contents);
            }
        } else {
            $this->codebase->statements_provider->setUnchangedFile($file_path);

            foreach ($file_storage->required_file_paths as $required_file_path) {
                if ($will_analyze) {
                    $this->addFileToDeepScan($required_file_path);
                } else {
                    $this->addFileToShallowScan($required_file_path);
                }
            }

            foreach ($file_storage->classlikes_in_file as $fq_classlike_name) {
                $this->codebase->exhumeClassLikeStorage($fq_classlike_name, $file_path);
            }

            foreach ($file_storage->required_classes as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, $will_analyze, false);
            }

            foreach ($file_storage->required_interfaces as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, false, false);
            }

            foreach ($file_storage->referenced_classlikes as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, false, false);
            }

            if ($this->codebase->register_autoload_files
                || $this->codebase->all_functions_global
            ) {
                foreach ($file_storage->functions as $function_storage) {
                    if ($function_storage->cased_name
                        && !$this->codebase->functions->hasStubbedFunction($function_storage->cased_name)
                    ) {
                        $this->codebase->functions->addGlobalFunction(
                            $function_storage->cased_name,
                            $function_storage,
                        );
                    }
                }
            }
            if ($this->codebase->register_autoload_files
                || $this->codebase->all_constants_global
            ) {
                foreach ($file_storage->constants as $name => $type) {
                    $this->codebase->addGlobalConstantType($name, $type);
                }
            }

            foreach ($file_storage->classlike_aliases as $aliased_name => $unaliased_name) {
                $this->codebase->classlikes->addClassAlias($unaliased_name, $aliased_name);
            }
        }
    }

    /**
     * @param  array<string, class-string<FileScanner>>  $filetype_scanners
     */
    private function getScannerForPath(
        string $file_path,
        array $filetype_scanners,
        bool $will_analyze = false,
    ): FileScanner {
        $path_parts = explode(DIRECTORY_SEPARATOR, $file_path);
        $file_name_parts = explode('.', array_pop($path_parts));
        $extension = count($file_name_parts) > 1 ? array_pop($file_name_parts) : '';

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_scanners[$extension])) {
            return new $filetype_scanners[$extension]($file_path, $file_name, $will_analyze);
        }

        return new FileScanner($file_path, $file_name, $will_analyze);
    }

    /**
     * @return array<string, bool>
     */
    public function getScannedFiles(): array
    {
        return $this->scanned_files;
    }

    /**
     * Checks whether a class exists, and if it does then records what file it's in
     * for later checking
     */
    private function fileExistsForClassLike(ClassLikes $classlikes, int $fq_class_name): bool
    {
        if (isset($this->classlike_files[$fq_class_name])) {
            return true;
        }

        if ($fq_class_name === StrId::self) {
            return false;
        }

        $fq_class_name_string = Interner::str($fq_class_name);

        $composer_file_path = $this->config->getComposerFilePathForClassLike($fq_class_name);

        if ($composer_file_path && file_exists($composer_file_path)) {
            $this->progress->debug('Using composer to locate file for ' . $fq_class_name_string . "\n");

            $classlikes->addFullyQualifiedClassLikeName(
                $fq_class_name,
                (string) realpath($composer_file_path),
            );

            return true;
        }

        foreach ($this->config->eventDispatcher->file_path_provider_interface as $provider) {
            $file_path = $provider::getClassFilePath($fq_class_name);

            if ($file_path !== null && file_exists($file_path)) {
                $this->progress->debug(
                    'Using custom file path provider to locate file for ' . $fq_class_name_string . "\n",
                );

                $classlikes->addFullyQualifiedClassLikeName(
                    $fq_class_name,
                    (string) realpath($file_path),
                );

                return true;
            }
        }

        $reflected_class = ErrorHandler::runWithExceptionsSuppressed(
            function () use ($fq_class_name_string): ?ReflectionClass {
                $old_level = error_reporting();
                $this->progress->setErrorReporting();

                try {
                    $this->progress->debug('Using reflection to locate file for ' . $fq_class_name_string . "\n");

                    /** @psalm-suppress ArgumentTypeCoercion */
                    return new ReflectionClass($fq_class_name_string);
                } catch (Throwable) {
                    // do not cache any results here (as case-sensitive filenames can screw things up)

                    return null;
                } finally {
                    error_reporting($old_level);
                }
            },
        );

        if (null === $reflected_class) {
            return false;
        }

        $file_path = (string)$reflected_class->getFileName();

        // if the file was autoloaded but exists in evaled code only, return false
        if (!file_exists($file_path)) {
            return false;
        }

        $new_fq_class_name_string = $reflected_class->getName();

        if ($new_fq_class_name_string !== $fq_class_name_string) {
            // names are case-sensitive: a wrongly-cased reference must not resolve (reflection is case-insensitive)
            if (strcasecmp($new_fq_class_name_string, $fq_class_name_string) === 0) {
                return false;
            }

            $new_fq_class_name = Interner::intern($new_fq_class_name_string);
            $classlikes->addClassAlias($new_fq_class_name, $fq_class_name);
            $fq_class_name = $new_fq_class_name;
        }

        $classlikes->addFullyQualifiedClassLikeName($fq_class_name);

        if ($reflected_class->isInterface()) {
            $classlikes->addFullyQualifiedInterfaceName($fq_class_name, $file_path);
        } elseif ($reflected_class->isTrait()) {
            $classlikes->addFullyQualifiedTraitName($fq_class_name, $file_path);
        } else {
            $classlikes->addFullyQualifiedClassName($fq_class_name, $file_path);
        }

        return true;
    }

    /**
     * @return ThreadData
     * @psalm-mutation-free
     */
    public function getThreadData(): array
    {
        return [
            $this->files_to_scan,
            $this->files_to_deep_scan,
            $this->classes_to_scan,
            $this->classes_to_deep_scan,
            $this->store_scan_failure,
            $this->classlike_files,
            $this->deep_scanned_classlike_files,
            $this->scanned_files,
            $this->reflected_classlikes,
        ];
    }

    /**
     * @param ThreadData $thread_data
     * @psalm-external-mutation-free
     */
    public function addThreadData(array $thread_data): void
    {
        [
            $files_to_scan,
            $files_to_deep_scan,
            $classes_to_scan,
            $classes_to_deep_scan,
            $store_scan_failure,
            $classlike_files,
            $deep_scanned_classlike_files,
            $scanned_files,
            $reflected_classlikes,
        ] = $thread_data;

        $this->files_to_scan = array_merge($files_to_scan, $this->files_to_scan);
        $this->files_to_deep_scan = array_merge($files_to_deep_scan, $this->files_to_deep_scan);
        $this->classes_to_scan = array_replace($classes_to_scan, $this->classes_to_scan);
        $this->classes_to_deep_scan = array_replace($classes_to_deep_scan, $this->classes_to_deep_scan);
        $this->store_scan_failure = array_replace($store_scan_failure, $this->store_scan_failure);
        $this->classlike_files = array_replace($classlike_files, $this->classlike_files);
        $this->deep_scanned_classlike_files = array_replace(
            $deep_scanned_classlike_files,
            $this->deep_scanned_classlike_files,
        );
        $this->scanned_files = array_merge($scanned_files, $this->scanned_files);
        $this->reflected_classlikes = array_replace($reflected_classlikes, $this->reflected_classlikes);
    }

    /**
     * @psalm-external-mutation-free
     */
    public function isForked(): void
    {
        $this->is_forked = true;
    }

    public function scanAPath(string $file_path): void
    {
        $this->scanFile(
            $file_path,
            $this->config->getFiletypeScanners(),
            isset($this->files_to_deep_scan[$file_path]),
        );
    }
}
