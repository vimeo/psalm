<?php
namespace Psalm\Codebase;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Provider\FileProvider;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Scanner\FileScanner;

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
        unset(
            $this->scanned_files[$file_path]
        );
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
    public function scanFiles(ClassLikes $classlikes)
    {
        $has_changes = false;

        while ($this->files_to_scan || $this->classes_to_scan) {
            if ($this->files_to_scan) {
                if ($this->scanFilePaths()) {
                    $has_changes = true;
                }
            } else {
                $this->convertClassesToFilePaths($classlikes);
            }
        }

        return $has_changes;
    }

    private function scanFilePaths() : bool
    {
        $filetype_scanners = $this->config->getFiletypeScanners();
        $files_to_scan = $this->files_to_scan;
        $has_changes = false;
        $this->files_to_scan = [];

        foreach ($files_to_scan as $file_path) {
            if (!isset($this->scanned_files[$file_path])
                || (isset($this->files_to_deep_scan[$file_path]) && !$this->scanned_files[$file_path])
            ) {
                $this->scanFile(
                    $file_path,
                    $filetype_scanners,
                    isset($this->files_to_deep_scan[$file_path])
                );
                $has_changes = true;
            }
        }

        return $has_changes;
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
}
