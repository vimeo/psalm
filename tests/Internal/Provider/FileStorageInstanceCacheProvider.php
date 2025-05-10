<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal\Provider;

use Override;
use Psalm\Internal\Provider\FileStorageCacheProvider;
use Psalm\Storage\FileStorage;

use function strtolower;

final class FileStorageInstanceCacheProvider extends FileStorageCacheProvider
{
    /** @var array<lowercase-string, FileStorage> */
    private array $cache = [];

    public function __construct()
    {
    }

    #[Override]
    public function writeToCache(FileStorage $storage, string $file_contents): void
    {
        $this->cache[strtolower($storage->file_path)] = $storage;
    }

    #[Override]
    public function getLatestFromCache(string $file_path, string $file_contents): ?FileStorage
    {
        return $this->cache[strtolower($file_path)] ?? null;
    }
}
