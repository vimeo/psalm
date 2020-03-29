<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\FileManipulation\FileManipulationBuffer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\CircularReference;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\DeprecatedConstant;
use Psalm\Issue\InaccessibleClassConstant;
use Psalm\Issue\NonStaticSelfCall;
use Psalm\Issue\ParentNotFound;
use Psalm\Issue\UndefinedConstant;
use Psalm\IssueBuffer;
use Psalm\Type;
use function strtolower;
use function explode;

/**
 * @internal
 */
class ClassConstFetchAnalyzer
{
    /**
     * @param   StatementsAnalyzer                   $statements_analyzer
     * @param   PhpParser\Node\Expr\ClassConstFetch $stmt
     * @param   Context                             $context
     *
     * @return  null|false
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ClassConstFetch $stmt,
        Context $context
    ) {
        $codebase = $statements_analyzer->getCodebase();

        if ($stmt->class instanceof PhpParser\Node\Name) {
            $first_part_lc = strtolower($stmt->class->parts[0]);

            if ($first_part_lc === 'self' || $first_part_lc === 'static') {
                if (!$context->self) {
                    if (IssueBuffer::accepts(
                        new NonStaticSelfCall(
                            'Cannot use ' . $first_part_lc . ' outside class context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }

                $fq_class_name = $context->self;
            } elseif ($first_part_lc === 'parent') {
                $fq_class_name = $statements_analyzer->getParentFQCLN();

                if ($fq_class_name === null) {
                    if (IssueBuffer::accepts(
                        new ParentNotFound(
                            'Cannot check property fetch on parent as this class does not extend another',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        return false;
                    }

                    return;
                }
            } else {
                $fq_class_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                    $stmt->class,
                    $statements_analyzer->getAliases()
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
                            false,
                            true
                        ) === false) {
                            return;
                        }
                    }
                }
            }

            $moved_class = false;

            if ($codebase->alter_code
                && !\in_array($stmt->class->parts[0], ['parent', 'static'])
            ) {
                $moved_class = $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id,
                    false,
                    $stmt->class->parts[0] === 'self'
                );
            }

            if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
                if ($codebase->classlikes->classExists($fq_class_name)) {
                    $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);
                    $class_const_storage = $codebase->classlike_storage_provider->get($fq_class_name);
                    $fq_class_name = $class_const_storage->name;

                    if ($class_const_storage->deprecated && $fq_class_name !== $context->self) {
                        if (IssueBuffer::accepts(
                            new DeprecatedClass(
                                'Class ' . $fq_class_name . ' is deprecated',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                                $fq_class_name
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    }
                }

                if ($first_part_lc === 'static') {
                    $static_named_object = new Type\Atomic\TNamedObject($fq_class_name);
                    $static_named_object->was_static = true;

                    $statements_analyzer->node_data->setType(
                        $stmt,
                        new Type\Union([
                            new Type\Atomic\TClassString($fq_class_name, $static_named_object)
                        ])
                    );
                } else {
                    $statements_analyzer->node_data->setType($stmt, Type::getLiteralClassString($fq_class_name));
                }

                if ($codebase->store_node_types
                    && !$context->collect_initializations
                    && !$context->collect_mutations
                ) {
                    $codebase->analyzer->addNodeReference(
                        $statements_analyzer->getFilePath(),
                        $stmt->class,
                        $fq_class_name
                    );
                }

                return null;
            }

            // if we're ignoring that the class doesn't exist, exit anyway
            if (!$codebase->classlikes->classOrInterfaceExists($fq_class_name)) {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());

                return null;
            }

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->class,
                    $fq_class_name
                );
            }

            if (!$stmt->name instanceof PhpParser\Node\Identifier) {
                return;
            }

            $const_id = $fq_class_name . '::' . $stmt->name;

            if ($codebase->store_node_types
                && !$context->collect_initializations
                && !$context->collect_mutations
            ) {
                $codebase->analyzer->addNodeReference(
                    $statements_analyzer->getFilePath(),
                    $stmt->name,
                    $const_id
                );
            }

            if ($fq_class_name === $context->self
                || (
                    $statements_analyzer->getSource()->getSource() instanceof TraitAnalyzer &&
                    $fq_class_name === $statements_analyzer->getSource()->getFQCLN()
                )
            ) {
                $class_visibility = \ReflectionProperty::IS_PRIVATE;
            } elseif ($context->self &&
                ($codebase->classlikes->classExtends($context->self, $fq_class_name)
                    || $codebase->classlikes->classExtends($fq_class_name, $context->self))
            ) {
                $class_visibility = \ReflectionProperty::IS_PROTECTED;
            } else {
                $class_visibility = \ReflectionProperty::IS_PUBLIC;
            }

            try {
                $class_constant_type = $codebase->classlikes->getConstantForClass(
                    $fq_class_name,
                    $stmt->name->name,
                    $class_visibility,
                    $statements_analyzer
                );
            } catch (\InvalidArgumentException $_) {
                return;
            } catch (\Psalm\Exception\CircularReferenceException $e) {
                if (IssueBuffer::accepts(
                    new CircularReference(
                        'Constant ' . $const_id . ' contains a circular reference',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }

            if (!$class_constant_type) {
                if ($fq_class_name !== $context->self) {
                    $class_constant_type = $codebase->classlikes->getConstantForClass(
                        $fq_class_name,
                        $stmt->name->name,
                        \ReflectionProperty::IS_PRIVATE,
                        $statements_analyzer
                    );
                }

                if ($class_constant_type) {
                    if (IssueBuffer::accepts(
                        new InaccessibleClassConstant(
                            'Constant ' . $const_id . ' is not visible in this context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                } elseif ($context->check_consts) {
                    if (IssueBuffer::accepts(
                        new UndefinedConstant(
                            'Constant ' . $const_id . ' is not defined',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // fall through
                    }
                }

                return;
            }

            if ($context->calling_method_id) {
                $codebase->file_reference_provider->addMethodReferenceToClassMember(
                    $context->calling_method_id,
                    strtolower($fq_class_name) . '::' . $stmt->name->name
                );
            }

            $declaring_const_id = strtolower($fq_class_name) . '::' . $stmt->name->name;

            if ($codebase->alter_code && !$moved_class) {
                foreach ($codebase->class_constant_transforms as $original_pattern => $transformation) {
                    if ($declaring_const_id === $original_pattern) {
                        list($new_fq_class_name, $new_const_name) = explode('::', $transformation);

                        $file_manipulations = [];

                        if (strtolower($new_fq_class_name) !== strtolower($fq_class_name)) {
                            $file_manipulations[] = new \Psalm\FileManipulation(
                                (int) $stmt->class->getAttribute('startFilePos'),
                                (int) $stmt->class->getAttribute('endFilePos') + 1,
                                Type::getStringFromFQCLN(
                                    $new_fq_class_name,
                                    $statements_analyzer->getNamespace(),
                                    $statements_analyzer->getAliasedClassesFlipped(),
                                    null
                                )
                            );
                        }

                        $file_manipulations[] = new \Psalm\FileManipulation(
                            (int) $stmt->name->getAttribute('startFilePos'),
                            (int) $stmt->name->getAttribute('endFilePos') + 1,
                            $new_const_name
                        );

                        FileManipulationBuffer::add($statements_analyzer->getFilePath(), $file_manipulations);
                    }
                }
            }

            $class_const_storage = $codebase->classlike_storage_provider->get($fq_class_name);

            if ($class_const_storage->deprecated && $fq_class_name !== $context->self) {
                if (IssueBuffer::accepts(
                    new DeprecatedClass(
                        'Class ' . $fq_class_name . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        $fq_class_name
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            } elseif (isset($class_const_storage->deprecated_constants[$stmt->name->name])) {
                if (IssueBuffer::accepts(
                    new DeprecatedConstant(
                        'Constant ' . $const_id . ' is deprecated',
                        new CodeLocation($statements_analyzer->getSource(), $stmt)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            if ($first_part_lc !== 'static' || $class_const_storage->final) {
                $stmt_type = clone $class_constant_type;

                $statements_analyzer->node_data->setType($stmt, $stmt_type);
                $context->vars_in_scope[$const_id] = $stmt_type;
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            }

            return null;
        }

        if ($stmt->name instanceof PhpParser\Node\Identifier && $stmt->name->name === 'class') {
            ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context);
            $lhs_type = $statements_analyzer->node_data->getType($stmt->class);

            $class_string_types = [];

            $has_mixed_or_object = false;

            if ($lhs_type) {
                foreach ($lhs_type->getAtomicTypes() as $lhs_atomic_type) {
                    if ($lhs_atomic_type instanceof Type\Atomic\TNamedObject) {
                        $class_string_types[] = new Type\Atomic\TClassString(
                            $lhs_atomic_type->value,
                            clone $lhs_atomic_type
                        );
                    } elseif ($lhs_atomic_type instanceof Type\Atomic\TObject
                        || $lhs_atomic_type instanceof Type\Atomic\TMixed
                    ) {
                        $has_mixed_or_object = true;
                    }
                }
            }

            if ($has_mixed_or_object) {
                $statements_analyzer->node_data->setType($stmt, new Type\Union([new Type\Atomic\TClassString()]));
            } elseif ($class_string_types) {
                $statements_analyzer->node_data->setType($stmt, new Type\Union($class_string_types));
            } else {
                $statements_analyzer->node_data->setType($stmt, Type::getMixed());
            }

            return;
        }

        $statements_analyzer->node_data->setType($stmt, Type::getMixed());

        if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->class, $context) === false) {
            return false;
        }

        return null;
    }
}
