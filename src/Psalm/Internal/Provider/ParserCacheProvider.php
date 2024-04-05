<?php

namespace Psalm\Internal\Provider;

use JsonException;
use PhpParser;
use PhpParser\Node\Stmt;
use Psalm\Config;
use Psalm\Internal\Cache;
use RuntimeException;
use UnexpectedValueException;

use function clearstatcache;
use function error_log;
use function file_put_contents;
use function filemtime;
use function gettype;
use function hash;
use function is_array;
use function is_dir;
use function is_readable;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;
use function scandir;
use function touch;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;
use const LOCK_EX;
use const PHP_VERSION_ID;
use const SCANDIR_SORT_NONE;

/**
 * @internal
 */
class ParserCacheProvider
{
    private const FILE_HASHES = 'file_hashes_json';
    private const PARSER_CACHE_DIRECTORY = 'php-parser';
    private const FILE_CONTENTS_CACHE_DIRECTORY = 'file-caches';

    private Cache $cache;

    /**
     * A map of filename hashes to contents hashes
     *
     * @var array<string, string>|null
     */
    protected ?array $existing_file_content_hashes = null;

    /**
     * A map of recently-added filename hashes to contents hashes
     *
     * @var array<string, string>
     */
    protected array $new_file_content_hashes = [];

    private bool $use_file_cache;

    public function __construct(Config $config, bool $use_file_cache = true)
    {
        $this->cache = new Cache($config);
        $this->use_file_cache = $use_file_cache;
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadStatementsFromCache(
        string $file_path,
        int $file_modified_time,
        string $file_content_hash
    ): ?array {
        if (!$this->use_file_cache) {
            return null;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::PARSER_CACHE_DIRECTORY);

        $file_cache_key = $this->getParserCacheKey($file_path);

        $file_content_hashes = $this->new_file_content_hashes + $this->getExistingFileContentHashes();

        if (isset($file_content_hashes[$file_cache_key])
            && $file_content_hash === $file_content_hashes[$file_cache_key]
            && is_readable($cache_location)
            && filemtime($cache_location) > $file_modified_time
        ) {
            $stmts = $this->cache->getItem($cache_location);

            if (is_array($stmts)) {
                /** @var list<Stmt> $stmts */
                return $stmts;
            }
        }

        return null;
    }

    /**
     * @return list<PhpParser\Node\Stmt>|null
     */
    public function loadExistingStatementsFromCache(string $file_path): ?array
    {
        if (!$this->use_file_cache) {
            return null;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::PARSER_CACHE_DIRECTORY);

        if (is_readable($cache_location)) {
            $stmts = $this->cache->getItem($cache_location);

            if (is_array($stmts)) {
                /** @var list<Stmt> $stmts */
                return $stmts;
            }
        }

        return null;
    }

    public function loadExistingFileContentsFromCache(string $file_path): ?string
    {
        if (!$this->use_file_cache) {
            return null;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::FILE_CONTENTS_CACHE_DIRECTORY);

        $cache_item = $this->cache->getItem($cache_location);

        if (!is_string($cache_item)) {
            return null;
        }

        return $cache_item;
    }

    /**
     * @return array<string, string>
     */
    private function getExistingFileContentHashes(): array
    {
        if (!$this->use_file_cache) {
            return [];
        }

        if ($this->existing_file_content_hashes === null) {
            $root_cache_directory = $this->cache->getCacheDirectory();
            $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;

            if (!$root_cache_directory) {
                throw new UnexpectedValueException('No cache directory defined');
            }
            if (is_readable($file_hashes_path)) {
                $hashes_encoded = Providers::safeFileGetContents($file_hashes_path);

                if (!$hashes_encoded) {
                    error_log('Unexpected value when loading from file content hashes');
                    $this->existing_file_content_hashes = [];

                    return [];
                }

                try {
                    $hashes_decoded = json_decode($hashes_encoded, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    error_log('Failed to parse hashes: ' . $e->getMessage());
                    $this->existing_file_content_hashes = [];

                    return [];
                }

                if (!is_array($hashes_decoded)) {
                    error_log('Unexpected value ' . gettype($hashes_decoded));
                    $this->existing_file_content_hashes = [];

                    return [];
                }

                /** @var array<string, string> $hashes_decoded */
                $this->existing_file_content_hashes = $hashes_decoded;
            } else {
                $this->existing_file_content_hashes = [];
            }

            if (!is_readable($file_hashes_path)) {
                // might not exist yet
                $this->existing_file_content_hashes = [];
                return $this->existing_file_content_hashes;
            }

            $hashes_encoded = Providers::safeFileGetContents($file_hashes_path);
            if (!$hashes_encoded) {
                throw new UnexpectedValueException('File content hashes should be in cache');
            }

            /** @psalm-suppress MixedAssignment */
            $hashes_decoded = json_decode($hashes_encoded, true);

            if (!is_array($hashes_decoded)) {
                throw new UnexpectedValueException(
                    'File content hashes are of invalid type ' . gettype($hashes_decoded),
                );
            }

            /** @var array<string, string> $hashes_decoded */
            $this->existing_file_content_hashes = $hashes_decoded;
        }

        return $this->existing_file_content_hashes;
    }

    /**
     * @param  list<PhpParser\Node\Stmt>        $stmts
     */
    public function saveStatementsToCache(
        string $file_path,
        string $file_content_hash,
        array $stmts,
        bool $touch_only
    ): void {
        $cache_location = $this->getCacheLocationForPath($file_path, self::PARSER_CACHE_DIRECTORY, !$touch_only);

        if ($touch_only) {
            touch($cache_location);
        } else {
            $this->cache->saveItem($cache_location, $stmts);

            $file_cache_key = $this->getParserCacheKey($file_path);
            $this->new_file_content_hashes[$file_cache_key] = $file_content_hash;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getNewFileContentHashes(): array
    {
        return $this->new_file_content_hashes;
    }

    /**
     * @param array<string, string> $file_content_hashes
     */
    public function addNewFileContentHashes(array $file_content_hashes): void
    {
        $this->new_file_content_hashes = $file_content_hashes + $this->new_file_content_hashes;
    }

    public function saveFileContentHashes(): void
    {
        if (!$this->use_file_cache) {
            return;
        }

        $root_cache_directory = $this->cache->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        // directory was removed most likely due to a race condition
        // with other psalm instances that were manually started at
        // the same time
        clearstatcache(true, $root_cache_directory);
        if (!is_dir($root_cache_directory)) {
            return;
        }

        $file_content_hashes = $this->new_file_content_hashes + $this->getExistingFileContentHashes();

        $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;

        file_put_contents(
            $file_hashes_path,
            json_encode($file_content_hashes, JSON_THROW_ON_ERROR),
            LOCK_EX,
        );
    }

    public function cacheFileContents(string $file_path, string $file_contents): void
    {
        if (!$this->use_file_cache) {
            return;
        }

        $cache_location = $this->getCacheLocationForPath($file_path, self::FILE_CONTENTS_CACHE_DIRECTORY, true);
        $this->cache->saveItem($cache_location, $file_contents);
    }

    public function deleteOldParserCaches(float $time_before): int
    {
        $cache_directory = $this->cache->getCacheDirectory();

        $this->existing_file_content_hashes = null;
        $this->new_file_content_hashes = [];

        if (!$cache_directory) {
            return 0;
        }

        $removed_count = 0;

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            $directory_files = scandir($cache_directory, SCANDIR_SORT_NONE);

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

    private function getParserCacheKey(string $file_path): string
    {
        if (PHP_VERSION_ID >= 8_01_00) {
            $hash = hash('xxh128', $file_path);
        } else {
            $hash = hash('md4', $file_path);
        }

        return $hash . ($this->cache->use_igbinary ? '-igbinary' : '') . '-r';
    }


    private function getCacheLocationForPath(
        string $file_path,
        string $subdirectory,
        bool $create_directory = false
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
