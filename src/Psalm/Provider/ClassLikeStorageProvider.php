<?php
namespace Psalm\Provider;

use Psalm\Storage\ClassLikeStorage;

class ClassLikeStorageProvider
{
    /**
     * Storing this statically is much faster (at least in PHP 7.2.1)
     *
     * @var array<string, ClassLikeStorage>
     */
    private static $storage = [];

    /**
     * @var array<string, ClassLikeStorage>
     */
    private static $new_storage = [];

    /**
     * @var ?ClassLikeStorageCacheProvider
     */
    public $cache;

    public function __construct(ClassLikeStorageCacheProvider $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return ClassLikeStorage
     */
    public function get($fq_classlike_name)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        if (!isset(self::$storage[$fq_classlike_name_lc])) {
            throw new \InvalidArgumentException('Could not get class storage for ' . $fq_classlike_name);
        }

        return self::$storage[$fq_classlike_name_lc];
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return bool
     */
    public function has($fq_classlike_name)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        return isset(self::$storage[$fq_classlike_name_lc]);
    }

    /**
     * @param  string  $fq_classlike_name
     * @param  string|null $file_path
     * @param  string|null $file_contents
     *
     * @return ClassLikeStorage
     */
    public function exhume($fq_classlike_name, $file_path, $file_contents)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        if (isset(self::$storage[$fq_classlike_name_lc])) {
            return self::$storage[$fq_classlike_name_lc];
        }

        if (!$this->cache) {
            throw new \LogicException('Cannot exhume when there’s no cache');
        }

        $cached_value = $this->cache->getLatestFromCache($fq_classlike_name_lc, $file_path, $file_contents);

        self::$storage[$fq_classlike_name_lc] =  $cached_value;
        self::$new_storage[$fq_classlike_name_lc] =  $cached_value;

        return $cached_value;
    }

    /**
     * @return array<string, ClassLikeStorage>
     */
    public function getAll()
    {
        return self::$storage;
    }

    /**
     * @return array<string, ClassLikeStorage>
     */
    public function getNew()
    {
        return self::$new_storage;
    }

    /**
     * @param array<string, ClassLikeStorage> $more
     * @return void
     */
    public function addMore(array $more)
    {
        self::$new_storage = array_merge($more, self::$new_storage);
        self::$storage = array_merge($more, self::$storage);
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return ClassLikeStorage
     */
    public function create($fq_classlike_name)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        $storage = new ClassLikeStorage($fq_classlike_name);
        self::$storage[$fq_classlike_name_lc] = $storage;
        self::$new_storage[$fq_classlike_name_lc] = $storage;

        return $storage;
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return void
     */
    public function remove($fq_classlike_name)
    {
        unset(self::$storage[strtolower($fq_classlike_name)]);
    }

    /**
     * @return void
     */
    public static function deleteAll()
    {
        self::$storage = [];
    }

    /**
     * @return void
     */
    public static function populated()
    {
        self::$new_storage = [];
    }
}
