<?php
namespace Psalm\Tests\Provider;

use PhpParser;

class ParserInstanceCacheProvider extends \Psalm\Provider\ParserCacheProvider
{
    /**
     * @var array<string, string>
     */
    private $file_contents_cache = [];

    /**
     * @var array<string, array<int, PhpParser\Node\Stmt>>
     */
    private $statements_cache = [];

    /**
     * @var array<string, float>
     */
    private $statements_cache_time = [];

    public function __construct()
    {
    }

    public function loadStatementsFromCache($file_path, $file_modified_time, $file_content_hash)
    {
        if (isset($this->statements_cache[$file_path])
            && $this->statements_cache_time[$file_path] >= $file_modified_time
        ) {
            return $this->statements_cache[$file_path];
        }

        return null;
    }

    /**
     * @param  string   $file_content_hash
     * @param  string   $file_path
     * @param mixed $file_modified_time
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     */
    public function loadExistingStatementsFromCache($file_path)
    {
        if (isset($this->statements_cache[$file_path])) {
            return $this->statements_cache[$file_path];
        }

        return null;
    }

    /**
     * @param  string                           $file_path
     * @param  string                           $file_content_hash
     * @param  array<int, PhpParser\Node\Stmt>  $stmts
     * @param  bool                             $touch_only
     *
     * @return void
     */
    public function saveStatementsToCache($file_path, $file_content_hash, array $stmts, $touch_only)
    {
        $this->statements_cache[$file_path] = $stmts;
        $this->statements_cache_time[$file_path] = microtime(true);
    }

    /**
     * @param  string   $file_path
     *
     * @return string|null
     */
    public function loadExistingFileContentsFromCache($file_path)
    {
        if (isset($this->file_contents_cache[$file_path])) {
            return $this->file_contents_cache[$file_path];
        }

        return null;
    }

    /**
     * @param  string  $file_path
     * @param  string  $file_contents
     *
     * @return void
     */
    public function cacheFileContents($file_path, $file_contents)
    {
        $this->file_contents_cache[$file_path] = $file_contents;
    }
}
