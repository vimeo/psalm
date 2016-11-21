<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\StatementsSource;
use Psalm\Context;

class TraitChecker extends ClassLikeChecker
{
    /**
     * @var array<string, string>
     */
    protected $method_map = [];

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

        $this->class = $class;
        $this->namespace = $source->getNamespace();
        $this->aliased_classes = $source->getAliasedClasses();
        $this->file_name = $source->getFileName();
        $this->fq_class_name = $fq_class_name;

        $this->parent_class = null;

        $this->suppressed_issues = $source->getSuppressedIssues();

        self::$class_checkers[$fq_class_name] = $this;
    }

    /**
     * @param   bool            $check_methods
     * @param   Context|null    $class_context
     * @param   bool            $update_docblocks
     * @return void
     */
    public function check($check_methods = true, Context $class_context = null, $update_docblocks = false)
    {
        if (!$class_context) {
            throw new \InvalidArgumentException('TraitChecker::check must be called with a $class_context');
        }

        parent::check($check_methods, $class_context);
    }

    /**
     * @param   array $method_map
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
     * @param  string $trait_name
     * @return boolean
     */
    public static function traitExists($trait_name)
    {
        return trait_exists($trait_name);
    }
}
