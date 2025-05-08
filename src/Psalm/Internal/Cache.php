<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\SerializationException;
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
 * 
 * @template T as array|object|string
 */
final class Cache
{
    private readonly string $dir;
    private readonly Serializer $serializer;

    /** @var array<string, string> */
    private array $idx = [];
    /** @var array<string, string> */
    private array $newIdx = [];
    /** @var array<string, T> */
    private array $cache = [];

    public function __construct(Config $config, string $subdir, mixed $dependencies = null)
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
        $data = stream_get_contents($idx);
        try {
            [$deps, $idx] = json_decode($data, true);

            if ($deps === $dependencies) {
                $this->idx = $idx;
            }
        } catch (RuntimeException) {}
        flock($idx, LOCK_UN);
        fclose($idx);
    }

    /** @return T */
    public function getItem(string $key, string $hash = ''): array|object|string|null
    {
        if (isset($this->idx[$key]) && $this->idx[$key] !== $hash) {
            unset($this->idx[$key]);
            unset($this->newIdx[$key]);
            unset($this->cache[$key]);
            return null;
        } elseif (!isset($this->idx[$key])) {
            return null;
        }

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $path = $this->dir . DIRECTORY_SEPARATOR . hash('xxh128', $key);

        if (!file_exists($path) 
            || !is_readable($path)
        ) {
            return null;
        }

        $cache = Providers::safeFileGetContents($path);
        if ($cache === '') {
            return null;
        }

        $this->cache[$key] = $v = $this->serializer->unserialize($cache);
        $this->idx[$key] = $hash;
        $this->newIdx[$key] = $hash;

        return $v;
    }

    public function deleteItem(string $key): void
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . hash('xxh128', $key);
        @unlink($path);
        unset($this->idx[$key]);
        unset($this->newIdx[$key]);
        unset($this->cache[$key]);
    }

    /** @param T $item */
    public function saveItem(string $key, array|object|string $item, string $hash = ''): void
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . hash('xxh128', $key);
        file_put_contents($path, $this->serializer->serialize($item), LOCK_EX);
        $this->cache[$key] = $item;
        $this->idx[$key] = $hash;
        $this->newIdx[$key] = $hash;
    }

    /** @return array<string, string> */
    public function getNewIdx(): array {
        return $this->newIdx;
    }
}
