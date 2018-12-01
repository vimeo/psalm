<?php
namespace Psalm\Internal\Provider;

use PhpParser;
use Psalm\Config;

/**
 * @internal
 */
class ParserCacheProvider
{
    const FILE_HASHES = 'file_hashes_json';
    const PARSER_CACHE_DIRECTORY = 'php-parser';
    const FILE_CONTENTS_CACHE_DIRECTORY = 'file-caches';
    const GOOD_RUN_NAME = 'good_run';

    /**
     * @var int|null
     */
    private $last_good_run = null;

    /**
     * A map of filename hashes to contents hashes
     *
     * @var array<string, string>|null
     */
    private $existing_file_content_hashes = null;

    /**
     * A map of recently-added filename hashes to contents hashes
     *
     * @var array<string, string>
     */
    private $new_file_content_hashes = [];

    /** @var bool */
    private $use_igbinary;

    public function __construct(Config $config)
    {
        $this->use_igbinary = $config->use_igbinary;
    }

    /**
     * @param  int      $file_modified_time
     * @param  string   $file_content_hash
     * @param  string   $file_path
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     *
     * @psalm-suppress UndefinedFunction
     */
    public function loadStatementsFromCache($file_path, $file_modified_time, $file_content_hash)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $file_cache_key = $this->getParserCacheKey(
            $file_path
        );

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        $file_content_hashes = $this->new_file_content_hashes + $this->getExistingFileContentHashes();

        $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $file_cache_key;

        if (isset($file_content_hashes[$file_cache_key])
            && $file_content_hash === $file_content_hashes[$file_cache_key]
            && is_readable($cache_location)
            && filemtime($cache_location) > $file_modified_time
        ) {
            if ($this->use_igbinary) {
                /** @var array<int, \PhpParser\Node\Stmt> */
                $stmts = igbinary_unserialize((string)file_get_contents($cache_location));
            } else {
                /** @var array<int, \PhpParser\Node\Stmt> */
                $stmts = unserialize((string)file_get_contents($cache_location));
            }

            return $stmts;
        }
    }

    /**
     * @param  string   $file_path
     *
     * @return array<int, PhpParser\Node\Stmt>|null
     *
     * @psalm-suppress UndefinedFunction
     */
    public function loadExistingStatementsFromCache($file_path)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $file_cache_key = $this->getParserCacheKey(
            $file_path
        );

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::PARSER_CACHE_DIRECTORY;

        $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $file_cache_key;

        if (is_readable($cache_location)) {
            if ($this->use_igbinary) {
                /** @var array<int, \PhpParser\Node\Stmt> */
                return igbinary_unserialize((string)file_get_contents($cache_location)) ?: null;
            }

            /** @var array<int, \PhpParser\Node\Stmt> */
            return unserialize((string)file_get_contents($cache_location)) ?: null;
        }
    }

    /**
     * @param  string   $file_path
     *
     * @return string|null
     */
    public function loadExistingFileContentsFromCache($file_path)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $file_cache_key = $this->getParserCacheKey(
            $file_path
        );

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_CONTENTS_CACHE_DIRECTORY;

        $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $file_cache_key;

        if (is_readable($cache_location)) {
            return file_get_contents($cache_location);
        }
    }

    /**
     * @return array<string, string>
     */
    private function getExistingFileContentHashes()
    {
        $config = Config::getInstance();
        $root_cache_directory = $config->getCacheDirectory();

        if ($this->existing_file_content_hashes === null) {
            $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;

            if ($root_cache_directory && is_readable($file_hashes_path)) {
                $hashes_encoded = (string) file_get_contents($file_hashes_path);

                if (!$hashes_encoded) {
                    error_log('Unexpected value when loading from file content hashes');
                    $this->existing_file_content_hashes = [];
                    return [];
                }

                /** @psalm-suppress MixedAssignment */
                $hashes_decoded = json_decode($hashes_encoded, true);

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
        }

        return $this->existing_file_content_hashes;
    }

    /**
     * @param  string                           $file_path
     * @param  string                           $file_content_hash
     * @param  array<int, PhpParser\Node\Stmt>  $stmts
     * @param  bool                             $touch_only
     *
     * @return void
     *
     * @psalm-suppress UndefinedFunction
     */
    public function saveStatementsToCache($file_path, $file_content_hash, array $stmts, $touch_only)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $file_cache_key = $this->getParserCacheKey(
            $file_path
        );

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

            $this->new_file_content_hashes[$file_cache_key] = $file_content_hash;
        }
    }

    /**
     * @return array<string, string>
     */
    public function getNewFileContentHashes()
    {
        return $this->new_file_content_hashes;
    }

    /**
     * @param array<string, string> $file_content_hashes
     * @return void
     */
    public function addNewFileContentHashes(array $file_content_hashes)
    {
        $this->new_file_content_hashes = $file_content_hashes + $this->new_file_content_hashes;
    }

    /**
     * @return void
     */
    public function saveFileContentHashes()
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $file_content_hashes = $this->new_file_content_hashes + $this->getExistingFileContentHashes();

        $file_hashes_path = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_HASHES;

        file_put_contents(
            $file_hashes_path,
            json_encode($file_content_hashes)
        );
    }

    /**
     * @param  string  $file_path
     * @param  string  $file_contents
     *
     * @return void
     */
    public function cacheFileContents($file_path, $file_contents)
    {
        $root_cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$root_cache_directory) {
            return;
        }

        $file_cache_key = $this->getParserCacheKey(
            $file_path
        );

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::FILE_CONTENTS_CACHE_DIRECTORY;

        $cache_location = $parser_cache_directory . DIRECTORY_SEPARATOR . $file_cache_key;

        if (!is_dir($parser_cache_directory)) {
            mkdir($parser_cache_directory, 0777, true);
        }

        file_put_contents($cache_location, $file_contents);
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

            if (file_exists($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME)) {
                $this->last_good_run = filemtime($cache_directory . DIRECTORY_SEPARATOR . self::GOOD_RUN_NAME);
            } else {
                $this->last_good_run = 0;
            }
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
                $hash_file_name = $cache_directory . DIRECTORY_SEPARATOR . $this->getParserCacheKey($file_name);

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
     *
     * @return string
     */
    private function getParserCacheKey($file_name)
    {
        return md5($file_name) . ($this->use_igbinary ? '-igbinary' : '') . '-r';
    }
}
