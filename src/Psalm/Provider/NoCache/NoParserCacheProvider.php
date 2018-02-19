<?php
namespace Psalm\Provider\NoCache;

use PhpParser;

class NoParserCacheProvider extends \Psalm\Provider\ParserCacheProvider
{
    /**
     * @param  int      $file_modified_time
     * @param  string   $file_content_hash
     * @param  string   $file_cache_key
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache($file_modified_time, $file_content_hash, $file_cache_key)
    {
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
    }
}
