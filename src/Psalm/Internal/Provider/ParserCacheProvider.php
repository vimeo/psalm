<?php
namespace Psalm\Internal\Provider;

use const DIRECTORY_SEPARATOR;
use function error_log;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filemtime;
use function gettype;
use function igbinary_serialize;
use function igbinary_unserialize;
use function is_array;
use function is_dir;
use function is_readable;
use function is_writable;
use function json_decode;
use function json_encode;
use function md5;
use function mkdir;
use PhpParser;
use Psalm\Config;
use function scandir;
use function serialize;
use function touch;
use function unlink;
use function unserialize;

/**
 * @internal
 */
class ParserCacheProvider
{
    const FILE_HASHES = 'file_hashes_json';
    const PARSER_CACHE_DIRECTORY = 'php-parser';
    const FILE_CONTENTS_CACHE_DIRECTORY = 'file-caches';

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

    /**
     * @var bool
     */
    private $use_file_cache;

    /** @var bool */
    private $use_igbinary;

    public function __construct(Config $config, bool $use_file_cache = true)
    {
        $this->use_igbinary = $config->use_igbinary;
        $this->use_file_cache = $use_file_cache;
    }

    /**
     * @param  int      $file_modified_time
     * @param  string   $file_content_hash
     * @param  string   $file_path
     *
     * @return list<PhpParser\Node\Stmt>|null
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
                /** @var list<\PhpParser\Node\Stmt> */
                $stmts = igbinary_unserialize((string)file_get_contents($cache_location));
            } else {
                /** @var list<\PhpParser\Node\Stmt> */
                $stmts = unserialize((string)file_get_contents($cache_location));
            }

            return $stmts;
        }
    }

    /**
     * @param  string   $file_path
     *
     * @return list<PhpParser\Node\Stmt>|null
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
                /** @var list<\PhpParser\Node\Stmt> */
                return igbinary_unserialize((string)file_get_contents($cache_location)) ?: null;
            }

            /** @var list<\PhpParser\Node\Stmt> */
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
        if (!$this->use_file_cache) {
            return null;
        }

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
     * @param  list<PhpParser\Node\Stmt>        $stmts
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
     *
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
        if (!$this->use_file_cache) {
            return;
        }

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
     * @param float $start_time
     *
     * @return void
     */
    public function processSuccessfulRun()
    {
        $cache_directory = Config::getInstance()->getCacheDirectory();

        if (!$cache_directory) {
            return;
        }

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
