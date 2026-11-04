<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\Serializer;
use DirectoryIterator;
use Psalm\Config;
use RuntimeException;
use Webmozart\Assert\Assert;

use function asort;
use function assert;
use function bin2hex;
use function ctype_xdigit;
use function error_clear_last;
use function error_get_last;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function flock;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function fwrite;
use function hash;
use function hash_final;
use function hash_init;
use function hash_update;
use function is_dir;
use function mkdir;
use function pack;
use function random_bytes;
use function rename;
use function restore_error_handler;
use function set_error_handler;
use function str_ends_with;
use function stream_get_contents;
use function strlen;
use function substr;
use function substr_compare;
use function unlink;
use function unpack;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;
use const LOCK_SH;
use const LOCK_UN;

/**
 * A key/value store with one file per entry (or, after consolidate(), one data file for all of them).
 *
 * An entry is a single file that describes itself, so readers take no lock: a reader that finds anything
 * wrong with it treats the entry as missing. The record is
 *
 *   <hash length u32><key length u32><payload length u32><xxh128 of the payload, 16 bytes><hash><key><payload>
 *
 * (integers are little-endian). The hash tells whether the entry is still valid for the caller (e.g. the
 * contents of the cached file), the key guards against a collision of the file name.
 *
 * A record is written to a temporary file next to the entry and renamed over it, so a writer that dies, or
 * two writers at once, leave the previous or the new record and never a mix of both. The checks of the
 * reader are there for files that were not written by this class (a full disk, a copy that was cut short).
 *
 * @internal
 * @template T as array|object|string
 */
final class Cache
{
    /** Changing the format of the records or the consolidated files requires changing this. */
    private const LAYOUT_VERSION = '2';
    private const RECORD_PREFIX_LENGTH = 28;
    private const CONSOLIDATED_INDEX = 'consolidated';
    private const TEMP_SUFFIX = '.tmp';

    /** @psalm-suppress PropertyNotSetInConstructor intentional */
    private readonly string $dir;
    private readonly Serializer $serializer;

    /** @var array<string, list{string, T}> */
    private array $cache = [];

    /**
     * Records of the consolidated data file: key => [hash, offset, length]
     *
     * @var array<string, array{string, int, int}>
     */
    private array $consolidated = [];
    private ?string $consolidated_path = null;

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
        hash_update($hash, "\0");
        hash_update($hash, self::LAYOUT_VERSION);

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

        $index = file_exists($this->dir . self::CONSOLIDATED_INDEX)
            ? @file_get_contents($this->dir . self::CONSOLIDATED_INDEX)
            : false;
        if ($index !== false) {
            /** @var array{string, array<string, array{string, int, int}>} $index */
            $index = $this->serializer->unserialize($index);
            [$data_file, $this->consolidated] = $index;
            $this->consolidated_path = $this->dir . $data_file;
        }
    }

    /**
     * Moves every entry into a single data file, so that the cache is a handful of files rather than
     * one per entry, and so that a process only reads the entries it asks for.
     */
    public function consolidate(): void
    {
        flock($this->lock, LOCK_UN);
        flock($this->lock, LOCK_EX);

        $data_file = 'consolidated-' . bin2hex(random_bytes(8)) . '.data';
        $out = fopen($this->dir . $data_file, 'wb');
        Assert::notFalse($out);

        /** @var array<string, array{string, int, int}> $entries */
        $entries = [];
        $offset = 0;

        foreach (new DirectoryIterator($this->dir) as $f) {
            $name = $f->getFilename();
            if (!$f->isFile()) {
                continue;
            }

            // Left by a writer that died before renaming it. No writer is alive: they hold the shared lock.
            if (str_ends_with($name, self::TEMP_SUFFIX)) {
                unlink($f->getPathname());
                continue;
            }

            if (strlen($name) !== 32 || !ctype_xdigit($name)) {
                continue;
            }

            $record = file_get_contents($f->getPathname());
            Assert::notFalse($record);
            $header = self::parseRecordHeader($record);
            // An entry that is not whole, or that is not the one its name says, is dropped
            if ($header !== null
                && hash('xxh128', $header[1]) === $name
                && self::parseRecord($record, $header[1], null) !== null
            ) {
                $length = strlen($record);
                Assert::eq(fwrite($out, $record), $length);
                $entries[$header[1]] = [$header[0], $offset, $length];
                $offset += $length;
            }
            unlink($f->getPathname());
        }

        // Entries of the previous data file that no newer record replaced, in file order
        if ($this->consolidated_path !== null) {
            $carried = $this->consolidated;
            foreach ($entries as $key => $_) {
                unset($carried[$key]);
            }
            $order = [];
            foreach ($carried as $key => [, $previous_offset]) {
                $order[$key] = $previous_offset;
            }
            asort($order);

            $previous = fopen($this->consolidated_path, 'rb');
            Assert::notFalse($previous);
            foreach ($order as $key => $_) {
                [$hash, $previous_offset, $length] = $carried[$key];
                fseek($previous, $previous_offset);
                $record = fread($previous, $length);
                Assert::string($record);
                Assert::eq(strlen($record), $length);
                Assert::eq(fwrite($out, $record), $length);
                $entries[$key] = [$hash, $offset, $length];
                $offset += $length;
            }
            fclose($previous);
        }
        fclose($out);

        $index_path = $this->dir . self::CONSOLIDATED_INDEX;
        Assert::notFalse(file_put_contents(
            $index_path . '.new',
            $this->serializer->serialize([$data_file, $entries]),
        ));
        Assert::true(rename($index_path . '.new', $index_path));

        if ($this->consolidated_path !== null) {
            unlink($this->consolidated_path);
        }
        $this->consolidated = $entries;
        $this->consolidated_path = $this->dir . $data_file;

        flock($this->lock, LOCK_UN);
        flock($this->lock, LOCK_SH);
    }

    /**
     * The hash of an entry, which is not necessarily one that getItem() can still load: only the header is read.
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
        $f = self::quietly(static fn(): mixed => fopen($path, 'rb'));
        if ($f !== false) {
            $stat = fstat($f);
            assert($stat !== false);
            $prefix = fread($f, self::RECORD_PREFIX_LENGTH);
            if ($prefix !== false && strlen($prefix) === self::RECORD_PREFIX_LENGTH) {
                /** @var array{1: int, 2: int} $lengths */
                $lengths = unpack('V2', $prefix);
                $length = $lengths[1] + $lengths[2];
                // Reading allocates what it is asked for (before PHP 8.5 whatever the file holds), so a header
                // that declares more than the file has is not read at all
                $header = self::RECORD_PREFIX_LENGTH + $length <= $stat['size']
                    ? stream_get_contents($f, $length)
                    : false;
                if ($header !== false && substr($header, $lengths[1]) === $key) {
                    fclose($f);

                    return substr($header, 0, $lengths[1]);
                }
            }
            fclose($f);
        }

        return $this->consolidated[$key][0] ?? null;
    }

    /** Whether contents are stored for the key, even if they are unusable */
    public function hasItem(string $key): bool
    {
        return isset($this->cache[$key])
            || isset($this->consolidated[$key])
            || ($this->persistent && file_exists($this->dir . hash('xxh128', $key)));
    }

    /** @return T|null */
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

        $entry = null;
        // A loose record is newer than the consolidated one, so unless the caller knows what it wants,
        // the consolidated one is the fallback.
        if ($hash !== null && isset($this->consolidated[$key]) && $this->consolidated[$key][0] === $hash) {
            $entry = $this->readConsolidated($key, $hash);
        }
        if ($entry === null) {
            $path = $this->dir . hash('xxh128', $key);
            $record = self::quietly(static fn(): string|false => file_get_contents($path));
            if ($record !== false) {
                $entry = self::parseRecord($record, $key, $hash);
            }
        }
        if ($entry === null && $hash === null && isset($this->consolidated[$key])) {
            $entry = $this->readConsolidated($key, null);
        }
        if ($entry === null) {
            return null;
        }

        /** @var T $content */
        $content = $this->serializer->unserialize($entry[1]);
        if ($this->arrayCache) {
            $this->cache[$key] = [$entry[0], $content];
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
            $payload = $this->serializer->serialize($item);
            $record = pack('VVV', strlen($hash), strlen($key), strlen($payload))
                . hash('xxh128', $payload, true)
                . $hash
                . $key
                . $payload;
            self::writeEntry($this->dir . hash('xxh128', $key), $record);
        }
        // Without a disk cache the array is the only copy. With one, keep it only if arrayCache is on,
        // otherwise a cold run holds every parsed AST and storage object in memory for nothing.
        if (!$this->persistent || $this->arrayCache) {
            $this->cache[$key] = [$hash, $item];
        }
    }

    /** @return array{string, string}|null [stored hash, serialized item] */
    private function readConsolidated(string $key, ?string $hash): ?array
    {
        assert($this->consolidated_path !== null);
        [, $offset, $length] = $this->consolidated[$key];
        $path = $this->consolidated_path;
        $record = self::quietly(static fn(): string|false => file_get_contents($path, false, null, $offset, $length));

        return $record === false ? null : self::parseRecord($record, $key, $hash);
    }

    /**
     * @param string|null $hash the hash the record must carry, or null for any
     * @return array{string, string}|null [stored hash, serialized item], null if the record is not
     *     the whole, intact record of that key and hash
     * @psalm-pure
     */
    private static function parseRecord(string $record, string $key, ?string $hash): ?array
    {
        if (strlen($record) < self::RECORD_PREFIX_LENGTH) {
            return null;
        }

        /** @var array{1: int, 2: int, 3: int} $lengths */
        $lengths = unpack('V3', $record);
        [1 => $hash_length, 2 => $key_length, 3 => $payload_length] = $lengths;
        $key_offset = self::RECORD_PREFIX_LENGTH + $hash_length;
        $payload_offset = $key_offset + $key_length;

        if (strlen($record) !== $payload_offset + $payload_length
            || $key_length !== strlen($key)
            || substr_compare($record, $key, $key_offset, $key_length) !== 0
        ) {
            return null;
        }

        if ($hash === null) {
            $hash = substr($record, self::RECORD_PREFIX_LENGTH, $hash_length);
        } elseif ($hash_length !== strlen($hash)
            || substr_compare($record, $hash, self::RECORD_PREFIX_LENGTH, $hash_length) !== 0
        ) {
            return null;
        }

        $payload = substr($record, $payload_offset);
        if (substr_compare($record, hash('xxh128', $payload, true), 12, 16) !== 0) {
            return null;
        }

        return [$hash, $payload];
    }

    /**
     * @return array{string, string}|null [hash, key]
     * @psalm-pure
     */
    private static function parseRecordHeader(string $record): ?array
    {
        if (strlen($record) < self::RECORD_PREFIX_LENGTH) {
            return null;
        }

        /** @var array{1: int, 2: int} $lengths */
        $lengths = unpack('V2', $record);
        if (strlen($record) < self::RECORD_PREFIX_LENGTH + $lengths[1] + $lengths[2]) {
            return null;
        }

        return [
            substr($record, self::RECORD_PREFIX_LENGTH, $lengths[1]),
            substr($record, self::RECORD_PREFIX_LENGTH + $lengths[1], $lengths[2]),
        ];
    }

    /**
     * Replaces the entry with the record in one step, so that no reader or other writer sees a part of it.
     *
     * @throws RuntimeException if the record could not be written
     */
    private static function writeEntry(string $path, string $record): void
    {
        // 'x' never reuses the file of another writer
        $temp = $path . '.' . bin2hex(random_bytes(8)) . self::TEMP_SUFFIX;
        $f = fopen($temp, 'xb');
        if ($f === false) {
            throw new RuntimeException("Could not create cache entry $temp");
        }

        $written = fwrite($f, $record);
        fclose($f);
        if ($written !== strlen($record)) {
            self::quietly(static fn(): bool => unlink($temp));

            throw new RuntimeException("Could not write cache entry $path");
        }

        // Where an open file cannot be replaced (Windows) another process is using the entry. It gets
        // the same contents, or it is fixed on the next run, so skipping the write is fine.
        if (!self::quietly(static fn(): bool => rename($temp, $path))) {
            self::quietly(static fn(): bool => unlink($temp));
        }
    }

    /**
     * Runs an operation that may fail for a reason that is not an error here (the file is missing, in use)
     * without raising a warning, whatever the error handler does with the error control operator.
     *
     * @template R
     * @param callable(): R $operation
     * @return R
     */
    private static function quietly(callable $operation): mixed
    {
        set_error_handler(static fn(): bool => true);
        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }
}
