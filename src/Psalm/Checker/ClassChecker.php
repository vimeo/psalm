<?php

namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;

class ClassChecker extends ClassLikeChecker
{
    protected static $existing_classes = [];
    protected static $existing_classes_ci = [];
    protected static $class_extends = [];
    protected static $class_implements = [];

    public function __construct(PhpParser\Node\Stmt\Class_ $class, StatementsSource $source, $absolute_class)
    {
        parent::__construct($class, $source, $absolute_class);

        self::$existing_classes[$absolute_class] = true;
        self::$existing_classes_ci[strtolower($absolute_class)] = true;

        self::$class_implements[$absolute_class] = [];

        foreach ($class->implements as $interface_name) {
            $absolute_interface_name = self::getAbsoluteClassFromName($interface_name, $this->namespace, $this->aliased_classes);

            self::$class_implements[$absolute_class][$absolute_interface_name] = true;
            self::registerClass($absolute_interface_name);
        }
    }

    public static function classExists($absolute_class)
    {
        if (isset(self::$existing_classes_ci[strtolower($absolute_class)])) {
            return self::$existing_classes_ci[strtolower($absolute_class)];
        }

        if (in_array($absolute_class, self::$SPECIAL_TYPES)) {
            return false;
        }

        if (class_exists($absolute_class)) {
            $reflected_class = new \ReflectionClass($absolute_class);

            self::$existing_classes_ci[strtolower($absolute_class)] = true;
            self::$existing_classes[$reflected_class->getName()] = true;

            return true;
        }

        self::$existing_classes_ci[strtolower($absolute_class)] = false;
        self::$existing_classes_ci[$absolute_class] = false;

        return false;
    }

    public static function hasCorrectCasing($absolute_class)
    {
        if (!self::classExists($absolute_class)) {
            throw new \InvalidArgumentException('Cannot check casing on nonexistent class ' . $absolute_class);
        }

        return isset(self::$existing_classes[$absolute_class]);
    }

    /**
     * @param  string $absolute_class
     * @param  string $possible_parent
     * @return bool
     */
    public static function classExtends($absolute_class, $possible_parent)
    {
        if (isset(self::$class_extends[$absolute_class][$possible_parent])) {
            return self::$class_extends[$absolute_class][$possible_parent];
        }

        if (!self::classExists($absolute_class) || !self::classExists($possible_parent)) {
            return false;
        }

        if (!isset(self::$class_extends[$absolute_class])) {
            self::$class_extends[$absolute_class] = [];
        }

        self::$class_extends[$absolute_class][$possible_parent] = is_subclass_of($absolute_class, $possible_parent);

        return self::$class_extends[$absolute_class][$possible_parent];
    }

    public static function getInterfacesForClass($absolute_class)
    {
        return isset(self::$class_implements[$absolute_class]) ? self::$class_implements[$absolute_class] : [];
    }

    /**
     * @return bool
     */
    public static function classImplements($absolute_class, $interface)
    {
        if (isset(self::$class_implements[$absolute_class][$interface])) {
            return true;
        }

        if (isset(self::$class_implements[$absolute_class])) {
            return false;
        }

        if (!ClassChecker::classExists($absolute_class)) {
            return false;
        }

        if (in_array($interface, self::$SPECIAL_TYPES)) {
            return false;
        }

        $class_implementations = class_implements($absolute_class);

        if (!isset($class_implementations[$interface])) {
            return false;
        }

        self::$class_implements[$absolute_class] = $class_implementations;

        return true;
    }
}
