<?php

namespace Psalm\Internal;

use Psalm\Config;
use Psalm\Internal\Provider\Providers;

use function file_exists;
use function file_put_contents;
use function gzdeflate;
use function gzinflate;
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

    /**
     * @return array|object|string|null
     */
    public function getItem(string $path)
    {
        if (!file_exists($path)) {
            return null;
        }

        $cache = Providers::safeFileGetContents($path);
        if ($this->config->use_gzip) {
            $inflated = @gzinflate($cache);
            if ($inflated !== false) {
                $cache = $inflated;
            }
        }

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

    /**
     * @param array|object|string $item
     */
    public function saveItem(string $path, $item): void
    {
        if ($this->config->use_igbinary) {
            $serialized = igbinary_serialize($item);
        } else {
            $serialized = serialize($item);
        }

        if ($this->config->use_gzip) {
            $serialized = gzdeflate($serialized);
        }

        file_put_contents($path, $serialized, LOCK_EX);
    }

    public function getCacheDirectory(): ?string
    {
        return $this->config->getCacheDirectory();
    }
}
