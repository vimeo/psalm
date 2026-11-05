<?php

declare(strict_types=1);

namespace Psalm\Internal;

use Amp\Serialization\Serializer;
use DirectoryIterator;
use Generator;
use Psalm\Config;
use RuntimeException;
use Webmozart\Assert\Assert;

use function array_keys;
use function asort;
use function assert;
use function basename;
use function bin2hex;
use function count;
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
use function intdiv;
use function is_dir;
use function iterator_to_array;
use function min;
use function mkdir;
use function pack;
use function random_bytes;
use function rename;
use function restore_error_handler;
use function set_error_handler;
use function str_ends_with;
use function stream_copy_to_stream;
use function stream_get_contents;
use function strlen;
use function substr;
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
    private const LAYOUT_VERSION = '3';
    private const RECORD_PREFIX_LENGTH = 28;
    private const CONSOLIDATED_INDEX = 'consolidated';
    private const TEMP_SUFFIX = '.tmp';
    private const DICTIONARY_SAMPLES = 14;
    private const DICTIONARY_SAMPLE_LENGTH = 8192;
    private const DICTIONARY_LENGTH = 114_688;

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

    /** Reads the records of the consolidated data file, which a dictionary may compress */
    private Serializer $consolidated_serializer;

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
        $this->consolidated_serializer = $this->serializer;
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
            /** @var array{string, array<string, array{string, int, int}>, string|null} $index */
            $index = $this->serializer->unserialize($index);
            [$data_file, $this->consolidated, $dictionary] = $index;
            $this->consolidated_path = $this->dir . $data_file;
            if ($dictionary !== null && $this->serializer instanceof ZstdSerializer) {
                $this->consolidated_serializer = $this->serializer->withDictionary($dictionary);
            }
        }
    }

    /**
     * Moves every entry into a single data file, so that the cache is a handful of files rather than
     * one per entry, and so that a process only reads the entries it asks for. With zstd the records of the data
     * file are compressed with a dictionary, which the index holds.
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
        /** @param array{string, string, string, string} $record see readRecord() */
        $append = static function (array $record) use ($out, &$entries, &$offset): void {
            [$prefix, $record_hash, $record_key, $payload] = $record;
            $header = $prefix . $record_hash . $record_key;
            Assert::eq(fwrite($out, $header), strlen($header));
            Assert::eq(fwrite($out, $payload), strlen($payload));
            $length = strlen($header) + strlen($payload);
            $entries[$record_key] = [$record_hash, $offset, $length];
            $offset += $length;
        };

        $previous_order = [];
        foreach ($this->consolidated as $key => [, $previous_offset]) {
            $previous_order[$key] = $previous_offset;
        }
        asort($previous_order);

        $zstd = $this->serializer instanceof ZstdSerializer ? $this->serializer : null;
        $previous_zstd = $this->consolidated_serializer instanceof ZstdSerializer
            ? $this->consolidated_serializer
            : null;
        $loose = $this->looseRecordFiles();
        $dictionary = null;
        $consolidated_zstd = null;
        if ($zstd !== null && $previous_zstd !== null) {
            // Only the dictionary needs them all at once
            $loose = iterator_to_array($loose, false);
            $dictionary = $this->buildDictionary($zstd, $previous_zstd, $loose, array_keys($previous_order));
            $consolidated_zstd = $zstd->withDictionary($dictionary);
        }

        foreach ($loose as $path) {
            $record = self::readRecord($path, 0, null, null, null);
            // An entry that is not whole, or that is not the one its name says, is dropped
            if ($record !== null && hash('xxh128', $record[2]) === basename($path)) {
                $append(
                    $zstd !== null && $consolidated_zstd !== null
                        ? self::recompress($record, $zstd, $consolidated_zstd)
                        : $record,
                );
            }
            unlink($path);
        }

        // Entries of the previous data file that no newer record replaced, in file order
        if ($this->consolidated_path !== null) {
            $previous = fopen($this->consolidated_path, 'rb');
            Assert::notFalse($previous);
            foreach ($previous_order as $key => $_) {
                if (isset($entries[$key])) {
                    continue;
                }
                if ($consolidated_zstd !== null && $previous_zstd !== null) {
                    $record = $this->readConsolidated($key, null);
                    if ($record !== null) {
                        $append(self::recompress($record, $previous_zstd, $consolidated_zstd));
                    }
                    continue;
                }
                [$hash, $previous_offset, $length] = $this->consolidated[$key];
                Assert::eq(stream_copy_to_stream($previous, $out, $length, $previous_offset), $length);
                $entries[$key] = [$hash, $offset, $length];
                $offset += $length;
            }
            fclose($previous);
        }
        fclose($out);

        $index_path = $this->dir . self::CONSOLIDATED_INDEX;
        Assert::notFalse(file_put_contents(
            $index_path . '.new',
            $this->serializer->serialize([$data_file, $entries, $dictionary]),
        ));
        Assert::true(rename($index_path . '.new', $index_path));

        if ($this->consolidated_path !== null) {
            unlink($this->consolidated_path);
        }
        $this->consolidated = $entries;
        $this->consolidated_path = $this->dir . $data_file;
        $this->consolidated_serializer = $consolidated_zstd ?? $this->serializer;

        flock($this->lock, LOCK_UN);
        flock($this->lock, LOCK_SH);
    }

    /**
     * @return Generator<int, string> the paths of the loose records, and removes the temporary files on the way
     */
    private function looseRecordFiles(): Generator
    {
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

            if (strlen($name) === 32 && ctype_xdigit($name)) {
                yield $f->getPathname();
            }
        }
    }

    /**
     * Entries are alike (the same class names, the same keys), so the beginnings of a few records, evenly
     * spaced among them, let zstd find in the dictionary what each record repeats from the others.
     *
     * @param list<string> $loose paths of the loose records
     * @param list<string> $previous keys of the records of the consolidated data file
     */
    private function buildDictionary(
        ZstdSerializer $loose_serializer,
        ZstdSerializer $previous_serializer,
        array $loose,
        array $previous,
    ): ?string {
        $count = count($loose) + count($previous);
        $samples = min($count, self::DICTIONARY_SAMPLES);
        $dictionary = '';
        for ($i = 0; $i < $samples; $i++) {
            $n = intdiv($i * $count, $samples);
            if ($n < count($loose)) {
                $record = self::readRecord($loose[$n], 0, null, null, null);
                $serializer = $loose_serializer;
            } else {
                $record = $this->readConsolidated($previous[$n - count($loose)], null);
                $serializer = $previous_serializer;
            }
            if ($record !== null) {
                $dictionary .= substr($serializer->uncompress($record[3]), 0, self::DICTIONARY_SAMPLE_LENGTH);
            }
        }

        return $dictionary === '' ? null : substr($dictionary, -self::DICTIONARY_LENGTH);
    }

    /**
     * @param array{string, string, string, string} $record see readRecord()
     * @return array{string, string, string, string} the record with its payload compressed by $to
     */
    private static function recompress(array $record, ZstdSerializer $from, ZstdSerializer $to): array
    {
        [, $record_hash, $record_key, $payload] = $record;
        $payload = $to->compress($from->uncompress($payload));

        return [
            pack('VVV', strlen($record_hash), strlen($record_key), strlen($payload))
                . hash('xxh128', $payload, true),
            $record_hash,
            $record_key,
            $payload,
        ];
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
        $serializer = $this->consolidated_serializer;
        // A loose record is newer than the consolidated one, so unless the caller knows what it wants,
        // the consolidated one is the fallback.
        if ($hash !== null && isset($this->consolidated[$key]) && $this->consolidated[$key][0] === $hash) {
            $entry = $this->readConsolidated($key, $hash);
        }
        if ($entry === null) {
            $serializer = $this->serializer;
            $entry = self::readRecord($this->dir . hash('xxh128', $key), 0, null, $key, $hash);
        }
        if ($entry === null && $hash === null && isset($this->consolidated[$key])) {
            $serializer = $this->consolidated_serializer;
            $entry = $this->readConsolidated($key, null);
        }
        if ($entry === null) {
            return null;
        }

        /** @var T $content */
        $content = $serializer->unserialize($entry[3]);
        if ($this->arrayCache) {
            $this->cache[$key] = [$entry[1], $content];
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
            $header = pack('VVV', strlen($hash), strlen($key), strlen($payload))
                . hash('xxh128', $payload, true)
                . $hash
                . $key;
            self::writeEntry($this->dir . hash('xxh128', $key), $header, $payload);
        }
        // Without a disk cache the array is the only copy. With one, keep it only if arrayCache is on,
        // otherwise a cold run holds every parsed AST and storage object in memory for nothing.
        if (!$this->persistent || $this->arrayCache) {
            $this->cache[$key] = [$hash, $item];
        }
    }

    /**
     * @param int|string $key an int if the key of a record is numeric, as PHP makes it one in an array
     * @return array{string, string, string, string}|null see readRecord()
     */
    private function readConsolidated(int|string $key, ?string $hash): ?array
    {
        assert($this->consolidated_path !== null);
        $key = (string) $key;
        [, $offset, $length] = $this->consolidated[$key];

        return self::readRecord($this->consolidated_path, $offset, $length, $key, $hash);
    }

    /**
     * Reads the record of $length bytes at $offset, or the whole file if $length is null. The payload is read
     * once and only after the rest of the record has been checked, because the reader allocates what it is
     * asked for: nothing the record declares is read before it is known to fit in the $length bytes.
     *
     * @param string|null $key the key the record must carry, or null for any
     * @param string|null $hash the hash the record must carry, or null for any
     * @return array{string, string, string, string}|null [prefix, stored hash, stored key, serialized item],
     *     null if the record is not the whole, intact record of that key and hash
     */
    private static function readRecord(string $path, int $offset, ?int $length, ?string $key, ?string $hash): ?array
    {
        return self::quietly(static function () use ($path, $offset, $length, $key, $hash): ?array {
            $f = fopen($path, 'rb');
            if ($f === false) {
                return null;
            }

            try {
                $stat = fstat($f);
                assert($stat !== false);
                fseek($f, $offset);

                $prefix = fread($f, self::RECORD_PREFIX_LENGTH);
                if ($prefix === false || strlen($prefix) !== self::RECORD_PREFIX_LENGTH) {
                    return null;
                }

                /** @var array{1: int, 2: int, 3: int} $lengths */
                $lengths = unpack('V3', $prefix);
                [1 => $hash_length, 2 => $key_length, 3 => $payload_length] = $lengths;
                if (self::RECORD_PREFIX_LENGTH + $hash_length + $key_length + $payload_length
                    !== ($length ?? $stat['size'])
                ) {
                    return null;
                }

                // A failed read is an empty string, which matches no record
                $names = (string) stream_get_contents($f, $hash_length + $key_length);
                $stored_hash = substr($names, 0, $hash_length);
                $stored_key = substr($names, $hash_length);
                if (($hash !== null && $stored_hash !== $hash) || ($key !== null && $stored_key !== $key)) {
                    return null;
                }

                $payload = stream_get_contents($f, $payload_length);
                if ($payload === false || hash('xxh128', $payload, true) !== substr($prefix, 12, 16)) {
                    return null;
                }

                return [$prefix, $stored_hash, $stored_key, $payload];
            } finally {
                fclose($f);
            }
        });
    }

    /**
     * Replaces the entry with the record in one step, so that no reader or other writer sees a part of it.
     * The payload is written as it is: putting it in a string together with the header copies it.
     *
     * @throws RuntimeException if the record could not be written
     */
    private static function writeEntry(string $path, string $header, string $payload): void
    {
        // 'x' never reuses the file of another writer
        $temp = $path . '.' . bin2hex(random_bytes(8)) . self::TEMP_SUFFIX;
        $f = fopen($temp, 'xb');
        if ($f === false) {
            throw new RuntimeException("Could not create cache entry $temp");
        }

        $complete = fwrite($f, $header) === strlen($header) && fwrite($f, $payload) === strlen($payload);
        fclose($f);
        if (!$complete) {
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
