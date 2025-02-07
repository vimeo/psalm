<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use InvalidArgumentException;
use LogicException;
use Psalm\Issue\DuplicateClass;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;

use function strtolower;

/**
 * @internal
 */
final class ClassLikeStorageProvider
{
    /**
     * Storing this statically is much faster (at least in PHP 7.2.1)
     *
     * @var array<string, ClassLikeStorage>
     */
    private static array $storage = [];

    /**
     * @var array<string, ClassLikeStorage>
     */
    private static array $new_storage = [];

    public function __construct(public ?ClassLikeStorageCacheProvider $cache = null)
    {
    }

    /**
     * @psalm-mutation-free
     * @throws InvalidArgumentException when class does not exist
     */
    public function get(string $fq_classlike_name): ClassLikeStorage
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);
        /** @psalm-suppress ImpureStaticProperty Used only for caching */
        if (!isset(self::$storage[$fq_classlike_name_lc])) {
            throw new InvalidArgumentException('Could not get class storage for ' . $fq_classlike_name_lc);
        }

        /** @psalm-suppress ImpureStaticProperty Used only for caching */
        return self::$storage[$fq_classlike_name_lc];
    }

    /**
     * @psalm-mutation-free
     */
    public function has(string $fq_classlike_name): bool
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        /** @psalm-suppress ImpureStaticProperty Used only for caching */
        return isset(self::$storage[$fq_classlike_name_lc]);
    }

    public function exhume(string $fq_classlike_name, string $file_path, string $file_contents): ClassLikeStorage
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        if (isset(self::$storage[$fq_classlike_name_lc])) {
            return self::$storage[$fq_classlike_name_lc];
        }

        if (!$this->cache) {
            throw new LogicException('Cannot exhume when there’s no cache');
        }

        $cached_value = $this->cache->getLatestFromCache($fq_classlike_name_lc, $file_path, $file_contents);

        self::$storage[$fq_classlike_name_lc] = $cached_value;
        self::$new_storage[$fq_classlike_name_lc] = $cached_value;

        return $cached_value;
    }

    /**
     * @return array<string, ClassLikeStorage>
     */
    public static function getAll(): array
    {
        return self::$storage;
    }

    /**
     * @return array<string, ClassLikeStorage>
     */
    public function getNew(): array
    {
        return self::$new_storage;
    }

    /**
     * @param array<string, ClassLikeStorage> $more
     */
    public function addMore(array $more): void
    {
        foreach ($more as $k => $storage) {
            if (isset(self::$storage[$k])) {
                $duplicate_storage = self::$storage[$k];
                $duplicate_location = $duplicate_storage->location ?? $duplicate_storage->stmt_location;
                $location = $storage->location ?? $storage->stmt_location;
                if ($duplicate_location !== null
                    && $location !== null
                    && $duplicate_location->getHash() !== $location->getHash()
                ) {
                    IssueBuffer::maybeAdd(
                        new DuplicateClass(
                            'Class ' . $k . ' has already been defined'
                            . ' in ' . $location->file_path,
                            $location,
                        ),
                    );

                    //$storage->file_storage->has_visitor_issues = true;

                    $duplicate_storage->has_visitor_issues = true;

                    continue;
                }
            }
            self::$new_storage[$k] = $storage;
            self::$storage[$k] = $storage;
        }
    }

    public function makeNew(string $fq_classlike_name_lc): void
    {
        self::$new_storage[$fq_classlike_name_lc] = self::$storage[$fq_classlike_name_lc];
    }

    public function create(string $fq_classlike_name): ClassLikeStorage
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        $storage = new ClassLikeStorage($fq_classlike_name);
        self::$storage[$fq_classlike_name_lc] = $storage;
        self::$new_storage[$fq_classlike_name_lc] = $storage;

        return $storage;
    }

    public function remove(string $fq_classlike_name): void
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        unset(self::$storage[$fq_classlike_name_lc]);
    }

    public static function deleteAll(): void
    {
        self::$storage = [];
        self::$new_storage = [];
    }

    public static function populated(): void
    {
        self::$new_storage = [];
    }
}
