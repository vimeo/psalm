<?php
namespace Psalm\Provider;

use Psalm\Config;
use Psalm\Storage\ClassLikeStorage;

class ClassLikeStorageCacheProvider
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var string
     */
    private $modified_timestamps = '';

    const CLASS_CACHE_DIRECTORY = 'class_cache';

    public function __construct(Config $config)
    {
        $this->config = $config;

        $storage_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Storage' . DIRECTORY_SEPARATOR;

        $dependent_files = [
            $storage_dir . 'ClassLikeStorage.php',
            $storage_dir . 'FunctionLikeStorage.php',
            $storage_dir . 'MethodStorage.php',
        ];

        foreach ($dependent_files as $dependent_file_path) {
            if (!file_exists($dependent_file_path)) {
                throw new \UnexpectedValueException($dependent_file_path . ' must exist');
            }

            $this->modified_timestamps .= ' ' . filemtime($dependent_file_path);
        }
    }

    /**
     * @param  string|null $file_path
     * @param  string|null $file_contents
     *
     * @return void
     */
    public function writeToCache(ClassLikeStorage $storage, $file_path, $file_contents)
    {
        $fq_classlike_name_lc = strtolower($storage->name);
        $cache_location = $this->getCacheLocationForClass($fq_classlike_name_lc, true);
        $storage->hash = $this->getCacheHash($file_path, $file_contents);

        if ($this->config->use_igbinary) {
            file_put_contents($cache_location, igbinary_serialize($storage));
        } else {
            file_put_contents($cache_location, serialize($storage));
        }
    }

    /**
     * @param  string  $fq_classlike_name_lc
     * @param  string|null $file_path
     * @param  string|null $file_contents
     *
     * @return ClassLikeStorage
     */
    public function getLatestFromCache($fq_classlike_name_lc, $file_path, $file_contents)
    {
        $cached_value = $this->loadFromCache($fq_classlike_name_lc);

        if (!$cached_value) {
            throw new \UnexpectedValueException('Should be in cache');
        }

        $cache_hash = $this->getCacheHash($file_path, $file_contents);

        if ($cache_hash !== $cached_value->hash) {
            unlink($this->getCacheLocationForClass($fq_classlike_name_lc));

            throw new \UnexpectedValueException('Should not be outdated');
        }

        return $cached_value;
    }

    /**
     * @param  string|null $file_path
     * @param  string|null $file_contents
     *
     * @return string
     */
    private function getCacheHash($file_path, $file_contents)
    {
        return sha1($file_path . ' ' . $file_contents . $this->modified_timestamps);
    }

    /**
     * @param  string  $fq_classlike_name_lc
     *
     * @return ClassLikeStorage|null
     */
    private function loadFromCache($fq_classlike_name_lc)
    {
        $cache_location = $this->getCacheLocationForClass($fq_classlike_name_lc);

        if (file_exists($cache_location)) {
            if ($this->config->use_igbinary) {
                /** @var ClassLikeStorage */
                return igbinary_unserialize((string)file_get_contents($cache_location)) ?: null;
            } else {
                /** @var ClassLikeStorage */
                return unserialize((string)file_get_contents($cache_location)) ?: null;
            }
        }

        return null;
    }

    /**
     * @param  string  $fq_classlike_name_lc
     * @param  bool $create_directory
     *
     * @return string
     */
    private function getCacheLocationForClass($fq_classlike_name_lc, $create_directory = false)
    {
        $root_cache_directory = $this->config->getCacheDirectory();

        if (!$root_cache_directory) {
            throw new \UnexpectedValueException('No cache directory defined');
        }

        $parser_cache_directory = $root_cache_directory . DIRECTORY_SEPARATOR . self::CLASS_CACHE_DIRECTORY;

        if ($create_directory && !is_dir($parser_cache_directory)) {
            mkdir($parser_cache_directory, 0777, true);
        }

        return $parser_cache_directory
            . DIRECTORY_SEPARATOR
            . sha1($fq_classlike_name_lc)
            . ($this->config->use_igbinary ? '-igbinary' : '');
    }
}
