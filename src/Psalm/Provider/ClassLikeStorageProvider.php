<?php
namespace Psalm\Provider;

use Psalm\Storage\ClassLikeStorage;

class ClassLikeStorageProvider
{
    /** @var array<string, ClassLikeStorage> */
    private static $storage = [];

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
     * @param  string  $fq_classlike_name
     *
     * @return bool
     */
    public function has($fq_classlike_name)
    {
        return isset(self::$storage[strtolower($fq_classlike_name)]);
    }

    /**
     * @return array<string, ClassLikeStorage>
     */
    public function getAll()
    {
        return self::$storage;
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return ClassLikeStorage
     */
    public function create($fq_classlike_name)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        self::$storage[$fq_classlike_name_lc] = $storage = new ClassLikeStorage();

        $storage->name = $fq_classlike_name;

        return $storage;
    }

    /**
     * @return void
     */
    public function deleteAll()
    {
        self::$storage = [];
    }
}
