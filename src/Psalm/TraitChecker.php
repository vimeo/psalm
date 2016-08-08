<?php

namespace Psalm;

use PhpParser;

class TraitChecker extends ClassChecker
{
    public function __construct(PhpParser\Node\Stmt\Trait_ $class, StatementsSource $source, $absolute_class)
    {
        $this->_class = $class;
        $this->_namespace = $source->getNamespace();
        $this->_aliased_classes = $source->getAliasedClasses();
        $this->_file_name = $source->getFileName();
        $this->_absolute_class = $absolute_class;

        $this->_parent_class = null;

        $this->_suppressed_issues = $source->getSuppressedIssues();

        self::$_existing_classes[$absolute_class] = 1;

        self::$_class_checkers[$absolute_class] = $this;
    }

    public function check($check_methods = true, Context $class_context = null)
    {
        if (!$class_context) {
            throw new \InvalidArgumentException('TraitChecker::check must be called with a $class_context');
        }

        parent::check($check_methods, $class_context);
    }
}
