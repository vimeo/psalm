<?php
namespace Psalm\Internal\Analyzer\Statements\Expression;

use PhpParser;
use Psalm\Codebase;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Issue\DuplicateArrayKey;
use Psalm\Issue\InvalidArrayOffset;
use Psalm\Issue\MixedArrayOffset;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Internal\Type\TypeCombiner;
use Psalm\Plugin\EventHandler\Event\AddRemoveTaintsEvent;

use function preg_match;
use function array_merge;
use function array_values;
use function count;
use const PHP_INT_MAX;

/**
 * @internal
 */
class ArrayAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\Array_ $stmt,
        Context $context
    ) : bool {
        // if the array is empty, this special type allows us to match any other array type against it
        if (empty($stmt->items)) {
            $statements_analyzer->node_data->setType($stmt, Type::getEmptyArray());

            return true;
        }

        $codebase = $statements_analyzer->getCodebase();

        $array_creation_info = new ArrayCreationInfo();

        foreach ($stmt->items as $item) {
            if ($item === null) {
                \Psalm\IssueBuffer::add(
                    new \Psalm\Issue\ParseError(
                        'Array element cannot be empty',
                        new CodeLocation($statements_analyzer, $stmt)
                    )
                );

                return false;
            }

            self::analyzeArrayItem(
                $statements_analyzer,
                $context,
                $array_creation_info,
                $item,
                $codebase
            );
        }

        if ($array_creation_info->item_key_atomic_types) {
            $item_key_type = TypeCombiner::combine(
                $array_creation_info->item_key_atomic_types,
                $codebase,
                false,
                true,
                30
            );
        } else {
            $item_key_type = null;
        }

        if ($array_creation_info->item_value_atomic_types) {
            $item_value_type = TypeCombiner::combine(
                $array_creation_info->item_value_atomic_types,
                $codebase,
                false,
                true,
                30
            );
        } else {
            $item_value_type = null;
        }

        // if this array looks like an object-like array, let's return that instead
        if ($item_value_type
            && $item_key_type
            && ($item_key_type->hasString() || $item_key_type->hasInt())
            && $array_creation_info->can_create_objectlike
            && $array_creation_info->property_types
        ) {
            $object_like = new Type\Atomic\TKeyedArray(
                $array_creation_info->property_types,
                $array_creation_info->class_strings
            );
            $object_like->sealed = true;
            $object_like->is_list = $array_creation_info->all_list;

            $stmt_type = new Type\Union([$object_like]);

            if ($array_creation_info->parent_taint_nodes) {
                $stmt_type->parent_nodes = $array_creation_info->parent_taint_nodes;
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($array_creation_info->all_list) {
            if (empty($array_creation_info->item_key_atomic_types)) {
                $array_type = new Type\Atomic\TList($item_value_type ?: Type::getMixed());
            } else {
                $array_type = new Type\Atomic\TNonEmptyList($item_value_type ?: Type::getMixed());
                $array_type->count = count($array_creation_info->property_types);
            }

            $stmt_type = new Type\Union([
                $array_type,
            ]);

            if ($array_creation_info->parent_taint_nodes) {
                $stmt_type->parent_nodes = $array_creation_info->parent_taint_nodes;
            }

            $statements_analyzer->node_data->setType($stmt, $stmt_type);

            return true;
        }

        if ($item_key_type) {
            $bad_types = [];
            $good_types = [];

            foreach ($item_key_type->getAtomicTypes() as $atomic_key_type) {
                if ($atomic_key_type instanceof Type\Atomic\TMixed) {
                    if (IssueBuffer::accepts(
                        new MixedArrayOffset(
                            'Cannot create mixed offset – expecting array-key',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // do nothing
                    }

                    $bad_types[] = $atomic_key_type;

                    $good_types[] = new Type\Atomic\TArrayKey;


                    continue;
                }

                if (!$atomic_key_type instanceof Type\Atomic\TString
                    && !$atomic_key_type instanceof Type\Atomic\TInt
                    && !$atomic_key_type instanceof Type\Atomic\TArrayKey
                    && !$atomic_key_type instanceof Type\Atomic\TMixed
                    && !$atomic_key_type instanceof Type\Atomic\TTemplateParam
                    && !(
                        $atomic_key_type instanceof Type\Atomic\TObjectWithProperties
                        && isset($atomic_key_type->methods['__toString'])
                    )
                ) {
                    if (IssueBuffer::accepts(
                        new InvalidArrayOffset(
                            'Cannot create offset of type ' . $item_key_type->getKey() . ', expecting array-key',
                            new CodeLocation($statements_analyzer->getSource(), $stmt)
                        ),
                        $statements_analyzer->getSuppressedIssues()
                    )) {
                        // do nothing
                    }

                    $bad_types[] = $atomic_key_type;

                    if ($atomic_key_type instanceof Type\Atomic\TFalse) {
                        $good_types[] = new Type\Atomic\TLiteralInt(0);
                    } elseif ($atomic_key_type instanceof Type\Atomic\TTrue) {
                        $good_types[] = new Type\Atomic\TLiteralInt(1);
                    } elseif ($atomic_key_type instanceof Type\Atomic\TBool) {
                        $good_types[] = new Type\Atomic\TLiteralInt(0);
                        $good_types[] = new Type\Atomic\TLiteralInt(1);
                    } elseif ($atomic_key_type instanceof Type\Atomic\TLiteralFloat) {
                        $good_types[] = new Type\Atomic\TLiteralInt((int) $atomic_key_type->value);
                    } elseif ($atomic_key_type instanceof Type\Atomic\TFloat) {
                        $good_types[] = new Type\Atomic\TInt;
                    } else {
                        $good_types[] = new Type\Atomic\TArrayKey;
                    }
                }
            }

            if ($bad_types && $good_types) {
                $item_key_type->substitute(
                    TypeCombiner::combine($bad_types, $codebase),
                    TypeCombiner::combine($good_types, $codebase)
                );
            }
        }

        $array_type = new Type\Atomic\TNonEmptyArray([
            $item_key_type && !$item_key_type->hasMixed() ? $item_key_type : Type::getArrayKey(),
            $item_value_type ?: Type::getMixed(),
        ]);

        $array_type->count = count($array_creation_info->property_types);

        $stmt_type = new Type\Union([
            $array_type,
        ]);

        if ($array_creation_info->parent_taint_nodes) {
            $stmt_type->parent_nodes = $array_creation_info->parent_taint_nodes;
        }

        $statements_analyzer->node_data->setType($stmt, $stmt_type);

        return true;
    }

    private static function analyzeArrayItem(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        ArrayCreationInfo $array_creation_info,
        PhpParser\Node\Expr\ArrayItem $item,
        Codebase $codebase
    ) : void {
        if (ExpressionAnalyzer::analyze($statements_analyzer, $item->value, $context) === false) {
            return;
        }

        if ($item->unpack) {
            $unpacked_array_type = $statements_analyzer->node_data->getType($item->value);

            if (!$unpacked_array_type) {
                return;
            }

            self::handleUnpackedArray(
                $statements_analyzer,
                $array_creation_info,
                $item,
                $unpacked_array_type
            );

            if (($data_flow_graph = $statements_analyzer->data_flow_graph)
                && $data_flow_graph instanceof \Psalm\Internal\Codebase\VariableUseGraph
                && $unpacked_array_type->parent_nodes
            ) {
                $var_location = new CodeLocation($statements_analyzer->getSource(), $item->value);

                $new_parent_node = \Psalm\Internal\DataFlow\DataFlowNode::getForAssignment(
                    'array',
                    $var_location
                );

                $data_flow_graph->addNode($new_parent_node);

                foreach ($unpacked_array_type->parent_nodes as $parent_node) {
                    $data_flow_graph->addPath(
                        $parent_node,
                        $new_parent_node,
                        'arrayvalue-assignment'
                    );
                }

                $array_creation_info->parent_taint_nodes += [$new_parent_node->id => $new_parent_node];
            }

            return;
        }

        $item_key_value = null;
        $item_key_type = null;
        $item_is_list_item = false;

        if ($item->key) {
            $was_inside_use = $context->inside_use;
            $context->inside_use = true;
            if (ExpressionAnalyzer::analyze($statements_analyzer, $item->key, $context) === false) {
                return;
            }
            $context->inside_use = $was_inside_use;

            if ($item_key_type = $statements_analyzer->node_data->getType($item->key)) {
                $key_type = $item_key_type;

                if ($key_type->isNull()) {
                    $key_type = Type::getString('');
                }

                if ($item->key instanceof PhpParser\Node\Scalar\String_
                    && preg_match('/^(0|[1-9][0-9]*)$/', $item->key->value)
                    && (
                        (int) $item->key->value < PHP_INT_MAX ||
                        $item->key->value === (string) PHP_INT_MAX
                    )
                ) {
                    $key_type = Type::getInt(false, (int) $item->key->value);
                }

                $array_creation_info->item_key_atomic_types = array_merge(
                    $array_creation_info->item_key_atomic_types,
                    array_values($key_type->getAtomicTypes())
                );

                if ($key_type->isSingleStringLiteral()) {
                    $item_key_literal_type = $key_type->getSingleStringLiteral();
                    $item_key_value = $item_key_literal_type->value;

                    if ($item_key_literal_type instanceof Type\Atomic\TLiteralClassString) {
                        $array_creation_info->class_strings[$item_key_value] = true;
                    }
                } elseif ($key_type->isSingleIntLiteral()) {
                    $item_key_value = $key_type->getSingleIntLiteral()->value;

                    if ($item_key_value >= $array_creation_info->int_offset) {
                        if ($item_key_value === $array_creation_info->int_offset) {
                            $item_is_list_item = true;
                        }
                        $array_creation_info->int_offset = $item_key_value + 1;
                    }
                }
            }
        } else {
            $item_is_list_item = true;
            $item_key_value = $array_creation_info->int_offset++;
            $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TLiteralInt($item_key_value);
        }

        $array_creation_info->all_list = $array_creation_info->all_list && $item_is_list_item;

        if ($item_key_value !== null) {
            if (isset($array_creation_info->array_keys[$item_key_value])) {
                if (IssueBuffer::accepts(
                    new DuplicateArrayKey(
                        'Key \'' . $item_key_value . '\' already exists on array',
                        new CodeLocation($statements_analyzer->getSource(), $item)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )) {
                    // fall through
                }
            }

            $array_creation_info->array_keys[$item_key_value] = true;
        }


        if (($data_flow_graph = $statements_analyzer->data_flow_graph)
            && ($data_flow_graph instanceof \Psalm\Internal\Codebase\VariableUseGraph
                || !\in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
        ) {
            if ($item_value_type = $statements_analyzer->node_data->getType($item->value)) {
                if ($item_value_type->parent_nodes
                    && !($item_value_type->isSingle()
                        && $item_value_type->hasLiteralValue()
                        && $data_flow_graph instanceof \Psalm\Internal\Codebase\TaintFlowGraph)
                ) {
                    $var_location = new CodeLocation($statements_analyzer->getSource(), $item);

                    $new_parent_node = \Psalm\Internal\DataFlow\DataFlowNode::getForAssignment(
                        'array'
                            . ($item_key_value !== null ? '[\'' . $item_key_value . '\']' : ''),
                        $var_location
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
                            $removed_taints
                        );
                    }

                    $array_creation_info->parent_taint_nodes += [$new_parent_node->id => $new_parent_node];
                }

                if ($item_key_type
                    && $item_key_type->parent_nodes
                    && $item_key_value === null
                    && !($item_key_type->isSingle()
                        && $item_key_type->hasLiteralValue()
                        && $data_flow_graph instanceof \Psalm\Internal\Codebase\TaintFlowGraph)
                ) {
                    $var_location = new CodeLocation($statements_analyzer->getSource(), $item);

                    $new_parent_node = \Psalm\Internal\DataFlow\DataFlowNode::getForAssignment(
                        'array',
                        $var_location
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
                            $removed_taints
                        );
                    }

                    $array_creation_info->parent_taint_nodes += [$new_parent_node->id => $new_parent_node];
                }
            }
        }

        if ($item->byRef) {
            $var_id = ExpressionIdentifier::getArrayVarId(
                $item->value,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer
            );

            if ($var_id) {
                $context->removeDescendents(
                    $var_id,
                    $context->vars_in_scope[$var_id] ?? null,
                    null,
                    $statements_analyzer
                );

                $context->vars_in_scope[$var_id] = Type::getMixed();
            }
        }

        if ($array_creation_info->item_value_atomic_types && !$array_creation_info->can_create_objectlike) {
            return;
        }

        if ($item_value_type = $statements_analyzer->node_data->getType($item->value)) {
            if ($item_key_value !== null && count($array_creation_info->property_types) <= 100) {
                $array_creation_info->property_types[$item_key_value] = $item_value_type;
            } else {
                $array_creation_info->can_create_objectlike = false;
            }

            $array_creation_info->item_value_atomic_types = array_merge(
                $array_creation_info->item_value_atomic_types,
                array_values($item_value_type->getAtomicTypes())
            );
        } else {
            $array_creation_info->item_value_atomic_types[] = new Type\Atomic\TMixed();

            if ($item_key_value !== null && count($array_creation_info->property_types) <= 100) {
                $array_creation_info->property_types[$item_key_value] = Type::getMixed();
            } else {
                $array_creation_info->can_create_objectlike = false;
            }
        }
    }

    private static function handleUnpackedArray(
        StatementsAnalyzer $statements_analyzer,
        ArrayCreationInfo $array_creation_info,
        PhpParser\Node\Expr\ArrayItem $item,
        Type\Union $unpacked_array_type
    ) : void {
        foreach ($unpacked_array_type->getAtomicTypes() as $unpacked_atomic_type) {
            if ($unpacked_atomic_type instanceof Type\Atomic\TKeyedArray) {
                foreach ($unpacked_atomic_type->properties as $key => $property_value) {
                    if (\is_string($key)) {
                        if (IssueBuffer::accepts(
                            new DuplicateArrayKey(
                                'String keys are not supported in unpacked arrays',
                                new CodeLocation($statements_analyzer->getSource(), $item->value)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }

                        return;
                    }

                    $new_int_offset = $array_creation_info->int_offset++;

                    $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TLiteralInt($new_int_offset);
                    $array_creation_info->item_value_atomic_types = array_merge(
                        $array_creation_info->item_value_atomic_types,
                        array_values($property_value->getAtomicTypes())
                    );

                    $array_creation_info->array_keys[$new_int_offset] = true;
                    $array_creation_info->property_types[$new_int_offset] = $property_value;
                }
            } else {
                $codebase = $statements_analyzer->getCodebase();

                if ($unpacked_atomic_type instanceof Type\Atomic\TArray
                    || $unpacked_atomic_type instanceof Type\Atomic\TIterable
                    || (
                        $unpacked_atomic_type instanceof Type\Atomic\TGenericObject
                        && $unpacked_atomic_type->hasTraversableInterface($codebase)
                        && \count($unpacked_atomic_type->type_params) === 2
                )) {
                    /** @psalm-suppress PossiblyUndefinedArrayOffset provably true, but Psalm can’t see it */
                    if ($unpacked_atomic_type->type_params[1]->isEmpty()) {
                        continue;
                    }
                    $array_creation_info->can_create_objectlike = false;

                    if ($unpacked_atomic_type->type_params[0]->hasString()) {
                        if (IssueBuffer::accepts(
                            new DuplicateArrayKey(
                                'String keys are not supported in unpacked arrays',
                                new CodeLocation($statements_analyzer->getSource(), $item->value)
                            ),
                            $statements_analyzer->getSuppressedIssues()
                        )) {
                            // fall through
                        }
                    } elseif ($unpacked_atomic_type->type_params[0]->hasInt()) {
                        $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TInt();
                    }

                    $array_creation_info->item_value_atomic_types = array_merge(
                        $array_creation_info->item_value_atomic_types,
                        array_values(
                            isset($unpacked_atomic_type->type_params[1])
                                ? $unpacked_atomic_type->type_params[1]->getAtomicTypes()
                                : [new Type\Atomic\TMixed()]
                        )
                    );
                } elseif ($unpacked_atomic_type instanceof Type\Atomic\TList) {
                    if ($unpacked_atomic_type->type_param->isEmpty()) {
                        continue;
                    }
                    $array_creation_info->can_create_objectlike = false;

                    $array_creation_info->item_key_atomic_types[] = new Type\Atomic\TInt();

                    $array_creation_info->item_value_atomic_types = array_merge(
                        $array_creation_info->item_value_atomic_types,
                        array_values($unpacked_atomic_type->type_param->getAtomicTypes())
                    );
                }
            }
        }
    }
}
