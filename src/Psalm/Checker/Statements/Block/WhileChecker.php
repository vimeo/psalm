<?php
namespace Psalm\Checker\Statements\Block;

use PhpParser;
use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\Checker\AlgebraChecker;
use Psalm\Checker\Statements\ExpressionChecker;
use Psalm\Checker\Statements\Expression\AssertionFinder;
use Psalm\Checker\StatementsChecker;
use Psalm\Checker\ScopeChecker;
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

        $while_context->inside_conditional = true;
        if (ExpressionChecker::analyze($statements_checker, $stmt->cond, $while_context) === false) {
            return false;
        }
        $while_context->inside_conditional = false;

        $while_clauses = AlgebraChecker::getFormula(
            $stmt->cond,
            $context->self,
            $statements_checker
        );

        $while_context->parent_context = $context;

        $while_context->clauses = AlgebraChecker::simplifyCNF(array_merge($context->clauses, $while_clauses));

        $reconcilable_while_types = AlgebraChecker::getTruthsFromFormula($while_context->clauses);

        // if the while has an or as the main component, we cannot safely reason about it
        if ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp &&
            ($stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\BooleanOr ||
                $stmt->cond instanceof PhpParser\Node\Expr\BinaryOp\LogicalOr)
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

        $asserted_while_vars = array_keys(AlgebraChecker::getTruthsFromFormula($while_clauses));

        if ($statements_checker->analyzeLoop($stmt->stmts, $asserted_while_vars, $while_context, $context) === false) {
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

        if (!ScopeChecker::doesEverBreak($stmt->stmts)) {
            // if the while contains an assertion and there are no break statements, we can negate that assertion
            // and apply it to the current context
            $negated_while_types = AlgebraChecker::getTruthsFromFormula(AlgebraChecker::negateFormula($while_clauses));

            if ($negated_while_types) {
                $changed_vars = [];
                $vars_in_scope_reconciled = TypeChecker::reconcileKeyedTypes(
                    $negated_while_types,
                    $context->vars_in_scope,
                    $changed_vars,
                    $statements_checker->getFileChecker(),
                    new CodeLocation($statements_checker->getSource(), $stmt->cond),
                    $statements_checker->getSuppressedIssues()
                );

                if ($vars_in_scope_reconciled === false) {
                    return false;
                }

                $context->vars_in_scope = $vars_in_scope_reconciled;

                foreach ($changed_vars as $changed_var) {
                    $context->removeVarFromConflictingClauses($changed_var);
                }
            }
        }

        $context->vars_possibly_in_scope = array_merge(
            $context->vars_possibly_in_scope,
            $while_context->vars_possibly_in_scope
        );

        if ($context->collect_references) {
            $context->referenced_vars = array_merge(
                $context->referenced_vars,
                $while_context->referenced_vars
            );
        }

        return null;
    }
}
