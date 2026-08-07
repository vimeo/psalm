<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\Serializer;
use AssertionError;
use DirectoryIterator;
use Psalm\Config;
use Psalm\Internal\Provider\Providers;
use RuntimeException;
use Webmozart\Assert\Assert;

use function assert;
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
use function stream_get_contents;
use function strlen;
use function substr;
use function substr_compare;
use function unlink;
use function unpack;
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
    /** Width of the little-endian hash length prefixed to every ".hash" file by saveItem(). */
    private const HASH_LENGTH_BYTES = 4;

    /** @psalm-suppress PropertyNotSetInConstructor intentional */
    private readonly string $dir;
    private readonly Serializer $serializer;

    /** @var array<string, list{string, T}> */
    private array $cache = [];
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

        $lock = fopen($this->dir.'lock', 'c');
        assert($lock !== false);
        flock($lock, LOCK_SH);
        $this->lock = $lock;

        if (file_exists($this->dir.'consolidated') && $this->arrayCache) {
            /** @var array<string, list{string, T}> */
            $this->cache = $this->serializer->unserialize(Providers::safeFileGetContents($this->dir.'consolidated'));
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
                $key = file_get_contents($f->getPathname());
                Assert::notFalse($key);
                /** @var int */
                $hashLen = unpack('V', $key)[1];
                $hash = substr($key, self::HASH_LENGTH_BYTES, $hashLen);
                $key = substr($key, self::HASH_LENGTH_BYTES + $hashLen);
                Assert::notNull($this->getItem($key, $hash));
                unlink($f->getPathname());
                unlink(substr($f->getPathname(), 0, -5));
            }
        }
        $consolidated = $this->serializer->serialize($this->cache);

        file_put_contents($this->dir . 'consolidated', $consolidated, LOCK_EX);
        flock($this->lock, LOCK_UN);
        flock($this->lock, LOCK_SH);
    }

    /**
     * Returns the hash stored alongside an item, or null when there is nothing usable to
     * report: no entry, or a header too damaged to trust.
     *
     * Callers should know that null does not mean "changed". ProjectAnalyzer::getDiffFiles()
     * and Codebase::reloadFiles() both add a file to the diff set only when the hash is
     * non-null and differs, so a null leaves the file out and it is treated as unchanged.
     * StatementsProvider is the exception: there a null causes a full reparse. Reporting a
     * damaged header as a miss is still the right trade, because a miss is indistinguishable
     * from an evicted or never-written entry, which is an ordinary state.
     */
    public function getHash(string $key): ?string
    {
        if (isset($this->cache[$key])) {
            return $this->cache[$key][0];
        }

        if (!$this->persistent) {
            return null;
        }

        $path = $this->dir . hash('xxh128', $key);

        // Both siblings, as getItem() requires: the header is written before the payload,
        // so an interrupted write can leave an orphan header describing nothing.
        if (!file_exists("$path.hash") || !file_exists($path)) {
            return null;
        }

        $header = Providers::safeFileGetContents("$path.hash");

        if (strlen($header) < self::HASH_LENGTH_BYTES) {
            return null;
        }

        $unpacked = unpack('V', substr($header, 0, self::HASH_LENGTH_BYTES));

        // 'V' is unsigned, but on a 32-bit build a value above PHP_INT_MAX decodes negative,
        // and a negative length would make the substr() below return a fabricated hash.
        // Psalm supports 32-bit PHP, so reject it rather than relying on the platform.
        if ($unpacked === false || !isset($unpacked[1]) || !is_int($unpacked[1]) || $unpacked[1] < 0) {
            return null;
        }

        $hash_length = $unpacked[1];

        // Anything that is not exactly "<length><hash><key>" is structurally corrupt. That
        // includes a torn read: saveItem() truncates the file before taking its lock, so a
        // concurrent reader can legitimately see a partial header. Report those as a miss.
        if (strlen($header) !== self::HASH_LENGTH_BYTES + $hash_length + strlen($key)) {
            return null;
        }

        // A structurally sound header whose trailing key is not ours is an xxh128 collision,
        // which is an invariant violation rather than a cache miss. getItem() throws here
        // and so do we: reporting it as a miss would hide real corruption, and callers
        // cannot act on it either way. Note getItem() would throw moments later regardless,
        // since StatementsProvider follows a getHash() with getItem($key, null).
        if (substr_compare($header, $key, self::HASH_LENGTH_BYTES + $hash_length) !== 0) {
            throw new AssertionError("Hash collision on key $key");
        }

        return substr($header, self::HASH_LENGTH_BYTES, $hash_length);
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

        $fileHash = stream_get_contents($fp);
        if ($hash === null) {
            if ($fileHash === '') {
                fclose($fp);
                return null;
            }
            assert($fileHash !== false);
            $hashLen = unpack('V', $fileHash)[1];
            assert(is_int($hashLen));
            $hash = substr($fileHash, self::HASH_LENGTH_BYTES, $hashLen);
            if (substr_compare($fileHash, $key, self::HASH_LENGTH_BYTES + $hashLen) !== 0) {
                throw new AssertionError("Hash collision on key $key");
            }
        } elseif (substr_compare($fileHash, $hash, self::HASH_LENGTH_BYTES, strlen($hash)) !== 0
            || substr_compare($fileHash, $key, strlen($hash) + self::HASH_LENGTH_BYTES) !== 0
            || strlen($fileHash) !== strlen($key) + strlen($hash) + self::HASH_LENGTH_BYTES
        ) {
            fclose($fp);
            return null;
        }

        $content = file_get_contents($path);
        Assert::notFalse($content);

        fclose($fp);

        /** @var T */
        $content = $this->serializer->unserialize($content);
        if ($this->arrayCache) {
            $this->cache[$key] = [$hash, $content];
        }
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
        if ($this->persistent) {
            $path = $this->dir . hash('xxh128', $key);
            $f = fopen("$path.hash", 'w');
            Assert::notFalse($f);
            flock($f, LOCK_EX);
            ftruncate($f, 0);
            Assert::eq(fwrite($f, pack('V', strlen($hash))), self::HASH_LENGTH_BYTES);
            Assert::eq(fwrite($f, $hash), strlen($hash));
            Assert::eq(fwrite($f, $key), strlen($key));
            file_put_contents($path, $this->serializer->serialize($item));
            fflush($f);
            flock($f, LOCK_UN);
            fclose($f);
        }
        $this->cache[$key] = [$hash, $item];
    }
}
