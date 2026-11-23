<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements\Expression;

use Attribute;
use InvalidArgumentException;
use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Exception\CircularReferenceException;
use Psalm\FileManipulation;
use Psalm\Internal\Analyzer\AttributesAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\PhpDeprecations;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\AmbiguousConstantInheritance;
use Psalm\Issue\CircularReference;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedConstant;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\InternalClass;
use Psalm\Issue\InvalidClassConstantType;
use Psalm\Issue\InvalidConstantAssignmentValue;
use Psalm\Issue\InvalidOverride;
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
use Psalm\Type\Atomic\TDependentGetClass;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;
use ReflectionProperty;

use function array_values;
use function assert;
use function explode;
use function in_array;
use function ltrim;
use function strtolower;

/**
 * @internal
 */
final class ClassConstAnalyzer
{
    /**
     * The type the constant has in the classes that use the trait the code is in, null outside a trait or where one of
     * the classes doesn't have the constant
     */
    private static function getConstantTypeOfTheClassesUsingTheTrait(
        StatementsAnalyzer $statements_analyzer,
        string $constant_name,
    ): ?Union {
        $trait_analyzer = $statements_analyzer->getSource()->getSource();

        if (!$trait_analyzer instanceof TraitAnalyzer) {
            return null;
        }

        $codebase = $statements_analyzer->getCodebase();
        $trait_lc = strtolower($trait_analyzer->getFQCLN());
        $types = [];

        foreach ($codebase->classlike_storage_provider->getAll() as $user_storage) {
            if (!isset($user_storage->used_traits[$trait_lc])) {
                continue;
            }

            $type = $codebase->classlikes->getClassConstantType(
                $user_storage->name,
                $constant_name,
                ReflectionProperty::IS_PRIVATE,
                $statements_analyzer,
            );

            if ($type === null) {
                return null;
            }

            $types[] = $type;
        }

        return $types !== [] ? Type::combineUnionTypeArray($types, $codebase) : null;
    }

    public static function analyzeFetch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();

        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

        if ($stmt->name instanceof PhpParser\Node\Expr && $codebase->analysis_php_version_id < 8_03_00) {
            IssueBuffer::maybeAdd(
                new ParseError(
                    'Dynamically fetching class constants and enums requires PHP 8.3',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        }

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

                $is_class_name = $stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class';

                if ((!$context->inside_class_exists || !$is_class_name)
                    && !isset($context->phantom_classes[strtolower($fq_class_name)])
                    // PHP allows the name of a class that is missing, such as in the message about it
                    && (!$is_class_name || !isset($context->negated_phantom_classes[strtolower($fq_class_name)]))
                ) {
                    if (ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                        $statements_analyzer,
                        $fq_class_name,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        $context,
                        $statements_analyzer->getSuppressedIssues(),
                        new ClassLikeNameOptions(
                            false,
                            true,
                            context: $context,
                            required_at_runtime: !$is_class_name,
                        ),
                    ) === false) {
                        return true;
                    }
                }
            }

            $moved_class = false;

            if ($codebase->alter_code
                && !in_array($stmt->class->getFirst(), ['parent', 'static'])
            ) {
                $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context,
                    false,
                    $stmt->class->getFirst() === 'self',
                );
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
                if ($codebase->classlikes->classExists($fq_class_name, null, $context)) {
                    $const_class_storage = $codebase->classlike_storage_provider->get(
                        $codebase->classlikes->getUnAliasedName($fq_class_name),
                    );
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
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->class,
                        $fq_class_name,
                    );
                }

                return true;
            }

            $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classlikes->classOrInterfaceOrEnumExists($fq_class_name, null, $context)) {
                return true;
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name,
                );
            }

            if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                $was_inside_general_use = $context->inside_general_use;

                $context->inside_general_use = true;

                $ret = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);

                $context->inside_general_use = $was_inside_general_use;

                return $ret && self::analyzeLiteralNamedFetch(
                    $statements_analyzer,
                    $stmt,
                    $stmt->name,
                    $context,
                    $fq_class_name,
                    $moved_class,
                    $first_part_lc === 'static',
                    $first_part_lc === 'static',
                );
            }

            return self::analyzeIdentifiedFetch(
                $statements_analyzer,
                $stmt,
                $stmt->name,
                $context,
                $fq_class_name,
                $moved_class,
                $first_part_lc === 'static',
            );
        }

        $was_inside_general_use = $context->inside_general_use;
        $context->inside_general_use = true;

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context) === false) {
            $context->inside_general_use = $was_inside_general_use;

            return false;
        }

        $context->inside_general_use = $was_inside_general_use;

        if ($stmt->name instanceof PhpParser\Node\Expr) {
            $context->inside_general_use = true;
            $name_analyzed = ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);
            $context->inside_general_use = $was_inside_general_use;

            if ($name_analyzed === false) {
                return false;
            }
        }

        $lhs_type = $statements_analyzer->node_data->getType($stmt->class);

        if ($lhs_type === null) {
            return true;
        }

        $lhs_type = TypeExpander::expandTemplateKeyOfAndValueOf($statements_analyzer->getCodebase(), $lhs_type);

        // ::class retrieves a class name; it is not a use of a string literal
        // instead of ::class. In particular, do not report a class-string made by Foo::class.
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

        // Check all union members, not just the single-type variable case below.
        if (!$codebase->config->allow_string_standin_for_class) {
            foreach ($lhs_type->getAtomicTypes() as $lhs_atomic_type) {
                if ($lhs_atomic_type instanceof TString
                    && !$lhs_atomic_type instanceof TClassString
                    && !$lhs_atomic_type instanceof TDependentGetClass
                    && !$lhs_atomic_type instanceof TLiteralClassString
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidStringClass(
                            'String cannot be used as a class',
                            new CodeLocation($statements_analyzer->getSource(), $stmt->class),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                    break;
                }
            }
        }

        if ($stmt->class instanceof PhpParser\Node\Expr\Variable) {
            $fq_class_name = null;
            $lhs_type_definite_class = null;
            $lhs_is_static = false;
            if ($lhs_type->isSingle()) {
                $atomic_type = $lhs_type->getSingleAtomic();
                if ($atomic_type instanceof TNamedObject || $atomic_type instanceof TLiteralClassString) {
                    $fq_class_name = $atomic_type->value;
                    $lhs_type_definite_class = $atomic_type->definite_class;
                    $lhs_is_static = $atomic_type instanceof TNamedObject && $atomic_type->is_static;
                } elseif ($atomic_type::class === TLiteralString::class
                    && $codebase->config->allow_string_standin_for_class
                ) {
                    $class_name = ltrim($atomic_type->value, '\\');
                    if ($codebase->classlike_storage_provider->has($class_name)) {
                        $fq_class_name = $class_name;
                        $lhs_type_definite_class = true;
                    }
                }
            }

            if ($fq_class_name === null || $lhs_type_definite_class === null) {
                return true;
            }

            $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

            $moved_class = false;

            if ($codebase->alter_code) {
                $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context,
                );
            }

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classlikes->classOrInterfaceOrEnumExists($fq_class_name, null, $context)) {
                return true;
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name,
                );
            }

            if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                return self::analyzeLiteralNamedFetch(
                    $statements_analyzer,
                    $stmt,
                    $stmt->name,
                    $context,
                    $fq_class_name,
                    $moved_class,
                    !$lhs_type_definite_class,
                    $lhs_is_static,
                );
            }

            return self::analyzeIdentifiedFetch(
                $statements_analyzer,
                $stmt,
                $stmt->name,
                $context,
                $fq_class_name,
                $moved_class,
                !$lhs_type_definite_class,
            );
        }

        return true;
    }

    /**
     * Analyses `Foo::{$name}` (PHP 8.3) like `Foo::NAME` when `$name` holds literal names
     *
     * @param bool $may_be_a_subclass whether the constant read may be the one of a subclass of $fq_class_name
     * @param bool $is_static whether the class is `static`, as in `static::{$name}` or `$this::{$name}`
     */
    private static function analyzeLiteralNamedFetch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        PhpParser\Node\Expr $name,
        Context $context,
        string $fq_class_name,
        bool $moved_class,
        bool $may_be_a_subclass,
        bool $is_static,
    ): bool {
        $name_type = $statements_analyzer->node_data->getType($name);

        if ($name_type === null || !$name_type->allStringLiterals()) {
            return true;
        }

        $const_types = [];

        foreach ($name_type->getAtomicTypes() as $atomic) {
            if (!$atomic instanceof TLiteralString) {
                return true;
            }

            // `Foo::{'class'}` is `Foo::class`
            if (strtolower($atomic->value) === 'class') {
                $const_types[] = new Union([
                    $may_be_a_subclass
                        ? new TClassString($fq_class_name, new TNamedObject($fq_class_name, $is_static))
                        : new TLiteralClassString($fq_class_name, true),
                ]);

                continue;
            }

            if ($atomic->value === '') {
                IssueBuffer::maybeAdd(
                    new UndefinedConstant(
                        'Constant ' . $fq_class_name . ':: is not defined',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );

                return true;
            }

            // a fetch of its own for each name, so that an undefined one leaves its fetch without a type
            $identifier = new PhpParser\Node\Identifier($atomic->value, $name->getAttributes());
            $fetch = new PhpParser\Node\Expr\ClassConstFetch($stmt->class, $identifier, $stmt->getAttributes());

            if (!self::analyzeIdentifiedFetch(
                $statements_analyzer,
                $fetch,
                $identifier,
                $context,
                $fq_class_name,
                $moved_class,
                $may_be_a_subclass,
            )) {
                return false;
            }

            $const_type = $statements_analyzer->node_data->getType($fetch);

            if ($const_type === null) {
                return true;
            }

            $const_types[] = $const_type;
        }

        $statements_analyzer->node_data->setType(
            $stmt,
            Type::combineUnionTypeArray($const_types, $statements_analyzer->getCodebase()),
        );

        return true;
    }

    /**
     * Analyses `Foo::NAME`, `static::NAME` or `$foo::NAME` once the class is known to exist
     *
     * @param bool $may_be_a_subclass whether the constant read may be the one of a subclass of $fq_class_name
     */
    private static function analyzeIdentifiedFetch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        PhpParser\Node\Identifier $name,
        Context $context,
        string $fq_class_name,
        bool $moved_class,
        bool $may_be_a_subclass,
    ): bool {
        $codebase = $statements_analyzer->getCodebase();
        $const_name = $name->name;
        $const_id = $fq_class_name . '::' . $const_name;
        $fq_class_name_lc = strtolower($fq_class_name);

        if ($codebase->store_node_types
            && !$context->collect_initializations
        ) {
            $codebase->analyzer->addNodeReference(
                $statements_analyzer->getFilePath(),
                $name,
                $const_id,
            );
        }

        $const_class_storage = $codebase->classlike_storage_provider->get($fq_class_name);
        $const_storage = $const_class_storage->constants[$const_name] ?? null;

        if ($const_class_storage->is_enum) {
            $case = $const_class_storage->enum_cases[$const_name] ?? null;
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

        $late_static_binding = $may_be_a_subclass
            && !$const_class_storage->final
            && !$const_storage?->final;

        try {
            $class_constant_type = $codebase->classlikes->getClassConstantType(
                $fq_class_name,
                $const_name,
                $class_visibility,
                $statements_analyzer,
                [],
                $late_static_binding,
            );
        } catch (InvalidArgumentException) {
            return true;
        } catch (CircularReferenceException) {
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
                    $const_name,
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
            $codebase->addReferenceToClassConstant(
                $fq_class_name_lc,
                $const_name,
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                $context,
            );
        }

        if ($codebase->alter_code && !$moved_class) {
            $declaring_const_id = $fq_class_name_lc . '::' . $const_name;

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
                        (int) $name->getAttribute('startFilePos'),
                        (int) $name->getAttribute('endFilePos') + 1,
                        $new_const_name,
                    );

                    FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                }
            }
        }

        $caller_identifier = $statements_analyzer->getFullyQualifiedFunctionMethodOrNamespaceName() ?? '';

        if (!$context->collect_initializations
            && !NamespaceAnalyzer::isWithinAny($caller_identifier, $const_class_storage->internal)
        ) {
            IssueBuffer::maybeAdd(
                new InternalClass(
                    $fq_class_name . ' is internal to '
                        . InternalClass::listToPhrase($const_class_storage->internal)
                        . ' but called from ' . ($caller_identifier ?: 'root namespace'),
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
        } elseif ($const_storage?->deprecated) {
            IssueBuffer::maybeAdd(
                new DeprecatedConstant(
                    'Constant ' . $const_id . ' is deprecated',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        } elseif (!$const_class_storage->user_defined) {
            $builtin_message = PhpDeprecations::getClassConstantMessage(
                [
                    $const_class_storage->name,
                    ...array_values($const_class_storage->parent_classes),
                    ...array_values($const_class_storage->class_implements),
                ],
                $const_name,
            );

            if ($builtin_message !== null) {
                IssueBuffer::maybeAdd(
                    new DeprecatedConstant(
                        $builtin_message,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        $class_keyword = $stmt->class instanceof PhpParser\Node\Name
            ? strtolower($stmt->class->getFirst())
            : null;

        // a trait is analysed for each class using it, and each class gives the constant its own value: what the
        // code of the trait can rely on is the values they give it together
        if ($class_keyword === 'self'
            || $class_keyword === 'static'
            || ($stmt->class instanceof PhpParser\Node\Expr\Variable
                && $stmt->class->name === 'this'
                && $fq_class_name === $context->self)
        ) {
            $class_constant_type = self::getConstantTypeOfTheClassesUsingTheTrait(
                $statements_analyzer,
                $const_name,
            ) ?? $class_constant_type;
        }

        if ($late_static_binding && !$class_constant_type->from_docblock) {
            return true;
        }

        $stmt_type = $class_constant_type;

        if ($class_keyword === 'static') {
            // a condition on `static::NAME` holds for the next `static::NAME`, not for `self::NAME`
            // (a subclass may override the constant)
            $static_const_id = 'static::' . $const_name;

            $stmt_type = $context->vars_in_scope[$static_const_id] ?? $stmt_type;
            $context->vars_in_scope[$static_const_id] = $stmt_type;

            if ($context->inside_conditional) {
                $context->cond_referenced_var_ids[$static_const_id] = true;
            }
        } elseif (!$late_static_binding) {
            $context->vars_in_scope[$const_id] = $stmt_type;
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }

    public static function analyzeAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\ClassConst $stmt,
        Context $context,
    ): void {
        assert($context->self !== null);
        $class_storage = $statements_analyzer->getCodebase()->classlike_storage_provider->get($context->self);

        if ($class_storage->has_visitor_issues) {
            return;
        }

        // the attributes are those of every constant of the statement
        $first_const = $stmt->consts[0] ?? null;

        if ($first_const) {
            $first_const_storage = $class_storage->constants[$first_const->name->name];

            AttributesAnalyzer::analyze(
                $statements_analyzer,
                $context,
                $first_const_storage,
                $stmt->attrGroups,
                Attribute::TARGET_CLASS_CONSTANT,
                $first_const_storage->suppressed_issues + $statements_analyzer->getSuppressedIssues(),
            );
        }

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
                        TypeExpander::expandUnion(
                            $statements_analyzer->getCodebase(),
                            $const_storage_type,
                            $class_storage->name,
                            $class_storage->name,
                            $class_storage->parent_class,
                            true,
                            false,
                            $class_storage->final,
                        ),
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
        Codebase $codebase,
    ): void {
        if ($codebase->analysis_php_version_id >= 8_06_00) {
            self::checkOverrideAttributes($class_storage, $codebase);
        }

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

                // without a docblock type, the type the parent constant declares natively is what overrides must keep
                $parent_type = $parent_const_storage->type_location === null && $parent_const_storage->signature_type
                    ? $parent_const_storage->signature_type
                    : $parent_const_storage->type;

                $reported_type = false;

                // Check covariance
                if ($const_storage->type !== null
                    && $parent_type !== null
                    && !UnionTypeComparator::isContainedBy(
                        $codebase,
                        $const_storage->type,
                        $parent_type,
                    )
                ) {
                    if (UnionTypeComparator::isContainedBy(
                        $codebase,
                        $parent_type,
                        $const_storage->type,
                    )) {
                        // Contravariant
                        IssueBuffer::maybeAdd(
                            new LessSpecificClassConstantType(
                                "The type \"{$const_storage->type->getId()}\" for {$class_storage->name}::"
                                    . "{$const_name} is more general than the type "
                                    . "\"{$parent_type->getId()}\" inherited from "
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
                                    . "\"{$parent_type->getId()}\" inherited from "
                                    . "{$parent_classlike_storage->name}::{$const_name}",
                                $type_location,
                                "{$class_storage->name}::{$const_name}",
                            ),
                            $const_storage->suppressed_issues,
                        );
                    }

                    $reported_type = true;
                }

                // PHP requires an override of a natively typed constant to declare a native type the parent's contains
                $parent_signature_type = $parent_const_storage->signature_type;
                if (!$reported_type
                    && $parent_signature_type !== null
                    && $parent_const_storage !== $const_storage
                    && ($const_storage->signature_type === null
                        || !UnionTypeComparator::isContainedBy(
                            $codebase,
                            $const_storage->signature_type,
                            $parent_signature_type,
                        ))
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidClassConstantType(
                            ($const_storage->signature_type === null
                                ? "{$class_storage->name}::{$const_name} must declare a native type contained in"
                                : "The native type \"{$const_storage->signature_type->getId()}\" of "
                                    . "{$class_storage->name}::{$const_name} is not contained in")
                                . " the native type \"{$parent_signature_type->getId()}\" of "
                                . "{$parent_classlike_storage->name}::{$const_name}",
                            $type_location,
                            "{$class_storage->name}::{$const_name}",
                        ),
                        $const_storage->suppressed_issues,
                    );
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
     * PHP 8.6 (RFC override_constants): #[\Override] on a constant or enum case needs a public or protected constant
     * of the same name in a parent class or an interface. A trait's own #[\Override] is not checked: the constant is
     * checked in the class using the trait, where Populator has copied it into the constants.
     */
    private static function checkOverrideAttributes(ClassLikeStorage $class_storage, Codebase $codebase): void
    {
        $constants = [];
        foreach ($class_storage->constants as $const_name => $const_storage) {
            if ($const_storage->attributes) {
                $constants[$const_name] = [$const_storage, $const_storage->attributes];
            }
        }

        foreach ($class_storage->enum_cases as $case_name => $case_storage) {
            if ($case_storage->attributes) {
                $constants[$case_name] = [null, $case_storage->attributes];
            }
        }

        foreach ($constants as $const_name => [$const_storage, $attributes]) {
            foreach ($attributes as $attribute) {
                if ($attribute->fq_class_name !== 'Override'
                    || self::overridesConstant($class_storage, $const_name, $const_storage, $codebase)
                ) {
                    continue;
                }

                IssueBuffer::maybeAdd(
                    new InvalidOverride(
                        ($const_storage === null ? 'Enum case ' : 'Class constant ')
                            . "{$class_storage->name}::{$const_name} does not match any parent constant",
                        $attribute->name_location,
                    ),
                    $const_storage->suppressed_issues ?? $class_storage->suppressed_issues,
                );
            }
        }
    }

    /**
     * A constant inherited as is (the very same storage) is not redeclared here, so it has nothing to check.
     *
     * @psalm-capabilities read-props
     */
    private static function overridesConstant(
        ClassLikeStorage $class_storage,
        string $const_name,
        ?ClassConstantStorage $const_storage,
        Codebase $codebase,
    ): bool {
        foreach ([
            ...$class_storage->parent_classes,
            ...$class_storage->class_implements,
            ...$class_storage->parent_interfaces,
        ] as $ancestor) {
            $inherited = $codebase->classlikes->getStorageFor($ancestor)?->constants[$const_name] ?? null;

            if ($inherited !== null
                && ($inherited === $const_storage || $inherited->visibility !== ClassLikeAnalyzer::VISIBILITY_PRIVATE)
            ) {
                return true;
            }
        }

        return false;
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
        Codebase $codebase,
    ): ?array {
        $parent_classlike_storage = $interface_const_storage = $parent_const_storage = null;
        $interface_overrides = [];
        foreach ($class_storage->class_implements ?: $class_storage->direct_interface_parents as $interface) {
            $interface_storage = $codebase->classlikes->getStorageFor($interface);
            $parent_const_storage = $interface_storage->constants[$const_name] ?? null;
            if ($interface_storage !== null && $parent_const_storage !== null) {
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
            $parent_class_storage = $codebase->classlikes->getStorageFor($parent_class);
            $parent_const_storage = $parent_class_storage->constants[$const_name] ?? null;
            if ($parent_class_storage !== null && $parent_const_storage !== null) {
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
