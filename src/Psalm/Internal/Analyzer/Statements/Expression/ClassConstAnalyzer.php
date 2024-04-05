<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\CircularReferenceException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Issue\AmbiguousConstantInheritance;
use Psalm\Issue\CircularReference;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedConstant;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidClassConstantType;
use Psalm\Issue\InvalidConstantAssignmentValue;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\LessSpecificClassConstantType;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\OverriddenFinalConstant;
use Psalm\Issue\OverriddenInterfaceConstant;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\ParseError;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Storage\ClassConstantStorage;
use Psalm\Storage\ClassLikeStorage;
use Psalm\Type;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use ReflectionProperty;

use function assert;
use function explode;
use function in_array;
use function strtolower;

/**
 * @internal
 */
final class ClassConstAnalyzer
{
    /**
     * @psalm-suppress ComplexMethod to be refactored. We should probably regroup the two big if about $stmt->class and
     * analyse the ::class int $stmt->name separately
     */
    public static function analyzeFetch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $first_part_lc = strtolower($stmt->class->getFirst());

            if ($first_part_lc === 'self' || $first_part_lc === 'static') {
                if (!$context->self) {
                    return !IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Cannot use ' . $first_part_lc . ' outside class context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                $fq_class_name = $context->self;
            } elseif ($first_part_lc === 'parent') {
                $fq_class_name = $statements_analyzer->getParentFQCLN();

                if ($fq_class_name === null) {
                    return !IssueBuffer::accepts(
                        new ParentNotFound(
                            'Cannot check property fetch on parent as this class does not extend another',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } else {
                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_analyzer->getAliases(),
                );

                if ($stmt->name instanceof PhpParser\Node\Identifier) {
                    if ((!$context->inside_class_exists || $stmt->name->name !== 'class')
                        && !isset($context->phantom_classes[strtolower($fq_class_name)])
                    ) {
                        if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                            $statements_analyzer,
                            $fq_class_name,
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                            $context->self,
                            $context->calling_method_id,
                            $statements_analyzer->getSuppressedIssues(),
                            new ClassLikeNameOptions(false, true),
                        ) === false) {
                            return true;
                        }
                    }
                }
            }

            $fq_class_name_lc = strtolower($fq_class_name);

            $moved_class = false;

            if ($codebase->alter_code
                && !in_array($stmt->class->getFirst(), ['parent', 'static'])
            ) {
                $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id,
                    false,
                    $stmt->class->getFirst() === 'self',
                );
            }

            if ($codebase->classlikes->classExists($fq_class_name)) {
                $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
                if ($codebase->classlikes->classExists($fq_class_name)) {
                    $const_class_storage = $codebase->classlike_storage_provider->get($fq_class_name);
                    $fq_class_name = $const_class_storage->name;

                    if ($const_class_storage->deprecated && $fq_class_name !== $context->self) {
                        IssueBuffer::maybeAdd(
                            new DeprecatedClass(
                                'Class ' . $fq_class_name . ' is deprecated',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $fq_class_name,
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                }

                if ($first_part_lc === 'static') {
                    $static_named_object = new TNamedObject($fq_class_name, true);

                    $statements_analyzer->node_data->setType(
                        $stmt,
                        new Union([
                            new TClassString($fq_class_name, $static_named_object),
                        ]),
                    );
                } else {
                    $statements_analyzer->node_data->setType($stmt, Type::getLiteralClassString($fq_class_name, true));
                }

                if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->class,
                        $fq_class_name,
                    );
                }

                return true;
            }

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classlikes->classOrInterfaceOrEnumExists($fq_class_name)) {
                return true;
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name,
                );
            }

            if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                if ($codebase->analysis_php_version_id < 8_03_00) {
                    IssueBuffer::maybeAdd(
                        new ParseError(
                            'Dynamically fetching class constants and enums requires PHP 8.3',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                $was_inside_general_use = $context->inside_general_use;

                $context->inside_general_use = true;

                $ret = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);

                $context->inside_general_use = $was_inside_general_use;

                return $ret;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $const_id,
                );
            }

            $const_class_storage = $codebase->classlike_storage_provider->get($fq_class_name);
            if ($const_class_storage->is_enum) {
                $case = $const_class_storage->enum_cases[(string)$stmt->name] ?? null;
                if ($case && $case->deprecated) {
                    IssueBuffer::maybeAdd(
                        new DeprecatedConstant(
                            "Enum Case $const_id is marked as deprecated",
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }

            if ($fq_class_name === $context->self
                || (
                    $statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer &&
                    $fq_class_name === $statements_analyzer->getSource()->getFQCLN()
                )
            ) {
                $class_visibility = ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self &&
                ($codebase->classlikes->classExtends($context->self, $fq_class_name)
                    || $codebase->classlikes->classExtends($fq_class_name, $context->self))
            ) {
                $class_visibility = ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = ReflectionProperty::IS_PUBLIC;
            }

            try {
                $class_constant_type = $codebase->classlikes->getClassConstantType(
                    $fq_class_name,
                    $stmt->name->name,
                    $class_visibility,
                    $statements_analyzer,
                    [],
                    $stmt->class->getFirst() === "static",
                );
            } catch (InvalidArgumentException $_) {
                return true;
            } catch (CircularReferenceException $e) {
                IssueBuffer::maybeAdd(
                    new CircularReference(
                        'Constant ' . $const_id . ' contains a circular reference',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return true;
            }

            if (!$class_constant_type) {
                if ($fq_class_name !== $context->self) {
                    $class_constant_type = $codebase->classlikes->getClassConstantType(
                        $fq_class_name,
                        $stmt->name->name,
                        ReflectionProperty::IS_PRIVATE,
                        $statements_analyzer,
                    );
                }

                if ($class_constant_type) {
                    IssueBuffer::maybeAdd(
                        new InaccessibleClassConstant(
                            'Constant ' . $const_id . ' is not visible in this context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } elseif ($context->check_consts) {
                    IssueBuffer::maybeAdd(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                return true;
            }

            if ($context->calling_method_id) {
                $codebase->file_reference_provider->addMethodReferenceToClassMember(
                    $context->calling_method_id,
                    $fq_class_name_lc . '::' . $stmt->name->name,
                    false,
                );
            }

            $declaring_const_id = $fq_class_name_lc . '::' . $stmt->name->name;

            if ($codebase->alter_code && !$moved_class) {
                foreach ($codebase->class_constant_transforms as $original_pattern => $transformation) {
                    if ($declaring_const_id === $original_pattern) {
                        [$new_fq_class_name, $new_const_name] = explode('::', $transformation);

                        $file_manipulations = [];

                        if (strtolower($new_fq_class_name) !== $fq_class_name_lc) {
                            $file_manipulations[] = new FileManipulation(
                                (int) $stmt->class->getAttribute('startFilePos'),
                                (int) $stmt->class->getAttribute('endFilePos') + 1,
                                Type::getStringFromFQCLN(
                                    $new_fq_class_name,
                                    $statements_analyzer->getNamespace(),
                                    $statements_analyzer->getAliasedClassesFlipped(),
                                    null,
                                ),
                            );
                        }

                        $file_manipulations[] = new FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_const_name,
                        );

                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }
            }

            if ($context->self
                && !$context->collect_initializations
                && !$context->collect_mutations
                && !NamespaceAnalyzer::isWithinAny($context->self, $const_class_storage->internal)
            ) {
                IssueBuffer::maybeAdd(
                    new InternalClass(
                        $fq_class_name . ' is internal to '
                            . InternalClass::listToPhrase($const_class_storage->internal)
                            . ' but called from ' . $context->self,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($const_class_storage->deprecated && $fq_class_name !== $context->self) {
                IssueBuffer::maybeAdd(
                    new DeprecatedClass(
                        'Class ' . $fq_class_name . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } elseif (isset($const_class_storage->constants[$stmt->name->name])
                && $const_class_storage->constants[$stmt->name->name]->deprecated
            ) {
                IssueBuffer::maybeAdd(
                    new DeprecatedConstant(
                        'Constant ' . $const_id . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($first_part_lc !== 'static' || $const_class_storage->final || $class_constant_type->from_docblock
                || (isset($const_class_storage->constants[$stmt->name->name])
                    && $const_class_storage->constants[$stmt->name->name]->final
                )
            ) {
                $stmt_type = $class_constant_type;

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
                $context->vars_in_scope[$const_id] = $stmt_type;
            }

            return true;
        }

        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context) === false) {
            $context->inside_general_use = $was_inside_general_use;

            return false;
        }

        $context->inside_general_use = $was_inside_general_use;

        $lhs_type = $statements_analyzer->node_data->getType($stmt->class);

        if ($lhs_type === null) {
            return true;
        }

        if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
            $class_string_types = [];

            $has_mixed_or_object = false;

            foreach ($lhs_type->getAtomicTypes() as $lhs_atomic_type) {
                if ($lhs_atomic_type instanceof TNamedObject) {
                    $class_string_types[] = new TClassString(
                        $lhs_atomic_type->value,
                        $lhs_atomic_type,
                    );
                } elseif ($lhs_atomic_type instanceof TTemplateParam
                    && $lhs_atomic_type->as->isSingle()) {
                    $as_atomic_type = $lhs_atomic_type->as->getSingleAtomic();

                    if ($as_atomic_type instanceof TObject) {
                        $class_string_types[] = new TTemplateParamClass(
                            $lhs_atomic_type->param_name,
                            'object',
                            null,
                            $lhs_atomic_type->defining_class,
                        );
                    } elseif ($as_atomic_type instanceof TNamedObject) {
                        $class_string_types[] = new TTemplateParamClass(
                            $lhs_atomic_type->param_name,
                            $as_atomic_type->value,
                            $as_atomic_type,
                            $lhs_atomic_type->defining_class,
                        );
                    }
                } elseif ($lhs_atomic_type instanceof TObject
                    || $lhs_atomic_type instanceof TMixed
                ) {
                    $has_mixed_or_object = true;
                }
            }

            if ($has_mixed_or_object) {
                $statements_analyzer->node_data->setType($stmt, new Union([new TClassString()]));
            } elseif ($class_string_types) {
                $statements_analyzer->node_data->setType($stmt, new Union($class_string_types));
            }

            return true;
        }

        if ($stmt->class instanceof PhpParser\Node\Expr\Variable) {
            $fq_class_name = null;
            $lhs_type_definite_class = null;
            if ($lhs_type->isSingle()) {
                $atomic_type = $lhs_type->getSingleAtomic();
                if ($atomic_type instanceof TNamedObject) {
                    $fq_class_name = $atomic_type->value;
                    $lhs_type_definite_class = $atomic_type->definite_class;
                } elseif ($atomic_type instanceof TLiteralClassString) {
                    $fq_class_name = $atomic_type->value;
                    $lhs_type_definite_class = $atomic_type->definite_class;
                } elseif ($atomic_type instanceof TString
                    && !$atomic_type instanceof TClassString
                    && !$codebase->config->allow_string_standin_for_class
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidStringClass(
                            'String cannot be used as a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }

            if ($fq_class_name === null || $lhs_type_definite_class === null) {
                return true;
            }

            if ($codebase->classlikes->classExists($fq_class_name)) {
                $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
            }

            $moved_class = false;

            if ($codebase->alter_code) {
                $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id,
                );
            }

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classlikes->classOrInterfaceOrEnumExists($fq_class_name)) {
                return true;
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name,
                );
            }

            if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                return true;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $const_id,
                );
            }

            $const_class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            if ($fq_class_name === $context->self
                || (
                    $statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer &&
                    $fq_class_name === $statements_analyzer->getSource()->getFQCLN()
                )
            ) {
                $class_visibility = ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self &&
                ($codebase->classlikes->classExtends($context->self, $fq_class_name)
                    || $codebase->classlikes->classExtends($fq_class_name, $context->self))
            ) {
                $class_visibility = ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = ReflectionProperty::IS_PUBLIC;
            }

            try {
                $class_constant_type = $codebase->classlikes->getClassConstantType(
                    $fq_class_name,
                    $stmt->name->name,
                    $class_visibility,
                    $statements_analyzer,
                );
            } catch (InvalidArgumentException $_) {
                return true;
            } catch (CircularReferenceException $e) {
                IssueBuffer::maybeAdd(
                    new CircularReference(
                        'Constant ' . $const_id . ' contains a circular reference',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return true;
            }

            if (!$class_constant_type) {
                if ($fq_class_name !== $context->self) {
                    $class_constant_type = $codebase->classlikes->getClassConstantType(
                        $fq_class_name,
                        $stmt->name->name,
                        ReflectionProperty::IS_PRIVATE,
                        $statements_analyzer,
                    );
                }

                if ($class_constant_type) {
                    IssueBuffer::maybeAdd(
                        new InaccessibleClassConstant(
                            'Constant ' . $const_id . ' is not visible in this context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } elseif ($context->check_consts) {
                    IssueBuffer::maybeAdd(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                return true;
            }

            if ($context->calling_method_id) {
                $codebase->file_reference_provider->addMethodReferenceToClassMember(
                    $context->calling_method_id,
                    strtolower($fq_class_name) . '::' . $stmt->name->name,
                    false,
                );
            }

            $declaring_const_id = strtolower($fq_class_name) . '::' . $stmt->name->name;

            if ($codebase->alter_code && !$moved_class) {
                foreach ($codebase->class_constant_transforms as $original_pattern => $transformation) {
                    if ($declaring_const_id === $original_pattern) {
                        [, $new_const_name] = explode('::', $transformation);

                        $file_manipulations = [];

                        $file_manipulations[] = new FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_const_name,
                        );

                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }
            }

            if ($context->self
                && !$context->collect_initializations
                && !$context->collect_mutations
                && !NamespaceAnalyzer::isWithinAny($context->self, $const_class_storage->internal)
            ) {
                IssueBuffer::maybeAdd(
                    new InternalClass(
                        $fq_class_name . ' is internal to '
                            . InternalClass::listToPhrase($const_class_storage->internal)
                            . ' but called from ' . $context->self,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($const_class_storage->deprecated && $fq_class_name !== $context->self) {
                IssueBuffer::maybeAdd(
                    new DeprecatedClass(
                        'Class ' . $fq_class_name . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name,
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } elseif (isset($const_class_storage->constants[$stmt->name->name])
                && $const_class_storage->constants[$stmt->name->name]->deprecated
            ) {
                IssueBuffer::maybeAdd(
                    new DeprecatedConstant(
                        'Constant ' . $const_id . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($const_class_storage->final || $lhs_type_definite_class === true) {
                $stmt_type = $class_constant_type;

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
                $context->vars_in_scope[$const_id] = $stmt_type;
            }

            return true;
        }

        return true;
    }

    public static function analyzeAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\ClassConst $stmt,
        Context $context
    ): void {
        assert($context->self !== null);
        $class_storage = $statements_analyzer->getCodebase()->classlike_storage_provider->get($context->self);

        foreach ($stmt->consts as $const) {
            ExpressionAnalyzer::analyze($statements_analyzer, $const->value, $context);
            $const_storage = $class_storage->constants[$const->name->name];

            // Check assigned type matches docblock type
            if ($assigned_type = $statements_analyzer->node_data->getType($const->value)) {
                $const_storage_type = $const_storage->type;

                if ($const_storage_type !== null
                    && $const_storage->stmt_location !== null
                    && $assigned_type !== $const_storage_type
                    // Check if this type was defined via a dockblock or type hint otherwise the inferred type
                    // should always match the assigned type and we don't even need to do additional checks
                    // There is an issue with constants over a certain length where additional values
                    // are added to fallback_params in the assigned_type but not in const_storage_type
                    // which causes a false flag for this error to appear. Usually happens with arrays
                    && ($const_storage_type->from_docblock || $const_storage_type->from_property)
                    && !UnionTypeComparator::isContainedBy(
                        $statements_analyzer->getCodebase(),
                        $assigned_type,
                        $const_storage_type,
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidConstantAssignmentValue(
                            "{$class_storage->name}::{$const->name->name} with declared type "
                            . "{$const_storage_type->getId()} cannot be assigned type {$assigned_type->getId()}",
                            $const_storage->stmt_location,
                            "{$class_storage->name}::{$const->name->name}",
                        ),
                        $const_storage->suppressed_issues,
                    );
                }
            }
        }
    }

    public static function analyze(
        ClassLikeStorage $class_storage,
        Codebase $codebase
    ): void {
        foreach ($class_storage->constants as $const_name => $const_storage) {
            [$parent_classlike_storage, $parent_const_storage] = self::getOverriddenConstant(
                $class_storage,
                $const_storage,
                $const_name,
                $codebase,
            );

            $type_location = $const_storage->location ?? $const_storage->stmt_location;
            if ($type_location === null) {
                continue;
            }

            if ($parent_const_storage !== null) {
                assert($parent_classlike_storage !== null);

                // Check covariance
                if ($const_storage->type !== null
                    && $parent_const_storage->type !== null
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $const_storage->type,
                        $parent_const_storage->type,
                    )
                ) {
                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $parent_const_storage->type,
                        $const_storage->type,
                    )) {
                        // Contravariant
                        IssueBuffer::maybeAdd(
                            new LessSpecificClassConstantType(
                                "The type \"{$const_storage->type->getId()}\" for {$class_storage->name}::"
                                    . "{$const_name} is more general than the type "
                                    . "\"{$parent_const_storage->type->getId()}\" inherited from "
                                    . "{$parent_classlike_storage->name}::{$const_name}",
                                $type_location,
                                "{$class_storage->name}::{$const_name}",
                            ),
                            $const_storage->suppressed_issues,
                        );
                    } else {
                        // Completely different
                        IssueBuffer::maybeAdd(
                            new InvalidClassConstantType(
                                "The type \"{$const_storage->type->getId()}\" for {$class_storage->name}::"
                                    . "{$const_name} does not satisfy the type "
                                    . "\"{$parent_const_storage->type->getId()}\" inherited from "
                                    . "{$parent_classlike_storage->name}::{$const_name}",
                                $type_location,
                                "{$class_storage->name}::{$const_name}",
                            ),
                            $const_storage->suppressed_issues,
                        );
                    }
                }

                // Check overridden final
                if ($parent_const_storage->final && $parent_const_storage !== $const_storage) {
                    IssueBuffer::maybeAdd(
                        new OverriddenFinalConstant(
                            "{$const_name} cannot be overridden because it is marked as final in "
                                . $parent_classlike_storage->name,
                            $type_location,
                            "{$class_storage->name}::{$const_name}",
                        ),
                        $const_storage->suppressed_issues,
                    );
                }
            }

            if ($const_storage->stmt_location !== null) {
                // Check final in PHP < 8.1
                if ($codebase->analysis_php_version_id < 8_01_00 && $const_storage->final) {
                    IssueBuffer::maybeAdd(
                        new ParseError(
                            "Class constants cannot be marked final before PHP 8.1",
                            $const_storage->stmt_location,
                        ),
                        $const_storage->suppressed_issues,
                    );
                }
            }
        }
    }

    /**
     * Get the const storage from the parent or interface that this class is overriding.
     *
     * @return array{ClassLikeStorage, ClassConstantStorage}|null
     */
    private static function getOverriddenConstant(
        ClassLikeStorage $class_storage,
        ClassConstantStorage $const_storage,
        string $const_name,
        Codebase $codebase
    ): ?array {
        $parent_classlike_storage = $interface_const_storage = $parent_const_storage = null;
        $interface_overrides = [];
        foreach ($class_storage->class_implements ?: $class_storage->direct_interface_parents as $interface) {
            $interface_storage = $codebase->classlike_storage_provider->get($interface);
            $parent_const_storage = $interface_storage->constants[$const_name] ?? null;
            if ($parent_const_storage !== null) {
                if ($const_storage->location
                    && $const_storage !== $parent_const_storage
                    && $codebase->analysis_php_version_id < 8_01_00
                ) {
                    $interface_overrides[strtolower($interface)] = new OverriddenInterfaceConstant(
                        "{$class_storage->name}::{$const_name} cannot override constant from $interface",
                        $const_storage->location,
                        "{$class_storage->name}::{$const_name}",
                    );
                }
                if ($interface_const_storage !== null && $const_storage->location !== null) {
                    assert($parent_classlike_storage !== null);
                    if (!isset($parent_classlike_storage->parent_interfaces[strtolower($interface)])
                        && !isset($interface_storage->parent_interfaces[strtolower($parent_classlike_storage->name)])
                        && $interface_const_storage !== $parent_const_storage
                    ) {
                        IssueBuffer::maybeAdd(
                            new AmbiguousConstantInheritance(
                                "Ambiguous inheritance of {$class_storage->name}::{$const_name} from $interface and "
                                    . $parent_classlike_storage->name,
                                $const_storage->location,
                                "{$class_storage->name}::{$const_name}",
                            ),
                            $const_storage->suppressed_issues,
                        );
                    }
                }
                $interface_const_storage = $parent_const_storage;
                $parent_classlike_storage = $interface_storage;
            }
        }

        foreach ($class_storage->parent_classes as $parent_class) {
            $parent_class_storage = $codebase->classlike_storage_provider->get($parent_class);
            $parent_const_storage = $parent_class_storage->constants[$const_name] ?? null;
            if ($parent_const_storage !== null) {
                if ($const_storage->location !== null && $interface_const_storage !== null) {
                    assert($parent_classlike_storage !== null);
                    if (!isset($parent_class_storage->class_implements[strtolower($parent_classlike_storage->name)])) {
                        IssueBuffer::maybeAdd(
                            new AmbiguousConstantInheritance(
                                "Ambiguous inheritance of {$class_storage->name}::{$const_name} from "
                                    . "$parent_classlike_storage->name and $parent_class",
                                $const_storage->location,
                                "{$class_storage->name}::{$const_name}",
                            ),
                            $const_storage->suppressed_issues,
                        );
                    }
                }
                foreach ($interface_overrides as $interface_lc => $_) {
                    // If the parent is the one with the const that's overriding the interface const, and the parent
                    // doesn't implement the interface, it's just an AmbiguousConstantInheritance, not an
                    // OverriddenInterfaceConstant
                    if (!isset($parent_class_storage->class_implements[$interface_lc])
                        && $parent_const_storage === $const_storage
                    ) {
                        unset($interface_overrides[$interface_lc]);
                    }
                }
                $parent_classlike_storage = $parent_class_storage;
                break;
            }
        }

        if ($parent_const_storage === null) {
            $parent_const_storage = $interface_const_storage;
        }

        foreach ($interface_overrides as $_ => $issue) {
            IssueBuffer::maybeAdd(
                $issue,
                $const_storage->suppressed_issues,
            );
        }

        if ($parent_classlike_storage !== null) {
            assert($parent_const_storage !== null);
            return [$parent_classlike_storage, $parent_const_storage];
        }
        return null;
    }
}
