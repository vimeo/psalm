<?php

namespace Psalm\Internal\LanguageServer\Provider;

use PhpParser;
use Psalm\Internal\Provider\ParserCacheProvider as InternalParserCacheProvider;

use function microtime;

/**
 * @internal
 */
final class ParserCacheProvider extends InternalParserCacheProvider
{
    /**
     * @var array<string, string>
     */
    private array $file_contents_cache = [];

    /**
     * @var array<string, string>
     */
    private array $file_content_hash = [];

    /**
     * @var array<string, list<PhpParser\Node\Stmt>>
     */
    private array $statements_cache = [];

    /**
     * @var array<string, float>
     */
    private array $statements_cache_time = [];

    public function __construct()
    {
    }

    public function loadStatementsFromCache(
        string $file_path,
        int $file_modified_time,
        string $file_content_hash
    ): ?array {
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
     */
    public function saveStatementsToCache(
        string $file_path,
        string $file_content_hash,
        array $stmts,
        bool $touch_only
    ): void {
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

    public function deleteOldParserCaches(float $time_before): int
    {
        $this->existing_file_content_hashes = null;
        $this->new_file_content_hashes = [];

        $this->file_contents_cache = [];
        $this->file_content_hash = [];
        $this->statements_cache = [];
        $this->statements_cache_time = [];
        return 0;
    }

    public function saveFileContentHashes(): void
    {
    }
}
