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
    protected static $SPECIAL_TYPES = ['int', 'string', 'float', 'bool', 'false', 'object', 'empty', 'callable', 'array'];

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var string
     */
    protected $include_file_name;

    /**
     * @var PhpParser\Node\Stmt\ClassLike
     */
    protected $class;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var array<string>
     */
    protected $aliased_classes;

    /**
     * @var string
     */
    protected $absolute_class;

    /**
     * @var bool
     */
    protected $has_custom_get = false;

    /**
     * @var StatementsSource
     */
    protected $source;

    /**
     * The parent class
     *
     * @var string|null
     */
    protected $parent_class;

    /**
     * @var array
     */
    protected $suppressed_issues;

    /**
     * @var array<string,MethodChecker>
     */
    protected static $method_checkers = [];


    protected static $this_class = null;

    /**
     * A lookup table of all methods on a given class
     *
     * @var array<string,string>
     */
    protected static $class_methods = [];

    /**
     * A lookup table of cached ClassLikeCheckers
     *
     * @var array<string,self>
     */
    protected static $class_checkers = [];

    /**
     * A lookup table for public class properties
     *
     * @var array<string,string>
     */
    protected static $public_class_properties = [];

    /**
     * A lookup table for protected class properties
     *
     * @var array<string,string>
     */
    protected static $protected_class_properties = [];

    /**
     * A lookup table for protected class properties
     *
     * @var array<string,string>
     */
    protected static $private_class_properties = [];

    /**
     * A lookup table for public static class properties
     *
     * @var array<string,string>
     */
    protected static $public_static_class_properties = [];

    /**
     * A lookup table for protected static class properties
     *
     * @var array<string,string>
     */
    protected static $protected_static_class_properties = [];

    /**
     * A lookup table for private static class properties
     *
     * @var array<string,string>
     */
    protected static $private_static_class_properties = [];

    /**
     * A lookup table for public class constants
     *
     * @var array<string,string>
     */
    protected static $public_class_constants = [];

    /**
     * A lookup table to record which classes have been scanned
     *
     * @var array<string,bool>
     */
    protected static $registered_classes = [];

    /**
     * A lookup table used for storing the results of ClassChecker::classImplements
     *
     * @var array<string,bool>
     */
    protected static $class_implements = [];

    /** @var array<string,string> */
    protected static $class_files = [];

    /** @var array<string,array<int,string>> */
    protected static $file_classes = [];

    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $absolute_class)
    {
        $this->class = $class;
        $this->namespace = $source->getNamespace();
        $this->aliased_classes = $source->getAliasedClasses();
        $this->file_name = $source->getFileName();
        $this->include_file_name = $source->getIncludeFileName();
        $this->absolute_class = $absolute_class;

        $this->suppressed_issues = $source->getSuppressedIssues();

        self::$class_files[$absolute_class] = $this->file_name;
        self::$file_classes[$this->file_name][] = $absolute_class;

        if (self::$this_class) {
            self::$class_checkers[$absolute_class] = $this;
        }
    }

    public function check($check_methods = true, Context $class_context = null)
    {
        if (!$check_methods && !($this instanceof TraitChecker) && isset(self::$registered_classes[$this->absolute_class])) {
            return;
        }

        $config = Config::getInstance();

        self::$registered_classes[$this->absolute_class] = true;

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

        if (!$class_context) {
            $class_context = new Context($this->file_name, $this->absolute_class);
            $class_context->parent = $this->parent_class;
            $class_context->vars_in_scope['this'] = new Type\Union([new Type\Atomic($this->absolute_class)]);
        }

        // set all constants first
        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                foreach ($stmt->consts as $const) {
                    self::$public_class_constants[$class_context->self][$const->name] = Type::getMixed();
                }
            }
        }

        if ($this instanceof ClassChecker) {
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

                self::registerClass($this->parent_class);

                $this->registerInheritedMethods($this->parent_class);

                FileChecker::addFileInheritanceToClass(Config::getInstance()->getBaseDir() . $this->file_name, $this->parent_class);

                self::$class_implements[$this->absolute_class] += self::$class_implements[$this->parent_class];

                self::$public_class_properties[$this->absolute_class] = self::$public_class_properties[$this->parent_class];
                self::$protected_class_properties[$this->absolute_class] = self::$protected_class_properties[$this->parent_class];

                self::$public_static_class_properties[$this->absolute_class] = self::$public_static_class_properties[$this->parent_class];
                self::$protected_static_class_properties[$this->absolute_class] = self::$protected_static_class_properties[$this->parent_class];

                self::$public_class_constants[$this->absolute_class] = array_merge(
                    self::$public_class_constants[$this->parent_class],
                    self::$public_class_constants[$this->absolute_class]
                );
            }

            foreach (self::$class_implements[$this->absolute_class] as $interface_id => $interface_name) {
                if (self::checkAbsoluteClassOrInterface(
                    $interface_name,
                    $this->file_name,
                    $this->class->getLine(),
                    $this->getSuppressedIssues()
                ) === false
                ) {
                    return false;
                }

                self::registerClass($interface_name);

                FileChecker::addFileInheritanceToClass(Config::getInstance()->getBaseDir() . $this->file_name, $interface_name);
            }

            foreach (ClassChecker::getInterfacesForClass($this->absolute_class) as $interface_id => $interface_name) {
                if (isset(self::$public_class_constants[$interface_name])) {
                    self::$public_class_constants[$this->absolute_class] += self::$public_class_constants[$interface_name];
                }
            }
        }

        $trait_checkers = [];

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_id = $this->absolute_class . '::' . strtolower($stmt->name);

                if (!isset(self::$method_checkers[$method_id])) {
                    $method_checker = new MethodChecker($stmt, $this);
                    $method_checkers[$stmt->name] = $method_checker;

                    if (self::$this_class && !$check_methods) {
                        self::$method_checkers[$method_id] = $method_checker;
                    }
                }
                else {
                    $method_checker = self::$method_checkers[$method_id];
                }

                if (!$stmt->isAbstract()) {
                    MethodChecker::setDeclaringMethod($class_context->self . '::' . $this->getMappedMethodName(strtolower($stmt->name)), $method_id);
                    self::$class_methods[$class_context->self][strtolower($stmt->name)] = true;
                }

            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                $method_map = [];
                foreach ($stmt->adaptations as $adaptation) {
                    if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                        if ($adaptation->method && $adaptation->newName) {
                            $method_map[strtolower($adaptation->method)] = strtolower($adaptation->newName);
                        }
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

                        $trait_checker = FileChecker::getClassLikeCheckerFromClass($trait_name);

                        $trait_checker->setMethodMap($method_map);

                        $trait_checker->check(false, $class_context);

                        FileChecker::addFileInheritanceToClass(Config::getInstance()->getBaseDir() . $this->file_name, $this->parent_class);

                        $trait_checkers[] = $trait_checker;
                    }
                }
            } else {
                if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                    $comment = $stmt->getDocComment();
                    $type_in_comment = null;

                    if ($comment && $config->use_docblock_types && count($stmt->props) === 1) {
                        $type_in_comment = CommentChecker::getTypeFromComment((string) $comment, null, $this);
                    }

                    $property_group_type = $type_in_comment ? $type_in_comment : null;

                    foreach ($stmt->props as $property) {
                        if (!$property_group_type) {
                            if (!$property->default) {
                                $property_type = Type::getMixed();
                            }
                            else {
                                $property_type = StatementsChecker::getSimpleType($property->default) ?: Type::getMixed();
                            }
                        }
                        else {
                            $property_type = $property_group_type;
                        }

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

        $all_instance_properties = array_merge(
            self::$public_class_properties[$this->absolute_class],
            self::$protected_class_properties[$this->absolute_class],
            self::$private_class_properties[$this->absolute_class]
        );

        foreach ($all_instance_properties as $property_name => $property_type) {
            $class_context->vars_in_scope['this->' . $property_name] = $property_type;
        }

        $config = Config::getInstance();

        if ($check_methods) {
            foreach ($trait_checkers as $trait_checker) {
                $trait_checker->check(true, $class_context);
            }

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
     * @return MethodChecker
     */
    public static function getMethodChecker($method_id)
    {
        if (isset(self::$method_checkers[$method_id])) {
            return self::$method_checkers[$method_id];
        }

        MethodChecker::registerClassMethod($method_id);

        $declaring_method_id = MethodChecker::getDeclaringMethod($method_id);
        $declaring_class = explode('::', $declaring_method_id)[0];

        $class_checker = FileChecker::getClassLikeCheckerFromClass($declaring_class);

        if (!$class_checker) {
            throw new \InvalidArgumentException('Could not get class checker for ' . $declaring_class);
        }

        foreach ($class_checker->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                if ($declaring_method_id === $class_checker->absolute_class . '::' . strtolower($stmt->name)) {
                    $method_checker = new MethodChecker($stmt, $class_checker);
                    self::$method_checkers[$method_id] = $method_checker;
                    return $method_checker;
                }
            }
        }

        throw new \InvalidArgumentException('Method checker not found');
    }

    /**
     * Returns a class checker for the given class, if one has already been registered
     *
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
     * Check whether a class/interface exists
     *
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

        FileChecker::addFileReferenceToClass(Config::getInstance()->getBaseDir() . $file_name, $absolute_class);

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

            if (isset($imported_namespaces[strtolower($first_namespace)])) {
                return $imported_namespaces[strtolower($first_namespace)] . '\\' . implode('\\', $class_parts);
            }
        } elseif (isset($imported_namespaces[strtolower($class)])) {
            return $imported_namespaces[strtolower($class)];
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

    public function getIncludeFileName()
    {
        return $this->include_file_name;
    }

    public function setIncludeFileName($file_name)
    {
        $this->include_file_name = $file_name;
    }

    public function getCheckedFileName()
    {
        return $this->include_file_name ?: $this->file_name;
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

    public static function registerClass($class_name)
    {
        if (isset(self::$registered_classes[$class_name])) {
            return true;
        }

        try {
            $reflected_class = new ReflectionClass($class_name);
        }
        catch (\ReflectionException $e) {
            return false;
        }

        if ($reflected_class->isUserDefined()) {
            $class_file_name = $reflected_class->getFileName();

            $file_checker = new FileChecker($class_file_name);

            $short_file_name = $file_checker->getFileName();

            self::$class_files[$class_name] = $class_file_name;
            self::$file_classes[$class_file_name][] = $class_name;

            // this doesn't work on traits
            $file_checker->check(true, false);
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

            self::$registered_classes[$class_name] = true;

            if (!$reflected_class->isTrait() && !$reflected_class->isInterface()) {
                ClassChecker::getInterfacesForClass($class_name);
            }

            $reflection_methods = $reflected_class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);

            self::$class_methods[$class_name] = [];

            foreach ($reflection_methods as $reflection_method) {
                MethodChecker::extractReflectionMethodInfo($reflection_method);

                if ($reflection_method->class !== $class_name) {
                    MethodChecker::setDeclaringMethod(
                        $class_name . '::' . strtolower($reflection_method->name),
                        $reflection_method->class . '::' . strtolower($reflection_method->name)
                    );

                    self::$class_methods[$class_name][strtolower($reflection_method->name)] = true;
                }

                if (!$reflection_method->isAbstract() && $reflection_method->getDeclaringClass()->getName() === $class_name) {
                    self::$class_methods[$class_name][strtolower($reflection_method->getName())] = true;
                }
            }
        }

        return true;
    }

    protected function registerInheritedMethods($parent_class)
    {
        $class_methods = self::$class_methods[$parent_class];

        foreach ($class_methods as $method_name => $_) {
            $parent_method_id = $parent_class . '::' . $method_name;
            $declaring_method_id = MethodChecker::getDeclaringMethod($parent_method_id);
            $implemented_method_id = $this->absolute_class . '::' . $method_name;

            if (!isset(self::$class_methods[$this->absolute_class][$method_name])) {
                MethodChecker::setDeclaringMethod($implemented_method_id, $declaring_method_id);
                self::$class_methods[$this->absolute_class][$method_name] = true;
            }
        }
    }

    public static function getInstancePropertiesForClass($class_name, $visibility)
    {
        if (self::registerClass($class_name) === false) {
            return [];
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
        if (self::registerClass($class_name) === false) {
            return [];
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

        if (self::registerClass($class_name) === false) {
            return [];
        }

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return self::$public_class_constants[$class_name];
        }

        throw new \InvalidArgumentException('Given $visibility not supported');
    }

    public static function setConstantType($class_name, $const_name, Type\Union $type)
    {
        self::$public_class_constants[$class_name][$const_name] = $type;
    }

    public function getSource()
    {
        return null;
    }

    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
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

    protected function getMappedMethodName($method_name)
    {
        return $method_name;
    }

    public static function getClassesForFile($file_name)
    {
        return isset(self::$file_classes[$file_name]) ? array_unique(self::$file_classes[$file_name]) : [];
    }

    public static function clearCache()
    {
        self::$method_checkers = [];

        self::$this_class = null;

        self::$class_implements = [];

        self::$class_methods = [];
        self::$class_checkers = [];

        self::$public_class_properties = [];
        self::$protected_class_properties = [];
        self::$private_class_properties = [];

        self::$public_static_class_properties = [];
        self::$protected_static_class_properties = [];
        self::$private_static_class_properties = [];

        self::$class_references = [];

        ClassChecker::clearCache();
        InterfaceChecker::clearCache();
    }
}
