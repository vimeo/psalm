<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Exception\DocblockParseException;
use Psalm\Issue\DuplicateClass;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\InvalidDocblock;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\MissingConstructor;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\PropertyNotSetInConstructor;
use Psalm\Issue\RedefinedTraitMethod;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedTrait;
use Psalm\Issue\UnimplementedInterfaceMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Provider\FileReferenceProvider;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\MethodStorage;
use Psalm\Storage\PropertyStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
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
     * @var PhpParser\Node\Stmt\ClassLike
     */
    protected $class;

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
    protected $parent_fq_class_name;

    /**
     * @var array<string, MethodChecker>
     */
    protected $method_checkers = [];

    /**
     * @var array<string, MethodChecker>
     */
    protected $property_types = [];

    /**
     * @var array<string, array<string, string>>|null
     */
    protected static $property_map;

    /**
     * @var array<string, ClassLikeStorage>
     */
    public static $storage = [];

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

        $fq_class_name_lower = strtolower($fq_class_name);

        if (!isset(self::$storage[$fq_class_name_lower])) {
            self::$storage[$fq_class_name_lower] = $storage = new ClassLikeStorage();
            $storage->name = $fq_class_name;
            $storage->location = new CodeLocation($this->source, $class, true);
            $storage->user_defined = true;

            self::$file_classes[$this->source->getFilePath()][] = $fq_class_name;
        } else {
            $storage = self::$storage[$fq_class_name_lower];

            if ($storage->location) {
                $storage_file_path = $storage->location->file_path;
                $source_file_path = $this->source->getCheckedFilePath();

                if (!Config::getInstance()->use_case_sensitive_file_names) {
                    $storage_file_path = strtolower($storage_file_path);
                    $source_file_path = strtolower($source_file_path);
                }

                if ($storage_file_path !== $source_file_path ||
                    $storage->location->getLineNumber() !== $class->getLine()
                ) {
                    if (IssueBuffer::accepts(
                        new DuplicateClass(
                            'Class ' . $fq_class_name . ' has already been defined at ' .
                                $storage_file_path . ':' . $storage->location->getLineNumber(),
                            new \Psalm\CodeLocation($this, $class, true)
                        )
                    )) {
                        // fall through
                    }
                }
            }
        }
    }

    /**
     * @param Context|null  $class_context
     * @param Context|null  $global_context
     * @return false|null
     */
    public function visit(
        Context $class_context = null,
        Context $global_context = null
    ) {
        $storage = self::$storage[strtolower($class_context ? (string)$class_context->self : $this->fq_class_name)];

        $config = Config::getInstance();

        $storage->user_defined = true;

        $long_file_name = $this->source->getFilePath();

        if (!$class_context) {
            $class_context = new Context($this->fq_class_name);
            $class_context->parent = $this->parent_fq_class_name;

            if ($global_context) {
                $class_context->vars_in_scope = $global_context->vars_in_scope;
                $class_context->vars_possibly_in_scope = $global_context->vars_possibly_in_scope;
            }

            $class_context->vars_in_scope['$this'] = new Type\Union([new TNamedObject($this->fq_class_name)]);
            $class_context->vars_possibly_in_scope['$this'] = true;
        }

        if (!($this instanceof TraitChecker) && $storage->registered) {
            // get leftover statements from properties to analyze
            foreach ($this->class->stmts as $stmt) {
                if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                    $this->visitPropertyDeclaration(
                        $stmt,
                        $class_context,
                        $config
                    );
                    $this->leftover_stmts[] = $stmt;
                }
            }

            return null;
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
            if ($this->parent_fq_class_name &&
                $this->registerParentClassInfo($this->parent_fq_class_name) === false
            ) {
                return false;
            }
        }

        if ($this instanceof InterfaceChecker || $this instanceof ClassChecker) {
            $extra_interfaces = [];

            if ($this instanceof InterfaceChecker) {
                $parent_interfaces = InterfaceChecker::getParentInterfaces(
                    $this->fq_class_name,
                    $this->getFileChecker()
                );

                $extra_interfaces = $parent_interfaces;
            } else {
                $parent_interfaces = self::$storage[strtolower($this->fq_class_name)]->class_implements;
            }

            foreach ($parent_interfaces as $interface_name) {
                if (self::checkFullyQualifiedClassLikeName(
                    $interface_name,
                    $this->getFileChecker(),
                    new CodeLocation($this, $this->class, true),
                    $this->getSuppressedIssues()
                ) === false) {
                    return false;
                }

                $extra_interfaces = array_merge(
                    $extra_interfaces,
                    InterfaceChecker::getParentInterfaces(
                        $interface_name,
                        $this->getFileChecker()
                    )
                );

                $interface_storage = self::$storage[strtolower($interface_name)];

                // copy over any constants
                $storage->public_class_constants = array_merge(
                    $storage->public_class_constants,
                    $interface_storage->public_class_constants
                );

                FileReferenceProvider::addFileInheritanceToClass($long_file_name, $interface_name);
            }

            $extra_interfaces = array_unique($extra_interfaces);

            foreach ($extra_interfaces as $extra_interface_name) {
                FileReferenceProvider::addFileInheritanceToClass($long_file_name, $extra_interface_name);

                if ($this instanceof ClassChecker) {
                    $storage->class_implements[strtolower($extra_interface_name)] = $extra_interface_name;
                } else {
                    $this->registerInheritedMethods($this->fq_class_name, $extra_interface_name);
                }
            }
        }

        $doc_comment = $this->class->getDocComment();

        if ($doc_comment) {
            $docblock_info = null;

            try {
                $docblock_info = CommentChecker::extractClassLikeDocblockInfo(
                    (string)$doc_comment,
                    $doc_comment->getLine()
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        $e->getMessage() . ' in docblock for ' . $this->fq_class_name,
                        new CodeLocation($this->source, $this->class, true)
                    )
                )) {
                    // fall through
                }
            }

            if ($docblock_info) {
                if ($docblock_info->template_types) {
                    $storage->template_types = [];

                    foreach ($docblock_info->template_types as $template_type) {
                        if (count($template_type) === 3) {
                            $as_type_string = ClassLikeChecker::getFQCLNFromString($template_type[2], $this->source);
                            $storage->template_types[$template_type[0]] = $as_type_string;
                        } else {
                            $storage->template_types[$template_type[0]] = 'mixed';
                        }
                    }
                }

                if ($docblock_info->properties) {
                    foreach ($docblock_info->properties as $property) {
                        $pseudo_property_type = Type::parseString(
                            FunctionLikeChecker::fixUpLocalType(
                                $property['type'],
                                $this
                            )
                        );

                        $storage->pseudo_instance_properties[$property['name']] = $pseudo_property_type;
                    }
                }
            }
        }

        $const_stmts = [];

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $this->visitClassMethod(
                    $stmt,
                    $class_context
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                $this->visitTraitUse(
                    $stmt,
                    $class_context
                );
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                $this->visitPropertyDeclaration(
                    $stmt,
                    $class_context,
                    $config
                );
                $this->leftover_stmts[] = $stmt;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $this->visitClassConstDeclaration(
                    $stmt,
                    $class_context,
                    $config
                );
                $const_stmts[] = $stmt;
            }
        }

        if (MethodChecker::methodExists($this->fq_class_name . '::__get', $this->getFileChecker())) {
            $this->has_custom_get = true;
        }

        if ($const_stmts) {
            (new StatementsChecker($this))->analyze($const_stmts, $class_context);
        }

        $config = Config::getInstance();

        if ($this instanceof ClassChecker && $this->class instanceof PhpParser\Node\Stmt\Class_) {
            $storage->abstract = (bool)$this->class->isAbstract();

            foreach (ClassChecker::getInterfacesForClass(
                $this->fq_class_name
            ) as $_ => $interface_name) {
                $interface_storage = self::$storage[strtolower($interface_name)];

                $storage->public_class_constants += $interface_storage->public_class_constants;

                foreach ($interface_storage->methods as $method_name => $method) {
                    if ($method->visibility === self::VISIBILITY_PUBLIC) {
                        $mentioned_method_id = $interface_name . '::' . $method_name;
                        $implemented_method_id = $this->fq_class_name . '::' . $method_name;

                        MethodChecker::setOverriddenMethodId($implemented_method_id, $mentioned_method_id);
                    }
                }
            }
        }

        $plugins = Config::getInstance()->getPlugins();

        if ($plugins) {
            $code_location = new CodeLocation($this->source, $this->class, true);

            foreach ($plugins as $plugin) {
                if ($plugin->visitClassLike($this, $this->class, $storage, $code_location) === false) {
                    return false;
                }
            }
        }

        $storage->registered = true;

        return null;
    }

    /**
     * @param Context|null  $class_context
     * @param Context|null  $global_context
     * @param bool          $update_docblocks
     * @return null|false
     */
    public function analyze(
        Context $class_context = null,
        Context $global_context = null,
        $update_docblocks = false
    ) {
        $fq_class_name = $class_context && $class_context->self ? $class_context->self : $this->fq_class_name;

        $storage = self::$storage[strtolower($fq_class_name)];

        if ($this->class instanceof PhpParser\Node\Stmt\Class_ && $this->class->extends) {
            if (!$this->parent_fq_class_name) {
                throw new \UnexpectedValueException('Parent class should be filled in');
            }

            $parent_reference_location = new CodeLocation($this, $this->class->extends);

            if (!ClassLikeChecker::classOrInterfaceExists(
                $this->parent_fq_class_name,
                $this->getFileChecker(),
                $parent_reference_location
            )) {
                // we should not normally get here
                return;
            }
        } elseif ($this->class instanceof PhpParser\Node\Stmt\Interface_ && $this->class->extends) {
            foreach ($this->class->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this
                );

                $parent_reference_location = new CodeLocation($this, $extended_interface);

                if (!ClassLikeChecker::classOrInterfaceExists(
                    $extended_interface_name,
                    $this->getFileChecker(),
                    $parent_reference_location
                )) {
                    // we should not normally get here
                    return;
                }
            }
        }

        if ($this instanceof ClassChecker && $this->class instanceof PhpParser\Node\Stmt\Class_) {
            $class_interfaces = ClassChecker::getInterfacesForClass($this->fq_class_name);

            if (!$this->class->isAbstract()) {
                foreach ($class_interfaces as $interface_name) {
                    if (!isset(self::$storage[strtolower($interface_name)])) {
                        continue;
                    }

                    $interface_storage = self::$storage[strtolower($interface_name)];

                    $storage->public_class_constants += $interface_storage->public_class_constants;

                    foreach ($interface_storage->methods as $method_name => $method) {
                        if ($method->visibility === self::VISIBILITY_PUBLIC) {
                            $implemented_method_id = $this->fq_class_name . '::' . $method_name;
                            $mentioned_method_id = $interface_name . '::' . $method_name;
                            $declaring_method_id = MethodChecker::getDeclaringMethodId($implemented_method_id);

                            $method_storage = $declaring_method_id
                                ? MethodChecker::getStorage($declaring_method_id)
                                : null;

                            if (!$method_storage) {
                                $cased_method_id = MethodChecker::getCasedMethodId($mentioned_method_id);

                                if (IssueBuffer::accepts(
                                    new UnimplementedInterfaceMethod(
                                        'Method ' . $cased_method_id . ' is not defined on class ' .
                                        $this->fq_class_name,
                                        new CodeLocation($this, $this->class, true)
                                    ),
                                    $this->source->getSuppressedIssues()
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
                                    $this->source->getSuppressedIssues()
                                )) {
                                    return false;
                                }

                                return null;
                            }
                        }
                    }
                }
            }
        }

        if (!$class_context) {
            $class_context = new Context($this->fq_class_name);
            $class_context->collect_references = $this->getFileChecker()->project_checker->collect_references;
            $class_context->parent = $this->parent_fq_class_name;
        }

        if ($this->leftover_stmts) {
            (new StatementsChecker($this))->analyze($this->leftover_stmts, $class_context);
        }

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
            if (explode('::', $appearing_property_id)[0] !== $fq_class_name) {
                continue;
            }

            $property_class_name = self::getDeclaringClassForProperty($appearing_property_id);
            $property_class_storage = self::$storage[strtolower((string)$property_class_name)];
            $property_class_name = self::getDeclaringClassForProperty($appearing_property_id);

            $property = $property_class_storage->properties[$property_name];

            if ($property->type) {
                $property_type = clone $property->type;

                if (!$property_type->isMixed() &&
                    !$property->has_default &&
                    !$property->type->isNullable()
                ) {
                    $property_type->initialized = false;
                }

                if ($storage->template_types) {
                    $generic_types = [];
                    $property_type->replaceTemplateTypes($storage->template_types, $generic_types);
                }
            } else {
                $property_type = Type::getMixed();
            }

            if ($property->type_location && !$property_type->isMixed()) {
                $fleshed_out_type = ExpressionChecker::fleshOutTypes(clone $property_type, $this->fq_class_name, null);
                $fleshed_out_type->check($this, $property->type_location, $this->getSuppressedIssues(), [], false);
            }

            if ($property->is_static) {
                $property_id = $this->fq_class_name . '::$' . $property_name;

                $class_context->vars_in_scope[$property_id] = $property_type;
            } else {
                $class_context->vars_in_scope['$this->' . $property_name] = $property_type;
            }
        }

        $constructor_checker = null;

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_checker = $this->analyzeClassMethod(
                    $stmt,
                    $this,
                    $class_context,
                    $global_context,
                    $update_docblocks
                );

                if ($stmt->name === '__construct') {
                    $constructor_checker = $method_checker;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source
                    );

                    if (!isset(self::$trait_checkers[$fq_trait_name])) {
                        throw new \UnexpectedValueException('Expecting trait to be hydrated');
                    }

                    $trait_checker = self::$trait_checkers[$fq_trait_name];

                    foreach ($trait_checker->class->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                            $this->analyzeClassMethod(
                                $trait_stmt,
                                $trait_checker,
                                $class_context,
                                $global_context
                            );
                        }
                    }
                }
            }
        }

        $config = Config::getInstance();

        if ($config->reportIssueInFile('PropertyNotSetInConstructor', $this->getFilePath())) {
            $uninitialized_variables = [];
            $uninitialized_properties = [];

            foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
                if (explode('::', $appearing_property_id)[0] !== $fq_class_name) {
                    continue;
                }

                $property_class_name = self::getDeclaringClassForProperty($appearing_property_id);
                $property_class_storage = self::$storage[strtolower((string)$property_class_name)];
                $property_class_name = self::getDeclaringClassForProperty($appearing_property_id);

                $property = $property_class_storage->properties[$property_name];

                if (!$property->has_default &&
                    $property->type &&
                    !$property->type->isMixed() &&
                    !$property->type->isNullable() &&
                    !$property->is_static
                ) {
                    $uninitialized_variables[] = '$this->' . $property_name;
                    $uninitialized_properties[$property_name] = $property;
                    break;
                }
            }

            if ($uninitialized_properties) {
                if (isset($storage->methods['__construct']) && $constructor_checker) {
                    $method_context = clone $class_context;
                    $method_context->collect_initializations = true;
                    $method_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
                    $method_context->vars_possibly_in_scope['$this'] = true;

                    $constructor_checker->analyze($method_context, $global_context, true);

                    foreach ($uninitialized_properties as $property_name => $property) {
                        if (!isset($method_context->vars_in_scope['$this->' . $property_name])) {
                            throw new \UnexpectedValueException('$this->' . $property_name . ' should be in scope');
                        }

                        $end_type = $method_context->vars_in_scope['$this->' . $property_name];

                        if (!$end_type->initialized && $property->location) {
                            $property_id = $this->fq_class_name . '::$' . $property_name;

                            if (IssueBuffer::accepts(
                                new PropertyNotSetInConstructor(
                                    'Property ' . $property_id . ' is not defined in constructor of ' .
                                        $this->fq_class_name . ' or in any private methods called in the constructor',
                                    $property->location
                                ),
                                $this->source->getSuppressedIssues()
                            )) {
                                continue;
                            }
                        }
                    }
                } elseif (!$this instanceof TraitChecker) {
                    $first_uninitialized_property = array_shift($uninitialized_properties);

                    if ($first_uninitialized_property->location) {
                        if (IssueBuffer::accepts(
                            new MissingConstructor(
                                $fq_class_name . ' has an uninitialized variable ' . $uninitialized_variables[0] .
                                    ', but no constructor',
                                $first_uninitialized_property->location
                            ),
                            $this->source->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                $this->checkForMissingPropertyType($stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source
                    );

                    if (!isset(self::$trait_checkers[$fq_trait_name])) {
                        throw new \UnexpectedValueException('Expecting trait to be hydrated');
                    }

                    $trait_checker = self::$trait_checkers[$fq_trait_name];

                    foreach ($trait_checker->class->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\Property) {
                            $this->checkForMissingPropertyType($trait_stmt);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\ClassMethod $stmt
     * @param  StatementsSource                $source
     * @param  Context                         $class_context
     * @param  Context|null                    $global_context
     * @param  boolean                         $update_docblocks
     * @return MethodChecker|null
     */
    private function analyzeClassMethod(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        StatementsSource $source,
        Context $class_context,
        Context $global_context = null,
        $update_docblocks = false
    ) {
        $config = Config::getInstance();

        $method_checker = new MethodChecker($stmt, $source);

        $actual_method_id = (string)$method_checker->getMethodId();

        if ($class_context->self && $class_context->self !== $source->getFQCLN()) {
            $analyzed_method_id = (string)$method_checker->getMethodId($class_context->self);

            $declaring_method_id = MethodChecker::getDeclaringMethodId($analyzed_method_id);

            if ($actual_method_id !== $declaring_method_id) {
                return;
            }
        }

        $method_checker->analyze(
            clone $class_context,
            $global_context ? clone $global_context : null
        );

        if ($config->reportIssueInFile('InvalidReturnType', $source->getFilePath())) {
            $return_type_location = null;
            $secondary_return_type_location = null;

            $actual_method_storage = MethodChecker::getStorage($actual_method_id);

            if (!$actual_method_storage->has_template_return_type) {
                if ($actual_method_id) {
                    $return_type_location = MethodChecker::getMethodReturnTypeLocation(
                        $actual_method_id,
                        $secondary_return_type_location
                    );
                }

                $return_type = MethodChecker::getMethodReturnType($actual_method_id);

                $method_checker->verifyReturnType(
                    $update_docblocks,
                    $return_type ? clone $return_type : null,
                    $class_context->self,
                    $return_type_location,
                    $secondary_return_type_location
                );
            }
        }

        return $method_checker;
    }

    /**
     * @param  string       $method_name
     * @param  Context      $context
     * @return void
     */
    public function getMethodMutations(
        $method_name,
        Context $context
    ) {
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
                        $this->source
                    );

                    $trait_checker = self::$trait_checkers[$fq_trait_name];

                    foreach ($trait_checker->class->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod &&
                            strtolower($trait_stmt->name) === strtolower($method_name)
                        ) {
                            $method_checker = new MethodChecker($trait_stmt, $trait_checker);

                            $actual_method_id = (string)$method_checker->getMethodId();

                            if ($context->self && $context->self !== $this->fq_class_name) {
                                $analyzed_method_id = (string)$method_checker->getMethodId($context->self);
                                $declaring_method_id = MethodChecker::getDeclaringMethodId($analyzed_method_id);

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
     * @param  string  $parent_class
     * @return false|null
     */
    private function registerParentClassInfo($parent_class)
    {
        if (!$this->class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \UnexpectedValueException('Cannot register parent class where none exists');
        }

        if (!$this->class->extends) {
            throw new \UnexpectedValueException('Cannot register parent class where none exists');
        }

        if (self::checkFullyQualifiedClassLikeName(
            $parent_class,
            $this->getFileChecker(),
            new CodeLocation($this, $this->class->extends, true),
            $this->getSuppressedIssues()
        ) === false
        ) {
            return false;
        }

        // if we don't care the parent class does not exist, exit now.
        if (!ClassLikeChecker::classOrInterfaceExists($parent_class, $this->getFileChecker())) {
            return;
        }

        $this->registerInheritedMethods($this->fq_class_name, $parent_class);
        $this->registerInheritedProperties($this->fq_class_name, $parent_class);

        $storage = self::$storage[strtolower($this->fq_class_name)];

        $parent_storage = self::$storage[strtolower($parent_class)];

        $storage->class_implements += $parent_storage->class_implements;

        $storage->public_class_constants = $parent_storage->public_class_constants;
        $storage->protected_class_constants = $parent_storage->protected_class_constants;

        $storage->parent_classes = array_merge([strtolower($parent_class)], $parent_storage->parent_classes);

        $storage->used_traits = $parent_storage->used_traits;

        FileReferenceProvider::addFileInheritanceToClass(
            $this->source->getFilePath(),
            $parent_class
        );

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\ClassMethod $stmt
     * @param   Context                         $class_context
     * @return  void
     */
    private function visitClassMethod(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        Context $class_context
    ) {
        $method_name = strtolower($stmt->name);
        $method_id = $this->fq_class_name . '::' . $method_name;
        $class_storage = self::$storage[strtolower($this->fq_class_name)];

        if (!isset($class_storage->methods[$method_name])) {
            FunctionLikeChecker::register($stmt, $this);
        }

        if ((!$stmt->isAbstract() || $this instanceof TraitChecker) && $class_context->self) {
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
     * @return  false|null
     */
    private function visitTraitUse(
        PhpParser\Node\Stmt\TraitUse $stmt,
        Context $class_context
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
                $this->source
            );

            if (!TraitChecker::traitExists($trait_name, $this->getFileChecker())) {
                if (IssueBuffer::accepts(
                    new UndefinedTrait(
                        'Trait ' . $trait_name . ' does not exist',
                        new CodeLocation($this, $trait)
                    ),
                    $this->source->getSuppressedIssues()
                )) {
                    return false;
                }
            } else {
                if (!TraitChecker::hasCorrectCase($trait_name, $this->getFileChecker())) {
                    if (IssueBuffer::accepts(
                        new UndefinedTrait(
                            'Trait ' . $trait_name . ' has wrong casing',
                            new CodeLocation($this, $trait)
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    continue;
                }

                $trait_checker = self::$trait_checkers[$trait_name];

                $trait_checker->setMethodMap($method_map);

                $trait_checker->visit($class_context);

                self::registerInheritedProperties($this->fq_class_name, $trait_name);

                ClassLikeChecker::registerTraitUse($this->fq_class_name, $trait_name);

                FileReferenceProvider::addFileInheritanceToClass(
                    $this->source->getFilePath(),
                    $trait_name
                );
            }
        }

        return null;
    }

    /**
     * @param   PhpParser\Node\Stmt\Property    $stmt
     * @param   Context                         $class_context
     * @param   Config                          $config
     * @return  void
     */
    private function visitPropertyDeclaration(
        PhpParser\Node\Stmt\Property $stmt,
        Context $class_context,
        Config $config
    ) {
        $comment = $stmt->getDocComment();
        $type_in_comment = null;
        $property_type_line_number = null;
        $storage = self::$storage[strtolower($this->fq_class_name)];

        if ($comment && $comment->getText() && $config->use_docblock_types) {
            try {
                $property_type_line_number = $comment->getLine();
                $type_in_comment = CommentChecker::getTypeFromComment(
                    $comment->getText(),
                    null,
                    $this,
                    null,
                    $storage->template_types,
                    $property_type_line_number
                );
            } catch (DocblockParseException $e) {
                if (IssueBuffer::accepts(
                    new InvalidDocblock(
                        (string)$e->getMessage(),
                        new CodeLocation($this, $this->class, true)
                    )
                )) {
                    // fall through
                }
            }
        }

        $property_group_type = $type_in_comment ?: null;

        foreach ($stmt->props as $property) {
            $property_type_location = null;
            $default_type = null;

            if (!$property_group_type) {
                if ($property->default) {
                    $default_type = StatementsChecker::getSimpleType($property->default);

                    if (!$config->use_property_default_for_type) {
                        $property_type = false;
                    } else {
                        $property_type = $default_type ?: Type::getMixed();
                    }
                } else {
                    $property_type = false;
                }
            } else {
                if ($property_type_line_number) {
                    $property_type_location = new CodeLocation($this, $stmt);
                    $property_type_location->setCommentLine($property_type_line_number);
                }

                $property_type = count($stmt->props) === 1 ? $property_group_type : clone $property_group_type;
            }

            $storage->properties[$property->name] = new PropertyStorage();
            $storage->properties[$property->name]->is_static = (bool)$stmt->isStatic();
            $storage->properties[$property->name]->type = $property_type;
            $storage->properties[$property->name]->location = new CodeLocation($this, $property);
            $storage->properties[$property->name]->type_location = $property_type_location;
            $storage->properties[$property->name]->has_default = $property->default ? true : false;
            $storage->properties[$property->name]->suggested_type = $property_group_type ? null : $default_type;

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

            if (!$stmt->isPrivate()) {
                $storage->inheritable_property_ids[$property->name] = $property_id;
            }
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\Property    $stmt
     * @return  void
     */
    private function checkForMissingPropertyType(PhpParser\Node\Stmt\Property $stmt)
    {
        $comment = $stmt->getDocComment();
        $type_in_comment = null;
        $property_type_line_number = null;
        $storage = self::$storage[strtolower($this->fq_class_name)];

        if (!$comment || !$comment->getText()) {
            $fq_class_name = $this->fq_class_name;
            $property_name = $stmt->props[0]->name;

            $declaring_property_class = ClassLikeChecker::getDeclaringClassForProperty(
                $fq_class_name . '::$' . $property_name
            );

            if (!$declaring_property_class) {
                throw new \UnexpectedValueException(
                    'Cannot get declaring class for ' . $fq_class_name . '::$' . $property_name
                );
            }

            $fq_class_name = $declaring_property_class;

            $message = 'Property ' . $fq_class_name . '::$' . $property_name . ' does not have a declared type';

            $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];

            $property_storage = $class_storage->properties[$property_name];

            if ($property_storage->suggested_type && !$property_storage->suggested_type->isNull()) {
                $message .= ' - consider ' . str_replace(
                    ['<mixed, mixed>', '<empty, empty>'],
                    '',
                    (string)$property_storage->suggested_type
                );
            }

            if (IssueBuffer::accepts(
                new MissingPropertyType(
                    $message,
                    new CodeLocation($this, $stmt)
                ),
                $this->source->getSuppressedIssues()
            )) {
                // fall through
            }
        }
    }

    /**
     * @param   PhpParser\Node\Stmt\ClassConst  $stmt
     * @param   Context                         $class_context
     * @param   Config                          $config
     * @return  void
     */
    private function visitClassConstDeclaration(
        PhpParser\Node\Stmt\ClassConst $stmt,
        Context $class_context,
        Config $config
    ) {
        $comment = $stmt->getDocComment();
        $type_in_comment = null;
        $storage = self::$storage[strtolower((string)$class_context->self)];

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
     * Check whether a class/interface exists
     *
     * @param  string       $fq_class_name
     * @param  FileChecker  $file_checker
     * @param  CodeLocation $code_location
     * @return bool
     */
    public static function classOrInterfaceExists(
        $fq_class_name,
        FileChecker $file_checker,
        CodeLocation $code_location = null
    ) {
        if (!ClassChecker::classExists($fq_class_name, $file_checker) &&
            !InterfaceChecker::interfaceExists($fq_class_name, $file_checker)
        ) {
            return false;
        }

        if ($file_checker->project_checker->collect_references && $code_location) {
            $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];
            if ($class_storage->referencing_locations === null) {
                $class_storage->referencing_locations = [];
            }
            $class_storage->referencing_locations[$file_checker->getFilePath()][] = $code_location;
        }

        return true;
    }

    /**
     * @param  string       $fq_class_name
     * @param  string       $possible_parent
     * @return bool
     */
    public static function classExtendsOrImplements(
        $fq_class_name,
        $possible_parent
    ) {
        return ClassChecker::classExtends($fq_class_name, $possible_parent) ||
            ClassChecker::classImplements($fq_class_name, $possible_parent);
    }

    /**
     * @param  string           $fq_class_name
     * @param  FileChecker      $file_checker
     * @param  CodeLocation     $code_location
     * @param  array<string>    $suppressed_issues
     * @param  bool             $inferred - whether or not the type was inferred
     * @return bool|null
     */
    public static function checkFullyQualifiedClassLikeName(
        $fq_class_name,
        FileChecker $file_checker,
        CodeLocation $code_location,
        array $suppressed_issues,
        $inferred = true
    ) {
        if (empty($fq_class_name)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        $fq_class_name = preg_replace('/^\\\/', '', $fq_class_name);

        if (in_array($fq_class_name, ['callable', 'iterable'])) {
            return true;
        }

        $class_exists = ClassChecker::classExists($fq_class_name, $file_checker);
        $interface_exists = InterfaceChecker::interfaceExists($fq_class_name, $file_checker);

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

        if ($file_checker->project_checker->collect_references && !$inferred) {
            $class_storage = ClassLikeChecker::$storage[strtolower($fq_class_name)];
            if ($class_storage->referencing_locations === null) {
                $class_storage->referencing_locations = [];
            }
            $class_storage->referencing_locations[$file_checker->getFilePath()][] = $code_location;
        }

        if (($class_exists && !ClassChecker::hasCorrectCasing($fq_class_name, $file_checker)) ||
            ($interface_exists && !InterfaceChecker::hasCorrectCasing($fq_class_name, $file_checker))
        ) {
            $file_checker->evaluateClassLike($fq_class_name);

            if (ClassLikeChecker::isUserDefined($fq_class_name)) {
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
        }

        FileReferenceProvider::addFileReferenceToClass(
            $code_location->file_path,
            $fq_class_name
        );

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     *
     * @param  PhpParser\Node\Name      $class_name
     * @param  StatementsSource         $source
     * @return string
     */
    public static function getFQCLNFromNameObject(
        PhpParser\Node\Name $class_name,
        StatementsSource $source
    ) {
        if ($class_name->parts == ['self']) {
            return 'self';
        }

        if ($class_name instanceof PhpParser\Node\Name\FullyQualified) {
            return implode('\\', $class_name->parts);
        }

        return self::getFQCLNFromString(
            implode('\\', $class_name->parts),
            $source
        );
    }

    /**
     * @param  string                   $class
     * @param  StatementsSource         $source
     * @return string
     */
    public static function getFQCLNFromString($class, StatementsSource $source)
    {
        if (empty($class)) {
            throw new \InvalidArgumentException('$class cannot be empty');
        }

        if ($class[0] === '\\') {
            return substr($class, 1);
        }

        $imported_namespaces = $source->getAliasedClasses();

        if (strpos($class, '\\') !== false) {
            $class_parts = explode('\\', $class);
            $first_namespace = array_shift($class_parts);

            if (isset($imported_namespaces[strtolower($first_namespace)])) {
                return $imported_namespaces[strtolower($first_namespace)] . '\\' . implode('\\', $class_parts);
            }
        } elseif (isset($imported_namespaces[strtolower($class)])) {
            return $imported_namespaces[strtolower($class)];
        }

        $namespace = $source->getNamespace();

        return ($namespace ? $namespace . '\\' : '') . $class;
    }

    /**
     * @return string
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
     * @return bool
     */
    public function hasCustomGet()
    {
        return $this->has_custom_get;
    }

    /**
     * @param  string          $class_name
     * @param  ReflectionClass $reflected_class
     * @param  ProjectChecker  $project_checker
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

        if (isset(self::$storage[$class_name_lower]) && self::$storage[$class_name_lower]->reflected) {
            return;
        }

        $reflected_parent_class = $reflected_class->getParentClass();

        $storage = self::$storage[$class_name_lower] = new ClassLikeStorage();
        $storage->name = $class_name;
        $storage->abstract = $reflected_class->isAbstract();

        if ($reflected_parent_class) {
            $parent_class_name = $reflected_parent_class->getName();
            self::registerReflectedClass($parent_class_name, $reflected_parent_class, $project_checker);

            $parent_storage = self::$storage[strtolower($parent_class_name)];

            self::registerInheritedMethods($class_name, $parent_class_name);
            self::registerInheritedProperties($class_name, $parent_class_name);

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

        $storage->registered = true;

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
                $storage->parent_interfaces[] = $interface_name;
            } else {
                $storage->class_implements[strtolower($interface_name)] = $interface_name;
            }
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
     * @param string $fq_class_name
     * @param string $parent_class
     * @return void
     */
    protected static function registerInheritedMethods($fq_class_name, $parent_class)
    {
        $parent_storage = self::$storage[strtolower($parent_class)];
        $storage = self::$storage[strtolower($fq_class_name)];

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $parent_method_id = $parent_class . '::' . $method_name;

            /** @var string */
            $appearing_method_id = MethodChecker::getAppearingMethodId($parent_method_id);
            $implemented_method_id = $fq_class_name . '::' . $method_name;

            $storage->appearing_method_ids[$method_name] = $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_method_ids as $method_name => $declaring_method_id) {
            $parent_method_id = $parent_class . '::' . $method_name;

            /** @var string */
            $declaring_method_id = MethodChecker::getDeclaringMethodId($parent_method_id);
            $implemented_method_id = $fq_class_name . '::' . $method_name;

            $storage->declaring_method_ids[$method_name] = $declaring_method_id;

            MethodChecker::setOverriddenMethodId($implemented_method_id, $declaring_method_id);
        }
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     * @return void
     */
    protected static function registerInheritedProperties($fq_class_name, $parent_class)
    {
        $parent_storage = self::$storage[strtolower($parent_class)];
        $storage = self::$storage[strtolower($fq_class_name)];

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $storage->appearing_property_ids[$property_name] = $appearing_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_property_ids as $property_name => $declaring_property_id) {
            $storage->declaring_property_ids[$property_name] = $declaring_property_id;
        }

        // register where they're declared
        foreach ($parent_storage->inheritable_property_ids as $property_name => $inheritable_property_id) {
            $storage->inheritable_property_ids[$property_name] = $inheritable_property_id;
        }
    }

    /**
     * @param  string $class_name
     * @param  mixed  $visibility
     * @return array<string, PropertyStorage>
     */
    public static function getPropertiesForClass($class_name, $visibility)
    {
        $class_name = strtolower($class_name);

        if (!isset(self::$storage[$class_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $class_name);
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
        $class_name = strtolower($class_name);

        $class_name = strtolower($class_name);

        if (!isset(self::$storage[$class_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $class_name);
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
        $storage = self::$storage[strtolower($class_name)];

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

        if (!isset(self::$storage[strtolower($fq_class_name)])) {
            throw new \UnexpectedValueException(
                'Storage not defined for ' . $fq_class_name
            );
        }

        $class_storage = self::$storage[strtolower($fq_class_name)];

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

        list(, $property_name) = explode('::$', (string)$property_id);

        // if the calling class is the same, we know the property exists, so it must be visible
        if ($appearing_property_class === $calling_context) {
            return null;
        }

        if ($source->getSource() instanceof TraitChecker && $declaring_property_class === $source->getFQCLN()) {
            return null;
        }

        $class_storage = self::$storage[strtolower($declaring_property_class)];

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

        $fq_class_name = strtolower($fq_class_name);

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

        $fq_class_name = strtolower($fq_class_name);

        if (isset(ClassLikeChecker::$storage[$fq_class_name]->appearing_property_ids[$property_name])) {
            $appearing_property_id = ClassLikeChecker::$storage[$fq_class_name]->appearing_property_ids[$property_name];

            return explode('::$', $appearing_property_id)[0];
        }
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
     * @param   string $file_path
     * @return  array<string>
     */
    public static function getClassesForFile($file_path)
    {
        return isset(self::$file_classes[$file_path]) ? array_unique(self::$file_classes[$file_path]) : [];
    }

    /**
     * @param  string  $fq_class_name
     * @return boolean
     */
    public static function isUserDefined($fq_class_name)
    {
        return self::$storage[strtolower($fq_class_name)]->user_defined;
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
        $storage = self::$storage[strtolower($fq_class_name)];

        $storage->used_traits[$fq_trait_name] = true;
    }

    /**
     * @return void
     */
    public static function clearCache()
    {
        self::$file_classes = [];

        self::$trait_checkers = [];

        self::$class_checkers = [];

        self::$storage = [];

        ClassChecker::clearCache();
    }
}
