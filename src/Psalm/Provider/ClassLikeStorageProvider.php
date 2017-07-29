<?php
namespace Psalm\Provider;

use Psalm\Checker\ClassLikeChecker;
use Psalm\Storage\ClassLikeStorage;

class ClassLikeStorageProvider
{
    /**
     * @param  string $fq_classlike_name
     *
     * @return ClassLikeStorage
     */
    public function get($fq_classlike_name)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        if (!isset(ClassLikeChecker::$all_storage[$fq_classlike_name_lc])) {
            throw new \InvalidArgumentException('Could not get class storage for ' . $fq_classlike_name);
        }

        return ClassLikeChecker::$all_storage[$fq_classlike_name_lc];
    }

    /**
     * @param  string  $fq_classlike_name
     *
     * @return bool
     */
    public function has($fq_classlike_name)
    {
        return isset(ClassLikeChecker::$all_storage[strtolower($fq_classlike_name)]);
    }

    /**
     * @return array<string, ClassLikeStorage>
     */
    public function getAll()
    {
        return ClassLikeChecker::$all_storage;
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return ClassLikeStorage
     */
    public function create($fq_classlike_name)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        ClassLikeChecker::$all_storage[$fq_classlike_name_lc] = $storage = new ClassLikeStorage();

        $storage->name = $fq_classlike_name;

        return $storage;
    }
}
