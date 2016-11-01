<?php

namespace Psalm\Checker;

use PhpParser;

use Psalm\StatementsSource;
use Psalm\Context;

class TraitChecker extends ClassLikeChecker
{
    /** @var array<string, string> */
    protected $method_map = [];

    public function __construct(PhpParser\Node\Stmt\Trait_ $class, StatementsSource $source, $absolute_class)
    {
        $this->class = $class;
        $this->namespace = $source->getNamespace();
        $this->aliased_classes = $source->getAliasedClasses();
        $this->file_name = $source->getFileName();
        $this->absolute_class = $absolute_class;

        $this->parent_class = null;

        $this->suppressed_issues = $source->getSuppressedIssues();

        self::$class_checkers[$absolute_class] = $this;
    }

    public function check($check_methods = true, Context $class_context = null)
    {
        if (!$class_context) {
            throw new \InvalidArgumentException('TraitChecker::check must be called with a $class_context');
        }

        parent::check($check_methods, $class_context);
    }

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
