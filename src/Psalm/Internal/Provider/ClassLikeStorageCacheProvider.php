<?php

declare(strict_types=1);

namespace Psalm\Internal\Provider;

use Psalm\Config;
use Psalm\Internal\Cache;
use Psalm\Storage\ClassLikeStorage;
use RuntimeException;
use UnexpectedValueException;

use function array_merge;
use function dirname;
use function file_exists;
use function filemtime;
use function hash;
use function is_dir;
use function is_null;
use function mkdir;
use function strtolower;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
class ClassLikeStorageCacheProvider
{
    private readonly Cache $cache;

    private string $modified_timestamps = '';

    private const CLASS_CACHE_DIRECTORY = 'class_cache';

    public function __construct(Config $config)
    {
        $this->cache = new Cache($config);

        $storage_dir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR;

        $dependent_files = [
            $storage_dir . 'FileStorage.php',
            $storage_dir . 'FunctionLikeStorage.php',
            $storage_dir . 'ClassLikeStorage.php',
            $storage_dir . 'MethodStorage.php',
        ];

        if ($config->eventDispatcher->hasAfterClassLikeVisitHandlers()) {
            $dependent_files = array_merge($dependent_files, $config->plugin_paths);
        }

        foreach ($dependent_files as $dependent_file_path) {
            if (!file_exists($dependent_file_path)) {
                throw new UnexpectedValueException($dependent_file_path . ' must exist');
            }

            $this->modified_timestamps .= ' ' . (int) filemtime($dependent_file_path);
        }

        $this->modified_timestamps .= $config->computeHash();
    }

    public function writeToCache(ClassLikeStorage $storage, string $file_path, string $file_contents): void
    {
        $fq_classlike_name_lc = strtolower($storage->name);

        $storage->hash = $this->getCacheHash($file_path, $file_contents);

        // check if we have it in cache already
        $cached_value = $this->loadFromCache($fq_classlike_name_lc, $file_path);
        if (!is_null($cached_value) && $cached_value->hash === $storage->hash) {
            return;
        }

        $cache_location = $this->getCacheLocationForClass($fq_classlike_name_lc, $file_path, true);
        $this->cache->saveItem($cache_location, $storage);
    }

    /**
     * @param lowercase-string $fq_classlike_name_lc
     */
    public function getLatestFromCache(
        string $fq_classlike_name_lc,
        ?string $file_path,
        ?string $file_contents,
    ): ClassLikeStorage {
        $cached_value = $this->loadFromCache($fq_classlike_name_lc, $file_path);

        if (!$cached_value) {
            throw new UnexpectedValueException($fq_classlike_name_lc . ' should be in cache');
        }

        $cache_hash = $this->getCacheHash($file_path, $file_contents);

        /** @psalm-suppress TypeDoesNotContainType */
        if (@$cached_value::class === '__PHP_Incomplete_Class'
            || $cache_hash !== $cached_value->hash
        ) {
            $this->cache->deleteItem($this->getCacheLocationForClass($fq_classlike_name_lc, $file_path));

            throw new UnexpectedValueException($fq_classlike_name_lc . ' should not be outdated');
        }

        return $cached_value;
    }

    private function getCacheHash(?string $_unused_file_path, ?string $file_contents): string
    {
        $data = $file_contents ?: $this->modified_timestamps;
        return hash('xxh128', $data);
    }

    /**
     * @param lowercase-string $fq_classlike_name_lc
     */
    private function loadFromCache(string $fq_classlike_name_lc, ?string $file_path): ?ClassLikeStorage
    {
        $storage = $this->cache->getItem($this->getCacheLocationForClass($fq_classlike_name_lc, $file_path));
        if ($storage instanceof ClassLikeStorage) {
            return $storage;
        }

        return null;
    }

    /**
     * @param lowercase-string $fq_classlike_name_lc
     */
    private function getCacheLocationForClass(
        string $fq_classlike_name_lc,
        ?string $file_path,
        bool $create_directory = false,
    ): string {
        $root_cache_directory = $this->cache->getCacheDirectory();

        if (!$root_cache_directory) {
            throw new UnexpectedValueException('No cache directory defined');
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::CLASS_CACHE_DIRECTORY;

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

        $data = $file_path ? strtolower($file_path) . ' ' : '';
        $data .= $fq_classlike_name_lc;
        $file_path_sha = hash('xxh128', $data);

        return $parser_cache_directory
            . DIRECTORY_SEPARATOR
            . $file_path_sha
            . ($this->cache->use_igbinary ? '-igbinary' : '');
    }
}
