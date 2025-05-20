<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\Serializer;
use Psalm\Config;
use Psalm\Internal\Provider\Providers;
use RuntimeException;
use Webmozart\Assert\Assert;

use function fclose;
use function fflush;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function ftruncate;
use function fwrite;
use function hash;
use function hash_final;
use function hash_init;
use function hash_update;
use function is_dir;
use function mkdir;
use function stream_get_contents;
use function strlen;
use function usleep;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;
use const LOCK_SH;
use const LOCK_UN;

/**
 * @internal
 * @template T as array|object|string
 */
final class Cache
{
    /** @psalm-suppress PropertyNotSetInConstructor intentional */
    private readonly string $dir;
    private readonly Serializer $serializer;

    /** @var array<string, list{string, T}> */
    private array $cache = [];

    public function __construct(
        Config $config,
        string $subdir,
        array $dependencies = [],
        private readonly bool $noFile = false,
    ) {
        $this->serializer = $config->getCacheSerializer();
        if ($noFile) {
            return;
        }

        $dir = $config->getCacheDirectory().DIRECTORY_SEPARATOR.$subdir;

        $hash = hash_init('xxh128');
        foreach ($dependencies as $dep) {
            hash_update($hash, (string) $dep);
            hash_update($hash, "\0");
        }
        hash_update($hash, $config->computeHash());
        hash_update($hash, "\0");
        hash_update($hash, $this->serializer->serialize($this->serializer));

        $dir .= DIRECTORY_SEPARATOR . hash_final($hash);

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
    }

    public function getHash(string $key): ?string
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key][0];
        }

        if ($this->noFile) {
            return null;
        }

        $path = $this->dir . hash('xxh128', $key);

        if (!file_exists($path)) {
            return null;
        }

        return Providers::safeFileGetContents($path);
    }

    /** @return T */
    public function getItem(string $key, ?string $hash = ''): array|object|string|null
    {
        if (isset($this->cache[$key])) {
            $combined = $this->cache[$key];
            if ($hash === null || $combined[0] === $hash) {
                return $combined[1];
            }
        }

        if ($this->noFile) {
            return null;
        }

        $path = $this->dir . hash('xxh128', $key);

        if (!file_exists("$path.hash")
            || !file_exists($path)
        ) {
            return null;
        }

        $fp = fopen("$path.hash", 'r');
        if ($fp === false) {
            return null;
        }
        $max_wait_cycles = 5;
        $has_lock = false;
        while ($max_wait_cycles > 0) {
            if (flock($fp, LOCK_SH)) {
                $has_lock = true;
                break;
            }
            $max_wait_cycles--;
            usleep(50_000);
        }

        if (!$has_lock) {
            fclose($fp);
            throw new RuntimeException("Could not acquire lock for $path.hash");
        }

        $fileHash = stream_get_contents($fp);
        if ($hash === null) {
            if ($fileHash === '') {
                fclose($fp);
                return null;
            }
            Assert::notFalse($fileHash);
            $hash = $fileHash;
        } elseif ($fileHash !== $hash) {
            fclose($fp);
            return null;
        }

        $content = file_get_contents($path);
        Assert::notFalse($content);

        fclose($fp);

        /** @var T */
        $content = $this->serializer->unserialize($content);
        $this->cache[$key] = [$hash, $content];
        return $content;
    }

    /** @param T $item */
    public function saveItem(string $key, array|object|string $item, ?string $hash = null): void
    {
        // Assume all threads will store the same contents.
        // If the assumption is wrong, it will get fixed on the next run.
        if ($hash === null) {
            $hash = '';
        } elseif (isset($this->cache[$key]) && $this->cache[$key][0] === $hash) {
            return;
        }
        if (!$this->noFile) {
            $path = $this->dir . hash('xxh128', $key);
            $f = fopen("$path.hash", 'w');
            Assert::notFalse($f);
            flock($f, LOCK_EX);
            ftruncate($f, 0);
            Assert::eq(fwrite($f, $hash), strlen($hash));
            file_put_contents($path, $this->serializer->serialize($item));
            fflush($f);
            flock($f, LOCK_UN);
            fclose($f);
        }
        $this->cache[$key] = [$hash, $item];
    }
}
