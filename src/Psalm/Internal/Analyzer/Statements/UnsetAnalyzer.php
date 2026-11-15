<?php

declare(strict_types=1);

namespace Psalm\Internal\Analyzer\Statements;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Internal\Analyzer\FunctionLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Assignment\ArrayAssignmentAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\DestructorAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\ExpressionIdentifier;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Internal\DataFlow\DataFlowNode;
use Psalm\Issue\ImpureVariable;
use Psalm\IssueBuffer;
use Psalm\Storage\Capabilities;
use Psalm\Type;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TIntRange;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TMixed;
use Psalm\Type\Atomic\TNever;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyMixed;
use Psalm\Type\Union;

use function array_key_first;
use function count;
use function is_int;
use function strtok;

/**
 * @internal
 */
final class UnsetAnalyzer
{
    /**
     * Marks an expression unset() is given, as opposed to one an unset expression goes through
     */
    public const TARGET_ATTRIBUTE = 'psalmUnsetTarget';

    /**
     * Marks an expression PHP fetches for unsetting rather than reads: the unset target, and the array or object
     * each element or property on its way is taken from. Accessing an element of null there is a silent no-op,
     * whereas offsets, call arguments, method receivers and dynamic names are read as usual
     */
    private const CHAIN_ATTRIBUTE = 'psalmUnsetChain';

    /**
     * Whether unset() fetches $stmt to remove something from it, so that its being null is not an error
     */
    public static function isOnTargetChain(Context $context, PhpParser\Node\Expr $stmt): bool
    {
        return $context->inside_unset && $stmt->getAttribute(self::CHAIN_ATTRIBUTE) === true;
    }

    /**
     * Whether unsetting writes the property the expression fetches: when it's the expression unset() removes, or
     * holds the array an element is removed from. An element of an object (an ArrayAccess) is removed by its
     * offsetUnset(), charged as that call, and the property keeps holding the same object
     */
    public static function writesFetchedProperty(Context $context, PhpParser\Node\Expr $stmt, ?Union $type): bool
    {
        return $context->inside_unset
            && ($stmt->getAttribute(self::TARGET_ATTRIBUTE) === true
                || $type === null
                || !$type->isObjectType());
    }

    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Stmt\Unset_ $stmt,
        Context $context,
    ): void {
        $context->inside_unset = true;

        foreach ($stmt->vars as $var) {
            $var->setAttribute(self::TARGET_ATTRIBUTE, true);

            $chain_node = $var;
            do {
                $chain_node->setAttribute(self::CHAIN_ATTRIBUTE, true);
                $chain_node = $chain_node instanceof PhpParser\Node\Expr\ArrayDimFetch
                    || $chain_node instanceof PhpParser\Node\Expr\PropertyFetch
                    ? $chain_node->var
                    : null;
            } while ($chain_node !== null);

            $was_inside_general_use = $context->inside_general_use;
            $context->inside_general_use = true;

            ExpressionAnalyzer::analyze($statements_analyzer, $var, $context);

            $context->inside_general_use = $was_inside_general_use;

            $var_id = ExpressionIdentifier::getExtendedVarId(
                $var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );

            if ($var_id) {
                if ($context->isMutationFree()
                    && (VariableFetchAnalyzer::isSuperGlobal((string) strtok($var_id, '['))
                        || isset($context->references_to_external_scope[$var_id])
                        || isset($context->referenced_globals[$var_id])
                    )
                ) {
                    IssueBuffer::maybeAdd(
                        new ImpureVariable(
                            'Cannot modify global ' . $var_id . ' in a '
                                . Capabilities::toString($context->capabilities) . ' context',
                            new CodeLocation($statements_analyzer->getSource(), $stmt),
                        ),
                        $statements_analyzer->getSuppressedIssues(),
                    );
                }

                $unset_type = $var instanceof PhpParser\Node\Expr\Variable
                    ? $context->vars_in_scope[$var_id] ?? null
                    : $statements_analyzer->node_data->getType($var);

                if ($unset_type !== null) {
                    // the object the variable, property or array element holds may die here
                    DestructorAnalyzer::chargeDestruction(
                        $statements_analyzer,
                        $context,
                        $unset_type,
                        $var_id,
                        $stmt,
                    );
                }

                FunctionLikeAnalyzer::unbindByRefParam($statements_analyzer->getCodebase(), $context, $var_id);

                $context->remove($var_id);
                unset($context->references_possibly_from_confusing_scope[$var_id]);
            }

            if ($var instanceof PhpParser\Node\Expr\ArrayDimFetch && $var->dim) {
                $root_var_id = ExpressionIdentifier::getExtendedVarId(
                    $var->var,
                    $statements_analyzer->getFQCLN(),
                    $statements_analyzer,
                );

                $key_type = $statements_analyzer->node_data->getType($var->dim);
                if ($root_var_id && isset($context->vars_in_scope[$root_var_id]) && $key_type) {
                    $root_types = [];

                    foreach ($context->vars_in_scope[$root_var_id]->getAtomicTypes() as $atomic_root_type) {
                        if ($atomic_root_type instanceof TKeyedArray) {
                            $key_value = null;
                            if ($key_type->isSingleIntLiteral()) {
                                $key_value = $key_type->getSingleIntLiteral()->value;
                            } elseif ($key_type->isSingleStringLiteral()) {
                                $key_value = $key_type->getSingleStringLiteral()->value;
                            }
                            if ($key_value !== null) {
                                $properties = $atomic_root_type->properties;
                                $is_list = $atomic_root_type->is_list;
                                $list_key = null;
                                if ($atomic_root_type->fallback_params) {
                                    $is_list = false;
                                } elseif (isset($properties[$key_value])) {
                                    if ($is_list
                                        && $key_value !== count($properties)-1
                                    ) {
                                        $is_list = false;
                                    }
                                }
                                unset($properties[$key_value]);

                                if ($atomic_root_type->is_list && !$is_list && is_int($key_value)) {
                                    if ($key_value === 0) {
                                        $list_key = Type::getIntRange(1, null);
                                    } elseif ($key_value === 1) {
                                        $list_key = new Union([
                                            new TLiteralInt(0),
                                            new TIntRange(2, null),
                                        ]);
                                    } else {
                                        $list_key = new Union([
                                            new TIntRange(0, $key_value-1),
                                            new TIntRange($key_value+1, null),
                                        ]);
                                    }
                                }

                                if (!$properties) {
                                    if ($atomic_root_type->fallback_params) {
                                        $root_types [] =
                                            new TArray([
                                                $list_key ?? $atomic_root_type->fallback_params[0],
                                                $atomic_root_type->fallback_params[1],
                                            ])
                                        ;
                                    } else {
                                        $root_types [] =
                                            new TArray([
                                                new Union([new TNever]),
                                                new Union([new TNever]),
                                            ])
                                        ;
                                    }
                                } else {
                                    $root_types []= TKeyedArray::make(
                                        $properties,
                                        null,
                                        $atomic_root_type->fallback_params ? [
                                            $list_key ?? $atomic_root_type->fallback_params[0],
                                            $atomic_root_type->fallback_params[1],
                                        ] : null,
                                        $is_list,
                                    );
                                }
                            } else {
                                $properties = [];
                                foreach ($atomic_root_type->properties as $key => $type) {
                                    $properties[$key] = $type->setPossiblyUndefined(true);
                                }
                                $root_types []= TKeyedArray::make(
                                    $properties,
                                    null,
                                    $atomic_root_type->fallback_params,
                                    false,
                                );
                            }
                        } elseif ($atomic_root_type instanceof TNonEmptyArray) {
                            $root_types []= new TArray($atomic_root_type->type_params);
                        } elseif ($atomic_root_type instanceof TNonEmptyMixed) {
                            $root_types []= new TMixed();
                        } else {
                            $root_types []= $atomic_root_type;
                        }
                    }

                    $context->vars_in_scope[$root_var_id] = self::taintUnsetOffset(
                        $statements_analyzer,
                        $var,
                        $root_var_id,
                        $context->vars_in_scope[$root_var_id],
                        new Union($root_types),
                        $key_type,
                    );
                    $context->possibly_assigned_var_ids[$root_var_id] = true;

                    $context->removeVarFromConflictingClauses(
                        $root_var_id,
                        $context->vars_in_scope[$root_var_id],
                        $statements_analyzer,
                    );

                    self::updateHoldingArrays(
                        $statements_analyzer,
                        $context,
                        $var->var,
                        $context->vars_in_scope[$root_var_id],
                    );
                }
            }
        }

        $context->inside_unset = false;
    }

    /**
     * Gives the arrays $child_expr is an element of, up to the root variable or property, the type they have once
     * $child_expr has become $child_type
     */
    private static function updateHoldingArrays(
        StatementsAnalyzer $statements_analyzer,
        Context $context,
        PhpParser\Node\Expr $child_expr,
        Union $child_type,
    ): void {
        $codebase = $statements_analyzer->getCodebase();

        while ($child_expr instanceof PhpParser\Node\Expr\ArrayDimFetch && $child_expr->dim) {
            $parent_var_id = ExpressionIdentifier::getExtendedVarId(
                $child_expr->var,
                $statements_analyzer->getFQCLN(),
                $statements_analyzer,
            );
            $key_type = $statements_analyzer->node_data->getType($child_expr->dim);

            if ($parent_var_id === null || $key_type === null || !isset($context->vars_in_scope[$parent_var_id])) {
                return;
            }

            $parent_type = $context->vars_in_scope[$parent_var_id];

            // reading a missing element gives null, but unsetting from it doesn't create it
            $child_type_without_null = $child_type;
            if ($child_type->isNull()) {
                $child_type_without_null = Type::getNever();
            } elseif ($child_type->hasNull()) {
                $child_type_builder = $child_type->getBuilder();
                $child_type_builder->removeType('null');
                $child_type_without_null = $child_type_builder->freeze();
            }

            // the keys the element may be under, null when it may be under any
            $keys = [];
            foreach ($key_type->getAtomicTypes() as $atomic_key_type) {
                if (!$atomic_key_type instanceof TLiteralInt && !$atomic_key_type instanceof TLiteralString) {
                    $keys = null;
                    break;
                }
                $keys[$atomic_key_type->value] = true;
            }

            $parent_atomic_types = [];
            $changed = false;
            foreach ($parent_type->getAtomicTypes() as $atomic_parent_type) {
                if ($atomic_parent_type instanceof TKeyedArray) {
                    $changed = true;
                    $properties = $atomic_parent_type->properties;
                    $fallback_params = $atomic_parent_type->fallback_params;
                    $key = $keys !== null && count($keys) === 1 ? array_key_first($keys) : null;

                    if ($key !== null && isset($properties[$key])) {
                        $old_property = $properties[$key];
                        $properties[$key] = ($old_property->hasNull() ? $child_type : $child_type_without_null)
                            ->setPossiblyUndefined($old_property->possibly_undefined);
                    } else {
                        foreach ($properties as $property_key => $property) {
                            if ($keys === null || isset($keys[$property_key])) {
                                $properties[$property_key] = Type::combineUnionTypes(
                                    $property,
                                    $child_type_without_null,
                                    $codebase,
                                )->setPossiblyUndefined($property->possibly_undefined);
                            }
                        }

                        if ($fallback_params !== null) {
                            $fallback_params[1] = Type::combineUnionTypes(
                                $fallback_params[1],
                                $child_type_without_null,
                                $codebase,
                            );
                        }
                    }

                    $atomic_parent_type = TKeyedArray::make(
                        $properties,
                        $atomic_parent_type->class_strings,
                        $fallback_params,
                        $atomic_parent_type->is_list,
                        $atomic_parent_type->from_docblock,
                    );
                } elseif ($atomic_parent_type instanceof TArray && !$atomic_parent_type->type_params[1]->isNever()) {
                    $changed = true;
                    $atomic_parent_type = $atomic_parent_type->setTypeParams([
                        $atomic_parent_type->type_params[0],
                        Type::combineUnionTypes(
                            $atomic_parent_type->type_params[1],
                            $child_type_without_null,
                            $codebase,
                        ),
                    ]);
                }

                $parent_atomic_types[] = $atomic_parent_type;
            }

            // an ArrayAccess object holds the element, and stays the same object
            if (!$changed) {
                return;
            }

            $parent_type = $parent_type->getBuilder()->setTypes($parent_atomic_types)->freeze();

            $context->vars_in_scope[$parent_var_id] = $parent_type;
            $context->possibly_assigned_var_ids[$parent_var_id] = true;
            $context->removeVarFromConflictingClauses($parent_var_id, $parent_type, $statements_analyzer);

            $child_expr = $child_expr->var;
            $child_type = $parent_type;
        }
    }

    /**
     * The array $root_var_id is once its offset $var is unset, of type $new_type: what it held under its other keys
     * flows into it, not what it held under that key (see ArrayAssignmentAnalyzer::getOverwritePathType()). Only
     * for a single literal key: the graphs are left as they were for any other.
     */
    private static function taintUnsetOffset(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $var,
        string $root_var_id,
        Union $old_type,
        Union $new_type,
        Union $key_type,
    ): Union {
        $graph = $statements_analyzer->getDataFlowGraphWithSuppressed();

        if ($key_type->isSingleStringLiteral()) {
            $key = $key_type->getSingleStringLiteral();
        } elseif ($key_type->isSingleIntLiteral()) {
            $key = $key_type->getSingleIntLiteral();
        } else {
            return $new_type;
        }

        if ($graph === null || $old_type->parent_nodes === []) {
            return $new_type;
        }

        $node = DataFlowNode::getForAssignment(
            $root_var_id,
            new CodeLocation($statements_analyzer->getSource(), $var->var),
        );

        $graph->addNode($node);

        $path_type = ArrayAssignmentAnalyzer::getOverwritePathType($old_type, $key);

        foreach ($old_type->parent_nodes as $parent_node) {
            $graph->addPath($parent_node, $node, $path_type);
        }

        return $new_type->setParentNodes([$node->id => $node]);
    }
}
