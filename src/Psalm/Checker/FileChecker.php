<?php
namespace Psalm\Checker;

use PhpParser\ParserFactory;
use PhpParser;
use Psalm\Config;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Issue\DuplicateClass;
use Psalm\StatementsSource;
use Psalm\Storage\FileStorage;
use Psalm\Type;

class FileChecker extends SourceChecker implements StatementsSource
{
    use CanAlias;

    const PARSER_CACHE_DIRECTORY = 'php-parser';
    const FILE_HASHES = 'file_hashes';
    const REFERENCE_CACHE_NAME = 'references';
    const GOOD_RUN_NAME = 'good_run';

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var string
     */
    protected $file_path;

    /**
     * @var string|null
     */
    protected $actual_file_name;

    /**
     * @var string|null
     */
    protected $actual_file_path;

    /**
     * @var array<string, string>
     */
    protected $suppressed_issues = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected $namespace_aliased_classes = [];

    /**
     * @var array<string, array<string, string>>
     */
    protected $namespace_aliased_classes_flipped = [];

    /**
     * @var array<int, \PhpParser\Node\Stmt>
     */
    protected $preloaded_statements = [];

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
     * A list of data useful to analyse files
     *
     * @var array<string, FileStorage>
     */
    public static $storage = [];

    /**
     * @var array<string, ClassLikeChecker>
     */
    protected $interface_checkers_to_visit = [];

    /**
     * @var array<string, ClassLikeChecker>
     */
    protected $class_checkers_to_visit = [];

    /**
     * @var array<int, ClassLikeChecker>
     */
    protected $class_checkers_to_analyze = [];

    /**
     * @var array<string, FunctionChecker>
     */
    protected $function_checkers = [];

    /**
     * @var array<int, NamespaceChecker>
     */
    protected $namespace_checkers = [];

    /**
     * @var Context
     */
    public $context;

    /**
     * @var ProjectChecker
     */
    public $project_checker;

    /**
     * @var bool
     */
    protected $will_analyze;

    /**
     * @param string                                $file_path
     * @param ProjectChecker                        $project_checker
     * @param array<int, PhpParser\Node\Stmt>|null  $preloaded_statements
     * @param bool                                  $will_analyze
     */
    public function __construct(
        $file_path,
        ProjectChecker $project_checker,
        array $preloaded_statements = null,
        $will_analyze = true
    ) {
        $this->file_path = $file_path;
        $this->file_name = Config::getInstance()->shortenFileName($this->file_path);
        $this->project_checker = $project_checker;
        $this->will_analyze = $will_analyze;

        if (!isset(self::$storage[$file_path])) {
            self::$storage[$file_path] = new FileStorage();
        }

        if ($preloaded_statements) {
            $this->preloaded_statements = $preloaded_statements;
        }

        $this->context = new Context();
        $this->context->count_references = $project_checker->count_references;
    }

    /**
     * @param   Context|null    $file_context
     * @return  void
     */
    public function visit(Context $file_context = null)
    {
        $this->context = $file_context ?: $this->context;

        $config = Config::getInstance();

        $stmts = $this->getStatements();

        /** @var array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt> */
        $leftover_stmts = [];

        /** @var array<int, PhpParser\Node\Stmt\Const_> */
        $leftover_const_stmts = [];

        $statements_checker = new StatementsChecker($this);

        $predefined_classlikes = $config->getPredefinedClassLikes();

        $function_stmts = [];

        foreach ($stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassLike && $stmt->name) {
                if (isset($predefined_classlikes[strtolower($stmt->name)])) {
                    if (IssueBuffer::accepts(
                        new DuplicateClass(
                            'Class ' . $stmt->name . ' has already been defined internally',
                            new \Psalm\CodeLocation($this, $stmt, true)
                        )
                    )) {
                        // fall through
                    }

                    continue;
                }

                if ($stmt instanceof PhpParser\Node\Stmt\Class_) {
                    $class_checker = new ClassChecker($stmt, $this, $stmt->name);

                    $fq_class_name = $class_checker->getFQCLN();

                    $this->class_checkers_to_visit[$fq_class_name] = $class_checker;
                    if ($this->will_analyze) {
                        $this->class_checkers_to_analyze[] = $class_checker;
                    }
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Interface_) {
                    $class_checker = new InterfaceChecker($stmt, $this, $stmt->name);

                    $fq_class_name = $class_checker->getFQCLN();

                    $this->interface_checkers_to_visit[$fq_class_name] = $class_checker;
                } elseif ($stmt instanceof PhpParser\Node\Stmt\Trait_) {
                    new TraitChecker($stmt, $this, $stmt->name);
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Namespace_) {
                $namespace_name = $stmt->name ? implode('\\', $stmt->name->parts) : '';

                $namespace_checker = new NamespaceChecker($stmt, $this);
                $namespace_checker->visit();

                $this->namespace_aliased_classes[$namespace_name] = $namespace_checker->getAliasedClasses();
                $this->namespace_aliased_classes_flipped[$namespace_name] =
                    $namespace_checker->getAliasedClassesFlipped();
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Function_) {
                $function_stmts[] = $stmt;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Use_) {
                $this->visitUse($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\GroupUse) {
                $this->visitGroupUse($stmt);
            } else {
                $leftover_stmts[] = $stmt;
            }
        }

        $function_checkers = [];

        // hoist functions to the top
        foreach ($function_stmts as $stmt) {
            $function_checkers[$stmt->name] = new FunctionChecker($stmt, $this);
            $function_id = $function_checkers[$stmt->name]->getMethodId();
            $this->function_checkers[$function_id] = $function_checkers[$stmt->name];
        }

        // if there are any leftover statements, evaluate them,
        // in turn causing the classes/interfaces be evaluated
        if ($leftover_stmts) {
            $statements_checker->analyze($leftover_stmts, $this->context);
        }

        // check any leftover interfaces not already evaluated
        foreach ($this->interface_checkers_to_visit as $interface_checker) {
            $interface_checker->visit();
        }

        // check any leftover classes not already evaluated
        foreach ($this->class_checkers_to_visit as $class_checker) {
            $class_checker->visit();
        }

        $this->class_checkers_to_visit = [];
        $this->interface_checkers_to_visit = [];

        $this->function_checkers = $function_checkers;

        self::$files_checked[$this->file_path] = true;
    }

    /**
     * @param  boolean $update_docblocks
     * @param  boolean $preserve_checkers
     * @return void
     */
    public function analyze($update_docblocks = false, $preserve_checkers = false)
    {
        $config = Config::getInstance();

        foreach ($this->class_checkers_to_analyze as $class_checker) {
            $class_checker->analyze(null, $this->context, $update_docblocks);
        }

        foreach ($this->function_checkers as $function_checker) {
            $function_context = new Context($this->context->self);
            $function_context->count_references = $this->project_checker->count_references;
            $function_checker->analyze($function_context, $this->context);

            if (!$config->excludeIssueInFile('InvalidReturnType', $this->file_path)) {
                /** @var string */
                $method_id = $function_checker->getMethodId();

                $function_storage = FunctionChecker::getStorage($method_id, $this->file_path);

                if (!$function_storage->has_template_return_type) {
                    $return_type = $function_storage->return_type;

                    $return_type_location = $function_storage->return_type_location;

                    $function_checker->verifyReturnType(
                        false,
                        $return_type,
                        null,
                        $return_type_location
                    );
                }
            }
        }

        if (!$preserve_checkers) {
            $this->class_checkers_to_analyze = [];
            $this->function_checkers = [];
        }

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
    }

    /**
     * @param string       $fq_class_name
     * @param ClassChecker $class_checker
     * @return  void
     */
    public function addNamespacedClassChecker($fq_class_name, ClassChecker $class_checker)
    {
        $this->class_checkers_to_visit[$fq_class_name] = $class_checker;
        if ($this->will_analyze) {
            $this->class_checkers_to_analyze[] = $class_checker;
        }
    }

    /**
     * @param string            $fq_class_name
     * @param InterfaceChecker  $interface_checker
     * @return  void
     */
    public function addNamespacedInterfaceChecker($fq_class_name, InterfaceChecker $interface_checker)
    {
        $this->interface_checkers_to_visit[$fq_class_name] = $interface_checker;
    }

    /**
     * @param  string   $method_id
     * @param  Context  $this_context
     * @return void
     */
    public function getMethodMutations($method_id, Context &$this_context)
    {
        list($fq_class_name, $method_name) = explode('::', $method_id);
        $call_context = new Context((string) $this_context->vars_in_scope['$this']);
        $call_context->collect_mutations = true;

        foreach ($this_context->vars_possibly_in_scope as $var => $type) {
            if (strpos($var, '$this->') === 0) {
                $call_context->vars_possibly_in_scope[$var] = true;
            }
        }

        foreach ($this_context->vars_in_scope as $var => $type) {
            if (strpos($var, '$this->') === 0) {
                $call_context->vars_in_scope[$var] = $type;
            }
        }

        $call_context->vars_in_scope['$this'] = $this_context->vars_in_scope['$this'];

        $checked = false;

        foreach ($this->class_checkers_to_analyze as $class_checker) {
            if (strtolower($class_checker->getFQCLN()) === strtolower($fq_class_name)) {
                $class_checker->getMethodMutations($method_name, $call_context);
                $checked = true;
                break;
            }
        }

        if (!$checked) {
            throw new \UnexpectedValueException('Method ' . $method_id . ' could not be checked');
        }

        foreach ($call_context->vars_possibly_in_scope as $var => $_) {
            $this_context->vars_possibly_in_scope[$var] = true;
        }

        foreach ($call_context->vars_in_scope as $var => $type) {
            $this_context->vars_in_scope[$var] = $type;
        }
    }

    /**
     * @param  Context|null $file_context
     * @param  boolean      $update_docblocks
     * @return void
     */
    public function visitAndAnalyzeMethods(Context $file_context = null, $update_docblocks = false)
    {
        $this->project_checker->registerVisitedFile($this->file_path);
        $this->visit($file_context);
        $this->analyze($update_docblocks);
    }

    /**
     * Used when checking single files with multiple classlike declarations
     *
     * @param  string $fq_class_name
     * @return bool
     */
    public function containsUnEvaluatedClassLike($fq_class_name)
    {
        return isset($this->interface_checkers_to_visit[$fq_class_name]) ||
            isset($this->class_checkers_to_visit[$fq_class_name]);
    }

    /**
     * When evaluating a file, we wait until a class is actually used to evaluate its contents
     *
     * @param  string $fq_class_name
     * @return null|false
     */
    public function evaluateClassLike($fq_class_name)
    {
        if (isset($this->interface_checkers_to_visit[$fq_class_name])) {
            $interface_checker = $this->interface_checkers_to_visit[$fq_class_name];

            unset($this->interface_checkers_to_visit[$fq_class_name]);

            if ($interface_checker->visit() === false) {
                return false;
            }

            return;
        }

        if (isset($this->class_checkers_to_visit[$fq_class_name])) {
            $class_checker = $this->class_checkers_to_visit[$fq_class_name];

            unset($this->class_checkers_to_visit[$fq_class_name]);

            if ($class_checker->visit(null, $this->context) === false) {
                return false;
            }

            return;
        }

        $this->project_checker->visitFileForClassLike($fq_class_name);
    }

    /**
     * @return array<int, \PhpParser\Node\Stmt>
     */
    protected function getStatements()
    {
        return $this->preloaded_statements
            ? $this->preloaded_statements
            : self::getStatementsForFile($this->file_path, $this->project_checker->debug_output);
    }

    /**
     * @param  string $file_path
     * @return bool
     */
    public function fileExists($file_path)
    {
        return file_exists($file_path) || isset($this->project_checker->fake_files[$file_path]);
    }

    /**
     * @param  string   $file_path
     * @param  bool     $debug_output
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public static function getStatementsForFile($file_path, $debug_output = false)
    {
        $stmts = [];

        $project_checker = ProjectChecker::getInstance();
        $root_cache_directory = Config::getInstance()->getCacheDirectory();
        $parser_cache_directory = $root_cache_directory
            ? $root_cache_directory . DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY
            : null;
        $from_cache = false;

        $cache_location = null;
        $name_cache_key = null;

        $version = 'parsercache4';

        $file_contents = $project_checker->getFileContents($file_path);
        $file_content_hash = md5($version . $file_contents);
        $name_cache_key = self::getParserCacheKey($file_path);

        $config = Config::getInstance();

        if (self::$file_content_hashes === null || !$config->cache_file_hashes_during_run) {
            /** @var array<string, string> */
            self::$file_content_hashes = $root_cache_directory &&
                is_readable($root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES)
                    ? unserialize((string)file_get_contents($root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES))
                    : [];
        }

        if ($parser_cache_directory) {
            $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $name_cache_key;

            if (isset(self::$file_content_hashes[$name_cache_key]) &&
                $file_content_hash === self::$file_content_hashes[$name_cache_key] &&
                is_readable($cache_location) &&
                filemtime($cache_location) > filemtime($file_path)
            ) {
                /** @var array<int, \PhpParser\Node\Stmt> */
                $stmts = unserialize((string)file_get_contents($cache_location));
                $from_cache = true;
            }
        }

        if (!$stmts) {
            if ($debug_output) {
                echo 'Parsing ' . $file_path . PHP_EOL;
            }

            $lexer = new PhpParser\Lexer([
                'usedAttributes' => [
                    'comments', 'startLine', 'startFilePos', 'endFilePos'
                ]
            ]);

            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

            /** @var array<int, \PhpParser\Node\Stmt> */
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
                    $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES,
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
     * @psalm-suppress InvalidPropertyAssignment
     */
    public static function loadReferenceCache()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

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
            $cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME;

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

        return $cache_directory && file_exists($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME);
    }

    /**
     * @return int
     */
    public static function getLastGoodRun()
    {
        if (self::$last_good_run === null) {
            $cache_directory = Config::getInstance()->getCacheDirectory();

            self::$last_good_run = filemtime($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME) ?: 0;
        }

        return self::$last_good_run;
    }

    /**
     * @param  string  $file_path
     * @return boolean
     */
    public static function hasFileChanged($file_path)
    {
        return filemtime($file_path) > self::getLastGoodRun();
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
            $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

            touch($run_cache_location, $start_time);

            $deleted_files = self::getDeletedReferencedFiles();

            if ($deleted_files) {
                foreach ($deleted_files as $file) {
                    unset(self::$file_references[$file]);
                }

                file_put_contents(
                    $cache_directory . DIRECTORY_SEPARATOR . self::REFERENCE_CACHE_NAME,
                    serialize(self::$file_references)
                );
            }

            $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

            if (is_dir($cache_directory)) {
                /** @var array<string> */
                $directory_files = scandir($cache_directory);

                foreach ($directory_files as $directory_file) {
                    $full_path = $cache_directory . DIRECTORY_SEPARATOR . $directory_file;

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
        self::$files_checked = [];

        self::$storage = [];

        ClassLikeChecker::clearCache();
        FunctionChecker::clearCache();
        StatementsChecker::clearCache();
        IssueBuffer::clearCache();
        FunctionLikeChecker::clearCache();
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
            $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

            if (is_dir($cache_directory)) {
                /** @var array<string> */
                $directory_files = scandir($cache_directory);

                foreach ($directory_files as $directory_file) {
                    $full_path = $cache_directory . DIRECTORY_SEPARATOR . $directory_file;

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
            $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

            if (is_dir($cache_directory)) {
                foreach ($file_names as $file_name) {
                    $hash_file_name = $cache_directory . DIRECTORY_SEPARATOR . self::getParserCacheKey($file_name);

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
    public static function updateDocblock(
        array &$file_lines,
        $line_number,
        &$line_upset,
        $existing_docblock,
        $type,
        $phpdoc_type
    ) {
        $line_number += $line_upset;
        $function_line = $file_lines[$line_number - 1];
        $left_padding = str_replace(ltrim($function_line), '', $function_line);

        $parsed_docblock = [];
        $existing_line_count = $existing_docblock ? substr_count($existing_docblock, PHP_EOL) + 1 : 0;

        if ($existing_docblock) {
            $parsed_docblock = CommentChecker::parseDocComment($existing_docblock);
        } else {
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

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->file_path;
    }

    /**
     * @param string $file_name
     * @param string $file_path
     * @return void
     */
    public function setFileName($file_name, $file_path)
    {
        $this->actual_file_name = $this->file_name;
        $this->actual_file_path = $this->file_path;

        $this->file_name = $file_name;
        $this->file_path = $file_path;
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->actual_file_name ?: $this->file_name;
    }

    /**
     * @return string
     */
    public function getCheckedFilePath()
    {
        return $this->actual_file_path ?: $this->file_path;
    }

    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

    public function getFQCLN()
    {
        return null;
    }

    public function getClassName()
    {
        return null;
    }

    public function isStatic()
    {
        return false;
    }
}
