<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\Serializer;
use DirectoryIterator;
use Psalm\Config;
use Psalm\Internal\Provider\Providers;
use RuntimeException;
use Webmozart\Assert\Assert;

use function assert;
use function error_clear_last;
use function error_get_last;
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
use function is_int;
use function mkdir;
use function pack;
use function serialize;
use function stream_get_contents;
use function strlen;
use function substr;
use function unlink;
use function unpack;
use function unserialize;
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
    /**
     * Entries of the consolidated file that nothing has asked for yet, as [hash, serialized item]. They stay
     * serialized, so that a process only pays for the items it actually uses, and the memory the forked workers
     * share with it is not filled with objects that they would then dirty by touching reference counts.
     *
     * @var array<string, list{string, string}>
     */
    private array $consolidated = [];
    /** @var resource */
    private mixed $lock;

    private readonly bool $arrayCache;

    public function __construct(
        Config $config,
        string $subdir,
        array $dependencies = [],
        private readonly bool $persistent = true,
    ) {
        $this->serializer = $config->getCacheSerializer();
        $this->arrayCache = $config->array_cache;
        if (!$persistent) {
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
        if (!is_dir($this->dir)) {
            error_clear_last();
            // Another process may create the directory between the check and mkdir() (#4483)
            if (!@mkdir($this->dir, 0777, true) && !is_dir($this->dir)) {
                $reason = error_get_last()['message'] ?? null;
                throw new RuntimeException(
                    'Failed to create ' . $this->dir . ' cache directory' . ($reason !== null ? ": $reason" : ''),
                );
            }
        }

        $lock = fopen($this->dir.'lock', 'c');
        assert($lock !== false);
        flock($lock, LOCK_SH);
        $this->lock = $lock;

        if (file_exists($this->dir.'consolidated') && $this->arrayCache) {
            // The entries are already serialized (and compressed) by $this->serializer, so the file around them is
            // plain: running the whole blob through the compressor again would cost time and save nothing.
            /** @var array<string, list{string, string}> */
            $this->consolidated = unserialize(
                Providers::safeFileGetContents($this->dir.'consolidated'),
                ['allowed_classes' => false],
            );
        }
    }

    public function consolidate(): void
    {
        flock($this->lock, LOCK_UN);
        flock($this->lock, LOCK_EX);

        foreach (new DirectoryIterator($this->dir) as $f) {
            if ($f->isFile() && !$f->isDot()
                && $f->getExtension() === 'hash'
            ) {
                $header = Providers::safeFileGetContents($f->getPathname());
                $hash = self::parseHeader($header);
                if ($hash === null) {
                    continue;
                }
                $key = substr($header, 4 + strlen($hash));
                // A cut-short key hashes to another file name
                if (hash('xxh128', $key) !== $f->getBasename('.hash')) {
                    continue;
                }
                $path = substr($f->getPathname(), 0, -5);
                // The file holds exactly what $this->serializer->serialize() returned for the item,
                // which is also how consolidated entries are stored: no need to unserialize and serialize again.
                $serialized = file_get_contents($path);
                Assert::notFalse($serialized);
                unset($this->cache[$key]);
                $this->consolidated[$key] = [$hash, $serialized];
                unlink($f->getPathname());
                unlink($path);
            }
        }
        $entries = $this->consolidated;
        foreach ($this->cache as $key => [$hash, $item]) {
            $entries[$key] = [$hash, $this->serializer->serialize($item)];
        }

        file_put_contents($this->dir . 'consolidated', serialize($entries), LOCK_EX);
        flock($this->lock, LOCK_UN);
        flock($this->lock, LOCK_SH);
    }

    /**
     * Header layout: "<hash length: 4 bytes><hash><key>", see saveItem().
     *
     * @return string|null the stored hash, or null for an empty or truncated header
     * @psalm-pure
     */
    private static function parseHeader(string $header): ?string
    {
        if (strlen($header) < 4) {
            return null;
        }

        $unpacked = unpack('V', $header);
        $hashLen = $unpacked === false ? null : ($unpacked[1] ?? null);
        if (!is_int($hashLen) || 4 + $hashLen > strlen($header)) {
            return null;
        }

        return substr($header, 4, $hashLen);
    }

    public function getHash(string $key): ?string
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key][0];
        }

        if (isset($this->consolidated[$key])) {
            return $this->consolidated[$key][0];
        }

        if (!$this->persistent) {
            return null;
        }

        $path = $this->dir . hash('xxh128', $key);

        if (!file_exists("$path.hash") || !file_exists($path)) {
            return null;
        }

        $header = Providers::safeFileGetContents("$path.hash");
        $stored = self::parseHeader($header);

        return $stored !== null && substr($header, 4 + strlen($stored)) === $key ? $stored : null;
    }

    /** Whether contents are stored for the key, even if their header is unusable */
    public function hasItem(string $key): bool
    {
        return isset($this->cache[$key])
            || isset($this->consolidated[$key])
            || ($this->persistent && file_exists($this->dir . hash('xxh128', $key)));
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

        if (isset($this->consolidated[$key])) {
            [$consolidated_hash, $serialized] = $this->consolidated[$key];
            if ($hash === null || $consolidated_hash === $hash) {
                unset($this->consolidated[$key]);
                /** @var T $item */
                $item = $this->serializer->unserialize($serialized);
                $this->cache[$key] = [$consolidated_hash, $item];
                return $item;
            }
        }

        if (!$this->persistent) {
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

        $header = stream_get_contents($fp);
        assert($header !== false);
        $stored = self::parseHeader($header);
        if ($stored === null
            || substr($header, 4 + strlen($stored)) !== $key
            || ($hash !== null && $stored !== $hash)
        ) {
            fclose($fp);
            return null;
        }
        $hash = $stored;

        $content = file_get_contents($path);
        Assert::notFalse($content);

        fclose($fp);

        /** @var T */
        $content = $this->serializer->unserialize($content);
        if ($this->arrayCache) {
            unset($this->consolidated[$key]);
            $this->cache[$key] = [$hash, $content];
        }
        return $content;
    }

    /**
     * Drops the item from memory, so that the next saveItem() with the same hash isn't skipped as a duplicate.
     *
     * @psalm-capabilities read-props|write-this-props|write-refs
     */
    public function forgetItem(string $key): void
    {
        unset($this->cache[$key], $this->consolidated[$key]);
    }

    /** @param T $item */
    public function saveItem(string $key, array|object|string $item, ?string $hash = null): void
    {
        // Assume all threads will store the same contents.
        // If the assumption is wrong, it will get fixed on the next run.
        if ($hash === null) {
            $hash = '';
        } elseif ((isset($this->cache[$key]) && $this->cache[$key][0] === $hash)
            || (isset($this->consolidated[$key]) && $this->consolidated[$key][0] === $hash)
        ) {
            return;
        }
        if ($this->persistent) {
            $path = $this->dir . hash('xxh128', $key);
            $header = pack('V', strlen($hash)) . $hash . $key;
            // Serialized before locking to keep the exclusive lock short
            $contents = $this->serializer->serialize($item);
            // 'c' does not truncate: that must wait until the lock is held, or readers see an empty header
            $f = fopen("$path.hash", 'c');
            Assert::notFalse($f);
            flock($f, LOCK_EX);
            // The header goes last: a writer killed anywhere in between leaves an empty or short header,
            // which readers treat as a miss, instead of a valid header in front of the previous payload
            ftruncate($f, 0);
            Assert::eq(file_put_contents($path, $contents), strlen($contents));
            Assert::eq(fwrite($f, $header), strlen($header));
            fflush($f);
            flock($f, LOCK_UN);
            fclose($f);
        }
        // Without a disk cache the array is the only copy. With one, keep it only if arrayCache is on,
        // otherwise a cold run holds every parsed AST and storage object in memory for nothing.
        if (!$this->persistent || $this->arrayCache) {
            unset($this->consolidated[$key]);
            $this->cache[$key] = [$hash, $item];
        }
    }
}
