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
     * A lookup table of existing classes
     *
     * @var array<string, bool>
     */
    protected static $existing_classes = [];

    /**
     * A lookup table of existing classes, all lowercased
     *
     * @var array<string, bool>
     */
    protected static $existing_classes_ci = [];

    /**
     * A lookup table used for caching the results of classExtends calls
     *
     * @var array<string, array<string, bool>>
     */
    protected static $class_extends = [];

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

        self::$existing_classes[$fq_class_name] = true;
        self::$existing_classes_ci[strtolower($fq_class_name)] = true;

        self::$class_implements[$fq_class_name] = [];

        if ($this->class->extends) {
            $this->parent_class = self::getFQCLNFromNameObject(
                $this->class->extends,
                $this->namespace,
                $this->aliased_classes
            );

            self::$class_extends[$this->fq_class_name][$this->parent_class] = true;
        }

        foreach ($class->implements as $interface_name) {
            $fq_interface_name = self::getFQCLNFromNameObject(
                $interface_name,
                $this->namespace,
                $this->aliased_classes
            );

            self::$class_implements[$fq_class_name][strtolower($fq_interface_name)] = $fq_interface_name;
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

        if ($fq_class_name === 'Generator') {
            return true;
        }

        $old_level = error_reporting();
        error_reporting(0);
        $class_exists = class_exists($fq_class_name);
        error_reporting($old_level);

        if ($class_exists) {
            $old_level = error_reporting();
            error_reporting(0);
            $reflected_class = new \ReflectionClass($fq_class_name);
            error_reporting($old_level);

            self::$existing_classes_ci[strtolower($fq_class_name)] = true;
            self::$existing_classes[$reflected_class->getName()] = true;

            return true;
        }

        // we can only be sure that the case-sensitive version does not exist
        self::$existing_classes[$fq_class_name] = false;

        return false;
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

        if ($fq_class_name === 'Generator') {
            return true;
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

        self::$class_extends[$fq_class_name][$possible_parent] = is_subclass_of($fq_class_name, $possible_parent);

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
        if (!isset(self::$class_implements[$fq_class_name])) {
            /** @var string[] */
            $class_implements = class_implements($fq_class_name);

            self::$class_implements[$fq_class_name] = [];

            foreach ($class_implements as $interface) {
                 self::$class_implements[$fq_class_name][strtolower($interface)] = $interface;
            }
        }

        return self::$class_implements[$fq_class_name];
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

        if (isset(self::$class_implements[$fq_class_name][$interface_id])) {
            return true;
        }

        if (isset(self::$class_implements[$fq_class_name])) {
            return false;
        }

        if (!ClassChecker::classExists($fq_class_name)) {
            return false;
        }

        if (in_array($interface_id, self::$SPECIAL_TYPES)) {
            return false;
        }

        $class_implementations = self::getInterfacesForClass($fq_class_name);

        return isset($class_implementations[$interface_id]);
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$existing_classes = [];
        self::$existing_classes_ci = [];

        self::$class_extends = [];

        self::$anonymous_class_count = 0;

        MethodChecker::clearCache();
    }
}
