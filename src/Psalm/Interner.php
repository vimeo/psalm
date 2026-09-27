<?php

declare(strict_types=1);

namespace Psalm;

use RuntimeException;

use function count;
use function fclose;
use function file_exists;
use function file_get_contents;
use function flock;
use function fopen;
use function fwrite;
use function hash;
use function pack;
use function strlen;
use function strtolower;
use function substr;
use function unpack;

use const LOCK_EX;
use const LOCK_UN;

/**
 * Global string interner.
 *
 * Every class, interface, trait, enum, function, method, property, constant, enum case,
 * template and type alias name is represented as an interned `int` id instead of a string.
 *
 * Ids are deterministic (a 64-bit xxh3 hash of the string), so every process
 * (including forked workers) and every run (including cached data) agrees on the id of a given
 * string without any coordination: only the reverse id => string mapping has to be shipped
 * around, which is done via {@see self::getSince()} and {@see self::import()}.
 *
 * Names are interned with their original casing; use {@see self::lower()} to obtain the id of the
 * lowercased string (e.g. for case-insensitive lookups of classes, functions and methods).
 *
 * Commonly used strings have precomputed ids in {@see StrId}.
 *
 * @api
 */
final class Interner
{
    /** @var array<int, string> */
    private static array $strings = StrId::STRINGS;

    /** @var array<string, int> */
    private static array $ids = StrId::IDS;

    /** @var array<int, int> */
    private static array $lower = StrId::LOWER;

    /**
     * Strings interned at runtime (i.e. not preloaded), in insertion order.
     *
     * @var list<string>
     */
    private static array $order = [];

    /**
     * Offsets into {@see self::$order} up to which strings were already persisted, by path.
     *
     * @var array<string, int>
     */
    private static array $persisted = [];

    /**
     * Strings already present in persisted files, by path.
     *
     * @var array<string, array<string, true>>
     */
    private static array $in_file = [];

    /**
     * Computes the id of a string, without interning it.
     *
     * @psalm-pure
     */
    public static function hash(string $str): int
    {
        /** @var array{1: int} */
        $result = unpack('J', hash('xxh3', $str, true));
        return $result[1];
    }

    /**
     * Interns a string, returning its id.
     *
     * @psalm-pure
     * @psalm-suppress ImpureStaticProperty interning is semantically pure
     */
    public static function intern(string $str): int
    {
        if (isset(self::$ids[$str])) {
            return self::$ids[$str];
        }

        $id = self::hash($str);
        if (isset(self::$strings[$id])) {
            throw new RuntimeException(
                'Interner hash collision between "' . self::$strings[$id] . '" and "' . $str . '"',
            );
        }

        self::$ids[$str] = $id;
        self::$strings[$id] = $str;
        self::$order[] = $str;

        return $id;
    }

    /**
     * Interns a list of strings, returning their ids.
     *
     * @param array<string> $strs
     * @return list<int>
     * @psalm-pure
     */
    public static function internAll(array $strs): array
    {
        $result = [];
        foreach ($strs as $str) {
            $result[] = self::intern($str);
        }
        return $result;
    }

    /**
     * Interns the lowercase version of a string, returning its id.
     *
     * @psalm-pure
     */
    public static function internLower(string $str): int
    {
        return self::intern(strtolower($str));
    }

    /**
     * Returns the id of a string, if it was already interned.
     *
     * @psalm-pure
     * @psalm-suppress ImpureStaticProperty interning is semantically pure
     */
    public static function find(string $str): ?int
    {
        return self::$ids[$str] ?? null;
    }

    /**
     * Returns the string corresponding to an id.
     *
     * @psalm-pure
     * @psalm-suppress ImpureStaticProperty interning is semantically pure
     */
    public static function str(int $id): string
    {
        return self::$strings[$id] ?? throw new RuntimeException("Unknown interned string id $id");
    }

    /**
     * Returns the id of the lowercase version of the string corresponding to an id.
     *
     * @psalm-pure
     * @psalm-suppress ImpureStaticProperty interning is semantically pure
     */
    public static function lower(int $id): int
    {
        return self::$lower[$id] ??= self::intern(strtolower(self::str($id)));
    }

    /**
     * Case-insensitive comparison of two interned strings.
     *
     * @psalm-pure
     */
    public static function equalsLower(int $a, int $b): bool
    {
        return $a === $b || self::lower($a) === self::lower($b);
    }

    /**
     * Converts a list of ids to their strings.
     *
     * @param array<int> $ids
     * @return list<string>
     * @psalm-pure
     */
    public static function strAll(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            $result[] = self::str($id);
        }
        return $result;
    }

    /**
     * Returns the number of strings interned at runtime.
     *
     * @internal
     * @psalm-external-mutation-free
     */
    public static function count(): int
    {
        return count(self::$order);
    }

    /**
     * Returns the strings interned at runtime, starting from the specified offset.
     *
     * @internal
     * @return list<string>
     * @psalm-external-mutation-free
     */
    public static function getSince(int $offset): array
    {
        $result = [];
        for ($x = $offset, $max = count(self::$order); $x < $max; $x++) {
            $result[] = self::$order[$x];
        }
        return $result;
    }

    /**
     * Imports strings interned by another process.
     *
     * @internal
     * @param list<string> $strings
     * @psalm-mutation-free
     */
    public static function import(array $strings): void
    {
        foreach ($strings as $str) {
            self::intern($str);
        }
    }

    /**
     * Loads strings persisted by {@see self::persist()}.
     *
     * @internal
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }
        $data = file_get_contents($path);
        if ($data === false) {
            return;
        }
        $offset = 0;
        $total = strlen($data);
        while ($offset + 4 <= $total) {
            /** @var array{1: int} */
            $len = unpack('V', $data, $offset);
            $len = $len[1];
            $offset += 4;
            if ($offset + $len > $total) {
                break;
            }
            $str = substr($data, $offset, $len);
            $offset += $len;
            self::intern($str);
            self::$in_file[$path][$str] = true;
        }
    }

    /**
     * Appends all runtime-interned strings that were not yet persisted to the specified file.
     *
     * Must be called before persisting any data containing interned ids.
     *
     * @internal
     */
    public static function persist(string $path): void
    {
        $max = count(self::$order);
        $start = self::$persisted[$path] ?? 0;
        if ($start === $max) {
            return;
        }
        $data = '';
        for ($x = $start; $x < $max; $x++) {
            $str = self::$order[$x];
            if (isset(self::$in_file[$path][$str])) {
                continue;
            }
            $data .= pack('V', strlen($str)) . $str;
        }
        self::$persisted[$path] = $max;
        if ($data === '') {
            return;
        }
        $f = fopen($path, 'a');
        if ($f === false) {
            throw new RuntimeException("Could not open $path");
        }
        flock($f, LOCK_EX);
        fwrite($f, $data);
        flock($f, LOCK_UN);
        fclose($f);
        self::$persisted[$path] = $max;
    }
}
