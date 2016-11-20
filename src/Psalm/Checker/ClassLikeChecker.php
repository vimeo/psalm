<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedTrait;
use Psalm\Issue\UnimplementedInterfaceMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Type;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class ClassLikeChecker implements StatementsSource
{
    /**
     * @var array
     */
    protected static $SPECIAL_TYPES = [
        'int',
        'string',
        'float',
        'bool',
        'false',
        'object',
        'empty',
        'callable',
        'array'
    ];

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var string|null
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
    protected $fq_class_name;

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

    /**
     * @var string|null
     */
    protected static $this_class = null;

    /**
     * A lookup table of all methods on a given class
     *
     * @var array<string,array<string,bool>>
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
     * @var array<string,array<string,Type\Union|false>>
     */
    protected static $public_class_properties = [];

    /**
     * A lookup table for protected class properties
     *
     * @var array<string,array<string,Type\Union|false>>
     */
    protected static $protected_class_properties = [];

    /**
     * A lookup table for protected class properties
     *
     * @var array<string,array<string,Type\Union|false>>
     */
    protected static $private_class_properties = [];

    /**
     * A lookup table for public static class properties
     *
     * @var array<string,array<string,Type\Union|false>>
     */
    protected static $public_static_class_properties = [];

    /**
     * A lookup table for protected static class properties
     *
     * @var array<string,array<string,Type\Union|false>>
     */
    protected static $protected_static_class_properties = [];

    /**
     * A lookup table for private static class properties
     *
     * @var array<string,array<string,Type\Union|false>>
     */
    protected static $private_static_class_properties = [];

    /**
     * A lookup table for public class constants
     *
     * @var array<string,array<string,Type\Union>>
     */
    protected static $public_class_constants = [];

    /**
     * A lookup table to record which classes have been scanned
     *
     * @var array<string,bool>
     */
    protected static $registered_classes = [];

    /**
     * A lookup table to record which classes are user-defined
     *
     * @var array<string,bool>
     */
    protected static $user_defined = [];

    /**
     * A lookup table used for storing the results of ClassChecker::classImplements
     *
     * @var array<string,array<string,string>>
     */
    protected static $class_implements = [];

    /**
     * A lookup table for interface parents
     *
     * @var array<string, array<string>>
     */
    protected static $parent_interfaces = [];

    /**
     * @var array<string,string>
     */
    protected static $class_files = [];

    /**
     * @var array<string,array<int,string>>
     */
    protected static $file_classes = [];

    /**
     * @var array<string,array<string,string>>|null
     */
    protected static $property_map;

    /**
     * @param PhpParser\Node\Stmt\ClassLike $class
     * @param StatementsSource              $source
     * @param string                        $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $fq_class_name)
    {
        $this->class = $class;
        $this->source = $source;
        $this->namespace = $source->getNamespace();
        $this->aliased_classes = $source->getAliasedClasses();
        $this->file_name = $source->getFileName();
        $this->include_file_name = $source->getIncludeFileName();
        $this->fq_class_name = $fq_class_name;

        $this->suppressed_issues = $source->getSuppressedIssues();

        self::$class_files[$fq_class_name] = $this->file_name;
        self::$file_classes[$this->file_name][] = $fq_class_name;

        if (self::$this_class) {
            self::$class_checkers[$fq_class_name] = $this;
        }
    }

    /**
     * @param bool          $check_methods
     * @param Context|null  $class_context
     * @param bool          $update_docblocks
     * @return false|null
     */
    public function check($check_methods = true, Context $class_context = null, $update_docblocks = false)
    {
        if (!$check_methods &&
            !($this instanceof TraitChecker) &&
            isset(self::$registered_classes[$this->fq_class_name])
        ) {
            return null;
        }

        $config = Config::getInstance();

        self::$registered_classes[$this->fq_class_name] = true;
        self::$user_defined[$this->fq_class_name] = true;

        $leftover_stmts = [];

        /** @var array<MethodChecker> */
        $method_checkers = [];

        $long_file_name = Config::getInstance()->getBaseDir() . $this->file_name;

        self::$class_methods[$this->fq_class_name] = [];

        self::$public_class_properties[$this->fq_class_name] = [];
        self::$protected_class_properties[$this->fq_class_name] = [];
        self::$private_class_properties[$this->fq_class_name] = [];

        self::$public_static_class_properties[$this->fq_class_name] = [];
        self::$protected_static_class_properties[$this->fq_class_name] = [];
        self::$private_static_class_properties[$this->fq_class_name] = [];

        self::$public_class_constants[$this->fq_class_name] = [];

        if (!$class_context) {
            $class_context = new Context($this->file_name, $this->fq_class_name);
            $class_context->parent = $this->parent_class;
            $class_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic($this->fq_class_name)]);
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
            if ($this->parent_class && $this->registerParentClassProperties($this->parent_class) === false) {
                return false;
            }
        }

        if ($this instanceof InterfaceChecker || $this instanceof ClassChecker) {
            $extra_interfaces = [];

            if ($this instanceof InterfaceChecker) {
                $parent_interfaces = InterfaceChecker::getParentInterfaces($this->fq_class_name);
                $extra_interfaces = $parent_interfaces;
            }
            else {
                $parent_interfaces = self::$class_implements[$this->fq_class_name];
            }

            foreach ($parent_interfaces as $interface_name) {
                if (self::checkFullyQualifiedClassLikeName(
                    $interface_name,
                    $this->file_name,
                    $this->class->getLine(),
                    $this->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                $extra_interfaces = array_merge(
                    $extra_interfaces,
                    InterfaceChecker::getParentInterfaces($interface_name)
                );

                FileChecker::addFileInheritanceToClass($long_file_name, $interface_name);
            }

            $extra_interfaces = array_unique($extra_interfaces);

            foreach ($extra_interfaces as $extra_interface_name) {
                FileChecker::addFileInheritanceToClass($long_file_name, $extra_interface_name);

                if ($this instanceof ClassChecker) {
                    self::$class_implements[$this->fq_class_name][strtolower($extra_interface_name)] =
                        $extra_interface_name;
                }
                else {
                    $this->registerInheritedMethods($extra_interface_name);
                }
            }
        }

        $trait_checkers = [];

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $this->visitClassMethod(
                    $stmt,
                    $class_context,
                    $method_checkers,
                    self::$this_class && !$check_methods
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                $this->visitTraitUse($stmt, $class_context, $trait_checkers);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                $this->visitPropertyDeclaration(
                    $stmt,
                    $class_context,
                    $config,
                    $check_methods
                );
                $leftover_stmts[] = $stmt;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $this->visitClassConstDeclaration($stmt, $class_context, $config);
                $leftover_stmts[] = $stmt;
            }
        }

        if (MethodChecker::methodExists($this->fq_class_name . '::__get')) {
            $this->has_custom_get = true;
        }

        if ($leftover_stmts) {
            (new StatementsChecker($this))->check($leftover_stmts, $class_context);
        }

        $all_instance_properties = array_merge(
            self::$public_class_properties[$this->fq_class_name],
            self::$protected_class_properties[$this->fq_class_name],
            self::$private_class_properties[$this->fq_class_name]
        );

        foreach ($all_instance_properties as $property_name => $property_type) {
            $class_context->vars_in_scope['$this->' . $property_name] = $property_type ?: Type::getMixed();
        }

        $all_static_properties = array_merge(
            self::$public_static_class_properties[$this->fq_class_name],
            self::$protected_static_class_properties[$this->fq_class_name],
            self::$private_static_class_properties[$this->fq_class_name]
        );

        foreach ($all_static_properties as $property_name => $property_type) {
            $class_context->vars_in_scope[$this->fq_class_name . '::$' . $property_name] = $property_type
                ?: Type::getMixed();
        }

        $config = Config::getInstance();

        if ($this instanceof ClassChecker) {
            foreach (ClassChecker::getInterfacesForClass($this->fq_class_name) as $interface_id => $interface_name) {
                if (isset(self::$public_class_constants[$interface_name])) {
                    self::$public_class_constants[$this->fq_class_name] +=
                        self::$public_class_constants[$interface_name];
                }

                foreach (self::$class_methods[$interface_name] as $method_name => $_) {
                    $mentioned_method_id = $interface_name . '::' . $method_name;
                    $implemented_method_id = $this->fq_class_name . '::' . $method_name;
                    MethodChecker::setOverriddenMethodId($implemented_method_id, $mentioned_method_id);

                    if (!isset(self::$class_methods[$this->fq_class_name])) {
                        if (IssueBuffer::accepts(
                            new UnimplementedInterfaceMethod(
                                'Method ' . $method_name . ' is not defined on class ' . $this->fq_class_name,
                                $this->file_name,
                                $this->class->getLine()
                            ),
                            $this->suppressed_issues
                        )) {
                            return false;
                        }

                        return null;
                    }
                }
            }
        }

        if ($check_methods) {
            foreach ($trait_checkers as $trait_checker) {
                $trait_checker->check(true, $class_context);
            }

            // do the method checks after all class methods have been initialised
            foreach ($method_checkers as $method_checker) {
                $method_checker->check(clone $class_context);

                if (!$config->excludeIssueInFile('InvalidReturnType', $this->file_name)) {
                    $method_checker->checkReturnTypes($update_docblocks);
                }
            }
        }

        if (!$this->class->name) {
            $this->class->name = $this->fq_class_name;
        }

        return null;
    }

    /**
     * @param  string  $parent_class
     * @return false|null
     */
    protected function registerParentClassProperties($parent_class)
    {
        if (self::checkFullyQualifiedClassLikeName(
            $parent_class,
            $this->file_name,
            $this->class->getLine(),
            $this->getSuppressedIssues()
        ) === false
        ) {
            return false;
        }

        self::registerClass($parent_class);

        $this->registerInheritedMethods($parent_class);

        FileChecker::addFileInheritanceToClass(Config::getInstance()->getBaseDir() . $this->file_name, $parent_class);

        self::$class_implements[$this->fq_class_name] += self::$class_implements[$parent_class];

        self::$public_class_properties[$this->fq_class_name] = self::$public_class_properties[$parent_class];
        self::$protected_class_properties[$this->fq_class_name] = self::$protected_class_properties[$parent_class];

        self::$public_static_class_properties[$this->fq_class_name] =
            self::$public_static_class_properties[$parent_class];

        self::$protected_static_class_properties[$this->fq_class_name] =
            self::$protected_static_class_properties[$parent_class];

        self::$public_class_constants[$this->fq_class_name] = array_merge(
            self::$public_class_constants[$parent_class],
            self::$public_class_constants[$this->fq_class_name]
        );

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\ClassMethod $stmt
     * @param   Context                         $class_context
     * @param   array<MethodChecker>            $method_checkers
     * @param   bool                            $cache_method_checker
     * @return  void
     */
    protected function visitClassMethod(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        Context $class_context,
        array &$method_checkers,
        $cache_method_checker
    ) {
        $method_id = $this->fq_class_name . '::' . strtolower($stmt->name);

        if (!isset(self::$method_checkers[$method_id])) {
            $method_checker = new MethodChecker($stmt, $this);
            $method_checkers[$stmt->name] = $method_checker;

            if ($cache_method_checker) {
                self::$method_checkers[$method_id] = $method_checker;
            }
        } else {
            $method_checker = self::$method_checkers[$method_id];
        }

        if (!$stmt->isAbstract()) {
            MethodChecker::setDeclaringMethodId(
                $class_context->self . '::' . $this->getMappedMethodName(strtolower($stmt->name)),
                $method_id
            );

            self::$class_methods[$class_context->self][strtolower($stmt->name)] = true;
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\TraitUse    $stmt
     * @param   Context                         $class_context
     * @param   array<TraitChecker>             $trait_checkers
     * @return  false|null
     */
    protected function visitTraitUse(
        PhpParser\Node\Stmt\TraitUse $stmt,
        Context $class_context,
        array &$trait_checkers
    ) {
        $method_map = [];

        foreach ($stmt->adaptations as $adaptation) {
            if ($adaptation instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Alias) {
                if ($adaptation->method && $adaptation->newName) {
                    $method_map[strtolower($adaptation->method)] = strtolower($adaptation->newName);
                }
            }
        }

        foreach ($stmt->traits as $trait) {
            $trait_name = self::getFQCLNFromNameObject(
                $trait,
                $this->namespace,
                $this->aliased_classes
            );

            if (!TraitChecker::traitExists($trait_name)) {
                if (IssueBuffer::accepts(
                    new UndefinedTrait('Trait ' . $trait_name . ' does not exist', $this->file_name, $trait->getLine()),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            } else {
                try {
                    $reflection_trait = new \ReflectionClass($trait_name);
                } catch (\ReflectionException $e) {
                    if (IssueBuffer::accepts(
                        new UndefinedTrait(
                            'Trait ' . $trait_name . ' has wrong casing',
                            $this->file_name,
                            $trait->getLine()
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }

                    continue;
                }

                /** @var TraitChecker */
                $trait_checker = FileChecker::getClassLikeCheckerFromClass($trait_name);

                $trait_checker->setMethodMap($method_map);

                $trait_checker->check(false, $class_context);

                FileChecker::addFileInheritanceToClass(
                    Config::getInstance()->getBaseDir() . $this->file_name,
                    $trait_name
                );

                $trait_checkers[] = $trait_checker;
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\Property    $stmt
     * @param   Context                         $class_context
     * @param   Config                          $config
     * @param   bool                            $check_property_types
     * @return  void
     */
    protected function visitPropertyDeclaration(
        PhpParser\Node\Stmt\Property $stmt,
        Context $class_context,
        Config $config,
        $check_property_types
    ) {
        $comment = $stmt->getDocComment();
        $type_in_comment = null;

        if ($comment && $config->use_docblock_types) {
            $type_in_comment = CommentChecker::getTypeFromComment((string) $comment, null, $this);
        } elseif (!$comment && $check_property_types) {
            if (IssueBuffer::accepts(
                new MissingPropertyType(
                    'Property ' . $this->fq_class_name . '::$' . $stmt->props[0]->name . ' does not have a ' .
                        'declared type',
                    $this->file_name,
                    $stmt->getLine()
                ),
                $this->suppressed_issues
            )) {
                // fall through
            }
        }

        $property_group_type = $type_in_comment ? $type_in_comment : null;

        foreach ($stmt->props as $property) {
            if (!$property_group_type) {
                if (!$property->default) {
                    $property_type = false;
                } else {
                    $property_type = StatementsChecker::getSimpleType($property->default) ?: Type::getMixed();
                }
            } else {
                $property_type = count($stmt->props) === 1 ? $property_group_type : clone $property_group_type;
            }

            if ($stmt->isStatic()) {
                if ($stmt->isPublic()) {
                    self::$public_static_class_properties[$class_context->self][$property->name] = $property_type;
                } elseif ($stmt->isProtected()) {
                    self::$protected_static_class_properties[$class_context->self][$property->name] = $property_type;
                } elseif ($stmt->isPrivate()) {
                    self::$private_static_class_properties[$class_context->self][$property->name] = $property_type;
                }
            } else {
                if ($stmt->isPublic()) {
                    self::$public_class_properties[$class_context->self][$property->name] = $property_type;
                } elseif ($stmt->isProtected()) {
                    self::$protected_class_properties[$class_context->self][$property->name] = $property_type;
                } elseif ($stmt->isPrivate()) {
                    self::$private_class_properties[$class_context->self][$property->name] = $property_type;
                }
            }
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\ClassConst  $stmt
     * @param   Context                         $class_context
     * @param   Config                          $config
     * @return  void
     */
    protected function visitClassConstDeclaration(
        PhpParser\Node\Stmt\ClassConst $stmt,
        Context $class_context,
        Config $config
    ) {
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

        /** @var string */
        $declaring_method_id = MethodChecker::getDeclaringMethodId($method_id);
        $declaring_class = explode('::', $declaring_method_id)[0];

        $class_checker = FileChecker::getClassLikeCheckerFromClass($declaring_class);

        if (!$class_checker) {
            throw new \InvalidArgumentException('Could not get class checker for ' . $declaring_class);
        }

        foreach ($class_checker->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                if ($declaring_method_id === $class_checker->fq_class_name . '::' . strtolower($stmt->name)) {
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
     * @param  string $fq_class_name
     * @return bool
     */
    public static function classOrInterfaceExists($fq_class_name)
    {
        return ClassChecker::classExists($fq_class_name) || InterfaceChecker::interfaceExists($fq_class_name);
    }

    /**
     * @param  string $fq_class_name
     * @param  string $possible_parent
     * @return bool
     */
    public static function classExtendsOrImplements($fq_class_name, $possible_parent)
    {
        return ClassChecker::classExtends($fq_class_name, $possible_parent)
            || ClassChecker::classImplements($fq_class_name, $possible_parent);
    }

    /**
     * @param  string           $fq_class_name
     * @param  string           $file_name
     * @param  int              $line_number
     * @param  array<string>    $suppressed_issues
     * @return bool|null
     */
    public static function checkFullyQualifiedClassLikeName(
        $fq_class_name,
        $file_name,
        $line_number,
        array $suppressed_issues
    ) {
        if (empty($fq_class_name)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name);

        $class_exists = ClassChecker::classExists($fq_class_name);
        $interface_exists = InterfaceChecker::interfaceExists($fq_class_name);

        if (!$class_exists && !$interface_exists) {
            if (IssueBuffer::accepts(
                new UndefinedClass(
                    'Class or interface ' . $fq_class_name . ' does not exist',
                    $file_name,
                    $line_number
                ),
                $suppressed_issues
            )) {
                return false;
            }

            return null;
        }

        if (($class_exists && !ClassChecker::hasCorrectCasing($fq_class_name))
            || ($interface_exists && !InterfaceChecker::hasCorrectCasing($fq_class_name))
        ) {
            if (IssueBuffer::accepts(
                new InvalidClass(
                    'Class or interface ' . $fq_class_name . ' has wrong casing',
                    $file_name,
                    $line_number
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        FileChecker::addFileReferenceToClass(Config::getInstance()->getBaseDir() . $file_name, $fq_class_name);

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     *
     * @param  PhpParser\Node\Name $class_name
     * @param  string              $namespace
     * @param  array<int,string>   $aliased_classes
     * @return string
     */
    public static function getFQCLNFromNameObject(
        PhpParser\Node\Name $class_name,
        $namespace,
        array $aliased_classes
    ) {
        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return implode('\\', $class_name->parts);
        }

        return self::getFQCLNFromString(implode('\\', $class_name->parts), $namespace, $aliased_classes);
    }

    /**
     * @param  string                   $class
     * @param  string                   $namespace
     * @param  array<string, string>    $imported_namespaces
     * @return string
     */
    public static function getFQCLNFromString($class, $namespace, array $imported_namespaces)
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

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getAliasedClasses()
    {
        return $this->aliased_classes;
    }

    /**
     * @return array<string, string>
     */
    public function getAliasedClassesFlipped()
    {
        if ($this->source instanceof NamespaceChecker || $this->source instanceof FileChecker) {
            return $this->source->getAliasedClassesFlipped();
        }

        return [];
    }

    /**
     * @return string
     */
    public function getFQCLN()
    {
        return $this->fq_class_name;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->class->name;
    }

    /**
     * @return string|null
     */
    public function getParentClass()
    {
        return $this->parent_class;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->file_name;
    }

    /**
     * @return null|string
     */
    public function getIncludeFileName()
    {
        return $this->include_file_name;
    }

    /**
     * @param string|null $file_name
     * @return  void
     */
    public function setIncludeFileName($file_name)
    {
        $this->include_file_name = $file_name;
    }

    /**
     * @return string
     */
    public function getCheckedFileName()
    {
        return $this->include_file_name ?: $this->file_name;
    }

    /**
     * @return $this
     */
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

    /**
     * @return bool
     */
    public function hasCustomGet()
    {
        return $this->has_custom_get;
    }

    /**
     * @param  string $class_name
     * @return boolean
     * @psalm-suppress MixedMethodCall due to Reflection class weirdness
     */
    public static function registerClass($class_name)
    {
        if (isset(self::$registered_classes[$class_name])) {
            return true;
        }

        if (!$class_name || strpos($class_name, '::') !== false) {
            throw new \InvalidArgumentException('Invalid class name ' . $class_name);
        }

        try {
            $old_level = error_reporting();
            error_reporting(0);
            $reflected_class = new ReflectionClass($class_name);
            error_reporting($old_level);
        } catch (\ReflectionException $e) {
            return false;
        }

        if ($reflected_class->isUserDefined()) {
            $class_file_name = (string)$reflected_class->getFileName();

            $file_checker = new FileChecker($class_file_name);

            $short_file_name = $file_checker->getFileName();

            self::$class_files[$class_name] = $class_file_name;
            self::$file_classes[$class_file_name][] = $class_name;

            // this doesn't work on traits
            $file_checker->check(true, false);

            if (self::inPropertyMap($class_name)) {
                $public_mapped_properties = self::getPropertyMap()[strtolower($class_name)];

                foreach ($public_mapped_properties as $property_name => $public_mapped_property) {
                    self::$public_class_properties[$class_name][$property_name] = Type::parseString(
                        $public_mapped_property
                    );
                }
            }
        } else {
            self::registerReflectedClass($class_name, $reflected_class);
        }

        return true;
    }

    /**
     * @param  string          $class_name
     * @param  ReflectionClass $reflected_class
     * @return void
     */
    protected static function registerReflectedClass($class_name, ReflectionClass $reflected_class)
    {
        self::$public_class_properties[$class_name] = [];
        self::$protected_class_properties[$class_name] = [];
        self::$private_class_properties[$class_name] = [];

        self::$public_static_class_properties[$class_name] = [];
        self::$protected_static_class_properties[$class_name] = [];
        self::$private_static_class_properties[$class_name] = [];

        $parent_class = $reflected_class->getParentClass();

        if ($parent_class) {
            $parent_class_name = $parent_class->getName();
            self::registerClass($parent_class_name);

            self::$public_class_properties[$class_name] = self::$public_class_properties[$parent_class_name];
            self::$protected_class_properties[$class_name] = self::$protected_class_properties[$parent_class_name];

            self::$public_static_class_properties[$class_name] =
                self::$public_static_class_properties[$parent_class_name];

            self::$protected_static_class_properties[$class_name] =
                self::$protected_static_class_properties[$parent_class_name];
        }

        $class_properties = $reflected_class->getProperties();

        /** @var \ReflectionProperty $class_property */
        foreach ($class_properties as $class_property) {
            if ($class_property->isStatic()) {
                if ($class_property->isPublic()) {
                    self::$public_static_class_properties[$class_name][$class_property->getName()] =
                        Type::getMixed();
                } elseif ($class_property->isProtected()) {
                    self::$protected_static_class_properties[$class_name][$class_property->getName()] =
                        Type::getMixed();
                } elseif ($class_property->isPrivate()) {
                    self::$private_static_class_properties[$class_name][$class_property->getName()] =
                        Type::getMixed();
                }
            } else {
                if ($class_property->isPublic()) {
                    self::$public_class_properties[$class_name][$class_property->getName()] =
                        Type::getMixed();
                } elseif ($class_property->isProtected()) {
                    self::$protected_class_properties[$class_name][$class_property->getName()] =
                        Type::getMixed();
                } elseif ($class_property->isPrivate()) {
                    self::$private_class_properties[$class_name][$class_property->getName()] =
                        Type::getMixed();
                }
            }
        }

        if (self::inPropertyMap($class_name)) {
            $public_mapped_properties = self::getPropertyMap()[strtolower($class_name)];

            foreach ($public_mapped_properties as $property_name => $public_mapped_property) {
                self::$public_class_properties[$class_name][$property_name] = Type::parseString(
                    $public_mapped_property
                );
            }
        }

        $class_constants = $reflected_class->getConstants();

        if ($reflected_class->isInterface()) {
            self::$parent_interfaces[$class_name] = [];
        }

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

        $reflection_methods = $reflected_class->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        self::$class_methods[$class_name] = [];

        foreach ($reflection_methods as $reflection_method) {
            MethodChecker::extractReflectionMethodInfo($reflection_method);

            if ($reflection_method->class !== $class_name) {
                MethodChecker::setDeclaringMethodId(
                    $class_name . '::' . strtolower((string)$reflection_method->name),
                    $reflection_method->class . '::' . strtolower((string)$reflection_method->name)
                );

                self::$class_methods[$class_name][strtolower((string)$reflection_method->name)] = true;
            }

            if (!$reflection_method->isAbstract() &&
                $reflection_method->getDeclaringClass()->getName() === $class_name
            ) {
                self::$class_methods[$class_name][strtolower((string)$reflection_method->getName())] = true;
            }
        }
    }

    /**
     * @param string $parent_class
     * @return void
     */
    protected function registerInheritedMethods($parent_class)
    {
        $class_methods = self::$class_methods[$parent_class];

        foreach ($class_methods as $method_name => $_) {
            $parent_method_id = $parent_class . '::' . $method_name;
            /** @var string */
            $declaring_method_id = MethodChecker::getDeclaringMethodId($parent_method_id);
            $implemented_method_id = $this->fq_class_name . '::' . $method_name;

            if (!isset(self::$class_methods[$this->fq_class_name][$method_name])) {
                MethodChecker::setDeclaringMethodId($implemented_method_id, $declaring_method_id);
                self::$class_methods[$this->fq_class_name][$method_name] = true;
                MethodChecker::setOverriddenMethodId($implemented_method_id, $declaring_method_id);
            }
        }
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     * @return array<string,Type\Union|false>
     */
    public static function getInstancePropertiesForClass($class_name, $visibility)
    {
        if (self::registerClass($class_name) === false) {
            return [];
        }

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return self::$public_class_properties[$class_name];
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_merge(
                self::$public_class_properties[$class_name],
                self::$protected_class_properties[$class_name]
            );
        }

        if ($visibility === ReflectionProperty::IS_PRIVATE) {
            return array_merge(
                self::$public_class_properties[$class_name],
                self::$protected_class_properties[$class_name],
                self::$private_class_properties[$class_name]
            );
        }

        throw new \InvalidArgumentException('Must specify $visibility');
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     * @return array<string,Type\Union|false>
     */
    public static function getStaticPropertiesForClass($class_name, $visibility)
    {
        if (self::registerClass($class_name) === false) {
            return [];
        }

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return self::$public_static_class_properties[$class_name];
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_merge(
                self::$public_static_class_properties[$class_name],
                self::$protected_static_class_properties[$class_name]
            );
        }

        if ($visibility === ReflectionProperty::IS_PRIVATE) {
            return array_merge(
                self::$public_static_class_properties[$class_name],
                self::$protected_static_class_properties[$class_name],
                self::$private_static_class_properties[$class_name]
            );
        }

        throw new \InvalidArgumentException('Must specify $visibility');
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     * @return array<string,Type\Union>
     */
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

    /**
     * @param   string      $class_name
     * @param   string      $const_name
     * @param   Type\Union  $type
     * @return  void
     */
    public static function setConstantType($class_name, $const_name, Type\Union $type)
    {
        self::$public_class_constants[$class_name][$const_name] = $type;
    }

    /**
     * @return null
     */
    public function getSource()
    {
        return null;
    }

    /**
     * Get a list of suppressed issues
     *
     * @return array<string>
     */
    public function getSuppressedIssues()
    {
        return $this->suppressed_issues;
    }

    /**
     * @param   string|null $this_class
     * @return  void
     */
    public static function setThisClass($this_class)
    {
        self::$this_class = $this_class;

        self::$class_checkers = [];
    }

    /**
     * @return string|null
     */
    public static function getThisClass()
    {
        return self::$this_class;
    }

    /**
     * @param   string $method_name
     * @return  mixed
     */
    protected function getMappedMethodName($method_name)
    {
        return $method_name;
    }

    /**
     * @param   string $file_name
     * @return  array
     */
    public static function getClassesForFile($file_name)
    {
        return isset(self::$file_classes[$file_name]) ? array_unique(self::$file_classes[$file_name]) : [];
    }

    /**
     * @param  string  $fq_class_name
     * @return boolean
     */
    public static function isUserDefined($fq_class_name)
    {
        self::registerClass($fq_class_name);
        return isset(self::$user_defined[$fq_class_name]);
    }

    /**
     * Gets the method/function call map
     *
     * @return array<string, array<string, string>>
     * @psalm-suppress MixedInferredReturnType as the use of require buggers things up
     */
    protected static function getPropertyMap()
    {
        if (self::$property_map !== null) {
            return self::$property_map;
        }

        $property_map = require_once(__DIR__.'/../PropertyMap.php');

        self::$property_map = [];

        foreach ($property_map as $key => $value) {
            $cased_key = strtolower($key);
            self::$property_map[$cased_key] = $value;
        }

        return self::$property_map;
    }

    /**
     * @param   string $class_name
     * @return  bool
     */
    public static function inPropertyMap($class_name)
    {
        return isset(self::getPropertyMap()[strtolower($class_name)]);
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$this_class = null;

        self::$method_checkers = [];

        self::$class_methods = [];

        self::$class_checkers = [];

        self::$public_class_properties = [];

        self::$protected_class_properties = [];

        self::$private_class_properties = [];

        self::$public_static_class_properties = [];

        self::$protected_static_class_properties = [];

        self::$private_static_class_properties = [];

        self::$public_class_constants = [];

        self::$registered_classes = [];

        self::$class_implements = [];

        self::$class_files = [];

        self::$file_classes = [];

        ClassChecker::clearCache();
        InterfaceChecker::clearCache();
    }
}
