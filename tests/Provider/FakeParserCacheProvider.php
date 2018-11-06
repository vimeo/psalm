<?php
namespace Psalm\Tests\Provider;

use PhpParser;

class FakeParserCacheProvider extends \Psalm\Internal\Provider\ParserCacheProvider
{
    public function __construct()
    {
    }

    public function loadStatementsFromCache($file_path, $file_modified_time, $file_content_hash)
    {
        return null;
    }

    public function loadExistingStatementsFromCache($file_cache_key)
    {
        return null;
    }

    public function saveStatementsToCache($file_cache_key, $file_content_hash, array $stmts, $touch_only)
    {
    }

    public function loadExistingFileContentsFromCache($file_cache_key)
    {
        return null;
    }

    public function cacheFileContents($file_cache_key, $file_contents)
    {
    }
}
