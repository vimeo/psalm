<?php
namespace Psalm\Tests\Internal\Provider;

class FakeParserCacheProvider extends \Psalm\Internal\Provider\ParserCacheProvider
{
    public function __construct()
    {
    }

    public function loadStatementsFromCache(string $file_path, int $file_modified_time, string $file_content_hash): ?array
    {
        return null;
    }

    public function loadExistingStatementsFromCache(string $file_path): ?array
    {
        return null;
    }

    public function saveStatementsToCache(string $file_path, string $file_content_hash, array $stmts, bool $touch_only): void
    {
    }

    public function loadExistingFileContentsFromCache(string $file_path): ?string
    {
        return null;
    }

    public function cacheFileContents(string $file_path, string $file_contents): void
    {
    }

    public function saveFileContentHashes(): void
    {
    }
}
