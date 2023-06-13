<?php

namespace Psalm\Internal;

use Psalm\Config;
use Psalm\Internal\Provider\Providers;
use RuntimeException;

use function file_exists;
use function gzdeflate;
use function gzinflate;
use function igbinary_serialize;
use function igbinary_unserialize;
use function lz4_compress;
use function lz4_uncompress;
use function serialize;
use function unserialize;

/**
 * @internal
 */
class Cache
{
    private Config $config;

    private int $errors = 0;

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

        // if 10 previous items were invalid, abort since the cache is invalid and inform the user
        // we don't report it to the user immediately, since it can happen that a few files get corrupted somehow
        // however the impact on performance is minimal, therefore we ignore it
        if ($this->errors > 10) {
            throw new RuntimeException(
                'The cache data is corrupted. Please delete the cache directory and run Psalm again',
            );
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
            $this->errors++;
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
            $this->errors++;
            $this->deleteItem($path);

            return null;
        }

        return $unserialized;
    }

    public function deleteItem(string $path): void
    {
        Providers::safeUnlink($path);
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
            $compressed = gzdeflate($serialized);
        } elseif ($this->config->compressor === 'lz4') {
            $compressed = lz4_compress($serialized, 1);
        } else {
            $compressed = $serialized;
        }

        if ($compressed === false) {
            throw new RuntimeException(
                'Failed to compress cache data',
            );
        }

        Providers::safeFilePutContents($path, $compressed);
    }

    public function getCacheDirectory(): ?string
    {
        return $this->config->getCacheDirectory();
    }
}
