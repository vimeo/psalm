<?php
namespace Psalm\Internal\Analyzer\Statements\Expression\Assignment;

use PhpParser;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ArrayFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\ExpressionAnalyzer;
use Psalm\Internal\Analyzer\StatementsAnalyzer;
use Psalm\Context;
use Psalm\IssueBuffer;
use Psalm\Issue\InvalidArrayAssignment;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;
use Psalm\Type\Atomic\TList;
use Psalm\Type\Atomic\TNonEmptyArray;
use Psalm\Type\Atomic\TNonEmptyList;
use function array_reverse;
use function array_shift;
use function count;
use function array_unshift;
use function preg_match;
use function is_string;
use function implode;
use function array_pop;

/**
 * @internal
 */
class ArrayAssignmentAnalyzer
{
    /**
     * @param   StatementsAnalyzer                  $statements_analyzer
     * @param   PhpParser\Node\Expr\ArrayDimFetch   $stmt
     * @param   Context                             $context
     * @param   PhpParser\Node\Expr|null            $assign_value
     * @param   Type\Union                          $assignment_value_type
     *
     * @return  void
     */
    public static function analyze(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        $assign_value,
        Type\Union $assignment_value_type
    ) {
        $nesting = 0;
        $var_id = ExpressionAnalyzer::getVarId(
            $stmt->var,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer,
            $nesting
        );

        self::updateArrayType(
            $statements_analyzer,
            $stmt,
            $assign_value,
            $assignment_value_type,
            $context
        );

        if (!isset($stmt->var->inferredType) && $var_id) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }
    }

    /**
     *
     * @return false|null
     */
    public static function updateArrayType(
        StatementsAnalyzer $statements_analyzer,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        ?PhpParser\Node\Expr $assign_value,
        Type\Union $assignment_type,
        Context $context
    ) {
        $root_array_expr = $stmt;

        $child_stmts = [];

        while ($root_array_expr->var instanceof PhpParser\Node\Expr\ArrayDimFetch) {
            $child_stmts[] = $root_array_expr;
            $root_array_expr = $root_array_expr->var;
        }

        $child_stmts[] = $root_array_expr;
        $root_array_expr = $root_array_expr->var;

        if (ExpressionAnalyzer::analyze(
            $statements_analyzer,
            $root_array_expr,
            $context,
            true
        ) === false) {
            // fall through
        }

        $codebase = $statements_analyzer->getCodebase();

        $root_type = isset($root_array_expr->inferredType) ? $root_array_expr->inferredType : Type::getMixed();

        if ($root_type->hasMixed()) {
            if (ExpressionAnalyzer::analyze(
                $statements_analyzer,
                $stmt->var,
                $context,
                true
            ) === false) {
                // fall through
            }

            if ($stmt->dim) {
                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $stmt->dim,
                    $context
                ) === false) {
                    // fall through
                }
            }

            return null;
        }

        $child_stmts = array_reverse($child_stmts);

        $current_type = $root_type;

        $current_dim = $stmt->dim;

        $reversed_child_stmts = [];

        // gets a variable id that *may* contain array keys
        $root_var_id = ExpressionAnalyzer::getRootVarId(
            $root_array_expr,
            $statements_analyzer->getFQCLN(),
            $statements_analyzer
        );

        $var_id_additions = [];

        $parent_var_id = null;

        $full_var_id = true;

        $child_stmt = null;

        $taint_sources = [];
        $taint_type = 0;

        if ($codebase->taint && isset($assign_value->inferredType)) {
            $taint_sources = $assign_value->inferredType->sources;
            $taint_type = $assign_value->inferredType->tainted ?: 0;
        }

        // First go from the root element up, and go as far as we can to figure out what
        // array types there are
        while ($child_stmts) {
            $child_stmt = array_shift($child_stmts);

            if (count($child_stmts)) {
                array_unshift($reversed_child_stmts, $child_stmt);
            }

            if ($child_stmt->dim) {
                if (ExpressionAnalyzer::analyze(
                    $statements_analyzer,
                    $child_stmt->dim,
                    $context
                ) === false) {
                    return false;
                }

                if (!isset($child_stmt->dim->inferredType)) {
                    return null;
                }

                if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_
                    || ($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                       && $child_stmt->dim->inferredType->isSingleStringLiteral())
                ) {
                    if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                        $value = $child_stmt->dim->value;
                    } else {
                        $value = $child_stmt->dim->inferredType->getSingleStringLiteral()->value;
                    }

                    if (preg_match('/^(0|[1-9][0-9]*)$/', $value)) {
                        $var_id_additions[] = '[' . $value . ']';
                    }
                    $var_id_additions[] = '[\'' . $value . '\']';
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber
                    || ($child_stmt->dim instanceof PhpParser\Node\Expr\ConstFetch
                        && $child_stmt->dim->inferredType->isSingleIntLiteral())
                ) {
                    if ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber) {
                        $value = $child_stmt->dim->value;
                    } else {
                        $value = $child_stmt->dim->inferredType->getSingleIntLiteral()->value;
                    }

                    $var_id_additions[] = '[' . $value . ']';
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Expr\Variable
                    && is_string($child_stmt->dim->name)
                ) {
                    $var_id_additions[] = '[$' . $child_stmt->dim->name . ']';
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Expr\PropertyFetch
                    && $child_stmt->dim->name instanceof PhpParser\Node\Identifier
                ) {
                    $object_id = ExpressionAnalyzer::getArrayVarId(
                        $child_stmt->dim->var,
                        $statements_analyzer->getFQCLN(),
                        $statements_analyzer
                    );

                    if ($object_id) {
                        $var_id_additions[] = '[' . $object_id . '->' . $child_stmt->dim->name->name . ']';
                    }
                } else {
                    $var_id_additions[] = '[' . $child_stmt->dim->inferredType . ']';
                    $full_var_id = false;
                }
            } else {
                $var_id_additions[] = '';
                $full_var_id = false;
            }

            if (!isset($child_stmt->var->inferredType)) {
                return null;
            }

            if ($child_stmt->var->inferredType->isEmpty()) {
                $child_stmt->var->inferredType = Type::getEmptyArray();
            }

            $array_var_id = $root_var_id . implode('', $var_id_additions);

            if ($parent_var_id && isset($context->vars_in_scope[$parent_var_id])) {
                $child_stmt->var->inferredType = clone $context->vars_in_scope[$parent_var_id];
            }

            $array_type = clone $child_stmt->var->inferredType;

            $child_stmt->inferredType = ArrayFetchAnalyzer::getArrayAccessTypeGivenOffset(
                $statements_analyzer,
                $child_stmt,
                $array_type,
                isset($child_stmt->dim->inferredType) ? $child_stmt->dim->inferredType : Type::getInt(),
                true,
                $array_var_id,
                $context,
                $assign_value,
                $child_stmts ? null : $assignment_type
            );

            $child_stmt->var->inferredType = $array_type;

            if ($root_var_id) {
                if (!$parent_var_id) {
                    $rooted_parent_id = $root_var_id;
                    $root_type = $array_type;
                } else {
                    $rooted_parent_id = $parent_var_id;
                }

                $context->vars_in_scope[$rooted_parent_id] = $array_type;
            }

            if (!$child_stmts) {
                $child_stmt->inferredType = $assignment_type;
            }

            $current_type = $child_stmt->inferredType;
            $current_dim = $child_stmt->dim;

            $parent_var_id = $array_var_id;

            if ($child_stmt->var->inferredType->hasMixed()) {
                $full_var_id = false;

                while ($child_stmts) {
                    $child_stmt = array_shift($child_stmts);

                    if ($child_stmt->dim) {
                        if (ExpressionAnalyzer::analyze(
                            $statements_analyzer,
                            $child_stmt->dim,
                            $context
                        ) === false) {
                            return false;
                        }
                    }
                }

                break;
            }
        }

        if ($root_var_id
            && $full_var_id
            && isset($child_stmt->var->inferredType)
            && !$child_stmt->var->inferredType->hasObjectType()
        ) {
            $array_var_id = $root_var_id . implode('', $var_id_additions);
            $context->vars_in_scope[$array_var_id] = clone $assignment_type;
        }

        // only update as many child stmts are we were able to process above
        foreach ($reversed_child_stmts as $child_stmt) {
            if (!isset($child_stmt->inferredType)) {
                throw new \InvalidArgumentException('Should never get here');
            }

            $key_value = null;

            if ($current_dim instanceof PhpParser\Node\Scalar\String_
                || $current_dim instanceof PhpParser\Node\Scalar\LNumber
            ) {
                $key_value = $current_dim->value;
            } elseif ($current_dim instanceof PhpParser\Node\Expr\ConstFetch
                && isset($current_dim->inferredType)
            ) {
                $is_single_string_literal = $current_dim->inferredType->isSingleStringLiteral();

                if ($is_single_string_literal || $current_dim->inferredType->isSingleIntLiteral()) {
                    if ($is_single_string_literal) {
                        $key_value = $current_dim->inferredType->getSingleStringLiteral()->value;
                    } else {
                        $key_value = $current_dim->inferredType->getSingleIntLiteral()->value;
                    }
                }
            }

            if ($key_value !== null) {
                $has_matching_objectlike_property = false;
                $has_matching_string = false;

                foreach ($child_stmt->inferredType->getTypes() as $type) {
                    if ($type instanceof ObjectLike) {
                        if (isset($type->properties[$key_value])) {
                            $has_matching_objectlike_property = true;

                            $type->properties[$key_value] = clone $current_type;
                        }
                    }

                    if ($type instanceof Type\Atomic\TString && \is_int($key_value)) {
                        $has_matching_string = true;

                        if ($type instanceof Type\Atomic\TLiteralString
                            && $current_type->isSingleStringLiteral()
                        ) {
                            $new_char = $current_type->getSingleStringLiteral()->value;

                            if (\strlen($new_char) === 1) {
                                $type->value[0] = $new_char;
                            }
                        }
                    }
                }

                if (!$has_matching_objectlike_property && !$has_matching_string) {
                    $array_assignment_type = new Type\Union([
                        new ObjectLike([$key_value => $current_type]),
                    ]);

                    $new_child_type = Type::combineUnionTypes(
                        $child_stmt->inferredType,
                        $array_assignment_type,
                        $codebase,
                        true,
                        false
                    );
                } else {
                    $new_child_type = $child_stmt->inferredType; // noop
                }
            } else {
                if (!$current_dim) {
                    $array_assignment_type = new Type\Union([
                        new TList($current_type),
                    ]);
                } else {
                    $array_assignment_type = new Type\Union([
                        new TArray([
                            $current_dim->inferredType ?? Type::getMixed(),
                            $current_type,
                        ]),
                    ]);
                }

                $new_child_type = Type::combineUnionTypes(
                    $child_stmt->inferredType,
                    $array_assignment_type,
                    $codebase,
                    true,
                    false
                );
            }

            $new_child_type->removeType('null');
            $new_child_type->possibly_undefined = false;

            if (!$child_stmt->inferredType->hasObjectType()) {
                $child_stmt->inferredType = $new_child_type;
            }

            $current_type = $child_stmt->inferredType;
            $current_dim = $child_stmt->dim;

            array_pop($var_id_additions);

            if ($root_var_id) {
                $array_var_id = $root_var_id . implode('', $var_id_additions);
                $context->vars_in_scope[$array_var_id] = clone $child_stmt->inferredType;
            }
        }

        $root_is_string = $root_type->isString();
        $key_value = null;

        if ($current_dim instanceof PhpParser\Node\Scalar\String_
            || ($current_dim instanceof PhpParser\Node\Scalar\LNumber && !$root_is_string)
        ) {
            $key_value = $current_dim->value;
        } elseif ($current_dim instanceof PhpParser\Node\Expr\ConstFetch
            && isset($current_dim->inferredType)
            && !$root_is_string
        ) {
            $is_single_string_literal = $current_dim->inferredType->isSingleStringLiteral();

            if ($is_single_string_literal || $current_dim->inferredType->isSingleIntLiteral()) {
                if ($is_single_string_literal) {
                    $key_value = $current_dim->inferredType->getSingleStringLiteral()->value;
                } else {
                    $key_value = $current_dim->inferredType->getSingleIntLiteral()->value;
                }
            }
        }

        if ($key_value !== null) {
            $has_matching_objectlike_property = false;

            foreach ($root_type->getTypes() as $type) {
                if ($type instanceof ObjectLike) {
                    if (isset($type->properties[$key_value])) {
                        $has_matching_objectlike_property = true;

                        $type->properties[$key_value] = clone $current_type;
                    }
                } elseif ($type instanceof TNonEmptyList && $key_value === 0) {
                    $has_matching_objectlike_property = true;

                    $type->type_param = Type::combineUnionTypes(
                        clone $current_type,
                        $type->type_param,
                        $codebase,
                        true,
                        false
                    );
                }
            }

            if (!$has_matching_objectlike_property) {
                $object_like = new ObjectLike([$key_value => $current_type]);
                $object_like->sealed = true;

                $array_assignment_type = new Type\Union([
                    $object_like,
                ]);

                $new_child_type = Type::combineUnionTypes(
                    $root_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    false
                );
            } else {
                $new_child_type = $root_type; // noop
            }
        } elseif (!$root_is_string) {
            if ($current_dim) {
                if (isset($current_dim->inferredType)) {
                    $array_atomic_key_type = ArrayFetchAnalyzer::replaceOffsetTypeWithInts(
                        $current_dim->inferredType
                    );
                } else {
                    $array_atomic_key_type = Type::getMixed();
                }

                $array_atomic_type = new TNonEmptyArray([
                    $array_atomic_key_type,
                    $current_type,
                ]);
            } else {
                $array_atomic_type = new TNonEmptyList($current_type);
            }

            $from_countable_object_like = false;

            $new_child_type = null;

            if (!$current_dim && !$context->inside_loop) {
                $atomic_root_types = $root_type->getTypes();

                if (isset($atomic_root_types['array'])) {
                    if ($atomic_root_types['array'] instanceof TNonEmptyArray
                        || $atomic_root_types['array'] instanceof TNonEmptyList
                    ) {
                        $array_atomic_type->count = $atomic_root_types['array']->count;
                    } elseif ($atomic_root_types['array'] instanceof ObjectLike
                        && $atomic_root_types['array']->sealed
                    ) {
                        $array_atomic_type->count = count($atomic_root_types['array']->properties);
                        $from_countable_object_like = true;

                        if ($atomic_root_types['array']->is_list
                            && $array_atomic_type instanceof TList
                        ) {
                            $array_atomic_type = clone $atomic_root_types['array'];

                            $new_child_type = new Type\Union([$array_atomic_type]);
                        }
                    } elseif ($array_atomic_type instanceof TList) {
                        $array_atomic_type = new TNonEmptyList(
                            $array_atomic_type->type_param
                        );
                    } else {
                        $array_atomic_type = new TNonEmptyArray(
                            $array_atomic_type->type_params
                        );
                    }
                }
            }

            $array_assignment_type = new Type\Union([
                $array_atomic_type,
            ]);

            if (!$new_child_type) {
                $new_child_type = Type::combineUnionTypes(
                    $root_type,
                    $array_assignment_type,
                    $codebase,
                    true,
                    false
                );
            }

            if ($from_countable_object_like) {
                $atomic_root_types = $new_child_type->getTypes();

                if (isset($atomic_root_types['array'])
                    && ($atomic_root_types['array'] instanceof TNonEmptyArray
                        || $atomic_root_types['array'] instanceof TNonEmptyList)
                    && $atomic_root_types['array']->count !== null
                ) {
                    $atomic_root_types['array']->count++;
                }
            }
        } else {
            $new_child_type = $root_type;
        }

        $new_child_type->removeType('null');

        if (!$root_type->hasObjectType()) {
            $root_type = $new_child_type;
        }

        if ($codebase->taint && $taint_sources) {
            $root_type->sources = \array_merge($taint_sources, $root_type->sources ?: []);
            $root_type->tainted = $taint_type | $root_type->tainted;
        }

        $root_array_expr->inferredType = $root_type;

        if ($root_array_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            if ($root_array_expr->name instanceof PhpParser\Node\Identifier) {
                PropertyAssignmentAnalyzer::analyzeInstance(
                    $statements_analyzer,
                    $root_array_expr,
                    $root_array_expr->name->name,
                    null,
                    $root_type,
                    $context,
                    false
                );
            } else {
                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->name, $context) === false) {
                    return false;
                }

                if (ExpressionAnalyzer::analyze($statements_analyzer, $root_array_expr->var, $context) === false) {
                    return false;
                }
            }
        } elseif ($root_var_id) {
            $context->vars_in_scope[$root_var_id] = $root_type;
        }

        if ($root_array_expr instanceof PhpParser\Node\Expr\MethodCall
            || $root_array_expr instanceof PhpParser\Node\Expr\StaticCall
            || $root_array_expr instanceof PhpParser\Node\Expr\FuncCall
        ) {
            if ($root_type->hasArray()) {
                if (IssueBuffer::accepts(
                    new InvalidArrayAssignment(
                        'Assigning to the output of a function has no effect',
                        new \Psalm\CodeLocation($statements_analyzer->getSource(), $root_array_expr)
                    ),
                    $statements_analyzer->getSuppressedIssues()
                )
                ) {
                    // do nothing
                }
            }
        }

        return null;
    }
}
