<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;

class ClassChecker extends ClassLikeChecker
{
    /**
     * @var PhpParser\Node\Stmt\Class_
     */
    protected $class;

    /**
     * @var integer
     */
    protected static $anonymous_class_count = 0;

    /**
     * @param PhpParser\Node\Stmt\ClassLike $class
     * @param StatementsSource              $source
     * @param string|null                   $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $fq_class_name)
    {
        if (!$class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \InvalidArgumentException('Bad');
        }

        if ($fq_class_name === null) {
            $fq_class_name = 'PsalmAnonymousClass' . (self::$anonymous_class_count++);
        }

        parent::__construct($class, $source, $fq_class_name);

        $storage = self::$storage[$fq_class_name];

        self::$existing_classes[$fq_class_name] = true;
        self::$existing_classes_ci[strtolower($fq_class_name)] = true;

        self::$class_extends[$this->fq_class_name] = [];

        if ($this->class->extends) {
            $this->parent_class = self::getFQCLNFromNameObject(
                $this->class->extends,
                $this->namespace,
                $this->aliased_classes
            );
        }

        foreach ($class->implements as $interface_name) {
            $fq_interface_name = self::getFQCLNFromNameObject(
                $interface_name,
                $this->namespace,
                $this->aliased_classes
            );

            $storage->class_implements[strtolower($fq_interface_name)] = $fq_interface_name;
        }
    }

    /**
     * Determine whether or not a given class exists
     *
     * @param  string $fq_class_name
     * @return bool
     */
    public static function classExists($fq_class_name)
    {
        if (isset(self::$existing_classes_ci[strtolower($fq_class_name)])) {
            return self::$existing_classes_ci[strtolower($fq_class_name)];
        }

        if (in_array($fq_class_name, self::$SPECIAL_TYPES)) {
            return false;
        }

        if (parent::registerClassLike($fq_class_name) === false) {
            self::$existing_classes[$fq_class_name] = false;

            return false;
        }

        if (!isset(self::$existing_classes_ci[strtolower($fq_class_name)])) {
            // it exists, but it's not a class
            self::$existing_classes_ci[strtolower($fq_class_name)] = false;
            return false;
        }

        return true;
    }

    /**
     * Determine whether or not a class has the correct casing
     *
     * @param  string  $fq_class_name
     * @return bool
     */
    public static function hasCorrectCasing($fq_class_name)
    {
        if (!self::classExists($fq_class_name)) {
            throw new \InvalidArgumentException('Cannot check casing on nonexistent class ' . $fq_class_name);
        }

        return isset(self::$existing_classes[$fq_class_name]);
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @param  string $fq_class_name
     * @param  string $possible_parent
     * @return bool
     */
    public static function classExtends($fq_class_name, $possible_parent)
    {
        if (isset(self::$class_extends[$fq_class_name][$possible_parent])) {
            return self::$class_extends[$fq_class_name][$possible_parent];
        }

        if (!self::classExists($fq_class_name) || !self::classExists($possible_parent)) {
            return false;
        }

        if (!isset(self::$class_extends[$fq_class_name])) {
            self::$class_extends[$fq_class_name] = [];
        }

        $old_level = error_reporting();
        error_reporting(0);
        self::$class_extends[$fq_class_name][$possible_parent] = is_subclass_of($fq_class_name, $possible_parent);
        error_reporting($old_level);

        return self::$class_extends[$fq_class_name][$possible_parent];
    }

    /**
     * Get all the interfaces a given class implements
     *
     * @param  string $fq_class_name
     * @return array<string>
     */
    public static function getInterfacesForClass($fq_class_name)
    {
        self::registerClassLike($fq_class_name);

        return self::$storage[$fq_class_name]->class_implements;
    }

    /**
     * Check whether a class implements an interface
     *
     * @param  string $fq_class_name
     * @param  string $interface
     * @return bool
     */
    public static function classImplements($fq_class_name, $interface)
    {
        $interface_id = strtolower($interface);

        if ($interface_id === 'callable' && $fq_class_name === 'Closure') {
            return true;
        }

        if (in_array($interface_id, self::$SPECIAL_TYPES) || in_array($fq_class_name, self::$SPECIAL_TYPES)) {
            return false;
        }

        if (self::registerClassLike($fq_class_name) === false) {
            return false;
        }

        $storage = self::$storage[$fq_class_name];

        return isset($storage->class_implements[$interface_id]);
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$anonymous_class_count = 0;

        MethodChecker::clearCache();
    }
}
