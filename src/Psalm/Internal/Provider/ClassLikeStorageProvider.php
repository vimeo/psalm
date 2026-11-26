<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use InvalidArgumentException;
use LogicException;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Issue\DuplicateClass;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassLikeStorage;

use function strcmp;
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

    /**
     * @psalm-mutation-free
     */
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

    /**
     * @return ClassLikeStorage|null null if the cache has no storage for the class-like in the file, or the name is
     *                               already taken by a declaration in another file (or by the native class)
     */
    public function exhume(string $fq_classlike_name, string $file_path, string $file_contents): ?ClassLikeStorage
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        if (isset(self::$storage[$fq_classlike_name_lc])) {
            $in_memory = self::$storage[$fq_classlike_name_lc];

            return $in_memory->stmt_location?->file_path === $file_path ? $in_memory : null;
        }

        if (!$this->cache) {
            throw new LogicException('Cannot exhume when there’s no cache');
        }

        $cached_value = $this->cache->getLatestFromCache($fq_classlike_name_lc, $file_path, $file_contents);

        if ($cached_value === null) {
            return null;
        }

        self::$storage[$fq_classlike_name_lc] = $cached_value;
        self::$new_storage[$fq_classlike_name_lc] = $cached_value;

        return $cached_value;
    }

    /**
     * @return array<string, ClassLikeStorage>
     * @psalm-external-mutation-free
     */
    public static function getAll(): array
    {
        return self::$storage;
    }

    /**
     * @return array<string, ClassLikeStorage>
     * @psalm-external-mutation-free
     */
    public function getNew(): array
    {
        return self::$new_storage;
    }

    /**
     * @param array<string, ClassLikeStorage> $more
     * @param array<string, ClassLikeStorage> $earlier the storages held before the scanning processes started
     */
    public function addMore(array $more, array $earlier, int $analysis_php_version_id): void
    {
        $analyzer = ProjectAnalyzer::getInstance()->getCodebase()->analyzer;

        foreach ($more as $k => $storage) {
            if (isset(self::$storage[$k])) {
                $duplicate_storage = self::$storage[$k];
                $duplicate_location = $duplicate_storage->location ?? $duplicate_storage->stmt_location;
                $location = $storage->location ?? $storage->stmt_location;
                // a project polyfill of a native class stubbed with an `@since` newer than the analysed
                // version replaces the stub, as in ClassLikeNodeScanner
                $is_polyfill = $duplicate_storage->stubbed
                    && $duplicate_storage->since_php_version_id !== null
                    && $analysis_php_version_id < $duplicate_storage->since_php_version_id
                    && $storage->user_defined;

                if (!$is_polyfill
                    && $duplicate_location !== null
                    && $location !== null
                    && $duplicate_location->getHash() !== $location->getHash()
                ) {
                    // a stub is no dependency alternative: a duplicate of it stays a duplicate
                    $has_stub = $duplicate_storage->stubbed || $storage->stubbed;
                    $is_own_code = $has_stub || $analyzer->isOwnCodeFile($location->file_path);
                    $duplicate_is_own_code = $has_stub || $analyzer->isOwnCodeFile($duplicate_location->file_path);

                    if ($is_own_code !== $duplicate_is_own_code) {
                        // the project's declaration replaces a dependency's, see ClassLikeNodeScanner
                        $new_is_kept = $is_own_code;
                    } else {
                        // a single process keeps what it scanned before (a worker may have rescanned that file),
                        // then the declaration in the smaller path: it takes its files in path order
                        $earlier_storage = $earlier[$k] ?? null;
                        $earlier_location = $earlier_storage?->location ?? $earlier_storage?->stmt_location;
                        $new_is_kept = $duplicate_location->file_path !== $earlier_location?->file_path
                            && strcmp($location->file_path, $duplicate_location->file_path) < 0;
                    }
                    [$kept, $kept_location, $dropped_location] = $new_is_kept
                        ? [$storage, $location, $duplicate_location]
                        : [$duplicate_storage, $duplicate_location, $location];

                    if ($is_own_code && $duplicate_is_own_code) {
                        IssueBuffer::maybeAdd(
                            new DuplicateClass(
                                'Class ' . $kept->name . ' has already been defined in ' . $kept_location->file_path,
                                $dropped_location,
                            ),
                        );

                        $kept->has_visitor_issues = true;
                    }

                    if (!$new_is_kept) {
                        continue;
                    }
                }
            }
            self::$new_storage[$k] = $storage;
            self::$storage[$k] = $storage;
        }
    }

    /**
     * @psalm-external-mutation-free
     */
    public function makeNew(string $fq_classlike_name_lc): void
    {
        self::$new_storage[$fq_classlike_name_lc] = self::$storage[$fq_classlike_name_lc];
    }

    /**
     * @psalm-external-mutation-free
     */
    public function create(string $fq_classlike_name): ClassLikeStorage
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        $storage = new ClassLikeStorage($fq_classlike_name);
        self::$storage[$fq_classlike_name_lc] = $storage;
        self::$new_storage[$fq_classlike_name_lc] = $storage;

        return $storage;
    }

    /**
     * @param string $file_path the file that declared the class-like, which keys its cache entry. Not the file of
     *                          the storage's stmt_location, which a stub moves.
     * @psalm-capabilities read-props|write-props|write-refs|read-globals|write-globals
     */
    public function remove(string $fq_classlike_name, string $file_path): void
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        $this->cache?->evict($fq_classlike_name_lc, $file_path);

        unset(self::$storage[$fq_classlike_name_lc]);
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function deleteAll(): void
    {
        self::$storage = [];
        self::$new_storage = [];
    }

    /**
     * @psalm-external-mutation-free
     */
    public static function populated(): void
    {
        self::$new_storage = [];
    }
}
