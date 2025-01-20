<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Psalm\Internal\Provider\ParserCacheProvider;

class FakeParserCacheProvider extends ParserCacheProvider
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

    public function deleteOldParserCaches(float $time_before): int
    {
        $this->existing_file_content_hashes = null;
        $this->new_file_content_hashes = [];

        return 0;
    }

    public function saveFileContentHashes(): void
    {
    }
}
