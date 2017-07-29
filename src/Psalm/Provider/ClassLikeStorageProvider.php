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

        if (!isset(ClassLikeChecker::$storage[$fq_classlike_name_lc])) {
            throw new \InvalidArgumentException('Could not get class storage for ' . $fq_classlike_name);
        }

        return ClassLikeChecker::$storage[$fq_classlike_name_lc];
    }

    /**
     * @return array<string, ClassLikeStorage>
     */
    public function getAll()
    {
        return ClassLikeChecker::$storage;
    }

    /**
     * @param  string $fq_classlike_name
     *
     * @return ClassLikeStorage
     */
    public function create($fq_classlike_name)
    {
        $fq_classlike_name_lc = strtolower($fq_classlike_name);

        ClassLikeChecker::$storage[$fq_classlike_name_lc] = $storage = new ClassLikeStorage();

        $storage->name = $fq_classlike_name;

        return $storage;
    }
}
