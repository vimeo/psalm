<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\Context;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\TypeChecker;
use Psalm\Type;

class WhileChecker
{
    /**
     * @param   StatementsChecker           $statements_checker
     * @param   PhpParser\Node\Stmt\While_  $stmt
     * @param   Context                     $context
     * @return  false|null
     */
    public static function check(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\While_ $stmt,
        Context $context
    ) {
        $while_context = clone $context;

        if (ExpressionChecker::check($statements_checker, $stmt->cond, $while_context) === false) {
            return false;
        }

        $while_types = TypeChecker::getTypeAssertions(
            $stmt->cond,
            $statements_checker->getFullQualifiedClass(),
            $statements_checker->getNamespace(),
            $statements_checker->getAliasedClasses()
        );

        // if the while has an or as the main component, we cannot safely reason about it
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp &&
            $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
        ) {
            // do nothing
        } else {
            $while_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $while_types,
                $while_context->vars_in_scope,
                $statements_checker->getCheckedFileName(),
                $stmt->getLine(),
                $statements_checker->getSuppressedIssues()
            );

            if ($while_vars_in_scope_reconciled === false) {
                return false;
            }

            $while_context->vars_in_scope = $while_vars_in_scope_reconciled;
        }

        if ($statements_checker->check($stmt->stmts, $while_context, $context) === false) {
            return false;
        }

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if (isset($while_context->vars_in_scope[$var])) {
                if ($while_context->vars_in_scope[$var]->isMixed()) {
                    $context->vars_in_scope[$var] = $while_context->vars_in_scope[$var];
                }

                if ((string) $while_context->vars_in_scope[$var] !== (string) $type) {
                    $context->vars_in_scope[$var] = Type::combineUnionTypes($while_context->vars_in_scope[$var], $type);
                }
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $while_context->vars_possibly_in_scope
        );

        return null;
    }
}
