<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Fetch;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Call\MethodCallAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Analyzer\TraitAnalyzer;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\Comparator\AtomicTypeComparator;
use Psalm\Internal\Type\Comparator\TypeComparisonResult;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Internal\Type\TypeExpander;
use Psalm\Issue\EmptyArrayAccess;
use Psalm\Issue\InvalidArrayAccess;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Issue\InvalidArrayOffset;
use Psalm\Issue\MixedArrayAccess;
use Psalm\Issue\MixedArrayAssignment;
use Psalm\Issue\MixedArrayOffset;
use Psalm\Issue\MixedArrayTypeCoercion;
use Psalm\Issue\MixedStringOffsetAssignment;
use Psalm\Issue\NullArrayAccess;
use Psalm\Issue\NullArrayOffset;
use Psalm\Issue\PossiblyInvalidArrayAccess;
use Psalm\Issue\PossiblyInvalidArrayAssignment;
use Psalm\Issue\PossiblyInvalidArrayOffset;
use Psalm\Issue\PossiblyNullArrayAccess;
use Psalm\Issue\PossiblyNullArrayAssignment;
use Psalm\Issue\PossiblyNullArrayOffset;
use Psalm\Issue\PossiblyUndefinedArrayOffset;
use Psalm\Issue\PossiblyUndefinedIntArrayOffset;
use Psalm\Issue\PossiblyUndefinedStringArrayOffset;
use Psalm\IssueBuffer;
use Psalm\Node\Expr\VirtualConstFetch;
use Psalm\Node\Expr\VirtualMethodCall;
use Psalm\Node\VirtualArg;
use Psalm\Node\VirtualIdentifier;
use Psalm\Node\VirtualName;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TClassString;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNull;
use Psalm\Type\Atomic\TObject;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TSingleLetter;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\MutableUnion;
use Psalm\Type\Union;
use UnexpectedValueException;

use function array_keys;
use function array_map;
use function array_pop;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_int;
use function is_numeric;
use function preg_match;
use function strlen;
use function strtolower;

/**
 * @internal
 */
class ArrayFetchAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context
    ): bool {
        $extended_var_id = ExpressionIdentifier::getExtendedVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        if ($stmt->dim) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;

            $was_inside_unset = $context->inside_unset;
            $context->inside_unset = false;

            if (ExpressionAnalyzer::analyze($statements_analyzer, $stmt->dim, $context) === false) {
                $context->inside_unset = $was_inside_unset;
                $context->inside_general_use = $was_inside_general_use;

                return false;
            }

            $context->inside_unset = $was_inside_unset;

            $context->inside_general_use = $was_inside_general_use;
        }

        $keyed_array_var_id = ExpressionIdentifier::getExtendedVarId(
            $stmt,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        $dim_var_id = null;
        $new_offset_type = null;

        if ($stmt->dim) {
            $used_key_type = $statements_analyzer->node_data->getType($stmt->dim) ?? Type::getMixed();

            $dim_var_id = ExpressionIdentifier::getExtendedVarId(
                $stmt->dim,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );
        } else {
            $used_key_type = Type::getInt();
        }

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $stmt->var,
            $context,
        ) === false) {
            return false;
        }

        $stmt_var_type = $statements_analyzer->node_data->getType($stmt->var);

        $codebase = $statements_analyzer->getCodebase();

        if ($keyed_array_var_id
            && $context->hasVariable($keyed_array_var_id)
            && !$context->vars_in_scope[$keyed_array_var_id]->possibly_undefined
            && $stmt_var_type
            && !$stmt_var_type->hasClassStringMap()
        ) {
            $stmt_type = $context->vars_in_scope[$keyed_array_var_id];

            self::taintArrayFetch(
                $statements_analyzer,
                $stmt->var,
                $keyed_array_var_id,
                $stmt_type,
                $used_key_type,
                $context,
            );

            if ($stmt->dim && $statements_analyzer->node_data->getType($stmt->dim)) {
                $statements_analyzer->node_data->setType($stmt->dim, $used_key_type);
            }

            $statements_analyzer->node_data->setType(
                $stmt,
                $stmt_type,
            );

            return true;
        }

        $can_store_result = false;

        if ($stmt_var_type) {
            if ($stmt_var_type->isNull()) {
                if (!$context->inside_isset) {
                    IssueBuffer::maybeAdd(
                        new NullArrayAccess(
                            'Cannot access array value on null variable ' . $extended_var_id,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                $stmt_type = $statements_analyzer->node_data->getType($stmt);
                $statements_analyzer->node_data->setType(
                    $stmt,
                    Type::combineUnionTypes($stmt_type, Type::getNull()),
                );

                return true;
            }

            $stmt_type = self::getArrayAccessTypeGivenOffset(
                $statements_analyzer,
                $stmt,
                $stmt_var_type,
                $used_key_type,
                false,
                $extended_var_id,
                $context,
                null,
            );

            if ($stmt->dim && $stmt_var_type->hasArray()) {
                $array_type = $stmt_var_type->getArray();

                if ($array_type instanceof TClassStringMap) {
                    $array_value_type = Type::getMixed();
                } elseif ($array_type instanceof TArray) {
                    $array_value_type = $array_type->type_params[1];
                } else {
                    $array_value_type = $array_type->getGenericValueType();
                }

                if ($context->inside_assignment || !$array_value_type->isMixed()) {
                    $can_store_result = true;
                }
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            if ($context->inside_isset
                && $stmt->dim
                && ($stmt_dim_type = $statements_analyzer->node_data->getType($stmt->dim))
                && $stmt_var_type->hasArray()
                && ($stmt->var instanceof PhpParser\Node\Expr\ClassConstFetch
                    || $stmt->var instanceof PhpParser\Node\Expr\ConstFetch)
            ) {
                /**
                 * @var TArray|TKeyedArray
                 */
                $array_type = $stmt_var_type->getArray();

                if ($array_type instanceof TArray) {
                    $const_array_key_type = $array_type->type_params[0];
                } else {
                    $const_array_key_type = $array_type->getGenericKeyType();
                }

                if ($dim_var_id
                    && !$const_array_key_type->hasMixed()
                    && !$stmt_dim_type->hasMixed()
                ) {
                    $new_offset_type = $stmt_dim_type->getBuilder();
                    $const_array_key_atomic_types = $const_array_key_type->getAtomicTypes();

                    foreach ($new_offset_type->getAtomicTypes() as $offset_key => $offset_atomic_type) {
                        if ($offset_atomic_type instanceof TString
                            || $offset_atomic_type instanceof TInt
                        ) {
                            if (!isset($const_array_key_atomic_types[$offset_key])
                                && !UnionTypeComparator::isContainedBy(
                                    $codebase,
                                    new Union([$offset_atomic_type]),
                                    $const_array_key_type,
                                )
                            ) {
                                $new_offset_type->removeType($offset_key);
                            }
                        } elseif (!UnionTypeComparator::isContainedBy(
                            $codebase,
                            $const_array_key_type,
                            new Union([$offset_atomic_type]),
                        )) {
                            $new_offset_type->removeType($offset_key);
                        }
                    }

                    $new_offset_type = $new_offset_type->freeze();
                }
            }
        }

        if ($keyed_array_var_id
            && $context->hasVariable($keyed_array_var_id)
            && (!($stmt_type = $statements_analyzer->node_data->getType($stmt)) || $stmt_type->isVanillaMixed())
        ) {
            $statements_analyzer->node_data->setType($stmt, $context->vars_in_scope[$keyed_array_var_id]);
        }

        if (!($stmt_type = $statements_analyzer->node_data->getType($stmt))) {
            $stmt_type = Type::getMixed();
        } else {
            if ($stmt_type->possibly_undefined
                && !$context->inside_isset
                && !$context->inside_unset
                && ($stmt_var_type && !$stmt_var_type->hasMixed())
            ) {
                IssueBuffer::maybeAdd(
                    new PossiblyUndefinedArrayOffset(
                        'Possibly undefined array key ' . $keyed_array_var_id
                            . ' on ' . $stmt_var_type->getId(),
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            $stmt_type = $stmt_type->setPossiblyUndefined(false);
        }

        if ($context->inside_isset && $dim_var_id && $new_offset_type && !$new_offset_type->isUnionEmpty()) {
            $context->vars_in_scope[$dim_var_id] = $new_offset_type;
        }

        self::taintArrayFetch(
            $statements_analyzer,
            $stmt->var,
            $keyed_array_var_id,
            $stmt_type,
            $used_key_type,
            $context,
        );

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        if ($stmt->dim && $statements_analyzer->node_data->getType($stmt->dim)) {
            $statements_analyzer->node_data->setType($stmt->dim, $used_key_type);
        }

        if ($keyed_array_var_id && !$context->inside_isset && $can_store_result) {
            $context->vars_in_scope[$keyed_array_var_id] = $stmt_type;
            $context->vars_possibly_in_scope[$keyed_array_var_id] = true;

            // reference the variable too
            $context->hasVariable($keyed_array_var_id);
        }

        return true;
    }

    /**
     * Used to create a path between a variable $foo and $foo["a"]
     */
    public static function taintArrayFetch(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $var,
        ?string $keyed_array_var_id,
        Union &$stmt_type,
        Union &$offset_type,
        ?Context $context = null
    ): void {
        if ($statements_analyzer->data_flow_graph
            && ($stmt_var_type = $statements_analyzer->node_data->getType($var))
            && $stmt_var_type->parent_nodes
        ) {
            if ($statements_analyzer->data_flow_graph instanceof TaintFlowGraph
                && in_array('TaintedInput', $statements_analyzer->getSuppressedIssues())
            ) {
                $statements_analyzer->node_data->setType($var, $stmt_var_type->setParentNodes([]));
                return;
            }

            $added_taints = [];
            $removed_taints = [];

            if ($context) {
                $codebase = $statements_analyzer->getCodebase();
                $event = new AddRemoveTaintsEvent($var, $context, $statements_analyzer, $codebase);

                $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);
            }

            $var_location = new CodeLocation($statements_analyzer->getSource(), $var);

            $new_parent_node = DataFlowNode::getForAssignment(
                $keyed_array_var_id ?: 'arrayvalue-fetch',
                $var_location,
            );

            $array_key_node = null;

            $statements_analyzer->data_flow_graph->addNode($new_parent_node);

            $dim_value = $offset_type->isSingleStringLiteral()
                ? $offset_type->getSingleStringLiteral()->value
                : ($offset_type->isSingleIntLiteral()
                    ? $offset_type->getSingleIntLiteral()->value
                    : null);

            if ($keyed_array_var_id === null && $dim_value === null) {
                $array_key_node = DataFlowNode::getForAssignment(
                    'arraykey-fetch',
                    $var_location,
                );

                $statements_analyzer->data_flow_graph->addNode($array_key_node);
            }

            foreach ($stmt_var_type->parent_nodes as $parent_node) {
                $statements_analyzer->data_flow_graph->addPath(
                    $parent_node,
                    $new_parent_node,
                    'arrayvalue-fetch' . ($dim_value !== null ? '-\'' . $dim_value . '\'' : ''),
                    $added_taints,
                    $removed_taints,
                );

                if ($stmt_type->by_ref) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $new_parent_node,
                        $parent_node,
                        'arrayvalue-assignment' . ($dim_value !== null ? '-\'' . $dim_value . '\'' : ''),
                        $added_taints,
                        $removed_taints,
                    );
                }

                if ($array_key_node) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        $array_key_node,
                        'arraykey-fetch',
                        $added_taints,
                        $removed_taints,
                    );
                }
            }

            $stmt_type = $stmt_type->setParentNodes([$new_parent_node->id => $new_parent_node]);

            if ($array_key_node) {
                $offset_type = $offset_type->setParentNodes([$array_key_node->id => $array_key_node]);
            }
        }
    }

    /**
     * @psalm-suppress ComplexMethod to be refactored.
     * Good type/bad type behaviour could be mutualised with ArrayAnalyzer
     */
    public static function getArrayAccessTypeGivenOffset(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Union &$array_type,
        Union &$offset_type_original,
        bool $in_assignment,
        ?string $extended_var_id,
        Context $context,
        PhpParser\Node\Expr $assign_value = null,
        Union $replacement_type = null
    ): Union {
        $offset_type = $offset_type_original->getBuilder();

        $codebase = $statements_analyzer->getCodebase();

        $has_array_access = false;
        $non_array_types = [];

        $has_valid_expected_offset = false;
        $expected_offset_types = [];

        $key_values = [];

        if ($stmt->dim instanceof PhpParser\Node\Scalar\String_) {
            $key_values[] = new TLiteralString($stmt->dim->value);
        } elseif ($stmt->dim instanceof PhpParser\Node\Scalar\LNumber) {
            $key_values[] = new TLiteralInt($stmt->dim->value);
        } elseif ($stmt->dim && ($stmt_dim_type = $statements_analyzer->node_data->getType($stmt->dim))) {
            $string_literals = $stmt_dim_type->getLiteralStrings();
            $int_literals = $stmt_dim_type->getLiteralInts();

            $all_atomic_types = $stmt_dim_type->getAtomicTypes();

            if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                foreach ($string_literals as $string_literal) {
                    $key_values[] = $string_literal;
                }

                foreach ($int_literals as $int_literal) {
                    $key_values[] = $int_literal;
                }
            }
        }

        $array_access_type = null;

        if ($offset_type->isNull()) {
            IssueBuffer::maybeAdd(
                new NullArrayOffset(
                    'Cannot access value on variable ' . $extended_var_id . ' using null offset',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            if ($in_assignment) {
                $offset_type->removeType('null');
                $offset_type->addType(new TLiteralString(''));
            }
        }

        if ($offset_type->isNullable() && !$context->inside_isset) {
            if (!$offset_type->ignore_nullable_issues) {
                IssueBuffer::maybeAdd(
                    new PossiblyNullArrayOffset(
                        'Cannot access value on variable ' . $extended_var_id
                            . ' using possibly null offset ' . $offset_type,
                        new CodeLocation($statements_analyzer->getSource(), $stmt->var),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            if ($in_assignment) {
                $offset_type->removeType('null');

                if (!$offset_type->ignore_nullable_issues) {
                    $offset_type->addType(new TLiteralString(''));
                }
            }
        }

        if ($array_type->isArray()) {
            $has_valid_absolute_offset = self::checkArrayOffsetType(
                $offset_type,
                $offset_type->getAtomicTypes(),
                $codebase,
            );

            if ($has_valid_absolute_offset === false) {
                //we didn't find a single type that could be valid
                $expected_offset_types[] = 'array-key';
            }
        } else {
            //on not-arrays, the type is considered valid
            $has_valid_absolute_offset = true;
        }

        $types = $array_type->getAtomicTypes();
        $changed = false;
        foreach ($types as $type_string => $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }

            $original_type_real = $type;
            $original_type = $type;

            if ($type instanceof TMixed
                || $type instanceof TTemplateParam
                || $type instanceof TNever
            ) {
                if (!$type instanceof TTemplateParam || $type->as->isMixed() || !$type->as->isSingle()) {
                    $array_access_type = self::handleMixedArrayAccess(
                        $context,
                        $statements_analyzer,
                        $codebase,
                        $in_assignment,
                        $extended_var_id,
                        $stmt,
                        $array_access_type,
                        $type,
                    );

                    $has_valid_expected_offset = true;

                    continue;
                }

                $type = $type->as->getSingleAtomic();
                $original_type = $type;
            }

            if ($type instanceof TNull) {
                if ($array_type->ignore_nullable_issues) {
                    continue;
                }

                if ($in_assignment) {
                    if ($replacement_type) {
                        $array_access_type = Type::combineUnionTypes($array_access_type, $replacement_type);
                    } else {
                        IssueBuffer::maybeAdd(
                            new PossiblyNullArrayAssignment(
                                'Cannot access array value on possibly null variable ' . $extended_var_id .
                                    ' of type ' . $array_type,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );

                        $array_access_type = new Union([new TNever]);
                    }
                } else {
                    if (!$context->inside_isset && !MethodCallAnalyzer::hasNullsafe($stmt->var)) {
                        IssueBuffer::maybeAdd(
                            new PossiblyNullArrayAccess(
                                'Cannot access array value on possibly null variable ' . $extended_var_id .
                                    ' of type ' . $array_type,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }

                    $array_access_type = Type::combineUnionTypes($array_access_type, Type::getNull());
                }

                continue;
            }

            if ($type instanceof TArray
                || $type instanceof TKeyedArray
                || $type instanceof TClassStringMap
            ) {
                self::handleArrayAccessOnArray(
                    $in_assignment,
                    $type,
                    $key_values,
                    $array_type->hasMixed(),
                    $stmt,
                    $replacement_type,
                    $offset_type,
                    $original_type_real,
                    $codebase,
                    $extended_var_id,
                    $context,
                    $statements_analyzer,
                    $expected_offset_types,
                    $array_access_type,
                    $has_array_access,
                    $has_valid_expected_offset,
                );

                if ($type !== $original_type) {
                    $changed = true;
                    unset($types[$type_string]);
                    $types[$type->getKey()] = $type;
                }

                continue;
            }

            if ($type instanceof TString) {
                self::handleArrayAccessOnString(
                    $statements_analyzer,
                    $codebase,
                    $stmt,
                    $in_assignment,
                    $context,
                    $replacement_type,
                    $type,
                    $offset_type,
                    $expected_offset_types,
                    $array_access_type,
                    $has_valid_expected_offset,
                );

                continue;
            }

            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            if ($type instanceof TFalse && $array_type->ignore_falsable_issues) {
                continue;
            }

            if ($type instanceof TNamedObject) {
                self::handleArrayAccessOnNamedObject(
                    $statements_analyzer,
                    $stmt,
                    $type,
                    $context,
                    $in_assignment,
                    $assign_value,
                    $array_access_type,
                    $has_array_access,
                );
            } elseif (!$array_type->hasMixed()) {
                $non_array_types[] = (string)$type;
            }
        }
        if ($changed) {
            $array_type = $array_type->getBuilder()->setTypes($types)->freeze();
        }

        if ($non_array_types) {
            if ($has_array_access) {
                if ($in_assignment) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidArrayAssignment(
                            'Cannot access array value on non-array variable ' .
                            $extended_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } elseif (!$context->inside_isset) {
                    IssueBuffer::maybeAdd(
                        new PossiblyInvalidArrayAccess(
                            'Cannot access array value on non-array variable ' .
                            $extended_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            } else {
                if ($in_assignment) {
                    IssueBuffer::maybeAdd(
                        new InvalidArrayAssignment(
                            'Cannot access array value on non-array variable ' .
                            $extended_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                } else {
                    IssueBuffer::maybeAdd(
                        new InvalidArrayAccess(
                            'Cannot access array value on non-array variable ' .
                            $extended_var_id . ' of type ' . $non_array_types[0],
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                $array_access_type = Type::getMixed();
            }
        }

        if ($offset_type->hasMixed()) {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
            }

            IssueBuffer::maybeAdd(
                new MixedArrayOffset(
                    'Cannot access value on variable ' . $extended_var_id . ' using mixed offset',
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );
        } else {
            if (!$context->collect_initializations
                && !$context->collect_mutations
                && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                && (!(($parent_source = $statements_analyzer->getSource())
                        instanceof FunctionLikeAnalyzer)
                    || !$parent_source->getSource() instanceof TraitAnalyzer)
            ) {
                $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
            }

            if ($expected_offset_types) {
                $invalid_offset_type = $expected_offset_types[0];

                $used_offset = 'using a ' . $offset_type->getId() . ' offset';

                if ($key_values) {
                    $used_offset = "using offset value of '" .
                        implode('|', array_map(static fn(Atomic $atomic_type)
                            => $atomic_type->value, $key_values)) . "'";
                }

                if ($has_valid_expected_offset && $has_valid_absolute_offset && $context->inside_isset) {
                    // do nothing
                } elseif ($has_valid_expected_offset && $has_valid_absolute_offset) {
                    if (!$context->inside_unset) {
                        IssueBuffer::maybeAdd(
                            new PossiblyInvalidArrayOffset(
                                'Cannot access value on variable ' . $extended_var_id . ' ' . $used_offset
                                    . ', expecting ' . $invalid_offset_type,
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    }
                } else {
                    $good_types = [];
                    $bad_types = [];
                    foreach ($offset_type->getAtomicTypes() as $atomic_key_type) {
                        if (!$atomic_key_type instanceof TString
                            && !$atomic_key_type instanceof TInt
                            && !$atomic_key_type instanceof TArrayKey
                            && !$atomic_key_type instanceof TMixed
                            && !$atomic_key_type instanceof TTemplateParam
                            && !(
                                $atomic_key_type instanceof TObjectWithProperties
                                && isset($atomic_key_type->methods['__tostring'])
                            )
                        ) {
                            $bad_types[] = $atomic_key_type;

                            if ($atomic_key_type instanceof TFalse) {
                                $good_types[] = new TLiteralInt(0);
                            } elseif ($atomic_key_type instanceof TTrue) {
                                $good_types[] = new TLiteralInt(1);
                            } elseif ($atomic_key_type instanceof TBool) {
                                $good_types[] = new TLiteralInt(0);
                                $good_types[] = new TLiteralInt(1);
                            } elseif ($atomic_key_type instanceof TLiteralFloat) {
                                $good_types[] = new TLiteralInt((int)$atomic_key_type->value);
                            } elseif ($atomic_key_type instanceof TFloat) {
                                $good_types[] = new TInt;
                            } else {
                                $good_types[] = new TArrayKey;
                            }
                        }
                    }

                    if ($bad_types && $good_types) {
                        $offset_type->substitute(
                            TypeCombiner::combine($bad_types, $codebase),
                            TypeCombiner::combine($good_types, $codebase),
                        );
                    }

                    IssueBuffer::maybeAdd(
                        new InvalidArrayOffset(
                            'Cannot access value on variable ' . $extended_var_id . ' ' . $used_offset
                                . ', expecting ' . $invalid_offset_type,
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }
            }
        }

        $offset_type_original = $offset_type->freeze();

        if ($array_access_type === null) {
            // shouldn’t happen, but don’t crash
            return Type::getMixed();
        }

        if ($array_type->by_ref) {
            return $array_access_type->setByRef(true);
        }

        return $array_access_type;
    }

    private static function checkLiteralIntArrayOffset(
        MutableUnion $offset_type,
        Union $expected_offset_type,
        ?string $extended_var_id,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ): void {
        if ($context->inside_isset || $context->inside_unset) {
            return;
        }

        if ($offset_type->hasLiteralInt()) {
            $found_match = false;

            foreach ($offset_type->getAtomicTypes() as $offset_type_part) {
                if ($extended_var_id
                    && $offset_type_part instanceof TLiteralInt
                    && isset(
                        $context->vars_in_scope[
                            $extended_var_id . '[' . $offset_type_part->value . ']'
                        ],
                    )
                    && !$context->vars_in_scope[
                            $extended_var_id . '[' . $offset_type_part->value . ']'
                        ]->possibly_undefined
                ) {
                    $found_match = true;
                    break;
                }
            }

            if (!$found_match) {
                IssueBuffer::maybeAdd(
                    new PossiblyUndefinedIntArrayOffset(
                        'Possibly undefined array offset \''
                            . $offset_type->getId() . '\' '
                            . 'is risky given expected type \''
                            . $expected_offset_type->getId() . '\'.'
                            . ' Consider using isset beforehand.',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }
    }

    private static function checkLiteralStringArrayOffset(
        MutableUnion $offset_type,
        Union $expected_offset_type,
        ?string $extended_var_id,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        StatementsAnalyzer $statements_analyzer
    ): void {
        if ($context->inside_isset || $context->inside_unset) {
            return;
        }

        if ($offset_type->hasLiteralString() && !$expected_offset_type->hasLiteralClassString()) {
            $found_match = false;

            foreach ($offset_type->getAtomicTypes() as $offset_type_part) {
                if ($extended_var_id
                    && $offset_type_part instanceof TLiteralString
                    && isset(
                        $context->vars_in_scope[
                            $extended_var_id . '[\'' . $offset_type_part->value . '\']'
                        ],
                    )
                    && !$context->vars_in_scope[
                            $extended_var_id . '[\'' . $offset_type_part->value . '\']'
                        ]->possibly_undefined
                ) {
                    $found_match = true;
                    break;
                }
            }

            if (!$found_match) {
                IssueBuffer::maybeAdd(
                    new PossiblyUndefinedStringArrayOffset(
                        'Possibly undefined array offset \''
                            . $offset_type->getId() . '\' '
                            . 'is risky given expected type \''
                            . $expected_offset_type->getId() . '\'.'
                            . ' Consider using isset beforehand.',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }
    }

    public static function replaceOffsetTypeWithInts(Union $offset_type): Union
    {
        $offset_type = $offset_type->getBuilder();
        $offset_types = $offset_type->getAtomicTypes();

        foreach ($offset_types as $key => $offset_type_part) {
            if ($offset_type_part instanceof TLiteralString) {
                if (preg_match('/^(0|[1-9][0-9]*)$/', $offset_type_part->value)) {
                    $offset_type->addType(new TLiteralInt((int) $offset_type_part->value));
                    $offset_type->removeType($key);
                }
            } elseif ($offset_type_part instanceof TBool) {
                if ($offset_type_part instanceof TFalse) {
                    if (!$offset_type->ignore_falsable_issues) {
                        $offset_type->addType(new TLiteralInt(0));
                        $offset_type->removeType($key);
                    }
                } elseif ($offset_type_part instanceof TTrue) {
                    $offset_type->addType(new TLiteralInt(1));
                    $offset_type->removeType($key);
                } else {
                    $offset_type->addType(new TLiteralInt(0));
                    $offset_type->addType(new TLiteralInt(1));
                    $offset_type->removeType($key);
                }
            }
        }

        return $offset_type->freeze();
    }

    /**
     * @param  TMixed|TTemplateParam|TNever $type
     */
    public static function handleMixedArrayAccess(
        Context $context,
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        bool $in_assignment,
        ?string $extended_var_id,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        ?Union $array_access_type,
        Atomic $type
    ): Union {
        if (!$context->collect_initializations
            && !$context->collect_mutations
            && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
            && (!(($parent_source = $statements_analyzer->getSource())
                    instanceof FunctionLikeAnalyzer)
                || !$parent_source->getSource() instanceof TraitAnalyzer)
        ) {
            $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
        }

        if (!$context->inside_isset) {
            if ($in_assignment) {
                IssueBuffer::maybeAdd(
                    new MixedArrayAssignment(
                        'Cannot access array value on mixed variable ' . $extended_var_id,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                IssueBuffer::maybeAdd(
                    new MixedArrayAccess(
                        'Cannot access array value on mixed variable ' . $extended_var_id,
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        if (($data_flow_graph = $statements_analyzer->data_flow_graph)
            && $data_flow_graph instanceof VariableUseGraph
            && ($stmt_var_type = $statements_analyzer->node_data->getType($stmt->var))
        ) {
            if ($stmt_var_type->parent_nodes) {
                $var_location = new CodeLocation($statements_analyzer->getSource(), $stmt->var);

                $new_parent_node = DataFlowNode::getForAssignment('mixed-var-array-access', $var_location);

                $data_flow_graph->addNode($new_parent_node);

                foreach ($stmt_var_type->parent_nodes as $parent_node) {
                    $data_flow_graph->addPath($parent_node, $new_parent_node, '=');

                    $data_flow_graph->addPath(
                        $parent_node,
                        new DataFlowNode('variable-use', 'variable use', null),
                        'variable-use',
                    );
                }

                $statements_analyzer->node_data->setType($stmt->var, $stmt_var_type->setParentNodes([
                    $new_parent_node->id => $new_parent_node,
                ]));
            }
        }

        return Type::combineUnionTypes(
            $array_access_type,
            Type::getMixed($type instanceof TNever),
        );
    }

    /**
     * @param list<string> $expected_offset_types
     * @param TArray|TKeyedArray|TClassStringMap $type
     * @param-out TArray|TKeyedArray|TClassStringMap $type
     * @param list<TLiteralInt|TLiteralString> $key_values
     * @psalm-suppress ConflictingReferenceConstraint Ignore
     */
    private static function handleArrayAccessOnArray(
        bool $in_assignment,
        Atomic &$type,
        array &$key_values,
        bool $hasMixed,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        ?Union $replacement_type,
        MutableUnion $offset_type,
        Atomic $original_type,
        Codebase $codebase,
        ?string $extended_var_id,
        Context $context,
        StatementsAnalyzer $statements_analyzer,
        array &$expected_offset_types,
        ?Union &$array_access_type,
        bool &$has_array_access,
        bool &$has_valid_offset
    ): void {
        $has_array_access = true;

        if ($in_assignment) {
            if ($type instanceof TArray) {
                $from_empty_array = $type->isEmptyArray();

                if (count($key_values) === 1) {
                    $single_atomic = $key_values[0];
                    $from_mixed_array = $type->type_params[1]->isMixed();

                    // ok, type becomes an TKeyedArray
                    $type = new TKeyedArray(
                        [
                            $single_atomic->value => $from_mixed_array ? Type::getMixed() : Type::getNever(),
                        ],
                        $single_atomic instanceof TLiteralClassString ? [
                            $single_atomic->value => true,
                        ] : null,
                        $from_empty_array ? null : $type->type_params,
                    );
                } elseif (!$stmt->dim && $from_empty_array && $replacement_type) {
                    $type = new TKeyedArray(
                        [$replacement_type],
                        null,
                        null,
                        true,
                    );
                    return;
                }
            } elseif ($type instanceof TKeyedArray
                && $type->fallback_params !== null
                && $type->fallback_params[1]->isMixed()
                && count($key_values) === 1
            ) {
                $properties = $type->properties;
                $properties[$key_values[0]->value] = Type::getMixed();
                $type = $type->setProperties($properties);
            }
        }

        $offset_type = self::replaceOffsetTypeWithInts($offset_type->freeze())->getBuilder();

        if ($type instanceof TKeyedArray
            && $type->is_list
            && (($in_assignment && $stmt->dim)
                || $original_type instanceof TTemplateParam
                || !$offset_type->isInt())
        ) {
            $temp = $type->getGenericArrayType();
            self::handleArrayAccessOnTArray(
                $statements_analyzer,
                $codebase,
                $context,
                $stmt,
                $hasMixed,
                $extended_var_id,
                $temp,
                $offset_type,
                $in_assignment,
                $expected_offset_types,
                $array_access_type,
                $original_type,
                $has_valid_offset,
            );
        } elseif ($type instanceof TArray) {
            self::handleArrayAccessOnTArray(
                $statements_analyzer,
                $codebase,
                $context,
                $stmt,
                $hasMixed,
                $extended_var_id,
                $type,
                $offset_type,
                $in_assignment,
                $expected_offset_types,
                $array_access_type,
                $original_type,
                $has_valid_offset,
            );
        } elseif ($type instanceof TClassStringMap) {
            self::handleArrayAccessOnClassStringMap(
                $codebase,
                $type,
                $offset_type,
                $replacement_type,
                $array_access_type,
            );
        } else {
            self::handleArrayAccessOnKeyedArray(
                $statements_analyzer,
                $codebase,
                $key_values,
                $replacement_type,
                $array_access_type,
                $in_assignment,
                $stmt,
                $offset_type,
                $extended_var_id,
                $context,
                $type,
                $hasMixed,
                $expected_offset_types,
                $has_valid_offset,
            );
        }

        if ($context->inside_isset) {
            $offset_type->ignore_isset = true;
        }
    }

    /**
     * @param list<string> $expected_offset_types
     * @param-out TArray $type
     */
    private static function handleArrayAccessOnTArray(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Context $context,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        bool $hasMixed,
        ?string $extended_var_id,
        TArray &$type,
        MutableUnion $offset_type,
        bool $in_assignment,
        array &$expected_offset_types,
        ?Union &$array_access_type,
        Atomic $original_type,
        bool &$has_valid_offset
    ): void {
        // if we're assigning to an empty array with a key offset, refashion that array
        if ($in_assignment) {
            if ($type->isEmptyArray()) {
                $type = $type->setTypeParams([
                    $offset_type->isMixed()
                        ? Type::getArrayKey()
                        : $offset_type->freeze(),
                    $type->type_params[1],
                ]);
            }
        } elseif (!$type->isEmptyArray()) {
            $expected_offset_type = $type->type_params[0]->hasMixed()
                ? new Union([new TArrayKey])
                : $type->type_params[0];

            $templated_offset_type = null;

            foreach ($offset_type->getAtomicTypes() as $offset_atomic_type) {
                if ($offset_atomic_type instanceof TTemplateParam) {
                    $templated_offset_type = $offset_atomic_type;
                }
            }

            $union_comparison_results = new TypeComparisonResult();

            if ($original_type instanceof TTemplateParam && $templated_offset_type) {
                foreach ($templated_offset_type->as->getAtomicTypes() as $offset_as) {
                    if ($offset_as instanceof TTemplateKeyOf
                        && $offset_as->param_name === $original_type->param_name
                        && $offset_as->defining_class === $original_type->defining_class
                    ) {
                        $type = $type->setTypeParams([
                            $type->type_params[0],
                            new Union([
                                new TTemplateIndexedAccess(
                                    $offset_as->param_name,
                                    $templated_offset_type->param_name,
                                    $offset_as->defining_class,
                                ),
                            ]),
                        ]);

                        $has_valid_offset = true;
                    }
                }
            } else {
                $offset_type_contained_by_expected = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $offset_type->freeze(),
                    $expected_offset_type,
                    true,
                    $offset_type->ignore_falsable_issues,
                    $union_comparison_results,
                );

                if ($codebase->config->ensure_array_string_offsets_exist
                    && $offset_type_contained_by_expected
                ) {
                    //we already know we found a match, so if the array is non-empty and the key is a literal,
                    //then no need to check for PossiblyUndefinedStringArrayOffset
                    if (!$type instanceof TNonEmptyArray || !$type->type_params[0]->isSingleStringLiteral()) {
                        self::checkLiteralStringArrayOffset(
                            $offset_type,
                            $expected_offset_type,
                            $extended_var_id,
                            $stmt,
                            $context,
                            $statements_analyzer,
                        );
                    }
                }

                if ($codebase->config->ensure_array_int_offsets_exist
                    && $offset_type_contained_by_expected
                ) {
                    self::checkLiteralIntArrayOffset(
                        $offset_type,
                        $expected_offset_type,
                        $extended_var_id,
                        $stmt,
                        $context,
                        $statements_analyzer,
                    );
                }

                if ((!$offset_type_contained_by_expected
                        && !$union_comparison_results->type_coerced_from_scalar)
                    || $union_comparison_results->to_string_cast
                ) {
                    if ($union_comparison_results->type_coerced_from_mixed
                        && !$offset_type->isMixed()
                    ) {
                        IssueBuffer::maybeAdd(
                            new MixedArrayTypeCoercion(
                                'Coercion from array offset type \'' . $offset_type->getId() . '\' '
                                . 'to the expected type \'' . $expected_offset_type->getId() . '\'',
                                new CodeLocation($statements_analyzer->getSource(), $stmt),
                            ),
                            $statements_analyzer->getSuppressedIssues(),
                        );
                    } else {
                        $expected_offset_types[] = $expected_offset_type->getId();
                    }

                    if (UnionTypeComparator::canExpressionTypesBeIdentical(
                        $codebase,
                        $offset_type->freeze(),
                        $expected_offset_type,
                    )) {
                        $has_valid_offset = true;
                    }
                } else {
                    $has_valid_offset = true;
                }
            }
        }

        if (!$stmt->dim) {
            if ($type instanceof TNonEmptyArray) {
                if ($type->count !== null) {
                    $type = $type->setCount($type->count+1);
                }
            } else {
                $type = new TNonEmptyArray(
                    $type->type_params,
                    null,
                    null,
                    'non-empty-array',
                    $type->from_docblock,
                );
            }
        }

        $array_access_type = Type::combineUnionTypes(
            $array_access_type,
            $type->type_params[1],
        );

        if ($array_access_type->isNever()
            && !$hasMixed
            && !$in_assignment
            && !$context->inside_isset
        ) {
            IssueBuffer::maybeAdd(
                new EmptyArrayAccess(
                    'Cannot access value on empty array variable ' . $extended_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $stmt),
                ),
                $statements_analyzer->getSuppressedIssues(),
            );

            if (!IssueBuffer::isRecording()) {
                $array_access_type = Type::getMixed(true);
            }
        }
    }

    private static function handleArrayAccessOnClassStringMap(
        Codebase $codebase,
        TClassStringMap &$type,
        MutableUnion $offset_type,
        ?Union $replacement_type,
        ?Union &$array_access_type
    ): void {
        $offset_type_parts = array_values($offset_type->getAtomicTypes());

        foreach ($offset_type_parts as $offset_type_part) {
            if ($offset_type_part instanceof TClassString) {
                if ($offset_type_part instanceof TTemplateParamClass) {
                    $template_result_get = new TemplateResult(
                        [],
                        [
                            $type->param_name => [
                                'class-string-map' => new Union([
                                    new TTemplateParam(
                                        $offset_type_part->param_name,
                                        $offset_type_part->as_type
                                            ? new Union([$offset_type_part->as_type])
                                            : Type::getObject(),
                                        $offset_type_part->defining_class,
                                    ),
                                ]),
                            ],
                        ],
                    );

                    $template_result_set = new TemplateResult(
                        [],
                        [
                            $offset_type_part->param_name => [
                                $offset_type_part->defining_class => new Union([
                                    new TTemplateParam(
                                        $type->param_name,
                                        $type->as_type
                                            ? new Union([$type->as_type])
                                            : Type::getObject(),
                                        'class-string-map',
                                    ),
                                ]),
                            ],
                        ],
                    );
                } else {
                    $template_result_get = new TemplateResult(
                        [],
                        [
                            $type->param_name => [
                                'class-string-map' => new Union([
                                    $offset_type_part->as_type
                                        ?: new TObject(),
                                ]),
                            ],
                        ],
                    );
                    $template_result_set = new TemplateResult(
                        [],
                        [],
                    );
                }

                $expected_value_param_get = TemplateInferredTypeReplacer::replace(
                    $type->value_param,
                    $template_result_get,
                    $codebase,
                );

                if ($replacement_type) {
                    $replacement_type = TemplateInferredTypeReplacer::replace(
                        $replacement_type,
                        $template_result_set,
                        $codebase,
                    );

                    $type = new TClassStringMap(
                        $type->param_name,
                        $type->as_type,
                        Type::combineUnionTypes(
                            $replacement_type,
                            $type->value_param,
                            $codebase,
                        ),
                    );
                }

                $array_access_type = Type::combineUnionTypes(
                    $array_access_type,
                    $expected_value_param_get,
                    $codebase,
                );
            }
        }
    }

    /**
     * @param list<string> $expected_offset_types
     * @param list<TLiteralString|TLiteralInt> $key_values
     * @param-out TArray|TKeyedArray $type
     */
    private static function handleArrayAccessOnKeyedArray(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        array &$key_values,
        ?Union $replacement_type,
        ?Union &$array_access_type,
        bool $in_assignment,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        MutableUnion $offset_type,
        ?string $extended_var_id,
        Context $context,
        TKeyedArray &$type,
        bool $hasMixed,
        array &$expected_offset_types,
        bool &$has_valid_offset
    ): void {
        $generic_key_type = $type->getGenericKeyType();

        if (!$stmt->dim && $type->fallback_params === null && $type->is_list) {
            $key_values[] = new TLiteralInt(count($type->properties));
        }

        if ($key_values) {
            $properties = $type->properties;
            foreach ($key_values as $key_value) {
                if ($type->is_list && (!is_numeric($key_value->value) || $key_value->value < 0)) {
                    $expected_offset_types[] = $type->getGenericKeyType();
                    $has_valid_offset = false;
                } elseif ((isset($properties[$key_value->value]) && !(
                    $key_value->value === 0 && AtomicTypeComparator::isLegacyTListLike($type)
                ))
                    || $replacement_type
                ) {
                    $has_valid_offset = true;

                    if ($replacement_type) {
                        $properties[$key_value->value] = Type::combineUnionTypes(
                            $properties[$key_value->value] ?? null,
                            $replacement_type,
                        );
                        if (is_int($key_value->value)
                            && !$stmt->dim
                            && $type->is_list
                            && $type->properties[$key_value->value-1]->possibly_undefined
                        ) {
                            $first = true;
                            for ($x = 0; $x < $key_value->value; $x++) {
                                if (!$properties[$x]->possibly_undefined) {
                                    continue;
                                }
                                $properties[$x] = Type::combineUnionTypes(
                                    $properties[$x],
                                    $replacement_type,
                                );
                                if ($first) {
                                    $first = false;
                                    $properties[$x] = $properties[$x]->setPossiblyUndefined(false);
                                }
                            }
                            $properties[$key_value->value] = $properties[$key_value->value]->
                                setPossiblyUndefined(true)
                            ;
                        }
                    }

                    $array_access_type = Type::combineUnionTypes(
                        $array_access_type,
                        $properties[$key_value->value],
                    );
                } elseif ($in_assignment) {
                    $properties[$key_value->value] = new Union([new TNever]);

                    $array_access_type = Type::combineUnionTypes(
                        $array_access_type,
                        $properties[$key_value->value],
                    );
                } elseif ($type->fallback_params !== null) {
                    if ($codebase->config->ensure_array_string_offsets_exist) {
                        self::checkLiteralStringArrayOffset(
                            $offset_type,
                            $type->getGenericKeyType(),
                            $extended_var_id,
                            $stmt,
                            $context,
                            $statements_analyzer,
                        );
                    }

                    if ($codebase->config->ensure_array_int_offsets_exist) {
                        self::checkLiteralIntArrayOffset(
                            $offset_type,
                            $type->getGenericKeyType(),
                            $extended_var_id,
                            $stmt,
                            $context,
                            $statements_analyzer,
                        );
                    }

                    $properties[$key_value->value] = $type->fallback_params[1];

                    $array_access_type = $type->fallback_params[1];
                } elseif ($hasMixed) {
                    $has_valid_offset = true;

                    $array_access_type = Type::getMixed();
                } else {
                    $object_like_keys = array_keys($properties);

                    $last_key = array_pop($object_like_keys);

                    $key_string = '';

                    if ($object_like_keys) {
                        $formatted_keys = implode(
                            ', ',
                            array_map(
                                /** @param int|string $key */
                                static fn($key): string => is_int($key) ? "$key" : '\'' . $key . '\'',
                                $object_like_keys,
                            ),
                        );

                        $key_string = $formatted_keys . ' or ';
                    }

                    $key_string .= is_int($last_key) ? $last_key : '\'' . $last_key . '\'';

                    $expected_offset_types[] = $key_string;

                    $array_access_type = Type::getMixed();
                }
            }

            $type = $type->setProperties($properties);
        } else {
            $key_type = $generic_key_type->hasMixed()
                ? Type::getArrayKey()
                : $generic_key_type;

            $union_comparison_results = new TypeComparisonResult();

            $is_contained = UnionTypeComparator::isContainedBy(
                $codebase,
                $offset_type->freeze(),
                $key_type,
                true,
                $offset_type->ignore_falsable_issues,
                $union_comparison_results,
            );

            if ($context->inside_isset && !$is_contained) {
                $is_contained = UnionTypeComparator::isContainedBy(
                    $codebase,
                    $key_type,
                    $offset_type->freeze(),
                    true,
                    $offset_type->ignore_falsable_issues,
                );
            }

            if (($is_contained
                    || $union_comparison_results->type_coerced_from_scalar
                    || $union_comparison_results->type_coerced_from_mixed
                    || $in_assignment)
                && !$union_comparison_results->to_string_cast
            ) {
                if ($replacement_type) {
                    $generic_params = Type::combineUnionTypes(
                        $type->getGenericValueType(),
                        $replacement_type,
                    );

                    $new_key_type = Type::combineUnionTypes(
                        $generic_key_type,
                        $offset_type->isMixed() ? Type::getArrayKey() : $offset_type->freeze(),
                    );

                    if (!$stmt->dim) {
                        if ($type->is_list) {
                            $type = new TKeyedArray(
                                $type->properties,
                                null,
                                [$new_key_type, $generic_params],
                                true,
                            );
                        } else {
                            $type = new TNonEmptyArray([
                                $new_key_type,
                                $generic_params,
                            ], null, $type->getMinCount()+1);
                        }
                    } else {
                        $min_count = $type->getMinCount();
                        if ($min_count) {
                            $type = new TNonEmptyArray([
                                $new_key_type,
                                $generic_params,
                            ], null, $min_count);
                        } else {
                            $type = new TArray([
                                $new_key_type,
                                $generic_params,
                            ]);
                        }
                    }

                    $array_access_type = Type::combineUnionTypes(
                        $array_access_type,
                        $generic_params,
                    );
                } else {
                    $array_access_type = Type::combineUnionTypes(
                        $array_access_type,
                        $type->getGenericValueType(),
                    );
                }

                $has_valid_offset = true;
            } else {
                if (!$context->inside_isset
                    || ($type->fallback_params === null && !$union_comparison_results->type_coerced)
                ) {
                    $expected_offset_types[] = $generic_key_type->getId();
                }

                $array_access_type = Type::getMixed();
            }
        }
    }

    private static function handleArrayAccessOnNamedObject(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        TNamedObject $type,
        Context $context,
        bool $in_assignment,
        ?PhpParser\Node\Expr $assign_value,
        ?Union &$array_access_type,
        bool &$has_array_access
    ): void {
        if (strtolower($type->value) === 'simplexmlelement') {
            $call_array_access_type = new Union([new TNamedObject('SimpleXMLElement')]);
        } elseif (strtolower($type->value) === 'domnodelist' && $stmt->dim) {
            $old_data_provider = $statements_analyzer->node_data;

            $statements_analyzer->node_data = clone $statements_analyzer->node_data;

            $fake_method_call = new VirtualMethodCall(
                $stmt->var,
                new VirtualIdentifier('item', $stmt->var->getAttributes()),
                [
                    new VirtualArg($stmt->dim),
                ],
            );

            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
            }

            if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['MixedMethodCall']);
            }

            MethodCallAnalyzer::analyze(
                $statements_analyzer,
                $fake_method_call,
                $context,
            );

            if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
            }

            if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['MixedMethodCall']);
            }

            $call_array_access_type = $statements_analyzer->node_data->getType($fake_method_call) ?? Type::getMixed();

            $statements_analyzer->node_data = $old_data_provider;
        } else {
            $suppressed_issues = $statements_analyzer->getSuppressedIssues();

            if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['PossiblyInvalidMethodCall']);
            }

            if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->addSuppressedIssues(['MixedMethodCall']);
            }

            if ($in_assignment) {
                $old_node_data = $statements_analyzer->node_data;

                $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                $fake_set_method_call = new VirtualMethodCall(
                    $stmt->var,
                    new VirtualIdentifier('offsetSet', $stmt->var->getAttributes()),
                    [
                        new VirtualArg(
                            $stmt->dim
                                ?? new VirtualConstFetch(
                                    new VirtualName('null'),
                                    $stmt->var->getAttributes(),
                                ),
                        ),
                        new VirtualArg(
                            $assign_value ?? new VirtualConstFetch(
                                new VirtualName('null'),
                                $stmt->var->getAttributes(),
                            ),
                        ),
                    ],
                );

                MethodCallAnalyzer::analyze(
                    $statements_analyzer,
                    $fake_set_method_call,
                    $context,
                );

                $statements_analyzer->node_data = $old_node_data;
            }

            if ($stmt->dim) {
                $old_node_data = $statements_analyzer->node_data;

                $statements_analyzer->node_data = clone $statements_analyzer->node_data;

                $fake_get_method_call = new VirtualMethodCall(
                    $stmt->var,
                    new VirtualIdentifier('offsetGet', $stmt->var->getAttributes()),
                    [
                        new VirtualArg(
                            $stmt->dim,
                        ),
                    ],
                );

                MethodCallAnalyzer::analyze(
                    $statements_analyzer,
                    $fake_get_method_call,
                    $context,
                );

                $call_array_access_type =
                    $statements_analyzer->node_data->getType($fake_get_method_call) ?? Type::getMixed();

                $statements_analyzer->node_data = $old_node_data;
            } else {
                $call_array_access_type = Type::getVoid();
            }

            $has_array_access = true;

            if (!in_array('PossiblyInvalidMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['PossiblyInvalidMethodCall']);
            }

            if (!in_array('MixedMethodCall', $suppressed_issues, true)) {
                $statements_analyzer->removeSuppressedIssues(['MixedMethodCall']);
            }
        }

        $array_access_type = Type::combineUnionTypes(
            $array_access_type,
            $call_array_access_type,
        );
    }

    /**
     * @param list<string> $expected_offset_types
     */
    private static function handleArrayAccessOnString(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        bool $in_assignment,
        Context $context,
        ?Union $replacement_type,
        TString $type,
        MutableUnion $offset_type,
        array &$expected_offset_types,
        ?Union &$array_access_type,
        bool &$has_valid_offset
    ): void {
        if ($in_assignment && $replacement_type) {
            if ($replacement_type->hasMixed()) {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementMixedCount($statements_analyzer->getFilePath());
                }

                IssueBuffer::maybeAdd(
                    new MixedStringOffsetAssignment(
                        'Right-hand-side of string offset assignment cannot be mixed',
                        new CodeLocation($statements_analyzer->getSource(), $stmt),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            } else {
                if (!$context->collect_initializations
                    && !$context->collect_mutations
                    && $statements_analyzer->getFilePath() === $statements_analyzer->getRootFilePath()
                    && (!(($parent_source = $statements_analyzer->getSource())
                            instanceof FunctionLikeAnalyzer)
                        || !$parent_source->getSource() instanceof TraitAnalyzer)
                ) {
                    $codebase->analyzer->incrementNonMixedCount($statements_analyzer->getFilePath());
                }
            }
        }

        if ($type instanceof TSingleLetter) {
            $valid_offset_type = Type::getInt(false, 0);
        } elseif ($type instanceof TLiteralString) {
            if ($type->value === '') {
                $valid_offset_type = Type::getNever();
            } elseif (strlen($type->value) < 10) {
                $valid_offsets = [];

                for ($i = -strlen($type->value), $l = strlen($type->value); $i < $l; $i++) {
                    $valid_offsets[] = new TLiteralInt($i);
                }

                if (!$valid_offsets) {
                    throw new UnexpectedValueException('This is weird');
                }

                $valid_offset_type = new Union($valid_offsets);
            } else {
                $valid_offset_type = Type::getInt();
            }
        } else {
            $valid_offset_type = Type::getInt();
        }

        if (!UnionTypeComparator::isContainedBy(
            $codebase,
            $offset_type->freeze(),
            $valid_offset_type,
            true,
        )) {
            $expected_offset_types[] = $valid_offset_type->getId();

            $array_access_type = Type::getMixed();
        } else {
            $has_valid_offset = true;

            $array_access_type = Type::combineUnionTypes(
                $array_access_type,
                Type::getSingleLetter(),
            );
        }
    }

    /**
     * @param Atomic[] $offset_types
     */
    private static function checkArrayOffsetType(
        MutableUnion $offset_type,
        array $offset_types,
        Codebase $codebase
    ): bool {
        $has_valid_absolute_offset = false;
        foreach ($offset_types as $atomic_offset_type) {
            if ($atomic_offset_type instanceof TClassConstant) {
                $expanded = TypeExpander::expandAtomic(
                    $codebase,
                    $atomic_offset_type,
                    $atomic_offset_type->fq_classlike_name,
                    $atomic_offset_type->fq_classlike_name,
                    null,
                    true,
                    true,
                );

                $has_valid_absolute_offset = self::checkArrayOffsetType(
                    $offset_type,
                    $expanded,
                    $codebase,
                );

                if ($has_valid_absolute_offset) {
                    break;
                }
            }

            if ($atomic_offset_type instanceof TFalse &&
                $offset_type->ignore_falsable_issues === true
            ) {
                //do nothing
            } elseif ($atomic_offset_type instanceof TNull &&
                $offset_type->ignore_nullable_issues === true
            ) {
                //do nothing
            } elseif ($atomic_offset_type instanceof TString ||
                $atomic_offset_type instanceof TInt ||
                $atomic_offset_type instanceof TArrayKey ||
                $atomic_offset_type instanceof TMixed
            ) {
                $has_valid_absolute_offset = true;
                break;
            } elseif ($atomic_offset_type instanceof TTemplateParam) {
                $has_valid_absolute_offset = self::checkArrayOffsetType(
                    $offset_type,
                    $atomic_offset_type->as->getAtomicTypes(),
                    $codebase,
                );

                if ($has_valid_absolute_offset) {
                    break;
                }
            }
        }
        return $has_valid_absolute_offset;
    }
}
