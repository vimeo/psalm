<?php
namespace Psalm\Checker;

use PhpParser;
use Psalm\Checker\FunctionLike\ReturnTypeChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedInterface;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\MissingConstructor;
use Psalm\Issue\MissingPropertyType;
use Psalm\Issue\OverriddenPropertyAccess;
use Psalm\Issue\PropertyNotSetInConstructor;
use Psalm\Issue\ReservedWord;
use Psalm\Issue\UndefinedTrait;
use Psalm\Issue\UnimplementedAbstractMethod;
use Psalm\Issue\UnimplementedInterfaceMethod;
use Psalm\IssueBuffer;
use Psalm\StatementsSource;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Storage\FunctionLikeParameter;
use Psalm\Type;

class ClassChecker extends ClassLikeChecker
{
    /**
     * @param PhpParser\Node\Stmt\Class_    $class
     * @param StatementsSource              $source
     * @param string|null                   $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\Class_ $class, StatementsSource $source, $fq_class_name)
    {
        if (!$fq_class_name) {
            $fq_class_name = self::getAnonymousClassName($class, $source->getFilePath());
        }

        parent::__construct($class, $source, $fq_class_name);

        if (!$this->class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \InvalidArgumentException('Bad');
        }

        if ($this->class->extends) {
            $this->parent_fq_class_name = self::getFQCLNFromNameObject(
                $this->class->extends,
                $this->source->getAliases()
            );
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\Class_ $class
     * @param  string                     $file_path
     *
     * @return string
     */
    public static function getAnonymousClassName(PhpParser\Node\Stmt\Class_ $class, $file_path)
    {
        return preg_replace('/[^A-Za-z0-9]/', '_', $file_path)
            . ':' . $class->getLine() . ':' . (int)$class->getAttribute('startFilePos');
    }

    /**
     * @param Context|null  $class_context
     * @param Context|null  $global_context
     *
     * @return null|false
     */
    public function analyze(
        Context $class_context = null,
        Context $global_context = null
    ) {
        $class = $this->class;

        if (!$class instanceof PhpParser\Node\Stmt\Class_) {
            throw new \LogicException('Something went badly wrong');
        }

        $fq_class_name = $class_context && $class_context->self ? $class_context->self : $this->fq_class_name;

        $storage = $this->storage;

        if ($class->name && preg_match(
            '/(^|\\\)(int|float|bool|string|void|null|false|true|resource|object|numeric|mixed)$/i',
            $fq_class_name
        )) {
            $class_name_parts = explode('\\', $fq_class_name);
            $class_name = array_pop($class_name_parts);

            if (IssueBuffer::accepts(
                new ReservedWord(
                    $class_name . ' is a reserved word',
                    new CodeLocation(
                        $this,
                        $class->name,
                        null,
                        true
                    ),
                    $class_name
                ),
                array_merge($storage->suppressed_issues, $this->source->getSuppressedIssues())
            )) {
                // fall through
            }

            return null;
        }

        $project_checker = $this->file_checker->project_checker;
        $codebase = $project_checker->codebase;

        $classlike_storage_provider = $project_checker->classlike_storage_provider;

        $parent_fq_class_name = $this->parent_fq_class_name;

        if ($class->extends) {
            if (!$parent_fq_class_name) {
                throw new \UnexpectedValueException('Parent class should be filled in for ' . $fq_class_name);
            }

            $parent_reference_location = new CodeLocation($this, $class->extends);

            if (self::checkFullyQualifiedClassLikeName(
                $this,
                $parent_fq_class_name,
                $parent_reference_location,
                array_merge($storage->suppressed_issues, $this->getSuppressedIssues()),
                false
            ) === false) {
                return false;
            }

            try {
                $parent_class_storage = $classlike_storage_provider->get($parent_fq_class_name);

                if ($parent_class_storage->deprecated) {
                    $code_location = new CodeLocation(
                        $this,
                        $class->extends,
                        $class_context ? $class_context->include_location : null,
                        true
                    );

                    if (IssueBuffer::accepts(
                        new DeprecatedClass(
                            $parent_fq_class_name . ' is marked deprecated',
                            $code_location
                        ),
                        array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                    )) {
                        // fall through
                    }
                }
            } catch (\InvalidArgumentException $e) {
                // do nothing
            }
        }

        foreach ($class->implements as $interface_name) {
            $fq_interface_name = self::getFQCLNFromNameObject(
                $interface_name,
                $this->source->getAliases()
            );

            $interface_location = new CodeLocation($this, $interface_name);

            if (self::checkFullyQualifiedClassLikeName(
                $this,
                $fq_interface_name,
                $interface_location,
                $this->getSuppressedIssues(),
                false
            ) === false) {
                return false;
            }
        }

        $class_interfaces = $storage->class_implements;

        if (!$class->isAbstract()) {
            foreach ($class_interfaces as $interface_name) {
                try {
                    $interface_storage = $classlike_storage_provider->get($interface_name);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

                $storage->public_class_constants += $interface_storage->public_class_constants;

                $code_location = new CodeLocation(
                    $this,
                    $class->name ? $class->name : $class,
                    $class_context ? $class_context->include_location : null,
                    true
                );

                if ($interface_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedInterface(
                            $interface_name . ' is marked deprecated',
                            $code_location
                        ),
                        array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                    )) {
                        // fall through
                    }
                }

                foreach ($interface_storage->methods as $method_name => $interface_method_storage) {
                    if ($interface_method_storage->visibility === self::VISIBILITY_PUBLIC) {
                        $implementer_declaring_method_id = $codebase->methods->getDeclaringMethodId(
                            $this->fq_class_name . '::' . $method_name
                        );

                        $implementer_fq_class_name = null;

                        if ($implementer_declaring_method_id) {
                            list($implementer_fq_class_name) = explode('::', $implementer_declaring_method_id);
                        }

                        $implementer_classlike_storage = $implementer_fq_class_name
                            ? $classlike_storage_provider->get($implementer_fq_class_name)
                            : null;

                        $implementer_method_storage = $implementer_declaring_method_id
                            ? $codebase->methods->getStorage($implementer_declaring_method_id)
                            : null;

                        if (!$implementer_method_storage) {
                            if (IssueBuffer::accepts(
                                new UnimplementedInterfaceMethod(
                                    'Method ' . $method_name . ' is not defined on class ' .
                                    $storage->name,
                                    $code_location
                                ),
                                array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                            )) {
                                return false;
                            }

                            return null;
                        }

                        if ($implementer_method_storage->visibility !== self::VISIBILITY_PUBLIC) {
                            if (IssueBuffer::accepts(
                                new InaccessibleMethod(
                                    'Interface-defined method ' . $implementer_method_storage->cased_name
                                        . ' must be public in ' . $storage->name,
                                    $code_location
                                ),
                                array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                            )) {
                                return false;
                            }

                            return null;
                        }

                        MethodChecker::compareMethods(
                            $project_checker,
                            $implementer_classlike_storage ?: $storage,
                            $interface_storage,
                            $implementer_method_storage,
                            $interface_method_storage,
                            $code_location,
                            $implementer_method_storage->suppressed_issues,
                            false
                        );
                    }
                }
            }
        }

        if (!$class_context) {
            $class_context = new Context($this->fq_class_name);
            $class_context->collect_references = $codebase->collect_references;
            $class_context->parent = $parent_fq_class_name;
        }

        if ($this->leftover_stmts) {
            (new StatementsChecker($this))->analyze($this->leftover_stmts, $class_context);
        }

        if (!$storage->abstract) {
            foreach ($storage->declaring_method_ids as $declaring_method_id) {
                $method_storage = $codebase->methods->getStorage($declaring_method_id);

                list($declaring_class_name, $method_name) = explode('::', $declaring_method_id);

                if ($method_storage->abstract) {
                    if (IssueBuffer::accepts(
                        new UnimplementedAbstractMethod(
                            'Method ' . $method_name . ' is not defined on class ' .
                            $this->fq_class_name . ', defined abstract in ' . $declaring_class_name,
                            new CodeLocation(
                                $this,
                                $class->name ? $class->name : $class,
                                $class_context->include_location,
                                true
                            )
                        ),
                        array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                    )) {
                        return false;
                    }
                }
            }
        }

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $property_class_name = $codebase->properties->getDeclaringClassForProperty($appearing_property_id);
            $property_class_storage = $classlike_storage_provider->get((string)$property_class_name);

            $property_storage = $property_class_storage->properties[$property_name];

            if (isset($storage->overridden_property_ids[$property_name])) {
                foreach ($storage->overridden_property_ids[$property_name] as $overridden_property_id) {
                    list($guide_class_name) = explode('::$', $overridden_property_id);
                    $guide_class_storage = $classlike_storage_provider->get($guide_class_name);
                    $guide_property_storage = $guide_class_storage->properties[$property_name];

                    if ($property_storage->visibility > $guide_property_storage->visibility
                        && $property_storage->location
                    ) {
                        if (IssueBuffer::accepts(
                            new OverriddenPropertyAccess(
                                'Property ' . $guide_class_storage->name . '::$' . $property_name
                                    . ' has different access level than '
                                    . $storage->name . '::$' . $property_name,
                                $property_storage->location
                            )
                        )) {
                            return false;
                        }

                        return null;
                    }
                }
            }

            if ($property_storage->type) {
                $property_type = clone $property_storage->type;

                if (!$property_type->isMixed() &&
                    !$property_storage->has_default &&
                    !$property_type->isNullable()
                ) {
                    $property_type->initialized = false;
                }
            } else {
                $property_type = Type::getMixed();
            }

            $property_type_location = $property_storage->type_location;

            if ($property_type_location && !$property_type->isMixed()) {
                $fleshed_out_type = ExpressionChecker::fleshOutType(
                    $project_checker,
                    $property_type,
                    $this->fq_class_name,
                    $this->fq_class_name
                );
                $fleshed_out_type->check(
                    $this,
                    $property_type_location,
                    $this->getSuppressedIssues(),
                    [],
                    false
                );
            }

            if ($property_storage->is_static) {
                $property_id = $this->fq_class_name . '::$' . $property_name;

                $class_context->vars_in_scope[$property_id] = $property_type;
            } else {
                $class_context->vars_in_scope['$this->' . $property_name] = $property_type;
            }
        }

        $constructor_checker = null;
        $property_stmts = [];

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_checker = $this->analyzeClassMethod(
                    $stmt,
                    $storage,
                    $this,
                    $class_context,
                    $global_context
                );

                if ($stmt->name->name === '__construct') {
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

                    if (!$codebase->classlikes->hasFullyQualifiedTraitName($fq_trait_name)) {
                        if (IssueBuffer::accepts(
                            new UndefinedTrait(
                                'Trait ' . $fq_trait_name . ' does not exist',
                                new CodeLocation($this, $trait)
                            ),
                            array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                        )) {
                            return false;
                        }
                    } else {
                        if (!$codebase->traitHasCorrectCase($fq_trait_name)) {
                            if (IssueBuffer::accepts(
                                new UndefinedTrait(
                                    'Trait ' . $fq_trait_name . ' has wrong casing',
                                    new CodeLocation($this, $trait)
                                ),
                                array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                            )) {
                                return false;
                            }

                            continue;
                        }

                        $trait_file_checker = $project_checker->getFileCheckerForClassLike($fq_trait_name);
                        $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                        $trait_aliases = $codebase->classlikes->getTraitAliases($fq_trait_name);
                        $trait_checker = new TraitChecker(
                            $trait_node,
                            $trait_file_checker,
                            $fq_trait_name,
                            $trait_aliases
                        );

                        foreach ($trait_node->stmts as $trait_stmt) {
                            if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                                $trait_method_checker = $this->analyzeClassMethod(
                                    $trait_stmt,
                                    $storage,
                                    $trait_checker,
                                    $class_context,
                                    $global_context
                                );

                                if ($trait_stmt->name->name === '__construct') {
                                    $constructor_checker = $trait_method_checker;
                                }
                            }
                        }
                    }
                }

                $class_context->include_location = $previous_context_include_location;
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        $property_stmts[] = $stmt;
                        break;
                    }
                }
            }
        }

        $statements_checker = new StatementsChecker($this);
        $statements_checker->analyze($property_stmts, $class_context, null, $global_context, true);

        $config = Config::getInstance();

        if ($config->reportIssueInFile('PropertyNotSetInConstructor', $this->getFilePath())) {
            $uninitialized_variables = [];
            $uninitialized_properties = [];

            foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
                $property_class_name = $codebase->properties->getDeclaringClassForProperty($appearing_property_id);
                $property_class_storage = $classlike_storage_provider->get((string)$property_class_name);

                $property = $property_class_storage->properties[$property_name];

                $property_is_initialized = isset($property_class_storage->initialized_properties[$property_name]);

                if ($property->has_default || $property->is_static || !$property->type || $property_is_initialized) {
                    continue;
                }

                if ($property->type->isMixed() || $property->type->isNullable()) {
                    continue;
                }

                $constructor_class_storage = null;

                if (isset($storage->methods['__construct'])) {
                    $constructor_class_storage = $storage;
                } elseif (isset($property_class_storage->methods['__construct'])
                    && $property_class_storage !== $storage
                ) {
                    $constructor_class_storage = $property_class_storage;
                } elseif (!empty($property_class_storage->overridden_method_ids['__construct'])) {
                    list($construct_fqcln) =
                        explode('::', $property_class_storage->overridden_method_ids['__construct'][0]);
                    $constructor_class_storage = $classlike_storage_provider->get($construct_fqcln);
                }

                if ($constructor_class_storage
                    && $constructor_class_storage->all_properties_set_in_constructor
                    && $constructor_class_storage->methods['__construct']->visibility !== self::VISIBILITY_PRIVATE
                ) {
                    continue;
                }

                $uninitialized_variables[] = '$this->' . $property_name;
                $uninitialized_properties[$property_name] = $property;
            }

            if ($uninitialized_properties) {
                if (!$storage->abstract
                    && !$constructor_checker
                    && isset($storage->declaring_method_ids['__construct'])
                    && $class->extends
                ) {
                    list($construct_fqcln) = explode('::', $storage->declaring_method_ids['__construct']);

                    $constructor_class_storage = $classlike_storage_provider->get($construct_fqcln);

                    // ignore oldstyle constructors and classes without any declared properties
                    if ($constructor_class_storage->user_defined
                        && isset($constructor_class_storage->methods['__construct'])
                    ) {
                        $constructor_storage = $constructor_class_storage->methods['__construct'];

                        $fake_constructor_params = array_map(
                            /** @return PhpParser\Node\Param */
                            function (FunctionLikeParameter $param) {
                                $fake_param = (new PhpParser\Builder\Param($param->name));
                                if ($param->signature_type) {
                                    $fake_param->setTypehint((string)$param->signature_type);
                                }

                                return $fake_param->getNode();
                            },
                            $constructor_storage->params
                        );

                        $fake_constructor_stmt_args = array_map(
                            /** @return PhpParser\Node\Arg */
                            function (FunctionLikeParameter $param) {
                                return new PhpParser\Node\Arg(new PhpParser\Node\Expr\Variable($param->name));
                            },
                            $constructor_storage->params
                        );

                        $fake_constructor_stmts = [
                            new PhpParser\Node\Stmt\Expression(
                                new PhpParser\Node\Expr\StaticCall(
                                    new PhpParser\Node\Name(['parent']),
                                    new PhpParser\Node\Identifier('__construct'),
                                    $fake_constructor_stmt_args,
                                    [
                                        'line' => $class->extends->getLine(),
                                        'startFilePos' => $class->extends->getAttribute('startFilePos'),
                                        'endFilePos' => $class->extends->getAttribute('endFilePos'),
                                    ]
                                )
                            ),
                        ];

                        $fake_stmt = new PhpParser\Node\Stmt\ClassMethod(
                            new PhpParser\Node\Identifier('__construct'),
                            [
                                'type' => PhpParser\Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'params' => $fake_constructor_params,
                                'stmts' => $fake_constructor_stmts,
                            ]
                        );

                        $codebase->analyzer->disableMixedCounts();

                        $constructor_checker = $this->analyzeClassMethod(
                            $fake_stmt,
                            $storage,
                            $this,
                            $class_context,
                            $global_context
                        );

                        $codebase->analyzer->enableMixedCounts();
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
                            ) && $class->extends
                            ) {
                                $error_location = new CodeLocation($this, $class->extends);
                            } else {
                                $error_location = $property->location;
                            }

                            if (IssueBuffer::accepts(
                                new PropertyNotSetInConstructor(
                                    'Property ' . $property_id . ' is not defined in constructor of ' .
                                        $this->fq_class_name . ' or in any private methods called in the constructor',
                                    $error_location
                                ),
                                array_merge($this->source->getSuppressedIssues(), $storage->suppressed_issues)
                            )) {
                                continue;
                            }
                        }
                    }

                    $storage->all_properties_set_in_constructor = $all_properties_set_in_constructor;
                } elseif (!$storage->abstract) {
                    $first_uninitialized_property = array_shift($uninitialized_properties);

                    if ($first_uninitialized_property->location) {
                        if (IssueBuffer::accepts(
                            new MissingConstructor(
                                $fq_class_name . ' has an uninitialized variable ' . $uninitialized_variables[0] .
                                    ', but no constructor',
                                $first_uninitialized_property->location
                            ),
                            array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                        )) {
                            // fall through
                        }
                    }
                }
            }
        }

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Property) {
                $this->checkForMissingPropertyType($project_checker, $this, $stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    $trait_file_checker = $project_checker->getFileCheckerForClassLike($fq_trait_name);
                    $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                    $trait_aliases = $codebase->classlikes->getTraitAliases($fq_trait_name);
                    $trait_checker = new TraitChecker(
                        $trait_node,
                        $trait_file_checker,
                        $fq_trait_name,
                        $trait_aliases
                    );

                    foreach ($trait_node->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\Property) {
                            $this->checkForMissingPropertyType($project_checker, $trait_checker, $trait_stmt);
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
    private function checkForMissingPropertyType(
        ProjectChecker $project_checker,
        StatementsSource $source,
        PhpParser\Node\Stmt\Property $stmt
    ) {
        $comment = $stmt->getDocComment();

        if (!$comment || !$comment->getText()) {
            $fq_class_name = $source->getFQCLN();
            $property_name = $stmt->props[0]->name->name;

            $codebase = $project_checker->codebase;

            $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                $fq_class_name . '::$' . $property_name
            );

            if (!$declaring_property_class) {
                throw new \UnexpectedValueException(
                    'Cannot get declaring class for ' . $fq_class_name . '::$' . $property_name
                );
            }

            $fq_class_name = $declaring_property_class;

            $message = 'Property ' . $fq_class_name . '::$' . $property_name . ' does not have a declared type';

            $class_storage = $project_checker->classlike_storage_provider->get($fq_class_name);

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
                    new CodeLocation($source, $stmt)
                ),
                $this->source->getSuppressedIssues()
            )) {
                // fall through
            }
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\ClassMethod $stmt
     * @param  StatementsSource                $source
     * @param  Context                         $class_context
     * @param  Context|null                    $global_context
     *
     * @return MethodChecker|null
     */
    private function analyzeClassMethod(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        ClassLikeStorage $class_storage,
        StatementsSource $source,
        Context $class_context,
        Context $global_context = null
    ) {
        $config = Config::getInstance();

        $method_checker = new MethodChecker($stmt, $source);

        $actual_method_id = (string)$method_checker->getMethodId();

        $project_checker = $source->getFileChecker()->project_checker;
        $codebase = $project_checker->codebase;

        $analyzed_method_id = $actual_method_id;

        if ($class_context->self && $class_context->self !== $source->getFQCLN()) {
            $analyzed_method_id = (string)$method_checker->getMethodId($class_context->self);

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

            if ($actual_method_id !== $declaring_method_id) {
                // the method is an abstract trait method

                $implementer_method_storage = $method_checker->getFunctionLikeStorage();

                if (!$implementer_method_storage instanceof \Psalm\Storage\MethodStorage) {
                    throw new \LogicException('This should never happen');
                }

                if ($declaring_method_id && $implementer_method_storage->abstract) {
                    $classlike_storage_provider = $project_checker->classlike_storage_provider;
                    $appearing_storage = $classlike_storage_provider->get($class_context->self);
                    $declaring_method_storage = $codebase->methods->getStorage($declaring_method_id);

                    MethodChecker::compareMethods(
                        $project_checker,
                        $class_storage,
                        $appearing_storage,
                        $implementer_method_storage,
                        $declaring_method_storage,
                        new CodeLocation($source, $stmt),
                        $implementer_method_storage->suppressed_issues,
                        false
                    );
                }


                return;
            }
        }

        $method_checker->analyze(
            clone $class_context,
            $global_context ? clone $global_context : null
        );

        if ($stmt->name->name !== '__construct'
            && $config->reportIssueInFile('InvalidReturnType', $source->getFilePath())
        ) {
            $return_type_location = null;
            $secondary_return_type_location = null;

            $actual_method_storage = $codebase->methods->getStorage($actual_method_id);

            if (!$actual_method_storage->has_template_return_type) {
                if ($actual_method_id) {
                    $return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                        $actual_method_id,
                        $secondary_return_type_location
                    );
                }

                $self_class = $class_context->self;

                $return_type = $codebase->methods->getMethodReturnType($analyzed_method_id, $self_class);

                $overridden_method_ids = isset($class_storage->overridden_method_ids[strtolower($stmt->name->name)])
                    ? $class_storage->overridden_method_ids[strtolower($stmt->name->name)]
                    : [];

                if ($actual_method_storage->overridden_downstream) {
                    $overridden_method_ids[] = 'overridden::downstream';
                }

                if (!$return_type && isset($class_storage->interface_method_ids[strtolower($stmt->name->name)])) {
                    $interface_method_ids = $class_storage->interface_method_ids[strtolower($stmt->name->name)];

                    foreach ($interface_method_ids as $interface_method_id) {
                        list($interface_class) = explode('::', $interface_method_id);

                        $interface_return_type = $codebase->methods->getMethodReturnType(
                            $interface_method_id,
                            $interface_class
                        );

                        $interface_return_type_location = $codebase->methods->getMethodReturnTypeLocation(
                            $interface_method_id
                        );

                        ReturnTypeChecker::verifyReturnType(
                            $stmt,
                            $source,
                            $method_checker,
                            $interface_return_type,
                            $interface_class,
                            $interface_return_type_location,
                            [$analyzed_method_id]
                        );
                    }
                }

                ReturnTypeChecker::verifyReturnType(
                    $stmt,
                    $source,
                    $method_checker,
                    $return_type,
                    $self_class,
                    $return_type_location,
                    $overridden_method_ids
                );
            }
        }

        return $method_checker;
    }
}
