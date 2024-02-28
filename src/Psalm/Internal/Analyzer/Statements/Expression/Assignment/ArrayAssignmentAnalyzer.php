<?php

namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use InvalidArgumentException;
use PhpParser;
use PhpParser\Node\Expr\Variable;
use Psalm\CodeLocation;
use Psalm\Codebase;
use Psalm\Context;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\Codebase\VariableUseGraph;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Internal\Type\TemplateInferredTypeReplacer;
use Psalm\Internal\Type\TemplateResult;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\IssueBuffer;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TClassStringMap;
use Psalm\Type\Atomic\TDependentListKey;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TLiteralClassString;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TString;
use Psalm\Type\Atomic\TTemplateIndexedAccess;
use Psalm\Type\Atomic\TTemplateKeyOf;
use Psalm\Type\Atomic\TTemplateParam;
use Psalm\Type\Atomic\TTemplateParamClass;
use Psalm\Type\Union;

use function array_fill;
use function array_pop;
use function array_reverse;
use function array_shift;
use function array_slice;
use function assert;
use function count;
use function end;
use function implode;
use function in_array;
use function is_string;
use function preg_match;
use function strlen;
use function strpos;

/**
 * @internal
 */
final class ArrayAssignmentAnalyzer
{
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        ?PhpParser\Node\Expr $assign_value,
        Union $assignment_value_type
    ): void {
        $nesting = 0;
        $var_id = ExpressionIdentifier::getVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
            $nesting,
        );

        self::updateArrayType(
            $statements_analyzer,
            $stmt,
            $assign_value,
            $assignment_value_type,
            $context,
        );

        if (!$statements_analyzer->node_data->getType($stmt->var) && $var_id) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }
    }

    /**
     * @return false|null
     * @psalm-suppress PossiblyUnusedReturnValue not used but seems important
     */
    public static function updateArrayType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        ?PhpParser\Node\Expr $assign_value,
        Union $assignment_type,
        Context $context
    ): ?bool {
        $root_array_expr = $stmt;

        $child_stmts = [];

        while ($root_array_expr->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $child_stmts[] = $root_array_expr;
            $root_array_expr = $root_array_expr->var;
        }

        $child_stmts[] = $root_array_expr;
        $root_array_expr = $root_array_expr->var;

        ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $root_array_expr,
            $context,
            true,
        );

        $codebase = $statements_analyzer->getCodebase();

        $root_type = $statements_analyzer->node_data->getType($root_array_expr) ?? Type::getMixed();

        if ($root_type->hasMixed()) {
            ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $context,
                true,
            );

            if ($stmt->dim) {
                ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->dim,
                    $context,
                );
            }
        }

        $current_type = $root_type;

        $current_dim = $stmt->dim;

        // gets a variable id that *may* contain array keys
        $root_var_id = ExpressionIdentifier::getExtendedVarId(
            $root_array_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
        );

        $parent_var_id = null;

        $offset_already_existed = false;

        self::analyzeNestedArrayAssignment(
            $statements_analyzer,
            $codebase,
            $context,
            $assign_value,
            $assignment_type,
            $child_stmts,
            $root_var_id,
            $parent_var_id,
            $root_type,
            $current_type,
            $current_dim,
            $offset_already_existed,
        );

        $root_is_string = $root_type->isString();
        $key_values = [];

        if ($current_dim instanceof PhpParser\Node\Scalar\String_) {
            $value_type = Type::getAtomicStringFromLiteral($current_dim->value);
            if ($value_type instanceof TLiteralString) {
                $key_values[] = $value_type;
            }
        } elseif ($current_dim instanceof PhpParser\Node\Scalar\LNumber && !$root_is_string) {
            $key_values[] = new TLiteralInt($current_dim->value);
        } elseif ($current_dim
            && ($key_type = $statements_analyzer->node_data->getType($current_dim))
            && !$root_is_string
        ) {
            $string_literals = $key_type->getLiteralStrings();
            $int_literals = $key_type->getLiteralInts();

            $all_atomic_types = $key_type->getAtomicTypes();

            if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                foreach ($string_literals as $string_literal) {
                    $key_values[] = $string_literal;
                }

                foreach ($int_literals as $int_literal) {
                    $key_values[] = $int_literal;
                }
            }
        }

        if ($key_values) {
            $new_child_type = self::updateTypeWithKeyValues(
                $codebase,
                $root_type,
                $current_type,
                $key_values,
            );
        } elseif (!$root_is_string) {
            $new_child_type = self::updateArrayAssignmentChildType(
                $statements_analyzer,
                $codebase,
                $current_dim,
                $context,
                $current_type,
                $root_type,
                $offset_already_existed,
                $parent_var_id,
            );
        } else {
            $new_child_type = $root_type;
        }

        $new_child_type = $new_child_type->getBuilder();
        $new_child_type->removeType('null');
        $new_child_type = $new_child_type->freeze();

        if (!$root_type->hasObjectType()) {
            $root_type = $new_child_type;
        }

        $statements_analyzer->node_data->setType($root_array_expr, $root_type);

        if ($root_array_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            if ($root_array_expr->name instanceof PhpParser\Node\Identifier) {
                InstancePropertyAssignmentAnalyzer::analyze(
                    $statements_analyzer,
                    $root_array_expr,
                    $root_array_expr->name->name,
                    null,
                    $root_type,
                    $context,
                    false,
                );
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->name, $context) === false) {
                    return false;
                }

                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->var, $context) === false) {
                    return false;
                }
            }
        } elseif ($root_array_expr instanceof PhpParser\Node\Expr\StaticPropertyFetch
            && $root_array_expr->name instanceof PhpParser\Node\Identifier
        ) {
            if (StaticPropertyAssignmentAnalyzer::analyze(
                $statements_analyzer,
                $root_array_expr,
                null,
                $root_type,
                $context,
            ) === false) {
                return false;
            }
        } elseif ($root_var_id) {
            $context->vars_in_scope[$root_var_id] = $root_type;
        }

        if ($root_array_expr instanceof PhpParser\Node\Expr\MethodCall
            || $root_array_expr instanceof PhpParser\Node\Expr\StaticCall
            || $root_array_expr instanceof PhpParser\Node\Expr\FuncCall
        ) {
            if ($root_type->hasArray()) {
                IssueBuffer::maybeAdd(
                    new InvalidArrayAssignment(
                        'Assigning to the output of a function has no effect',
                        new CodeLocation($statements_analyzer->getSource(), $root_array_expr),
                    ),
                    $statements_analyzer->getSuppressedIssues(),
                );
            }
        }

        return null;
    }

    /**
     * @param non-empty-list<TLiteralInt|TLiteralString> $key_values
     */
    private static function updateTypeWithKeyValues(
        Codebase $codebase,
        Union $child_stmt_type,
        Union $current_type,
        array $key_values
    ): Union {
        $has_matching_objectlike_property = false;
        $has_matching_string = false;

        $changed = false;
        $types = [];
        foreach ($child_stmt_type->getAtomicTypes() as $type) {
            if ($type instanceof TList) {
                $type = $type->getKeyedArray();
            }
            $old_type = $type;
            if ($type instanceof TTemplateParam) {
                $type = $type->replaceAs(self::updateTypeWithKeyValues(
                    $codebase,
                    $type->as,
                    $current_type,
                    $key_values,
                ));
                $has_matching_objectlike_property = true;
            } elseif ($type instanceof TKeyedArray) {
                $properties = $type->properties;
                foreach ($key_values as $key_value) {
                    if (isset($properties[$key_value->value])) {
                        $has_matching_objectlike_property = true;

                        $properties[$key_value->value] = $current_type;
                    }
                }
                $type = $type->setProperties($properties);
            } elseif ($type instanceof TString) {
                foreach ($key_values as $key_value) {
                    if ($key_value instanceof TLiteralInt) {
                        $has_matching_string = true;

                        if ($type instanceof TLiteralString
                            && $current_type->isSingleStringLiteral()
                        ) {
                            $new_char = $current_type->getSingleStringLiteral()->value;

                            if (strlen($new_char) === 1 && $type->value[0] !== $new_char) {
                                $v = $type->value;
                                $v[0] = $new_char;
                                $changed = true;
                                $type = Type::getAtomicStringFromLiteral($v);
                                break;
                            }
                        }
                    }
                }
            }
            $types[$type->getKey()] = $type;
            $changed = $changed || $old_type !== $type;
        }

        if ($changed) {
            $child_stmt_type = $child_stmt_type->getBuilder()->setTypes($types)->freeze();
        }

        if (!$has_matching_objectlike_property && !$has_matching_string) {
            $properties = [];
            $classStrings = [];
            $current_type = $current_type->setPossiblyUndefined(
                $current_type->possibly_undefined || count($key_values) > 1,
            );
            foreach ($key_values as $key_value) {
                $properties[$key_value->value] = $current_type;
                if ($key_value instanceof TLiteralClassString) {
                    $classStrings[$key_value->value] = true;
                }
            }
            $object_like = new TKeyedArray(
                $properties,
                $classStrings ?: null,
            );

            $array_assignment_type = new Union([
                $object_like,
            ]);

            return Type::combineUnionTypes(
                $child_stmt_type,
                $array_assignment_type,
                $codebase,
                true,
                false,
            );
        }

        return $child_stmt_type;
    }

    /**
     * @param list<TLiteralInt|TLiteralString> $key_values $key_values
     */
    private static function taintArrayAssignment(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $expr,
        Union &$stmt_type,
        Union $child_stmt_type,
        ?string $var_var_id,
        array $key_values
    ): void {
        if ($statements_analyzer->data_flow_graph
            && ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
                || !in_array('TaintedInput', $statements_analyzer->getSuppressedIssues()))
        ) {
            $var_location = new CodeLocation($statements_analyzer->getSource(), $expr->var);

            $parent_node = DataFlowNode::getForAssignment(
                $var_var_id ?: 'assignment',
                $var_location,
            );

            $statements_analyzer->data_flow_graph->addNode($parent_node);

            $old_parent_nodes = $stmt_type->parent_nodes;

            $stmt_type = $stmt_type->setParentNodes([$parent_node->id => $parent_node]);

            foreach ($old_parent_nodes as $old_parent_node) {
                $statements_analyzer->data_flow_graph->addPath(
                    $old_parent_node,
                    $parent_node,
                    '=',
                );

                if ($stmt_type->by_ref) {
                    $statements_analyzer->data_flow_graph->addPath(
                        $parent_node,
                        $old_parent_node,
                        '=',
                    );
                }
            }

            foreach ($stmt_type->parent_nodes as $parent_node) {
                foreach ($child_stmt_type->parent_nodes as $child_parent_node) {
                    if ($key_values) {
                        foreach ($key_values as $key_value) {
                            $statements_analyzer->data_flow_graph->addPath(
                                $child_parent_node,
                                $parent_node,
                                'arrayvalue-assignment-\'' . $key_value->value . '\'',
                            );
                        }
                    } else {
                        $statements_analyzer->data_flow_graph->addPath(
                            $child_parent_node,
                            $parent_node,
                            'arrayvalue-assignment',
                        );
                    }
                }
            }
        }
    }

    private static function updateArrayAssignmentChildType(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        ?PhpParser\Node\Expr $current_dim,
        Context $context,
        Union $value_type,
        Union $root_type,
        bool $offset_already_existed,
        ?string $parent_var_id
    ): Union {
        $templated_assignment = false;

        $array_atomic_type_class_string = null;
        $array_atomic_type_array = null;
        $array_atomic_type_list = null;
        if ($current_dim) {
            $key_type = $statements_analyzer->node_data->getType($current_dim);

            if ($key_type) {
                if ($key_type->hasMixed()) {
                    $key_type = Type::getArrayKey();
                }

                if ($key_type->isSingle()) {
                    $key_type_type = $key_type->getSingleAtomic();

                    if (($key_type_type instanceof TIntRange
                        && $key_type_type->dependent_list_key === $parent_var_id
                    ) || ($key_type_type instanceof TDependentListKey
                        && $key_type_type->var_id === $parent_var_id
                    )) {
                        $offset_already_existed = true;
                    }

                    if ($key_type_type instanceof TTemplateParam
                        && $key_type_type->as->isSingle()
                        && $root_type->isSingle()
                        && $value_type->isSingle()
                    ) {
                        $key_type_as_type = $key_type_type->as->getSingleAtomic();
                        $value_atomic_type = $value_type->getSingleAtomic();
                        $root_atomic_type = $root_type->getSingleAtomic();

                        if ($key_type_as_type instanceof TTemplateKeyOf
                            && $root_atomic_type instanceof TTemplateParam
                            && $value_atomic_type instanceof TTemplateIndexedAccess
                            && $key_type_as_type->param_name === $root_atomic_type->param_name
                            && $key_type_as_type->defining_class === $root_atomic_type->defining_class
                            && $value_atomic_type->array_param_name === $root_atomic_type->param_name
                            && $value_atomic_type->offset_param_name === $key_type_type->param_name
                            && $value_atomic_type->defining_class === $root_atomic_type->defining_class
                        ) {
                            $templated_assignment = true;
                            $offset_already_existed = true;
                        }
                    }
                }

                $array_atomic_key_type = ArrayFetchAnalyzer::replaceOffsetTypeWithInts($key_type);
            } else {
                $array_atomic_key_type = Type::getArrayKey();
            }

            if ($parent_var_id && ($parent_type = $context->vars_in_scope[$parent_var_id] ?? null)) {
                if ($offset_already_existed && $parent_type->hasList() && strpos($parent_var_id, '[') === false) {
                    $array_atomic_type_list = $value_type;
                } elseif ($parent_type->hasClassStringMap()
                    && $key_type
                    && $key_type->isTemplatedClassString()
                ) {
                    /**
                     * @var TClassStringMap
                     */
                    $class_string_map = $parent_type->getArray();
                    /**
                     * @var TTemplateParamClass
                     */
                    $offset_type_part = $key_type->getSingleAtomic();

                    $template_result = new TemplateResult(
                        [],
                        [
                            $offset_type_part->param_name => [
                                $offset_type_part->defining_class => new Union([
                                    new TTemplateParam(
                                        $class_string_map->param_name,
                                        $offset_type_part->as_type
                                            ? new Union([$offset_type_part->as_type])
                                            : Type::getObject(),
                                        'class-string-map',
                                    ),
                                ]),
                            ],
                        ],
                    );

                    $value_type = TemplateInferredTypeReplacer::replace(
                        $value_type,
                        $template_result,
                        $codebase,
                    );

                    $array_atomic_type_class_string = new TClassStringMap(
                        $class_string_map->param_name,
                        $class_string_map->as_type,
                        $value_type,
                    );
                } else {
                    $array_atomic_type_array = [
                        $array_atomic_key_type,
                        $value_type,
                    ];
                }
            } else {
                $array_atomic_type_array = [
                    $array_atomic_key_type,
                    $value_type,
                ];
            }
        } else {
            $array_atomic_type_list = $value_type;
        }

        $from_countable_object_like = false;

        $new_child_type = null;

        $array_atomic_type = null;
        if (!$current_dim && !$context->inside_loop) {
            $atomic_root_types = $root_type->getAtomicTypes();

            if (isset($atomic_root_types['array'])) {
                $atomic_root_type_array = $atomic_root_types['array'];
                if ($atomic_root_type_array instanceof TList) {
                    $atomic_root_type_array = $atomic_root_type_array->getKeyedArray();
                }

                if ($array_atomic_type_class_string) {
                    $array_atomic_type = new TNonEmptyArray([
                        $array_atomic_type_class_string->getStandinKeyParam(),
                        $array_atomic_type_class_string->value_param,
                    ]);
                } elseif ($atomic_root_type_array instanceof TKeyedArray
                    && $atomic_root_type_array->is_list
                    && $atomic_root_type_array->fallback_params === null
                ) {
                    $array_atomic_type = $atomic_root_type_array;
                } elseif ($atomic_root_type_array instanceof TNonEmptyArray
                    || ($atomic_root_type_array instanceof TKeyedArray
                        && $atomic_root_type_array->is_list
                        && $atomic_root_type_array->isNonEmpty()
                    )
                ) {
                    $prop_count = null;
                    if ($atomic_root_type_array instanceof TNonEmptyArray) {
                        $prop_count = $atomic_root_type_array->count;
                    } else {
                        $min_count = $atomic_root_type_array->getMinCount();
                        if ($min_count === $atomic_root_type_array->getMaxCount()) {
                            $prop_count = $min_count;
                        }
                    }
                    if ($array_atomic_type_array) {
                        $array_atomic_type = new TNonEmptyArray(
                            $array_atomic_type_array,
                            $prop_count,
                        );
                    } elseif ($prop_count !== null) {
                        assert($array_atomic_type_list !== null);
                        $array_atomic_type = new TKeyedArray(
                            array_fill(
                                0,
                                $prop_count,
                                $array_atomic_type_list,
                            ),
                            null,
                            [
                                Type::getListKey(),
                                $array_atomic_type_list,
                            ],
                            true,
                        );
                    }
                } elseif ($atomic_root_type_array instanceof TKeyedArray
                    && $atomic_root_type_array->fallback_params === null
                ) {
                    if ($array_atomic_type_array) {
                        $array_atomic_type = new TNonEmptyArray(
                            $array_atomic_type_array,
                            count($atomic_root_type_array->properties),
                        );
                    } elseif ($atomic_root_type_array->is_list) {
                        $array_atomic_type = $atomic_root_type_array;
                        $new_child_type = new Union([$array_atomic_type], [
                            'parent_nodes' => $root_type->parent_nodes,
                        ]);
                    } else {
                        assert($array_atomic_type_list !== null);
                        $array_atomic_type = array_fill(
                            $atomic_root_type_array->getMinCount(),
                            count($atomic_root_type_array->properties)-1,
                            $array_atomic_type_list,
                        );
                        assert(count($array_atomic_type) > 0);
                        $array_atomic_type = new TKeyedArray(
                            $array_atomic_type,
                            null,
                            null,
                            true,
                        );
                    }
                    $from_countable_object_like = true;
                } elseif ($array_atomic_type_list) {
                    $array_atomic_type = Type::getNonEmptyListAtomic(
                        $array_atomic_type_list,
                    );
                } else {
                    assert($array_atomic_type_array !== null);
                    $array_atomic_type = new TNonEmptyArray(
                        $array_atomic_type_array,
                    );
                }
            }
        }

        $array_atomic_type ??= $array_atomic_type_class_string
            ?? ($array_atomic_type_list !== null
                ? Type::getNonEmptyListAtomic($array_atomic_type_list)
                : null
            ) ?? ($array_atomic_type_array !== null
                ? new TNonEmptyArray($array_atomic_type_array)
                : null
            )
        ;
        assert($array_atomic_type !== null);

        $array_assignment_type = new Union([$array_atomic_type]);

        if (!$new_child_type) {
            if ($templated_assignment) {
                $new_child_type = $root_type;
            } else {
                $new_child_type = Type::combineUnionTypes(
                    $root_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    true,
                );
            }
        }

        if ($from_countable_object_like) {
            $atomic_root_types = $new_child_type->getAtomicTypes();

            if (isset($atomic_root_types['array'])) {
                $atomic_root_type_array = $atomic_root_types['array'];
                if ($atomic_root_type_array instanceof TList) {
                    $atomic_root_type_array = $atomic_root_type_array->getKeyedArray();
                }

                if ($atomic_root_type_array instanceof TNonEmptyArray
                    && $atomic_root_type_array->count !== null
                ) {
                    $atomic_root_types['array'] =
                        $atomic_root_type_array->setCount($atomic_root_type_array->count+1);
                    $new_child_type = new Union($atomic_root_types);
                } elseif ($atomic_root_type_array instanceof TKeyedArray
                    && $atomic_root_type_array->is_list) {
                    $properties = $atomic_root_type_array->properties;
                    $had_undefined = false;
                    foreach ($properties as &$property) {
                        if ($property->possibly_undefined) {
                            $property = $property->setPossiblyUndefined(true);
                            $had_undefined = true;
                            break;
                        }
                    }

                    if (!$had_undefined && $atomic_root_type_array->fallback_params) {
                        $properties []= $atomic_root_type_array->fallback_params[1];
                    }

                    $atomic_root_types['array'] =
                        $atomic_root_type_array->setProperties($properties);

                    $new_child_type = new Union($atomic_root_types);
                }
            }
        }

        return $new_child_type;
    }

    /**
     * @param  non-empty-list<PhpParser\Node\Expr\ArrayDimFetch>  $child_stmts
     * @param-out PhpParser\Node\Expr $child_stmt
     */
    private static function analyzeNestedArrayAssignment(
        StatementsAnalyzer $statements_analyzer,
        Codebase $codebase,
        Context $context,
        ?PhpParser\Node\Expr $assign_value,
        Union $assignment_type,
        array $child_stmts,
        ?string $root_var_id,
        ?string &$parent_var_id,
        Union &$root_type,
        Union &$current_type,
        ?PhpParser\Node\Expr &$current_dim,
        bool &$offset_already_existed
    ): void {
        $var_id_additions = [];

        $root_var = end($child_stmts)->var;

        // First go from the root element up, and go as far as we can to figure out what
        // array types there are
        foreach (array_reverse($child_stmts) as $i => $child_stmt) {
            $child_stmt_dim_type = null;

            $offset_type = null;

            if ($child_stmt->dim) {
                $was_inside_general_use = $context->inside_general_use;
                $context->inside_general_use = true;

                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $child_stmt->dim,
                    $context,
                ) === false) {
                    $context->inside_general_use = $was_inside_general_use;

                    return;
                }

                $context->inside_general_use = $was_inside_general_use;

                if (!($child_stmt_dim_type = $statements_analyzer->node_data->getType($child_stmt->dim))) {
                    return;
                }

                [$offset_type, $var_id_addition, $full_var_id] = self::getArrayAssignmentOffsetType(
                    $statements_analyzer,
                    $child_stmt,
                    $child_stmt_dim_type,
                );

                $var_id_additions[] = $var_id_addition;
            } else {
                $var_id_additions[] = '';
                $full_var_id = false;
            }

            if (!($array_type = $statements_analyzer->node_data->getType($child_stmt->var))) {
                return;
            }

            if ($array_type->isNever()) {
                $array_type = Type::getEmptyArray();
                $statements_analyzer->node_data->setType($child_stmt->var, $array_type);
            }

            $extended_var_id = $root_var_id . implode('', $var_id_additions);

            if ($parent_var_id && isset($context->vars_in_scope[$parent_var_id])) {
                $array_type = $context->vars_in_scope[$parent_var_id];
                $statements_analyzer->node_data->setType($child_stmt->var, $array_type);
            }

            $is_last = $i === count($child_stmts) - 1;

            $child_stmt_dim_type_or_int = $child_stmt_dim_type ?? Type::getInt();
            $child_stmt_type = ArrayFetchAnalyzer::getArrayAccessTypeGivenOffset(
                $statements_analyzer,
                $child_stmt,
                $array_type,
                $child_stmt_dim_type_or_int,
                true,
                $extended_var_id,
                $context,
                $assign_value,
                !$is_last ? null : $assignment_type,
            );
            if ($child_stmt->dim) {
                $statements_analyzer->node_data->setType(
                    $child_stmt->dim,
                    $child_stmt_dim_type_or_int,
                );
            }

            $statements_analyzer->node_data->setType(
                $child_stmt,
                $child_stmt_type,
            );

            if ($is_last) {
                // we need this slight hack as the type we're putting it has to be
                // different from the type we're getting out
                if ($array_type->isSingle() && $array_type->hasClassStringMap()) {
                    $assignment_type = $child_stmt_type;
                }

                $child_stmt_type = $assignment_type;
                $statements_analyzer->node_data->setType($child_stmt, $assignment_type);

                if ($statements_analyzer->data_flow_graph) {
                    self::taintArrayAssignment(
                        $statements_analyzer,
                        $child_stmt,
                        $array_type,
                        $assignment_type,
                        ExpressionIdentifier::getExtendedVarId(
                            $child_stmt->var,
                            $statements_analyzer->getFQCLN(),
                            $statements_analyzer,
                        ),
                        $offset_type !== null ? [$offset_type] : [],
                    );
                }
            }

            $statements_analyzer->node_data->setType($child_stmt->var, $array_type);

            if ($root_var_id) {
                if (!$parent_var_id) {
                    $rooted_parent_id = $root_var_id;
                    $root_type = $array_type;
                } else {
                    $rooted_parent_id = $parent_var_id;
                }

                $context->vars_in_scope[$rooted_parent_id] = $array_type;
                $context->possibly_assigned_var_ids[$rooted_parent_id] = true;
            }

            $current_type = $child_stmt_type;
            $current_dim = $child_stmt->dim;

            $parent_var_id = $extended_var_id;
        }

        if ($statements_analyzer->data_flow_graph instanceof VariableUseGraph
            && $root_var_id !== null
            && isset($context->references_to_external_scope[$root_var_id])
            && $root_var instanceof Variable && is_string($root_var->name)
            && $root_var_id === '$' . $root_var->name
        ) {
            // Array is a reference to an external scope, mark it as used
            $statements_analyzer->data_flow_graph->addPath(
                DataFlowNode::getForAssignment(
                    $root_var_id,
                    new CodeLocation($statements_analyzer->getSource(), $root_var),
                ),
                new DataFlowNode('variable-use', 'variable use', null),
                'variable-use',
            );
        }

        if ($root_var_id
            && $full_var_id
            && ($child_stmt_var_type = $statements_analyzer->node_data->getType($child_stmt->var))
            && !$child_stmt_var_type->hasObjectType()
        ) {
            $extended_var_id = $root_var_id . implode('', $var_id_additions);
            $parent_var_id = $root_var_id . implode('', array_slice($var_id_additions, 0, -1));

            if (isset($context->vars_in_scope[$extended_var_id])
                && !$context->vars_in_scope[$extended_var_id]->possibly_undefined
            ) {
                $offset_already_existed = true;
            }

            $context->vars_in_scope[$extended_var_id] = $assignment_type;
            $context->possibly_assigned_var_ids[$extended_var_id] = true;
        }

        array_shift($child_stmts);

        // only update as many child stmts are we were able to process above
        foreach ($child_stmts as $child_stmt) {
            $child_stmt_type = $statements_analyzer->node_data->getType($child_stmt);

            if (!$child_stmt_type) {
                throw new InvalidArgumentException('Should never get here');
            }

            $key_values = $current_dim ? self::getDimKeyValues($statements_analyzer, $current_dim) : [];

            if ($key_values) {
                $new_child_type = self::updateTypeWithKeyValues(
                    $codebase,
                    $child_stmt_type,
                    $current_type,
                    $key_values,
                );
            } else {
                if (!$current_dim) {
                    $array_assignment_type = Type::getList($current_type);
                } else {
                    $key_type = $statements_analyzer->node_data->getType($current_dim);

                    $array_assignment_type = new Union([
                        new TArray([
                            $key_type && !$key_type->hasMixed()
                                ? $key_type
                                : Type::getArrayKey(),
                            $current_type,
                        ]),
                    ]);
                }

                $new_child_type = Type::combineUnionTypes(
                    $child_stmt_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    true,
                );
            }
            if ($new_child_type->hasNull() || $new_child_type->possibly_undefined) {
                $new_child_type = $new_child_type->getBuilder();
                $new_child_type->removeType('null');
                $new_child_type->possibly_undefined = false;
                $new_child_type = $new_child_type->freeze();
            }
            if (!$child_stmt_type->hasObjectType()) {
                $child_stmt_type = $new_child_type;
                $statements_analyzer->node_data->setType($child_stmt, $new_child_type);
            }

            $current_type = $child_stmt_type;
            $current_dim = $child_stmt->dim;

            array_pop($var_id_additions);

            $parent_array_var_id = null;

            if ($root_var_id) {
                $extended_var_id = $root_var_id . implode('', $var_id_additions);
                $parent_array_var_id = $root_var_id . implode('', array_slice($var_id_additions, 0, -1));
                $context->vars_in_scope[$extended_var_id] = $child_stmt_type;
                $context->possibly_assigned_var_ids[$extended_var_id] = true;
            }

            if ($statements_analyzer->data_flow_graph) {
                $t_orig = $statements_analyzer->node_data->getType($child_stmt->var);
                $array_type = $t_orig ?? Type::getMixed();
                self::taintArrayAssignment(
                    $statements_analyzer,
                    $child_stmt,
                    $array_type,
                    $new_child_type,
                    $parent_array_var_id,
                    $child_stmt->dim ? self::getDimKeyValues($statements_analyzer, $child_stmt->dim) : [],
                );
                if ($t_orig) {
                    $statements_analyzer->node_data->setType($child_stmt->var, $array_type);
                }
                if ($root_var_id) {
                    if ($parent_array_var_id === $root_var_id) {
                        $rooted_parent_id = $root_var_id;
                        $root_type = $array_type;
                    } else {
                        assert($parent_array_var_id !== null);
                        $rooted_parent_id = $parent_array_var_id;
                    }

                    $context->vars_in_scope[$rooted_parent_id] = $array_type;
                }
            }
        }
    }

    /**
     * @return list<TLiteralInt|TLiteralString>
     */
    private static function getDimKeyValues(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr $dim
    ): array {
        $key_values = [];

        if ($dim instanceof PhpParser\Node\Scalar\String_) {
            $value_type = Type::getAtomicStringFromLiteral($dim->value);
            if ($value_type instanceof TLiteralString) {
                $key_values[] = $value_type;
            }
        } elseif ($dim instanceof PhpParser\Node\Scalar\LNumber) {
            $key_values[] = new TLiteralInt($dim->value);
        } else {
            $key_type = $statements_analyzer->node_data->getType($dim);

            if ($key_type) {
                $string_literals = $key_type->getLiteralStrings();
                $int_literals = $key_type->getLiteralInts();

                $all_atomic_types = $key_type->getAtomicTypes();

                if (count($string_literals) + count($int_literals) === count($all_atomic_types)) {
                    foreach ($string_literals as $string_literal) {
                        $key_values[] = $string_literal;
                    }

                    foreach ($int_literals as $int_literal) {
                        $key_values[] = $int_literal;
                    }
                }
            }
        }

        return $key_values;
    }

    /**
     * @return array{TLiteralInt|TLiteralString|null, string, bool}
     */
    private static function getArrayAssignmentOffsetType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $child_stmt,
        Union $child_stmt_dim_type
    ): array {
        if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_
            || (($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                    || $child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch)
                && $child_stmt_dim_type->isSingleStringLiteral())
        ) {
            if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                $offset_type = Type::getAtomicStringFromLiteral($child_stmt->dim->value);
                if (!$offset_type instanceof TLiteralString) {
                    return [null, '[string]', false];
                }
            } else {
                $offset_type = $child_stmt_dim_type->getSingleStringLiteral();
            }

            if (preg_match('/^(0|[1-9][0-9]*)$/', $offset_type->value)) {
                $var_id_addition = '[' . $offset_type->value . ']';
            } else {
                $var_id_addition = '[\'' . $offset_type->value . '\']';
            }

            return [$offset_type, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber
            || (($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                    || $child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch)
                && $child_stmt_dim_type->isSingleIntLiteral())
        ) {
            if ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber) {
                $offset_type = new TLiteralInt($child_stmt->dim->value);
            } else {
                $offset_type = $child_stmt_dim_type->getSingleIntLiteral();
            }

            $var_id_addition = '[' . $offset_type->value . ']';

            return [$offset_type, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Expr\Variable
            && is_string($child_stmt->dim->name)
        ) {
            $var_id_addition = '[$' . $child_stmt->dim->name . ']';

            return [null, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Expr\PropertyFetch
            && $child_stmt->dim->name instanceof PhpParser\Node\Identifier
        ) {
            $object_id = ExpressionIdentifier::getExtendedVarId(
                $child_stmt->dim->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );

            if ($object_id) {
                $var_id_addition = '[' . $object_id . '->' . $child_stmt->dim->name->name . ']';
            } else {
                $var_id_addition = '[' . $child_stmt_dim_type . ']';
            }

            return [null, $var_id_addition, true];
        }

        if ($child_stmt->dim instanceof PhpParser\Node\Expr\ClassConstFetch
            && $child_stmt->dim->name instanceof PhpParser\Node\Identifier
            && $child_stmt->dim->class instanceof PhpParser\Node\Name
        ) {
            $object_name = ClassLikeAnalyzer::getFQCLNFromNameObject(
                $child_stmt->dim->class,
                $statements_analyzer->getAliases(),
            );
            $var_id_addition = '[' . $object_name . '::' . $child_stmt->dim->name->name . ']';

            return [null, $var_id_addition, true];
        }

        $var_id_addition = '[' . $child_stmt_dim_type . ']';

        return [null, $var_id_addition, false];
    }
}
