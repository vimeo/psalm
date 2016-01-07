<?php

namespace CodeInspector;

use \PhpParser;
use \PhpParser\Error;
use \PhpParser\ParserFactory;

class ClassChecker
{
    protected $_file_name;
    protected $_class;
    protected $_namespace;
    protected $_aliased_classes;
    protected static $_existing_classes = [];

    public function __construct(PhpParser\Node\Stmt\Class_ $class, $namespace, $aliased_classes, $file_name)
    {
        $this->_class = $class;
        $this->_namespace = $namespace;
        $this->_aliased_classes = $aliased_classes;
        $this->_file_name = $file_name;

        self::$_existing_classes[self::getAbsoluteClass($class->name, $this->_namespace, [])] = 1;
    }

    public function check()
    {
        if ($this->_class->extends instanceof PhpParser\Node\Name) {
            self::checkClassName($this->_class->extends, $this->_namespace, $this->_aliased_classes, $this->_file_name);
        }

        foreach ($this->_class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_checker = new ClassMethodChecker($stmt, $this->_namespace, $this->_aliased_classes, $this->_file_name, $this->_class->name, $this->_class->extends);
                $method_checker->check();
            }
        }
    }

    public static function checkClassName(PhpParser\Node\Name $class_name, $namespace, array $aliased_classes, $file_name)
    {
        if ($class_name->parts[0] === 'static') {
            return;
        }

        $absolute_class = self::getAbsoluteClassFromName($class_name, $namespace, $aliased_classes);

        if (!isset(self::$_existing_classes[$absolute_class]) && !class_exists($absolute_class, true) && !interface_exists($absolute_class, true)) {
            throw new CodeException('Class ' . $absolute_class . ' does not exist', $file_name, $class_name->getLine());
        }

        self::$_existing_classes[$absolute_class] = 1;
    }

    public static function getAbsoluteClassFromName(PhpParser\Node\Name $class_name, $namespace, array $aliased_classes)
    {
        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return '\\' . implode('\\', $class_name->parts);
        }

        return self::getAbsoluteClass(implode('\\', $class_name->parts), $namespace, $aliased_classes);
    }

    public static function getAbsoluteClass($class, $namespace, array $imported_namespaces) {
        if ($class[0] === '\\') {
            return $class;
        }

        if (strpos($class, '\\') !== false) {
            $class_parts = explode('\\', $class);
            $first_namespace = array_shift($class_parts);

            if (isset($imported_namespaces[$first_namespace])) {
                return self::_addSlash($imported_namespaces[$first_namespace] . '\\' . implode('\\', $class_parts));
            }
        }
        else if (isset($imported_namespaces[$class])) {
            return self::_addSlash($imported_namespaces[$class]);
        }

        if ($namespace && substr($namespace, -1) !== '\\') {
            $namespace .= '\\';
        }

        return self::_addSlash($namespace . $class);
    }

    protected static function _addSlash($class)
    {
        if ($class[0] === '\\') {
            return $class;
        }

        return '\\' . $class;
    }
}
