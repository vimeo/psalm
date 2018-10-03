<?php
namespace Psalm\Provider;

use PhpParser;

class StatementsProvider
{
    /**
     * @var FileProvider
     */
    private $file_provider;

    /**
     * @var ?ParserCacheProvider
     */
    private $parser_cache_provider;

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

    /**
     * @var PhpParser\NodeTraverser|null
     */
    protected static $node_traverser;

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

            return self::parseStatements($file_contents, $file_path) ?: [];
        }

        $file_content_hash = md5($version . $file_contents);
        $file_cache_key = $this->parser_cache_provider->getParserCacheKey(
            $file_path,
            $this->parser_cache_provider->use_igbinary
        );

        $stmts = $this->parser_cache_provider->loadStatementsFromCache(
            $modified_time,
            $file_content_hash,
            $file_cache_key
        );

        if ($stmts === null) {
            if ($debug_output) {
                echo 'Parsing ' . $file_path . "\n";
            }

            $stmts = self::parseStatements($file_contents, $file_path);

            $existing_file_contents = $this->parser_cache_provider->loadExistingFileContentsFromCache($file_cache_key);

            if ($existing_file_contents) {
                $existing_statements = $this->parser_cache_provider->loadExistingStatementsFromCache($file_cache_key);

                if ($existing_statements) {
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
            }

            if ($this->file_storage_cache_provider) {
                $this->file_storage_cache_provider->removeCacheForFile($file_path);
            }

            $this->parser_cache_provider->cacheFileContents($file_cache_key, $file_contents);
        } else {
            $from_cache = true;
            $this->diff_map[$file_path] = [];
        }

        $this->parser_cache_provider->saveStatementsToCache($file_cache_key, $file_content_hash, $stmts, $from_cache);

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
     * @param  string   $file_contents
     * @param  string   $file_path
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public static function parseStatements($file_contents, $file_path = null)
    {
        if (!self::$parser) {
            $lexer = new PhpParser\Lexer([
                'usedAttributes' => [
                    'comments', 'startLine', 'startFilePos', 'endFilePos',
                ],
            ]);

            self::$parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::PREFER_PHP7, $lexer);
        }

        if (!self::$node_traverser) {
            self::$node_traverser = new PhpParser\NodeTraverser;
            $name_resolver = new \Psalm\Visitor\SimpleNameResolver;
            self::$node_traverser->addVisitor($name_resolver);
        }

        $error_handler = new \PhpParser\ErrorHandler\Collecting();

        /** @var array<int, \PhpParser\Node\Stmt> */
        $stmts = self::$parser->parse($file_contents, $error_handler) ?: [];

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

        /** @var array<int, \PhpParser\Node\Stmt> */
        self::$node_traverser->traverse($stmts);

        return $stmts;
    }
}
