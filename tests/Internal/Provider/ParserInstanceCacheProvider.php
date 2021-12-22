<?php

namespace Psalm\Tests\Internal\Provider;

use PhpParser;
use Psalm\Internal\Provider\ParserCacheProvider;

use function microtime;

class ParserInstanceCacheProvider extends ParserCacheProvider
{
    /**
     * @var array<string, string>
     */
    private $file_contents_cache = [];

    /**
     * @var array<string, string>
     */
    private $file_content_hash = [];

    /**
     * @var array<string, list<PhpParser\Node\Stmt>>
     */
    private $statements_cache = [];

    /**
     * @var array<string, float>
     */
    private $statements_cache_time = [];

    public function __construct()
    {
    }

    public function loadStatementsFromCache(string $file_path, int $file_modified_time, string $file_content_hash): ?array
    {
        if (isset($this->statements_cache[$file_path])
            && $this->statements_cache_time[$file_path] >= $file_modified_time
            && $this->file_content_hash[$file_path] === $file_content_hash
        ) {
            return $this->statements_cache[$file_path];
        }

        return null;
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadExistingStatementsFromCache(string $file_path): ?array
    {
        if (isset($this->statements_cache[$file_path])) {
            return $this->statements_cache[$file_path];
        }

        return null;
    }

    /**
     * @param  list<PhpParser\Node\Stmt>        $stmts
     *
     */
    public function saveStatementsToCache(string $file_path, string $file_content_hash, array $stmts, bool $touch_only): void
    {
        $this->statements_cache[$file_path] = $stmts;
        $this->statements_cache_time[$file_path] = microtime(true);
        $this->file_content_hash[$file_path] = $file_content_hash;
    }

    public function loadExistingFileContentsFromCache(string $file_path): ?string
    {
        if (isset($this->file_contents_cache[$file_path])) {
            return $this->file_contents_cache[$file_path];
        }

        return null;
    }

    public function cacheFileContents(string $file_path, string $file_contents): void
    {
        $this->file_contents_cache[$file_path] = $file_contents;
    }

    public function saveFileContentHashes(): void
    {
    }
}
