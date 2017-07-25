<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;
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
    public function __construct(PhpParser\Node\Stmt\Trait_ $class, StatementsSource $source, $fq_class_name)
    {
        $this->source = $source;
        $this->file_checker = $source->getFileChecker();
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;

        $fq_class_name_lower = strtolower($fq_class_name);

        $project_checker = $source->getFileChecker()->project_checker;
        $project_checker->addFullyQualifiedTraitName($fq_class_name, $source->getFilePath());
    }

    /**
     * @param   Context|null    $class_context
     * @param   Context|null    $global_context
     *
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
     *
     * @return  void
     */
    public function setMethodMap(array $method_map)
    {
        $this->method_map = $method_map;
    }

    /**
     * @param  string $method_name
     *
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
     *
     * @return bool
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
     *
     * @return bool
     */
    public static function hasCorrectCase($fq_trait_name, FileChecker $file_checker)
    {
        return isset($file_checker->project_checker->existing_traits[$fq_trait_name]);
    }
}
