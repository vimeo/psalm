<?php
namespace Psalm\Provider;

use PhpParser;
use Psalm\Config;

class CacheProvider
{
    const FILE_HASHES = 'file_hashes';
    const PARSER_CACHE_DIRECTORY = 'php-parser';
    const GOOD_RUN_NAME = 'good_run';

    /**
     * @var int|null
     */
    protected static $last_good_run = null;

    /**
     * A map of filename hashes to contents hashes
     *
     * @var array<string, string>|null
     */
    private static $file_content_hashes = null;

    /**
     * @param  string   $file_path
     * @param  string   $file_content_hash
     * @param  string   $file_cache_key
     * @return array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt>|null
     */
    public static function loadStatementsFromCache($file_path, $file_content_hash, $file_cache_key)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        $cache_location = null;

        $file_content_hashes = self::getFileContentHashes();

        $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $file_cache_key;

        if (isset($file_content_hashes[$file_cache_key]) &&
            $file_content_hash === $file_content_hashes[$file_cache_key] &&
            is_readable($cache_location) &&
            filemtime($cache_location) > filemtime($file_path)
        ) {
            /** @var array<int, \PhpParser\Node\Stmt> */
            return unserialize((string)file_get_contents($cache_location));
        }
    }

    /**
     * @return array<string, string>
     */
    private static function getFileContentHashes()
    {
        $config = Config::getInstance();
        $root_cache_directory = $config->getCacheDirectory();

        if (self::$file_content_hashes === null || !$config->cache_file_hashes_during_run) {
            $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;
            /** @var array<string, string> */
            self::$file_content_hashes =
                $root_cache_directory && is_readable($file_hashes_path)
                    ? unserialize((string)file_get_contents($file_hashes_path))
                    : [];
        }

        return self::$file_content_hashes;
    }

    /**
     * @param  string                           $file_cache_key
     * @param  string                           $file_content_hash
     * @param  array<int, PhpParser\Node\Expr|PhpParser\Node\Stmt>  $stmts
     * @param  bool                             $touch_only
     * @return void
     */
    public static function saveStatementsToCache($file_cache_key, $file_content_hash, array $stmts, $touch_only)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $file_cache_key;

        if ($touch_only) {
            touch($cache_location);
        } else {
            if (!is_dir($parser_cache_directory)) {
                mkdir($parser_cache_directory, 0777, true);
            }

            file_put_contents($cache_location, serialize($stmts));

            self::$file_content_hashes[$file_cache_key] = $file_content_hash;

            file_put_contents(
                $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES,
                serialize(self::$file_content_hashes)
            );
        }
    }

    /**
     * @return bool
     */
    public static function canDiffFiles()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        return $cache_directory && file_exists($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME);
    }

    /**
     * @param int $start_time
     * @return void
     */
    public static function processSuccessfulRun($start_time)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

        touch($run_cache_location, $start_time);

        FileReferenceProvider::removeDeletedFilesFromReferences();

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            /** @var array<string> */
            $directory_files = scandir($cache_directory);

            foreach ($directory_files as $directory_file) {
                $full_path = $cache_directory . DIRECTORY_SEPARATOR . $directory_file;

                if ($directory_file[0] === '.') {
                    continue;
                }

                touch($full_path);
            }
        }
    }

    /**
     * @return int
     */
    public static function getLastGoodRun()
    {
        if (self::$last_good_run === null) {
            $cache_directory = Config::getInstance()->getCacheDirectory();

            self::$last_good_run = filemtime($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME) ?: 0;
        }

        return self::$last_good_run;
    }

    /**
     * @param  float $time_before
     * @return int
     */
    public static function deleteOldParserCaches($time_before)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            return 0;
        }

        $removed_count = 0;

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            /** @var array<string> */
            $directory_files = scandir($cache_directory);

            foreach ($directory_files as $directory_file) {
                $full_path = $cache_directory . DIRECTORY_SEPARATOR . $directory_file;

                if ($directory_file[0] === '.') {
                    continue;
                }

                if (filemtime($full_path) < $time_before && is_writable($full_path)) {
                    unlink($full_path);
                    $removed_count++;
                }
            }
        }

        return $removed_count;
    }

    /**
     * @param  array<string>    $file_names
     * @param  int              $min_time
     * @return void
     */
    public static function touchParserCaches(array $file_names, $min_time)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            foreach ($file_names as $file_name) {
                $hash_file_name = $cache_directory . DIRECTORY_SEPARATOR . self::getParserCacheKey($file_name);

                if (file_exists($hash_file_name)) {
                    if (filemtime($hash_file_name) < $min_time) {
                        touch($hash_file_name, $min_time);
                    }
                }
            }
        }
    }

    /**
     * @param  string   $file_name
     * @return string
     */
    public static function getParserCacheKey($file_name)
    {
        return md5($file_name);
    }
}
