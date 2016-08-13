<?php

namespace Psalm\Checker;

use Psalm\Issue\InvalidClass;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedTrait;
use Psalm\IssueBuffer;
use Psalm\Context;
use Psalm\Config;
use Psalm\Type;
use Psalm\StatementsSource;
use PhpParser;
use PhpParser\Error;
use PhpParser\ParserFactory;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

abstract class ClassLikeChecker implements StatementsSource
{
    protected static $SPECIAL_TYPES = ['int', 'string', 'double', 'float', 'bool', 'false', 'object', 'empty', 'callable', 'array'];

    protected $file_name;
    protected $class;
    protected $namespace;
    protected $aliased_classes;
    protected $absolute_class;
    protected $has_custom_get = false;
    protected $source;

    /** @var string|null */
    protected $parent_class;

    /**
     * @var array
     */
    protected $suppressed_issues;

    /**
     * @var array<ClassMethodChecker>
     */
    protected static $method_checkers = [];

    protected static $this_class = null;

    protected static $class_methods = [];
    protected static $class_checkers = [];

    protected static $public_class_properties = [];
    protected static $protected_class_properties = [];
    protected static $private_class_properties = [];

    protected static $public_static_class_properties = [];
    protected static $protected_static_class_properties = [];
    protected static $private_static_class_properties = [];

    protected static $public_class_constants = [];

    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $absolute_class)
    {
        $this->class = $class;
        $this->namespace = $source->getNamespace();
        $this->aliased_classes = $source->getAliasedClasses();
        $this->file_name = $source->getFileName();
        $this->absolute_class = $absolute_class;

        $this->suppressed_issues = $source->getSuppressedIssues();

        $this->parent_class = $this->class->extends
            ? self::getAbsoluteClassFromName($this->class->extends, $this->namespace, $this->aliased_classes)
            : null;

        if (self::$this_class || $class instanceof PhpParser\Node\Stmt\Trait_) {
            self::$class_checkers[$absolute_class] = $this;
        }
    }

    public function check($check_methods = true, Context $class_context = null)
    {
        if ($this->parent_class) {
            if (self::checkAbsoluteClassOrInterface(
                    $this->parent_class,
                    $this->file_name,
                    $this->class->getLine(),
                    $this->getSuppressedIssues()
                ) === false
            ) {
                return false;
            }

            if (!isset(self::$public_class_properties[$this->parent_class]) || !isset(self::$public_class_constants[$this->parent_class])) {
                self::registerClass($this->parent_class);
            }

            $this->registerInheritedMethods($this->parent_class);
        }

        $config = Config::getInstance();

        $leftover_stmts = [];

        $method_checkers = [];

        self::$class_methods[$this->absolute_class] = [];

        self::$public_class_properties[$this->absolute_class] = [];
        self::$protected_class_properties[$this->absolute_class] = [];
        self::$private_class_properties[$this->absolute_class] = [];

        self::$public_static_class_properties[$this->absolute_class] = [];
        self::$protected_static_class_properties[$this->absolute_class] = [];
        self::$private_static_class_properties[$this->absolute_class] = [];

        self::$public_class_constants[$this->absolute_class] = [];

        if ($this->parent_class) {
            self::$public_class_properties[$this->absolute_class] = self::$public_class_properties[$this->parent_class];
            self::$protected_class_properties[$this->absolute_class] = self::$protected_class_properties[$this->parent_class];

            self::$public_static_class_properties[$this->absolute_class] = self::$public_static_class_properties[$this->parent_class];
            self::$protected_static_class_properties[$this->absolute_class] = self::$protected_static_class_properties[$this->parent_class];

            self::$public_class_constants[$this->absolute_class] = self::$public_class_constants[$this->parent_class];
        }

        if ($this instanceof ClassChecker) {
            foreach (ClassChecker::getInterfacesForClass($this->absolute_class) as $interface_name => $_) {
                self::$public_class_constants[$this->absolute_class] += self::$public_class_constants[$interface_name];
            }
        }

        if (!$class_context) {
            $class_context = new Context();
            $class_context->self = $this->absolute_class;
            $class_context->parent = $this->parent_class;
            $class_context->vars_in_scope['this'] = new Type\Union([new Type\Atomic($this->absolute_class)]);
        }

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_id = $this->absolute_class . '::' . $stmt->name;

                if (!isset(self::$method_checkers[$method_id])) {
                    $method_checker = new ClassMethodChecker($stmt, $this);
                    $method_checkers[$stmt->name] = $method_checker;

                    if (self::$this_class && !$check_methods) {
                        self::$method_checkers[$method_id] = $method_checker;
                    }
                }
                else {
                    $method_checker = self::$method_checkers[$method_id];
                }

                self::$class_methods[$this->absolute_class][] = $stmt->name;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                $method_map = [];
                foreach ($stmt->adaptations as $adaptation) {
                    if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                        $method_map[$adaptation->method] = $adaptation->newName;
                    }
                }

                foreach ($stmt->traits as $trait) {
                    $trait_name = self::getAbsoluteClassFromName($trait, $this->namespace, $this->aliased_classes);

                    if (!trait_exists($trait_name)) {
                        if (IssueBuffer::accepts(
                            new UndefinedTrait('Trait ' . $trait_name . ' does not exist', $this->file_name, $trait->getLine()),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }
                    }
                    else {
                        try {
                            $reflection_trait = new \ReflectionClass($trait_name);
                        }
                        catch (\ReflectionException $e) {
                            if (IssueBuffer::accepts(
                                new UndefinedTrait('Trait ' . $trait_name . ' has wrong casing', $this->file_name, $trait->getLine()),
                                $this->suppressed_issues
                            )) {
                                return false;
                            }

                            continue;
                        }

                        $this->registerInheritedMethods($trait_name, $method_map);

                        $trait_checker = FileChecker::getClassLikeCheckerFromClass($trait_name);

                        $trait_checker->check(true, $class_context);
                    }
                }
            } else {
                if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                    $comment = $stmt->getDocComment();
                    $type_in_comment = null;

                    if ($comment && $config->use_docblock_types && count($stmt->props) === 1) {
                        $type_in_comment = CommentChecker::getTypeFromComment((string) $comment, null, $this);
                    }

                    $property_type = $type_in_comment ? $type_in_comment : Type::getMixed();

                    foreach ($stmt->props as $property) {
                        if ($stmt->isStatic()) {
                            if ($stmt->isPublic()) {
                                self::$public_static_class_properties[$class_context->self][$property->name] = $property_type;
                            }
                            elseif ($stmt->isProtected()) {
                                self::$protected_static_class_properties[$class_context->self][$property->name] = $property_type;
                            }
                            elseif ($stmt->isPrivate()) {
                                self::$private_static_class_properties[$class_context->self][$property->name] = $property_type;
                            }
                        }
                        else {
                            if ($stmt->isPublic()) {
                                self::$public_class_properties[$class_context->self][$property->name] = $property_type;
                            }
                            elseif ($stmt->isProtected()) {
                                self::$protected_class_properties[$class_context->self][$property->name] = $property_type;
                            }
                            elseif ($stmt->isPrivate()) {
                                self::$private_class_properties[$class_context->self][$property->name] = $property_type;
                            }
                        }

                        if (!$stmt->isStatic()) {
                            $class_context->vars_in_scope['this->' . $property->name] = $property_type;
                        }
                    }
                }
                elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                    $comment = $stmt->getDocComment();
                    $type_in_comment = null;

                    if ($comment && $config->use_docblock_types && count($stmt->consts) === 1) {
                        $type_in_comment = CommentChecker::getTypeFromComment((string) $comment, null, $this);
                    }

                    $const_type = $type_in_comment ? $type_in_comment : Type::getMixed();

                    foreach ($stmt->consts as $const) {
                        self::$public_class_constants[$class_context->self][$const->name] = $const_type;
                    }
                }

                $leftover_stmts[] = $stmt;
            }
        }

        if (method_exists($this->absolute_class, '__get')) {
            $this->has_custom_get = true;
        }

        if ($leftover_stmts) {
            (new StatementsChecker($this))->check($leftover_stmts, $class_context);
        }

        $config = Config::getInstance();

        if ($check_methods) {
            // do the method checks after all class methods have been initialised
            foreach ($method_checkers as $method_checker) {
                $method_checker->check(clone $class_context);

                if (!$config->excludeIssueInFile('InvalidReturnType', $this->file_name)) {
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
        if (isset(self::$method_checkers[$method_id])) {
            return self::$method_checkers[$method_id];
        }

        $declaring_method_id = ClassMethodChecker::getDeclaringMethod($method_id);
        $declaring_class = explode('::', $declaring_method_id)[0];

        $class_checker = FileChecker::getClassLikeCheckerFromClass($declaring_class);

        if (!$class_checker) {
            throw new \InvalidArgumentException('Could not get class checker for ' . $declaring_class);
        }

        foreach ($class_checker->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_checker = new ClassMethodChecker($stmt, $class_checker);
                $method_id = $class_checker->absolute_class . '::' . $stmt->name;
                self::$method_checkers[$method_id] = $method_checker;
                return $method_checker;
            }
        }

        throw new \InvalidArgumentException('Method checker not found');
    }

    /**
     * Returns a class checker for the given class, if one has already been registered
     * @param  string $class_name
     * @return self|null
     */
    public static function getClassLikeCheckerFromClass($class_name)
    {
        if (isset(self::$class_checkers[$class_name])) {
            return self::$class_checkers[$class_name];
        }

        return null;
    }

    /**
     * @return bool|null
     */
    public static function checkClassName(PhpParser\Node\Name $class_name, $namespace, array $aliased_classes, $file_name, array $suppressed_issues)
    {
        if ($class_name->parts[0] === 'static') {
            return;
        }

        $absolute_class = self::getAbsoluteClassFromName($class_name, $namespace, $aliased_classes);

        return self::checkAbsoluteClassOrInterface($absolute_class, $file_name, $class_name->getLine(), $suppressed_issues);
    }

    /**
     * @param  string $absolute_class
     * @return bool
     */
    public static function classOrInterfaceExists($absolute_class)
    {
        return ClassChecker::classExists($absolute_class) || InterfaceChecker::interfaceExists($absolute_class);
    }

    /**
     * @param  string $absolute_class
     * @param  string $possible_parent
     * @return bool
     */
    public static function classExtendsOrImplements($absolute_class, $possible_parent)
    {
        return ClassChecker::classExtends($absolute_class, $possible_parent)
            || ClassChecker::classImplements($absolute_class, $possible_parent);
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

        $class_exists = ClassChecker::classExists($absolute_class);
        $interface_exists = InterfaceChecker::interfaceExists($absolute_class);

        if (!$class_exists && !$interface_exists) {
            if (IssueBuffer::accepts(
                new UndefinedClass('Class or interface ' . $absolute_class . ' does not exist', $file_name, $line_number),
                $suppressed_issues
            )) {
                return false;
            }

            return;
        }

        if (($class_exists && !ClassChecker::hasCorrectCasing($absolute_class))
            || ($interface_exists && !InterfaceChecker::hasCorrectCasing($absolute_class))
        ) {
            if (IssueBuffer::accepts(
                new InvalidClass('Class or interface ' . $absolute_class . ' has wrong casing', $file_name, $line_number),
                $suppressed_issues
            )) {
                return false;
            }
        }

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
        return $this->namespace;
    }

    public function getAliasedClasses()
    {
        return $this->aliased_classes;
    }

    public function getAbsoluteClass()
    {
        return $this->absolute_class;
    }

    public function getClassName()
    {
        return $this->class->name;
    }

    public function getParentClass()
    {
        return $this->parent_class;
    }

    public function getFileName()
    {
        return $this->file_name;
    }

    public function getClassLikeChecker()
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
        return $this->has_custom_get;
    }

    protected static function registerClass($class_name)
    {
        try {
            $reflected_class = new ReflectionClass($class_name);
        }
        catch (\ReflectionException $e) {
            return false;
        }

        if ($reflected_class->isUserDefined()) {
            $class_file_name = $reflected_class->getFileName();

            (new FileChecker($class_file_name))->check(true, false);
        }
        else {
            $class_properties = $reflected_class->getProperties();

            self::$public_class_properties[$class_name] = [];
            self::$protected_class_properties[$class_name] = [];
            self::$private_class_properties[$class_name] = [];

            self::$public_static_class_properties[$class_name] = [];
            self::$protected_static_class_properties[$class_name] = [];
            self::$private_static_class_properties[$class_name] = [];

            foreach ($class_properties as $class_property) {
                if ($class_property->isStatic()) {
                    if ($class_property->isPublic()) {
                        self::$public_static_class_properties[$class_name][$class_property->getName()] = Type::getMixed();
                    }
                    elseif ($class_property->isProtected()) {
                        self::$protected_static_class_properties[$class_name][$class_property->getName()] = Type::getMixed();
                    }
                    elseif ($class_property->isPrivate()) {
                        self::$private_static_class_properties[$class_name][$class_property->getName()] = Type::getMixed();
                    }
                }
                else {
                    if ($class_property->isPublic()) {
                        self::$public_class_properties[$class_name][$class_property->getName()] = Type::getMixed();
                    }
                    elseif ($class_property->isProtected()) {
                        self::$protected_class_properties[$class_name][$class_property->getName()] = Type::getMixed();
                    }
                    elseif ($class_property->isPrivate()) {
                        self::$private_class_properties[$class_name][$class_property->getName()] = Type::getMixed();
                    }
                }

            }

            $class_constants = $reflected_class->getConstants();

            self::$public_class_constants[$class_name] = [];

            foreach ($class_constants as $name => $value) {
                switch (gettype($value)) {
                    case 'boolean':
                        $const_type = Type::getBool();
                        break;

                    case 'integer':
                        $const_type = Type::getInt();
                        break;

                    case 'double':
                        $const_type = Type::getFloat();
                        break;

                    case 'string':
                        $const_type = Type::getString();
                        break;

                    case 'array':
                        $const_type = Type::getArray();
                        break;

                    case 'NULL':
                        $const_type = Type::getNull();
                        break;

                    default:
                        $const_type = Type::getMixed();
                }

                self::$public_class_constants[$class_name][$name] = $const_type;
            }
        }
    }

    public static function getInstancePropertiesForClass($class_name, $visibility)
    {
        if (!isset(self::$public_class_properties[$class_name])) {
            if (self::registerClass($class_name) === false) {
                return [];
            }
        }

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return self::$public_class_properties[$class_name];
        }
        elseif ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_merge(
                self::$public_class_properties[$class_name],
                self::$protected_class_properties[$class_name]
            );
        }
        elseif ($visibility === ReflectionProperty::IS_PRIVATE) {
            return array_merge(
                self::$public_class_properties[$class_name],
                self::$protected_class_properties[$class_name],
                self::$private_class_properties[$class_name]
            );
        }

        throw new \InvalidArgumentException('Must specify $visibility');
    }

    public static function getStaticPropertiesForClass($class_name, $visibility)
    {
        if (!isset(self::$public_static_class_properties[$class_name])) {
            if (self::registerClass($class_name) === false) {
                return [];
            }
        }

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return self::$public_static_class_properties[$class_name];
        }
        elseif ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_merge(
                self::$public_static_class_properties[$class_name],
                self::$protected_static_class_properties[$class_name]
            );
        }
        elseif ($visibility === ReflectionProperty::IS_PRIVATE) {
            return array_merge(
                self::$public_static_class_properties[$class_name],
                self::$protected_static_class_properties[$class_name],
                self::$private_static_class_properties[$class_name]
            );
        }

        throw new \InvalidArgumentException('Must specify $visibility');
    }

    public static function getConstantsForClass($class_name, $visibility)
    {
        // remove for PHP 7.1 support
        $visibility = ReflectionProperty::IS_PUBLIC;

        if (!isset(self::$public_class_constants[$class_name])) {
            if (self::registerClass($class_name) === false) {
                return [];
            }
        }

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return self::$public_class_constants[$class_name];
        }

        throw new \InvalidArgumentException('Given $visibility not supported');
    }

    public static function setConstantType($class_name, $const_name, Type\Union $type)
    {
        self::$public_class_constants[$class_name] = $type;
    }

    public function getSource()
    {
        return null;
    }

    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

    protected function registerInheritedMethods($parent_class, array $method_map = null)
    {
        if (!isset(self::$class_methods[$parent_class])) {
            $class_methods = [];

            $reflection_class = new ReflectionClass($parent_class);

            $reflection_methods = $reflection_class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

            foreach ($reflection_methods as $reflection_method) {
                if (!$reflection_method->isAbstract() && $reflection_method->getDeclaringClass()->getName() === $parent_class) {
                    $method_name = $reflection_method->getName();
                    $class_methods[] = $method_name;
                }
            }

            self::$class_methods[$parent_class] = $class_methods;
        }
        else {
            $class_methods = self::$class_methods[$parent_class];
        }

        foreach ($class_methods as $method_name) {
            $parent_method_id = $parent_class . '::' . $method_name;
            $implemented_method_id = $this->absolute_class . '::' . (isset($method_map[$method_name]) ? $method_map[$method_name] : $method_name);

            ClassMethodChecker::registerInheritedMethod($parent_method_id, $implemented_method_id);
        }
    }

    public static function setThisClass($this_class)
    {
        self::$this_class = $this_class;

        self::$class_checkers = [];
    }

    public static function getThisClass()
    {
        return self::$this_class;
    }

    public static function clearCache()
    {
        self::$method_checkers = [];

        self::$this_class = null;

        self::$existing_classes = [];
        self::$existing_classes_ci = [];
        self::$existing_interfaces_ci = [];
        self::$class_implements = [];

        self::$class_methods = [];
        self::$class_checkers = [];

        self::$public_class_properties = [];
        self::$protected_class_properties = [];
        self::$private_class_properties = [];

        self::$public_static_class_properties = [];
        self::$protected_static_class_properties = [];
        self::$private_static_class_properties = [];

        self::$class_extends = [];
    }
}
