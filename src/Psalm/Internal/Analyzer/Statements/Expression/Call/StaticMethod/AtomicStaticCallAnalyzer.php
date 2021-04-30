<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Call\StaticMethod;

use PhpParser;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\ClassLikeNameOptions;
use Psalm\Internal\Analyzer\MethodAnalyzer;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\CallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentsAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\Method\MethodVisibilityAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\AtomicPropertyFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\MethodIdentifier;
use Psalm\Issue\DeprecatedClass;
use Psalm\Issue\InvalidStringClass;
use Psalm\Issue\InternalClass;
use Psalm\Issue\MixedMethodCall;
use Psalm\Issue\UndefinedClass;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualArray;
use Psalm\Node\Expr\VirtualArrayItem;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\Expr\VirtualVariable;
use Psalm\Node\Scalar\VirtualString;
use Psalm\Node\VirtualArg;
use Psalm\Type;
use Psalm\Type\Atomic\TNamedObject;
use function count;
use function in_array;
use function strtolower;
use function array_map;
use function array_filter;

class AtomicStaticCallAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context,
        Type\Atomic $lhs_type_part,
        bool $ignore_nullable_issues,
        bool &$moved_call,
        bool &$has_mock,
        bool &$has_existing_method
    ) : void {
        $intersection_types = [];

        if ($lhs_type_part instanceof TNamedObject) {
            $fq_class_name = $lhs_type_part->value;

            if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($statements_analyzer, $stmt->class),
                !$context->collect_initializations
                    && !$context->collect_mutations
                    ? $context->self
                    : null,
                !$context->collect_initializations
                    && !$context->collect_mutations
                    ? $context->calling_method_id
                    : null,
                $statements_analyzer->getSuppressedIssues(),
                new ClassLikeNameOptions(
                    $stmt->class instanceof PhpParser\Node\Name
                        && count($stmt->class->parts) === 1
                        && in_array(strtolower($stmt->class->parts[0]), ['self', 'static'], true)
                )
            )) {
                return;
            }

            $intersection_types = $lhs_type_part->extra_types;
        } elseif ($lhs_type_part instanceof Type\Atomic\TClassString
            && $lhs_type_part->as_type
        ) {
            $fq_class_name = $lhs_type_part->as_type->value;

            if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($statements_analyzer, $stmt->class),
                $context->self,
                $context->calling_method_id,
                $statements_analyzer->getSuppressedIssues()
            )) {
                return;
            }

            $intersection_types = $lhs_type_part->as_type->extra_types;
        } elseif ($lhs_type_part instanceof Type\Atomic\TDependentGetClass
            && !$lhs_type_part->as_type->hasObject()
        ) {
            $fq_class_name = 'object';

            if ($lhs_type_part->as_type->hasObjectType()
                && $lhs_type_part->as_type->isSingle()
            ) {
                foreach ($lhs_type_part->as_type->getAtomicTypes() as $typeof_type_atomic) {
                    if ($typeof_type_atomic instanceof Type\Atomic\TNamedObject) {
                        $fq_class_name = $typeof_type_atomic->value;
                    }
                }
            }

            if ($fq_class_name === 'object') {
                return;
            }
        } elseif ($lhs_type_part instanceof Type\Atomic\TLiteralClassString) {
            $fq_class_name = $lhs_type_part->value;

            if (!ClassLikeAnalyzer::checkFullyQualifiedClassLikeName(
                $statements_analyzer,
                $fq_class_name,
                new CodeLocation($statements_analyzer, $stmt->class),
                $context->self,
                $context->calling_method_id,
                $statements_analyzer->getSuppressedIssues()
            )) {
                return;
            }
        } elseif ($lhs_type_part instanceof Type\Atomic\TTemplateParam
            && !$lhs_type_part->as->isMixed()
            && !$lhs_type_part->as->hasObject()
        ) {
            $fq_class_name = null;

            foreach ($lhs_type_part->as->getAtomicTypes() as $generic_param_type) {
                if (!$generic_param_type instanceof TNamedObject) {
                    return;
                }

                $fq_class_name = $generic_param_type->value;
                break;
            }

            if (!$fq_class_name) {
                if (IssueBuffer::accepts(
                    new UndefinedClass(
                        'Type ' . $lhs_type_part->as . ' cannot be called as a class',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                        (string) $lhs_type_part
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }

                return;
            }
        } else {
            self::handleNonObjectCall(
                $statements_analyzer,
                $stmt,
                $context,
                $lhs_type_part,
                $ignore_nullable_issues
            );

            return;
        }

        $codebase = $statements_analyzer->getCodebase();

        $fq_class_name = $codebase->classlikes->getUnAliasedName($fq_class_name);

        $is_mock = ExpressionAnalyzer::isMock($fq_class_name);

        $has_mock = $has_mock || $is_mock;

        if ($stmt->name instanceof PhpParser\Node\Identifier && !$is_mock) {
            self::handleNamedCall(
                $statements_analyzer,
                $stmt,
                $stmt->name,
                $context,
                $lhs_type_part,
                $intersection_types ?: [],
                $fq_class_name,
                $moved_call,
                $has_existing_method
            );
        } else {
            if ($stmt->name instanceof PhpParser\Node\Expr) {
                $was_inside_use = $context->inside_use;
                $context->inside_use = true;

                ExpressionAnalyzer::analyze($statements_analyzer, $stmt->name, $context);

                $context->inside_use = $was_inside_use;
            }

            if (!$context->ignore_variable_method) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($fq_class_name) . '::',
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                true,
                $context
            ) === false) {
                return;
            }
        }

        if ($codebase->alter_code
            && $fq_class_name
            && !$moved_call
            && $stmt->class instanceof PhpParser\Node\Name
            && !in_array($stmt->class->parts[0], ['parent', 'static'])
        ) {
            $codebase->classlikes->handleClassLikeReferenceInMigration(
                $codebase,
                $statements_analyzer,
                $stmt->class,
                $fq_class_name,
                $context->calling_method_id,
                false,
                $stmt->class->parts[0] === 'self'
            );
        }
    }

    private static function handleNamedCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        PhpParser\Node\Identifier $stmt_name,
        Context $context,
        Type\Atomic $lhs_type_part,
        array $intersection_types,
        string $fq_class_name,
        bool &$moved_call,
        bool &$has_existing_method
    ) : bool {
        $codebase = $statements_analyzer->getCodebase();

        $method_name_lc = strtolower($stmt_name->name);
        $method_id = new MethodIdentifier($fq_class_name, $method_name_lc);

        $cased_method_id = $fq_class_name . '::' . $stmt_name->name;

        if ($codebase->store_node_types
            && !$context->collect_initializations
            && !$context->collect_mutations
        ) {
            \Psalm\Internal\Analyzer\Statements\Expression\Call\ArgumentMapPopulator::recordArgumentPositions(
                $statements_analyzer,
                $stmt,
                $codebase,
                (string) $method_id
            );
        }

        $args = $stmt->args;

        if ($intersection_types
            && !$codebase->methods->methodExists($method_id)
        ) {
            foreach ($intersection_types as $intersection_type) {
                if (!$intersection_type instanceof TNamedObject) {
                    continue;
                }

                $intersection_method_id = new MethodIdentifier(
                    $intersection_type->value,
                    $method_name_lc
                );

                if ($codebase->methods->methodExists($intersection_method_id)) {
                    $method_id = $intersection_method_id;
                    $cased_method_id = $intersection_type->value . '::' . $stmt_name->name;
                    $fq_class_name = $intersection_type->value;
                    break;
                }
            }
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        $naive_method_exists = $codebase->methods->methodExists(
            $method_id,
            !$context->collect_initializations
                && !$context->collect_mutations
                ? $context->calling_method_id
                : null,
            $codebase->collect_locations
                ? new CodeLocation($statements_analyzer, $stmt_name)
                : null,
            $statements_analyzer,
            $statements_analyzer->getFilePath(),
            false
        );

        $fake_method_exists = false;

        if (!$naive_method_exists
            && $codebase->methods->existence_provider->has($fq_class_name)
        ) {
            $method_exists = $codebase->methods->existence_provider->doesMethodExist(
                $fq_class_name,
                $method_id->method_name,
                $statements_analyzer,
                null
            );

            if ($method_exists) {
                $fake_method_exists = true;
            }
        }

        if (!$naive_method_exists
            && $class_storage->mixin_declaring_fqcln
            && $class_storage->namedMixins
        ) {
            foreach ($class_storage->namedMixins as $mixin) {
                $new_method_id = new MethodIdentifier(
                    $mixin->value,
                    $method_name_lc
                );

                if ($codebase->methods->methodExists(
                    $new_method_id,
                    $context->calling_method_id,
                    $codebase->collect_locations
                        ? new CodeLocation($statements_analyzer, $stmt_name)
                        : null,
                    !$context->collect_initializations
                    && !$context->collect_mutations
                        ? $statements_analyzer
                        : null,
                    $statements_analyzer->getFilePath()
                )) {
                    $mixin_candidates = [];
                    foreach ($class_storage->templatedMixins as $mixin_candidate) {
                        $mixin_candidates[] = clone $mixin_candidate;
                    }

                    foreach ($class_storage->namedMixins as $mixin_candidate) {
                        $mixin_candidates[] = clone $mixin_candidate;
                    }

                    $mixin_candidates_no_generic = array_filter($mixin_candidates, function ($check): bool {
                        return !($check instanceof Type\Atomic\TGenericObject);
                    });

                    // $mixin_candidates_no_generic will only be empty when there are TGenericObject entries.
                    // In that case, Union will be initialized with an empty array but
                    // replaced with non-empty types in the following loop.
                    /** @psalm-suppress ArgumentTypeCoercion */
                    $mixin_candidate_type = new Type\Union($mixin_candidates_no_generic);

                    foreach ($mixin_candidates as $tGenericMixin) {
                        if (!($tGenericMixin instanceof Type\Atomic\TGenericObject)) {
                            continue;
                        }

                        $mixin_declaring_class_storage = $codebase->classlike_storage_provider->get(
                            $class_storage->mixin_declaring_fqcln
                        );

                        $new_mixin_candidate_type = AtomicPropertyFetchAnalyzer::localizePropertyType(
                            $codebase,
                            new Type\Union([$lhs_type_part]),
                            $tGenericMixin,
                            $class_storage,
                            $mixin_declaring_class_storage
                        );

                        foreach ($mixin_candidate_type->getAtomicTypes() as $type) {
                            $new_mixin_candidate_type->addType($type);
                        }

                        $mixin_candidate_type = $new_mixin_candidate_type;
                    }

                    $new_lhs_type = \Psalm\Internal\Type\TypeExpander::expandUnion(
                        $codebase,
                        $mixin_candidate_type,
                        $fq_class_name,
                        $fq_class_name,
                        $class_storage->parent_class,
                        true,
                        false,
                        $class_storage->final
                    );

                    $old_data_provider = $statements_analyzer->node_data;

                    $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                    $context->vars_in_scope['$tmp_mixin_var'] = $new_lhs_type;

                    $fake_method_call_expr = new VirtualMethodCall(
                        new VirtualVariable(
                            'tmp_mixin_var',
                            $stmt->class->getAttributes()
                        ),
                        $stmt_name,
                        $stmt->args,
                        $stmt->getAttributes()
                    );

                    if (MethodCallAnalyzer::analyze(
                        $statements_analyzer,
                        $fake_method_call_expr,
                        $context
                    ) === false) {
                        return false;
                    }

                    $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call_expr);

                    $statements_analyzer->node_data = $old_data_provider;

                    $statements_analyzer->node_data->setType($stmt, $fake_method_call_type ?: Type::getMixed());

                    return true;
                }
            }
        }

        $config = $codebase->config;

        if (!$naive_method_exists
            || !MethodAnalyzer::isMethodVisible(
                $method_id,
                $context,
                $statements_analyzer->getSource()
            )
            || $fake_method_exists
            || (isset($class_storage->pseudo_static_methods[$method_name_lc])
                && ($config->use_phpdoc_method_without_magic_or_parent || $class_storage->parent_class))
        ) {
            $callstatic_id = new MethodIdentifier(
                $fq_class_name,
                '__callstatic'
            );

            if ($codebase->methods->methodExists(
                $callstatic_id,
                $context->calling_method_id,
                $codebase->collect_locations
                    ? new CodeLocation($statements_analyzer, $stmt_name)
                    : null,
                !$context->collect_initializations
                    && !$context->collect_mutations
                    ? $statements_analyzer
                    : null,
                $statements_analyzer->getFilePath()
            )) {
                if ($codebase->methods->return_type_provider->has($fq_class_name)) {
                    $return_type_candidate = $codebase->methods->return_type_provider->getReturnType(
                        $statements_analyzer,
                        $method_id->fq_class_name,
                        $method_id->method_name,
                        $stmt->args,
                        $context,
                        new CodeLocation($statements_analyzer->getSource(), $stmt_name),
                        null,
                        null,
                        strtolower($stmt_name->name)
                    );

                    if ($return_type_candidate) {
                        CallAnalyzer::checkMethodArgs(
                            $method_id,
                            $stmt->args,
                            null,
                            $context,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                            $statements_analyzer
                        );

                        $statements_analyzer->node_data->setType($stmt, $return_type_candidate);

                        return true;
                    }
                }

                if (isset($class_storage->pseudo_static_methods[$method_name_lc])) {
                    $pseudo_method_storage = $class_storage->pseudo_static_methods[$method_name_lc];

                    if (self::checkPseudoMethod(
                        $statements_analyzer,
                        $stmt,
                        $method_id,
                        $fq_class_name,
                        $args,
                        $class_storage,
                        $pseudo_method_storage,
                        $context
                    ) === false
                    ) {
                        return false;
                    }

                    if ($pseudo_method_storage->return_type) {
                        return true;
                    }
                } else {
                    if (ArgumentsAnalyzer::analyze(
                        $statements_analyzer,
                        $args,
                        null,
                        null,
                        true,
                        $context
                    ) === false) {
                        return false;
                    }
                }

                $array_values = array_map(
                    function (PhpParser\Node\Arg $arg): PhpParser\Node\Expr\ArrayItem {
                        return new VirtualArrayItem(
                            $arg->value,
                            null,
                            false,
                            $arg->getAttributes()
                        );
                    },
                    $args
                );

                $args = [
                    new VirtualArg(
                        new VirtualString((string) $method_id, $stmt_name->getAttributes()),
                        false,
                        false,
                        $stmt_name->getAttributes()
                    ),
                    new VirtualArg(
                        new VirtualArray($array_values, $stmt->getAttributes()),
                        false,
                        false,
                        $stmt->getAttributes()
                    ),
                ];

                $method_id = new MethodIdentifier(
                    $fq_class_name,
                    '__callstatic'
                );
            } elseif (isset($class_storage->pseudo_static_methods[$method_name_lc])
                && ($config->use_phpdoc_method_without_magic_or_parent || $class_storage->parent_class)
            ) {
                $pseudo_method_storage = $class_storage->pseudo_static_methods[$method_name_lc];

                if (self::checkPseudoMethod(
                    $statements_analyzer,
                    $stmt,
                    $method_id,
                    $fq_class_name,
                    $args,
                    $class_storage,
                    $pseudo_method_storage,
                    $context
                ) === false
                ) {
                    return false;
                }

                if ($pseudo_method_storage->return_type) {
                    return true;
                }
            }

            if (!$context->check_methods) {
                if (ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->args,
                    null,
                    null,
                    true,
                    $context
                ) === false) {
                    return false;
                }

                return true;
            }
        }

        $does_method_exist = MethodAnalyzer::checkMethodExists(
            $codebase,
            $method_id,
            new CodeLocation($statements_analyzer, $stmt),
            $statements_analyzer->getSuppressedIssues(),
            $context->calling_method_id
        );

        if (!$does_method_exist) {
            if (ArgumentsAnalyzer::analyze(
                $statements_analyzer,
                $stmt->args,
                null,
                null,
                true,
                $context
            ) === false) {
                return false;
            }

            if ($codebase->alter_code && $fq_class_name && !$moved_call) {
                $codebase->classlikes->handleClassLikeReferenceInMigration(
                    $codebase,
                    $statements_analyzer,
                    $stmt->class,
                    $fq_class_name,
                    $context->calling_method_id
                );
            }

            return true;
        }

        $class_storage = $codebase->classlike_storage_provider->get($fq_class_name);

        if ($class_storage->deprecated && $fq_class_name !== $context->self) {
            if (IssueBuffer::accepts(
                new DeprecatedClass(
                    $fq_class_name . ' is marked deprecated',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $fq_class_name
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if ($context->self && ! NamespaceAnalyzer::isWithin($context->self, $class_storage->internal)) {
            if (IssueBuffer::accepts(
                new InternalClass(
                    $fq_class_name . ' is internal to ' . $class_storage->internal
                        . ' but called from ' . $context->self,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                    $fq_class_name
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }
        }

        if (MethodVisibilityAnalyzer::analyze(
            $method_id,
            $context,
            $statements_analyzer->getSource(),
            new CodeLocation($statements_analyzer, $stmt),
            $statements_analyzer->getSuppressedIssues()
        ) === false) {
            return false;
        }

        if ((!$stmt->class instanceof PhpParser\Node\Name
                || $stmt->class->parts[0] !== 'parent'
                || $statements_analyzer->isStatic())
            && (
                !$context->self
                || $statements_analyzer->isStatic()
                || !$codebase->classExtends($context->self, $fq_class_name)
            )
        ) {
            if (MethodAnalyzer::checkStatic(
                $method_id,
                ($stmt->class instanceof PhpParser\Node\Name
                    && strtolower($stmt->class->parts[0]) === 'self')
                    || $context->self === $fq_class_name,
                !$statements_analyzer->isStatic(),
                $codebase,
                new CodeLocation($statements_analyzer, $stmt),
                $statements_analyzer->getSuppressedIssues(),
                $is_dynamic_this_method
            ) === false) {
                // fall through
            }

            if ($is_dynamic_this_method) {
                $old_data_provider = $statements_analyzer->node_data;

                $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                $fake_method_call_expr = new VirtualMethodCall(
                    new VirtualVariable(
                        'this',
                        $stmt->class->getAttributes()
                    ),
                    $stmt_name,
                    $stmt->args,
                    $stmt->getAttributes()
                );

                if (MethodCallAnalyzer::analyze(
                    $statements_analyzer,
                    $fake_method_call_expr,
                    $context
                ) === false) {
                    return false;
                }

                $fake_method_call_type = $statements_analyzer->node_data->getType($fake_method_call_expr);

                $statements_analyzer->node_data = $old_data_provider;

                if ($fake_method_call_type) {
                    $statements_analyzer->node_data->setType($stmt, $fake_method_call_type);
                }

                return true;
            }
        }

        $has_existing_method = true;

        ExistingAtomicStaticCallAnalyzer::analyze(
            $statements_analyzer,
            $stmt,
            $stmt_name,
            $args,
            $context,
            $lhs_type_part,
            $method_id,
            $cased_method_id,
            $class_storage,
            $moved_call
        );

        return true;
    }

    /**
     * @param  list<PhpParser\Node\Arg> $args
     * @return false|null
     */
    private static function checkPseudoMethod(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        MethodIdentifier $method_id,
        string $fq_class_name,
        array $args,
        \Psalm\Storage\ClassLikeStorage $class_storage,
        \Psalm\Storage\MethodStorage $pseudo_method_storage,
        Context $context
    ): ?bool {
        if (ArgumentsAnalyzer::analyze(
            $statements_analyzer,
            $args,
            $pseudo_method_storage->params,
            (string) $method_id,
            true,
            $context
        ) === false) {
            return false;
        }

        $codebase = $statements_analyzer->getCodebase();

        if (ArgumentsAnalyzer::checkArgumentsMatch(
            $statements_analyzer,
            $args,
            $method_id,
            $pseudo_method_storage->params,
            $pseudo_method_storage,
            null,
            null,
            new CodeLocation($statements_analyzer, $stmt),
            $context
        ) === false) {
            return false;
        }

        $method_storage = null;

        if ($statements_analyzer->data_flow_graph) {
            try {
                $method_storage = $codebase->methods->getStorage($method_id);

                ArgumentsAnalyzer::analyze(
                    $statements_analyzer,
                    $args,
                    $method_storage->params,
                    (string) $method_id,
                    true,
                    $context
                );

                ArgumentsAnalyzer::checkArgumentsMatch(
                    $statements_analyzer,
                    $args,
                    $method_id,
                    $method_storage->params,
                    $method_storage,
                    null,
                    null,
                    new CodeLocation($statements_analyzer, $stmt),
                    $context
                );
            } catch (\Exception $e) {
                // do nothing
            }
        }

        if ($pseudo_method_storage->return_type) {
            $return_type_candidate = clone $pseudo_method_storage->return_type;

            $return_type_candidate = \Psalm\Internal\Type\TypeExpander::expandUnion(
                $statements_analyzer->getCodebase(),
                $return_type_candidate,
                $fq_class_name,
                $fq_class_name,
                $class_storage->parent_class
            );

            if ($method_storage) {
                \Psalm\Internal\Analyzer\Statements\Expression\Call\StaticCallAnalyzer::taintReturnType(
                    $statements_analyzer,
                    $stmt,
                    $method_id,
                    (string) $method_id,
                    $return_type_candidate,
                    $method_storage,
                    null,
                    $context
                );
            }

            $stmt_type = $statements_analyzer->node_data->getType($stmt);

            if (!$stmt_type) {
                $statements_analyzer->node_data->setType($stmt, $return_type_candidate);
            } else {
                $statements_analyzer->node_data->setType(
                    $stmt,
                    Type::combineUnionTypes(
                        $return_type_candidate,
                        $stmt_type
                    )
                );
            }
        }

        return null;
    }

    public static function handleNonObjectCall(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\StaticCall $stmt,
        Context $context,
        Type\Atomic $lhs_type_part,
        bool $ignore_nullable_issues
    ) : void {
        $codebase = $statements_analyzer->getCodebase();
        $config = $codebase->config;

        if ($lhs_type_part instanceof Type\Atomic\TMixed
            || $lhs_type_part instanceof Type\Atomic\TTemplateParam
            || $lhs_type_part instanceof Type\Atomic\TClassString
            || $lhs_type_part instanceof Type\Atomic\TObject
        ) {
            if ($stmt->name instanceof PhpParser\Node\Identifier) {
                $codebase->analyzer->addMixedMemberName(
                    strtolower($stmt->name->name),
                    $context->calling_method_id ?: $statements_analyzer->getFileName()
                );
            }

            if (IssueBuffer::accepts(
                new MixedMethodCall(
                    'Cannot call method on an unknown class',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return;
        }

        if ($lhs_type_part instanceof Type\Atomic\TString) {
            if ($config->allow_string_standin_for_class
                && !$lhs_type_part instanceof Type\Atomic\TNumericString
            ) {
                return;
            }

            if (IssueBuffer::accepts(
                new InvalidStringClass(
                    'String cannot be used as a class',
                    new CodeLocation($statements_analyzer->getSource(), $stmt)
                ),
                $statements_analyzer->getSuppressedIssues()
            )) {
                // fall through
            }

            return;
        }

        if ($lhs_type_part instanceof Type\Atomic\TNull
            && $ignore_nullable_issues
        ) {
            return;
        }

        if (IssueBuffer::accepts(
            new UndefinedClass(
                'Type ' . $lhs_type_part . ' cannot be called as a class',
                new CodeLocation($statements_analyzer->getSource(), $stmt),
                (string) $lhs_type_part
            ),
            $statements_analyzer->getSuppressedIssues()
        )) {
            // fall through
        }
    }
}
