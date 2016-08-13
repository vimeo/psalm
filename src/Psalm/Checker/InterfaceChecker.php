<?php

namespace Psalm\Checker;

use PhpParser;

class InterfaceChecker extends ClassLikeChecker
{
    public function __construct(PhpParser\Node\Stmt\Interface_ $interface, StatementsSource $source)
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

    }

    public static function interfaceExists($absolute_class)
    {
        if (isset(self::$_existing_interfaces_ci[strtolower($absolute_class)])) {
            return true;
        }

        if (interface_exists($absolute_class, true)) {
            self::$_existing_interfaces_ci[strtolower($absolute_class)] = true;
            return true;
        }

        return false;
    }
}
