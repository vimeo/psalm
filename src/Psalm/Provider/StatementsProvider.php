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
     * @var ParserCacheProvider
     */
    private $cache_provider;

    /**
     * @var int
     */
    private $this_modified_time;

    /**
     * @var FileStorageCacheProvider
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
     * @var PhpParser\Parser|null
     */
    protected static $parser;

    /**
     * @var PhpParser\NodeTraverser|null
     */
    protected static $node_traverser;

    public function __construct(
        FileProvider $file_provider,
        ParserCacheProvider $cache_provider,
        FileStorageCacheProvider $file_storage_cache_provider
    ) {
        $this->file_provider = $file_provider;
        $this->cache_provider = $cache_provider;
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

        $file_content_hash = md5($version . $file_contents);
        $file_cache_key = $this->cache_provider->getParserCacheKey($file_path, $this->cache_provider->use_igbinary);

        $stmts = $this->cache_provider->loadStatementsFromCache(
            $modified_time,
            $file_content_hash,
            $file_cache_key
        );

        if ($stmts === null) {
            if ($debug_output) {
                echo 'Parsing ' . $file_path . "\n";
            }

            $stmts = self::parseStatements($file_contents);

            $existing_file_contents = $this->cache_provider->loadExistingFileContentsFromCache($file_cache_key);

            if ($existing_file_contents) {
                $existing_statements = $this->cache_provider->loadExistingStatementsFromCache($file_cache_key);

                if ($existing_statements) {
                    list($unchanged_members, $unchanged_signature_members) = \Psalm\Diff\FileStatementsDiffer::diff(
                        $existing_statements,
                        $stmts,
                        $existing_file_contents,
                        $file_contents
                    );

                    $unchanged_members = array_map(
                        function (int $_) : bool {
                            return true;
                        },
                        array_flip($unchanged_members)
                    );

                    $unchanged_signature_members = array_map(
                        function (int $_) : bool {
                            return true;
                        },
                        array_flip($unchanged_signature_members)
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
                }
            }

            $this->file_storage_cache_provider->removeCacheForFile($file_path);
            $this->cache_provider->cacheFileContents($file_cache_key, $file_contents);
        } else {
            $from_cache = true;
        }

        $this->cache_provider->saveStatementsToCache($file_cache_key, $file_content_hash, $stmts, $from_cache);

        if (!$stmts) {
            return [];
        }

        return $stmts;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getUnchangedMembers()
    {
        return $this->unchanged_members;
    }

    /**
     * @return array<string, array<string, bool>>
     */
    public function getUnchangedSignatureMembers()
    {
        return $this->unchanged_signature_members;
    }

    /**
     * @param  string   $file_contents
     *
     * @return array<int, \PhpParser\Node\Stmt>
     */
    public static function parseStatements($file_contents)
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

        try {
            /** @var array<int, \PhpParser\Node\Stmt> */
            $stmts = self::$parser->parse($file_contents);
        } catch (PhpParser\Error $e) {
            throw $e;
        }

        /** @var array<int, \PhpParser\Node\Stmt> */
        $stmts = self::$node_traverser->traverse($stmts);

        return $stmts;
    }
}
