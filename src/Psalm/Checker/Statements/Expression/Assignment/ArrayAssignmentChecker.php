<?php
namespace Psalm\Checker\Statements\Expression\Assignment;

use PhpParser;
use Psalm\Checker\Statements\Expression\Fetch\ArrayFetchChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Context;
use Psalm\Type;
use Psalm\Type\Atomic\ObjectLike;
use Psalm\Type\Atomic\TArray;

class ArrayAssignmentChecker
{
    /**
     * @param   StatementsChecker                   $statements_checker
     * @param   PhpParser\Node\Expr\ArrayDimFetch   $stmt
     * @param   Context                             $context
     * @param   Type\Union                          $assignment_value_type
     *
     * @return  void
     * @psalm-suppress MixedMethodCall - some funky logic here
     */
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
        Context $context,
        Type\Union $assignment_value_type
    ) {
        $nesting = 0;
        $var_id = ExpressionChecker::getVarId(
            $stmt->var,
            $statements_checker->getFQCLN(),
            $statements_checker,
            $nesting
        );

        self::updateArrayType(
            $statements_checker,
            $stmt,
            $assignment_value_type,
            $context
        );

        if (!isset($stmt->var->inferredType) && $var_id) {
            $context->vars_in_scope[$var_id] = Type::getMixed();
        }
    }

    /**
     * @param  StatementsChecker                 $statements_checker
     * @param  PhpParser\Node\Expr\ArrayDimFetch $stmt
     * @param  Type\Union                        $assignment_type
     * @param  Context                           $context
     *
     * @return false|null
     */
    public static function updateArrayType(
        StatementsChecker $statements_checker,
        PhpParser\Node\Expr\ArrayDimFetch $stmt,
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

        if (ExpressionChecker::analyze(
            $statements_checker,
            $root_array_expr,
            $context,
            true
        ) === false) {
            // fall through
        }

        $root_type = isset($root_array_expr->inferredType) ? $root_array_expr->inferredType : Type::getMixed();

        if ($root_type->isMixed()) {
            return null;
        }

        $child_stmts = array_reverse($child_stmts);

        $current_type = $root_type;

        $current_dim = $stmt->dim;

        $reversed_child_stmts = [];

        // gets a variable id that *may* contain array keys
        $root_var_id = ExpressionChecker::getRootVarId(
            $root_array_expr,
            $statements_checker->getFQCLN(),
            $statements_checker
        );

        $var_id_additions = [];

        $full_var_id = true;

        $child_stmt = null;

        // First go from the root element up, and go as far as we can to figure out what
        // array types there are
        while ($child_stmts) {
            $child_stmt = array_shift($child_stmts);

            if (count($child_stmts)) {
                array_unshift($reversed_child_stmts, $child_stmt);
            }

            if ($child_stmt->dim) {
                if (ExpressionChecker::analyze(
                    $statements_checker,
                    $child_stmt->dim,
                    $context
                ) === false) {
                    return false;
                }

                if (!isset($child_stmt->dim->inferredType)) {
                    return null;
                }

                if ($child_stmt->dim instanceof PhpParser\Node\Scalar\String_) {
                    $var_id_additions[] = '[\'' . $child_stmt->dim->value . '\']';
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Scalar\LNumber) {
                    $var_id_additions[] = '[' . $child_stmt->dim->value . ']';
                } elseif ($child_stmt->dim instanceof PhpParser\Node\Expr\Variable
                    && is_string($child_stmt->dim->name)
                ) {
                    $var_id_additions[] = '[$' . $child_stmt->dim->name . ']';
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

            $child_stmt->inferredType = ArrayFetchChecker::getArrayAccessTypeGivenOffset(
                $statements_checker,
                $child_stmt,
                $child_stmt->var->inferredType,
                isset($child_stmt->dim->inferredType) ? $child_stmt->dim->inferredType : Type::getInt(),
                true,
                $array_var_id,
                $child_stmts ? null : $assignment_type
            );

            if (!$child_stmts) {
                $child_stmt->inferredType = $assignment_type;
            }

            $current_type = $child_stmt->inferredType;
            $current_dim = $child_stmt->dim;

            if ($child_stmt->var->inferredType->isMixed()) {
                $full_var_id = false;
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

            if ($current_dim instanceof PhpParser\Node\Scalar\String_
                || $current_dim instanceof PhpParser\Node\Scalar\LNumber
            ) {
                $key_value = $current_dim->value;

                $has_matching_objectlike_property = false;

                foreach ($child_stmt->inferredType->getTypes() as $type) {
                    if ($type instanceof ObjectLike) {
                        if (isset($type->properties[$key_value])) {
                            $has_matching_objectlike_property = true;

                            $type->properties[$key_value] = clone $current_type;
                        }
                    }
                }

                if (!$has_matching_objectlike_property) {
                    $array_assignment_type = new Type\Union([
                        new ObjectLike([$key_value => $current_type]),
                    ]);

                    $new_child_type = Type::combineUnionTypes(
                        $child_stmt->inferredType,
                        $array_assignment_type
                    );
                } else {
                    $new_child_type = $child_stmt->inferredType; // noop
                }
            } else {
                $array_assignment_type = new Type\Union([
                    new TArray([
                        isset($current_dim->inferredType) ? $current_dim->inferredType : Type::getInt(),
                        $current_type,
                    ]),
                ]);

                $new_child_type = Type::combineUnionTypes(
                    $child_stmt->inferredType,
                    $array_assignment_type
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

        $root_is_string = array_keys($root_type->getTypes()) === ['string'];

        if (($current_dim instanceof PhpParser\Node\Scalar\String_
                || $current_dim instanceof PhpParser\Node\Scalar\LNumber)
            && ($current_dim instanceof PhpParser\Node\Scalar\String_
                || !$root_is_string)
        ) {
            $key_value = $current_dim->value;

            $has_matching_objectlike_property = false;

            foreach ($root_type->getTypes() as $type) {
                if ($type instanceof ObjectLike) {
                    if (isset($type->properties[$key_value])) {
                        $has_matching_objectlike_property = true;

                        $type->properties[$key_value] = clone $current_type;
                    }
                }
            }

            if (!$has_matching_objectlike_property) {
                $array_assignment_type = new Type\Union([
                    new ObjectLike([$key_value => $current_type]),
                ]);

                $new_child_type = Type::combineUnionTypes(
                    $root_type,
                    $array_assignment_type
                );
            } else {
                $new_child_type = $root_type; // noop
            }
        } elseif (!$root_is_string) {
            $array_assignment_type = new Type\Union([
                new TArray([
                    isset($current_dim->inferredType) ? $current_dim->inferredType : Type::getInt(),
                    $current_type,
                ]),
            ]);

            $new_child_type = Type::combineUnionTypes(
                $root_type,
                $array_assignment_type
            );
        } else {
            $new_child_type = $root_type;
        }

        $new_child_type->removeType('null');

        if (!$root_type->hasObjectType()) {
            $root_type = $new_child_type;
        }

        $root_array_expr->inferredType = $root_type;

        if ($root_array_expr instanceof PhpParser\Node\Expr\PropertyFetch) {
            if ($root_array_expr->name instanceof PhpParser\Node\Identifier) {
                PropertyAssignmentChecker::analyzeInstance(
                    $statements_checker,
                    $root_array_expr,
                    $root_array_expr->name->name,
                    null,
                    $root_type,
                    $context,
                    false
                );
            } else {
                if (ExpressionChecker::analyze($statements_checker, $root_array_expr->name, $context) === false) {
                    return false;
                }

                if (ExpressionChecker::analyze($statements_checker, $root_array_expr->var, $context) === false) {
                    return false;
                }
            }
        } elseif ($root_var_id) {
            if ($context->hasVariable($root_var_id, $statements_checker)) {
                $context->vars_in_scope[$root_var_id] = $root_type;
            } else {
                $context->vars_in_scope[$root_var_id] = $root_type;
            }
        }

        return null;
    }
}
