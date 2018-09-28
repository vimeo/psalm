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

    /**
     * @param  string   $file_content_hash
     * @param  string   $file_cache_key
     * @param mixed $file_modified_time
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache($file_modified_time, $file_content_hash, $file_cache_key)
    {
        if (isset($this->statements_cache[$file_cache_key])
            && $this->statements_cache_time[$file_cache_key] >= $file_modified_time
        ) {
            return $this->statements_cache[$file_cache_key];
        }

        return null;
    }

    /**
     * @param  string   $file_content_hash
     * @param  string   $file_cache_key
     * @param mixed $file_modified_time
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     */
    public function loadExistingStatementsFromCache($file_cache_key)
    {
        if (isset($this->statements_cache[$file_cache_key])) {
            return $this->statements_cache[$file_cache_key];
        }

        return null;
    }

    /**
     * @param  string                           $file_cache_key
     * @param  string                           $file_content_hash
     * @param  array<int, PhpParser\Node\Stmt>  $stmts
     * @param  bool                             $touch_only
     *
     * @return void
     */
    public function saveStatementsToCache($file_cache_key, $file_content_hash, array $stmts, $touch_only)
    {
        $this->statements_cache[$file_cache_key] = $stmts;
        $this->statements_cache_time[$file_cache_key] = (float) microtime(true);
    }

    /**
     * @param  string   $file_cache_key
     *
     * @return string|null
     */
    public function loadExistingFileContentsFromCache($file_cache_key)
    {
        if (isset($this->file_contents_cache[$file_cache_key])) {
            return $this->file_contents_cache[$file_cache_key];
        }

        return null;
    }

    /**
     * @param  string  $file_cache_key
     * @param  string  $file_contents
     *
     * @return void
     */
    public function cacheFileContents($file_cache_key, $file_contents)
    {
        $this->file_contents_cache[$file_cache_key] = $file_contents;
    }
}
