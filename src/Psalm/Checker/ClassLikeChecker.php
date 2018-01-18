<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Aliases;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\FileManipulation\FileManipulationBuffer;
use Psalm\Issue\DuplicateClass;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Provider\FileReferenceProvider;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
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
    public static $SPECIAL_TYPES = [
        'int' => 'int',
        'string' => 'stirng',
        'float' => 'float',
        'bool' => 'bool',
        'false' => 'false',
        'object' => 'object',
        'empty' => 'empty',
        'callable' => 'callable',
        'array' => 'array',
        'iterable' => 'iterable',
        'null' => 'null',
        'mixed' => 'mixed',
    ];

    /**
     * @var array
     */
    public static $GETTYPE_TYPES = [
        'boolean' => true,
        'integer' => true,
        'double' => true,
        'string' => true,
        'array' => true,
        'object' => true,
        'resource' => true,
        'NULL' => true,
        'unknown type' => true,
    ];

    /**
     * @var PhpParser\Node\Stmt\ClassLike
     */
    protected $class;

    /**
     * @var StatementsSource
     */
    protected $source;

    /** @var FileChecker */
    public $file_checker;

    /**
     * @var string
     */
    protected $fq_class_name;

    /**
     * The parent class
     *
     * @var string|null
     */
    protected $parent_fq_class_name;

    /**
     * @var array<string, array<string, string>>|null
     */
    protected static $property_map;

    /**
     * A lookup table of cached TraitCheckers
     *
     * @var array<string, TraitChecker>
     */
    public static $trait_checkers;

    /**
     * A lookup table of cached ClassCheckers
     *
     * @var array<string, ClassChecker>
     */
    public static $class_checkers;

    /**
     * @var array<string, array<string, string>>
     */
    public static $file_classes = [];

    /**
     * @var PhpParser\Node\Stmt[]
     */
    protected $leftover_stmts = [];

    /** @var ClassLikeStorage */
    protected $storage;

    /**
     * @param PhpParser\Node\Stmt\ClassLike $class
     * @param StatementsSource              $source
     * @param string                        $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\ClassLike $class, StatementsSource $source, $fq_class_name)
    {
        $this->class = $class;
        $this->source = $source;
        $this->file_checker = $source->getFileChecker();
        $this->fq_class_name = $fq_class_name;

        $this->storage = $this->file_checker->project_checker->classlike_storage_provider->get($fq_class_name);

        if ($this->storage->location) {
            $storage_file_path = $this->storage->location->file_path;
            $source_file_path = $this->source->getCheckedFilePath();

            if (!Config::getInstance()->use_case_sensitive_file_names) {
                $storage_file_path = strtolower($storage_file_path);
                $source_file_path = strtolower($source_file_path);
            }

            if ($storage_file_path !== $source_file_path ||
                $this->storage->location->getLineNumber() !== $class->getLine()
            ) {
                if (IssueBuffer::accepts(
                    new DuplicateClass(
                        'Class ' . $fq_class_name . ' has already been defined at ' .
                            $storage_file_path . ':' . $this->storage->location->getLineNumber(),
                        new CodeLocation($this, $class, null, true)
                    )
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param  string       $method_name
     * @param  Context      $context
     *
     * @return void
     */
    public function getMethodMutations(
        $method_name,
        Context $context
    ) {
        $project_checker = $this->getFileChecker()->project_checker;

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                strtolower($stmt->name) === strtolower($method_name)
            ) {
                $project_checker = $this->getFileChecker()->project_checker;

                $method_id = $this->fq_class_name . '::' . $stmt->name;

                if ($project_checker->canCache() && isset($project_checker->method_checkers[$method_id])) {
                    $method_checker = $project_checker->method_checkers[$method_id];
                } else {
                    $method_checker = new MethodChecker($stmt, $this);

                    if ($project_checker->canCache()) {
                        $project_checker->method_checkers[$method_id] = $method_checker;
                    }
                }

                $method_checker->analyze($context, null, true);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    if (!isset(self::$trait_checkers[strtolower($fq_trait_name)])) {
                        throw new \UnexpectedValueException(
                            'Expecting trait statements to exist for ' . $fq_trait_name
                        );
                    }

                    $trait_checker = self::$trait_checkers[strtolower($fq_trait_name)];

                    foreach ($trait_checker->class->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                            strtolower($trait_stmt->name) === strtolower($method_name)
                        ) {
                            $method_checker = new MethodChecker($trait_stmt, $trait_checker);

                            $actual_method_id = (string)$method_checker->getMethodId();

                            if ($context->self && $context->self !== $this->fq_class_name) {
                                $analyzed_method_id = (string)$method_checker->getMethodId($context->self);
                                $declaring_method_id = MethodChecker::getDeclaringMethodId(
                                    $project_checker,
                                    $analyzed_method_id
                                );

                                if ($actual_method_id !== $declaring_method_id) {
                                    break;
                                }
                            }

                            $method_checker->analyze($context, null, true);
                        }
                    }
                }
            }
        }
    }

    /**
     * Check whether a class/interface exists
     *
     * @param  string          $fq_class_name
     * @param  ProjectChecker  $project_checker
     * @param  CodeLocation $code_location
     *
     * @return bool
     */
    public static function classOrInterfaceExists(
        ProjectChecker $project_checker,
        $fq_class_name,
        CodeLocation $code_location = null
    ) {
        if (!ClassChecker::classExists($project_checker, $fq_class_name) &&
            !InterfaceChecker::interfaceExists($project_checker, $fq_class_name)
        ) {
            return false;
        }

        if ($project_checker->collect_references && $code_location) {
            $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);
            if ($class_storage->referencing_locations === null) {
                $class_storage->referencing_locations = [];
            }
            $class_storage->referencing_locations[$code_location->file_path][] = $code_location;
        }

        return true;
    }

    /**
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     *
     * @return bool
     */
    public static function classExtendsOrImplements(
        ProjectChecker $project_checker,
        $fq_class_name,
        $possible_parent
    ) {
        return ClassChecker::classExtends($project_checker, $fq_class_name, $possible_parent) ||
            ClassChecker::classImplements($project_checker, $fq_class_name, $possible_parent);
    }

    /**
     * @param  string           $fq_class_name
     * @param  array<string>    $suppressed_issues
     * @param  bool             $inferred - whether or not the type was inferred
     *
     * @return bool|null
     */
    public static function checkFullyQualifiedClassLikeName(
        StatementsSource $statements_source,
        $fq_class_name,
        CodeLocation $code_location,
        array $suppressed_issues,
        $inferred = true
    ) {
        if (empty($fq_class_name)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        $project_checker = $statements_source->getFileChecker()->project_checker;

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name);

        if (in_array($fq_class_name, ['callable', 'iterable'], true)) {
            return true;
        }

        if (preg_match(
            '/(^|\\\)(int|float|bool|string|void|null|false|true|resource|object|numeric|mixed)$/i',
            $fq_class_name
        )
        ) {
            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            if (IssueBuffer::accepts(
                new ReservedWord(
                    $class_name . ' is a reserved word',
                    $code_location
                ),
                $suppressed_issues
            )) {
                // fall through
            }

            return null;
        }

        $class_exists = ClassChecker::classExists($project_checker, $fq_class_name);
        $interface_exists = InterfaceChecker::interfaceExists($project_checker, $fq_class_name);

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

        if ($project_checker->collect_references && !$inferred) {
            $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);
            if ($class_storage->referencing_locations === null) {
                $class_storage->referencing_locations = [];
            }
            $class_storage->referencing_locations[$code_location->file_path][] = $code_location;
        }

        if (($class_exists && !ClassChecker::hasCorrectCasing($project_checker, $fq_class_name)) ||
            ($interface_exists && !InterfaceChecker::hasCorrectCasing($project_checker, $fq_class_name))
        ) {
            if (ClassLikeChecker::isUserDefined($project_checker, $fq_class_name)) {
                if (IssueBuffer::accepts(
                    new InvalidClass(
                        'Class or interface ' . $fq_class_name . ' has wrong casing',
                        $code_location
                    ),
                    $suppressed_issues
                )) {
                    // fall through here
                }
            }
        }

        FileReferenceProvider::addFileReferenceToClass(
            $code_location->file_path,
            strtolower($fq_class_name)
        );

        if (!$inferred) {
            $plugins = Config::getInstance()->getPlugins();

            if ($plugins) {
                $file_manipulations = [];

                foreach ($plugins as $plugin) {
                    $plugin->afterClassLikeExistsCheck(
                        $statements_source,
                        $fq_class_name,
                        $code_location,
                        $file_manipulations
                    );
                }

                if ($file_manipulations) {
                    FileManipulationBuffer::add($code_location->file_path, $file_manipulations);
                }
            }
        }

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     *
     * @param  PhpParser\Node\Name      $class_name
     * @param  Aliases                  $aliases
     *
     * @return string
     */
    public static function getFQCLNFromNameObject(PhpParser\Node\Name $class_name, Aliases $aliases)
    {
        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return implode('\\', $class_name->parts);
        }

        if (in_array($class_name->parts[0], ['self', 'static', 'parent'], true)) {
            return $class_name->parts[0];
        }

        return self::getFQCLNFromString(
            implode('\\', $class_name->parts),
            $aliases
        );
    }

    /**
     * @param  string                   $class
     * @param  Aliases                  $aliases
     *
     * @return string
     */
    public static function getFQCLNFromString($class, Aliases $aliases)
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        if ($class[0] === '\\') {
            return substr($class, 1);
        }

        $imported_namespaces = $aliases->uses;

        if (strpos($class, '\\') !== false) {
            $class_parts = explode('\\', $class);
            $first_namespace = array_shift($class_parts);

            if (isset($imported_namespaces[strtolower($first_namespace)])) {
                return $imported_namespaces[strtolower($first_namespace)] . '\\' . implode('\\', $class_parts);
            }
        } elseif (isset($imported_namespaces[strtolower($class)])) {
            return $imported_namespaces[strtolower($class)];
        }

        $namespace = $aliases->namespace;

        return ($namespace ? $namespace . '\\' : '') . $class;
    }

    /**
     * @return ?string
     */
    public function getNamespace()
    {
        return $this->source->getNamespace();
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
    public function getParentFQCLN()
    {
        return $this->parent_fq_class_name;
    }

    /**
     * @return bool
     */
    public function isStatic()
    {
        return false;
    }

    /**
     * @param  string          $class_name
     * @param  ReflectionClass $reflected_class
     * @param  ProjectChecker  $project_checker
     *
     * @return void
     */
    public static function registerReflectedClass(
        $class_name,
        ReflectionClass $reflected_class,
        ProjectChecker $project_checker
    ) {
        $class_name = $reflected_class->name;

        if ($class_name === 'LibXMLError') {
            $class_name = 'libXMLError';
        }

        $class_name_lower = strtolower($class_name);

        $storage_provider = $project_checker->classlike_storage_provider;

        try {
            $storage_provider->get($class_name_lower);

            return;
        } catch (\Exception $e) {
            // this is fine
        }

        $reflected_parent_class = $reflected_class->getParentClass();

        $storage = $storage_provider->create($class_name);
        $storage->abstract = $reflected_class->isAbstract();

        if ($reflected_parent_class) {
            $parent_class_name = $reflected_parent_class->getName();
            self::registerReflectedClass($parent_class_name, $reflected_parent_class, $project_checker);

            $parent_storage = $storage_provider->get($parent_class_name);

            self::registerInheritedMethods($project_checker, $class_name, $parent_class_name);
            self::registerInheritedProperties($project_checker, $class_name, $parent_class_name);

            $storage->class_implements = $parent_storage->class_implements;

            $storage->public_class_constants = $parent_storage->public_class_constants;
            $storage->protected_class_constants = $parent_storage->protected_class_constants;
            $storage->parent_classes = array_merge([strtolower($parent_class_name)], $parent_storage->parent_classes);

            $storage->used_traits = $parent_storage->used_traits;
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

            if (!$class_property->isPrivate()) {
                $storage->inheritable_property_ids[$property_name] = $property_id;
            }
        }

        // have to do this separately as there can be new properties here
        foreach ($public_mapped_properties as $property_name => $type) {
            if (!isset($storage->properties[$property_name])) {
                $storage->properties[$property_name] = new PropertyStorage();
                $storage->properties[$property_name]->visibility = self::VISIBILITY_PUBLIC;

                $property_id = $class_name . '::$' . $property_name;

                $storage->declaring_property_ids[$property_name] = $property_id;
                $storage->appearing_property_ids[$property_name] = $property_id;
                $storage->inheritable_property_ids[$property_name] = $property_id;
            }

            $storage->properties[$property_name]->type = Type::parseString($type);
        }

        /** @var array<string, int|string|float|null|array> */
        $class_constants = $reflected_class->getConstants();

        foreach ($class_constants as $name => $value) {
            $storage->public_class_constants[$name] = self::getTypeFromValue($value);
        }

        if ($reflected_class->isInterface()) {
            $project_checker->addFullyQualifiedInterfaceName($class_name);
        } elseif ($reflected_class->isTrait()) {
            $project_checker->addFullyQualifiedTraitName($class_name);
        } else {
            $project_checker->addFullyQualifiedClassName($class_name);
        }

        $reflection_methods = $reflected_class->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        if ($class_name_lower === 'generator') {
            $storage->template_types = ['TKey' => 'mixed', 'TValue' => 'mixed'];
        }

        $interfaces = $reflected_class->getInterfaces();

        /** @var \ReflectionClass $interface */
        foreach ($interfaces as $interface) {
            $interface_name = $interface->getName();
            self::registerReflectedClass($interface_name, $interface, $project_checker);

            if ($reflected_class->isInterface()) {
                $storage->parent_interfaces[strtolower($interface_name)] = $interface_name;
            } else {
                $storage->class_implements[strtolower($interface_name)] = $interface_name;
            }
        }

        /** @var \ReflectionMethod $reflection_method */
        foreach ($reflection_methods as $reflection_method) {
            $method_reflection_class = $reflection_method->getDeclaringClass();
            $method_class_name = $method_reflection_class->getName();

            self::registerReflectedClass($method_class_name, $method_reflection_class, $project_checker);

            MethodChecker::extractReflectionMethodInfo($reflection_method, $project_checker);

            if ($reflection_method->class !== $class_name) {
                MethodChecker::setDeclaringMethodId(
                    $project_checker,
                    $class_name . '::' . strtolower($reflection_method->name),
                    $reflection_method->class . '::' . strtolower($reflection_method->name)
                );

                MethodChecker::setAppearingMethodId(
                    $project_checker,
                    $class_name . '::' . strtolower($reflection_method->name),
                    $reflection_method->class . '::' . strtolower($reflection_method->name)
                );

                continue;
            }
        }
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     *
     * @return void
     */
    protected static function registerInheritedMethods(ProjectChecker $project_checker, $fq_class_name, $parent_class)
    {
        $parent_storage = $project_checker->classlike_storage_provider->get($parent_class);
        $storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $implemented_method_id = $fq_class_name . '::' . $method_name;

            $storage->appearing_method_ids[$method_name] = $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_method_ids as $method_name => $declaring_method_id) {
            $implemented_method_id = $fq_class_name . '::' . $method_name;

            $storage->declaring_method_ids[$method_name] = $declaring_method_id;
            $storage->inheritable_method_ids[$method_name] = $declaring_method_id;

            MethodChecker::setOverriddenMethodId($project_checker, $implemented_method_id, $declaring_method_id);
        }
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     *
     * @return void
     */
    protected static function registerInheritedProperties(
        ProjectChecker $project_checker,
        $fq_class_name,
        $parent_class
    ) {
        $parent_storage = $project_checker->classlike_storage_provider->get($parent_class);
        $storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === self::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->appearing_property_ids[$property_name] = $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === self::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->declaring_property_ids[$property_name] = $declaring_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            if (!$parent_storage->is_trait
                && isset($parent_storage->properties[$property_name])
                && $parent_storage->properties[$property_name]->visibility === self::VISIBILITY_PRIVATE
            ) {
                continue;
            }

            $storage->inheritable_property_ids[$property_name] = $inheritable_property_id;
        }
    }

    /**
     * Gets the Psalm type from a particular value
     *
     * @param  mixed $value
     *
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
     *
     * @return array<string,Type\Union>
     */
    public static function getConstantsForClass(ProjectChecker $project_checker, $class_name, $visibility)
    {
        $class_name = strtolower($class_name);

        $class_name = strtolower($class_name);

        $storage = $project_checker->classlike_storage_provider->get($class_name);

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
     *
     * @return  void
     */
    public static function setConstantType(
        ProjectChecker $project_checker,
        $class_name,
        $const_name,
        Type\Union $type,
        $visibility
    ) {
        $storage = $project_checker->classlike_storage_provider->get($class_name);

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
     *
     * @return bool
     */
    public static function propertyExists(
        ProjectChecker $project_checker,
        $property_id,
        CodeLocation $code_location = null
    ) {
        // remove trailing backslash if it exists
        $property_id = preg_replace('/^\\\\/', '', $property_id);

        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            if ($project_checker->collect_references && $code_location) {
                $declaring_property_id = $class_storage->declaring_property_ids[$property_name];
                list($declaring_property_class, $declaring_property_name) = explode('::$', $declaring_property_id);

                $declaring_class_storage = $project_checker->classlike_storage_provider->get($declaring_property_class);
                $declaring_property_storage = $declaring_class_storage->properties[$declaring_property_name];

                if ($declaring_property_storage->referencing_locations === null) {
                    $declaring_property_storage->referencing_locations = [];
                }

                $declaring_property_storage->referencing_locations[$code_location->file_path][] = $code_location;
            }

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
     * @param  bool             $emit_issues
     *
     * @return bool|null
     */
    public static function checkPropertyVisibility(
        $property_id,
        $calling_context,
        StatementsSource $source,
        CodeLocation $code_location,
        array $suppressed_issues,
        $emit_issues = true
    ) {
        $project_checker = $source->getFileChecker()->project_checker;

        $declaring_property_class = self::getDeclaringClassForProperty($project_checker, $property_id);
        $appearing_property_class = self::getAppearingClassForProperty($project_checker, $property_id);

        if (!$declaring_property_class || !$appearing_property_class) {
            throw new \UnexpectedValueException(
                'Appearing/Declaring classes are not defined for ' . $property_id
            );
        }

        list(, $property_name) = explode('::$', (string)$property_id);

        // if the calling class is the same, we know the property exists, so it must be visible
        if ($appearing_property_class === $calling_context) {
            return $emit_issues ? null : true;
        }

        if ($source->getSource() instanceof TraitChecker && $declaring_property_class === $source->getFQCLN()) {
            return $emit_issues ? null : true;
        }

        $class_storage = $project_checker->classlike_storage_provider->get($declaring_property_class);

        if (!isset($class_storage->properties[$property_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $property_id);
        }

        $storage = $class_storage->properties[$property_name];

        switch ($storage->visibility) {
            case self::VISIBILITY_PUBLIC:
                return $emit_issues ? null : true;

            case self::VISIBILITY_PRIVATE:
                if (!$calling_context || $appearing_property_class !== $calling_context) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access private property ' . $property_id . ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }

                return $emit_issues ? null : true;

            case self::VISIBILITY_PROTECTED:
                if ($appearing_property_class === $calling_context) {
                    return null;
                }

                if (!$calling_context) {
                    if ($emit_issues && IssueBuffer::accepts(
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

                if (ClassChecker::classExtends($project_checker, $appearing_property_class, $calling_context)) {
                    return $emit_issues ? null : true;
                }

                if (!ClassChecker::classExtends($project_checker, $calling_context, $appearing_property_class)) {
                    if ($emit_issues && IssueBuffer::accepts(
                        new InaccessibleProperty(
                            'Cannot access protected property ' . $property_id . ' from context ' . $calling_context,
                            $code_location
                        ),
                        $suppressed_issues
                    )) {
                        return false;
                    }

                    return null;
                }
        }

        return $emit_issues ? null : true;
    }

    /**
     * @param  string $property_id
     *
     * @return string|null
     */
    public static function getDeclaringClassForProperty(ProjectChecker $project_checker, $property_id)
    {
        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $fq_class_name = strtolower($fq_class_name);

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->declaring_property_ids[$property_name])) {
            $declaring_property_id = $class_storage->declaring_property_ids[$property_name];

            return explode('::$', $declaring_property_id)[0];
        }
    }

    /**
     * Get the class this property appears in (vs is declared in, which could give a trait)
     *
     * @param  string $property_id
     *
     * @return string|null
     */
    public static function getAppearingClassForProperty(ProjectChecker $project_checker, $property_id)
    {
        list($fq_class_name, $property_name) = explode('::$', $property_id);

        $fq_class_name = strtolower($fq_class_name);

        $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

        if (isset($class_storage->appearing_property_ids[$property_name])) {
            $appearing_property_id = $class_storage->appearing_property_ids[$property_name];

            return explode('::$', $appearing_property_id)[0];
        }
    }

    /**
     * @param   string $file_path
     *
     * @return  array<string>
     */
    public static function getClassesForFile(ProjectChecker $project_checker, $file_path)
    {
        try {
            return $project_checker->file_storage_provider->get($file_path)->classes_in_file;
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }

    /**
     * @param  string  $fq_class_name
     *
     * @return bool
     */
    public static function isUserDefined(ProjectChecker $project_checker, $fq_class_name)
    {
        return $project_checker->classlike_storage_provider->get($fq_class_name)->user_defined;
    }

    /**
     * Gets the method/function call map
     *
     * @return array<string, array<string, string>>
     * @psalm-suppress MixedInferredReturnType as the use of require buggers things up
     * @psalm-suppress MixedAssignment
     */
    public static function getPropertyMap()
    {
        if (self::$property_map !== null) {
            return self::$property_map;
        }

        /** @var array<string, array<string, string>> */
        $property_map = require_once(__DIR__ . '/../PropertyMap.php');

        self::$property_map = [];

        foreach ($property_map as $key => $value) {
            $cased_key = strtolower($key);
            self::$property_map[$cased_key] = $value;
        }

        return self::$property_map;
    }

    /**
     * @param   string $class_name
     *
     * @return  bool
     */
    public static function inPropertyMap($class_name)
    {
        return isset(self::getPropertyMap()[strtolower($class_name)]);
    }

    /**
     * @return FileChecker
     */
    public function getFileChecker()
    {
        return $this->file_checker;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$file_classes = [];

        self::$trait_checkers = [];

        self::$class_checkers = [];
    }
}
