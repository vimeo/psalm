<?php
namespace Psalm\Codebase;

use Psalm\Codebase;
use Psalm\Config;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Provider\FileStorageProvider;
use Psalm\Provider\StatementsProvider;
use Psalm\Scanner\FileScanner;

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
     * @var null|array<string, string>
     */
    private $composer_classmap;

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
     * @var StatementsProvider
     */
    private $statements_provider;

    /**
     * @param bool $debug_output
     */
    public function __construct(
        Codebase $codebase,
        Config $config,
        FileStorageProvider $file_storage_provider,
        StatementsProvider $statements_provider,
        Reflection $reflection,
        $debug_output
    ) {
        $this->codebase = $codebase;
        $this->reflection = $reflection;
        $this->debug_output = $debug_output;
        $this->file_storage_provider = $file_storage_provider;
        $this->statements_provider = $statements_provider;
        $this->config = $config;
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

        if (!isset($this->classlike_files[$fq_classlike_name_lc])) {
            if (!isset($this->classes_to_scan[$fq_classlike_name_lc]) || $store_failure) {
                $this->classes_to_scan[$fq_classlike_name_lc] = $fq_classlike_name;
            }

            if ($analyze_too) {
                $this->classes_to_deep_scan[$fq_classlike_name_lc] = true;
            }

            $this->store_scan_failure[$fq_classlike_name] = $store_failure;
        }

        if ($referencing_file_path) {
            FileReferenceProvider::addFileReferenceToClass($referencing_file_path, $fq_classlike_name_lc);
        }
    }

    /**
     * @return bool
     */
    public function scanFiles(ClassLikes $classlikes)
    {
        $filetype_scanners = $this->config->getFiletypeScanners();

        $has_changes = false;

        while ($this->files_to_scan || $this->classes_to_scan) {
            if ($this->files_to_scan) {
                $file_path = array_shift($this->files_to_scan);

                if (!isset($this->scanned_files[$file_path])) {
                    $this->scanFile(
                        $file_path,
                        $filetype_scanners,
                        isset($this->files_to_deep_scan[$file_path])
                    );
                    $has_changes = true;
                }
            } else {
                /** @var string */
                $fq_classlike_name = array_shift($this->classes_to_scan);
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
                            echo 'Using reflection to get metadata for ' . $fq_classlike_name . PHP_EOL;
                        }

                        $reflected_class = new \ReflectionClass($fq_classlike_name);
                        $this->reflection->registerClass($reflected_class);
                        $this->reflected_classlikes_lc[$fq_classlike_name_lc] = true;
                    } elseif ($this->fileExistsForClassLike($classlikes, $fq_classlike_name)) {
                        if (isset($this->classlike_files[$fq_classlike_name_lc])) {
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
                }
            }
        }

        return $has_changes;
    }

    /**
     * @param  string $file_path
     * @param  array<string, string>  $filetype_scanners
     * @param  bool   $will_analyze
     *
     * @return FileScanner
     */
    private function scanFile(
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
            $file_checker = new $filetype_scanners[$extension]($file_path, $file_name, $will_analyze);
        } else {
            $file_checker = new FileScanner($file_path, $file_name, $will_analyze);
        }

        if (isset($this->scanned_files[$file_path])) {
            throw new \UnexpectedValueException('Should not be rescanning ' . $file_path);
        }

        $this->file_storage_provider->create($file_path);

        if ($this->debug_output) {
            if (isset($this->files_to_deep_scan[$file_path])) {
                echo 'Deep scanning ' . $file_path . PHP_EOL;
            } else {
                echo 'Scanning ' . $file_path . PHP_EOL;
            }
        }

        $this->scanned_files[$file_path] = true;

        $file_checker->scan(
            $this->codebase,
            $this->statements_provider->getStatementsForFile(
                $file_path,
                $this->debug_output
            ),
            $this->file_storage_provider->get($file_path)
        );

        return $file_checker;
    }

    /**
     * @param  string $file_path
     *
     * @return void
     */
    public function queueFileForScanning($file_path)
    {
        $this->files_to_scan[$file_path] = $file_path;
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

        if ($this->composer_classmap === null) {
            $this->composer_classmap = $this->config->getComposerClassMap();
        }

        if (isset($this->composer_classmap[$fq_class_name_lc])) {
            if (file_exists($this->composer_classmap[$fq_class_name_lc])) {
                if ($this->debug_output) {
                    echo 'Using generated composer classmap to locate file for ' . $fq_class_name . PHP_EOL;
                }

                $classlikes->addFullyQualifiedClassLikeName(
                    $fq_class_name_lc,
                    $this->composer_classmap[$fq_class_name_lc]
                );

                return true;
            }
        }

        $old_level = error_reporting();

        if (!$this->debug_output) {
            error_reporting(E_ERROR);
        }

        try {
            if ($this->debug_output) {
                echo 'Using reflection to locate file for ' . $fq_class_name . PHP_EOL;
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
