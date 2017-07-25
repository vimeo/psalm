<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\TraitSource;

class TraitChecker extends ClassLikeChecker
{
    /**
     * @var array<string, string>
     */
    private $method_map = [];

    /**
     * @param  PhpParser\Node\Stmt\Trait_       $class
     * @param   StatementsSource                $source
     * @param   string                          $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\Trait_ $class, TraitSource $trait_source, $fq_class_name)
    {
        $this->source = $trait_source;
        $this->file_checker = $trait_source->getFileChecker();
        $this->class = $class;
        $this->fq_class_name = $fq_class_name;

        self::$trait_checkers[strtolower($fq_class_name)] = $this;
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
