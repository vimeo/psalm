<?php
namespace Psalm\Provider;

use PhpParser;
use Psalm\Checker\ProjectChecker;

class StatementsProvider
{
    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var ?ParserCacheProvider
     */
    public $parser_cache_provider;

    /**
     * @var int
     */
    private $this_modified_time;

    /**
     * @var ?FileStorageCacheProvider
     */
    private $file_storage_cache_provider;

    /**
     * @var array<string, array<string, bool>>
     */
    private $unchanged_members = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private $unchanged_signature_members = [];

    /**
     * @var array<string, array<string, bool>>
     */
    private $changed_members = [];

    /**
     * @var array<string, array<int, array{0: int, 1: int, 2: int, 3: int}>>
     */
    private $diff_map = [];

    /**
     * @var PhpParser\Parser|null
     */
    protected static $parser;

    public function __construct(
        FileProvider $file_provider,
        ParserCacheProvider $parser_cache_provider = null,
        FileStorageCacheProvider $file_storage_cache_provider = null
    ) {
        $this->file_provider = $file_provider;
        $this->parser_cache_provider = $parser_cache_provider;
        $this->this_modified_time = filemtime(__FILE__);
        $this->file_storage_cache_provider = $file_storage_cache_provider;
    }

    /**
     * @param  string  $file_path
     * @param  bool    $debug_output
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public function getStatementsForFile($file_path, $debug_output = false)
    {
        $from_cache = false;

        $version = (string) PHP_PARSER_VERSION . $this->this_modified_time;

        $file_contents = $this->file_provider->getContents($file_path);
        $modified_time = $this->file_provider->getModifiedTime($file_path);

        if (!$this->parser_cache_provider) {
            if ($debug_output) {
                echo 'Parsing ' . $file_path . "\n";
            }

            $stmts = self::parseStatements($file_contents, $file_path);

            return $stmts ?: [];
        }

        $file_content_hash = md5($version . $file_contents);

        $stmts = $this->parser_cache_provider->loadStatementsFromCache(
            $file_path,
            $modified_time,
            $file_content_hash
        );

        if ($stmts === null) {
            if ($debug_output) {
                echo 'Parsing ' . $file_path . "\n";
            }

            $existing_statements = $this->parser_cache_provider->loadExistingStatementsFromCache($file_path);

            $existing_file_contents = $this->parser_cache_provider->loadExistingFileContentsFromCache($file_path);

            // this happens after editing temporary file
            if ($existing_file_contents === $file_contents && $existing_statements) {
                $this->diff_map[$file_path] = [];
                $this->parser_cache_provider->saveStatementsToCache(
                    $file_path,
                    $file_content_hash,
                    $existing_statements,
                    true
                );

                return $existing_statements;
            }

            $file_changes = null;

            $existing_statements_copy = null;

            if ($existing_statements && $existing_file_contents) {
                $file_changes = \Psalm\Diff\FileDiffer::getDiff($existing_file_contents, $file_contents);
                $traverser = new PhpParser\NodeTraverser;
                $traverser->addVisitor(new \Psalm\Visitor\CloningVisitor);
                // performs a deep clone
                /** @var array<int, PhpParser\Node\Stmt> */
                $existing_statements_copy = $traverser->traverse($existing_statements);
            }

            $stmts = self::parseStatements(
                $file_contents,
                $file_path,
                $existing_file_contents,
                $existing_statements_copy,
                $file_changes
            );

            if ($existing_file_contents && $existing_statements) {
                list($unchanged_members, $unchanged_signature_members, $changed_members, $diff_map)
                    = \Psalm\Diff\FileStatementsDiffer::diff(
                        $existing_statements,
                        $stmts,
                        $existing_file_contents,
                        $file_contents
                    );

                $unchanged_members = array_map(
                    /**
                     * @param int $_
                     * @return bool
                     */
                    function ($_) {
                        return true;
                    },
                    array_flip($unchanged_members)
                );

                $unchanged_signature_members = array_map(
                    /**
                     * @param int $_
                     * @return bool
                     */
                    function ($_) {
                        return true;
                    },
                    array_flip($unchanged_signature_members)
                );

                $changed_members = array_map(
                    /**
                     * @param int $_
                     * @return bool
                     */
                    function ($_) {
                        return true;
                    },
                    array_flip($changed_members)
                );

                if (isset($this->unchanged_members[$file_path])) {
                    $this->unchanged_members[$file_path] = array_intersect_key(
                        $this->unchanged_members[$file_path],
                        $unchanged_members
                    );
                } else {
                    $this->unchanged_members[$file_path] = $unchanged_members;
                }

                if (isset($this->unchanged_signature_members[$file_path])) {
                    $this->unchanged_signature_members[$file_path] = array_intersect_key(
                        $this->unchanged_signature_members[$file_path],
                        $unchanged_signature_members
                    );
                } else {
                    $this->unchanged_signature_members[$file_path] = $unchanged_signature_members;
                }

                if (isset($this->changed_members[$file_path])) {
                    $this->changed_members[$file_path] = array_merge(
                        $this->changed_members[$file_path],
                        $changed_members
                    );
                } else {
                    $this->changed_members[$file_path] = $changed_members;
                }

                $this->diff_map[$file_path] = $diff_map;
            }

            if ($this->file_storage_cache_provider) {
                $this->file_storage_cache_provider->removeCacheForFile($file_path);
            }

            $this->parser_cache_provider->cacheFileContents($file_path, $file_contents);
        } else {
            $from_cache = true;
            $this->diff_map[$file_path] = [];
        }

        $this->parser_cache_provider->saveStatementsToCache($file_path, $file_content_hash, $stmts, $from_cache);

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getChangedMembers()
    {
        return $this->changed_members;
    }

    /**
     * @param array<string, array<string, bool>> $more_changed_members
     * @return void
     */
    public function addChangedMembers(array $more_changed_members)
    {
        $this->changed_members = array_merge($more_changed_members, $this->changed_members);
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getUnchangedSignatureMembers()
    {
        return $this->unchanged_signature_members;
    }

    /**
     * @param array<string, array<string, bool>> $more_unchanged_members
     * @return void
     */
    public function addUnchangedSignatureMembers(array $more_unchanged_members)
    {
        $this->unchanged_signature_members = array_merge($more_unchanged_members, $this->unchanged_signature_members);
    }

    /**
     * @param string $file_path
     * @return void
     */
    public function setUnchangedFile($file_path)
    {
        if (!isset($this->diff_map[$file_path])) {
            $this->diff_map[$file_path] = [];
        }
    }

    /**
     * @return array<string, array<int, array{0: int, 1: int, 2: int, 3: int}>>
     */
    public function getDiffMap()
    {
        return $this->diff_map;
    }

    /**
     * @param array<string, array<int, array{0: int, 1: int, 2: int, 3: int}>> $diff_map
     * @return void
     */
    public function addDiffMap(array $diff_map)
    {
        $this->diff_map = array_merge($diff_map, $this->diff_map);
    }

    /**
     * @return void
     */
    public function resetDiffs()
    {
        $this->changed_members = [];
        $this->unchanged_members = [];
        $this->unchanged_signature_members = [];
        $this->diff_map = [];
    }

    /**
     * @param  string  $file_contents
     * @param  bool    $server_mode
     * @param  string   $file_path
     * @param  array<int, \PhpParser\Node\Stmt> $existing_statements
     * @param  array<int, array{0:int, 1:int, 2: int, 3: int, 4: int, 5:string}> $file_changes
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public static function parseStatements(
        $file_contents,
        $file_path = null,
        string $existing_file_contents = null,
        array $existing_statements = null,
        array $file_changes = null
    ) {
        if (!self::$parser) {
            $attributes = [
                'comments', 'startLine', 'startFilePos', 'endFilePos',
            ];

            $lexer = new PhpParser\Lexer([ 'usedAttributes' => $attributes ]);

            self::$parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::PREFER_PHP7, $lexer);
        }

        $used_cached_statements = false;

        $error_handler = new \PhpParser\ErrorHandler\Collecting();

        if ($existing_statements && $file_changes && $existing_file_contents) {
            $clashing_traverser = new \Psalm\Traverser\CustomTraverser;
            $offset_checker = new \Psalm\Visitor\OffsetMapCheckerVisitor(
                self::$parser,
                $error_handler,
                $file_changes,
                $existing_file_contents,
                $file_contents
            );
            $clashing_traverser->addVisitor($offset_checker);
            $clashing_traverser->traverse($existing_statements);

            if (!$offset_checker->mustRescan()) {
                $used_cached_statements = true;
                $stmts = $existing_statements;
            } else {
                /** @var array<int, \PhpParser\Node\Stmt> */
                $stmts = self::$parser->parse($file_contents, $error_handler) ?: [];
            }
        } else {
            /** @var array<int, \PhpParser\Node\Stmt> */
            $stmts = self::$parser->parse($file_contents, $error_handler) ?: [];
        }

        if ($error_handler->hasErrors() && $file_path) {
            $config = \Psalm\Config::getInstance();

            foreach ($error_handler->getErrors() as $error) {
                if ($error->hasColumnInfo()) {
                    \Psalm\IssueBuffer::add(
                        new \Psalm\Issue\ParseError(
                            $error->getMessage(),
                            new \Psalm\CodeLocation\ParseErrorLocation(
                                $error,
                                $file_contents,
                                $file_path,
                                $config->shortenFileName($file_path)
                            )
                        )
                    );
                }
            }
        }

        $resolving_traverser = new PhpParser\NodeTraverser;
        $name_resolver = new \Psalm\Visitor\SimpleNameResolver(
            $error_handler,
            $used_cached_statements ? $file_changes : []
        );
        $resolving_traverser->addVisitor($name_resolver);
        $resolving_traverser->traverse($stmts);

        return $stmts;
    }
}
