<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use Psalm\Internal\Provider\ParserCacheProvider;

final class FakeParserCacheProvider extends ParserCacheProvider
{
    public function __construct()
    {
    }

    #[Override]
    public function loadStatementsFromCache(string $file_path, int $file_modified_time, string $file_content_hash): ?array
    {
        return null;
    }

    #[Override]
    public function loadExistingStatementsFromCache(string $file_path): ?array
    {
        return null;
    }

    #[Override]
    public function saveStatementsToCache(string $file_path, string $file_content_hash, array $stmts, bool $touch_only): void
    {
    }

    #[Override]
    public function loadExistingFileContentsFromCache(string $file_path): ?string
    {
        return null;
    }

    #[Override]
    public function cacheFileContents(string $file_path, string $file_contents): void
    {
    }

    #[Override]
    public function deleteOldParserCaches(float $time_before): int
    {
        $this->existing_file_content_hashes = null;
        $this->new_file_content_hashes = [];

        return 0;
    }

    #[Override]
    public function saveFileContentHashes(): void
    {
    }
}
