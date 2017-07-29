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
     *
     * @return string
     */
    public static function getAnonymousClassName(PhpParser\Node\Stmt\Class_ $class, $file_path)
    {
        return preg_replace('/[^A-Za-z0-9]/', '_', $file_path . ':' . $class->getLine());
    }

    /**
     * Determine whether or not a given class exists
     *
     * @param  string       $fq_class_name
     * @param  FileChecker  $file_checker
     *
     * @return bool
     */
    public static function classExists(ProjectChecker $project_checker, $fq_class_name)
    {
        if (isset(self::$SPECIAL_TYPES[$fq_class_name])) {
            return false;
        }

        if ($fq_class_name === 'Generator') {
            return true;
        }

        return $project_checker->hasFullyQualifiedClassName($fq_class_name);
    }

    /**
     * Determine whether or not a class has the correct casing
     *
     * @param  string       $fq_class_name
     *
     * @return bool
     */
    public static function hasCorrectCasing(ProjectChecker $project_checker, $fq_class_name)
    {
        if ($fq_class_name === 'Generator') {
            return true;
        }

        return isset($project_checker->existing_classes[$fq_class_name]);
    }

    /**
     * Determine whether or not a class extends a parent
     *
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     */
    public static function classExtends(ProjectChecker $project_checker, $fq_class_name, $possible_parent)
    {
        $fq_class_name = strtolower($fq_class_name);

        if ($fq_class_name === 'generator') {
            return false;
        }

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        return in_array(strtolower($possible_parent), $class_storage->parent_classes, true);
    }

    /**
     * Check whether a class implements an interface
     *
     * @param  string       $fq_class_name
     * @param  string       $interface
     *
     * @return bool
     */
    public static function classImplements(ProjectChecker $project_checker, $fq_class_name, $interface)
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

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        return isset($class_storage->class_implements[$interface_id]);
    }
}
