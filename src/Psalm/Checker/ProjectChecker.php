<?php
namespace Psalm\Checker;

use Psalm\Config;
use Psalm\Context;
use Psalm\Exception;
use Psalm\IssueBuffer;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ProjectChecker
{
    /**
     * Cached config
     *
     * @var Config|null
     */
    protected $config;

    /**
     * @var self
     */
    public static $instance;

    /**
     * Whether or not to use colors in error output
     *
     * @var boolean
     */
    public $use_color;

    /**
     * Whether or not to show informational messages
     *
     * @var boolean
     */
    public $show_info;

    /**
     * @var string
     */
    public $output_format;

    /**
     * @var bool
     */
    public $count_references = false;

    /**
     * @var bool
     */
    public $debug_output = false;

    /**
     * @var boolean
     */
    public $update_docblocks = false;

    /**
     * @var boolean
     */
    public $cache = false;

    /**
     * @var array<string, bool>
     */
    protected $existing_classlikes_ci = [];

    /**
     * @var array<string, bool>
     */
    protected $existing_classlikes = [];

    /**
     * @var array<string, bool>
     */
    protected $existing_classes_ci = [];

    /**
     * @var array<string, bool>
     */
    public $existing_classes = [];

    /**
     * @var array<string, bool>
     */
    protected $existing_interfaces_ci = [];

    /**
     * @var array<string, bool>
     */
    public $existing_interfaces = [];

    /**
     * @var array<string, bool>
     */
    protected $existing_traits_ci = [];

    /**
     * @var array<string, bool>
     */
    public $existing_traits = [];

    /**
     * @var array<string, string>
     */
    protected $classlike_files = [];

    /**
     * @var array<string, string>
     */
    protected $files_to_visit = [];

    /**
     * @var array<string, string>
     */
    protected $files_to_analyze = [];

    /**
     * @var array<string, bool>
     */
    protected $scanned_files = [];

    /**
     * @var array<string, bool>
     */
    protected $visited_files = [];

    /**
     * @var array<string, bool>
     */
    protected $visited_classes = [];

    /**
     * @var array<string, FileChecker>
     */
    protected $file_checkers = [];

    /**
     * @var array<string, MethodChecker>
     */
    public $method_checkers = [];

    /**
     * @var array<string, int>
     */
    public $classlike_references = [];

    /**
     * @var array<string, string>
     */
    public $fake_files = [];

    const TYPE_CONSOLE = 'console';
    const TYPE_JSON = 'json';
    const TYPE_EMACS = 'emacs';

    /**
     * @param boolean $use_color
     * @param boolean $show_info
     * @param boolean $debug_output
     * @param string  $output_format
     * @param bool    $update_docblocks
     * @param bool    $find_dead_code
     */
    public function __construct(
        $use_color = true,
        $show_info = true,
        $output_format = self::TYPE_CONSOLE,
        $debug_output = false,
        $update_docblocks = false,
        $find_dead_code = false
    ) {
        $this->use_color = $use_color;
        $this->show_info = $show_info;
        $this->debug_output = $debug_output;
        $this->update_docblocks = $update_docblocks;
        $this->count_references = $find_dead_code;

        if (!in_array($output_format, [self::TYPE_CONSOLE, self::TYPE_JSON, self::TYPE_EMACS])) {
            throw new \UnexpectedValueException('Unrecognised output format ' . $output_format);
        }

        $this->output_format = $output_format;
        self::$instance = $this;
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        return self::$instance;
    }

    /**
     * @param  boolean $is_diff
     * @return void
     */
    public function check($is_diff = false)
    {
        $cwd = getcwd();

        $start_checks = (int)microtime(true);

        if (!$cwd) {
            throw new \InvalidArgumentException('Cannot work with empty cwd');
        }

        if (!$this->config) {
            $this->config = $this->getConfigForPath($cwd);
        }

        $diff_files = null;
        $deleted_files = null;

        if ($is_diff && FileChecker::loadReferenceCache() && FileChecker::canDiffFiles()) {
            $deleted_files = FileChecker::getDeletedReferencedFiles();
            $diff_files = $deleted_files;

            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $diff_files = array_merge($diff_files, self::getDiffFilesInDir($dir_name, $this->config));
            }
        }

        $files_checked = [];

        if ($diff_files === null || $deleted_files === null || count($diff_files) > 200) {
            foreach ($this->config->getProjectDirectories() as $dir_name) {
                $this->checkDirWithConfig($dir_name, $this->config);
            }

            $this->visitFiles();
            $this->analyzeFiles();
        } else {
            if ($this->debug_output) {
                echo count($diff_files) . ' changed files' . PHP_EOL;
            }

            $file_list = self::getReferencedFilesFromDiff($diff_files);

            // strip out deleted files
            $file_list = array_diff($file_list, $deleted_files);
            $this->checkDiffFilesWithConfig($this->config, $file_list);

            $this->visitFiles();
            $this->analyzeFiles();
        }

        $removed_parser_files = FileChecker::deleteOldParserCaches(
            $is_diff ? FileChecker::getLastGoodRun() : $start_checks
        );

        if ($this->debug_output && $removed_parser_files) {
            echo 'Removed ' . $removed_parser_files . ' old parser caches' . PHP_EOL;
        }

        if ($is_diff) {
            FileChecker::touchParserCaches($this->getAllFiles($this->config), $start_checks);
        }

        if ($this->count_references) {
            foreach ($this->existing_classlikes_ci as $fq_class_name_ci => $_) {
                if (isset(ClassLikeChecker::$storage[$fq_class_name_ci])) {
                    $classlike_storage = ClassLikeChecker::$storage[$fq_class_name_ci];

                    if ($classlike_storage->file_path &&
                        $this->config->isInProjectDirs($classlike_storage->file_path)
                    ) {
                        if (!isset($this->classlike_references[$fq_class_name_ci])) {
                            echo $fq_class_name_ci . ' is never referenced' . PHP_EOL;
                        } else {
                            foreach ($classlike_storage->methods as $method_name => $method_storage) {
                                if ($method_storage->references === 0 &&
                                    !$classlike_storage->overridden_method_ids[$method_name]
                                ) {
                                    echo 'Method ' . $fq_class_name_ci . '::' . $method_name .
                                        ' is never referenced' . PHP_EOL;
                                }
                            }
                        }
                    }
                }
            }
        }

        IssueBuffer::finish(true, (int)$start_checks, $this->debug_output);
    }

    /**
     * @return void
     */
    protected function visitFiles()
    {
        if (!$this->config) {
            throw new \UnexpectedValueException('$this->config cannot be null');
        }

        $filetype_handlers = $this->config->getFiletypeHandlers();

        foreach ($this->files_to_analyze as $file_path => $_) {
            $this->visitFile($file_path, $filetype_handlers);
        }
    }

    /**
     * @return void
     */
    protected function analyzeFiles()
    {
        if (!$this->config) {
            throw new \UnexpectedValueException('$this->config cannot be null');
        }

        $filetype_handlers = $this->config->getFiletypeHandlers();

        foreach ($this->files_to_analyze as $file_path => $_) {
            $file_checker = $this->visitFile($file_path, $filetype_handlers);

            if ($this->debug_output) {
                echo 'Analyzing ' . $file_checker->getFilePath() . PHP_EOL;
            }

            $file_checker->analyze($this->update_docblocks);
        }
    }

    /**
     * @param  string  $dir_name
     * @return void
     */
    public function checkDir($dir_name)
    {
        if (!$this->config) {
            $this->config = $this->getConfigForPath($dir_name);
            $this->config->hide_external_errors = $this->config->isInProjectDirs($dir_name . DIRECTORY_SEPARATOR);
        }

        FileChecker::loadReferenceCache();

        $start_checks = (int)microtime(true);

        $this->checkDirWithConfig($dir_name, $this->config, true);

        $this->visitFiles();
        $this->analyzeFiles();

        IssueBuffer::finish(false, $start_checks, $this->debug_output);
    }

    /**
     * @param  string $dir_name
     * @param  Config $config
     * @param  bool   $allow_non_project_files
     * @return void
     */
    protected function checkDirWithConfig($dir_name, Config $config, $allow_non_project_files = false)
    {
        $file_extensions = $config->getFileExtensions();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions)) {
                    $file_path = (string)$iterator->getRealPath();

                    if ($allow_non_project_files || $config->isInProjectDirs($file_path)) {
                        $this->files_to_analyze[$file_path] = $file_path;
                    }
                }
            }

            $iterator->next();
        }
    }

    /**
     * @param  Config $config
     * @return array<int, string>
     */
    protected function getAllFiles(Config $config)
    {
        $file_extensions = $config->getFileExtensions();
        $file_names = [];

        foreach ($config->getProjectDirectories() as $dir_name) {
            /** @var RecursiveDirectoryIterator */
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
            $iterator->rewind();

            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    $extension = $iterator->getExtension();
                    if (in_array($extension, $file_extensions)) {
                        $file_names[] = (string)$iterator->getRealPath();
                    }
                }

                $iterator->next();
            }
        }

        return $file_names;
    }

    /**
     * @param  string $dir_name
     * @param  Config $config
     * @return array<string>
     */
    protected static function getDiffFilesInDir($dir_name, Config $config)
    {
        $file_extensions = $config->getFileExtensions();
        $filetype_handlers = $config->getFiletypeHandlers();

        /** @var RecursiveDirectoryIterator */
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_name));
        $iterator->rewind();

        $diff_files = [];

        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                $extension = $iterator->getExtension();
                if (in_array($extension, $file_extensions)) {
                    $file_name = (string)$iterator->getRealPath();

                    if ($config->isInProjectDirs($file_name)) {
                        if (FileChecker::hasFileChanged($file_name)) {
                            $diff_files[] = $file_name;
                        }
                    }
                }
            }

            $iterator->next();
        }

        return $diff_files;
    }

    /**
     * @param  Config           $config
     * @param  array<string>    $file_list
     * @return void
     */
    protected function checkDiffFilesWithConfig(Config $config, array $file_list = [])
    {
        $file_extensions = $config->getFileExtensions();
        $filetype_handlers = $config->getFiletypeHandlers();

        foreach ($file_list as $file_path) {
            if (!file_exists($file_path)) {
                continue;
            }

            if (!$config->isInProjectDirs($file_path)) {
                if ($this->debug_output) {
                    echo('skipping ' . $file_path . PHP_EOL);
                }

                continue;
            }

            $this->files_to_analyze[$file_path] = $file_path;
        }
    }

    /**
     * @param  string  $file_name
     * @return void
     */
    public function checkFile($file_name)
    {
        if ($this->debug_output) {
            echo 'Checking ' . $file_name . PHP_EOL;
        }

        if (!$this->config) {
            $this->config = $this->getConfigForPath($file_name);
        }

        $start_checks = (int)microtime(true);

        $this->config->hide_external_errors = $this->config->isInProjectDirs($file_name);

        $file_name_parts = explode('.', $file_name);

        $extension = array_pop($file_name_parts);

        $filetype_handlers = $this->config->getFiletypeHandlers();

        FileChecker::loadReferenceCache();

        $file_checker = $this->visitFile($file_name, $filetype_handlers);

        if ($this->debug_output) {
            echo 'Analyzing ' . $file_checker->getFilePath() . PHP_EOL;
        }

        $file_checker->analyze($this->update_docblocks);

        IssueBuffer::finish(false, $start_checks, $this->debug_output);
    }

    /**
     * @param  string $file_path
     * @param  array  $filetype_handlers
     * @return FileChecker
     */
    public function getFileChecker($file_path, array $filetype_handlers)
    {
        $extension = (string)pathinfo($file_path)['extension'];

        if (isset($filetype_handlers[$extension])) {
            /** @var FileChecker */
            return new $filetype_handlers[$extension]($file_path, $this);
        }

        return new FileChecker($file_path, $this);
    }

    /**
     * @param  string $file_path
     * @param  array  $filetype_handlers
     * @return FileChecker
     */
    public function visitFile($file_path, array $filetype_handlers)
    {
        $file_checker = $this->getFileChecker($file_path, $filetype_handlers);

        if ($this->debug_output) {
            echo (isset($this->visited_files[$file_path]) ? 'Rev' : 'V') . 'isiting ' . $file_path . PHP_EOL;
        }

        $this->visited_files[$file_path] = true;

        $file_checker->visit();

        return $file_checker;
    }

    /**
     * Checks whether a class exists, and if it does then records what file it's in
     * for later checking
     *
     * @param  string $fq_class_name
     * @return boolean
     * @psalm-suppress MixedMethodCall due to Reflection class weirdness
     */
    public function fileExistsForClassLike($fq_class_name)
    {
        $fq_class_name_ci = strtolower($fq_class_name);

        if (isset($this->existing_classlikes_ci[$fq_class_name_ci])) {
            return $this->existing_classlikes_ci[$fq_class_name_ci];
        }

        if (!$this->config) {
            throw new \UnexpectedValueException('Config should not be null here');
        }

        $predefined_classlikes = $this->config->getPredefinedClassLikes();

        if (isset($predefined_classlikes[$fq_class_name_ci])) {
            $this->visited_classes[$fq_class_name_ci] = true;
            $reflected_class = new \ReflectionClass($fq_class_name);
            ClassLikeChecker::registerReflectedClass($reflected_class->name, $reflected_class, $this);
            return true;
        }

        $old_level = error_reporting();
        error_reporting(0);

        try {
            $reflected_class = new \ReflectionClass($fq_class_name);
        } catch (\ReflectionException $e) {
            error_reporting($old_level);

            $this->visited_classes[$fq_class_name_ci] = false;

            return false;
        }

        error_reporting($old_level);

        $fq_class_name = $reflected_class->getName();
        $this->existing_classlikes_ci[$fq_class_name_ci] = true;
        $this->existing_classlikes[$fq_class_name] = true;

        if ($reflected_class->isInterface()) {
            $this->addFullyQualifiedInterfaceName($fq_class_name, (string)$reflected_class->getFileName());
        } elseif ($reflected_class->isTrait()) {
            $this->addFullyQualifiedTraitName($fq_class_name, (string)$reflected_class->getFileName());
        } else {
            $this->addFullyQualifiedClassName($fq_class_name, (string)$reflected_class->getFileName());
        }

        return true;
    }

    /**
     * @param  string       $fq_class_name
     * @return boolean
     * @psalm-suppress MixedMethodCall due to Reflection class weirdness
     */
    public function visitFileForClassLike($fq_class_name)
    {
        if (!$fq_class_name || strpos($fq_class_name, '::') !== false) {
            throw new \InvalidArgumentException('Invalid class name ' . $fq_class_name);
        }

        $fq_class_name_ci = strtolower($fq_class_name);

        if (isset($this->visited_classes[$fq_class_name_ci])) {
            return $this->visited_classes[$fq_class_name_ci];
        }

        $this->visited_classes[$fq_class_name_ci] = true;

        // this registers the class if it's not user defined
        if (!$this->fileExistsForClassLike($fq_class_name)) {
            return false;
        }

        if (isset($this->classlike_files[$fq_class_name_ci])) {
            $file_path = $this->classlike_files[$fq_class_name_ci];

            if (isset($this->visited_files[$file_path])) {
                return true;
            }

            $this->visited_files[$file_path] = true;

            $file_checker = new FileChecker($file_path, $this);

            $short_file_name = $file_checker->getFileName();

            ClassLikeChecker::$file_classes[$file_path][] = $fq_class_name;

            $fq_class_name_lower = strtolower($fq_class_name);

            if ($this->debug_output) {
                echo 'Visiting ' . $file_path . PHP_EOL;
            }

            $file_checker->visit();

            $storage = ClassLikeChecker::$storage[$fq_class_name_lower];

            if (ClassLikeChecker::inPropertyMap($fq_class_name)) {
                $public_mapped_properties = ClassLikeChecker::getPropertyMap()[strtolower($fq_class_name)];

                foreach ($public_mapped_properties as $property_name => $public_mapped_property) {
                    $property_type = Type::parseString($public_mapped_property);
                    $storage->properties[$property_name] = new PropertyStorage();
                    $storage->properties[$property_name]->type = $property_type;
                    $storage->properties[$property_name]->visibility = ClassLikeChecker::VISIBILITY_PUBLIC;

                    $property_id = $fq_class_name . '::$' . $property_name;

                    $storage->declaring_property_ids[$property_name] = $property_id;
                    $storage->appearing_property_ids[$property_name] = $property_id;
                }
            }
        }

        return true;
    }

    /**
     * @return void
     */
    public function enableCache()
    {
        $this->cache = true;
    }

    /**
     * @return void
     */
    public function disableCache()
    {
        $this->cache = false;
    }

    /**
     * @return bool
     */
    public function canCache()
    {
        return $this->cache;
    }

    /**
     * @param  string   $original_method_id
     * @param  Context  $this_context
     * @return void
     */
    public function getMethodMutations($original_method_id, Context $this_context)
    {
        list($fq_class_name) = explode('::', $original_method_id);

        $file_checker = $this->getVisitedFileCheckerForClassLike($fq_class_name);

        $declaring_method_id = (string)MethodChecker::getDeclaringMethodId($original_method_id);
        list($declaring_fq_class_name) = explode('::', $declaring_method_id);

        if (strtolower($declaring_fq_class_name) !== strtolower($fq_class_name)) {
            $file_checker = $this->getVisitedFileCheckerForClassLike($declaring_fq_class_name);
        }

        $file_checker->analyze(false, true);

        if (!$this_context->self) {
            $this_context->self = $fq_class_name;
            $this_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
        }

        $file_checker->getMethodMutations($declaring_method_id, $this_context);
    }

    /**
     * @param  string $fq_class_name
     * @return FileChecker
     */
    public function getVisitedFileCheckerForClassLike($fq_class_name)
    {
        $fq_class_name_ci = strtolower($fq_class_name);

        if (!$this->fake_files) {
            // this registers the class if it's not user defined
            if (!$this->fileExistsForClassLike($fq_class_name)) {
                throw new \UnexpectedValueException('File does not exist for ' . $fq_class_name);
            }

            if (!isset($this->classlike_files[$fq_class_name_ci])) {
                throw new \UnexpectedValueException('Class ' . $fq_class_name . ' is not user-defined');
            }

            $file_path = $this->classlike_files[$fq_class_name_ci];
        } else {
            $file_path = array_keys($this->fake_files)[0];
        }

        if ($this->cache && isset($this->file_checkers[$file_path])) {
            return $this->file_checkers[$file_path];
        }

        $file_checker = new FileChecker($file_path, $this);

        $file_checker->visit();

        if ($this->debug_output) {
            echo 'Visiting ' . $file_path . PHP_EOL;
        }

        if ($this->cache) {
            $this->file_checkers[$file_path] = $file_checker;
        }

        return $file_checker;
    }

    /**
     * Gets a Config object from an XML file.
     *
     * Searches up a folder hierarchy for the most immediate config.
     *
     * @param  string $path
     * @return Config
     * @throws Exception\ConfigException If a config path is not found.
     */
    protected function getConfigForPath($path)
    {
        $dir_path = realpath($path);

        if (!is_dir($dir_path)) {
            $dir_path = dirname($dir_path);
        }

        $config = null;

        do {
            $maybe_path = $dir_path . DIRECTORY_SEPARATOR . Config::DEFAULT_FILE_NAME;

            if (file_exists($maybe_path)) {
                $config = Config::loadFromXMLFile($maybe_path);

                break;
            }

            $dir_path = dirname($dir_path);
        } while (dirname($dir_path) !== $dir_path);

        if (!$config) {
            throw new Exception\ConfigException('Config not found for path ' . $path);
        }

        $this->config = $config;

        $config->visitStubFiles($this);
        $config->initializePlugins($this);

        return $config;
    }

    /**
     * @param  Config $config
     * @return void
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        $config->visitStubFiles($this);
        $config->initializePlugins($this);
    }

    /**
     * @param string $path_to_config
     * @return void
     * @throws Exception\ConfigException If a config file is not found in the given location.
     */
    public function setConfigXML($path_to_config)
    {
        if (!file_exists($path_to_config)) {
            throw new Exception\ConfigException('Config not found at location ' . $path_to_config);
        }

        $dir_path = dirname($path_to_config) . DIRECTORY_SEPARATOR;

        $this->config = Config::loadFromXMLFile($path_to_config);
    }

    /**
     * @param  array<string>  $diff_files
     * @return array<string>
     */
    public static function getReferencedFilesFromDiff(array $diff_files)
    {
        $all_inherited_files_to_check = $diff_files;

        while ($diff_files) {
            $diff_file = array_shift($diff_files);

            $dependent_files = FileChecker::getFilesInheritingFromFile($diff_file);
            $new_dependent_files = array_diff($dependent_files, $all_inherited_files_to_check);

            $all_inherited_files_to_check += $new_dependent_files;
            $diff_files += $new_dependent_files;
        }

        $all_files_to_check = $all_inherited_files_to_check;

        foreach ($all_inherited_files_to_check as $file_name) {
            $dependent_files = FileChecker::getFilesReferencingFile($file_name);
            $all_files_to_check = array_merge($dependent_files, $all_files_to_check);
        }

        return array_unique($all_files_to_check);
    }

    /**
     * @param  string $file_path
     * @param  string $file_contents
     * @return void
     */
    public function registerFile($file_path, $file_contents)
    {
        $this->fake_files[$file_path] = $file_contents;
    }

    /**
     * @param  string $file_path
     * @return void
     */
    public function registerVisitedFile($file_path)
    {
        $this->visited_files[$file_path] = true;
    }

    /**
     * @param  string $file_path
     * @return string
     */
    public function getFileContents($file_path)
    {
        if (isset($this->fake_files[$file_path])) {
            return $this->fake_files[$file_path];
        }

        return (string)file_get_contents($file_path);
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     * @return void
     */
    public function addFullyQualifiedClassName($fq_class_name, $file_path = null)
    {
        $fq_class_name_ci = strtolower($fq_class_name);
        $this->existing_classlikes_ci[$fq_class_name_ci] = true;
        $this->existing_classes_ci[$fq_class_name_ci] = true;
        $this->existing_traits_ci[$fq_class_name_ci] = false;
        $this->existing_interfaces_ci[$fq_class_name_ci] = false;
        $this->existing_classes[$fq_class_name] = true;

        if ($file_path) {
            $this->classlike_files[$fq_class_name_ci] = $file_path;
        }
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     * @return void
     */
    public function addFullyQualifiedInterfaceName($fq_class_name, $file_path = null)
    {
        $fq_class_name_ci = strtolower($fq_class_name);
        $this->existing_classlikes_ci[$fq_class_name_ci] = true;
        $this->existing_interfaces_ci[$fq_class_name_ci] = true;
        $this->existing_classes_ci[$fq_class_name_ci] = false;
        $this->existing_traits_ci[$fq_class_name_ci] = false;
        $this->existing_interfaces[$fq_class_name] = true;

        if ($file_path) {
            $this->classlike_files[$fq_class_name_ci] = $file_path;
        }
    }

    /**
     * @param string        $fq_class_name
     * @param string|null   $file_path
     * @return void
     */
    public function addFullyQualifiedTraitName($fq_class_name, $file_path = null)
    {
        $fq_class_name_ci = strtolower($fq_class_name);
        $this->existing_classlikes_ci[$fq_class_name_ci] = true;
        $this->existing_traits_ci[$fq_class_name_ci] = true;
        $this->existing_classes_ci[$fq_class_name_ci] = false;
        $this->existing_interfaces_ci[$fq_class_name_ci] = false;
        $this->existing_traits[$fq_class_name] = true;

        if ($file_path) {
            $this->classlike_files[$fq_class_name_ci] = $file_path;
        }
    }

    /**
     * @param string $fq_class_name
     * @return bool
     */
    public function hasFullyQualifiedClassName($fq_class_name)
    {
        $fq_class_name_ci = strtolower($fq_class_name);

        if (!isset($this->existing_classes_ci[$fq_class_name_ci]) ||
            !$this->existing_classes_ci[$fq_class_name_ci]
        ) {
            return false;
        }

        if ($this->count_references) {
            if (!isset($this->classlike_references[$fq_class_name_ci])) {
                $this->classlike_references[$fq_class_name_ci] = 0;
            }

            $this->classlike_references[$fq_class_name_ci]++;
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     * @return bool
     */
    public function hasFullyQualifiedInterfaceName($fq_class_name)
    {
        $fq_class_name_ci = strtolower($fq_class_name);

        if (!isset($this->existing_interfaces_ci[$fq_class_name_ci]) ||
            !$this->existing_interfaces_ci[$fq_class_name_ci]
        ) {
            return false;
        }

        if ($this->count_references) {
            if (!isset($this->classlike_references[$fq_class_name_ci])) {
                $this->classlike_references[$fq_class_name_ci] = 0;
            }

            $this->classlike_references[$fq_class_name_ci]++;
        }

        return true;
    }

    /**
     * @param string $fq_class_name
     * @return bool
     */
    public function hasFullyQualifiedTraitName($fq_class_name)
    {
        $fq_class_name_ci = strtolower($fq_class_name);

        if (!isset($this->existing_traits_ci[$fq_class_name_ci]) ||
            !$this->existing_traits_ci[$fq_class_name_ci]
        ) {
            return false;
        }

        if ($this->count_references) {
            if (!isset($this->classlike_references[$fq_class_name_ci])) {
                $this->classlike_references[$fq_class_name_ci] = 0;
            }

            $this->classlike_references[$fq_class_name_ci]++;
        }

        return true;
    }
}
