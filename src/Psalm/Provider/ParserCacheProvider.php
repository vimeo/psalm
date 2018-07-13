<?php
namespace Psalm\Provider;

use PhpParser;
use Psalm\Config;

class ParserCacheProvider
{
    const FILE_HASHES = 'file_hashes_json';
    const PARSER_CACHE_DIRECTORY = 'php-parser';
    const GOOD_RUN_NAME = 'good_run';

    /**
     * @var int|null
     */
    protected $last_good_run = null;

    /**
     * A map of filename hashes to contents hashes
     *
     * @var array<string, string>|null
     */
    protected $file_content_hashes = null;

    /** @var bool */
    public $use_igbinary = false;

    /**
     * @param  int      $file_modified_time
     * @param  string   $file_content_hash
     * @param  string   $file_cache_key
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     *
     * @psalm-suppress UndefinedFunction
     */
    public function loadStatementsFromCache($file_modified_time, $file_content_hash, $file_cache_key)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        $file_content_hashes = $this->getFileContentHashes();

        $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $file_cache_key;

        if (isset($file_content_hashes[$file_cache_key]) &&
            $file_content_hash === $file_content_hashes[$file_cache_key] &&
            is_readable($cache_location) &&
            filemtime($cache_location) > $file_modified_time
        ) {
            if ($this->use_igbinary) {
                /** @var array<int, \PhpParser\Node\Stmt> */
                return igbinary_unserialize((string)file_get_contents($cache_location)) ?: null;
            }

            /** @var array<int, \PhpParser\Node\Stmt> */
            return unserialize((string)file_get_contents($cache_location)) ?: null;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getFileContentHashes()
    {
        $config = Config::getInstance();
        $root_cache_directory = $config->getCacheDirectory();

        if ($this->file_content_hashes === null || !$config->cache_file_hashes_during_run) {
            $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;
            /** @var array<string, string> */
            $this->file_content_hashes =
                $root_cache_directory && is_readable($file_hashes_path)
                    ? json_decode((string)file_get_contents($file_hashes_path), true)
                    : [];
        }

        return $this->file_content_hashes;
    }

    /**
     * @param  string                           $file_cache_key
     * @param  string                           $file_content_hash
     * @param  array<int, PhpParser\Node\Stmt>  $stmts
     * @param  bool                             $touch_only
     *
     * @return void
     *
     * @psalm-suppress UndefinedFunction
     */
    public function saveStatementsToCache($file_cache_key, $file_content_hash, array $stmts, $touch_only)
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

            if ($this->use_igbinary) {
                file_put_contents($cache_location, igbinary_serialize($stmts));
            } else {
                file_put_contents($cache_location, serialize($stmts));
            }

            $this->file_content_hashes[$file_cache_key] = $file_content_hash;

            file_put_contents(
                $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES,
                json_encode($this->file_content_hashes)
            );
        }
    }

    /**
     * @return bool
     */
    public function canDiffFiles()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        return $cache_directory && file_exists($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME);
    }

    /**
     * @param float $start_time
     *
     * @return void
     */
    public function processSuccessfulRun($start_time)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $run_cache_location = $cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME;

        touch($run_cache_location, (int)$start_time);

        FileReferenceProvider::removeDeletedFilesFromReferences();

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
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
    public function getLastGoodRun()
    {
        if ($this->last_good_run === null) {
            $cache_directory = Config::getInstance()->getCacheDirectory();

            $this->last_good_run = filemtime($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME) ?: 0;
        }

        return $this->last_good_run;
    }

    /**
     * @param  float $time_before
     *
     * @return int
     */
    public function deleteOldParserCaches($time_before)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if ($cache_directory) {
            return 0;
        }

        $removed_count = 0;

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            $directory_files = scandir($cache_directory);

            foreach ($directory_files as $directory_file) {
                $full_path = $cache_directory . DIRECTORY_SEPARATOR . $directory_file;

                if ($directory_file[0] === '.') {
                    continue;
                }

                if (filemtime($full_path) < $time_before && is_writable($full_path)) {
                    unlink($full_path);
                    ++$removed_count;
                }
            }
        }

        return $removed_count;
    }

    /**
     * @param  array<string>    $file_names
     * @param  int              $min_time
     *
     * @return void
     */
    public function touchParserCaches(array $file_names, $min_time)
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

        $cache_directory .= DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        if (is_dir($cache_directory)) {
            foreach ($file_names as $file_name) {
                $hash_file_name =
                    $cache_directory . DIRECTORY_SEPARATOR . $this->getParserCacheKey($file_name, $this->use_igbinary);

                if (file_exists($hash_file_name)) {
                    if (filemtime($hash_file_name) < $min_time) {
                        touch($hash_file_name, $min_time);
                    }
                }
            }
        }
    }

    /**
     * @param  string  $file_name
     * @param  bool $use_igbinary
     *
     * @return string
     */
    public static function getParserCacheKey($file_name, $use_igbinary)
    {
        return md5($file_name) . ($use_igbinary ? '-igbinary' : '') . '-r';
    }
}
