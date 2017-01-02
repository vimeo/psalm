<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedTrait;
use Psalm\Issue\UnimplementedInterfaceMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

abstract class ClassLikeChecker extends SourceChecker implements StatementsSource
{
    const VISIBILITY_PUBLIC = 1;
    const VISIBILITY_PROTECTED = 2;
    const VISIBILITY_PRIVATE = 3;

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
        'array',
        'iterable',
        'null',
        'mixed',
    ];

    /**
     * @var PhpParser\Node\Stmt\ClassLike
     */
    protected $class;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var StatementsSource
     */
    protected $source;

    /**
     * @var string
     */
    protected $fq_class_name;

    /**
     * @var bool
     */
    protected $has_custom_get = false;

    /**
     * The parent class
     *
     * @var string|null
     */
    protected $parent_class;

    /**
     * @var array<string>
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
     * @var array<string, array<string, string>>|null
     */
    protected static $property_map;

    /**
     * @var array<string, ClassLikeStorage>
     */
    public static $storage = [];

    /**
     * A lookup table of cached ClassLikeCheckers
     *
     * @var array<string, self>
     */
    public static $class_checkers;

    /**
     * @var array<string, array<string, string>>
     */
    public static $file_classes = [];

    /**
     * A lookup table of existing classes
     *
     * @var array<string, bool>
     */
    protected static $existing_classes = [];

    /**
     * A lookup table of existing classes, all lowercased
     *
     * @var array<string, bool>
     */
    protected static $existing_classes_ci = [];

    /**
     * A lookup table used for caching the results of classExtends calls
     *
     * @var array<string, array<string, bool>>
     */
    protected static $class_extends = [];

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
        $this->aliased_constants = $source->getAliasedConstants();
        $this->aliased_functions = $source->getAliasedFunctions();
        $this->file_name = $source->getFileName();
        $this->file_path = $source->getFilePath();
        $this->include_file_name = $source->getIncludeFileName();
        $this->include_file_path = $source->getIncludeFilePath();
        $this->fq_class_name = $fq_class_name;

        $this->suppressed_issues = $source->getSuppressedIssues();

        if (!isset(self::$storage[$fq_class_name])) {
            self::$storage[$fq_class_name] = new ClassLikeStorage();
            self::$storage[$fq_class_name]->file_name = $this->file_name;
            self::$storage[$fq_class_name]->file_path = $this->file_path;
        }

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
        $storage = self::$storage[$class_context ? $class_context->self : $this->fq_class_name];

        if (!$check_methods && !($this instanceof TraitChecker) && $storage->registered) {
            return null;
        }

        $config = Config::getInstance();

        $storage->user_defined = true;
        $storage->registered = true;

        $leftover_stmts = [];

        /** @var array<MethodChecker> */
        $method_checkers = [];

        $long_file_name = Config::getInstance()->getBaseDir() . $this->file_name;

        if (!$class_context) {
            $class_context = new Context($this->file_name, $this->fq_class_name);
            $class_context->parent = $this->parent_class;
            $class_context->vars_in_scope['$this'] = new Type\Union([new Type\Atomic($this->fq_class_name)]);
        }

        // set all constants first
        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                foreach ($stmt->consts as $const) {
                    if ($stmt->isProtected()) {
                        $storage->protected_class_constants[$const->name] = Type::getMixed();
                    } elseif ($stmt->isPrivate()) {
                        $storage->private_class_constants[$const->name] = Type::getMixed();
                    } else {
                        $storage->public_class_constants[$const->name] = Type::getMixed();
                    }
                }
            }
        }

        if ($this instanceof ClassChecker) {
            if ($this->parent_class && $this->registerParentClassInfo($this->parent_class) === false) {
                return false;
            }
        }

        if ($this instanceof InterfaceChecker || $this instanceof ClassChecker) {
            $extra_interfaces = [];

            if ($this instanceof InterfaceChecker) {
                $parent_interfaces = InterfaceChecker::getParentInterfaces($this->fq_class_name);
                $extra_interfaces = $parent_interfaces;
            } else {
                $parent_interfaces = self::$storage[$this->fq_class_name]->class_implements;
            }

            foreach ($parent_interfaces as $interface_name) {
                if (self::checkFullyQualifiedClassLikeName(
                    $interface_name,
                    new CodeLocation($this, $this->class, true),
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
                    $storage->class_implements[strtolower($extra_interface_name)] = $extra_interface_name;
                } else {
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

        foreach ($storage->properties as $property_name => $property) {
            if ($property->is_static) {
                $class_context->vars_in_scope[$this->fq_class_name . '::$' . $property_name] = $property->type
                    ?: Type::getMixed();
            } else {
                $class_context->vars_in_scope['$this->' . $property_name] = $property->type ?: Type::getMixed();
            }
        }

        $config = Config::getInstance();

        if ($this instanceof ClassChecker && $this->class instanceof PhpParser\Node\Stmt\Class_) {
            foreach (ClassChecker::getInterfacesForClass($this->fq_class_name) as $interface_id => $interface_name) {
                $interface_storage = self::$storage[$interface_name];

                $storage->public_class_constants += $interface_storage->public_class_constants;

                foreach ($interface_storage->methods as $method_name => $method) {
                    if ($method->visibility === self::VISIBILITY_PUBLIC) {
                        $mentioned_method_id = $interface_name . '::' . $method_name;
                        $implemented_method_id = $this->fq_class_name . '::' . $method_name;

                        MethodChecker::setOverriddenMethodId($implemented_method_id, $mentioned_method_id);

                        $declaring_method_id = MethodChecker::getDeclaringMethodId($implemented_method_id);

                        $method_storage = $declaring_method_id
                            ? MethodChecker::getStorage($declaring_method_id)
                            : null;

                        if (!$method_storage) {
                            $cased_method_id = MethodChecker::getCasedMethodId($mentioned_method_id);

                            if (IssueBuffer::accepts(
                                new UnimplementedInterfaceMethod(
                                    'Method ' . $cased_method_id . ' is not defined on class ' . $this->fq_class_name,
                                    new CodeLocation($this, $this->class, true)
                                ),
                                $this->suppressed_issues
                            )) {
                                return false;
                            }

                            return null;
                        } elseif ($method_storage->visibility !== self::VISIBILITY_PUBLIC) {
                            $cased_method_id = MethodChecker::getCasedMethodId($mentioned_method_id);

                            if (IssueBuffer::accepts(
                                new InaccessibleMethod(
                                    'Interface-defined method ' . $cased_method_id .
                                        ' must be public in ' . $this->fq_class_name,
                                    new CodeLocation($this, $this->class, true)
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
        }

        if ($check_methods) {
            foreach ($trait_checkers as $trait_checker) {
                $trait_checker->checkMethods($class_context);
            }

            // do the method checks after all class methods have been initialised
            foreach ($method_checkers as $method_checker) {
                $method_checker->check(clone $class_context, null);

                if (!$config->excludeIssueInFile('InvalidReturnType', $this->file_name)) {
                    $secondary_return_type_location = null;

                    /** @var string */
                    $method_id = $method_checker->getMethodId();
                    $return_type_location = MethodChecker::getMethodReturnTypeLocation(
                        $method_id,
                        $secondary_return_type_location
                    );

                    $method_checker->checkReturnTypes(
                        $update_docblocks,
                        MethodChecker::getMethodReturnType($method_id),
                        $return_type_location,
                        $secondary_return_type_location
                    );
                }
            }
        } elseif ($this instanceof TraitChecker) {
            $this->methods = $method_checkers;
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
    protected function registerParentClassInfo($parent_class)
    {
        if (!$this->class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \UnexpectedValueException('Cannot register parent class where none exists');
        }

        if (!$this->class->extends) {
            throw new \UnexpectedValueException('Cannot register parent class where none exists');
        }

        if (self::checkFullyQualifiedClassLikeName(
            $parent_class,
            new CodeLocation($this, $this->class->extends, true),
            $this->getSuppressedIssues()
        ) === false
        ) {
            return false;
        }

        self::registerClassLike($parent_class);

        self::$class_extends[$this->fq_class_name] = self::$class_extends[$this->parent_class];
        self::$class_extends[$this->fq_class_name][$this->parent_class] = true;

        $this->registerInheritedMethods($parent_class);
        $this->registerInheritedProperties($parent_class);

        $storage = self::$storage[$this->fq_class_name];

        $parent_storage = self::$storage[$parent_class];

        FileChecker::addFileInheritanceToClass(Config::getInstance()->getBaseDir() . $this->file_name, $parent_class);

        $storage->class_implements += $parent_storage->class_implements;

        $storage->public_class_constants = $parent_storage->public_class_constants;
        $storage->protected_class_constants = $parent_storage->protected_class_constants;

        $storage->used_traits = $parent_storage->used_traits;

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
        $storage = self::$storage[$class_context->self];

        if (!isset(self::$method_checkers[$method_id])) {
            $method_checker = new MethodChecker($stmt, $this);
            $method_checkers[$stmt->name] = $method_checker;

            if ($cache_method_checker) {
                self::$method_checkers[$method_id] = $method_checker;
            }
        } else {
            $method_checker = self::$method_checkers[$method_id];
        }

        if (!$stmt->isAbstract() && $class_context->self) {
            $implemented_method_id =
                $class_context->self . '::' . strtolower($this->getMappedMethodName(strtolower($stmt->name)));

            MethodChecker::setDeclaringMethodId(
                $implemented_method_id,
                $method_id
            );

            // set a different apparent method if we're in a trait checker
            MethodChecker::setAppearingMethodId(
                $implemented_method_id,
                $this instanceof TraitChecker ? $implemented_method_id : $method_id
            );
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
                    new UndefinedTrait(
                        'Trait ' . $trait_name . ' does not exist',
                        new CodeLocation($this, $trait)
                    ),
                    $this->suppressed_issues
                )) {
                    return false;
                }
            } else {
                if (!TraitChecker::hasCorrectCase($trait_name)) {
                    if (IssueBuffer::accepts(
                        new UndefinedTrait(
                            'Trait ' . $trait_name . ' has wrong casing',
                            new CodeLocation($this, $trait)
                        ),
                        $this->suppressed_issues
                    )) {
                        return false;
                    }

                    continue;
                }

                if (isset(self::$class_checkers[$trait_name])) {
                    /** @var TraitChecker */
                    $trait_checker = self::$class_checkers[$trait_name];
                } else {
                    /** @var TraitChecker */
                    $trait_checker = FileChecker::getClassLikeCheckerFromClass($trait_name);
                }

                $trait_checker->setMethodMap($method_map);

                $trait_checker->check(false, $class_context);

                ClassLikeChecker::registerTraitUse($this->fq_class_name, $trait_name);

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
     * @return  false|null
     */
    protected function visitPropertyDeclaration(
        PhpParser\Node\Stmt\Property $stmt,
        Context $class_context,
        Config $config,
        $check_property_types
    ) {
        $comment = $stmt->getDocComment();
        $type_in_comment = null;
        $storage = self::$storage[$this->fq_class_name];

        if ($comment && $config->use_docblock_types) {
            try {
                $type_in_comment = CommentChecker::getTypeFromComment((string) $comment, null, $this);
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($this, $this->class, true)
                    )
                )) {
                    return false;
                }
            }
        } elseif (!$comment && $check_property_types) {
            if (IssueBuffer::accepts(
                new MissingPropertyType(
                    'Property ' . $this->fq_class_name . '::$' . $stmt->props[0]->name . ' does not have a ' .
                        'declared type',
                    new CodeLocation($this, $stmt)
                ),
                $this->suppressed_issues
            )) {
                // fall through
            }
        }

        $property_group_type = $type_in_comment ? $type_in_comment : null;

        foreach ($stmt->props as $property) {
            if (!$property_group_type) {
                if (!$property->default || !$config->use_property_default_for_type) {
                    $property_type = false;
                } else {
                    $property_type = StatementsChecker::getSimpleType($property->default) ?: Type::getMixed();
                }
            } else {
                $property_type = count($stmt->props) === 1 ? $property_group_type : clone $property_group_type;
            }

            $storage->properties[$property->name] = new PropertyStorage();
            $storage->properties[$property->name]->is_static = (bool)$stmt->isStatic();
            $storage->properties[$property->name]->type = $property_type;

            if ($stmt->isPublic()) {
                $storage->properties[$property->name]->visibility = self::VISIBILITY_PUBLIC;
            } elseif ($stmt->isProtected()) {
                $storage->properties[$property->name]->visibility = self::VISIBILITY_PROTECTED;
            } elseif ($stmt->isPrivate()) {
                $storage->properties[$property->name]->visibility = self::VISIBILITY_PRIVATE;
            }

            $property_id = $this->fq_class_name . '::$' . $property->name;

            $implemented_property_id = $class_context->self . '::$' . $property->name;

            $storage->declaring_property_ids[$property->name] = $property_id;
            $storage->appearing_property_ids[$property->name] = $this instanceof TraitChecker
                ? $implemented_property_id
                : $property_id;
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
        $storage = self::$storage[$class_context->self];

        if ($comment && $config->use_docblock_types && count($stmt->consts) === 1) {
            $type_in_comment = CommentChecker::getTypeFromComment((string) $comment, null, $this);
        }

        $const_type = $type_in_comment ? $type_in_comment : Type::getMixed();

        foreach ($stmt->consts as $const) {
            if ($stmt->isProtected()) {
                $storage->protected_class_constants[$const->name] = $const_type;
            } elseif ($stmt->isPrivate()) {
                $storage->private_class_constants[$const->name] = $const_type;
            } else {
                $storage->public_class_constants[$const->name] = $const_type;
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

        MethodChecker::registerClassLikeMethod($method_id);

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
     * @param  CodeLocation    $code_location
     * @param  array<string>    $suppressed_issues
     * @return bool|null
     */
    public static function checkFullyQualifiedClassLikeName(
        $fq_class_name,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        if (empty($fq_class_name)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name);

        if (in_array($fq_class_name, ['callable', 'iterable'])) {
            return true;
        }

        $class_exists = ClassChecker::classExists($fq_class_name);
        $interface_exists = InterfaceChecker::interfaceExists($fq_class_name);

        if (!$class_exists && !$interface_exists) {
            if (IssueBuffer::accepts(
                new UndefinedClass(
                    'Class or interface ' . $fq_class_name . ' does not exist',
                    $code_location
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
                    $code_location
                ),
                $suppressed_issues
            )) {
                return false;
            }
        }

        FileChecker::addFileReferenceToClass(
            Config::getInstance()->getBaseDir() . $code_location->file_name,
            $fq_class_name
        );

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     *
     * @param  PhpParser\Node\Name      $class_name
     * @param  string                   $namespace
     * @param  array<string, string>    $aliased_classes
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
     * @return string|null
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
    public static function registerClassLike($class_name)
    {
        if (isset(self::$storage[$class_name]) && self::$storage[$class_name]->registered) {
            return true;
        }

        if (!$class_name || strpos($class_name, '::') !== false) {
            throw new \InvalidArgumentException('Invalid class name ' . $class_name);
        }

        $old_level = error_reporting();
        error_reporting(0);

        try {
            $reflected_class = new ReflectionClass($class_name);
        } catch (\ReflectionException $e) {
            error_reporting($old_level);

            return false;
        }

        error_reporting($old_level);

        if ($reflected_class->isUserDefined()) {
            $class_file_name = (string)$reflected_class->getFileName();

            $file_checker = new FileChecker($class_file_name);

            $short_file_name = $file_checker->getFileName();

            self::$file_classes[$class_file_name][] = $class_name;

            if (!isset(self::$storage[$class_name])) {
                self::$storage[$class_name] = new ClassLikeStorage();
                self::$storage[$class_name]->file_path = $class_file_name;
                self::$storage[$class_name]->file_name = $short_file_name;
            }

            $storage = self::$storage[$class_name];

            // this doesn't work on traits
            $file_checker->check(true, false);

            if (self::inPropertyMap($class_name)) {
                $public_mapped_properties = self::getPropertyMap()[strtolower($class_name)];

                foreach ($public_mapped_properties as $property_name => $public_mapped_property) {
                    $property_type = Type::parseString($public_mapped_property);
                    $storage->properties[$property_name] = new PropertyStorage();
                    $storage->properties[$property_name]->type = $property_type;
                    $storage->properties[$property_name]->visibility = self::VISIBILITY_PUBLIC;

                    $property_id = $class_name . '::$' . $property_name;

                    $storage->declaring_property_ids[$property_name] = $property_id;
                    $storage->appearing_property_ids[$property_name] = $property_id;
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
        if (isset(self::$storage[$class_name]) && self::$storage[$class_name]->reflected) {
            return;
        }

        $parent_class = $reflected_class->getParentClass();

        $cased_name = $reflected_class->getName();

        if ($cased_name === 'LibXMLError') {
            $cased_name = 'libXMLError';
        }

        $storage = self::$storage[$cased_name] = new ClassLikeStorage();

        self::$existing_classes_ci[strtolower($class_name)] = true;
        self::$existing_classes[$cased_name] = true;

        self::$class_extends[$cased_name] = [];

        if ($parent_class) {
            $parent_class_name = $parent_class->getName();
            self::registerReflectedClass($parent_class_name, $parent_class);

            $parent_storage = self::$storage[$parent_class_name];
        }

        $class_properties = $reflected_class->getProperties();

        $public_mapped_properties = self::inPropertyMap($class_name)
            ? self::getPropertyMap()[strtolower($class_name)]
            : [];

        /** @var \ReflectionProperty $class_property */
        foreach ($class_properties as $class_property) {
            $property_name = $class_property->getName();
            $storage->properties[$property_name] = new PropertyStorage();

            $storage->properties[$property_name]->type = Type::getMixed();

            if ($class_property->isStatic()) {
                $storage->properties[$property_name]->is_static = true;
            }

            if ($class_property->isPublic()) {
                $storage->properties[$property_name]->visibility = self::VISIBILITY_PUBLIC;
            } elseif ($class_property->isProtected()) {
                $storage->properties[$property_name]->visibility = self::VISIBILITY_PROTECTED;
            } elseif ($class_property->isPrivate()) {
                $storage->properties[$property_name]->visibility = self::VISIBILITY_PRIVATE;
            }

            $property_id = (string)$class_property->class . '::$' . $property_name;

            $storage->declaring_property_ids[$property_name] = $property_id;
            $storage->appearing_property_ids[$property_name] = $property_id;
        }

        // have to do this separately as there can be new properties here
        foreach ($public_mapped_properties as $property_name => $type) {
            if (!isset($storage->properties[$property_name])) {
                $storage->properties[$property_name] = new PropertyStorage();
                $storage->properties[$property_name]->visibility = self::VISIBILITY_PUBLIC;

                $property_id = $cased_name . '::$' . $property_name;

                $storage->declaring_property_ids[$property_name] = $property_id;
                $storage->appearing_property_ids[$property_name] = $property_id;
            }

            $storage->properties[$property_name]->type = Type::parseString($type);
        }

        /** @var array<string, int|string|float|null|array> */
        $class_constants = $reflected_class->getConstants();

        foreach ($class_constants as $name => $value) {
            $storage->public_class_constants[$name] = self::getTypeFromValue($value);
        }

        $storage->registered = true;

        if (!$reflected_class->isTrait() && !$reflected_class->isInterface()) {
            ClassChecker::getInterfacesForClass($class_name);
        }

        $reflection_methods = $reflected_class->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        $interfaces = $reflected_class->getInterfaces();

        /** @var \ReflectionClass $interface */
        foreach ($interfaces as $interface) {
            $interface_name = $interface->getName();
            self::registerReflectedClass($interface_name, $interface);
            $storage->class_implements[strtolower($interface_name)] = $interface_name;
        }

        /** @var \ReflectionMethod $reflection_method */
        foreach ($reflection_methods as $reflection_method) {
            MethodChecker::extractReflectionMethodInfo($reflection_method);

            if ($reflection_method->class !== $class_name) {
                MethodChecker::setDeclaringMethodId(
                    $class_name . '::' . strtolower($reflection_method->name),
                    $reflection_method->class . '::' . strtolower($reflection_method->name)
                );

                MethodChecker::setAppearingMethodId(
                    $class_name . '::' . strtolower($reflection_method->name),
                    $reflection_method->class . '::' . strtolower($reflection_method->name)
                );

                continue;
            }
        }
    }

    /**
     * @param string $parent_class
     * @return void
     */
    protected function registerInheritedMethods($parent_class)
    {
        $parent_storage = self::$storage[$parent_class];
        $storage = self::$storage[$this->fq_class_name];

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $parent_method_id = $parent_class . '::' . $method_name;

            /** @var string */
            $appearing_method_id = MethodChecker::getAppearingMethodId($parent_method_id);
            $implemented_method_id = $this->fq_class_name . '::' . $method_name;

            $storage->appearing_method_ids[$method_name] = $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_method_ids as $method_name => $declaring_method_id) {
            $parent_method_id = $parent_class . '::' . $method_name;

            /** @var string */
            $declaring_method_id = MethodChecker::getDeclaringMethodId($parent_method_id);
            $implemented_method_id = $this->fq_class_name . '::' . $method_name;

            $storage->declaring_method_ids[$method_name] = $declaring_method_id;

            MethodChecker::setOverriddenMethodId($implemented_method_id, $declaring_method_id);
        }
    }

    /**
     * @param string $parent_class
     * @return void
     */
    protected function registerInheritedProperties($parent_class)
    {
        $parent_storage = self::$storage[$parent_class];
        $storage = self::$storage[$this->fq_class_name];

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $storage->appearing_property_ids[$property_name] = $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_id) {
            $storage->declaring_property_ids[$property_name] = $declaring_property_id;
        }
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     * @param  bool   $is_static
     * @return array<string, PropertyStorage>
     */
    public static function getPropertiesForClass($class_name, $visibility, $is_static)
    {
        if (self::registerClassLike($class_name) === false) {
            return [];
        }

        $storage = self::$storage[$class_name];

        $properties = [];

        foreach ($storage->properties as $property_name => $property) {
            if (!$property->is_static) {
                if ($visibility === ReflectionProperty::IS_PRIVATE ||
                    $property->visibility === ClassLikeChecker::VISIBILITY_PUBLIC ||
                    ($property->visibility === ClassLikeChecker::VISIBILITY_PROTECTED &&
                        $visibility === ReflectionProperty::IS_PROTECTED)
                ) {
                    $properties[$property_name] = $property;
                }
            }
        }

        return $properties;
    }

    /**
     * Gets the Psalm type from a particular value
     *
     * @param  mixed $value
     * @return Type\Union
     */
    public static function getTypeFromValue($value)
    {
        switch (gettype($value)) {
            case 'boolean':
                return Type::getBool();

            case 'integer':
                return Type::getInt();

            case 'double':
                return Type::getFloat();

            case 'string':
                return Type::getString();

            case 'array':
                return Type::getArray();

            case 'NULL':
                return Type::getNull();

            default:
                return Type::getMixed();
        }
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     * @return array<string,Type\Union>
     */
    public static function getConstantsForClass($class_name, $visibility)
    {
        if (self::registerClassLike($class_name) === false) {
            return [];
        }

        $storage = self::$storage[$class_name];

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return $storage->public_class_constants;
        }

        if ($visibility === ReflectionProperty::IS_PROTECTED) {
            return array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants
            );
        }

        if ($visibility === ReflectionProperty::IS_PRIVATE) {
            return array_merge(
                $storage->public_class_constants,
                $storage->protected_class_constants,
                $storage->private_class_constants
            );
        }

        throw new \InvalidArgumentException('Must specify $visibility');
    }

    /**
     * @param   string      $class_name
     * @param   string      $const_name
     * @param   Type\Union  $type
     * @param   int         $visibility
     * @return  void
     */
    public static function setConstantType($class_name, $const_name, Type\Union $type, $visibility)
    {
        $storage = self::$storage[$class_name];

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            $storage->public_class_constants[$const_name] = $type;
        } elseif ($visibility === ReflectionProperty::IS_PROTECTED) {
            $storage->protected_class_constants[$const_name] = $type;
        } elseif ($visibility === ReflectionProperty::IS_PRIVATE) {
            $storage->private_class_constants[$const_name] = $type;
        }
    }

    /**
     * Whether or not a given property exists
     *
     * @param  string $property_id
     * @return bool
     */
    public static function propertyExists($property_id)
    {
        // remove trailing backslash if it exists
        $property_id = preg_replace('/^\\\\/', '', $property_id);

        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $old_property_id = null;

        if (ClassLikeChecker::registerClassLike($fq_class_name) === false) {
            return false;
        }

        $class_storage = self::$storage[$fq_class_name];

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            return true;
        }

        return false;
    }

    /**
     * @param  string           $property_id
     * @param  string|null      $calling_context
     * @param  StatementsSource $source
     * @param  CodeLocation     $code_location
     * @param  array            $suppressed_issues
     * @return false|null
     */
    public static function checkPropertyVisibility(
        $property_id,
        $calling_context,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues
    ) {
        $declaring_property_class = self::getDeclaringClassForProperty($property_id);
        $appearing_property_class = self::getAppearingClassForProperty($property_id);

        if (!$declaring_property_class || !$appearing_property_class) {
            throw new \UnexpectedValueException(
                'Appearing/Declaring classes are not defined for ' . $property_id
            );
        }

        list($property_class, $property_name) = explode('::$', (string)$property_id);

        // if the calling class is the same, we know the property exists, so it must be visible
        if ($appearing_property_class === $calling_context) {
            return null;
        }

        if ($source->getSource() instanceof TraitChecker && $declaring_property_class === $source->getFQCLN()) {
            return null;
        }

        $class_storage = self::$storage[$declaring_property_class];

        if (!$class_storage) {
            throw new \UnexpectedValueException('$class_storage should not be null for ' . $declaring_property_class);
        }

        $storage = $class_storage->properties[$property_name];

        if (!$storage) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $property_id);
        }

        switch ($storage->visibility) {
            case self::VISIBILITY_PUBLIC:
                return null;

            case self::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_property_class !== $calling_context) {
                    if (IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access private property ' . $property_id . ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }

                return null;

            case self::VISIBILITY_PROTECTED:
                if ($appearing_property_class === $calling_context) {
                    return null;
                }

                if (!$calling_context) {
                    if (IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }

                if (ClassChecker::classExtends($appearing_property_class, $calling_context)) {
                    return null;
                }

                if (!ClassChecker::classExtends($calling_context, $appearing_property_class)) {
                    if (IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id . ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }
                }
        }

        return null;
    }

    /**
     * @param  string $property_id
     * @return string|null
     */
    public static function getDeclaringClassForProperty($property_id)
    {
        list($fq_class_name, $property_name) = explode('::$', $property_id);

        if (isset(ClassLikeChecker::$storage[$fq_class_name]->declaring_property_ids[$property_name])) {
            $declaring_property_id = ClassLikeChecker::$storage[$fq_class_name]->declaring_property_ids[$property_name];

            return explode('::$', $declaring_property_id)[0];
        }
    }

    /**
     * Get the class this property appears in (vs is declared in, which could give a trait)
     *
     * @param  string $property_id
     * @return string|null
     */
    public static function getAppearingClassForProperty($property_id)
    {
        list($fq_class_name, $property_name) = explode('::$', $property_id);

        if (isset(ClassLikeChecker::$storage[$fq_class_name]->appearing_property_ids[$property_name])) {
            $appearing_property_id = ClassLikeChecker::$storage[$fq_class_name]->appearing_property_ids[$property_name];

            return explode('::$', $appearing_property_id)[0];
        }
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
     * @return  string
     */
    protected function getMappedMethodName($method_name)
    {
        return $method_name;
    }

    /**
     * @param   string $file_name
     * @return  array<string>
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
        self::registerClassLike($fq_class_name);
        $storage = self::$storage[$fq_class_name];
        return $storage->user_defined;
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

        /** @var array<string, array> */
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
     * @param  string $fq_class_name
     * @param  string $fq_trait_name
     * @return void
     */
    public static function registerTraitUse($fq_class_name, $fq_trait_name)
    {
        $storage = self::$storage[$fq_class_name];

        $storage->used_traits[$fq_trait_name] = true;
    }

    /**
     * @param string $fq_class_name
     * @param string $fq_trait_name
     * @return bool
     */
    public static function classUsesTrait($fq_class_name, $fq_trait_name)
    {
        $storage = self::$storage[$fq_class_name];

        return isset($storage->used_traits[$fq_trait_name]);
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$this_class = null;

        self::$method_checkers = [];

        self::$file_classes = [];

        self::$class_checkers = [];

        self::$storage = [];

        self::$existing_classes = [];
        self::$existing_classes_ci = [];

        self::$class_extends = [];

        ClassChecker::clearCache();
        InterfaceChecker::clearCache();
        TraitChecker::clearCache();
    }
}
