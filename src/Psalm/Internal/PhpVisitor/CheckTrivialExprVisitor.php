<?php

namespace Psalm\Internal\PhpVisitor;

use PhpParser;

/**
 * @internal
 */
final class CheckTrivialExprVisitor extends PhpParser\NodeVisitorAbstract
{
    private bool $has_non_trivial_expr = false;

    private function checkNonTrivialExpr(PhpParser\Node\Expr $node): bool
    {
        if ($node instanceof PhpParser\Node\Expr\ArrayDimFetch
            || $node instanceof PhpParser\Node\Expr\Closure
            || $node instanceof PhpParser\Node\Expr\ClosureUse
            || $node instanceof PhpParser\Node\Expr\Eval_
            || $node instanceof PhpParser\Node\Expr\Exit_
            || $node instanceof PhpParser\Node\Expr\Include_
            || $node instanceof PhpParser\Node\Expr\FuncCall
            || $node instanceof PhpParser\Node\Expr\MethodCall
            || $node instanceof PhpParser\Node\Expr\ArrowFunction
            || $node instanceof PhpParser\Node\Expr\ShellExec
            || $node instanceof PhpParser\Node\Expr\StaticCall
            || $node instanceof PhpParser\Node\Expr\Yield_
            || $node instanceof PhpParser\Node\Expr\YieldFrom
            || $node instanceof PhpParser\Node\Expr\New_
            || $node instanceof PhpParser\Node\Expr\Cast\String_
        ) {
            if (($node instanceof PhpParser\Node\Expr\FuncCall
                    || $node instanceof PhpParser\Node\Expr\MethodCall
                    || $node instanceof PhpParser\Node\Expr\StaticCall)
                && $node->getAttribute('pure', false)
            ) {
                return false;
            }

            if ($node instanceof PhpParser\Node\Expr\New_ && $node->getAttribute('external_mutation_free', false)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function enterNode(PhpParser\Node $node): ?int
    {
        if ($node instanceof PhpParser\Node\Expr) {
            // Check for Non-Trivial Expression first
            if ($this->checkNonTrivialExpr($node)) {
                $this->has_non_trivial_expr = true;
                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
            }

            if ($node instanceof PhpParser\Node\Expr\ClassConstFetch
                || $node instanceof PhpParser\Node\Expr\ConstFetch
                || $node instanceof PhpParser\Node\Expr\Error
                || $node instanceof PhpParser\Node\Expr\PropertyFetch
                || $node instanceof PhpParser\Node\Expr\StaticPropertyFetch) {
                return PhpParser\NodeTraverser::STOP_TRAVERSAL;
            }
        }
        return null;
    }

    public function hasNonTrivialExpr(): bool
    {
        return $this->has_non_trivial_expr;
    }
}
