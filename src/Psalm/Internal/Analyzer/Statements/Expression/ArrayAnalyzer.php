<?php

namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\ConstantTypeResolver;
use Psalm\Internal\Codebase\TaintFlowGraph;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\Comparator\UnionTypeComparator;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Issue\DuplicateArrayKey;
use Psalm\Issue\InvalidArrayOffset;
use Psalm\Issue\InvalidOperand;
use Psalm\Issue\MixedArrayOffset;
use Psalm\Issue\ParseError;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TArrayKey;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TFalse;
use Psalm\Type\Atomic\TFloat;
use Psalm\Type\Atomic\TInt;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralFloat;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TObjectWithProperties;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTrue;
use Psalm\Type\Union;

use function array_merge;
use function array_values;
use function count;
use function filter_var;
use function in_array;
use function is_int;
use function is_numeric;
use function is_string;
use function trim;

use const FILTER_VALIDATE_INT;
use const PHP_INT_MAX;

/**
 * @internal
 */
final class ArrayAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Array_ $stmt,
        Context $context
    ): bool {
        // if the array is empty, this special type allows us to match any other array type against it
        if (count($stmt->items) === 0) {
            $statements_analyzer->node_data->setType($stmt, Type::getEmptyArray());

            return true;
        }

        $codebase = $statements_analyzer->getCodebase();

        $array_creation_info = new ArrayCreationInfo();

        foreach ($stmt->items as $item) {
            if ($item === null) {
                IssueBuffer::maybeAdd(
                    new ParseError(
                        'Array element cannot be empty',
                        new CodeLocation($statements_analyzer, $stmt),
                    ),
                );

                return false;
            }

            self::analyzeArrayItem(
                $statements_analyzer,
                $context,
                $array_creation_info,
                $item,
                $codebase,
            );
        }

        if (count($array_creation_info->item_key_atomic_types) !== 0) {
            $item_key_type = TypeCombiner::combine(
                $array_creation_info->item_key_atomic_types,
                $codebase,
            );
        } else {
            $item_key_type = null;
        }

        if (count($array_creation_info->item_value_atomic_types) !== 0) {
            $item_value_type = TypeCombiner::combine(
                $array_creation_info->item_value_atomic_types,
                $codebase,
            );
        } else {
            $item_value_type = null;
        }

        // if this array looks like an object-like array, let's return that instead
        if (count($array_creation_info->property_types) !== 0) {
            $atomic_type = new TKeyedArray(
                $array_creation_info->property_types,
                $array_creation_info->class_strings,
                $array_creation_info->can_create_objectlike
                    ? null :
                    [$item_key_type ?? Type::getArrayKey(), $item_value_type ?? Type::getMixed()],
                $array_creation_info->all_list,
            );

            $stmt_type = new Union([$atomic_type], [
                'parent_nodes' => $array_creation_info->parent_taint_nodes,
            ]);

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($item_key_type === null && $item_value_type === null) {
            $statements_analyzer->node_data->setType($stmt, Type::getEmptyArray());

            return true;
        }

        if ($array_creation_info->all_list) {
            if ($array_creation_info->can_be_empty) {
                $array_type = Type::getListAtomic($item_value_type ?? Type::getMixed());
            } else {
                $array_type = Type::getNonEmptyListAtomic($item_value_type ?? Type::getMixed());
            }

            $stmt_type = new Union([
                $array_type,
            ], [
                'parent_nodes' => $array_creation_info->parent_taint_nodes,
            ]);

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($item_key_type) {
            $bad_types = [];
            $good_types = [];

            foreach ($item_key_type->getAtomicTypes() as $atomic_key_type) {
                if ($atomic_key_type instanceof TMixed) {
                    IssueBuffer::maybeAdd(
                        new MixedArrayOffset(
                            'Cannot create mixed offset – expecting array-key',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );

                    $bad_types[] = $atomic_key_type;

                    $good_types[] = new TArrayKey;


                    continue;
                }

                if (!$atomic_key_type instanceof TString
                    && !$atomic_key_type instanceof TInt
                    && !$atomic_key_type instanceof TArrayKey
                    && !$atomic_key_type instanceof TTemplateParam
                    && !(
                        $atomic_key_type instanceof TObjectWithProperties
                        && isset($atomic_key_type->methods['__tostring'])
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new InvalidArrayOffset(
                            'Cannot create offset of type ' . $item_key_type->getKey() . ', expecting array-key',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );

                    $bad_types[] = $atomic_key_type;

                    if ($atomic_key_type instanceof TFalse) {
                        $good_types[] = new TLiteralInt(0);
                    } elseif ($atomic_key_type instanceof TTrue) {
                        $good_types[] = new TLiteralInt(1);
                    } elseif ($atomic_key_type instanceof TBool) {
                        $good_types[] = new TLiteralInt(0);
                        $good_types[] = new TLiteralInt(1);
                    } elseif ($atomic_key_type instanceof TLiteralFloat) {
                        $good_types[] = new TLiteralInt((int) $atomic_key_type->value);
                    } elseif ($atomic_key_type instanceof TFloat) {
                        $good_types[] = new TInt;
                    } else {
                        $good_types[] = new TArrayKey;
                    }
                }
            }

            if ($bad_types && $good_types) {
                $item_key_type = $item_key_type->getBuilder()->substitute(
                    TypeCombiner::combine($bad_types, $codebase),
                    TypeCombiner::combine($good_types, $codebase),
                )->freeze();
            }
        }

        $array_args = [
            $item_key_type && !$item_key_type->hasMixed() ? $item_key_type : Type::getArrayKey(),
            $item_value_type ?? Type::getMixed(),
        ];
        $array_type = $array_creation_info->can_be_empty ? new TArray($array_args) : new TNonEmptyArray($array_args);

        $stmt_type = new Union([
            $array_type,
        ], [
            'parent_nodes' => $array_creation_info->parent_taint_nodes,
        ]);

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }

    /**
     * @param string|int $literal_array_key
     * @return false|int
     * @psalm-assert-if-false !numeric $literal_array_key
     */
    public static function getLiteralArrayKeyInt(
        $literal_array_key
    ) {
        if (is_int($literal_array_key)) {
            return $literal_array_key;
        }

        if (!is_numeric($literal_array_key)) {
            return false;
        }

        // PHP 8 values with whitespace after number are counted as numeric
        // and filter_var treats them as such too
        // ensures that '15 ' will stay '15 '
        if (trim($literal_array_key) !== $literal_array_key) {
            return false;
        }

        // '+5' will pass the filter_var check but won't be changed in keys
        if ($literal_array_key[0] === '+') {
            return false;
        }

        // e.g. 015 is numeric but won't be typecast as it's not a valid int
        return filter_var($literal_array_key, FILTER_VALIDATE_INT);
    }

    private static function analyzeArrayItem(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        ArrayCreationInfo $array_creation_info,
        PhpParser\Node\Expr\ArrayItem $item,
        Codebase $codebase
    ): void {
        if ($item->unpack) {
            if (ExpressionAnalyzer::analyze($statements_analyzer, $item->value, $context) === false) {
                return;
            }

            $unpacked_array_type = $statements_analyzer->node_data->getType($item->value);

            if (!$unpacked_array_type) {
                return;
            }

            self::handleUnpackedArray(
                $statements_analyzer,
                $array_creation_info,
                $item,
                $unpacked_array_type,
                $codebase,
            );

            if (($data_flow_graph = $statements_analyzer->data_flow_graph)
                && $data_flow_graph instanceof VariableUseGraph
                && $unpacked_array_type->parent_nodes
            ) {
                $var_location = new CodeLocation($statements_analyzer->getSource(), $item->value);

                $new_parent_node = DataFlowNode::getForAssignment(
                    'array',
                    $var_location,
                );

                $data_flow_graph->addNode($new_parent_node);

                foreach ($unpacked_array_type->parent_nodes as $parent_node) {
                    $data_flow_graph->addPath(
                        $parent_node,
                        $new_parent_node,
                        'arrayvalue-assignment',
                    );
                }

                $array_creation_info->parent_taint_nodes += [$new_parent_node->id => $new_parent_node];
            }

            return;
        }

        $item_key_value = null;
        $item_key_type = null;
        $item_is_list_item = false;

        $array_creation_info->can_be_empty = false;

        if ($item->key) {
            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $item->key, $context) === false) {
                $context->inside_general_use = $was_inside_general_use;

                return;
            }
            $context->inside_general_use = $was_inside_general_use;

            if ($item_key_type = $statements_analyzer->node_data->getType($item->key)) {
                $key_type = $item_key_type;

                if ($key_type->isNull()) {
                    $key_type = Type::getString('');
                }

                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    && self::getLiteralArrayKeyInt($item->key->value) !== false
                ) {
                    $key_type = Type::getInt(false, (int) $item->key->value);
                }

                if ($key_type->isSingleStringLiteral()) {
                    $item_key_literal_type = $key_type->getSingleStringLiteral();
                    $string_to_int = self::getLiteralArrayKeyInt($item_key_literal_type->value);
                    $item_key_value = $string_to_int === false ? $item_key_literal_type->value : $string_to_int;

                    if (is_string($item_key_value) && $item_key_literal_type instanceof TLiteralClassString) {
                        $array_creation_info->class_strings[$item_key_value] = true;
                    }
                } elseif ($key_type->isSingleIntLiteral()) {
                    $item_key_value = $key_type->getSingleIntLiteral()->value;

                    if ($item_key_value <= PHP_INT_MAX
                        && $item_key_value > $array_creation_info->int_offset
                    ) {
                        if ($item_key_value - 1 === $array_creation_info->int_offset) {
                            $item_is_list_item = true;
                        }
                        $array_creation_info->int_offset = $item_key_value;
                    }
                }
            } else {
                $key_type = Type::getArrayKey();
            }
        } else {
            if ($array_creation_info->int_offset === PHP_INT_MAX) {
                IssueBuffer::maybeAdd(
                    new InvalidArrayOffset(
                        'Cannot add an item with an offset beyond PHP_INT_MAX',
                        new CodeLocation($statements_analyzer->getSource(), $item),
                    ),
                );
                return;
            }

            $item_is_list_item = true;
            $item_key_value = ++$array_creation_info->int_offset;

            $key_atomic_type = new TLiteralInt($item_key_value);
            $array_creation_info->item_key_atomic_types[] = $key_atomic_type;
            $key_type = new Union([$key_atomic_type]);
        }

        if (ExpressionAnalyzer::analyze($statements_analyzer, $item->value, $context) === false) {
            return;
        }

        $array_creation_info->all_list = $array_creation_info->all_list && $item_is_list_item;

        if ($item_key_value !== null) {
            if (isset($array_creation_info->array_keys[$item_key_value])) {
                IssueBuffer::maybeAdd(
                    new DuplicateArrayKey(
                        'Key \'' . $item_key_value . '\' already exists on array',
                        new CodeLocation($statements_analyzer->getSource(), $item),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }

            $array_creation_info->array_keys[$item_key_value] = true;
        }

        if (($data_flow_graph = $statements_analyzer->data_flow_graph)
            && ($data_flow_graph instanceof VariableUseGraph
                || !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
        ) {
            if ($item_value_type = $statements_analyzer->node_data->getType($item->value)) {
                if ($item_value_type->parent_nodes
                    && !($item_value_type->isSingle()
                        && $item_value_type->hasLiteralValue()
                        && $data_flow_graph instanceof TaintFlowGraph)
                ) {
                    $var_location = new CodeLocation($statements_analyzer->getSource(), $item);

                    $new_parent_node = DataFlowNode::getForAssignment(
                        'array'
                            . ($item_key_value !== null ? '[\'' . $item_key_value . '\']' : ''),
                        $var_location,
                    );

                    $data_flow_graph->addNode($new_parent_node);

                    $event = new AddRemoveTaintsEvent($item, $context, $statements_analyzer, $codebase);

                    $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                    $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                    foreach ($item_value_type->parent_nodes as $parent_node) {
                        $data_flow_graph->addPath(
                            $parent_node,
                            $new_parent_node,
                            'arrayvalue-assignment'
                                . ($item_key_value !== null ? '-\'' . $item_key_value . '\'' : ''),
                            $added_taints,
                            $removed_taints,
                        );
                    }

                    $array_creation_info->parent_taint_nodes += [$new_parent_node->id => $new_parent_node];
                }

                if ($item_key_type
                    && $item_key_type->parent_nodes
                    && $item_key_value === null
                    && !($item_key_type->isSingle()
                        && $item_key_type->hasLiteralValue()
                        && $data_flow_graph instanceof TaintFlowGraph)
                ) {
                    $var_location = new CodeLocation($statements_analyzer->getSource(), $item);

                    $new_parent_node = DataFlowNode::getForAssignment(
                        'array',
                        $var_location,
                    );

                    $data_flow_graph->addNode($new_parent_node);

                    $event = new AddRemoveTaintsEvent($item, $context, $statements_analyzer, $codebase);

                    $added_taints = $codebase->config->eventDispatcher->dispatchAddTaints($event);
                    $removed_taints = $codebase->config->eventDispatcher->dispatchRemoveTaints($event);

                    foreach ($item_key_type->parent_nodes as $parent_node) {
                        $data_flow_graph->addPath(
                            $parent_node,
                            $new_parent_node,
                            'arraykey-assignment',
                            $added_taints,
                            $removed_taints,
                        );
                    }

                    $array_creation_info->parent_taint_nodes += [$new_parent_node->id => $new_parent_node];
                }
            }
        }

        if ($item->byRef) {
            $var_id = ExpressionIdentifier::getExtendedVarId(
                $item->value,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );

            if ($var_id) {
                if (isset($context->vars_in_scope[$var_id])) {
                    $context->removeDescendents(
                        $var_id,
                        $context->vars_in_scope[$var_id],
                        null,
                        $statements_analyzer,
                    );
                }

                $context->vars_in_scope[$var_id] = Type::getMixed();
            }
        }

        $config = $codebase->config;

        if ($item_value_type = $statements_analyzer->node_data->getType($item->value)) {
            if ($item_key_value !== null
                && count($array_creation_info->property_types) <= $config->max_shaped_array_size
            ) {
                $array_creation_info->property_types[$item_key_value] = $item_value_type;
            } else {
                $array_creation_info->can_create_objectlike = false;
                $array_creation_info->item_key_atomic_types = array_merge(
                    $array_creation_info->item_key_atomic_types,
                    array_values($key_type->getAtomicTypes()),
                );
                $array_creation_info->item_value_atomic_types = array_merge(
                    $array_creation_info->item_value_atomic_types,
                    array_values($item_value_type->getAtomicTypes()),
                );
            }
        } else {
            if ($item_key_value !== null
                && count($array_creation_info->property_types) <= $config->max_shaped_array_size
            ) {
                $array_creation_info->property_types[$item_key_value] = Type::getMixed();
            } else {
                $array_creation_info->can_create_objectlike = false;
                $array_creation_info->item_key_atomic_types = array_merge(
                    $array_creation_info->item_key_atomic_types,
                    array_values($key_type->getAtomicTypes()),
                );
                $array_creation_info->item_value_atomic_types[] = new TMixed();
            }
        }
    }

    private static function handleUnpackedArray(
        StatementsAnalyzer $statements_analyzer,
        ArrayCreationInfo $array_creation_info,
        PhpParser\Node\Expr\ArrayItem $item,
        Union $unpacked_array_type,
        Codebase $codebase
    ): void {
        $all_non_empty = true;

        $has_possibly_undefined = false;
        foreach ($unpacked_array_type->getAtomicTypes() as $unpacked_atomic_type) {
            if ($unpacked_atomic_type instanceof TList) {
                $unpacked_atomic_type = $unpacked_atomic_type->getKeyedArray();
            }
            if ($unpacked_atomic_type instanceof TKeyedArray) {
                foreach ($unpacked_atomic_type->properties as $key => $property_value) {
                    if ($property_value->possibly_undefined) {
                        $has_possibly_undefined = true;
                        continue;
                    }
                    if (is_string($key)) {
                        if ($codebase->analysis_php_version_id <= 8_00_00) {
                            IssueBuffer::maybeAdd(
                                new DuplicateArrayKey(
                                    'String keys are not supported in unpacked arrays',
                                    new CodeLocation($statements_analyzer->getSource(), $item->value),
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );

                            continue 2;
                        }
                        $new_offset = $key;
                        $array_creation_info->item_key_atomic_types[] = Type::getAtomicStringFromLiteral($new_offset);
                        $array_creation_info->all_list = false;
                    } else {
                        if ($array_creation_info->int_offset === PHP_INT_MAX) {
                            IssueBuffer::maybeAdd(
                                new InvalidArrayOffset(
                                    'Cannot add an item with an offset beyond PHP_INT_MAX',
                                    new CodeLocation($statements_analyzer->getSource(), $item->value),
                                ),
                                $statements_analyzer->getSuppressedIssues(),
                            );
                            continue 2;
                        }
                        $new_offset = ++$array_creation_info->int_offset;
                        $array_creation_info->item_key_atomic_types[] = new TLiteralInt($new_offset);
                    }

                    $array_creation_info->array_keys[$new_offset] = true;
                    $array_creation_info->property_types[$new_offset] = $property_value;
                }

                if (!$unpacked_atomic_type->isNonEmpty()) {
                    $all_non_empty = false;
                }

                if ($has_possibly_undefined) {
                    $unpacked_atomic_type = $unpacked_atomic_type->getGenericArrayType();
                } elseif (!$unpacked_atomic_type->fallback_params) {
                    continue;
                }
            } elseif (!$unpacked_atomic_type instanceof TNonEmptyArray) {
                $all_non_empty = false;
            }

            $codebase = $statements_analyzer->getCodebase();

            if (!$unpacked_atomic_type->isIterable($codebase)) {
                $array_creation_info->can_create_objectlike = false;
                $array_creation_info->item_key_atomic_types[] = new TArrayKey();
                $array_creation_info->item_value_atomic_types[] = new TMixed();
                IssueBuffer::maybeAdd(
                    new InvalidOperand(
                        "Cannot use spread operator on non-iterable type {$unpacked_array_type->getId()}",
                        new CodeLocation($statements_analyzer->getSource(), $item->value),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
                continue;
            }

            $iterable_type = $unpacked_atomic_type->getIterable($codebase);

            if ($iterable_type->type_params[0]->isNever()) {
                continue;
            }

            $array_creation_info->can_create_objectlike = false;

            if (!UnionTypeComparator::isContainedBy(
                $codebase,
                $iterable_type->type_params[0],
                Type::getArrayKey(),
            )) {
                IssueBuffer::maybeAdd(
                    new InvalidOperand(
                        "Cannot use spread operator on iterable with key type "
                            . $iterable_type->type_params[0]->getId(),
                        new CodeLocation($statements_analyzer->getSource(), $item->value),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
                continue;
            }

            if ($iterable_type->type_params[0]->hasString()) {
                if ($codebase->analysis_php_version_id <= 8_00_00) {
                    IssueBuffer::maybeAdd(
                        new DuplicateArrayKey(
                            'String keys are not supported in unpacked arrays',
                            new CodeLocation($statements_analyzer->getSource(), $item->value),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );

                    continue;
                }
                $array_creation_info->all_list = false;
            }

            // Unpacked array might overwrite known properties, so values are merged when the keys intersect.
            foreach ($array_creation_info->property_types as $prop_key_val => $prop_val) {
                $prop_key = new Union([ConstantTypeResolver::getLiteralTypeFromScalarValue($prop_key_val)]);
                // Since $prop_key is a single literal type, the types intersect iff $prop_key is contained by the
                // template type (ie $prop_key cannot overlap with the template type without being contained by it).
                if (UnionTypeComparator::isContainedBy($codebase, $prop_key, $iterable_type->type_params[0])) {
                    $new_prop_val = Type::combineUnionTypes($prop_val, $iterable_type->type_params[1]);
                    $array_creation_info->property_types[$prop_key_val] = $new_prop_val;
                }
            }

            $array_creation_info->item_key_atomic_types = array_merge(
                $array_creation_info->item_key_atomic_types,
                array_values($iterable_type->type_params[0]->getAtomicTypes()),
            );
            $array_creation_info->item_value_atomic_types = array_merge(
                $array_creation_info->item_value_atomic_types,
                array_values($iterable_type->type_params[1]->getAtomicTypes()),
            );
        }

        if ($all_non_empty) {
            $array_creation_info->can_be_empty = false;
        }
    }
}
