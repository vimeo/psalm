<?php

namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;

class ClassChecker extends ClassLikeChecker
{
    public function __construct(PhpParser\Node\Stmt\Class_ $class, StatementsSource $source, $absolute_class)
    {
        parent::__construct($class, $source, $absolute_class);
    }

    public static function classExists($absolute_class)
    {
        if (isset(self::$_existing_classes_ci[strtolower($absolute_class)])) {
            return true;
        }

        if (in_array($absolute_class, self::$SPECIAL_TYPES)) {
            return false;
        }

        if (class_exists($absolute_class, true)) {
            self::$_existing_classes_ci[strtolower($absolute_class)] = true;
            return true;
        }

        return false;
    }

    /**
     * @param  string $absolute_class
     * @param  string $possible_parent
     * @return bool
     */
    public static function classExtends($absolute_class, $possible_parent)
    {
        if (isset(self::$_class_extends[$absolute_class][$possible_parent])) {
            return self::$_class_extends[$absolute_class][$possible_parent];
        }

        if (!self::classExists($absolute_class) || !self::classExists($possible_parent)) {
            return false;
        }

        if (!isset(self::$_class_extends[$absolute_class])) {
            self::$_class_extends[$absolute_class] = [];
        }

        self::$_class_extends[$absolute_class][$possible_parent] = is_subclass_of($absolute_class, $possible_parent);

        return self::$_class_extends[$absolute_class][$possible_parent];
    }
}
