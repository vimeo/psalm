<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use PhpParser;
use PhpParser\Node\Stmt;
use Psalm\Config;
use Psalm\Internal\Cache;
use RuntimeException;
use UnexpectedValueException;

use function assert;
use function filemtime;
use function hash;
use function is_array;
use function is_dir;
use function is_string;
use function mkdir;
use function scandir;
use function touch;

use const DIRECTORY_SEPARATOR;
use const SCANDIR_SORT_NONE;

/**
 * @internal
 */
class ParserCacheProvider
{
    private const PARSER_CACHE_DIRECTORY = 'php-parser';
    private const FILE_CONTENTS_CACHE_DIRECTORY = 'file-caches';

    private readonly Cache $stmtCache;
    private readonly Cache $fileCache;

    /**
     * A map of filename hashes to contents hashes
     *
     * @var array<string, string>|null
     */
    protected ?array $existing_stmt_hashes = null;

    /**
     * A map of recently-added filename hashes to contents hashes
     *
     * @var array<string, string>
     */
    protected array $new_stmt_hashes = [];

    public function __construct(Config $config)
    {
        $this->stmtCache = new Cache($config, self::PARSER_CACHE_DIRECTORY);
        $this->fileCache = new Cache($config, self::FILE_CONTENTS_CACHE_DIRECTORY);
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache(
        string $file_path,
        int $file_modified_time,
        string $file_content_hash,
    ): ?array {
        $file_cache_key = hash('xxh128', $file_path);

        $existing = $this->new_stmt_hashes[$file_cache_key]
            ?? $this->getExistingStmtHashes()[$file_cache_key]
            ?? null;

        if ($file_content_hash !== $existing) {
            return null;
        }
        $stmts = $this->stmtCache->getItem($file_cache_key, $file_modified_time);

        if (!is_array($stmts)) {
            return null;
        }

        /** @var list<Stmt> $stmts */
        return $stmts;
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadExistingStatementsFromCache(string $file_path): ?array
    {
        $stmts = $this->stmtCache->getItem(hash('xxh128', $file_path));

        if (is_array($stmts)) {
            /** @var list<Stmt> $stmts */
            return $stmts;
        }

        return null;
    }

    public function loadExistingFileContentsFromCache(string $file_path): ?string
    {
        $cache_item = $this->fileCache->getItem(hash('xxh128', $file_path));

        if (!is_string($cache_item)) {
            return null;
        }

        return $cache_item;
    }

    public function cacheFileContents(string $file_path, string $file_contents): void
    {
        $this->fileCache->saveItem(hash('xxh128', $file_path), $file_contents);
    }

    /**
     * @return array<string, string>
     */
    private function getExistingStmtHashes(): array
    {
        if ($this->existing_stmt_hashes !== null) {
            return $this->existing_stmt_hashes;
        }
        return $this->existing_stmt_hashes = $this->stmtCache->getItem('idx') ?? [];
    }

    /**
     * @param  list<PhpParser\Node\Stmt>        $stmts
     */
    public function saveStatementsToCache(
        string $file_path,
        string $file_content_hash,
        array $stmts,
        bool $touch_only,
    ): void {
        $cache_location = $this->getCacheLocationForPath($file_path, self::PARSER_CACHE_DIRECTORY, !$touch_only);

        if ($touch_only) {
            touch($cache_location);
        } else {
            $this->cache->saveItem($cache_location, $stmts);

            $file_cache_key = $this->getParserCacheKey($file_path);
            $this->new_stmt_hashes[$file_cache_key] = $file_content_hash;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getNewFileContentHashes(): array
    {
        return $this->new_stmt_hashes;
    }

    /**
     * @param array<string, string> $stmt_hashes
     */
    public function addNewFileContentHashes(array $stmt_hashes): void
    {
        $this->new_stmt_hashes += $stmt_hashes;
    }

    public function saveFileContentHashes(): void
    {
        // Load
        $this->getExistingStmtHashes();

        $this->existing_stmt_hashes += $this->new_stmt_hashes;

        $this->stmtCache->saveItem('idx', $this->existing_stmt_hashes);
    }

    public function deleteOldParserCaches(float $time_before): int
    {
        $cache_directory = $this->cache->getCacheDirectory();

        $this->existing_stmt_hashes = null;
        $this->new_stmt_hashes = [];

        if (!$cache_directory) {
            return 0;
        }

        $removed_count = 0;

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            $directory_files = scandir($cache_directory, SCANDIR_SORT_NONE);
            assert($directory_files !== false);

            foreach ($directory_files as $directory_file) {
                $full_path = $cache_directory . DIRECTORY_SEPARATOR . $directory_file;

                if ($directory_file[0] === '.') {
                    continue;
                }

                if (filemtime($full_path) < $time_before) {
                    $this->cache->deleteItem($full_path);
                    ++$removed_count;
                }
            }
        }

        return $removed_count;
    }

    private function getCacheLocationForPath(
        string $file_path,
        string $subdirectory,
        bool $create_directory = false,
    ): string {
        $root_cache_directory = $this->cache->getCacheDirectory();

        if (!$root_cache_directory) {
            throw new UnexpectedValueException('No cache directory defined');
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . $subdirectory;

        if ($create_directory && !is_dir($parser_cache_directory)) {
            try {
                if (mkdir($parser_cache_directory, 0777, true) === false) {
                    // any other error than directory already exists/permissions issue
                    throw new RuntimeException(
                        'Failed to create ' . $parser_cache_directory . ' cache directory for unknown reasons',
                    );
                }
            } catch (RuntimeException $e) {
                // Race condition (#4483)
                if (!is_dir($parser_cache_directory)) {
                    // rethrow the error with default message
                    // it contains the reason why creation failed
                    throw $e;
                }
            }
        }

        return $parser_cache_directory
               . DIRECTORY_SEPARATOR
               . $this->getParserCacheKey($file_path);
    }
}
