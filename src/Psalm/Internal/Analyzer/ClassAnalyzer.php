<?php
namespace Psalm\Internal\Analyzer;

use PhpParser;
use Psalm\Aliases;
use Psalm\Internal\Analyzer\FunctionLike\ReturnTypeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Config;
use Psalm\Context;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedInterface;
use Psalm\Issue\DeprecatedTrait;
use Psalm\Issue\InaccessibleMethod;
use Psalm\Issue\InternalClass;
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

/**
 * @internal
 */
class ClassAnalyzer extends ClassLikeAnalyzer
{
    /**
     * @param PhpParser\Node\Stmt\Class_    $class
     * @param SourceAnalyzer                $source
     * @param string|null                   $fq_class_name
     */
    public function __construct(PhpParser\Node\Stmt\Class_ $class, SourceAnalyzer $source, $fq_class_name)
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
            . '_' . $class->getLine() . '_' . (int)$class->getAttribute('startFilePos');
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

        if ($storage->has_visitor_issues) {
            return;
        }

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

        $project_analyzer = $this->file_analyzer->project_analyzer;
        $codebase = $this->getCodebase();

        $classlike_storage_provider = $codebase->classlike_storage_provider;

        $parent_fq_class_name = $this->parent_fq_class_name;

        if ($class->extends) {
            if (!$parent_fq_class_name) {
                throw new \UnexpectedValueException('Parent class should be filled in for ' . $fq_class_name);
            }

            $parent_reference_location = new CodeLocation($this, $class->extends);

            if (self::checkFullyQualifiedClassLikeName(
                $this->getSource(),
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

                if ($parent_class_storage->internal) {
                    $code_location = new CodeLocation(
                        $this,
                        $class->extends,
                        $class_context ? $class_context->include_location : null,
                        true
                    );

                    $self_root = preg_replace('/^([^\\\]+).*/', '$1', $fq_class_name);
                    $declaring_root = preg_replace('/^([^\\\]+).*/', '$1', $parent_fq_class_name);

                    if (strtolower($self_root) !== strtolower($declaring_root)) {
                        if (IssueBuffer::accepts(
                            new InternalClass(
                                $parent_fq_class_name . ' is marked internal',
                                $code_location
                            ),
                            array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                        )) {
                            // fall through
                        }
                    }
                }

                if ($codebase->server_mode && $fq_class_name) {
                    $codebase->analyzer->addNodeReference(
                        $this->getFilePath(),
                        $class->extends,
                        $parent_fq_class_name
                    );
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

            if ($codebase->server_mode && $fq_class_name) {
                $bounds = $interface_location->getSelectionBounds();

                $codebase->analyzer->addOffsetReference(
                    $this->getFilePath(),
                    $bounds[0],
                    $bounds[1],
                    $fq_interface_name
                );
            }
        }

        if ($storage->invalid_dependencies) {
            return;
        }

        $class_interfaces = $storage->class_implements;

        if (!$class->isAbstract()) {
            foreach ($class_interfaces as $interface_name) {
                try {
                    $interface_storage = $classlike_storage_provider->get($interface_name);
                } catch (\InvalidArgumentException $e) {
                    continue;
                }

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

                        $implementer_method_storage = null;
                        $implementer_classlike_storage = null;

                        if ($implementer_declaring_method_id) {
                            list($implementer_fq_class_name) = explode('::', $implementer_declaring_method_id);
                            $implementer_method_storage = $codebase->methods->getStorage(
                                $implementer_declaring_method_id
                            );
                            $implementer_classlike_storage = $classlike_storage_provider->get(
                                $implementer_fq_class_name
                            );
                        }

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

                        $implementer_appearing_method_id = $codebase->methods->getAppearingMethodId(
                            $this->fq_class_name . '::' . $method_name
                        );

                        $implementer_visibility = $implementer_method_storage->visibility;

                        if ($implementer_appearing_method_id
                            && $implementer_appearing_method_id !== $implementer_declaring_method_id
                        ) {
                            list($appearing_fq_class_name, $appearing_method_name) = explode(
                                '::',
                                $implementer_appearing_method_id
                            );

                            $appearing_class_storage = $classlike_storage_provider->get(
                                $appearing_fq_class_name
                            );

                            if (isset($appearing_class_storage->trait_visibility_map[$appearing_method_name])) {
                                $implementer_visibility
                                    = $appearing_class_storage->trait_visibility_map[$appearing_method_name];
                            }
                        }

                        if ($implementer_visibility !== self::VISIBILITY_PUBLIC) {
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

                        MethodAnalyzer::compareMethods(
                            $codebase,
                            $implementer_classlike_storage ?: $storage,
                            $interface_storage,
                            $implementer_method_storage,
                            $interface_method_storage,
                            $this->fq_class_name,
                            $implementer_visibility,
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

        if ($global_context) {
            $class_context->strict_types = $global_context->strict_types;
        }

        if ($this->leftover_stmts) {
            (new StatementsAnalyzer($this))->analyze($this->leftover_stmts, $class_context);
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
            $property_class_name = $codebase->properties->getDeclaringClassForProperty(
                $appearing_property_id
            );
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

            $fleshed_out_type = !$property_type->isMixed()
                ? ExpressionAnalyzer::fleshOutType(
                    $codebase,
                    $property_type,
                    $this->fq_class_name,
                    $this->fq_class_name
                )
                : $property_type;

            if ($property_type_location && !$fleshed_out_type->isMixed()) {
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

                $class_context->vars_in_scope[$property_id] = $fleshed_out_type;
            } else {
                $class_context->vars_in_scope['$this->' . $property_name] = $fleshed_out_type;
            }
        }

        $constructor_analyzer = null;
        $member_stmts = [];

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                $method_analyzer = $this->analyzeClassMethod(
                    $stmt,
                    $storage,
                    $this,
                    $class_context,
                    $global_context
                );

                if ($stmt->name->name === '__construct') {
                    $constructor_analyzer = $method_analyzer;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                if ($this->analyzeTraitUse(
                    $this->source->getAliases(),
                    $stmt,
                    $project_analyzer,
                    $storage,
                    $class_context,
                    $global_context,
                    $constructor_analyzer
                ) === false) {
                    return false;
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\Property) {
                foreach ($stmt->props as $prop) {
                    if ($prop->default) {
                        $member_stmts[] = $stmt;
                        break;
                    }
                }
            } elseif ($stmt instanceof PhpParser\Node\Stmt\ClassConst) {
                $member_stmts[] = $stmt;
            }
        }

        $statements_analyzer = new StatementsAnalyzer($this);
        $statements_analyzer->analyze($member_stmts, $class_context, $global_context, true);

        $config = Config::getInstance();

        $this->checkPropertyInitialization(
            $codebase,
            $config,
            $storage,
            $class_context,
            $global_context,
            $constructor_analyzer
        );

        foreach ($class->stmts as $stmt) {
            if ($stmt instanceof PhpParser\Node\Stmt\Property && !isset($stmt->type)) {
                $this->checkForMissingPropertyType($this, $stmt);
            } elseif ($stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                foreach ($stmt->traits as $trait) {
                    $fq_trait_name = self::getFQCLNFromNameObject(
                        $trait,
                        $this->source->getAliases()
                    );

                    $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name);
                    $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                    $trait_aliases = $codebase->classlikes->getTraitAliases($fq_trait_name);
                    $trait_analyzer = new TraitAnalyzer(
                        $trait_node,
                        $trait_file_analyzer,
                        $fq_trait_name,
                        $trait_aliases
                    );

                    foreach ($trait_node->stmts as $trait_stmt) {
                        if ($trait_stmt instanceof PhpParser\Node\Stmt\Property) {
                            $this->checkForMissingPropertyType($trait_analyzer, $trait_stmt);
                        }
                    }
                }
            }
        }

        foreach ($storage->pseudo_methods as $pseudo_method_name => $pseudo_method_storage) {
            $pseudo_method_id = $this->fq_class_name . '::' . $pseudo_method_name;

            $overridden_method_ids = $codebase->methods->getOverriddenMethodIds($pseudo_method_id);

            if ($overridden_method_ids
                && $pseudo_method_name !== '__construct'
                && $pseudo_method_storage->location
            ) {
                foreach ($overridden_method_ids as $overridden_method_id) {
                    $parent_method_storage = $codebase->methods->getStorage($overridden_method_id);

                    list($overridden_fq_class_name) = explode('::', $overridden_method_id);

                    $parent_storage = $classlike_storage_provider->get($overridden_fq_class_name);

                    MethodAnalyzer::compareMethods(
                        $codebase,
                        $storage,
                        $parent_storage,
                        $pseudo_method_storage,
                        $parent_method_storage,
                        $this->fq_class_name,
                        $pseudo_method_storage->visibility ?: 0,
                        $pseudo_method_storage->location,
                        $storage->suppressed_issues,
                        true,
                        false
                    );
                }
            }
        }

        $plugin_classes = $codebase->config->after_classlike_checks;

        if ($plugin_classes) {
            $file_manipulations = [];

            foreach ($plugin_classes as $plugin_fq_class_name) {
                if ($plugin_fq_class_name::afterStatementAnalysis(
                    $class,
                    $storage,
                    $this->getSource(),
                    $codebase,
                    $file_manipulations
                ) === false) {
                    return false;
                }
            }

            if ($file_manipulations) {
                \Psalm\Internal\FileManipulation\FileManipulationBuffer::add(
                    $this->getFilePath(),
                    $file_manipulations
                );
            }
        }
    }

    /**
     * @return void
     */
    private function checkPropertyInitialization(
        Codebase $codebase,
        Config $config,
        ClassLikeStorage $storage,
        Context $class_context,
        Context $global_context = null,
        MethodAnalyzer $constructor_analyzer = null
    ) {
        if (!$config->reportIssueInFile('PropertyNotSetInConstructor', $this->getFilePath())) {
            return;
        }

        if (!isset($storage->declaring_method_ids['__construct'])
            && !$config->reportIssueInFile('MissingConstructor', $this->getFilePath())
        ) {
            return;
        }

        $fq_class_name = $class_context->self ? $class_context->self : $this->fq_class_name;

        $included_file_path = $this->getFilePath();

        if ($class_context->include_location) {
            $included_file_path = $class_context->include_location->file_path;
        }

        $method_already_analyzed = $codebase->analyzer->isMethodAlreadyAnalyzed(
            $included_file_path,
            strtolower($fq_class_name) . '::__construct',
            true
        );

        if ($method_already_analyzed) {
            return;
        }

        /** @var PhpParser\Node\Stmt\Class_ */
        $class = $this->class;
        $classlike_storage_provider = $codebase->classlike_storage_provider;

        $constructor_appearing_fqcln = $fq_class_name;

        $uninitialized_variables = [];
        $uninitialized_properties = [];

        foreach ($storage->appearing_property_ids as $property_name => $appearing_property_id) {
            $property_class_name = (string) $codebase->properties->getDeclaringClassForProperty(
                $appearing_property_id
            );
            $property_class_storage = $classlike_storage_provider->get($property_class_name);

            $property = $property_class_storage->properties[$property_name];

            $property_is_initialized = isset($property_class_storage->initialized_properties[$property_name]);

            if ($property->is_static) {
                continue;
            }

            $codebase->file_reference_provider->addReferenceToClassMethod(
                strtolower($fq_class_name) . '::__construct',
                strtolower($property_class_name) . '::$' . $property_name
            );

            if ($property->has_default || !$property->type || $property_is_initialized) {
                continue;
            }

            if ($property->type->isMixed() || $property->type->isNullable()) {
                continue;
            }

            $uninitialized_variables[] = '$this->' . $property_name;
            $uninitialized_properties[$property_class_name . '::$' . $property_name] = $property;
        }

        if (!$uninitialized_properties) {
            return;
        }

        if (!$storage->abstract
            && !$constructor_analyzer
            && isset($storage->declaring_method_ids['__construct'])
            && $class->extends
        ) {
            list($constructor_declaring_fqcln) = explode('::', $storage->declaring_method_ids['__construct']);
            list($constructor_appearing_fqcln) = explode('::', $storage->appearing_method_ids['__construct']);

            $constructor_class_storage = $classlike_storage_provider->get($constructor_declaring_fqcln);

            // ignore oldstyle constructors and classes without any declared properties
            if ($constructor_class_storage->user_defined
                && !$constructor_class_storage->stubbed
                && isset($constructor_class_storage->methods['__construct'])
            ) {
                $constructor_storage = $constructor_class_storage->methods['__construct'];

                $fake_constructor_params = array_map(
                    /** @return PhpParser\Node\Param */
                    function (FunctionLikeParameter $param) {
                        $fake_param = (new PhpParser\Builder\Param($param->name));
                        if ($param->signature_type) {
                            /** @psalm-suppress DeprecatedMethod */
                            $fake_param->setTypeHint((string)$param->signature_type);
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

                $constructor_analyzer = $this->analyzeClassMethod(
                    $fake_stmt,
                    $storage,
                    $this,
                    $class_context,
                    $global_context,
                    true
                );

                $codebase->analyzer->enableMixedCounts();
            }
        }

        if ($constructor_analyzer) {
            $method_context = clone $class_context;
            $method_context->collect_initializations = true;
            $method_context->self = $fq_class_name;
            $method_context->vars_in_scope['$this'] = Type::parseString($fq_class_name);
            $method_context->vars_possibly_in_scope['$this'] = true;

            $constructor_analyzer->analyze($method_context, $global_context, true);

            foreach ($uninitialized_properties as $property_id => $property_storage) {
                list(,$property_name) = explode('::$', $property_id);
                if (!isset($method_context->vars_in_scope['$this->' . $property_name])) {
                    throw new \UnexpectedValueException('$this->' . $property_name . ' should be in scope');
                }

                $end_type = $method_context->vars_in_scope['$this->' . $property_name];

                $constructor_class_property_storage = $property_storage;

                if ($fq_class_name !== $constructor_appearing_fqcln) {
                    $a_class_storage = $classlike_storage_provider->get($constructor_appearing_fqcln);

                    if (!isset($a_class_storage->declaring_property_ids[$property_name])) {
                        $constructor_class_property_storage = null;
                    } else {
                        $declaring_property_class = $a_class_storage->declaring_property_ids[$property_name];
                        $constructor_class_property_storage = $classlike_storage_provider
                            ->get($declaring_property_class)
                            ->properties[$property_name];
                    }
                }

                if ($property_storage->location
                    && (!$end_type->initialized || $property_storage !== $constructor_class_property_storage)
                ) {
                    if (IssueBuffer::accepts(
                        new PropertyNotSetInConstructor(
                            'Property ' . $property_id . ' is not defined in constructor of ' .
                                $this->fq_class_name . ' or in any private methods called in the constructor',
                            $property_storage->location,
                            $property_id
                        ),
                        array_merge($this->source->getSuppressedIssues(), $storage->suppressed_issues)
                    )) {
                        continue;
                    }
                }
            }

            $codebase->analyzer->setAnalyzedMethod(
                $included_file_path,
                strtolower($fq_class_name) . '::__construct',
                true
            );

            return;
        }

        if (!$storage->abstract) {
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

    /**
     * @return false|null
     */
    private function analyzeTraitUse(
        Aliases $aliases,
        PhpParser\Node\Stmt\TraitUse $stmt,
        ProjectAnalyzer $project_analyzer,
        ClassLikeStorage $storage,
        Context $class_context,
        Context $global_context = null,
        MethodAnalyzer &$constructor_analyzer = null
    ) {
        $codebase = $this->getCodebase();

        $previous_context_include_location = $class_context->include_location;

        foreach ($stmt->traits as $trait_name) {
            $class_context->include_location = new CodeLocation($this, $trait_name, null, true);

            $fq_trait_name = self::getFQCLNFromNameObject(
                $trait_name,
                $aliases
            );

            if (!$codebase->classlikes->hasFullyQualifiedTraitName($fq_trait_name)) {
                if (IssueBuffer::accepts(
                    new UndefinedTrait(
                        'Trait ' . $fq_trait_name . ' does not exist',
                        new CodeLocation($this, $trait_name)
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
                            new CodeLocation($this, $trait_name)
                        ),
                        array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                    )) {
                        return false;
                    }

                    continue;
                }

                $trait_storage = $codebase->classlike_storage_provider->get($fq_trait_name);

                if ($trait_storage->deprecated) {
                    if (IssueBuffer::accepts(
                        new DeprecatedTrait(
                            'Trait ' . $fq_trait_name . ' is deprecated',
                            new CodeLocation($this, $trait_name)
                        ),
                        array_merge($storage->suppressed_issues, $this->getSuppressedIssues())
                    )) {
                        // fall through
                    }
                }

                $trait_file_analyzer = $project_analyzer->getFileAnalyzerForClassLike($fq_trait_name);
                $trait_node = $codebase->classlikes->getTraitNode($fq_trait_name);
                $trait_aliases = $codebase->classlikes->getTraitAliases($fq_trait_name);
                $trait_analyzer = new TraitAnalyzer(
                    $trait_node,
                    $trait_file_analyzer,
                    $fq_trait_name,
                    $trait_aliases
                );

                foreach ($trait_node->stmts as $trait_stmt) {
                    if ($trait_stmt instanceof PhpParser\Node\Stmt\ClassMethod) {
                        if ($trait_stmt->stmts) {
                            $traverser = new PhpParser\NodeTraverser;

                            $traverser->addVisitor(new \Psalm\Internal\Visitor\NodeCleanerVisitor());
                            $traverser->traverse($trait_stmt->stmts);
                        }

                        $trait_method_analyzer = $this->analyzeClassMethod(
                            $trait_stmt,
                            $storage,
                            $trait_analyzer,
                            $class_context,
                            $global_context
                        );

                        if ($trait_stmt->name->name === '__construct') {
                            $constructor_analyzer = $trait_method_analyzer;
                        }
                    } elseif ($trait_stmt instanceof PhpParser\Node\Stmt\TraitUse) {
                        if ($this->analyzeTraitUse(
                            $trait_aliases,
                            $trait_stmt,
                            $project_analyzer,
                            $storage,
                            $class_context,
                            $global_context,
                            $constructor_analyzer
                        ) === false) {
                            return false;
                        }
                    }
                }
            }
        }

        $class_context->include_location = $previous_context_include_location;
    }

    /**
     * @param   PhpParser\Node\Stmt\Property    $stmt
     *
     * @return  void
     */
    private function checkForMissingPropertyType(
        StatementsSource $source,
        PhpParser\Node\Stmt\Property $stmt
    ) {
        $comment = $stmt->getDocComment();

        if (!$comment || !$comment->getText()) {
            $fq_class_name = $source->getFQCLN();
            $property_name = $stmt->props[0]->name->name;

            $codebase = $this->getCodebase();

            $property_id = $fq_class_name . '::$' . $property_name;

            $declaring_property_class = $codebase->properties->getDeclaringClassForProperty(
                $property_id
            );

            if (!$declaring_property_class) {
                throw new \UnexpectedValueException(
                    'Cannot get declaring class for ' . $property_id
                );
            }

            $fq_class_name = $declaring_property_class;

            // gets inherited property type
            $class_property_type = $codebase->properties->getPropertyType($property_id);

            if ($class_property_type) {
                return;
            }

            $message = 'Property ' . $property_id . ' does not have a declared type';

            $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

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
                    new CodeLocation($source, $stmt->props[0]->name)
                ),
                $this->source->getSuppressedIssues()
            )) {
                // fall through
            }
        }
    }

    /**
     * @param  PhpParser\Node\Stmt\ClassMethod $stmt
     * @param  SourceAnalyzer                  $source
     * @param  Context                         $class_context
     * @param  Context|null                    $global_context
     * @param  bool                            $is_fake
     *
     * @return MethodAnalyzer|null
     */
    private function analyzeClassMethod(
        PhpParser\Node\Stmt\ClassMethod $stmt,
        ClassLikeStorage $class_storage,
        SourceAnalyzer $source,
        Context $class_context,
        Context $global_context = null,
        $is_fake = false
    ) {
        $config = Config::getInstance();

        $method_analyzer = new MethodAnalyzer($stmt, $source);

        $actual_method_id = (string)$method_analyzer->getMethodId();

        $project_analyzer = $source->getProjectAnalyzer();
        $codebase = $source->getCodebase();

        $analyzed_method_id = $actual_method_id;

        $classlike_storage_provider = $codebase->classlike_storage_provider;

        $included_file_path = $source->getFilePath();

        if ($class_context->include_location) {
            $included_file_path = $class_context->include_location->file_path;
        }

        if ($class_context->self && $class_context->self !== $source->getFQCLN()) {
            $analyzed_method_id = (string)$method_analyzer->getMethodId($class_context->self);

            $declaring_method_id = $codebase->methods->getDeclaringMethodId($analyzed_method_id);

            if ($actual_method_id !== $declaring_method_id) {
                // the method is an abstract trait method

                $implementer_method_storage = $method_analyzer->getFunctionLikeStorage();

                if (!$implementer_method_storage instanceof \Psalm\Storage\MethodStorage) {
                    throw new \LogicException('This should never happen');
                }

                if ($declaring_method_id && $implementer_method_storage->abstract) {
                    $appearing_storage = $classlike_storage_provider->get($class_context->self);
                    $declaring_method_storage = $codebase->methods->getStorage($declaring_method_id);

                    MethodAnalyzer::compareMethods(
                        $codebase,
                        $class_storage,
                        $appearing_storage,
                        $implementer_method_storage,
                        $declaring_method_storage,
                        $this->fq_class_name,
                        $implementer_method_storage->visibility,
                        new CodeLocation($source, $stmt),
                        $implementer_method_storage->suppressed_issues,
                        false
                    );
                }

                return;
            }
        }

        $trait_safe_method_id = strtolower($analyzed_method_id);

        if (strtolower($actual_method_id) !== $trait_safe_method_id) {
            $trait_safe_method_id .= '&' . strtolower($actual_method_id);
        }

        $method_already_analyzed = $codebase->analyzer->isMethodAlreadyAnalyzed(
            $included_file_path,
            $trait_safe_method_id
        );

        $start = (int)$stmt->getAttribute('startFilePos');
        $end = (int)$stmt->getAttribute('endFilePos');

        $comments = $stmt->getComments();

        if ($comments) {
            $start = $comments[0]->getFilePos();
        }

        if ($codebase->diff_methods
            && $method_already_analyzed
            && !$class_context->collect_initializations
            && !$class_context->collect_mutations
            && !$is_fake
        ) {
            if ($project_analyzer->debug_output) {
                echo 'Skipping analysis of pre-analyzed method ' . $analyzed_method_id . "\n";
            }

            $existing_issues = $codebase->analyzer->getExistingIssuesForFile(
                $source->getFilePath(),
                $start,
                $end
            );

            IssueBuffer::addIssues($existing_issues);

            return $method_analyzer;
        }

        $codebase->analyzer->removeExistingDataForFile(
            $source->getFilePath(),
            $start,
            $end
        );

        $method_context = clone $class_context;
        $method_context->collect_exceptions = $config->check_for_throws_docblock;

        $method_analyzer->analyze(
            $method_context,
            $global_context ? clone $global_context : null
        );

        if ($stmt->name->name !== '__construct'
            && $config->reportIssueInFile('InvalidReturnType', $source->getFilePath())
            && $class_context->self
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

                        ReturnTypeAnalyzer::verifyReturnType(
                            $stmt,
                            $source,
                            $method_analyzer,
                            $interface_return_type,
                            $interface_class,
                            $interface_return_type_location,
                            [$analyzed_method_id]
                        );
                    }
                }

                ReturnTypeAnalyzer::verifyReturnType(
                    $stmt,
                    $source,
                    $method_analyzer,
                    $return_type,
                    $self_class,
                    $return_type_location,
                    $overridden_method_ids
                );
            }
        }

        if (!$method_already_analyzed
            && !$class_context->collect_initializations
            && !$class_context->collect_mutations
            && !$is_fake
        ) {
            $codebase->analyzer->setAnalyzedMethod($included_file_path, $trait_safe_method_id);
        }

        return $method_analyzer;
    }
}
