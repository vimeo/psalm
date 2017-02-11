<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\Statements\Expression\AssertionFinder;
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
    public static function analyze(
        StatementsChecker $statements_checker,
        PhpParser\Node\Stmt\While_ $stmt,
        Context $context
    ) {
        $while_context = clone $context;

        if (ExpressionChecker::analyze($statements_checker, $stmt->cond, $while_context) === false) {
            return false;
        }

        $while_clauses = TypeChecker::getFormula(
            $stmt->cond,
            $context->self,
            $statements_checker
        );

        $while_context->clauses = TypeChecker::simplifyCNF(array_merge($context->clauses, $while_clauses));

        $reconcilable_while_types = TypeChecker::getTruthsFromFormula($while_context->clauses);

        // if the while has an or as the main component, we cannot safely reason about it
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp &&
            $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr
        ) {
            // do nothing
        } else {
            $changed_vars = [];

            $while_vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                $reconcilable_while_types,
                $while_context->vars_in_scope,
                $changed_vars,
                $statements_checker->getFileChecker(),
                new CodeLocation($statements_checker->getSource(), $stmt->cond),
                $statements_checker->getSuppressedIssues()
            );

            if ($while_vars_in_scope_reconciled === false) {
                return false;
            }

            $while_context->vars_in_scope = $while_vars_in_scope_reconciled;
        }

        if ($statements_checker->analyze($stmt->stmts, $while_context, $context) === false) {
            return false;
        }

        foreach ($context->vars_in_scope as $var => $type) {
            if ($type->isMixed()) {
                continue;
            }

            if ($while_context->hasVariable($var)) {
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

        if ($context->count_references) {
            $context->referenced_vars = array_merge(
                $context->referenced_vars,
                $while_context->referenced_vars
            );
        }

        return null;
    }
}
