<?php

namespace Psalm\Checker;

use PhpParser;
use PhpParser\Error;
use PhpParser\ParserFactory;

use Psalm\StatementsSource;
use Psalm\Config;
use Psalm\Context;

class FileChecker implements StatementsSource
{
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
     * @var string
     */
    protected $namespace;

    /**
     * @var array
     */
    protected $aliased_classes = [];

    protected $class_name;

    protected $namespace_aliased_classes = [];

    protected $preloaded_statements = [];

    protected $declared_classes = [];

    /**
     * @var array
     */
    protected $suppressed_issues = [];

    /**
     * @var string|null
     */
    protected static $cache_dir = null;

    /**
     * @var array<string,static>
     */
    protected static $file_checkers = [];

    protected static $functions_checked = [];
    protected static $classes_checked = [];
    protected static $files_checked = [];

    public static $show_notices = true;

    const REFERENCE_CACHE_NAME = 'references';
    const GOOD_RUN_NAME = 'good_run';

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
     * @var array<string,object-like{a:array<int,string>,i:array<int,string>}>
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

    public function check($check_classes = true, $check_functions = true, Context $file_context = null, $cache = true)
    {
        if ($cache && isset(self::$functions_checked[$this->short_file_name])) {
            return;
        }

        if ($cache && $check_classes && !$check_functions && isset(self::$classes_checked[$this->real_file_name])) {
            return;
        }

        if ($cache && !$check_classes && !$check_functions && isset(self::$files_checked[$this->real_file_name])) {
            return;
        }

        if (!$file_context) {
            $file_context = new Context($this->short_file_name);
        }

        $config = Config::getInstance();

        $stmts = $this->getStatements();

        $leftover_stmts = [];

        $statments_checker = new StatementsChecker($this);

        $function_checkers = [];

        // hoist functions to the top
        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                foreach ($stmt->uses as $use) {
                    $this->aliased_classes[strtolower($use->alias)] = implode('\\', $use->name->parts);
                }
            }

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
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name) ?: new ClassChecker($stmt, $this, $stmt->name);
                        $this->declared_classes[] = $class_checker->getAbsoluteClass();
                        $class_checker->check($check_functions);
                    }

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name) ?: new InterfaceChecker($stmt, $this, $stmt->name);
                        $this->declared_classes[] = $class_checker->getAbsoluteClass();
                        $class_checker->check(false);
                    }

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                    if ($check_classes) {
                        $trait_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name) ?: new TraitChecker($stmt, $this, $stmt->name);
                        $trait_checker->check($check_functions);
                    }

                } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_ && $stmt->name instanceof PhpParser\Node\Name) {
                    $namespace_name = implode('\\', $stmt->name->parts);

                    $namespace_checker = new NamespaceChecker($stmt, $this);
                    $this->namespace_aliased_classes[$namespace_name] = $namespace_checker->check($check_classes, $check_functions);
                    $this->declared_classes = array_merge($namespace_checker->getDeclaredClasses());

                }
                elseif ($stmt instanceof PhpParser\Node\Stmt\Function_ && $check_functions) {
                    $function_context = new Context($this->short_file_name, $file_context->self);
                    $function_checkers[$stmt->name]->check($function_context);

                    if (!$config->excludeIssueInFile('InvalidReturnType', $this->short_file_name)) {
                        $function_checkers[$stmt->name]->checkReturnTypes();
                    }
                }
            }
            else {
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
     * @return array<string>
     */
    public function getDeclaredClasses()
    {
        return $this->declared_classes;
    }

    /**
     * Gets a list of the classes declared in that file
     * @param  string $file_name
     * @return array<string>
     */
    public static function getDeclaredClassesInFile($file_name)
    {
        if (isset(self::$file_checkers[$file_name])) {
            $file_checker = self::$file_checkers[$file_name];
        }
        else {
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
        return $this->preloaded_statements ?
                    $this->preloaded_statements :
                    self::getStatementsForFile($this->real_file_name);
    }

    /**
     * @param  string $file_name
     * @return array<\PhpParser\Node>
     */
    public static function getStatementsForFile($file_name)
    {
        $contents = (string) file_get_contents($file_name);

        $stmts = [];

        $from_cache = false;

        $cache_location = null;

        if (self::$cache_dir) {
            $key = md5($contents);

            $cache_location = self::$cache_dir . '/' . $key;

            if (is_readable($cache_location)) {
                $stmts = unserialize((string) file_get_contents($cache_location));
                $from_cache = true;
            }
        }

        if (!$stmts && $contents) {
            $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

            $stmts = $parser->parse($contents);
        }

        if (self::$cache_dir && $cache_location) {
            if ($from_cache) {
                touch($cache_location);
            } else {
                if (!file_exists(self::$cache_dir)) {
                    mkdir(self::$cache_dir);
                }

                file_put_contents($cache_location, serialize($stmts));
            }
        }

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    public static function loadReferenceCache()
    {
        if (self::$cache_dir) {
            $cache_location = self::$cache_dir . '/' . self::REFERENCE_CACHE_NAME;

            if (is_readable($cache_location)) {
                self::$file_references = unserialize((string) file_get_contents($cache_location));
                return true;
            }
        }

        return false;
    }

    public static function updateReferenceCache()
    {
        if (self::$cache_dir) {
            $cache_location = self::$cache_dir . '/' . self::REFERENCE_CACHE_NAME;

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

    public static function setCacheDir($cache_dir)
    {
        self::$cache_dir = $cache_dir;
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

    public function getClassName()
    {
        return $this->class_name;
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

    public function getFileName()
    {
        return $this->short_file_name;
    }

    public function getRealFileName()
    {
        return $this->real_file_name;
    }

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

    public function getSource()
    {
        return null;
    }

    /**
     * Get a list of suppressed issues
     * @return array<string>
     */
    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

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
        $file_name = (string)(new \ReflectionClass($class_name))->getFileName();

        if (isset(self::$file_checkers[$file_name])) {
            $file_checker = self::$file_checkers[$file_name];
        }
        else {
            $file_checker = new FileChecker($file_name);
        }

        $file_checker->check(true, false, null, false);

        return ClassLikeChecker::getClassLikeCheckerFromClass($class_name);
    }

    public static function addFileReferenceToClass($source_file, $absolute_class)
    {
        self::$referencing_files[$source_file] = true;
        self::$file_references_to_class[$absolute_class][$source_file] = true;
    }

    public static function addFileInheritanceToClass($source_file, $absolute_class)
    {
        self::$files_inheriting_classes[$absolute_class][$source_file] = true;
    }

    public static function calculateFilesReferencingFile($file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($file);

        foreach ($file_classes as $file_class) {
            if (isset(self::$file_references_to_class[$file_class])) {
                $referenced_files = array_merge($referenced_files, array_keys(self::$file_references_to_class[$file_class]));
            }
        }

        return array_unique($referenced_files);
    }

    public static function calculateFilesInheritingFile($file)
    {
        $referenced_files = [];

        $file_classes = ClassLikeChecker::getClassesForFile($file);

        foreach ($file_classes as $file_class) {
            if (isset(self::$files_inheriting_classes[$file_class])) {
                $referenced_files = array_merge($referenced_files, array_keys(self::$files_inheriting_classes[$file_class]));
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

    public static function canDiffFiles()
    {
        return self::$cache_dir && file_exists(self::$cache_dir . '/' . self::GOOD_RUN_NAME);
    }

    /**
     * @param  string  $file
     * @return boolean
     */
    public static function hasFileChanged($file)
    {
        if (self::$last_good_run === null) {
            self::$last_good_run = filemtime(self::$cache_dir . '/' . self::GOOD_RUN_NAME);
        }

        return filemtime($file) > self::$last_good_run;
    }

    /**
     * @return array<string>
     */
    public static function getDeletedReferencedFiles()
    {
        if (self::$deleted_files === null) {
            self::$deleted_files = array_filter(
                array_keys(self::$file_references),
                function($file_name) {
                    return !file_exists((string)$file_name);
                }
            );
        }

        return self::$deleted_files;
    }

    public static function goodRun()
    {
        if (self::$cache_dir) {
            $run_cache_location = self::$cache_dir . '/' . self::GOOD_RUN_NAME;

            touch($run_cache_location);

            $deleted_files = self::getDeletedReferencedFiles();

            if ($deleted_files) {

                foreach ($deleted_files as $file) {
                    unset(self::$file_references[$file]);
                }

                file_put_contents(self::$cache_dir . '/' . self::REFERENCE_CACHE_NAME, serialize(self::$file_references));
            }
        }
    }

    public static function clearCache()
    {
        self::$file_checkers = [];

        self::$functions_checked = [];
        self::$classes_checked = [];
        self::$files_checked = [];

        ClassLikeChecker::clearCache();
    }
}
