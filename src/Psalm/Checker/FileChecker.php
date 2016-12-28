<?php
namespace Psalm\Checker;

use PhpParser\ParserFactory;
use PhpParser;
use Psalm\Config;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;

class FileChecker extends SourceChecker implements StatementsSource
{
    const PARSER_CACHE_DIRECTORY = 'php-parser';
    const FILE_HASHES = 'file_hashes';
    const REFERENCE_CACHE_NAME = 'references';
    const GOOD_RUN_NAME = 'good_run';

    /**
     * @var string
     */
    protected $file_path;

    /**
     * @var array<string, array<string, string>>
     */
    protected $namespace_aliased_classes = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected $namespace_aliased_classes_flipped = [];

    /**
     * @var array<int, \PhpParser\Node\Expr|\PhpParser\Node\Stmt>
     */
    protected $preloaded_statements = [];

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
     * @var array<string, array<string,bool>>
     */
    protected static $file_references_to_class = [];

    /**
     * A lookup table used for getting all the files referenced by a file
     *
     * @var array<string, array{a:array<int, string>, i:array<int, string>}>
     */
    protected static $file_references = [];

    /**
     * A lookup table used for getting all the files that reference any other file
     *
     * @var array<string,array<string,bool>>
     */
    protected static $referencing_files = [];

    /**
     * @var array<string, array<int,string>>
     */
    protected static $files_inheriting_classes = [];

    /**
     * A list of all files deleted since the last successful run
     *
     * @var array<int, string>|null
     */
    protected static $deleted_files = null;

    /**
     * A list of return types, keyed by file
     *
     * @var array<string, array<int, array<string>>>
     */
    protected static $docblock_return_types = [];

    /**
     * A map of filename hashes to contents hashes
     *
     * @var array<string, string>|null
     */
    protected static $file_content_hashes = null;

    /**
     * @param string $file_name
     * @param array  $preloaded_statements
     */
    public function __construct($file_name, array $preloaded_statements = [])
    {
        $this->file_path = $file_name;
        $this->file_name = Config::getInstance()->shortenFileName($this->file_path);

        self::$file_checkers[$this->file_name] = $this;
        self::$file_checkers[$this->file_path] = $this;

        if ($preloaded_statements) {
            $this->preloaded_statements = $preloaded_statements;
        }
    }

    /**
     * @param   bool            $check_classes
     * @param   bool            $check_functions
     * @param   Context|null    $file_context
     * @param   bool            $cache
     * @param   bool            $update_docblocks
     * @return  array|null
     */
    public function check(
        $check_classes = true,
        $check_functions = true,
        Context $file_context = null,
        $cache = true,
        $update_docblocks = false
    ) {
        if ($cache && isset(self::$functions_checked[$this->file_path])) {
            return null;
        }

        if ($cache && $check_classes && !$check_functions && isset(self::$classes_checked[$this->file_path])) {
            return null;
        }

        if ($cache && !$check_classes && !$check_functions && isset(self::$files_checked[$this->file_path])) {
            return null;
        }

        if (!$file_context) {
            $file_context = new Context($this->file_name);
        }

        $config = Config::getInstance();

        $stmts = $this->getStatements();

        /** @var array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt> */
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

        $classes_to_check = [];
        $interfaces_to_check = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Class_
                || $stmt instanceof PhpParser\Node\Stmt\Interface_
                || $stmt instanceof PhpParser\Node\Stmt\Trait_
                || ($stmt instanceof PhpParser\Node\Stmt\Namespace_ &&
                    $stmt->name instanceof PhpParser\Node\Name)
                || $stmt instanceof PhpParser\Node\Stmt\Function_
            ) {
                if ($leftover_stmts) {
                    $statments_checker->check($leftover_stmts, $file_context);
                    $leftover_stmts = [];
                }

                if ($stmt instanceof PhpParser\Node\Stmt\Class_ && $stmt->name) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name)
                            ?: new ClassChecker($stmt, $this, $stmt->name);

                        $this->declared_classes[$class_checker->getFQCLN()] = true;
                        $classes_to_check[] = $class_checker;
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_ && $stmt->name) {
                    if ($check_classes) {
                        $class_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name)
                            ?: new InterfaceChecker($stmt, $this, $stmt->name);

                        $this->declared_classes[$class_checker->getFQCLN()] = true;
                        $interfaces_to_check[] = $class_checker;
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_ && $stmt->name) {
                    if ($check_classes) {
                        $trait_checker = ClassLikeChecker::getClassLikeCheckerFromClass($stmt->name)
                            ?: new TraitChecker($stmt, $this, $stmt->name);
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_ &&
                    $stmt->name instanceof PhpParser\Node\Name
                ) {
                    $namespace_name = implode('\\', $stmt->name->parts);

                    $namespace_checker = new NamespaceChecker($stmt, $this);

                    $namespace_checker->check(
                        $check_classes,
                        $check_functions,
                        $update_docblocks
                    );

                    $this->namespace_aliased_classes[$namespace_name] = $namespace_checker->getAliasedClasses();
                    $this->namespace_aliased_classes_flipped[$namespace_name] =
                        $namespace_checker->getAliasedClassesFlipped();

                    $this->declared_classes = array_merge($namespace_checker->getDeclaredClasses());
                }
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        foreach ($interfaces_to_check as $interface_checker) {
            $interface_checker->check(false);
        }

        foreach ($classes_to_check as $class_checker) {
            $class_checker->check($check_functions, null, $update_docblocks);
        }

        foreach ($function_checkers as $function_checker) {
            $function_context = new Context($this->file_name, $file_context->self);
            $function_checker->check($function_context, $file_context);

            if (!$config->excludeIssueInFile('InvalidReturnType', $this->file_name)) {
                /** @var string */
                $method_id = $function_checker->getMethodId();

                $return_type = FunctionChecker::getFunctionReturnType(
                    $method_id,
                    $this->file_name
                );

                $return_type_location = FunctionChecker::getFunctionReturnTypeLocation(
                    $method_id,
                    $this->file_name
                );

                $function_checker->checkReturnTypes(
                    false,
                    $return_type,
                    $return_type_location
                );
            }
        }

        if ($leftover_stmts) {
            $statments_checker->check($leftover_stmts, $file_context);
        }

        if ($check_functions) {
            self::$functions_checked[$this->file_path] = true;
        }

        if ($check_classes) {
            self::$classes_checked[$this->file_path] = true;
        }

        self::$files_checked[$this->file_path] = true;

        if ($update_docblocks && isset(self::$docblock_return_types[$this->file_name])) {
            $line_upset = 0;

            $file_lines = explode(PHP_EOL, (string)file_get_contents($this->file_path));

            $file_docblock_updates = self::$docblock_return_types[$this->file_name];

            foreach ($file_docblock_updates as $line_number => $type) {
                self::updateDocblock($file_lines, $line_number, $line_upset, $type[0], $type[1], $type[2]);
            }

            file_put_contents($this->file_path, implode(PHP_EOL, $file_lines));

            echo 'Added/updated ' . count($file_docblock_updates) . ' docblocks in ' . $this->file_name . PHP_EOL;
        }

        return $stmts;
    }

    /**
     * @param  string $class
     * @param  string $namespace
     * @param  string $file_name
     * @return string
     */
    public static function getFQCLNFromNameInFile($class, $namespace, $file_name)
    {
        if (isset(self::$file_checkers[$file_name])) {
            $aliased_classes = self::$file_checkers[$file_name]->getAliasedClasses($namespace);
        } else {
            $file_checker = new FileChecker($file_name);
            $file_checker->check(false, false, new Context($file_name));
            $aliased_classes = $file_checker->getAliasedClasses($namespace);
        }

        return ClassLikeChecker::getFQCLNFromString($class, $namespace, $aliased_classes);
    }

    /**
     * Gets a list of the classes declared in that file
     *
     * @param  string $file_name
     * @return array<int, string>
     */
    public static function getDeclaredClassesInFile($file_name)
    {
        if (isset(self::$file_checkers[$file_name])) {
            $file_checker = self::$file_checkers[$file_name];
        } else {
            $file_checker = new FileChecker($file_name);
            $file_checker->check(false, false, new Context($file_name));
        }

        return array_keys($file_checker->getDeclaredClasses());
    }

    /**
     * @return array<int, \PhpParser\Node\Expr|\PhpParser\Node\Stmt>
     */
    protected function getStatements()
    {
        return $this->preloaded_statements
            ? $this->preloaded_statements
            : self::getStatementsForFile($this->file_path);
    }

    /**
     * @param  string $file_path
     * @return array<int, \PhpParser\Node\Expr|\PhpParser\Node\Stmt>
     */
    public static function getStatementsForFile($file_path)
    {
        $stmts = [];

        $project_checker = ProjectChecker::getInstance();
        $root_cache_directory = Config::getInstance()->getCacheDirectory();
        $parser_cache_directory = $root_cache_directory
            ? $root_cache_directory . '/' . self::PARSER_CACHE_DIRECTORY
            : null;
        $from_cache = false;

        $cache_location = null;
        $name_cache_key = null;

        $version = 'parsercache4';

        $file_contents = $project_checker->getFileContents($file_path);
        $file_content_hash = md5($version . $file_contents);
        $name_cache_key = self::getParserCacheKey($file_path);

        if (self::$file_content_hashes === null) {
            /** @var array<string, string> */
            self::$file_content_hashes = $root_cache_directory &&
                is_readable($root_cache_directory . '/' . self::FILE_HASHES)
                    ? unserialize((string)file_get_contents($root_cache_directory . '/' . self::FILE_HASHES))
                    : [];
        }

        if ($parser_cache_directory) {
            $cache_location = $parser_cache_directory . '/' . $name_cache_key;

            if (isset(self::$file_content_hashes[$name_cache_key]) &&
                $file_content_hash === self::$file_content_hashes[$name_cache_key] &&
                is_readable($cache_location) &&
                filemtime($cache_location) > filemtime($file_path)
            ) {
                /** @var array<int, \PhpParser\Node\Expr|\PhpParser\Node\Stmt> */
                $stmts = unserialize((string)file_get_contents($cache_location));
                $from_cache = true;
            }
        }

        if (!$stmts) {
            $lexer = new PhpParser\Lexer([
                'usedAttributes' => [
                    'comments', 'startLine', 'startFilePos', 'endFilePos'
                ]
            ]);

            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

            /** @var array<int, \PhpParser\Node\Expr|\PhpParser\Node\Stmt> */
            $stmts = $parser->parse($file_contents);
        }

        if ($parser_cache_directory && $cache_location) {
            if ($from_cache) {
                touch($cache_location);
            } else {
                if (!is_dir($parser_cache_directory)) {
                    mkdir($parser_cache_directory, 0777, true);
                }

                file_put_contents($cache_location, serialize($stmts));

                self::$file_content_hashes[$name_cache_key] = $file_content_hash;

                file_put_contents(
                    $root_cache_directory . '/' . self::FILE_HASHES,
                    serialize(self::$file_content_hashes)
                );
            }
        }

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    /**
     * @return bool
     * @psalm-suppress MixedAssignment
     */
    public static function loadReferenceCache()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_location = $cache_directory . '/' . self::REFERENCE_CACHE_NAME;

            if (is_readable($cache_location)) {
                $reference_cache = unserialize((string) file_get_contents($cache_location));

                if (!is_array($reference_cache)) {
                    throw new \UnexpectedValueException('The reference cache must be an array');
                }

                self::$file_references = $reference_cache;
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
     * @return array<string, string>
     */
    public function getAliasedClasses($namespace_name = null)
    {
        if ($namespace_name && isset($this->namespace_aliased_classes[$namespace_name])) {
            return $this->namespace_aliased_classes[$namespace_name];
        }

        return $this->aliased_classes;
    }

    /**
     * @param  string|null $namespace_name
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped($namespace_name = null)
    {
        if ($namespace_name && isset($this->namespace_aliased_classes_flipped[$namespace_name])) {
            return $this->namespace_aliased_classes_flipped[$namespace_name];
        }

        return $this->aliased_classes_flipped;
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
                $this->visitUse($stmt);
            }

            if ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            }
        }
    }

    /**
     * @param string $source_file
     * @param string $fq_class_name
     * @return void
     */
    public static function addFileReferenceToClass($source_file, $fq_class_name)
    {
        self::$referencing_files[$source_file] = true;
        self::$file_references_to_class[$fq_class_name][$source_file] = true;
    }

    /**
     * @param string $source_file
     * @param string $fq_class_name
     * @return void
     */
    public static function addFileInheritanceToClass($source_file, $fq_class_name)
    {
        self::$files_inheriting_classes[$fq_class_name][$source_file] = true;
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
                /**
                 * @param  string $file_name
                 * @return bool
                 */
                function ($file_name) {
                    return !file_exists($file_name);
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
                /** @var array<string> */
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
        IssueBuffer::clearCache();
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
                /** @var array<string> */
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

    /**
     * Adds a docblock to the given file
     *
     * @param   string      $file_name
     * @param   int         $line_number
     * @param   string      $docblock
     * @param   string      $new_type
     * @param   string      $phpdoc_type
     * @return  void
     */
    public static function addDocblockReturnType($file_name, $line_number, $docblock, $new_type, $phpdoc_type)
    {
        $new_type = str_replace(['<mixed, mixed>', '<empty, empty>'], '', $new_type);

        self::$docblock_return_types[$file_name][$line_number] = [$docblock, $new_type, $phpdoc_type];
    }

    /**
     * @param  array<int, string>   $file_lines
     * @param  int                  $line_number
     * @param  int                  $line_upset
     * @param  string               $existing_docblock
     * @param  string               $type
     * @param  string               $phpdoc_type
     * @return void
     */
    public static function updateDocblock(array &$file_lines, $line_number, &$line_upset, $existing_docblock, $type, $phpdoc_type)
    {
        $line_number += $line_upset;
        $function_line = $file_lines[$line_number - 1];
        $left_padding = str_replace(ltrim($function_line), '', $function_line);

        $line_before = $file_lines[$line_number - 2];

        $parsed_docblock = [];
        $existing_line_count = $existing_docblock ? substr_count($existing_docblock, PHP_EOL) + 1 : 0;

        if ($existing_docblock) {
            $parsed_docblock = CommentChecker::parseDocComment($existing_docblock);
        }
        else {
            $parsed_docblock['description'] = '';
        }

        $parsed_docblock['specials']['return'] = [$phpdoc_type];

        if ($type !== $phpdoc_type) {
            $parsed_docblock['specials']['psalm-return'] = [$type];
        }

        $new_docblock_lines = CommentChecker::renderDocComment($parsed_docblock, $left_padding);

        $line_upset += count($new_docblock_lines) - $existing_line_count;

        array_splice($file_lines, $line_number - $existing_line_count - 1, $existing_line_count, $new_docblock_lines);
    }
}
