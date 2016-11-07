<?php
namespace Psalm\Checker;

use PhpParser\ParserFactory;
use PhpParser;
use Psalm\Config;
use Psalm\Context;
use Psalm\StatementsSource;

class FileChecker implements StatementsSource
{
    const PARSER_CACHE_DIRECTORY = 'php-parser';
    const REFERENCE_CACHE_NAME = 'references';
    const GOOD_RUN_NAME = 'good_run';

    /**
     * @var string
     */
    protected $real_file_name;

    /**
     * @var string
     */
    protected $short_file_name;

    /**
     * @var string|null
     */
    protected $include_file_name;

    /**
     * @var array
     */
    protected $aliased_classes = [];

    /**
     * @var array<string, array<int, string>>
     */
    protected $namespace_aliased_classes = [];

    /**
     * @var array<int, \PhpParser\Node>
     */
    protected $preloaded_statements = [];

    /**
     * @var array<int, string>
     */
    protected $declared_classes = [];

    /**
     * @var array<int, string>
     */
    protected $suppressed_issues = [];

    /**
     * @var array<string, static>
     */
    protected static $file_checkers = [];

    /**
     * @var array<string, bool>
     */
    protected static $functions_checked = [];

    /**
     * @var array<string, bool>
     */
    protected static $classes_checked = [];

    /**
     * @var array<string, bool>
     */
    protected static $files_checked = [];

    /**
     * @var bool
     */
    public static $show_notices = true;

    /**
     * @var int|null
     */
    protected static $last_good_run = null;

    /**
     * A lookup table used for getting all the files that reference a class
     *
     * @var array<string,array<string,bool>>
     */
    protected static $file_references_to_class = [];

    /**
     * A lookup table used for getting all the files referenced by a file
     *
     * @var array<string,array{a:array<int,string>,i:array<int,string>}>
     */
    protected static $file_references = [];

    /**
     * A lookup table used for getting all the files that reference any other file
     *
     * @var array<string,array<string,bool>>
     */
    protected static $referencing_files = [];

    /**
     * @var array<string,array<int,string>>
     */
    protected static $files_inheriting_classes = [];

    /**
     * A list of all files deleted since the last successful run
     *
     * @var array<int,string>|null
     */
    protected static $deleted_files = null;

    /**
     * @param string $file_name
     * @param array  $preloaded_statements
     */
    public function __construct($file_name, array $preloaded_statements = [])
    {
        $this->real_file_name = $file_name;
        $this->short_file_name = Config::getInstance()->shortenFileName($file_name);

        self::$file_checkers[$this->short_file_name] = $this;
        self::$file_checkers[$file_name] = $this;

        if ($preloaded_statements) {
            $this->preloaded_statements = $preloaded_statements;
        }
    }

    /**
     * @param   bool            $check_classes
     * @param   bool            $check_functions
     * @param   Context|null    $file_context
     * @param   bool            $cache
     * @return  array|null
     */
    public function check($check_classes = true, $check_functions = true, Context $file_context = null, $cache = true)
    {
        if ($cache && isset(self::$functions_checked[$this->short_file_name])) {
            return null;
        }

        if ($cache && $check_classes && !$check_functions && isset(self::$classes_checked[$this->real_file_name])) {
            return null;
        }

        if ($cache && !$check_classes && !$check_functions && isset(self::$files_checked[$this->real_file_name])) {
            return null;
        }

        if (!$file_context) {
            $file_context = new Context($this->short_file_name);
        }

        $config = Config::getInstance();

        $stmts = $this->getStatements();

        $leftover_stmts = [];

        $statments_checker = new StatementsChecker($this);

        $function_checkers = [];

        $this->registerUses();

        // hoist functions to the top
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_checkers[$stmt->name] = new FunctionChecker($stmt, $this, $file_context->file_name);
            }
        }

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_
                || $stmt instanceof PhpParser\Node\Stmt\Interface_
                || $stmt instanceof PhpParser\Node\Stmt\Trait_
                || $stmt instanceof PhpParser\Node\Stmt\Namespace_
                || $stmt instanceof PhpParser\Node\Stmt\Function_
            ) {
                if ($leftover_stmts) {
                    $statments_checker->check($leftover_stmts, $file_context);
                    $leftover_stmts = [];
                }

                if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name)
                            ?: new ClassChecker($stmt, $this, $stmt->name);

                        $this->declared_classes[] = $class_checker->getAbsoluteClass();
                        $class_checker->check($check_functions);
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name)
                            ?: new InterfaceChecker($stmt, $this, $stmt->name);

                        $this->declared_classes[] = $class_checker->getAbsoluteClass();
                        $class_checker->check(false);
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                    if ($check_classes) {
                        $trait_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name)
                            ?: new TraitChecker($stmt, $this, $stmt->name);

                        $trait_checker->check($check_functions);
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_ &&
                    $stmt->name instanceof PhpParser\Node\Name
                ) {
                    $namespace_name = implode('\\', $stmt->name->parts);

                    $namespace_checker = new NamespaceChecker($stmt, $this);
                    $this->namespace_aliased_classes[$namespace_name] = $namespace_checker->check(
                        $check_classes,
                        $check_functions
                    );

                    $this->declared_classes = array_merge($namespace_checker->getDeclaredClasses());
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_ && $check_functions) {
                    $function_context = new Context($this->short_file_name, $file_context->self);
                    $function_checkers[$stmt->name]->check($function_context, $file_context);

                    if (!$config->excludeIssueInFile('InvalidReturnType', $this->short_file_name)) {
                        $function_checkers[$stmt->name]->checkReturnTypes();
                    }
                }
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $statments_checker->check($leftover_stmts, $file_context);
        }

        if ($check_functions) {
            self::$functions_checked[$this->real_file_name] = true;
        }

        if ($check_classes) {
            self::$classes_checked[$this->real_file_name] = true;
        }

        self::$files_checked[$this->real_file_name] = true;

        return $stmts;
    }

    /**
     * @param  string $class
     * @param  string $namespace
     * @param  string $file_name
     * @return string
     */
    public static function getAbsoluteClassFromNameInFile($class, $namespace, $file_name)
    {
        if (isset(self::$file_checkers[$file_name])) {
            $aliased_classes = self::$file_checkers[$file_name]->getAliasedClasses($namespace);
        } else {
            $file_checker = new FileChecker($file_name);
            $file_checker->check(false, false, new Context($file_name));
            $aliased_classes = $file_checker->getAliasedClasses($namespace);
        }

        return ClassLikeChecker::getAbsoluteClassFromString($class, $namespace, $aliased_classes);
    }

    /**
     * Gets a list of the classes declared
     *
     * @return array<int, string>
     */
    public function getDeclaredClasses()
    {
        return $this->declared_classes;
    }

    /**
     * Gets a list of the classes declared in that file
     *
     * @param  string $file_name
     * @return array<string>
     */
    public static function getDeclaredClassesInFile($file_name)
    {
        if (isset(self::$file_checkers[$file_name])) {
            $file_checker = self::$file_checkers[$file_name];
        } else {
            $file_checker = new FileChecker($file_name);
            $file_checker->check(false, false, new Context($file_name));
        }

        return $file_checker->getDeclaredClasses();
    }

    /**
     * @return array<int, \PhpParser\Node>
     */
    protected function getStatements()
    {
        return $this->preloaded_statements
            ? $this->preloaded_statements
            : self::getStatementsForFile($this->real_file_name);
    }

    /**
     * @param  string $file_name
     * @return array<int, \PhpParser\Node>
     */
    public static function getStatementsForFile($file_name)
    {
        $stmts = [];

        $from_cache = false;

        $cache_location = null;

        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_directory .= '/' . self::PARSER_CACHE_DIRECTORY;

            $cache_location = $cache_directory . '/' . self::getParserCacheKey($file_name);

            if (is_readable($cache_location) && filemtime($cache_location) >= filemtime($file_name)) {
                /** @var array<int, \PhpParser\Node> */
                $stmts = unserialize((string)file_get_contents($cache_location));
                $from_cache = true;
            }
        }

        if (!$stmts) {
            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

            $stmts = $parser->parse((string)file_get_contents($file_name));
        }

        if ($cache_directory && $cache_location) {
            if ($from_cache) {
                touch($cache_location);
            } else {
                if (!is_dir($cache_directory)) {
                    mkdir($cache_directory, 0777, true);
                }

                file_put_contents($cache_location, serialize($stmts));
            }
        }

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    /**
     * @return bool
     */
    public static function loadReferenceCache()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_location = $cache_directory . '/' . self::REFERENCE_CACHE_NAME;

            if (is_readable($cache_location)) {
                self::$file_references = unserialize((string) file_get_contents($cache_location));
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    public static function updateReferenceCache()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_location = $cache_directory . '/' . self::REFERENCE_CACHE_NAME;

            foreach (self::$files_checked as $file => $_) {
                $all_file_references = array_unique(
                    array_merge(
                        isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [],
                        self::calculateFilesReferencingFile($file)
                    )
                );

                $inheritance_references = array_unique(
                    array_merge(
                        isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [],
                        self::calculateFilesInheritingFile($file)
                    )
                );

                self::$file_references[$file] = [
                    'a' => $all_file_references,
                    'i' => $inheritance_references
                ];
            }

            file_put_contents($cache_location, serialize(self::$file_references));
        }
    }

    /**
     * @return null
     */
    public function getNamespace()
    {
        return null;
    }

    /**
     * @param  string|null $namespace_name
     * @return array<string>
     */
    public function getAliasedClasses($namespace_name = null)
    {
        if ($namespace_name && isset($this->namespace_aliased_classes[$namespace_name])) {
            return $this->namespace_aliased_classes[$namespace_name];
        }

        return $this->aliased_classes;
    }

    /**
     * @return null
     */
    public function getAbsoluteClass()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getClassName()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getClassLikeChecker()
    {
        return null;
    }

    /**
     * @return null
     */
    public function getParentClass()
    {
        return null;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->short_file_name;
    }

    /**
     * @return string
     */
    public function getRealFileName()
    {
        return $this->real_file_name;
    }

    /**
     * @return null|string
     */
    public function getIncludeFileName()
    {
        return $this->include_file_name;
    }

    /**
     * @param string|null $file_name
     */
    public function setIncludeFileName($file_name)
    {
        $this->include_file_name = $file_name;
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->include_file_name ?: $this->short_file_name;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * @return null
     */
    public function getSource()
    {
        return null;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

    /**
     * @param   string  $file_name
     * @return  mixed
     */
    public static function getFileCheckerFromFileName($file_name)
    {
        return self::$file_checkers[$file_name];
    }

    /**
     * @param  string $class_name
     * @return ClassLikeChecker|null
     */
    public static function getClassLikeCheckerFromClass($class_name)
    {
        $old_level = error_reporting();
        error_reporting(0);
        $file_name = (string)(new \ReflectionClass($class_name))->getFileName();
        error_reporting($old_level);

        if (isset(self::$file_checkers[$file_name])) {
            $file_checker = self::$file_checkers[$file_name];
        } else {
            $file_checker = new FileChecker($file_name);
        }

        $file_checker->check(true, false, null, false);

        return ClassLikeChecker::getClassLikeCheckerFromClass($class_name);
    }

    /**
     * @return void
     */
    protected function registerUses()
    {
        foreach ($this->getStatements() as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->aliased_classes[strtolower($use->alias)] = implode('\\', $use->name->parts);
                }
            }
        }
    }

    /**
     * @param string $source_file
     * @param string $absolute_class
     * @return void
     */
    public static function addFileReferenceToClass($source_file, $absolute_class)
    {
        self::$referencing_files[$source_file] = true;
        self::$file_references_to_class[$absolute_class][$source_file] = true;
    }

    /**
     * @param string $source_file
     * @param string $absolute_class
     * @return void
     */
    public static function addFileInheritanceToClass($source_file, $absolute_class)
    {
        self::$files_inheriting_classes[$absolute_class][$source_file] = true;
    }

    /**
     * @param   string $file
     * @return  array
     */
    public static function calculateFilesReferencingFile($file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($file);

        foreach ($file_classes as $file_class) {
            if (isset(self::$file_references_to_class[$file_class])) {
                $referenced_files = array_merge(
                    $referenced_files,
                    array_keys(self::$file_references_to_class[$file_class])
                );
            }
        }

        return array_unique($referenced_files);
    }

    /**
     * @param   string $file
     * @return  array
     */
    public static function calculateFilesInheritingFile($file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($file);

        foreach ($file_classes as $file_class) {
            if (isset(self::$files_inheriting_classes[$file_class])) {
                $referenced_files = array_merge(
                    $referenced_files,
                    array_keys(self::$files_inheriting_classes[$file_class])
                );
            }
        }

        return array_unique($referenced_files);
    }

    /**
     * @param  string $file
     * @return array<string>
     */
    public static function getFilesReferencingFile($file)
    {
        return isset(self::$file_references[$file]['a']) ? self::$file_references[$file]['a'] : [];
    }

    /**
     * @param  string $file
     * @return array<string>
     */
    public static function getFilesInheritingFromFile($file)
    {
        return isset(self::$file_references[$file]['i']) ? self::$file_references[$file]['i'] : [];
    }

    /**
     * @return bool
     */
    public static function canDiffFiles()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        return $cache_directory && file_exists($cache_directory . '/' . self::GOOD_RUN_NAME);
    }

    /**
     * @return int
     */
    public static function getLastGoodRun()
    {
        if (self::$last_good_run === null) {
            $cache_directory = Config::getInstance()->getCacheDirectory();

            self::$last_good_run = filemtime($cache_directory . '/' . self::GOOD_RUN_NAME) ?: 0;
        }

        return self::$last_good_run;
    }

    /**
     * @param  string  $file
     * @return boolean
     */
    public static function hasFileChanged($file)
    {
        return filemtime($file) > self::getLastGoodRun();
    }

    /**
     * @return array<string>
     */
    public static function getDeletedReferencedFiles()
    {
        if (self::$deleted_files === null) {
            self::$deleted_files = array_filter(
                array_keys(self::$file_references),
                function ($file_name) {
                    return !file_exists((string)$file_name);
                }
            );
        }

        return self::$deleted_files;
    }

    /**
     * @param int $start_time
     * @return void
     */
    public static function goodRun($start_time)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $run_cache_location = $cache_directory . '/' . self::GOOD_RUN_NAME;

            touch($run_cache_location, $start_time);

            $deleted_files = self::getDeletedReferencedFiles();

            if ($deleted_files) {
                foreach ($deleted_files as $file) {
                    unset(self::$file_references[$file]);
                }

                file_put_contents(
                    $cache_directory . '/' . self::REFERENCE_CACHE_NAME,
                    serialize(self::$file_references)
                );
            }

            $cache_directory .= '/' . self::PARSER_CACHE_DIRECTORY;

            if (is_dir($cache_directory)) {
                $directory_files = scandir($cache_directory);

                foreach ($directory_files as $directory_file) {
                    $full_path = $cache_directory . '/' . $directory_file;

                    if ($directory_file[0] === '.') {
                        continue;
                    }

                    touch($full_path);
                }
            }
        }
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$file_checkers = [];

        self::$functions_checked = [];
        self::$classes_checked = [];
        self::$files_checked = [];

        ClassLikeChecker::clearCache();
        FunctionChecker::clearCache();
        StatementsChecker::clearCache();
    }

    /**
     * @param  float $time_before
     * @return int
     */
    public static function deleteOldParserCaches($time_before)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        $removed_count = 0;

        if ($cache_directory) {
            $cache_directory .= '/' . self::PARSER_CACHE_DIRECTORY;

            if (is_dir($cache_directory)) {
                $directory_files = scandir($cache_directory);

                foreach ($directory_files as $directory_file) {
                    $full_path = $cache_directory . '/' . $directory_file;

                    if ($directory_file[0] === '.') {
                        continue;
                    }

                    if (filemtime($full_path) < $time_before && is_writable($full_path)) {
                        unlink($full_path);
                        $removed_count++;
                    }
                }
            }
        }

        return $removed_count;
    }

    /**
     * @param  array<string>    $file_names
     * @param  int              $min_time
     * @return void
     */
    public static function touchParserCaches(array $file_names, $min_time)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_directory .= '/' . self::PARSER_CACHE_DIRECTORY;

            if (is_dir($cache_directory)) {
                foreach ($file_names as $file_name) {
                    $hash_file_name = $cache_directory . '/' . self::getParserCacheKey($file_name);

                    if (file_exists($hash_file_name)) {
                        if (filemtime($hash_file_name) < $min_time) {
                            touch($hash_file_name, $min_time);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  string $file_name
     * @return string
     */
    protected static function getParserCacheKey($file_name)
    {
        return md5($file_name);
    }
}
