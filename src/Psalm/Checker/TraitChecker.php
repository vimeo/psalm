<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\Storage\ClassLikeStorage;

class TraitChecker extends ClassLikeChecker
{
    /**
     * @var array<string, string>
     */
    private $method_map = [];

    /**
     * @param   PhpParser\Node\Stmt\ClassLike   $class
     * @param   StatementsSource                $source
     * @param   string                          $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $fq_class_name)
    {
        if (!$class instanceof PhpParser\Node\Stmt\Trait_) {
            throw new \InvalidArgumentException('Trait checker must be passed a trait');
        }

        $this->source = $source;
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;

        $fq_class_name_lower = strtolower($fq_class_name);

        $project_checker = $source->getFileChecker()->project_checker;
        $project_checker->addFullyQualifiedTraitName($fq_class_name, $source->getFilePath());

        if (!isset(self::$storage[$fq_class_name_lower])) {
            self::$storage[$fq_class_name_lower] = $storage = new ClassLikeStorage();
            $storage->name = $fq_class_name;
            $storage->location = new CodeLocation($this->source, $class, true);

            self::$file_classes[$this->source->getFilePath()][] = $fq_class_name;
        }

        self::$trait_checkers[$fq_class_name] = $this;
    }

    /**
     * @param   Context|null    $class_context
     * @param   Context|null    $global_context
     * @return void
     */
    public function visit(
        Context $class_context = null,
        Context $global_context = null
    ) {
        if (!$class_context) {
            throw new \InvalidArgumentException('TraitChecker::check must be called with a $class_context');
        }

        parent::visit($class_context, $global_context);
    }

    /**
     * @param   array<string, string> $method_map
     * @return  void
     */
    public function setMethodMap(array $method_map)
    {
        $this->method_map = $method_map;
    }

    /**
     * @param  string $method_name
     * @return string
     */
    protected function getMappedMethodName($method_name)
    {
        if (isset($this->method_map[$method_name])) {
            return $this->method_map[$method_name];
        }

        return $method_name;
    }

    /**
     * @param  string       $fq_trait_name
     * @param  FileChecker  $file_checker
     * @return boolean
     */
    public static function traitExists($fq_trait_name, FileChecker $file_checker)
    {
        if ($file_checker->evaluateClassLike($fq_trait_name) === false) {
            return false;
        }

        return $file_checker->project_checker->hasFullyQualifiedTraitName($fq_trait_name);
    }

    /**
     * @param  string       $fq_trait_name
     * @param  FileChecker  $file_checker
     * @return boolean
     */
    public static function hasCorrectCase($fq_trait_name, FileChecker $file_checker)
    {
        return isset($file_checker->project_checker->existing_traits[$fq_trait_name]);
    }
}
