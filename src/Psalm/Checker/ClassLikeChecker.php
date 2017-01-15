<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Aliases;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\DuplicateClass;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InaccessibleProperty;
use Psalm\Issue\InvalidClass;
use Psalm\Issue\MissingConstructor;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\PropertyNotSetInConstructor;
use Psalm\Issue\UndefinedClass;
use Psalm\Issue\UndefinedTrait;
use Psalm\Issue\UnimplementedAbstractMethod;
use Psalm\Issue\UnimplementedInterfaceMethod;
use Psalm\IssueBuffer;
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
                        new \Psalm\CodeLocation($this, $class, null, true)
                    )
                )) {
                    // fall through
                }
            }
        }
    }

    /**
     * @param Context|null  $class_context
     * @param Context|null  $global_context
     * @param bool          $update_docblocks
     *
     * @return null|false
     */
    public function analyze(
        Context $class_context = null,
        Context $global_context = null,
        $update_docblocks = false
    ) {
        $fq_class_name = $class_context && $class_context->self ? $class_context->self : $this->fq_class_name;

        $storage = self::$storage[strtolower($fq_class_name)];

        if ($this->class instanceof PhpParser\Node\Stmt\Class_) {
            if ($this->class->extends) {
                if (!$this->parent_fq_class_name) {
                    throw new \UnexpectedValueException('Parent class should be filled in');
                }

                $parent_reference_location = new CodeLocation($this, $this->class->extends);

                if (self::checkFullyQualifiedClassLikeName(
                    $this->parent_fq_class_name,
                    $this->getFileChecker(),
                    $parent_reference_location,
                    $this->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }

            foreach ($this->class->implements as $interface_name) {
                $fq_interface_name = self::getFQCLNFromNameObject(
                    $interface_name,
                    $this->source->getAliases()
                );

                $interface_location = new CodeLocation($this, $interface_name);

                if (self::checkFullyQualifiedClassLikeName(
                    $fq_interface_name,
                    $this->getFileChecker(),
                    $interface_location,
                    $this->getSuppressedIssues()
                ) === false) {
                    return false;
                }
            }
        } elseif ($this->class instanceof PhpParser\Node\Stmt\Interface_ && $this->class->extends) {
            foreach ($this->class->extends as $extended_interface) {
                $extended_interface_name = self::getFQCLNFromNameObject(
                    $extended_interface,
                    $this->getAliases()
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
                                        new CodeLocation(
                                            $this,
                                            $this->class,
                                            $class_context ? $class_context->include_location : null,
                                            true
                                        )
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
                                        new CodeLocation(
                                            $this,
                                            $this->class,
                                            $class_context ? $class_context->include_location : null,
                                            true
                                        )
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

        if (!$storage->abstract) {
            foreach ($storage->declaring_method_ids as $method_name => $declaring_method_id) {
                $method_storage = MethodChecker::getStorage($declaring_method_id);

                list($declaring_class_name, $method_name) = explode('::', $declaring_method_id);

                if ($method_storage->abstract) {
                    if (IssueBuffer::accepts(
                        new UnimplementedAbstractMethod(
                            'Method ' . $method_name . ' is not defined on class ' .
                            $this->fq_class_name . ', defined abstract in ' . $declaring_class_name,
                            new CodeLocation(
                                $this,
                                $this->class,
                                $class_context->include_location,
                                true
                            )
                        ),
                        $this->source->getSuppressedIssues()
                    )) {
                        return false;
                    }
                }
            }
        }

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
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
                $previous_context_include_location = $class_context->include_location;
                foreach ($stmt->traits as $trait) {
                    $class_context->include_location = new CodeLocation($this, $trait, null, true);

                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    if (!TraitChecker::traitExists($fq_trait_name, $this->getFileChecker())) {
                        if (IssueBuffer::accepts(
                            new UndefinedTrait(
                                'Trait ' . $fq_trait_name . ' does not exist',
                                new CodeLocation($this, $trait)
                            ),
                            $this->source->getSuppressedIssues()
                        )) {
                            return false;
                        }
                    } else {
                        if (!TraitChecker::hasCorrectCase($fq_trait_name, $this->getFileChecker())) {
                            if (IssueBuffer::accepts(
                                new UndefinedTrait(
                                    'Trait ' . $fq_trait_name . ' has wrong casing',
                                    new CodeLocation($this, $trait)
                                ),
                                $this->source->getSuppressedIssues()
                            )) {
                                return false;
                            }

                            continue;
                        }

                        if (!isset(self::$trait_checkers[strtolower($fq_trait_name)])) {
                            throw new \UnexpectedValueException('Expecting trait statements to exist');
                        }

                        $trait_checker = self::$trait_checkers[strtolower($fq_trait_name)];

                        foreach ($trait_checker->class->stmts as $trait_stmt) {
                            if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                                $trait_method_checker = $this->analyzeClassMethod(
                                    $trait_stmt,
                                    $trait_checker,
                                    $class_context,
                                    $global_context
                                );

                                if ($trait_stmt->name === '__construct') {
                                    $constructor_checker = $trait_method_checker;
                                }
                            }
                        }
                    }
                }

                $class_context->include_location = $previous_context_include_location;
            }
        }

        $config = Config::getInstance();

        if ($this->class instanceof PhpParser\Node\Stmt\Class_
            && $config->reportIssueInFile('PropertyNotSetInConstructor', $this->getFilePath())
        ) {
            $uninitialized_variables = [];
            $uninitialized_properties = [];

            foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
                $property_class_name = self::getDeclaringClassForProperty($appearing_property_id);
                $property_class_storage = self::$storage[strtolower((string)$property_class_name)];
                $property_class_name = self::getDeclaringClassForProperty($appearing_property_id);

                $property = $property_class_storage->properties[$property_name];

                $constructor_class_storage = null;

                if (isset($property_class_storage->methods['__construct'])
                    && $property_class_storage !== $storage
                ) {
                    $constructor_class_storage = $property_class_storage;
                } elseif (!empty($property_class_storage->overridden_method_ids['__construct'])) {
                    list($construct_fqcln) =
                        explode('::', $property_class_storage->overridden_method_ids['__construct'][0]);
                    $constructor_class_storage = self::$storage[strtolower($construct_fqcln)];
                }

                if ((!$constructor_class_storage
                        || !$constructor_class_storage->all_properties_set_in_constructor
                        || $constructor_class_storage->methods['__construct']->visibility === self::VISIBILITY_PRIVATE)
                    && !$property->has_default
                    && $property->type
                    && !$property->type->isMixed()
                    && !$property->type->isNullable()
                    && !$property->is_static
                ) {
                    $uninitialized_variables[] = '$this->' . $property_name;
                    $uninitialized_properties[$property_name] = $property;
                }
            }

            if ($uninitialized_properties) {
                if (!$storage->abstract
                    && !$constructor_checker
                    && isset($storage->declaring_method_ids['__construct'])
                    && $this->class->extends
                ) {
                    list($construct_fqcln) = explode('::', $storage->declaring_method_ids['__construct']);

                    $constructor_class_storage = self::$storage[strtolower($construct_fqcln)];

                    // ignore oldstyle constructors and classes without any declared properties
                    if (isset($constructor_class_storage->methods['__construct'])) {
                        $constructor_storage = self::$storage[strtolower($construct_fqcln)]->methods['__construct'];

                        $fake_constructor_params = array_map(
                            /** @return PhpParser\Node\Param */
                            function (\Psalm\FunctionLikeParameter $param) {
                                return (new PhpParser\Builder\Param($param->name))
                                    ->setTypehint((string)$param->signature_type)
                                    ->getNode();
                            },
                            $constructor_storage->params
                        );

                        $fake_constructor_stmt_args = array_map(
                            /** @return PhpParser\Node\Arg */
                            function (\Psalm\FunctionLikeParameter $param) {
                                return new PhpParser\Node\Arg(new PhpParser\Node\Expr\Variable($param->name));
                            },
                            $constructor_storage->params
                        );

                        $fake_constructor_stmts = [
                            new PhpParser\Node\Expr\StaticCall(
                                new PhpParser\Node\Name(['parent']),
                                '__construct',
                                $fake_constructor_stmt_args,
                                [
                                    'line' => $this->class->extends->getLine(),
                                    'startFilePos' => $this->class->extends->getAttribute('startFilePos'),
                                    'endFilePos' => $this->class->extends->getAttribute('endFilePos'),
                                ]
                            ),
                        ];

                        $fake_stmt = new PhpParser\Node\Stmt\ClassMethod(
                            '__construct',
                            [
                                'type' => PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'params' => $fake_constructor_params,
                                'stmts' => $fake_constructor_stmts,
                            ]
                        );

                        $constructor_checker = $this->analyzeClassMethod(
                            $fake_stmt,
                            $this,
                            $class_context,
                            $global_context,
                            $update_docblocks
                        );
                    }
                }

                if ($constructor_checker) {
                    $method_context = clone $class_context;
                    $method_context->collect_initializations = true;
                    $method_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
                    $method_context->vars_possibly_in_scope['$this'] = true;

                    $constructor_checker->analyze($method_context, $global_context, true);

                    $all_properties_set_in_constructor = true;

                    foreach ($uninitialized_properties as $property_name => $property) {
                        if (!isset($method_context->vars_in_scope['$this->' . $property_name])) {
                            throw new \UnexpectedValueException('$this->' . $property_name . ' should be in scope');
                        }

                        $end_type = $method_context->vars_in_scope['$this->' . $property_name];

                        if (!$end_type->initialized) {
                            $all_properties_set_in_constructor = false;
                        }

                        if (!$end_type->initialized && $property->location) {
                            $property_id = $this->fq_class_name . '::$' . $property_name;

                            if (!$config->reportIssueInFile(
                                'PropertyNotSetInConstructor',
                                $property->location->file_path
                            ) && $this->class->extends
                            ) {
                                $error_location = new CodeLocation($this, $this->class->extends);
                            } else {
                                $error_location = $property->location;
                            }

                            if (IssueBuffer::accepts(
                                new PropertyNotSetInConstructor(
                                    'Property ' . $property_id . ' is not defined in constructor of ' .
                                        $this->fq_class_name . ' or in any private methods called in the constructor',
                                    $error_location
                                ),
                                $this->source->getSuppressedIssues()
                            )) {
                                continue;
                            }
                        }
                    }

                    $storage->all_properties_set_in_constructor = $all_properties_set_in_constructor;
                } elseif (!$storage->abstract) {
                    $first_uninitialized_property = array_shift($uninitialized_properties);

                    if ($first_uninitialized_property->location) {
                        if (!$config->reportIssueInFile(
                            'PropertyNotSetInConstructor',
                            $first_uninitialized_property->location->file_path
                        ) && $this->class->extends) {
                            $error_location = new CodeLocation($this, $this->class->extends);
                        } else {
                            $error_location = $first_uninitialized_property->location;
                        }

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
                        $this->source->getAliases()
                    );

                    if (!isset(self::$trait_checkers[strtolower($fq_trait_name)])) {
                        throw new \UnexpectedValueException('Expecting trait statements to exist');
                    }

                    $trait_checker = self::$trait_checkers[strtolower($fq_trait_name)];

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
     * @param  bool                         $update_docblocks
     *
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

        if ($stmt->name !== '__construct' && $config->reportIssueInFile('InvalidReturnType', $source->getFilePath())) {
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
     *
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
                        $this->source->getAliases()
                    );

                    if (!isset(self::$trait_checkers[strtolower($fq_trait_name)])) {
                        throw new \UnexpectedValueException('Expecting trait statements to exist');
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
     * @param   PhpParser\Node\Stmt\Property    $stmt
     *
     * @return  void
     */
    private function checkForMissingPropertyType(PhpParser\Node\Stmt\Property $stmt)
    {
        $comment = $stmt->getDocComment();
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
     * Check whether a class/interface exists
     *
     * @param  string       $fq_class_name
     * @param  FileChecker  $file_checker
     * @param  CodeLocation $code_location
     *
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
     *
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
     *
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

        if (in_array($fq_class_name, ['callable', 'iterable'], true)) {
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

        return true;
    }

    /**
     * Gets the fully-qualified class name from a Name object
     *
     * @param  PhpParser\Node\Name      $class_name
     * @param  StatementsSource         $source
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
     * @param  StatementsSource         $source
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
     * @return string
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
            MethodChecker::extractReflectionMethodInfo($reflection_method, $project_checker);

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
     *
     * @return void
     */
    protected static function registerInheritedMethods($fq_class_name, $parent_class)
    {
        $parent_storage = self::$storage[strtolower($parent_class)];
        $storage = self::$storage[strtolower($fq_class_name)];

        // register where they appear (can never be in a trait)
        foreach ($parent_storage->appearing_method_ids as $method_name => $appearing_method_id) {
            $parent_method_id = $parent_class . '::' . $method_name;

            $implemented_method_id = $fq_class_name . '::' . $method_name;

            $storage->appearing_method_ids[$method_name] = $appearing_method_id;
        }

        // register where they're declared
        foreach ($parent_storage->declaring_method_ids as $method_name => $declaring_method_id) {
            $parent_method_id = $parent_class . '::' . $method_name;

            $implemented_method_id = $fq_class_name . '::' . $method_name;

            $storage->declaring_method_ids[$method_name] = $declaring_method_id;

            MethodChecker::setOverriddenMethodId($implemented_method_id, $declaring_method_id);
        }
    }

    /**
     * @param string $fq_class_name
     * @param string $parent_class
     *
     * @return void
     */
    protected static function registerInheritedProperties($fq_class_name, $parent_class)
    {
        $parent_storage = self::$storage[strtolower($parent_class)];
        $storage = self::$storage[strtolower($fq_class_name)];

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
     * @param  string $class_name
     * @param  mixed  $visibility
     *
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
    public static function getConstantsForClass($class_name, $visibility)
    {
        // remove for PHP 7.1 support
        $visibility = ReflectionProperty::IS_PUBLIC;

        $class_name = strtolower($class_name);

        if (!isset(self::$storage[$class_name])) {
            throw new \UnexpectedValueException('$storage should not be null for ' . $class_name);
        }

        $storage = self::$storage[$class_name];

        if ($visibility === ReflectionProperty::IS_PUBLIC) {
            return $storage->public_class_constants;
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
    public static function setConstantType($class_name, $const_name, Type\Union $type, $visibility)
    {
        $storage = self::$storage[strtolower($class_name)];

        $storage->public_class_constants[$const_name] = $type;
    }

    /**
     * Whether or not a given property exists
     *
     * @param  string $property_id
     *
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
     *
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
     *
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
     *
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
     *
     * @return  string
     */
    protected function getMappedMethodName($method_name)
    {
        return $method_name;
    }

    /**
     * @param   string $file_path
     *
     * @return  array<string>
     */
    public static function getClassesForFile($file_path)
    {
        return isset(FileChecker::$storage[strtolower($file_path)])
            ? array_unique(FileChecker::$storage[strtolower($file_path)]->classes_in_file)
            : [];
    }

    /**
     * @param  string  $fq_class_name
     *
     * @return bool
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
     * @return void
     */
    public static function clearCache()
    {
        self::$file_classes = [];

        self::$trait_checkers = [];

        self::$class_checkers = [];

        self::$storage = [];
    }
}
