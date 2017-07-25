<?php
namespace Psalm\Tests\Provider;

use PhpParser;
use Psalm\Config;

class FakeCacheProvider extends \Psalm\Provider\CacheProvider
{
    /**
     * @param  string   $file_path
     * @param  string   $file_content_hash
     * @param  string   $file_cache_key
     * @param mixed $file_modified_time
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache($file_path, $file_modified_time, $file_content_hash, $file_cache_key)
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
        return;
    }
}
