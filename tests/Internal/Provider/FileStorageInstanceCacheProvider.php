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

    /**
     * @param lowercase-string $file_path
     */
    #[Override]
    protected function storeInCache(string $file_path, FileStorage $storage): void
    {
        $this->cache[$file_path] = $storage;
    }

    #[Override]
    public function removeCacheForFile(string $file_path): void
    {
        unset($this->cache[strtolower($file_path)]);
    }

    /**
     * @param lowercase-string $file_path
     */
    #[Override]
    protected function loadFromCache(string $file_path): ?FileStorage
    {
        return $this->cache[$file_path] ?? null;
    }
}
