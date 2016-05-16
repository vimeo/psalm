<?php

namespace CodeInspector;

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

        self::$_existing_classes[$absolute_class] = 1;
        if (self::$_this_class) {
            self::$_class_checkers[$absolute_class] = $this;
        }
    }
}
