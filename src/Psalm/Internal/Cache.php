<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\Serializer;
use Closure;
use Psalm\Config;
use Psalm\Internal\Provider\Providers;
use RuntimeException;

use function file_exists;
use function file_put_contents;
use function filemtime;
use function gzdeflate;
use function gzinflate;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_dir;
use function is_readable;
use function is_writable;
use function lz4_compress;
use function lz4_uncompress;
use function mkdir;
use function serialize;
use function unlink;
use function unserialize;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

/**
 * @internal
 */
final class Cache
{
    private readonly string $dir;
    private readonly Serializer $serializer;

    public function __construct(Config $config, string $subdir, mixed $dependencies)
    {
        $this->serializer = $config->getCacheSerializer();

        $dir = $config->getCacheDirectory().DIRECTORY_SEPARATOR.$subdir;

        $this->dir = $dir.DIRECTORY_SEPARATOR;
        try {
            if (mkdir($this->dir, 0777, true) === false) {
                // any other error than directory already exists/permissions issue
                throw new RuntimeException(
                    'Failed to create ' . $this->dir . ' cache directory for unknown reasons',
                );
            }
        } catch (RuntimeException $e) {
            // Race condition (#4483)
            if (!is_dir($this->dir)) {
                // rethrow the error with default message
                // it contains the reason why creation failed
                throw $e;
            }
        }

        $dependencies = $this->serializer->serialize($dependencies);

        $idx = fopen($this->dir.'idx', 'r');
        flock($idx, LOCK_EX);
        if (stream_get_contents($idx) !== )
    }

    public function getItem(string $key, ?int $mtime_at_least = null, ?string $hash = null): array|object|string|null
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . $key;
        if (!file_exists($path) || !is_readable($path)) {
            return null;
        }
        if ($mtime_at_least !== null && filemtime($path) <= $mtime_at_least) {
            return null;
        }

        $cache = Providers::safeFileGetContents($path);
        if ($cache === '') {
            return null;
        }

        if ($this->decompressor !== null) {
            $cache = ($this->decompressor)($cache);
        }

        // invalid cache data
        if ($cache === false) {
            $this->deleteItem($path);

            return null;
        }

        if ($this->use_igbinary) {
            /** @var object|false $unserialized */
            $cache = @igbinary_unserialize($cache);
        } else {
            /** @var object|false $unserialized */
            $cache = @unserialize($cache);
        }

        if ($cache === false) {
            $this->deleteItem($path);

            return null;
        }

        return $cache;
    }

    public function deleteItem(string $path): void
    {
        if (@is_writable($path)) {
            @unlink($path);
        }
    }

    public function saveItem(string $path, array|object|string $item): void
    {
        if ($this->use_igbinary) {
            $item = (string) igbinary_serialize($item);
        } else {
            $item = serialize($item);
        }

        if ($this->compressor !== null) {
            $item = ($this->compressor)($item);
        }

        file_put_contents($path, $item, LOCK_EX);
    }
}
