<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;

class ClassChecker extends ClassLikeChecker
{
    /**
     * @param PhpParser\Node\Stmt\Class_    $class
     * @param StatementsSource              $source
     * @param string|null                   $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\Class_ $class, StatementsSource $source, $fq_class_name)
    {
        if (!$fq_class_name) {
            $fq_class_name = self::getAnonymousClassName($class, $source->getFilePath());
        }

        parent::__construct($class, $source, $fq_class_name);

        $fq_class_name_lower = strtolower($fq_class_name);

        $storage = self::$storage[$fq_class_name_lower];

        $project_checker = $source->getFileChecker()->project_checker;
        $project_checker->addFullyQualifiedClassName($fq_class_name, $source->getFilePath());

        if (!$this->class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \InvalidArgumentException('Bad');
        }

        if ($this->class->extends) {
            $this->parent_fq_class_name = self::getFQCLNFromNameObject(
                $this->class->extends,
                $this->source->getAliases()
            );
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\Class_ $class
     * @param  string                     $file_path
     * @return string
     */
    public static function getAnonymousClassName(PhpParser\Node\Stmt\Class_ $class, $file_path)
    {
        return $file_path . ':' . $class->getLine();
    }

    /**
     * Determine whether or not a given class exists
     *
     * @param  string       $fq_class_name
     * @param  FileChecker  $file_checker
     *
     * @return bool
     */
    public static function classExists($fq_class_name, FileChecker $file_checker)
    {
        if (isset(self::$SPECIAL_TYPES[$fq_class_name])) {
            return false;
        }

        if ($fq_class_name === 'Generator') {
            return true;
        }

        if ($file_checker->evaluateClassLike($fq_class_name) === false) {
            return false;
        }

        return $file_checker->project_checker->hasFullyQualifiedClassName($fq_class_name);
    }

    /**
     * Determine whether or not a class has the correct casing
     *
     * @param  string       $fq_class_name
     * @param  FileChecker  $file_checker
     *
     * @return bool
     */
    public static function hasCorrectCasing($fq_class_name, FileChecker $file_checker)
    {
        if ($fq_class_name === 'Generator') {
            return true;
        }

        return isset($file_checker->project_checker->existing_classes[$fq_class_name]);
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     */
    public static function classExtends($fq_class_name, $possible_parent)
    {
        $fq_class_name = strtolower($fq_class_name);

        if ($fq_class_name === 'generator') {
            return false;
        }

        if (!isset(self::$storage[$fq_class_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $fq_class_name);
        }

        return in_array(strtolower($possible_parent), self::$storage[$fq_class_name]->parent_classes, true);
    }

    /**
     * Get all the interfaces a given class implements
     *
     * @param  string $fq_class_name
     *
     * @return array<string>
     */
    public static function getInterfacesForClass($fq_class_name)
    {
        return self::$storage[strtolower($fq_class_name)]->class_implements;
    }

    /**
     * Check whether a class implements an interface
     *
     * @param  string       $fq_class_name
     * @param  string       $interface
     *
     * @return bool
     */
    public static function classImplements($fq_class_name, $interface)
    {
        $interface_id = strtolower($interface);

        $fq_class_name = strtolower($fq_class_name);

        if ($interface_id === 'callable' && $fq_class_name === 'closure') {
            return true;
        }

        if ($interface_id === 'traversable' && $fq_class_name === 'generator') {
            return true;
        }

        if (isset(self::$SPECIAL_TYPES[$interface_id]) || isset(self::$SPECIAL_TYPES[$fq_class_name])) {
            return false;
        }

        $storage = self::$storage[$fq_class_name];

        return isset($storage->class_implements[$interface_id]);
    }
}
