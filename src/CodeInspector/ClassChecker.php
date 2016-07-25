<?php

namespace CodeInspector;

use CodeInspector\Issue\InvalidClass;
use CodeInspector\Issue\UndefinedClass;
use CodeInspector\Issue\UndefinedTrait;
use CodeInspector\IssueBuffer;
use PhpParser;
use PhpParser\Error;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class ClassChecker implements StatementsSource
{
    protected static $SPECIAL_TYPES = ['int', 'string', 'double', 'float', 'bool', 'false', 'object', 'empty', 'callable', 'array'];

    protected $_file_name;
    protected $_class;
    protected $_namespace;
    protected $_aliased_classes;
    protected $_absolute_class;
    protected $_has_custom_get = false;
    protected $_source;

    /** @var string|null */
    protected $_parent_class;

    /**
     * @var array
     */
    protected $_suppressed_issues;

    /**
     * @var array<ClassMethodChecker>
     */
    protected static $_method_checkers = [];

    protected static $_this_class = null;

    protected static $_existing_classes = [];
    protected static $_existing_classes_ci = [];
    protected static $_existing_interfaces = [];
    protected static $_class_implements = [];

    protected static $_class_methods = [];
    protected static $_class_checkers = [];

    protected static $_class_properties = [];

    protected static $_class_extends = [];

    public function __construct(PhpParser\Node\Stmt\Class_ $class, StatementsSource $source, $absolute_class)
    {
        $this->_class = $class;
        $this->_namespace = $source->getNamespace();
        $this->_aliased_classes = $source->getAliasedClasses();
        $this->_file_name = $source->getFileName();
        $this->_absolute_class = $absolute_class;

        $this->_suppressed_issues = $source->getSuppressedIssues();

        $this->_parent_class = $this->_class->extends ? ClassChecker::getAbsoluteClassFromName($this->_class->extends, $this->_namespace, $this->_aliased_classes) : null;

        self::$_existing_classes[$absolute_class] = true;

        if (self::$_this_class) {
            self::$_class_checkers[$absolute_class] = $this;
        }
    }

    public function check($check_statements = true, $method_id = null)
    {
        if ($this->_parent_class) {
            self::checkAbsoluteClassOrInterface($this->_parent_class, $this->_file_name, $this->_class->getLine(), $this->getSuppressedIssues());

            $this->_registerInheritedMethods($this->_parent_class);
        }

        $config = Config::getInstance();

        $leftover_stmts = [];

        $method_checkers = [];

        self::$_class_methods[$this->_absolute_class] = [];

        $class_context = new Context();

        foreach ($this->_class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_id = $this->_absolute_class . '::' . $stmt->name;

                if (!isset(self::$_method_checkers[$method_id])) {
                    $method_checker = new ClassMethodChecker($stmt, $this);
                    $method_checkers[$stmt->name] = $method_checker;

                    if (self::$_this_class && !$check_statements) {
                        self::$_method_checkers[$method_id] = $method_checker;
                    }
                }
                else {
                    $method_checker = self::$_method_checkers[$method_id];
                }

                self::$_class_methods[$this->_absolute_class][] = $stmt->name;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                $method_map = [];
                foreach ($stmt->adaptations as $adaptation) {
                    if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                        $method_map[$adaptation->method] = $adaptation->newName;
                    }
                }

                foreach ($stmt->traits as $trait) {
                    $trait_name = self::getAbsoluteClassFromName($trait, $this->_namespace, $this->_aliased_classes);
                    if (!trait_exists($trait_name)) {
                        if (IssueBuffer::accepts(
                            new UndefinedTrait('Trait ' . $trait_name . ' does not exist', $this->_file_name, $trait->getLine()),
                            $this->_suppressed_issues
                        )) {
                            return false;
                        }
                    }
                    $this->_registerInheritedMethods($trait_name, $method_map);
                }

            } else {
                if (!isset(self::$_class_properties[$this->_absolute_class])) {
                    self::$_class_properties[$this->_absolute_class] = [];
                }

                if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                    foreach ($stmt->props as $property) {
                        $comment = $stmt->getDocComment();
                        $type_in_comment = null;
                        if ($comment && $config->use_docblock_types) {
                            $type_in_comment = CommentChecker::getTypeFromComment($comment, null, $this);
                        }

                        $property_type = $type_in_comment ? Type::parseString($type_in_comment) : Type::getMixed();
                        self::$_class_properties[$this->_absolute_class][$property->name] = $property_type;

                        if (!$stmt->isStatic()) {
                            $class_context->vars_in_scope['this->' . $property->name] = $property_type;
                        }
                    }
                }
                $leftover_stmts[] = $stmt;
            }
        }

        if (method_exists($this->_absolute_class, '__get')) {
            $this->_has_custom_get = true;
        }

        if ($leftover_stmts) {
            $context = new Context();

            (new StatementsChecker($this))->check($leftover_stmts, $context);
        }

        $config = Config::getInstance();

        if ($check_statements) {
            // do the method checks after all class methods have been initialised
            foreach ($method_checkers as $method_checker) {
                $method_checker->check(clone $class_context);

                if (!$config->excludeIssueInFile('InvalidReturnType', $this->_file_name)) {
                    $method_checker->checkReturnTypes();
                }
            }
        }
    }

    /**
     * Used in deep method evaluation, we get method checkers on the current or parent
     * classes
     *
     * @param  string $method_id
     * @return ClassMethodChecker
     */
    public static function getMethodChecker($method_id)
    {
        if (isset(self::$_method_checkers[$method_id])) {
            return self::$_method_checkers[$method_id];
        }

        $parent_method_id = ClassMethodChecker::getDeclaringMethod($method_id);
        $parent_class = explode('::', $parent_method_id)[0];

        $class_checker = FileChecker::getClassCheckerFromClass($parent_class);

        // this is now set
        return self::$_method_checkers[$parent_method_id];
    }

    /**
     * Returns a class checker for the given class, if one has already been registered
     * @param  string $class_name
     * @return ClassChecker|null
     */
    public static function getClassCheckerFromClass($class_name)
    {
        if (isset(self::$_class_checkers[$class_name])) {
            return self::$_class_checkers[$class_name];
        }

        return null;
    }

    /**
     * @return bool
     */
    public static function checkClassName(PhpParser\Node\Name $class_name, $namespace, array $aliased_classes, $file_name, array $suppressed_issues)
    {
        if ($class_name->parts[0] === 'static') {
            return;
        }

        $absolute_class = self::getAbsoluteClassFromName($class_name, $namespace, $aliased_classes);

        return self::checkAbsoluteClassOrInterface($absolute_class, $file_name, $class_name->getLine(), $suppressed_issues);
    }

    public static function classExists($absolute_class)
    {
        if (isset(self::$_existing_classes_ci[$absolute_class])) {
            return true;
        }

        if (isset(self::$_existing_classes[$absolute_class])) {
            return true;
        }

        if (in_array($absolute_class, self::$SPECIAL_TYPES)) {
            return false;
        }

        if (class_exists($absolute_class, true)) {
            self::$_existing_classes_ci[$absolute_class] = true;
            return true;
        }

        return false;
    }

    public static function interfaceExists($absolute_class)
    {
        if (isset(self::$_existing_interfaces[$absolute_class])) {
            return true;
        }

        if (interface_exists($absolute_class, true)) {
            self::$_existing_interfaces[$absolute_class] = true;
            return true;
        }

        return false;
    }

    /**
     * @param  string $absolute_class
     * @return bool
     */
    public static function classOrInterfaceExists($absolute_class)
    {
        return self::classExists($absolute_class) || self::interfaceExists($absolute_class);
    }

    /**
     * @param  string $absolute_class
     * @param  string $possible_parent
     * @return bool
     */
    public static function classExtends($absolute_class, $possible_parent)
    {
        if (isset(self::$_class_extends[$absolute_class][$possible_parent])) {
            return self::$_class_extends[$absolute_class][$possible_parent];
        }

        if (!self::classExists($absolute_class) || !self::classExists($possible_parent)) {
            return false;
        }

        if (!isset(self::$_class_extends[$absolute_class])) {
            self::$_class_extends[$absolute_class] = [];
        }

        self::$_class_extends[$absolute_class][$possible_parent] = is_subclass_of($absolute_class, $possible_parent);

        return self::$_class_extends[$absolute_class][$possible_parent];
    }

    /**
     * @param  string $absolute_class
     * @param  string $possible_parent
     * @return bool
     */
    public static function classExtendsOrImplements($absolute_class, $possible_parent)
    {
        return self::classExtends($absolute_class, $possible_parent) || self::classImplements($absolute_class, $possible_parent);
    }

    /**
     * @param  string $absolute_class
     * @param  string $file_name
     * @param  int $line_number
     * @param  array<string>  $suppressed_issues
     * @return bool|null
     */
    public static function checkAbsoluteClassOrInterface($absolute_class, $file_name, $line_number, array $suppressed_issues)
    {
        if (empty($absolute_class)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        $absolute_class = preg_replace('/^\\\/', '', $absolute_class);

        if (!self::classOrInterfaceExists($absolute_class)) {
            if (IssueBuffer::accepts(
                new UndefinedClass('Class or interface ' . $absolute_class . ' does not exist', $file_name, $line_number),
                $suppressed_issues
            )) {
                return false;
            }

            return;
        }

        if (!isset(self::$_existing_classes[$absolute_class]) && strpos($absolute_class, '\\') === false) {
            $reflection_class = new ReflectionClass($absolute_class);

            if ($reflection_class->getName() !== $absolute_class) {
                if (IssueBuffer::accepts(
                    new InvalidClass('Class or interface ' . $absolute_class . ' has wrong casing', $file_name, $line_number),
                    $suppressed_issues
                )) {
                    return false;
                }
            }
        }

        self::$_existing_classes[$absolute_class] = true;
        return true;
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
        if (empty($class)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

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

        return ($namespace ? $namespace . '\\' : '') . $class;
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

    public function getParentClass()
    {
        return $this->_parent_class;
    }

    public function getFileName()
    {
        return $this->_file_name;
    }

    public function getClassChecker()
    {
        return $this;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    public function hasCustomGet()
    {
        return $this->_has_custom_get;
    }

    public function getProperties()
    {
        $class_properties = isset(self::$_class_properties[$this->_absolute_class]) ? self::$_class_properties[$this->_absolute_class] : [];
        $parent_properties = isset(self::$_class_properties[$this->_parent_class]) ? self::$_class_properties[$this->_parent_class] : [];

        return array_merge($parent_properties, $class_properties);
    }

    public function getSource()
    {
        return null;
    }

    public function getSuppressedIssues()
    {
        return $this->_suppressed_issues;
    }

    /**
     * @return bool
     */
    public static function classImplements($absolute_class, $interface)
    {
        if (isset(self::$_class_implements[$absolute_class][$interface])) {
            return true;
        }

        if (isset(self::$_class_implements[$absolute_class])) {
            return false;
        }

        if (!ClassChecker::classExists($absolute_class)) {
            return false;
        }

        if (in_array($interface, self::$SPECIAL_TYPES)) {
            return false;
        }

        $class_implementations = class_implements($absolute_class);

        if (!isset($class_implementations[$interface])) {
            return false;
        }

        self::$_class_implements[$absolute_class] = $class_implementations;

        return true;
    }

    protected function _registerInheritedMethods($parent_class, array $method_map = null)
    {
        if (!isset(self::$_class_methods[$parent_class])) {
            $class_methods = [];

            $reflection_class = new ReflectionClass($parent_class);

            $reflection_methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

            foreach ($reflection_methods as $reflection_method) {
                if (!$reflection_method->isAbstract() && $reflection_method->getDeclaringClass()->getName() === $parent_class) {
                    $method_name = $reflection_method->getName();
                    $class_methods[] = $method_name;
                }
            }

            self::$_class_methods[$parent_class] = $class_methods;
        }
        else {
            $class_methods = self::$_class_methods[$parent_class];
        }

        foreach ($class_methods as $method_name) {
            $parent_method_id = $parent_class . '::' . $method_name;
            $implemented_method_id = $this->_absolute_class . '::' . (isset($method_map[$method_name]) ? $method_map[$method_name] : $method_name);

            ClassMethodChecker::registerInheritedMethod($parent_method_id, $implemented_method_id);
        }
    }

    public static function setThisClass($this_class)
    {
        self::$_this_class = $this_class;

        self::$_class_checkers = [];
    }

    public static function getThisClass()
    {
        return self::$_this_class;
    }

    public static function clearCache()
    {
        self::$_method_checkers = [];

        self::$_this_class = null;

        self::$_existing_classes = [];
        self::$_class_implements = [];

        self::$_class_methods = [];
        self::$_class_checkers = [];

        self::$_class_properties = [];
    }
}
