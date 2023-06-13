<?php

namespace Psalm\Internal;

use Psalm\Config;
use Psalm\Internal\Provider\Providers;

use function file_exists;
use function file_put_contents;
use function gzdeflate;
use function gzinflate;
use function igbinary_serialize;
use function igbinary_unserialize;
use function lz4_compress;
use function lz4_uncompress;
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
        if ($cache === '') {
            return null;
        }

        if ($this->config->compressor === 'off') {
            $inflated = $cache;
        } elseif ($this->config->compressor === 'lz4') {
            $inflated = lz4_uncompress($cache);
        } else {
            $inflated = @gzinflate($cache);
        }

        // invalid cache data
        if ($inflated === false) {
            $this->deleteItem($path);

            return null;
        }

        if ($this->config->use_igbinary) {
            /** @var object|false $unserialized */
            $unserialized = @igbinary_unserialize($inflated);
        } else {
            /** @var object|false $unserialized */
            $unserialized = @unserialize($inflated);
        }

        if ($unserialized === false) {
            $this->deleteItem($path);

            return null;
        }

        return $unserialized;
    }

    public function deleteItem(string $path): void
    {
        if (file_exists($path)) {
            @unlink($path);
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

        if ($this->config->compressor === 'deflate') {
            $serialized = gzdeflate($serialized);
        } elseif ($this->config->compressor === 'lz4') {
            $serialized = lz4_compress($serialized, 1);
        }

        file_put_contents($path, $serialized, LOCK_EX);
    }

    public function getCacheDirectory(): ?string
    {
        return $this->config->getCacheDirectory();
    }
}
