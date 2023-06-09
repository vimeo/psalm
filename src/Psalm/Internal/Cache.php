<?php

namespace Psalm\Internal;

use Psalm\Config;
use Psalm\Internal\Provider\Providers;

use function file_exists;
use function file_put_contents;
use function serialize;
use function unlink;
use function unserialize;

use const LOCK_EX;

/**
 * @internal
 */
class Cache
{
    private Config $config;

    public bool $use_igbinary;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->use_igbinary = $config->use_igbinary;
    }

    public function getItem(string $path): ?object
    {
        if (!file_exists($path)) {
            return null;
        }

        $cache = Providers::safeFileGetContents($path);
        if ($this->config->use_igbinary) {
            /** @var object|false $unserialized */
            $unserialized = igbinary_unserialize($cache);
        } else {
            /** @var object|false $unserialized */
            $unserialized = @unserialize($cache);
        }

        return $unserialized !== false ? $unserialized : null;
    }

    public function deleteItem(string $path): void
    {
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function saveItem(string $path, object $item): void
    {
        if ($this->config->use_igbinary) {
            file_put_contents($path, igbinary_serialize($item), LOCK_EX);
        } else {
            file_put_contents($path, serialize($item), LOCK_EX);
        }
    }

    public function getCacheDirectory(): ?string
    {
        return $this->config->getCacheDirectory();
    }
}
