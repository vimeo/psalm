<?php

namespace CodeInspector;

use PhpParser;
use PhpParser\Error;
use PhpParser\ParserFactory;

class ClassChecker implements StatementsSource
{
    protected $_file_name;
    protected $_class;
    protected $_namespace;
    protected $_aliased_classes;
    protected $_absolute_class;
    protected $_class_properties = [];
    protected $_has_custom_get = false;

    protected static $_existing_classes = [];

    public function __construct(PhpParser\Node\Stmt\Class_ $class, StatementsSource $source, $absolute_class)
    {
        $this->_class = $class;
        $this->_namespace = $source->getNamespace();
        $this->_aliased_classes = $source->getAliasedClasses();
        $this->_file_name = $source->getFileName();
        $this->_absolute_class = $absolute_class;

        self::$_existing_classes[$absolute_class] = 1;
    }

    public function check()
    {
        if ($this->_class->extends instanceof PhpParser\Node\Name) {
            self::checkClassName($this->_class->extends, $this->_namespace, $this->_aliased_classes, $this->_file_name);
        }

        $leftover_stmts = [];

        try {
            new \ReflectionMethod($this->_absolute_class . '::__get');
            $this->_has_custom_get = true;

        } catch (\ReflectionException $e) {}

        foreach ($this->_class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_checker = new ClassMethodChecker($stmt, $this);
                $method_checker->check();

            } else {
                if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($stmt->props as $property) {
                        $this->_class_properties[] = $property->name;
                    }
                }
                $leftover_stmts[] = $stmt;
            }
        }

        if ($leftover_stmts) {
            $scope_vars = [];
            $possibly_in_scope_vars = [];

            (new StatementsChecker($this))->check($leftover_stmts, $scope_vars, $possibly_in_scope_vars);
        }
    }

    public static function checkClassName(PhpParser\Node\Name $class_name, $namespace, array $aliased_classes, $file_name)
    {
        if ($class_name->parts[0] === 'static') {
            return;
        }

        $absolute_class = self::getAbsoluteClassFromName($class_name, $namespace, $aliased_classes);

        self::checkAbsoluteClass($absolute_class, $class_name, $file_name);
    }

    public static function checkAbsoluteClass($absolute_class, PhpParser\NodeAbstract $stmt, $file_name)
    {
        if (strpos($file_name, 'hoodahoop')) {
            return;
        }

        if (!isset(self::$_existing_classes[$absolute_class]) && !class_exists($absolute_class, true) && !interface_exists($absolute_class, true)) {
            throw new CodeException('Class ' . $absolute_class . ' does not exist', $file_name, $stmt->getLine());
        }

        self::$_existing_classes[$absolute_class] = 1;
    }

    public static function getAbsoluteClassFromName(PhpParser\Node\Name $class_name, $namespace, array $aliased_classes)
    {
        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return implode('\\', $class_name->parts);
        }

        return self::getAbsoluteClassFromString(implode('\\', $class_name->parts), $namespace, $aliased_classes);
    }

    public static function getAbsoluteClassFromString($class, $namespace, array $imported_namespaces)
    {
        if ($class[0] === '\\') {
            return substr($class, 1);
        }

        if (strpos($class, '\\') !== false) {
            $class_parts = explode('\\', $class);
            $first_namespace = array_shift($class_parts);

            if (isset($imported_namespaces[$first_namespace])) {
                return $imported_namespaces[$first_namespace] . '\\' . implode('\\', $class_parts);
            }
        } elseif (isset($imported_namespaces[$class])) {
            return $imported_namespaces[$class];
        }

        return $namespace . '\\' . $class;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function getAliasedClasses()
    {
        return $this->_aliased_classes;
    }

    public function getAbsoluteClass()
    {
        return $this->_absolute_class;
    }

    public function getClassName()
    {
        return $this->_class->name;
    }

    public function getClassExtends()
    {
        return $this->_class->extends;
    }

    public function getFileName()
    {
        return $this->_file_name;
    }

    public function getClassChecker()
    {
        return $this;
    }

    public function isStatic()
    {
        return false;
    }

    public function hasCustomGet()
    {
        return $this->_has_custom_get;
    }

    public function getPropertyNames()
    {
        return $this->_class_properties;
    }
}
