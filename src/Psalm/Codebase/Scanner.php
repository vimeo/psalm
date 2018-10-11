<?php
namespace Psalm\Codebase;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Scanner\FileScanner;

/**
 * @psalm-type  IssueData = array{
 *     severity: string,
 *     line_from: int,
 *     line_to: int,
 *     type: string,
 *     message: string,
 *     file_name: string,
 *     file_path: string,
 *     snippet: string,
 *     from: int,
 *     to: int,
 *     snippet_from: int,
 *     snippet_to: int,
 *     column_from: int,
 *     column_to: int
 * }
 *
 * @psalm-type  PoolData = array{
 *     classlikes_data:array{
 *         0:array<string, bool>,
 *         1:array<string, bool>,
 *         2:array<string, bool>,
 *         3:array<string, bool>,
 *         4:array<string, bool>,
 *         5:array<string, bool>,
 *         6:array<string, bool>,
 *         7:array<string, \PhpParser\Node\Stmt\Trait_>,
 *         8:array<string, \Psalm\Aliases>,
 *         9:array<string, int>
 *     },
 *     scanner_data:array{
 *         0:array<string, string>,
 *         1:array<string, string>,
 *         2:array<string, string>,
 *         3:array<string, bool>,
 *         4:array<string, bool>,
 *         5:array<string, string>,
 *         6:array<string, bool>,
 *         7:array<string, bool>,
 *         8:array<string, bool>
 *     },
 *     issues:array<int, IssueData>,
 *     changed_members:array<string, array<string, bool>>,
 *     unchanged_signature_members:array<string, array<string, bool>>,
 *     diff_map:array<string, array<int, array{0:int, 1:int, 2:int, 3:int}>>,
 *     classlike_storage:array<string, \Psalm\Storage\ClassLikeStorage>,
 *     file_storage:array<string, \Psalm\Storage\FileStorage>
 * }
 */

/**
 * @internal
 *
 * Contains methods that aid in the scanning of Psalm's codebase
 */
class Scanner
{
    /**
     * @var Codebase
     */
    private $codebase;

    /**
     * @var array<string, string>
     */
    private $classlike_files = [];

    /**
     * @var array<string, bool>
     */
    private $deep_scanned_classlike_files = [];

    /**
     * @var array<string, string>
     */
    private $files_to_scan = [];

    /**
     * @var array<string, string>
     */
    private $classes_to_scan = [];

    /**
     * @var array<string, bool>
     */
    private $classes_to_deep_scan = [];

    /**
     * @var array<string, string>
     */
    private $files_to_deep_scan = [];

    /**
     * @var array<string, bool>
     */
    private $scanned_files = [];

    /**
     * @var array<string, bool>
     */
    private $store_scan_failure = [];

    /**
     * @var array<string, bool>
     */
    private $reflected_classlikes_lc = [];

    /**
     * @var Reflection
     */
    private $reflection;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var bool
     */
    private $debug_output;

    /**
     * @var FileStorageProvider
     */
    private $file_storage_provider;

    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var FileReferenceProvider
     */
    private $file_reference_provider;

    /**
     * @var bool
     */
    private $is_forked = false;

    /**
     * @param bool $debug_output
     */
    public function __construct(
        Codebase $codebase,
        Config $config,
        FileStorageProvider $file_storage_provider,
        FileProvider $file_provider,
        Reflection $reflection,
        FileReferenceProvider $file_reference_provider,
        $debug_output
    ) {
        $this->codebase = $codebase;
        $this->reflection = $reflection;
        $this->file_provider = $file_provider;
        $this->debug_output = $debug_output;
        $this->file_storage_provider = $file_storage_provider;
        $this->config = $config;
        $this->file_reference_provider = $file_reference_provider;
    }

    /**
     * @param array<string, string> $files_to_scan
     *
     * @return void
     */
    public function addFilesToShallowScan(array $files_to_scan)
    {
        $this->files_to_scan += $files_to_scan;
    }

    /**
     * @param array<string, string> $files_to_scan
     *
     * @return void
     */
    public function addFilesToDeepScan(array $files_to_scan)
    {
        $this->files_to_scan += $files_to_scan;
        $this->files_to_deep_scan += $files_to_scan;
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function addFileToShallowScan($file_path)
    {
        $this->files_to_scan[$file_path] = $file_path;
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function addFileToDeepScan($file_path)
    {
        $this->files_to_scan[$file_path] = $file_path;
        $this->files_to_deep_scan[$file_path] = $file_path;
    }

    /**
     * @param string $file_path
     *
     * @return void
     */
    public function removeFile($file_path)
    {
        unset($this->scanned_files[$file_path]);
    }

    /**
     * @param  string $fq_classlike_name_lc
     * @return void
     */
    public function removeClassLike($fq_classlike_name_lc)
    {
        unset($this->classlike_files[$fq_classlike_name_lc]);
        unset($this->deep_scanned_classlike_files[$fq_classlike_name_lc]);
    }

    /**
     * @param  string $fq_classlike_name_lc
     * @param  string $file_path
     *
     * @return void
     */
    public function setClassLikeFilePath($fq_classlike_name_lc, $file_path)
    {
        $this->classlike_files[$fq_classlike_name_lc] = $file_path;
    }

    /**
     * @param  string $fq_classlike_name_lc
     *
     * @return string
     */
    public function getClassLikeFilePath($fq_classlike_name_lc)
    {
        if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
            throw new \UnexpectedValueException('Could not find file for ' . $fq_classlike_name_lc);
        }

        return $this->classlike_files[$fq_classlike_name_lc];
    }

    /**
     * @param  string  $fq_classlike_name
     * @param  string|null  $referencing_file_path
     * @param  bool $analyze_too
     * @param  bool $store_failure
     *
     * @return void
     */
    public function queueClassLikeForScanning(
        $fq_classlike_name,
        $referencing_file_path = null,
        $analyze_too = false,
        $store_failure = true
    ) {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        // avoid checking classes that we know will just end in failure
        if ($fq_classlike_name_lc === 'null' || substr($fq_classlike_name_lc, -5) === '\null') {
            return;
        }

        if (!isset($this->classlike_files[$fq_classlike_name_lc])
            || ($analyze_too && !isset($this->deep_scanned_classlike_files[$fq_classlike_name_lc]))
        ) {
            if (!isset($this->classes_to_scan[$fq_classlike_name_lc]) || $store_failure) {
                $this->classes_to_scan[$fq_classlike_name_lc] = $fq_classlike_name;
            }

            if ($analyze_too) {
                $this->classes_to_deep_scan[$fq_classlike_name_lc] = true;
            }

            $this->store_scan_failure[$fq_classlike_name] = $store_failure;

            if (PropertyMap::inPropertyMap($fq_classlike_name_lc)) {
                $public_mapped_properties = PropertyMap::getPropertyMap()[$fq_classlike_name_lc];

                foreach ($public_mapped_properties as $public_mapped_property) {
                    if (strtolower($public_mapped_property) !== $fq_classlike_name_lc) {
                        $property_type = \Psalm\Type::parseString($public_mapped_property);
                        $property_type->queueClassLikesForScanning($this->codebase);
                    }
                }
            }
        }

        if ($referencing_file_path) {
            $this->file_reference_provider->addFileReferenceToClass($referencing_file_path, $fq_classlike_name_lc);
        }
    }

    /**
     * @return bool
     */
    public function scanFiles(ClassLikes $classlikes, int $pool_size = 1)
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

    private function scanFilePaths(int $pool_size) : bool
    {
        $filetype_scanners = $this->config->getFiletypeScanners();
        $files_to_scan = array_filter(
            $this->files_to_scan,
            function (string $file_path) : bool {
                return !isset($this->scanned_files[$file_path])
                    || (isset($this->files_to_deep_scan[$file_path]) && !$this->scanned_files[$file_path]);
            }
        );

        $this->files_to_scan = [];

        $files_to_deep_scan = $this->files_to_deep_scan;

        $scanner_worker =
            /**
             * @param int $_
             * @param string $file_path
             *
             * @return void
             */
            function ($_, $file_path) use ($filetype_scanners, $files_to_deep_scan) {
                $this->scanFile(
                    $file_path,
                    $filetype_scanners,
                    isset($files_to_deep_scan[$file_path])
                );
            };

        if (!$this->is_forked && $pool_size > 1 && count($files_to_scan) > 512) {
            $pool_size = ceil(min($pool_size, count($files_to_scan) / 256));
        } else {
            $pool_size = 1;
        }

        if ($pool_size > 1) {
            $process_file_paths = [];

            $i = 0;

            foreach ($files_to_scan as $file_path) {
                $process_file_paths[$i % $pool_size][] = $file_path;
                ++$i;
            }

            // Run scanning one file at a time, splitting the set of
            // files up among a given number of child processes.
            $pool = new \Psalm\Fork\Pool(
                $process_file_paths,
                /** @return void */
                function () {
                    $project_checker = \Psalm\Checker\ProjectChecker::getInstance();
                    $statements_provider = $project_checker->codebase->statements_provider;

                    $project_checker->codebase->scanner->isForked();
                    $project_checker->codebase->file_storage_provider->deleteAll();
                    $project_checker->codebase->classlike_storage_provider->deleteAll();

                    $statements_provider->resetDiffs();
                },
                $scanner_worker,
                /**
                 * @return PoolData
                */
                function () {
                    $project_checker = \Psalm\Checker\ProjectChecker::getInstance();
                    $statements_provider = $project_checker->codebase->statements_provider;

                    return [
                        'classlikes_data' => $project_checker->codebase->classlikes->getThreadData(),
                        'scanner_data' => $project_checker->codebase->scanner->getThreadData(),
                        'issues' => \Psalm\IssueBuffer::getIssuesData(),
                        'changed_members' => $statements_provider->getChangedMembers(),
                        'unchanged_signature_members' => $statements_provider->getUnchangedSignatureMembers(),
                        'diff_map' => $statements_provider->getDiffMap(),
                        'classlike_storage' => $project_checker->classlike_storage_provider->getAll(),
                        'file_storage' => $project_checker->file_storage_provider->getAll(),
                    ];
                }
            );

            // Wait for all tasks to complete and collect the results.
            /**
             * @var array<int, PoolData>
             */
            $forked_pool_data = $pool->wait();

            foreach ($forked_pool_data as $pool_data) {
                \Psalm\IssueBuffer::addIssues($pool_data['issues']);

                $this->codebase->statements_provider->addChangedMembers(
                    $pool_data['changed_members']
                );
                $this->codebase->statements_provider->addUnchangedSignatureMembers(
                    $pool_data['unchanged_signature_members']
                );
                $this->codebase->statements_provider->addDiffMap(
                    $pool_data['diff_map']
                );

                $this->codebase->file_storage_provider->addMore($pool_data['file_storage']);
                $this->codebase->classlike_storage_provider->addMore($pool_data['classlike_storage']);

                $this->codebase->classlikes->addThreadData($pool_data['classlikes_data']);

                $this->addThreadData($pool_data['scanner_data']);
            }
        } else {
            $i = 0;

            foreach ($files_to_scan as $file_path => $_) {
                $scanner_worker($i, $file_path);
                ++$i;
            }
        }

        return (bool) $files_to_scan;
    }

    /**
     * @return void
     */
    private function convertClassesToFilePaths(ClassLikes $classlikes)
    {
        $classes_to_scan = $this->classes_to_scan;

        $this->classes_to_scan = [];

        foreach ($classes_to_scan as $fq_classlike_name) {
            $fq_classlike_name_lc = strtolower($fq_classlike_name);

            if (isset($this->reflected_classlikes_lc[$fq_classlike_name_lc])) {
                continue;
            }

            if ($classlikes->isMissingClassLike($fq_classlike_name_lc)) {
                continue;
            }

            if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
                if ($classlikes->doesClassLikeExist($fq_classlike_name_lc)) {
                    if ($this->debug_output) {
                        echo 'Using reflection to get metadata for ' . $fq_classlike_name . "\n";
                    }

                    $reflected_class = new \ReflectionClass($fq_classlike_name);
                    $this->reflection->registerClass($reflected_class);
                    $this->reflected_classlikes_lc[$fq_classlike_name_lc] = true;
                } elseif ($this->fileExistsForClassLike($classlikes, $fq_classlike_name)) {
                    // even though we've checked this above, calling the method invalidates it
                    if (isset($this->classlike_files[$fq_classlike_name_lc])) {
                        /** @var string */
                        $file_path = $this->classlike_files[$fq_classlike_name_lc];
                        $this->files_to_scan[$file_path] = $file_path;
                        if (isset($this->classes_to_deep_scan[$fq_classlike_name_lc])) {
                            unset($this->classes_to_deep_scan[$fq_classlike_name_lc]);
                            $this->files_to_deep_scan[$file_path] = $file_path;
                        }
                    }
                } elseif ($this->store_scan_failure[$fq_classlike_name]) {
                    $classlikes->registerMissingClassLike($fq_classlike_name_lc);
                }
            } elseif (isset($this->classes_to_deep_scan[$fq_classlike_name_lc])
                && !isset($this->deep_scanned_classlike_files[$fq_classlike_name_lc])
            ) {
                $file_path = $this->classlike_files[$fq_classlike_name_lc];
                $this->files_to_scan[$file_path] = $file_path;
                unset($this->classes_to_deep_scan[$fq_classlike_name_lc]);
                $this->files_to_deep_scan[$file_path] = $file_path;
                $this->deep_scanned_classlike_files[$fq_classlike_name_lc] = true;
            }
        }
    }

    /**
     * @param  string $file_path
     * @param  array<string, string>  $filetype_scanners
     * @param  bool   $will_analyze
     *
     * @return FileScanner
     *
     * @psalm-suppress MixedOffset
     */
    private function scanFile(
        $file_path,
        array $filetype_scanners,
        $will_analyze = false
    ) {
        $file_scanner = $this->getScannerForPath($file_path, $filetype_scanners, $will_analyze);

        if (isset($this->scanned_files[$file_path])
            && (!$will_analyze || $this->scanned_files[$file_path])
        ) {
            throw new \UnexpectedValueException('Should not be rescanning ' . $file_path);
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
            $this->debug_output
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
                $this->queueClassLikeForScanning($fq_classlike_name, $file_path, $will_analyze, false);
            }

            foreach ($file_storage->required_interfaces as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, $file_path, false, false);
            }

            foreach ($file_storage->referenced_classlikes as $fq_classlike_name) {
                $this->queueClassLikeForScanning($fq_classlike_name, $file_path, false, false);
            }

            if ($this->codebase->register_autoload_files) {
                foreach ($file_storage->functions as $function_storage) {
                    $this->codebase->functions->addGlobalFunction($function_storage->cased_name, $function_storage);
                }

                foreach ($file_storage->constants as $name => $type) {
                    $this->codebase->addGlobalConstantType($name, $type);
                }
            }
        }

        return $file_scanner;
    }

    /**
     * @param  string $file_path
     * @param  array<string, string>  $filetype_scanners
     * @param  bool   $will_analyze
     *
     * @return FileScanner
     */
    private function getScannerForPath(
        $file_path,
        array $filetype_scanners,
        $will_analyze = false
    ) {
        $path_parts = explode(DIRECTORY_SEPARATOR, $file_path);
        $file_name_parts = explode('.', array_pop($path_parts));
        $extension = count($file_name_parts) > 1 ? array_pop($file_name_parts) : null;

        $file_name = $this->config->shortenFileName($file_path);

        if (isset($filetype_scanners[$extension])) {
            /** @var FileScanner */
            return new $filetype_scanners[$extension]($file_path, $file_name, $will_analyze);
        }

        return new FileScanner($file_path, $file_name, $will_analyze);
    }

    /**
     * @return array<string, bool>
     */
    public function getScannedFiles()
    {
        return $this->scanned_files;
    }

    /**
     * Checks whether a class exists, and if it does then records what file it's in
     * for later checking
     *
     * @param  string $fq_class_name
     *
     * @return bool
     */
    private function fileExistsForClassLike(ClassLikes $classlikes, $fq_class_name)
    {
        $fq_class_name_lc = strtolower($fq_class_name);

        if (isset($this->classlike_files[$fq_class_name_lc])) {
            return true;
        }

        if (isset($this->existing_classlikes_lc[$fq_class_name_lc])) {
            throw new \InvalidArgumentException('Why are you asking about a builtin class?');
        }

        $composer_file_path = $this->config->getComposerFilePathForClassLike($fq_class_name);

        if ($composer_file_path && file_exists($composer_file_path)) {
            if ($this->debug_output) {
                echo 'Using composer to locate file for ' . $fq_class_name . "\n";
            }

            $classlikes->addFullyQualifiedClassLikeName(
                $fq_class_name_lc,
                realpath($composer_file_path)
            );

            return true;
        }

        $old_level = error_reporting();

        if (!$this->debug_output) {
            error_reporting(E_ERROR);
        }

        try {
            if ($this->debug_output) {
                echo 'Using reflection to locate file for ' . $fq_class_name . "\n";
            }

            $reflected_class = new \ReflectionClass($fq_class_name);
        } catch (\ReflectionException $e) {
            error_reporting($old_level);

            // do not cache any results here (as case-sensitive filenames can screw things up)

            return false;
        }

        error_reporting($old_level);

        /** @psalm-suppress MixedMethodCall due to Reflection class weirdness */
        $file_path = (string)$reflected_class->getFileName();

        // if the file was autoloaded but exists in evaled code only, return false
        if (!file_exists($file_path)) {
            return false;
        }

        $fq_class_name = $reflected_class->getName();
        $classlikes->addFullyQualifiedClassLikeName($fq_class_name_lc);

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
     * @return array{
     *     0: array<string, string>,
     *     1: array<string, string>,
     *     2: array<string, string>,
     *     3: array<string, bool>,
     *     4: array<string, bool>,
     *     5: array<string, string>,
     *     6: array<string, bool>,
     *     7: array<string, bool>,
     *     8: array<string, bool>
     * }
     */
    public function getThreadData()
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
            $this->reflected_classlikes_lc
        ];
    }

    /**
     * @param array{
     *     0: array<string, string>,
     *     1: array<string, string>,
     *     2: array<string, string>,
     *     3: array<string, bool>,
     *     4: array<string, bool>,
     *     5: array<string, string>,
     *     6: array<string, bool>,
     *     7: array<string, bool>,
     *     8: array<string, bool>
     * } $thread_data
     *
     * @return void
     */
    public function addThreadData(array $thread_data)
    {
        list(
            $files_to_scan,
            $files_to_deep_scan,
            $classes_to_scan,
            $classes_to_deep_scan,
            $store_scan_failure,
            $classlike_files,
            $deep_scanned_classlike_files,
            $scanned_files,
            $reflected_classlikes_lc
        ) = $thread_data;

        $this->files_to_scan = array_merge($files_to_scan, $this->files_to_scan);
        $this->files_to_deep_scan = array_merge($files_to_deep_scan, $this->files_to_deep_scan);
        $this->classes_to_scan = array_merge($classes_to_scan, $this->classes_to_scan);
        $this->classes_to_deep_scan = array_merge($classes_to_deep_scan, $this->classes_to_deep_scan);
        $this->store_scan_failure = array_merge($store_scan_failure, $this->store_scan_failure);
        $this->classlike_files = array_merge($classlike_files, $this->classlike_files);
        $this->deep_scanned_classlike_files = array_merge(
            $deep_scanned_classlike_files,
            $this->deep_scanned_classlike_files
        );
        $this->scanned_files = array_merge($scanned_files, $this->scanned_files);
        $this->reflected_classlikes_lc = array_merge($reflected_classlikes_lc, $this->reflected_classlikes_lc);
    }

    /**
     * @return void
     */
    public function isForked()
    {
        $this->is_forked = true;
    }
}
